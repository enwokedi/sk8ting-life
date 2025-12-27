<?php
/**
 * Membership controller.
 *
 * @package HivePress\Controllers
 */

namespace HivePress\Controllers;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Forms;
use HivePress\Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Membership controller class.
 *
 * @class Membership
 */
final class Membership extends Controller {

	/**
	 * Class constructor.
	 *
	 * @param array $args Controller arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'routes' => [
					'membership_reveal_action'      => [
						'path'   => '/memberships/reveal',
						'method' => 'POST',
						'action' => [ $this, 'reveal_membership' ],
						'rest'   => true,
					],

					'membership_plans_view_page'    => [
						'title'    => esc_html_x( 'Select Plan', 'imperative', 'hivepress-memberships' ),
						'path'     => '/select-plan',
						'redirect' => [ $this, 'redirect_membership_plans_view_page' ],
						'action'   => [ $this, 'render_membership_plans_view_page' ],
					],

					'membership_plan_select_page'   => [
						'base'     => 'membership_plans_view_page',
						'path'     => '/(?P<membership_plan_id>\d+)',
						'redirect' => [ $this, 'redirect_membership_plan_select_page' ],
					],

					'memberships_view_page'         => [
						'title'    => esc_html__( 'Memberships', 'hivepress-memberships' ),
						'base'     => 'user_account_page',
						'path'     => '/memberships',
						'redirect' => [ $this, 'redirect_memberships_view_page' ],
						'action'   => [ $this, 'render_memberships_view_page' ],
					],

					'listing_feature_page'          => [
						'base'     => 'listing_edit_page',
						'path'     => '/feature',

						'redirect' => [
							[
								'callback' => [ $this, 'redirect_listing_feature_page' ],
								'_order'   => 5,
							],
						],
					],

					'listing_feature_complete_page' => [
						'title'    => hivepress()->translator->get_string( 'listing_featured' ),
						'base'     => 'listing_feature_page',
						'path'     => '/complete',
						'redirect' => [ $this, 'redirect_listing_feature_complete_page' ],
						'action'   => [ $this, 'render_listing_feature_complete_page' ],
					],

					'attachment_download_page'      => [
						'path'     => '/download-attachment/(?P<attachment_id>\d+)',
						'redirect' => [ $this, 'redirect_attachment_download_page' ],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Reveals membership.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function reveal_membership( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Check permissions.
		if ( current_user_can( 'edit_others_posts' ) ) {
			return hp\rest_error( 400 );
		}

		// Validate form.
		$form = ( new Forms\Membership_Reveal() )->set_values( $request->get_params() );

		if ( ! $form->validate() ) {
			return hp\rest_error( 400, $form->get_errors() );
		}

		// Get model and object.
		$model  = $form->get_value( 'model' );
		$object = hivepress()->model->get_model_object( $model, $form->get_value( 'id' ) );

		if ( ! $object || ( 'user' !== $model && ! in_array( $object->get_status(), [ 'publish', 'private' ] ) ) ) {
			return hp\rest_error( 404 );
		}

		// Get reveal IDs.
		$reveal_ids = hivepress()->membership->get_reveal_ids( get_current_user_id() );

		if ( in_array( $object->get_id(), $reveal_ids[ $model ] ) ) {
			return hp\rest_error( 400, esc_html__( 'The details are already revealed.', 'hivepress-memberships' ) );
		}

		// Update membership.
		$reduced = hivepress()->membership->reduce_membership_limit( get_current_user_id(), $model . '_view_limit' );

		if ( is_null( $reduced ) ) {
			return hp\rest_error( 401, esc_html__( 'An active membership is required for this action.', 'hivepress-memberships' ) );
		} elseif ( ! $reduced ) {
			return hp\rest_error( 401, esc_html__( 'The view limit is exceeded.', 'hivepress-memberships' ) );
		}

		// Add reveal ID.
		$reveal_ids[ $model ][] = $object->get_id();

		hivepress()->membership->set_reveal_ids( get_current_user_id(), $reveal_ids );

		return hp\rest_response(
			200,
			[
				'id' => $object->get_id(),
			]
		);
	}

	/**
	 * Redirects membership plans view page.
	 *
	 * @return mixed
	 */
	public function redirect_membership_plans_view_page() {

		// Check plans.
		if ( ! hivepress()->request->get_context( 'membership_plan' ) ) {
			return true;
		}

		// Get page ID.
		$page_id = get_option( 'hp_page_membership_plans' );

		// Redirect page.
		if ( $page_id ) {
			return get_permalink( $page_id );
		}

		return false;
	}

