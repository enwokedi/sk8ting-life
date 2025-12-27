<?php
/**
 * Plugin Name: HivePress Social Login
 * Description: Allow users to sign in via third-party platforms.
 * Version: 1.0.6
 * Author: HivePress
 * Author URI: https://hivepress.io/
 * Text Domain: hivepress-social-login
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
