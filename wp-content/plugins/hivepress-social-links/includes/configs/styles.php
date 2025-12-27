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
	'social_links_frontend' => [
		'handle'  => 'hivepress-social-links-frontend',
		'src'     => hivepress()->get_url( 'social_links' ) . '/assets/css/frontend.min.css',
		'version' => hivepress()->get_version( 'social_links' ),
		'scope'   => [ 'frontend', 'editor' ],
	],
];
