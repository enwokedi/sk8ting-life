<?php
/**
 * Booking decline form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking decline form class.
 *
 * @class Booking_Decline
 */
class Booking_Decline extends Model_Form {

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
				'redirect' => true,

				'fields'   => [
					'reason' => [
						'label'      => esc_html__( 'Reason', 'hivepress-bookings' ),
						'type'       => 'textarea',
						'max_length' => 2048,
						'required'   => true,
						'_separate'  => true,
						'_order'     => 10,
					],
				],

				'button'   => [
					'label' => esc_html__( 'Decline Booking', 'hivepress-bookings' ),
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Bootstraps form properties.
	 */
	protected function boot() {

		// Set action.
		if ( $this->model->get_id() ) {
			$this->action = hivepress()->router->get_url(
				'booking_decline_action',
				[
					'booking_id' => $this->model->get_id(),
				]
			);
		}

		parent::boot();
	}
}
