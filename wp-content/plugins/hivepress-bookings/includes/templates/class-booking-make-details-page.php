<?php
/**
 * Booking make details page template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking make details page template class.
 *
 * @class Booking_Make_Details_Page
 */
class Booking_Make_Details_Page extends Booking_Make_Page {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Class meta values.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label' => esc_html__( 'Confirm Booking', 'hivepress-bookings' ) . ' (' . hivepress()->translator->get_string( 'details' ) . ')',
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
							'booking_confirm_form' => [
								'type'   => 'form',
								'form'   => 'booking_confirm',
								'_label' => hivepress()->translator->get_string( 'form' ),
								'_order' => 10,

								'footer' => [
									'form_actions' => [
										'type'       => 'container',
										'_order'     => 10,

										'attributes' => [
											'class' => [ 'hp-form__actions' ],
										],

										'blocks'     => [
											'booking_dates_change_link' => [
												'type'   => 'part',
												'path'   => 'booking/make/booking-dates-change-link',
												'_order' => 10,
											],
										],
									],
								],
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
