<?php
/**
 * Plugin Name: HivePress SEO
 * Description: Improve SEO of your directory or marketplace.
 * Version: 1.0.0
 * Author: HivePress
 * Author URI: https://hivepress.io/
 * Text Domain: hivepress-seo
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

// Include the updates manager.
require_once __DIR__ . '/vendor/hivepress/hivepress-updates/hivepress-updates.php';
