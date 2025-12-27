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
			'submission' => [
				'fields' => [
					'listing_allow_import' => [
						'label'   => esc_html_x( 'Import', 'noun', 'hivepress-import' ),
						'caption' => esc_html__( 'Allow importing listings', 'hivepress-import' ),
						'type'    => 'checkbox',
						'_order'  => 50,
					],
				],
			],
		],
	],
];
