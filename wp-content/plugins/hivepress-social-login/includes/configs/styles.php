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
	'social_login_frontend' => [
		'handle'  => 'hivepress-social-login-frontend',
		'src'     => hivepress()->get_url( 'social_login' ) . '/assets/css/frontend.min.css',
		'version' => hivepress()->get_version( 'social_login' ),
		'scope'   => [ 'frontend', 'editor' ],
	],
];
