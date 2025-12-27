<?php
/**
 * Scripts configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'bookings' => [
		'handle'  => 'hivepress-bookings',
		'src'     => hivepress()->get_url( 'bookings' ) . '/assets/js/common.min.js',
		'version' => hivepress()->get_version( 'bookings' ),
		'deps'    => [ 'hivepress-core' ],
		'scope'   => [ 'frontend', 'backend' ],

		'data'    => [
			'blockText'       => esc_html__( 'Block', 'hivepress-bookings' ),
			'unblockText'     => esc_html__( 'Unblock', 'hivepress-bookings' ),
			'changePriceText' => esc_html__( 'Change Price', 'hivepress-bookings' ),
		],
	],
];
