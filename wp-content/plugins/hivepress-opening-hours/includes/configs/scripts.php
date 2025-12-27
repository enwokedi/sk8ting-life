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
	'opening_hours' => [
		'handle'  => 'hivepress-opening-hours',
		'src'     => hivepress()->get_url( 'opening_hours' ) . '/assets/js/common.min.js',
		'version' => hivepress()->get_version( 'opening_hours' ),
		'deps'    => [ 'hivepress-core' ],
		'scope'   => [ 'frontend', 'backend' ],
	],
];
