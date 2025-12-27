<?php
/**
 * Plugin Name: HivePress Social Links
 * Description: Add social links to listings.
 * Version: 1.0.3
 * Author: HivePress
 * Author URI: https://hivepress.io/
 * Text Domain: hivepress-social-links
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
