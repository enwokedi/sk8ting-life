<?php
/**
 * Plugin Name: HivePress Memberships
 * Description: Charge users for accessing various features.
 * Version: 2.1.0
 * Author: HivePress
 * Author URI: https://hivepress.io/
 * Text Domain: hivepress-memberships
 * Domain Path: /languages/
 *
 * @package HivePress
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Register extension directory.
add_filter(
	'hivepress/v1/extensions',
	function ( $extensions ) {
		$extensions[] = __DIR__;

		return $extensions;
	}
);
