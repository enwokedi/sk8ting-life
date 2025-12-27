<?php
/**
 * Booking confirm form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking confirm form class.
 *
 * @class Booking_Confirm
 */
class Booking_Confirm extends Booking_Update {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'   => esc_html__( 'Confirm Booking', 'hivepress-bookings' ),
				'captcha' => false,
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
				'message'  => null,
				'redirect' => true,

				'fields'   => [
					'start_time' => [
						'disabled' => true,
					],

					'end_time'   => [
						'disabled' => true,
					],
				],

				'button'   => [
					'label' => esc_html__( 'Confirm Booking', 'hivepress-bookings' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}
}
