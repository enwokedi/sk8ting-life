<?php
/**
 * Booking update form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking update form class.
 *
 * @class Booking_Update
 */
class Booking_Update extends Model_Form {

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
				'fields' => [
					'start_time' => [
						'type'   => 'date',
						'format' => 'U',
						'_order' => 10,
					],

					'end_time'   => [
						'type'   => 'date',
						'format' => 'U',
						'_order' => 20,
					],

					'note'       => [
						'html'   => false,
						'_order' => 200,
					],
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
				'booking_update_action',
				[
					'booking_id' => $this->model->get_id(),
				]
			);
		}

		parent::boot();
	}
}
