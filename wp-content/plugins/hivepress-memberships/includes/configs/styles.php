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
	'memberships_frontend' => [
		'handle'  => 'hivepress-memberships-frontend',
		'src'     => hivepress()->get_url( 'memberships' ) . '/assets/css/frontend.min.css',
		'version' => hivepress()->get_version( 'memberships' ),
		'scope'   => [ 'frontend', 'editor' ],
	],
];
