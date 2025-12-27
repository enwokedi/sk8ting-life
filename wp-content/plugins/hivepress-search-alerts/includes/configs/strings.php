<?php
/**
 * Strings configuration.
 *
 * @package HivePress\Configs
 */

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

return [
	'listings_found' => esc_html__( 'Listings Found', 'hivepress-search-alerts' ),
	'requests_found' => esc_html__( 'Requests Found', 'hivepress-search-alerts' ),
];
