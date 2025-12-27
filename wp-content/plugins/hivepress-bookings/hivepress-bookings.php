<?php
/**
 * Plugin Name: HivePress Bookings
 * Description: Allow users to book listings.
 * Version: 1.5.5
 * Author: HivePress
 * Author URI: https://hivepress.io/
 * Text Domain: hivepress-bookings
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
