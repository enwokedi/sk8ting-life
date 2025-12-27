<?php
/**
 * Search alert component.
 *
 * @package HivePress\Components
 */

namespace HivePress\Components;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Forms;
use HivePress\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Search alert component class.
 *
 * @class Search_Alert
 */
final class Search_Alert extends Component {

	/**
	 * Model names.
	 *
	 * @var array
	 */
	protected $models = [ 'listing', 'request' ];

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Check search alerts.
		add_action( 'hivepress/v1/events/hourly', [ $this, 'check_search_alerts' ] );

		if ( ! is_admin() ) {

			// Set request context.
			add_filter( 'hivepress/v1/components/request/context', [ $this, 'set_request_context' ], 100 );

			// Alter menus.
			add_filter( 'hivepress/v1/menus/user_account', [ $this, 'alter_user_account_menu' ] );

			// Alter templates.
			foreach ( $this->models as $model ) {
				add_filter( 'hivepress/v1/templates/' . $model . 's_view_page', [ $this, 'alter_models_view_page' ], 10, 2 );
			}
		}

		parent::__construct( $args );
	}

	/**
	 * Gets alert key.
	 *
	 * @param array $params Query parameters.
	 * @return string
	 */
	public function get_alert_key( $params ) {
		return 'http://' . md5( wp_json_encode( $params ) );
	}

	/**
	 * Gets model name.
	 *
	 * @param array $params Parameters.
	 * @return string
	 */
	public function get_model_name( $params ) {
		$name = hp\unprefix( sanitize_key( hp\get_array_value( $params, 'post_type' ) ) );

		if ( ! in_array( $name, $this->models ) ) {
			return;
		}

		return $name;
	}

	/**
	 * Gets model form.
	 *
	 * @param array  $params Parameters.
	 * @param string $context Context.
	 * @return object
	 */
	public function get_model_form( $params, $context = 'filter' ) {

		// Get model name.
		$model = $this->get_model_name( $params );

		if ( ! $model ) {
			return;
		}

		// Get category ID.
		$category_id = absint( hp\get_array_value( $params, '_category' ) );

		// Create form.
		// @todo remove temporary fix.
		remove_filter( 'hivepress/v1/forms/' . $model . '_filter', [ hivepress()->attribute, 'add_category_options' ], 100 );

		$form = hp\create_class_instance( '\HivePress\Forms\\' . $model . '_' . $context, [ [ 'values' => [ '_category' => $category_id ] ] ] );

		add_filter( 'hivepress/v1/forms/' . $model . '_filter', [ hivepress()->attribute, 'add_category_options' ], 100, 2 );

		if ( ! $form ) {
			return;
		}

		// Set values.
		$form->set_values( $params );

		if ( ! $form->validate() ) {
			return;
		}

		return $form;
	}

	/**
	 * Gets first model.
	 *
	 * @param array $params Parameters.
	 * @return mixed
	 * @todo Use HivePress API instead of WP_Query.
	 */
	public function get_first_model( $params ) {

		// Get model name.
		$model = $this->get_model_name( $params );

		if ( ! $model ) {
			return;
		}

		// Get form.
		$form = $this->get_model_form( $params );

		if ( ! $form ) {
			return;
		}

		// Get search query.
		$search_query = sanitize_text_field( hp\get_array_value( $params, 's' ) );

		// Get category ID.
		$category_id = absint( hp\get_array_value( $params, '_category' ) );

		// Unset parameters.
		$form->set_values(
			[
				'_category' => null,
				'_sort'     => null,
				's'         => null,
				'post_type' => null,
			],
			true
		);

		// Get meta and taxonomy queries.
		$meta_query = [];
		$tax_query  = [];

		// Set category ID.
		if ( $category_id ) {
			$tax_query[] = [
				'taxonomy' => hp\prefix( $model . '_category' ),
				'terms'    => $category_id,
			];
		}

		foreach ( $form->get_fields() as $field ) {

			// Get field filter.
			$field_filter = $field->get_filter();

			if ( $field_filter ) {
				if ( ! is_null( $field->get_arg( 'options' ) ) && ! $field->get_arg( '_external' ) ) {

					// Set filter name.
					$field_filter['name'] = hp\prefix( $model . '_' . $field_filter['name'] );

					// Set taxonomy filter.
					$field_filter = array_combine(
						array_map(
							function( $param ) {
								return hp\get_array_value(
									[
										'name'  => 'taxonomy',
										'value' => 'terms',
									],
									$param,
									$param
								);
							},
							array_keys( $field_filter )
						),
						$field_filter
					);

					unset( $field_filter['type'] );

					// Add taxonomy clause.
					$tax_query[] = $field_filter;
				} else {

					// Set filter name.
					$field_filter['name'] = hp\prefix( $field_filter['name'] );

					// Set meta filter.
					$field_filter = array_combine(
						array_map(
							function( $param ) {
								return hp\get_array_value(
									[
										'name'     => 'key',
										'operator' => 'compare',
									],
									$param,
									$param
								);
							},
							array_keys( $field_filter )
						),
						$field_filter
					);

					// Add meta clause.
					$meta_query[] = $field_filter;
				}
			}
		}

		// Create query.
		$query = null;

		$class = '\HivePress\Models\\' . $model;

		if ( class_exists( $class ) ) {
			$query = call_user_func( [ $class, 'query' ] );
		}

		if ( ! $query ) {
			return;
		}

		$query->filter(
			[
				'status' => 'publish',
			]
		)->set_args(
			[
				's'          => $search_query,
				'tax_query'  => $tax_query,
				'meta_query' => $meta_query,
			]
		)->order( [ 'created_date' => 'desc' ] );

		return $query->get_first();
	}

	/**
	 * Checks search alerts.
	 */
	public function check_search_alerts() {

		// Get alerts.
		$alerts = Models\Search_Alert::query()->order( [ 'checked_date' => 'asc' ] )->limit( 10 )->get();

		foreach ( $alerts as $alert ) {

			// Get model.
			$model = $this->get_first_model( $alert->get_params() );

			if ( $model ) {

				// Get found time.
				$found_time = strtotime( $model->get_created_date() );

				if ( $found_time > $alert->get_found_time() ) {

					// Set found time.
					$alert->set_found_time( $found_time );

					// Send email.
					$user = $alert->get_user();

					if ( $user ) {

						// Create email.
						$email = hp\create_class_instance(
							'\HivePress\Emails\\' . $model::_get_meta( 'name' ) . '_Find',
							[
								[
									'recipient' => $user->get_email(),

									'tokens'    => [
										'user'      => $user,
										'user_name' => $user->get_display_name(),
										$model::_get_meta( 'name' ) . 's_url' => add_query_arg( $alert->get_params(), home_url() ),
									],
								],
							]
						);

						if ( $email ) {

							// Send email.
							$email->send();
						}
					}
				}
			}

			// Update alert.
			$alert->fill(
				[
					'checked_date' => current_time( 'mysql' ),
				]
			)->save(
				[
					'checked_date',
					'found_time',
				]
			);
		}
	}

	/**
	 * Sets request context.
	 *
	 * @param array $context Request context.
	 * @return array
	 */
	public function set_request_context( $context ) {

		// Get cached alert keys.
		$alert_keys = hivepress()->cache->get_user_cache( get_current_user_id(), 'search_alert_keys', 'models/search_alert' );

		if ( is_null( $alert_keys ) ) {

			// Get alerts.
			$alerts = Models\Search_Alert::query()->filter(
				[
					'user' => get_current_user_id(),
				]
			)->get()
			->serialize();

			// Get alert keys.
			$alert_keys = array_map(
				function( $alert ) {
					return $alert->get_key();
				},
				$alerts
			);

			// Cache alert keys.
			if ( count( $alert_keys ) <= 100 ) {
				hivepress()->cache->set_user_cache( get_current_user_id(), 'search_alert_keys', 'models/search_alert', $alert_keys );
			}
		}

		// Set request context.
		$context['search_alert_keys'] = $alert_keys;

		return $context;
	}

	/**
	 * Alters user account menu.
	 *
	 * @param array $menu Menu arguments.
	 * @return array
	 */
	public function alter_user_account_menu( $menu ) {
		if ( hivepress()->request->get_context( 'search_alert_keys' ) ) {
			$menu['items']['search_alerts_view'] = [
				'route'  => 'search_alerts_view_page',
				'_order' => 25,
			];
		}

		return $menu;
	}

	/**
	 * Alters models view page.
	 *
	 * @param array  $template_args Template arguments.
	 * @param object $template Template object.
	 * @return array
	 */
	public function alter_models_view_page( $template_args, $template ) {
		return hp\merge_trees(
			$template_args,
			[
				'blocks' => [
					$template::get_meta( 'model' ) . '_filter_form' => [
						'footer' => [
							'search_alert_toggle' => [
								'type'   => 'search_alert_toggle',
								'_order' => 10,
							],
						],
					],
				],
			]
		);
	}
}
