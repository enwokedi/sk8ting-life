<?php
/**
 * Statistics controller.
 *
 * @package HivePress\Controllers
 */

namespace HivePress\Controllers;

use HivePress\Helpers as hp;
use HivePress\Models;
use HivePress\Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Statistics controller class.
 *
 * @class Statistics
 */
final class Statistics extends Controller {

	/**
	 * Class constructor.
	 *
	 * @param array $args Controller arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'routes' => [
					'listing_statistics_page' => [
						'base'     => 'listing_edit_page',
						'path'     => '/statistics',
						'title'    => [ $this, 'get_listing_statistics_title' ],
						'redirect' => [ $this, 'redirect_listing_statistics_page' ],
						'action'   => [ $this, 'render_listing_statistics_page' ],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Gets listing statistics title.
	 *
	 * @return string
	 */
	public function get_listing_statistics_title() {
		$title = null;

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( hivepress()->request->get_param( 'listing_id' ) );

		// Set title.
		if ( $listing ) {
			$title = $listing->get_title();
		}

		// Set request context.
		hivepress()->request->set_context( 'listing', $listing );

		return $title;
	}

	/**
	 * Redirects listing statistics page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_statistics_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Check listing.
		$listing = hivepress()->request->get_context( 'listing' );

		if ( empty( $listing ) || get_current_user_id() !== $listing->get_user__id() || $listing->get_status() !== 'publish' ) {
			return hivepress()->router->get_url( 'listings_edit_page' );
		}

		return false;
	}

	/**
	 * Renders listing statistics page.
	 *
	 * @return string
	 */
	public function render_listing_statistics_page() {
		return ( new Blocks\Template(
			[
				'template' => 'listing_statistics_page',

				'context'  => [
					'listing' => hivepress()->request->get_context( 'listing' ),
				],
			]
		) )->render();
	}
}
