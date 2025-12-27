<?php
/**
 * Listing import process form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing import process form class.
 *
 * @class Listing_Import_Process
 */
class Listing_Import_Process extends Form {

	/**
	 * Class constructor.
	 *
	 * @param array $args Form arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'action'   => hivepress()->router->get_url( 'listing_import_process_action' ),
				'redirect' => true,

				'fields'   => [],

				'button'   => [
					'label' => esc_html__( 'Import', 'hivepress-import' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
