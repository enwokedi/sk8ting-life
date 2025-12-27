<?php
/**
 * Listing range dates form.
 *
 * @package HivePress\Forms
 */

namespace HivePress\Forms;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listing range dates form class.
 *
 * @class Listing_Range_Dates
 */
class Listing_Range_Dates extends Model_Form {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'model' => 'listing',
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
				'message' => hivepress()->translator->get_string( 'changes_have_been_saved' ),

				'fields'  => [
					'start_date' => [
						'type'         => 'date',
						'display_type' => 'hidden',
						'format'       => 'Y-m-d H:i:s',
						'_separate'    => true,
					],

					'end_date'   => [
						'type'         => 'date',
						'display_type' => 'hidden',
						'format'       => 'Y-m-d H:i:s',
						'_separate'    => true,
					],

					'price'      => [
						'label'     => hivepress()->translator->get_string( 'price' ),
						'type'      => 'currency',
						'min_value' => 0,
						'required'  => true,
						'_separate' => true,
						'_order'    => 10,
					],
				],

				'button'  => [
					'label' => hivepress()->translator->get_string( 'save_changes' ),
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
				'listing_range_dates_action',
				[
					'listing_id' => $this->model->get_id(),
				]
			);
		}

		parent::boot();
	}
}
