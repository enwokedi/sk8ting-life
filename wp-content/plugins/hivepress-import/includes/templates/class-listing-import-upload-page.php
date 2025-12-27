<?php
/**
 * Listing import upload page template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing import upload page template class.
 *
 * @class Listing_Import_Upload_Page
 */
class Listing_Import_Upload_Page extends Listing_Import_Page {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Class meta values.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label' => hivepress()->translator->get_string( 'import_listings' ) . ' (' . esc_html__( 'Upload', 'hivepress-import' ) . ')',
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
							'listing_import_upload_form' => [
								'type'   => 'form',
								'form'   => 'listing_import_upload',
								'_label' => hivepress()->translator->get_string( 'form' ),
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
