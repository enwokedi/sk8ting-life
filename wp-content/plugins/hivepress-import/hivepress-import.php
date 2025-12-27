<?php
/**
 * Plugin Name: HivePress Import
 * Description: Allow users to import listings.
 * Version: 1.2.2
 * Author: HivePress
 * Author URI: https://hivepress.io/
 * Text Domain: hivepress-import
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
