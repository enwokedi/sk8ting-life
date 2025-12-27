<?php
/**
 * Booking make page template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking make page template class.
 *
 * @class Booking_Make_Page
 */
abstract class Booking_Make_Page extends Page_Sidebar_Left {

	/**
	 * Class constructor.
	 *
	 * @param array $args Template arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_trees(
			[
				'blocks' => [
					'page_content' => [],

					'page_sidebar' => [
						'blocks' => [
							'booking_listing' => [
								'type'     => 'template',
								'template' => 'listing_view_block',
								'_label'   => hivepress()->translator->get_string( 'listing' ),
								'_order'   => 10,
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
