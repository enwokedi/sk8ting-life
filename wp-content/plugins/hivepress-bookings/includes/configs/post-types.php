<?php
/**
 * Post types configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'booking' => [
		'public'           => false,
		'show_ui'          => true,
		'delete_with_user' => true,
		'supports'         => [ 'title' ],
		'menu_icon'        => 'dashicons-calendar',

		'labels'           => [
			'name'               => esc_html__( 'Bookings', 'hivepress-bookings' ),
			'singular_name'      => hivepress()->translator->get_string( 'booking' ),
			'add_new'            => esc_html_x( 'Add New', 'booking', 'hivepress-bookings' ),
			'add_new_item'       => esc_html__( 'Add Booking', 'hivepress-bookings' ),
			'edit_item'          => esc_html__( 'Edit Booking', 'hivepress-bookings' ),
			'new_item'           => esc_html__( 'Add Booking', 'hivepress-bookings' ),
			'all_items'          => esc_html__( 'Bookings', 'hivepress-bookings' ),
			'search_items'       => esc_html__( 'Search Bookings', 'hivepress-bookings' ),
			'not_found'          => esc_html__( 'No bookings found.', 'hivepress-bookings' ),
			'not_found_in_trash' => esc_html__( 'No bookings found.', 'hivepress-bookings' ),
		],
	],
];
