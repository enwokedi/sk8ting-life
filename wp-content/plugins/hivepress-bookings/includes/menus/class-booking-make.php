<?php
/**
 * Booking make menu.
 *
 * @package HivePress\Menus
 */

namespace HivePress\Menus;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking make menu class.
 *
 * @class Booking_Make
 */
class Booking_Make extends Menu {

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
					'booking_make'          => [
						'route'  => 'booking_make_page',
						'_order' => 0,
					],

					'booking_make_details'  => [
						'route'  => 'booking_make_details_page',
						'_order' => 10,
					],

					'booking_make_complete' => [
						'route'  => 'booking_make_complete_page',
						'_order' => 1000,
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
