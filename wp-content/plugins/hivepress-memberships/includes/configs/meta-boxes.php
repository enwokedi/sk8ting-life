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
	'membership_plan_settings' => [
		'title'  => hivepress()->translator->get_string( 'settings' ),
		'screen' => 'membership_plan',

		'fields' => [
			'product'       => [
				'label'       => hivepress()->translator->get_string( 'ecommerce_product' ),
				'description' => esc_html__( 'Choose a product that must be purchased in order to get this membership.', 'hivepress-memberships' ),
				'type'        => 'select',
				'options'     => 'posts',
				'option_args' => [ 'post_type' => 'product' ],
				'_alias'      => 'post_parent',
				'_order'      => 10,
			],

			'expire_period' => [
				'label'       => hivepress()->translator->get_string( 'expiration_period' ),
				'description' => esc_html__( 'Set the number of days after which this membership expires.', 'hivepress-memberships' ),
				'type'        => 'number',
				'min_value'   => 1,
				'_order'      => 20,
			],

			'primary'       => [
				'label'   => esc_html_x( 'Recommended', 'plan', 'hivepress-memberships' ),
				'caption' => esc_html__( 'Display this plan as recommended', 'hivepress-memberships' ),
				'type'    => 'checkbox',
				'_order'  => 30,
			],
		],
	],

	'membership_settings'      => [
		'title'  => hivepress()->translator->get_string( 'settings' ),
		'screen' => 'membership',

		'fields' => [
			'user'         => [
				'label'    => hivepress()->translator->get_string( 'user' ),
				'type'     => 'select',
				'options'  => 'users',
				'source'   => hivepress()->router->get_url( 'users_resource' ),
				'required' => true,
				'_alias'   => 'post_author',
				'_order'   => 10,
			],

			'plan'         => [
				'label'       => esc_html__( 'Plan', 'hivepress-memberships' ),
				'type'        => 'select',
				'options'     => 'posts',
				'option_args' => [ 'post_type' => 'hp_membership_plan' ],
				'required'    => true,
				'_alias'      => 'post_parent',
				'_order'      => 20,
			],

			'expired_time' => [
				'label'       => hivepress()->translator->get_string( 'expiration_date' ),
				'description' => esc_html__( 'Set a date on which the membership will expire.', 'hivepress-memberships' ),
				'type'        => 'date',
				'format'      => 'U',
				'_order'      => 30,
			],
		],
	],
];
