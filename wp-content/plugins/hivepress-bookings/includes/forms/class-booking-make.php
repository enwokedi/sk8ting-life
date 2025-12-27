<?php
/**
 * Booking make form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking make form class.
 *
 * @class Booking_Make
 */
class Booking_Make extends Model_Form {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'model' => 'booking',
			],
			$meta
		);

		parent::init( $meta );
	}

	/**
	 * Class constructor.
	 *
	 * @param array $args Form arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'action' => hivepress()->router->get_url( 'booking_make_page' ),
				'method' => 'GET',

				'fields' => [
					'_dates'  => [
						'label'      => esc_html__( 'Dates', 'hivepress-bookings' ),
						'type'       => 'date_range',
						'offset'     => 0,
						'min_length' => 1,
						'required'   => true,
						'_separate'  => true,
						'_order'     => 10,
					],

					'listing' => [
						'display_type' => 'hidden',
					],
				],

				'button' => [
					'label' => esc_html__( 'Book Now', 'hivepress-bookings' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
