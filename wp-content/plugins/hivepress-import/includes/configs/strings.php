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
	'import_listings'   => esc_html__( 'Import Listings', 'hivepress-import' ),
	'export_listings'   => esc_html__( 'Export Listings', 'hivepress-import' ),
	'listings_imported' => esc_html__( 'Listings Imported', 'hivepress-import' ),
];
