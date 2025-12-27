<?php
/**
 * Listing export form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing export form class.
 *
 * @class Listing_Export
 */
class Listing_Export extends Form {

	/**
	 * Class constructor.
	 *
	 * @param array $args Form arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'action'   => hivepress()->router->get_url( 'listing_export_action' ),
				'redirect' => hivepress()->router->get_url( 'listing_export_download_page' ),

				'fields'   => [
					'category'  => [
						'label'       => hivepress()->translator->get_string( 'listing_category' ),
						'placeholder' => hivepress()->translator->get_string( 'all_categories' ),
						'type'        => 'select',
						'options'     => 'terms',
						'option_args' => [ 'taxonomy' => 'hp_listing_category' ],
						'_order'      => 10,
					],

					'delimiter' => [
						'label'      => esc_html__( 'CSV Delimiter', 'hivepress-import' ),
						'type'       => 'text',
						'max_length' => 1,
						'default'    => ',',
						'required'   => true,
						'_order'     => 20,
					],
				],

				'button'   => [
					'label' => esc_html__( 'Export', 'hivepress-import' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
