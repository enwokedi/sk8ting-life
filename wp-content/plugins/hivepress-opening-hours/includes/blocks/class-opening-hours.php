<?php
/**
 * Opening hours block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Opening hours block class.
 *
 * @class Opening_Hours
 */
class Opening_Hours extends Block {

	/**
	 * Renders block HTML.
	 *
	 * @return string
	 */
	public function render() {
		$output = '';

		// Get listing.
		$listing = $this->get_context( 'listing' );

		if ( $listing ) {

			// Set days.
			$days = [
				'monday',
				'tuesday',
				'wednesday',
				'thursday',
				'friday',
				'saturday',
				'sunday',
			];

			// Get periods.
			$periods = [];

			if ( $listing->get_opening_hours() ) {
				foreach ( $days as $day ) {
					$periods[ $day ] = [
						'days'   => [ $day ],
						'ranges' => [],
					];

					foreach ( $listing->get_opening_hours() as $range ) {
						if ( $range['day'] === $day && $range['from'] !== $range['to'] ) {
							$periods[ $day ]['ranges'][] = [
								'from' => $range['from'],
								'to'   => $range['to'],
							];
						}
					}
				}

				foreach ( $periods as $current_day => $current_period ) {
					$index = array_search( $current_day, array_keys( $periods ) );

					if ( false !== $index && $index < count( $periods ) - 1 ) {
						foreach ( array_slice( $periods, $index + 1 ) as $next_day => $next_period ) {
							if ( $next_period['ranges'] !== $current_period['ranges'] ) {
								break;
							}

							$periods[ $current_day ]['days'][] = $next_day;

							unset( $periods[ $next_day ] );
						}
					}
				}
			}

			// Render periods.
			if ( $periods || $listing->is_always_open() ) {
				$output .= '<div class="hp-listing__opening-hours hp-widget widget">';
				$output .= '<table class="hp-table"><tbody>';

				if ( $listing->is_always_open() ) {
					$output .= '<tr><th>' . esc_html__( 'Open 24/7', 'hivepress-opening-hours' ) . '</th></tr>';
				} else {
					$format = get_option( 'time_format' );

					foreach ( $periods as $period ) {
						$output .= '<tr><th>' . esc_html(
							implode(
								', ',
								array_map(
									function( $day ) {
										return date_i18n( 'D', strtotime( $day ) );
									},
									$period['days']
								)
							)
						) . '</th>';

						$output .= '<td>' . esc_html(
							$period['ranges'] ? implode(
								', ',
								array_map(
									function( $range ) use ( $format ) {
										return date_i18n( $format, $range['from'] ) . ' - ' . date_i18n( $format, $range['to'] );
									},
									$period['ranges']
								)
							) : __( 'Closed', 'hivepress-opening-hours' )
						) . '</td>';

						$output .= '</tr>';
					}
				}

				$output .= '</tbody></table>';
				$output .= '</div>';
			}
		}

		return $output;
	}
}
