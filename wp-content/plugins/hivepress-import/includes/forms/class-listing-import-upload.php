<?php
/**
 * Listing import upload form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing import upload form class.
 *
 * @class Listing_Import_Upload
 */
class Listing_Import_Upload extends Form {

	/**
	 * Class constructor.
	 *
	 * @param array $args Form arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'action'   => hivepress()->router->get_url( 'listing_import_upload_action' ),
				'redirect' => true,

				'fields'   => [
					'file'      => [
						'label'    => esc_html__( 'CSV File', 'hivepress-import' ),
						'type'     => 'file',
						'formats'  => [ 'csv', 'txt' ],
						'required' => true,
						'_order'   => 10,
					],

					'delimiter' => [
						'label'      => esc_html__( 'CSV Delimiter', 'hivepress-import' ),
						'type'       => 'text',
						'max_length' => 1,
						'default'    => ',',
						'required'   => true,
						'_order'     => 20,
					],

					'mode'      => [
						'label'       => esc_html__( 'Import Mode', 'hivepress-import' ),
						'placeholder' => esc_html__( 'Create new only', 'hivepress-import' ),
						'type'        => 'select',
						'_order'      => 30,

						'options'     => [
							'append' => esc_html__( 'Update existing or create new', 'hivepress-import' ),
							'update' => esc_html__( 'Update existing only', 'hivepress-import' ),
						],
					],

					'category'  => [
						'label'       => hivepress()->translator->get_string( 'listing_category' ),
						'type'        => 'select',
						'options'     => 'terms',
						'option_args' => [ 'taxonomy' => 'hp_listing_category' ],
						'_order'      => 40,
					],
				],

				'button'   => [
					'label' => esc_html__( 'Upload', 'hivepress-import' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