	/**
	 * Renders membership plans view page.
	 *
	 * @return string
	 */
	public function render_membership_plans_view_page() {
		return ( new Blocks\Template(
			[
				'template' => 'membership_plans_view_page',

				'context'  => [
					'membership_plans' => [],
				],
			]
		) )->render();
	}

	/**
	 * Redirects membership plan select page.
	 *
	 * @return mixed
	 */
	public function redirect_membership_plan_select_page() {

		// Get plan.
		$plan = Models\Membership_Plan::query()->get_by_id( hivepress()->request->get_param( 'membership_plan_id' ) );

		if ( ! $plan || $plan->get_status() !== 'publish' ) {
			return true;
		}

		if ( hp\is_plugin_active( 'woocommerce' ) && $plan->get_product__id() ) {

			// Add product to cart.
			WC()->cart->empty_cart();
			WC()->cart->add_to_cart( $plan->get_product__id() );

			return wc_get_page_permalink( 'checkout' );
		}

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Add membership.
		if ( hivepress()->membership->add_membership( get_current_user_id(), $plan ) ) {
			if ( hivepress()->router->get_redirect_url() ) {
				return hivepress()->router->get_redirect_url();
			} else {
				return hivepress()->router->get_url( 'memberships_view_page' );
			}
		}

		return true;
	}

	/**
	 * Redirects memberships view page.
	 *
	 * @return mixed
	 */
	public function redirect_memberships_view_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Check memberships.
		if ( ! hivepress()->request->get_context( 'membership_count' ) ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		return false;
	}

	/**
	 * Renders memberships view page.
	 *
	 * @return string
	 */
	public function render_memberships_view_page() {
		return ( new Blocks\Template(
			[
				'template' => 'memberships_view_page',
			]
		) )->render();
	}

	/**
	 * Redirects listing feature page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_feature_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( hivepress()->request->get_param( 'listing_id' ) );

		if ( ! $listing || get_current_user_id() !== $listing->get_user__id() || $listing->get_status() !== 'publish' || $listing->is_featured() ) {
			return hivepress()->router->get_url( 'listings_edit_page' );
		}

		// Update listing.
		$listing->set_featured( true )->save_featured();

		return hivepress()->router->get_url( 'listing_feature_complete_page', [ 'listing_id' => $listing->get_id() ] );
	}

	/**
	 * Redirects listing feature complete page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_feature_complete_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( hivepress()->request->get_param( 'listing_id' ) );

		if ( ! $listing || get_current_user_id() !== $listing->get_user__id() || $listing->get_status() !== 'publish' || ! $listing->is_featured() ) {
			return hivepress()->router->get_url( 'listings_edit_page' );
		}

		// Set request context.
		hivepress()->request->set_context( 'listing', $listing );

		return false;
	}

	/**
	 * Renders listing feature complete page.
	 *
	 * @return string
	 */
	public function render_listing_feature_complete_page() {
		return ( new Blocks\Template(
			[
				'template' => 'listing_feature_complete_page',

				'context'  => [
					'listing' => hivepress()->request->get_context( 'listing' ),
				],
			]
		) )->render();
	}

	/**
	 * Redirects attachment download page.
	 *
	 * @return mixed
	 */
	public function redirect_attachment_download_page() {

		// Get attachment.
		$attachment = Models\Attachment::query()->get_by_id( hivepress()->request->get_param( 'attachment_id' ) );

		if ( ! $attachment ) {
			return true;
		}

		// Get model name.
		$model = $attachment->get_parent_model();

		if ( ! $model || ! $attachment->get_parent_field() ) {
			return true;
		}

		// Get limit name.
		$limit = $model . '_view_attributes';

		// Get membership.
		$membership = hivepress()->request->get_context( 'membership' );

		if ( ! $membership || ! in_array( $attachment->get_parent_field(), (array) call_user_func( [ $membership, 'get_' . $limit ] ) ) ) {
			return hivepress()->router->get_return_url( 'membership_plans_view_page', [ 'restriction' => $limit ] );
		}

		// Get file path.
		$file_path = get_attached_file( $attachment->get_id() );

		if ( ! $file_path ) {
			return true;
		}

		// Get file type.
		$file_type = get_post_mime_type( $attachment->get_id() );

		if ( ! $file_type ) {
			return true;
		}

		// Get file details.
		$file_name = basename( $file_path );
		$file_size = filesize( $file_path );

		// Download file.
		header( 'Content-Type: ' . $file_type );
		header( 'Content-disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Length: ' . $file_size );

		readfile( $file_path );

		exit;
	}
}
