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
	'listings' => [
		'sections' => [
			'social' => [
				'title'  => esc_html__( 'Social Links', 'hivepress-social-links' ),
				'_order' => 100,

				'fields' => [
					'listing_social_links'         => [
						'label'    => esc_html__( 'Social Links', 'hivepress-social-links' ),
						'type'     => 'select',
						'options'  => 'social_links',
						'multiple' => true,
						'_order'   => 10,
					],

					'listing_social_links_display' => [
						'label'       => esc_html__( 'Social Links Display', 'hivepress-social-links' ),
						'placeholder' => esc_html__( 'Icons', 'hivepress-social-links' ),
						'type'        => 'select',
						'_parent'     => 'listing_social_links[]',
						'_order'      => 20,

						'options'     => [
							'button' => esc_html__( 'Buttons', 'hivepress-social-links' ),
						],
					],
				],
			],
		],
	],

	'vendors'  => [
		'sections' => [
			'social' => [
				'title'  => esc_html__( 'Social Links', 'hivepress-social-links' ),
				'_order' => 100,

				'fields' => [
					'vendor_social_links'         => [
						'label'    => esc_html__( 'Social Links', 'hivepress-social-links' ),
						'type'     => 'select',
						'options'  => 'social_links',
						'multiple' => true,
						'_order'   => 10,
					],

					'vendor_social_links_display' => [
						'label'       => esc_html__( 'Social Links Display', 'hivepress-social-links' ),
						'placeholder' => esc_html__( 'Icons', 'hivepress-social-links' ),
						'type'        => 'select',
						'_parent'     => 'vendor_social_links[]',
						'_order'      => 20,

						'options'     => [
							'button' => esc_html__( 'Buttons', 'hivepress-social-links' ),
						],
					],
				],
			],
		],
	],
];
