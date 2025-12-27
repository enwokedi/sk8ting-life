<?php
/**
 * Meta boxes configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'booking_settings' => [
		'title'  => hivepress()->translator->get_string( 'settings' ),
		'screen' => 'booking',

		'fields' => [
			'listing'    => [
				'label'       => hivepress()->translator->get_string( 'listing' ),
				'type'        => 'select',
				'options'     => 'posts',
				'option_args' => [ 'post_type' => 'hp_listing' ],
				'source'      => hivepress()->router->get_url( 'listings_resource' ),
				'required'    => true,
				'_alias'      => 'post_parent',
				'_order'      => 10,
			],

			'user'       => [
				'label'    => hivepress()->translator->get_string( 'user' ),
				'type'     => 'select',
				'options'  => 'users',
				'source'   => hivepress()->router->get_url( 'users_resource' ),
				'required' => true,
				'_alias'   => 'post_author',
				'_order'   => 20,
			],

			'start_time' => [
				'label'    => hivepress()->translator->get_string( 'start_date' ),
				'type'     => 'date',
				'format'   => 'U',
				'required' => true,
				'_order'   => 30,
			],

			'end_time'   => [
				'label'    => hivepress()->translator->get_string( 'end_date' ),
				'type'     => 'date',
				'format'   => 'U',
				'required' => true,
				'_order'   => 40,
			],

			'canceled'   => [
				'label'   => hivepress()->translator->get_string( 'status' ),
				'caption' => esc_html__( 'Canceled by user', 'hivepress-bookings' ),
				'type'    => 'checkbox',
				'_order'  => 100,
			],
		],
	],

	'vendor_calendar'  => [
		'title'  => esc_html__( 'Calendar', 'hivepress-bookings' ),
		'screen' => 'vendor',

		'blocks' => [
			'vendor_calendar' => [
				'type'   => 'booking_calendar',
				'_order' => 10,
			],
		],
	],

	'listing_calendar' => [
		'title'  => esc_html__( 'Calendar', 'hivepress-bookings' ),
		'screen' => 'listing',

		'blocks' => [
			'listing_calendar' => [
				'type'   => 'booking_calendar',
				'_order' => 10,
			],
		],
	],
];
