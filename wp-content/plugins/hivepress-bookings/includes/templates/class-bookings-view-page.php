<?php
/**
 * Bookings view page template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bookings view page template class.
 *
 * @class Bookings_View_Page
 */
class Bookings_View_Page extends User_Account_Page {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Class meta values.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label' => esc_html__( 'Bookings', 'hivepress-bookings' ) . ' (' . hivepress()->translator->get_string( 'user' ) . ')',
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
							'bookings'           => [
								'type'   => 'bookings',
								'_label' => esc_html__( 'Bookings', 'hivepress-bookings' ),
								'_order' => 10,
							],

							'booking_pagination' => [
								'type'   => 'part',
								'path'   => 'page/pagination',
								'_label' => hivepress()->translator->get_string( 'pagination' ),
								'_order' => 20,
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
