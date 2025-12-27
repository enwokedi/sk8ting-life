<?php
/**
 * WooCommerce configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'item_meta' => [
		'booking'  => [
			'type' => 'id',
		],

		'quantity' => [
			'label' => hivepress()->translator->get_string( 'places' ),
			'type'  => 'number',
		],
	],
];
