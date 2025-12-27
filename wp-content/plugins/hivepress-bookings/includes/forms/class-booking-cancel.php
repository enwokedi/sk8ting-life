<?php
/**
 * Booking cancel form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking cancel form class.
 *
 * @class Booking_Cancel
 */
class Booking_Cancel extends Model_Form {

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
				'description' => esc_html__( 'Are you sure you want to cancel this booking?', 'hivepress-bookings' ),
				'method'      => 'DELETE',
				'redirect'    => true,

				'button'      => [
					'label' => esc_html__( 'Cancel Booking', 'hivepress-bookings' ),
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
				'booking_cancel_action',
				[
					'booking_id' => $this->model->get_id(),
				]
			);
		}

		parent::boot();
	}
}
