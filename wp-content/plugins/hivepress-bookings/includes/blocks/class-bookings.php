<?php
/**
 * Bookings block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;
use HivePress\Models;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bookings block class.
 *
 * @class Bookings
 */
class Bookings extends Block {

	/**
	 * Renders block HTML.
	 *
	 * @return string
	 */
	public function render() {
		global $wp_query;

		$output = '';

		// Query bookings.
		$query = $wp_query;

		if ( $query->have_posts() ) {

			// Render bookings.
			$output  = '<div class="hp-bookings hp-grid hp-block">';
			$output .= '<div class="hp-row">';

			while ( $query->have_posts() ) {
				$query->the_post();

				// Get booking.
				$booking = Models\Booking::query()->get_by_id( get_post() );

				if ( $booking ) {

					// Get listing.
					$listing = $booking->get_listing();

					if ( $listing ) {

						// Render booking.
						$output .= '<div class="hp-grid__item hp-col-xs-12">';

						$output .= ( new Template(
							[
								'template' => 'booking_view_block',

								'context'  => [
									'booking' => $booking,
									'listing' => $listing,
								],
							]
						) )->render();

						$output .= '</div>';
					}
				}
			}

			$output .= '</div>';
			$output .= '</div>';
		}

		// Reset query.
		wp_reset_postdata();

		return $output;
	}
}
