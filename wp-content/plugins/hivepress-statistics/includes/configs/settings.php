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
	'integrations' => [
		'sections' => [
			'google'     => [
				'fields' => [
					'google_client_id'     => [
						'label'      => hivepress()->translator->get_string( 'client_id' ),
						'type'       => 'text',
						'max_length' => 256,
						'_order'     => 10,
					],

					'google_client_secret' => [
						'label'      => hivepress()->translator->get_string( 'client_secret' ),
						'type'       => 'text',
						'max_length' => 256,
						'_order'     => 20,
					],

					'google_redirect_url'  => [
						'label'    => hivepress()->translator->get_string( 'redirect_url' ),
						'type'     => 'url',
						'default'  => hivepress()->router->get_url( 'google_oauth_grant_access_action' ),
						'readonly' => true,
						'_order'   => 30,
					],
				],
			],

			'ganalytics' => [
				'title'  => esc_html__( 'Google Analytics', 'hivepress-statistics' ),
				'_order' => 40,

				'fields' => [
					'ganalytics_measurement_id' => [
						'label'      => esc_html__( 'Measurement ID', 'hivepress-statistics' ),
						'type'       => 'text',
						'max_length' => 256,
						'_order'     => 10,
					],

					'ganalytics_property_id'    => [
						'label'      => esc_html__( 'Property ID', 'hivepress-statistics' ),
						'type'       => 'text',
						'max_length' => 256,
						'_order'     => 20,
					],

					'ganalytics'                => [
						'label'    => hivepress()->translator->get_string( 'authorization' ),
						'type'     => 'google_oauth_button',
						'scope'    => 'https://www.googleapis.com/auth/analytics.readonly',
						'readonly' => true,
						'_order'   => 30,
					],
				],
			],
		],
	],
];
