<?php
/**
 * Settings configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'listings'     => [
		'sections' => [
			'search'  => [
				'fields' => [
					'listing_optimize_date_search' => [
						'label'       => esc_html__( 'Performance', 'hivepress-bookings' ),
						'caption'     => esc_html__( 'Optimize availability search', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to speed up the search by not checking the minimum booking duration, offset and window.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 30,
					],
				],
			],

			'selling' => [
				'title'  => hivepress()->translator->get_string( 'booking' ),
				'_order' => 100,

				'fields' => [
					'listing_allow_purchase_note' => [
						'label'   => esc_html__( 'Booking Note', 'hivepress-bookings' ),
						'caption' => hivepress()->translator->get_string( 'allow_vendors_to_add_listing_notes' ),
						'type'    => 'checkbox',
						'_order'  => 50,
					],
				],
			],
		],
	],

	'bookings'     => [
		'title'    => esc_html__( 'Bookings', 'hivepress-bookings' ),
		'_order'   => 120,

		'sections' => [
			'availability' => [
				'title'  => esc_html__( 'Availability', 'hivepress-bookings' ),
				'_order' => 10,

				'fields' => [
					'booking_categories'       => [
						'label'       => esc_html__( 'Booking Categories', 'hivepress-bookings' ),
						'description' => esc_html__( 'Select categories where booking should be available, or leave empty for all categories.', 'hivepress-bookings' ),
						'type'        => 'select',
						'options'     => 'terms',
						'option_args' => [ 'taxonomy' => 'hp_listing_category' ],
						'multiple'    => true,
						'_order'      => 10,
					],

					'booking_blocked_statuses' => [
						'label'       => esc_html__( 'Blocking Statuses', 'hivepress-bookings' ),
						'description' => esc_html__( 'Select which booking statuses in addition to Confirmed should block the dates.', 'hivepress-bookings' ),
						'type'        => 'select',
						'default'     => [ 'draft', 'pending' ],
						'multiple'    => true,
						'_order'      => 20,

						'options'     => [
							'draft'   => esc_html_x( 'Unpaid', 'booking', 'hivepress-bookings' ),
							'pending' => esc_html_x( 'Pending', 'booking', 'hivepress-bookings' ),
						],
					],

					'booking_per_vendor'       => [
						'label'       => esc_html__( 'Availability', 'hivepress-bookings' ),
						'caption'     => esc_html__( 'Manage availability per vendor', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to manage the availability per vendor instead of per listing.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 30,
					],

					'booking_allow_sync'       => [
						'label'       => esc_html__( 'Syncing', 'hivepress-bookings' ),
						'caption'     => esc_html__( 'Allow syncing with external calendars', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to allow syncing the availability with external ICS calendars.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 35,
					],

					'booking_enable_quantity'  => [
						'label'       => hivepress()->translator->get_string( 'places' ),
						'caption'     => esc_html__( 'Allow multiple places per booking', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to allow reserving multiple places per booking.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 40,
					],

					'booking_enable_capacity'  => [
						'caption'     => esc_html__( 'Allow multiple bookings per time period', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to allow reservations until the places are filled.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_parent'     => 'booking_enable_quantity',
						'_order'      => 50,
					],

					'booking_enable_daily'     => [
						'label'       => esc_html__( 'Daily Bookings', 'hivepress-bookings' ),
						'caption'     => esc_html__( 'Enable daily bookings', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to enable daily instead of overnight bookings.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 60,
					],

					'booking_enable_time'      => [
						'label'       => esc_html__( 'Time Slots', 'hivepress-bookings' ),
						'caption'     => esc_html__( 'Enable time slots', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to enable time instead of date based bookings.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 70,
					],

					'booking_multiple_time'    => [
						'caption'     => esc_html__( 'Allow booking multiple time slots', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to allow reserving multiple time slots per booking.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_parent'     => 'booking_enable_time',
						'_order'      => 80,
					],

					'booking_time_categories'  => [
						'label'       => esc_html__( 'Time Categories', 'hivepress-bookings' ),
						'description' => esc_html__( 'Select categories where time-based bookings should be enabled, or leave empty for all categories.', 'hivepress-bookings' ),
						'type'        => 'select',
						'options'     => 'terms',
						'option_args' => [ 'taxonomy' => 'hp_listing_category' ],
						'multiple'    => true,
						'_parent'     => 'booking_enable_time',
						'_order'      => 90,
					],

					'booking_enable_timezone'  => [
						'label'       => esc_html__( 'Time Zones', 'hivepress-bookings' ),
						'caption'     => esc_html__( 'Enable time zones', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to enable the time zone indication for bookings.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_parent'     => 'booking_enable_time',
						'_order'      => 100,
					],
				],
			],

			'pricing'      => [
				'title'  => esc_html__( 'Pricing', 'hivepress-bookings' ),
				'_order' => 20,

				'fields' => [
					'booking_enable_price'      => [
						'label'       => hivepress()->translator->get_string( 'price' ),
						'caption'     => esc_html__( 'Enable variable pricing', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to allow setting custom prices per time period.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 10,
					],

					'listing_multiply_quantity' => [
						'caption'     => esc_html__( 'Multiply price by the number of places', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option if the booking price depends on the number of places.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'default'     => true,
						'_parent'     => 'booking_enable_quantity',
						'_order'      => 20,
					],

					'booking_enable_deposit'    => [
						'label'       => hivepress()->translator->get_string( 'security_deposit' ),
						'caption'     => esc_html__( 'Allow security deposits', 'hivepress-bookings' ),
						'description' => esc_html__( 'Check this option to allow charging extra amount and refunding it once the booking is over.', 'hivepress-bookings' ),
						'type'        => 'checkbox',
						'_order'      => 30,
					],
				],
			],

			'сonfirmation' => [
				'title'  => esc_html__( 'Confirmation', 'hivepress-bookings' ),
				'_order' => 30,

				'fields' => [
					'page_booking_terms' => [
						'label'       => esc_html__( 'Booking Terms Page', 'hivepress-bookings' ),
						'description' => esc_html__( 'Choose a page with terms that user has to accept before making a booking.', 'hivepress-bookings' ),
						'type'        => 'select',
						'options'     => 'posts',
						'option_args' => [ 'post_type' => 'page' ],
						'_order'      => 10,
					],
				],
			],

			'expiration'   => [
				'title'  => hivepress()->translator->get_string( 'expiration' ),
				'_order' => 40,

				'fields' => [
					'booking_expiration_period' => [
						'label'       => hivepress()->translator->get_string( 'expiration_period' ),
						'description' => esc_html__( 'Set the number of days after which a pending or unpaid booking is canceled.', 'hivepress-bookings' ),
						'type'        => 'number',
						'min_value'   => 1,
						'default'     => 2,
						'_order'      => 10,
					],

					'booking_storage_period'    => [
						'label'       => hivepress()->translator->get_string( 'storage_period' ),
						'description' => esc_html__( 'Set the number of days after which a past or canceled booking is deleted.', 'hivepress-bookings' ),
						'type'        => 'number',
						'min_value'   => 1,
						'_order'      => 20,
					],
				],
			],
		],
	],

	'integrations' => [
		'sections' => [
			'zoom' => [
				'title'  => 'Zoom',
				'_order' => 50,

				'fields' => [
					'zoom_client_id'     => [
						'label'      => hivepress()->translator->get_string( 'client_id' ),
						'type'       => 'text',
						'max_length' => 256,
						'_order'     => 10,
					],

					'zoom_client_secret' => [
						'label'      => hivepress()->translator->get_string( 'client_secret' ),
						'type'       => 'text',
						'max_length' => 256,
						'_order'     => 20,
					],

					'zoom_redirect_url'  => [
						'label'    => hivepress()->translator->get_string( 'redirect_url' ),
						'type'     => 'url',
						'default'  => hivepress()->router->get_url( 'zoom_oauth_grant_access_action' ),
						'readonly' => true,
						'_order'   => 30,
					],
				],
			],
		],
	],
];
