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
	'users' => [
		'sections' => [
			'registration' => [
				'fields' => [
					'user_auth_methods'  => [
						'label'       => esc_html__( 'Authentication Methods', 'hivepress-social-login' ),
						'description' => esc_html__( 'Select the available authentication methods. Each method requires the API credentials that you can set in the Integrations section.', 'hivepress-social-login' ),
						'type'        => 'select',
						'options'     => [],
						'multiple'    => true,
						'_order'      => 20,
					],

					'user_auth_redirect' => [
						'label'       => hivepress()->translator->get_string( 'redirect_url' ),
						'description' => esc_html__( 'Set this redirect URL in the API settings for each of the enabled authentication methods.', 'hivepress-social-login' ),
						'type'        => 'url',
						'default'     => hivepress()->router->get_url( 'user_auth_page' ),
						'readonly'    => true,
						'_order'      => 25,
					],
				],
			],
		],
	],
];
