<?php
/**
 * Booking view page template.
 *
 * @package HivePress\Templates
 */

namespace HivePress\Templates;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking view page template class.
 *
 * @class Booking_View_Page
 */
class Booking_View_Page extends Page_Sidebar_Right {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Class meta values.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label' => hivepress()->translator->get_string( 'booking' ),
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
				'attributes' => [
					'class' => [ 'hp-listing', 'hp-listing--view-page' ],
				],

				'blocks'     => [
					'page_title'   => [
						'type' => 'content',
					],

					'page_content' => [
						'attributes' => [
							'class' => [ 'hp-listing', 'hp-listing--view-page', 'hp-booking', 'hp-booking--view-page' ],
						],

						'blocks'     => [
							'booking_title'                => [
								'type'   => 'part',
								'path'   => 'booking/view/page/booking-title',
								'_label' => hivepress()->translator->get_string( 'title' ),
								'_order' => 10,
							],

							'booking_details_primary'      => [
								'type'       => 'container',
								'_label'     => hivepress()->translator->get_string( 'details' ),
								'_order'     => 20,

								'attributes' => [
									'class' => [ 'hp-listing__details', 'hp-listing__details--primary' ],
								],

								'blocks'     => [
									'booking_listing'      => [
										'type'   => 'part',
										'path'   => 'booking/view/booking-listing',
										'_label' => hivepress()->translator->get_string( 'listing' ),
										'_order' => 5,
									],

									'booking_created_date' => [
										'type'   => 'part',
										'path'   => 'booking/view/booking-created-date',
										'_label' => hivepress()->translator->get_string( 'date' ) . ' (' . esc_html_x( 'Confirmed', 'booking', 'hivepress-bookings' ) . ')',
										'_order' => 10,
									],

									'booking_canceled_date' => [
										'type'   => 'part',
										'path'   => 'booking/view/booking-canceled-date',
										'_label' => hivepress()->translator->get_string( 'date' ) . ' (' . esc_html_x( 'Canceled', 'booking', 'hivepress-bookings' ) . ')',
										'_order' => 20,
									],

									'booking_status'       => [
										'type'   => 'part',
										'path'   => 'booking/view/booking-status',
										'_label' => hivepress()->translator->get_string( 'status' ),
										'_order' => 30,
									],
								],
							],

							'booking_attributes_secondary' => [
								'type'   => 'attributes',
								'model'  => 'booking',
								'area'   => 'view_page_secondary',
								'alias'  => 'listing',
								'_label' => hivepress()->translator->get_string( 'attributes' ) . ' (' . hivepress()->translator->get_string( 'secondary_plural' ) . ')',
								'_order' => 30,
							],

							'booking_attributes_ternary'   => [
								'type'      => 'attributes',
								'model'     => 'booking',
								'area'      => 'view_page_ternary',
								'alias'     => 'listing',
								'_label'    => hivepress()->translator->get_string( 'attributes' ) . ' (' . hivepress()->translator->get_string( 'ternary_plural' ) . ')',
								'_settings' => [ 'columns' ],
								'_order'    => 40,
							],

							'booking_note'                 => [
								'type'   => 'part',
								'path'   => 'booking/view/page/booking-notes',
								'_label' => esc_html__( 'Notes', 'hivepress-bookings' ),
								'_order' => 50,
							],
						],
					],

					'page_sidebar' => [
						'attributes' => [
							'data-component' => 'sticky',
						],

						'blocks'     => [
							'booking_sidebar' => [
								'type'       => 'container',
								'_order'     => 10,

								'attributes' => [
									'class' => [ 'hp-listing', 'hp-listing--view-page', 'hp-booking', 'hp-booking--view-page', 'hp-booking__sidebar' ],
								],

								'blocks'     => [
									'booking_attributes_primary' => [
										'type'       => 'container',
										'_label'     => hivepress()->translator->get_string( 'attributes' ) . ' (' . hivepress()->translator->get_string( 'primary_plural' ) . ')',

										'attributes' => [
											'class' => [ 'hp-listing__attributes', 'hp-listing__attributes--primary', 'hp-widget', 'widget' ],
										],

										'blocks'     => [
											'booking_dates' => [
												'type'   => 'part',
												'path'   => 'booking/view/booking-dates',
												'_order' => 10,
											],

											'booking_price' => [
												'type'   => 'part',
												'path'   => 'booking/view/booking-price',
												'_order' => 20,
											],

											'booking_attributes_primary_loop' => [
												'type'   => 'attributes',
												'model'  => 'booking',
												'area'   => 'view_page_primary',
												'alias'  => 'listing',
												'tag'    => false,
												'_order' => 30,
											],
										],
									],

									'booking_actions_primary' => [
										'type'       => 'container',
										'_label'     => hivepress()->translator->get_string( 'actions' ) . ' (' . hivepress()->translator->get_string( 'primary_plural' ) . ')',
										'_order'     => 20,

										'attributes' => [
											'class' => [ 'hp-listing__actions', 'hp-listing__actions--primary', 'hp-widget', 'widget' ],
										],

										'blocks'     => [
											'booking_accept_modal' => [
												'type'   => 'modal',
												'title'  => esc_html__( 'Accept Booking', 'hivepress-bookings' ),
												'_capability' => 'edit_posts',
												'_order' => 5,

												'blocks' => [
													'booking_accept_form' => [
														'type'   => 'form',
														'form'   => 'booking_accept',
														'_order' => 10,
													],
												],
											],

											'booking_decline_modal' => [
												'type'   => 'modal',
												'title'  => esc_html__( 'Decline Booking', 'hivepress-bookings' ),
												'_capability' => 'edit_posts',
												'_order' => 5,

												'blocks' => [
													'booking_decline_form' => [
														'type'   => 'form',
														'form'   => 'booking_decline',
														'_order' => 10,
													],
												],
											],

											'booking_cancel_modal' => [
												'type'   => 'modal',
												'title'  => esc_html__( 'Cancel Booking', 'hivepress-bookings' ),
												'_capability' => 'read',
												'_order' => 5,

												'blocks' => [
													'booking_cancel_form' => [
														'type'   => 'form',
														'form'   => 'booking_cancel',
														'_order' => 10,
													],
												],
											],

											'booking_pay_link'     => [
												'type'   => 'part',
												'path'   => 'booking/view/page/booking-pay-link',
												'_order' => 10,
											],

											'order_view_link'  => [
												'type'   => 'part',
												'path'   => 'booking/view/page/order-view-link',
												'_order' => 20,
											],

											'booking_accept_link' => [
												'type'   => 'part',
												'path'   => 'booking/view/page/booking-accept-link',
												'_order' => 30,
											],

											'booking_decline_link'  => [
												'type'   => 'part',
												'path'   => 'booking/view/page/booking-decline-link',
												'_order' => 40,
											],

											'booking_cancel_link'  => [
												'type'   => 'part',
												'path'   => 'booking/view/page/booking-cancel-link',
												'_order' => 50,
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
