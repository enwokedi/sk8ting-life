<?php
/**
 * Styles configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'bookings'          => [
		'handle'  => 'hivepress-bookings',
		'src'     => hivepress()->get_url( 'bookings' ) . '/assets/css/common.min.css',
		'version' => hivepress()->get_version( 'bookings' ),
		'scope'   => [ 'frontend', 'backend' ],
	],

	'bookings_frontend' => [
		'handle'  => 'hivepress-bookings-frontend',
		'src'     => hivepress()->get_url( 'bookings' ) . '/assets/css/frontend.min.css',
		'version' => hivepress()->get_version( 'bookings' ),
	],
];
