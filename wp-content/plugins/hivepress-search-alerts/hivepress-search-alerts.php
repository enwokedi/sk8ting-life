<?php
/**
 * Plugin Name: HivePress Search Alerts
 * Description: Notify users about new listings.
 * Version: 1.1.3
 * Author: HivePress
 * Author URI: https://hivepress.io/
 * Text Domain: hivepress-search-alerts
 * Domain Path: /languages/
 *
 * @package HivePress
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Register extension directory.
add_filter(
	'hivepress/v1/extensions',
	function( $extensions ) {
		$extensions[] = __DIR__;

		return $extensions;
	}
);
