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
	'listing_statistics' => [
		'title'  => esc_html__( 'Statistics', 'hivepress-statistics' ),
		'screen' => 'listing',

		'blocks' => [
			'listing_statistics' => [
				'type'   => 'listing_statistics',
				'_order' => 10,
			],
		],
	],
];
