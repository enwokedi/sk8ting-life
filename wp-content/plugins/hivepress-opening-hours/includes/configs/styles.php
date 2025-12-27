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
	'opening_hours' => [
		'handle'  => 'hivepress-opening-hours',
		'src'     => hivepress()->get_url( 'opening_hours' ) . '/assets/css/common.min.css',
		'version' => hivepress()->get_version( 'opening_hours' ),
		'scope'   => [ 'frontend', 'backend' ],
	],
];
