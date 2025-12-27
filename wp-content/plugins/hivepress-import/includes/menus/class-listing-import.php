<?php
/**
 * Listing import menu.
 *
 * @package HivePress\Menus
 */

namespace HivePress\Menus;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing import menu class.
 *
 * @class Listing_Import
 */
class Listing_Import extends Menu {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Menu meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'chained' => true,
			],
			$meta
		);

		parent::init( $meta );
	}

	/**
	 * Class constructor.
	 *
	 * @param array $args Menu arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'items' => [
					'listing_import'          => [
						'route'  => 'listing_import_page',
						'_order' => 0,
					],

					'listing_import_upload'   => [
						'route'  => 'listing_import_upload_page',
						'_order' => 10,
					],

					'listing_import_process'  => [
						'route'  => 'listing_import_process_page',
						'_order' => 20,
					],

					'listing_import_complete' => [
						'route'  => 'listing_import_complete_page',
						'_order' => 1000,
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
