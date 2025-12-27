<?php
/**
 * Strings configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'booking'                            => esc_html__( 'Booking', 'hivepress-bookings' ),
	'start_date'                         => esc_html__( 'Start Date', 'hivepress-bookings' ),
	'end_date'                           => esc_html__( 'End Date', 'hivepress-bookings' ),
	'places'                             => esc_html__( 'Places', 'hivepress-bookings' ),
	'security_deposit'                   => esc_html__( 'Security Deposit', 'hivepress-bookings' ),
	/* translators: %s: number. */
	'places_n'                           => esc_html__( 'Places: %s', 'hivepress-bookings' ),
	'min_places_per_booking'             => esc_html__( 'Minimum Places per Booking', 'hivepress-bookings' ),
	'max_places_per_booking'             => esc_html__( 'Maximum Places per Booking', 'hivepress-bookings' ),
	'allow_vendors_to_add_listing_notes' => esc_html__( 'Allow vendors to add booking notes', 'hivepress-bookings' ),
];
