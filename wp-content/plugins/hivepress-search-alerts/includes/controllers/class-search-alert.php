<?php
/**
 * Search alert controller.
 *
 * @package HivePress\Controllers
 */

namespace HivePress\Controllers;

use HivePress\Helpers as hp;
use HivePress\Forms;
use HivePress\Models;
use HivePress\Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Search alert controller class.
 *
 * @class Search_Alert
 */
final class Search_Alert extends Controller {

	/**
	 * Class constructor.
	 *
	 * @param array $args Controller arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'routes' => [
					'search_alerts_resource'     => [
						'path' => '/search-alerts',
						'rest' => true,
					],

					'search_alert_resource'      => [
						'base' => 'search_alerts_resource',
						'path' => '/(?P<search_alert_id>\d+)',
						'rest' => true,
					],

					'search_alert_update_action' => [
						'base'   => 'search_alerts_resource',
						'method' => 'POST',
						'action' => [ $this, 'update_search_alert' ],
						'rest'   => true,
					],

					'search_alert_delete_action' => [
						'base'   => 'search_alert_resource',
						'method' => 'DELETE',
						'action' => [ $this, 'delete_search_alert' ],
						'rest'   => true,
					],

					'search_alerts_view_page'    => [
						'title'    => esc_html__( 'Searches', 'hivepress-search-alerts' ),
						'base'     => 'user_account_page',
						'path'     => '/searches',
						'redirect' => [ $this, 'redirect_search_alerts_view_page' ],
						'action'   => [ $this, 'render_search_alerts_view_page' ],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Updates search alert.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function update_search_alert( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get form.
		$form = hivepress()->search_alert->get_model_form( $request->get_params() );

		if ( ! $form ) {
			return hp\rest_error( 400 );
		}

		// Get parameters.
		$params = [];

		foreach ( $form->get_fields() as $name => $field ) {
			if ( $field->is_disabled() ) {
				continue;
			}

			if ( 'number_range' === $field::get_meta( 'name' ) ) {
				$range = [
					floatval( hp\get_array_value( $field->get_args(), 'min_value' ) ),
					floatval( hp\get_array_value( $field->get_args(), 'max_value' ) ),
				];

				if ( ! array_diff( (array) $field->get_value(), $range ) ) {
					continue;
				}
			}

			if ( ! is_null( $field->get_value() ) ) {
				$params[ $name ] = $field->get_value();
			}
		}

		ksort( $params );

		// Get key.
		$key = hivepress()->search_alert->get_alert_key( $params );

		// Get alert.
		$alert = Models\Search_Alert::query()->filter(
			[
				'key'  => $key,
				'user' => get_current_user_id(),
			]
		)->get_first();

		if ( $alert ) {

			// Delete alert.
			if ( ! $alert->delete() ) {
				return hp\rest_error( 400 );
			}

			return hp\rest_response( 204 );
		}

		// Get alerts.
		$alerts = Models\Search_Alert::query()->filter(
			[
				'user' => get_current_user_id(),
			]
		);

		if ( $alerts->get_count() >= 10 ) {
			return hp\rest_error( 400 );
		}

		// Create alert.
		$alert = ( new Models\Search_Alert() )->fill(
			[
				'query'    => hp\get_array_value( $params, 's' ),
				'key'      => $key,
				'params'   => $params,
				'category' => hp\get_array_value( $params, '_category' ),
				'user'     => get_current_user_id(),
			]
		);

		// Get model.
		$model = hivepress()->search_alert->get_first_model( $params );

		if ( $model ) {
			$alert->set_found_time( strtotime( $model->get_created_date() ) );
		}

		if ( ! $alert->save() ) {
			return hp\rest_error( 400 );
		}

		return hp\rest_response(
			200,
			[
				'id' => $alert->get_id(),
			]
		);
	}

	/**
	 * Deletes search alert.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function delete_search_alert( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get alert.
		$alert = Models\Search_Alert::query()->get_by_id( $request->get_param( 'search_alert_id' ) );

		if ( ! $alert ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( $alert->get_user__id() !== get_current_user_id() ) {
			return hp\rest_error( 403 );
		}

		// Delete alert.
		if ( ! $alert->delete() ) {
			return hp\rest_error( 400 );
		}

		return hp\rest_response( 204 );
	}

	/**
	 * Redirects search alerts view page.
	 *
	 * @return mixed
	 */
	public function redirect_search_alerts_view_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Check alerts.
		if ( ! hivepress()->request->get_context( 'search_alert_keys' ) ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		return false;
	}

	/**
	 * Renders search alerts view page.
	 *
	 * @return string
	 */
	public function render_search_alerts_view_page() {
		return ( new Blocks\Template(
			[
				'template' => 'search_alerts_view_page',
			]
		) )->render();
	}
}
