<?php
/**
 * Listing import complete page template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing import complete page template class.
 *
 * @class Listing_Import_Complete_Page
 */
class Listing_Import_Complete_Page extends Listing_Import_Page {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Class meta values.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label' => hivepress()->translator->get_string( 'import_listings' ) . ' (' . hivepress()->translator->get_string( 'completed' ) . ')',
			],
			$meta
		);

		parent::init( $meta );
	}

	/**
	 * Class constructor.
	 *
	 * @param array $args Template arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_trees(
			[
				'blocks' => [
					'page_content' => [
						'blocks' => [
							'listing_import_complete_message' => [
								'type'   => 'part',
								'path'   => 'listing/import/listing-import-complete-message',
								'_label' => hivepress()->translator->get_string( 'message' ),
								'_order' => 10,
							],
						],
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
