<?php
/**
 * Booking view block template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking view block template class.
 *
 * @class Booking_View_Block
 */
class Booking_View_Block extends Template {

	/**
	 * Class constructor.
	 *
	 * @param array $args Template arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_trees(
			[
				'blocks' => [
					'booking_container' => [
						'type'       => 'container',
						'_order'     => 10,

						'attributes' => [
							'class' => [ 'hp-listing', 'hp-listing--view-block', 'hp-booking', 'hp-booking--view-block' ],
						],

						'blocks'     => [
							'booking_content' => [
								'type'       => 'container',
								'_order'     => 10,

								'attributes' => [
									'class' => [ 'hp-listing__content' ],
								],

								'blocks'     => [
									'booking_title' => [
										'type'   => 'part',
										'path'   => 'booking/view/block/booking-title',
										'_order' => 10,
									],

									'booking_details_primary' => [
										'type'       => 'container',
										'_order'     => 20,

										'attributes' => [
											'class' => [ 'hp-listing__details', 'hp-listing__details--primary' ],
										],

										'blocks'     => [
											'booking_listing' => [
												'type'   => 'part',
												'path'   => 'booking/view/booking-listing',
												'_order' => 5,
											],

											'booking_created_date' => [
												'type'   => 'part',
												'path'   => 'booking/view/booking-created-date',
												'_order' => 10,
											],

											'booking_status'       => [
												'type'   => 'part',
												'path'   => 'booking/view/booking-status',
												'_order' => 20,
											],
										],
									],

									'booking_attributes_secondary' => [
										'type'   => 'attributes',
										'model'  => 'booking',
										'area'   => 'view_block_secondary',
										'alias'  => 'listing',
										'_order' => 30,
									],

									'booking_attributes_ternary' => [
										'type'   => 'attributes',
										'model'  => 'booking',
										'area'   => 'view_block_ternary',
										'alias'  => 'listing',
										'_order' => 40,
									],
								],
							],

							'booking_footer'  => [
								'type'       => 'container',
								'tag'        => 'footer',
								'_order'     => 20,

								'attributes' => [
									'class' => [ 'hp-listing__footer' ],
								],

								'blocks'     => [
									'booking_attributes_primary' => [
										'type'       => 'container',
										'_order'     => 10,

										'attributes' => [
											'class' => [ 'hp-listing__attributes', 'hp-listing__attributes--primary' ],
										],

										'blocks'     => [
											'booking_dates' => [
												'type'   => 'part',
												'path'   => 'booking/view/booking-dates',
												'_order' => 10,
											],

											'booking_attributes_primary_loop' => [
												'type'   => 'attributes',
												'model'  => 'booking',
												'area'   => 'view_block_primary',
												'alias'  => 'listing',
												'tag'    => false,
												'_order' => 20,
											],
										],
									],

									'booking_actions_primary'    => [
										'type'       => 'container',
										'_order'     => 20,

										'attributes' => [
											'class' => [ 'hp-listing__actions', 'hp-listing__actions--primary' ],
										],

										'blocks'     => [
											'booking_pay_link'     => [
												'type'   => 'part',
												'path'   => 'booking/view/block/booking-pay-link',
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
