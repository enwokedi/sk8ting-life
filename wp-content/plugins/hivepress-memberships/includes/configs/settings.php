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
	'memberships' => [
		'title'    => esc_html__( 'Memberships', 'hivepress-memberships' ),
		'_order'   => 120,

		'sections' => [
			'display'      => [
				'title'  => hivepress()->translator->get_string( 'display_noun' ),
				'_order' => 10,

				'fields' => [
					'page_membership_plans' => [
						'label'       => esc_html__( 'Plans Page', 'hivepress-memberships' ),
						'description' => esc_html__( 'Choose a page that displays all plans.', 'hivepress-memberships' ),
						'type'        => 'select',
						'options'     => 'posts',
						'option_args' => [ 'post_type' => 'page' ],
						'_order'      => 10,
					],
				],
			],

			'restrictions' => [
				'title'  => esc_html__( 'Restrictions', 'hivepress-memberships' ),
				'_order' => 20,

				'fields' => [
					'membership_models'     => [
						'label'       => esc_html__( 'Content Types', 'hivepress-memberships' ),
						'description' => esc_html__( 'Select the content types that should have membership restrictions.', 'hivepress-memberships' ),
						'type'        => 'select',
						'default'     => [ 'listing' ],
						'multiple'    => true,
						'required'    => true,
						'_order'      => 10,

						'options'     => [
							'user'    => hivepress()->translator->get_string( 'users' ),
							'vendor'  => hivepress()->translator->get_string( 'vendors' ),
							'listing' => hivepress()->translator->get_string( 'listings' ),
						],
					],

					'membership_renew_free' => [
						'label'       => esc_html__( 'Renewing', 'hivepress-memberships' ),
						'caption'     => esc_html__( 'Allow renewing free memberships', 'hivepress-memberships' ),
						'description' => esc_html__( 'Check this option to allow users to renew free memberships once they expire.', 'hivepress-memberships' ),
						'type'        => 'checkbox',
						'_order'      => 20,
					],
				],
			],
		],
	],
];
