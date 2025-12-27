<?php
/**
 * Booking calendar block.
 *
 * @package HivePress\Blocks
 */

namespace HivePress\Blocks;

use HivePress\Helpers as hp;
use HivePress\Models;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking calendar block class.
 *
 * @class Booking_Calendar
 */
class Booking_Calendar extends Block {

	/**
	 * Calendar view.
	 *
	 * @var string
	 */
	protected $view = 'month';

	/**
	 * Common flag.
	 *
	 * @var bool
	 */
	protected $common = false;

	/**
	 * Calendar attributes.
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Bootstraps block properties.
	 */
	protected function boot() {
		$attributes = [];

		// Get settings.
		$per_vendor = get_option( 'hp_booking_per_vendor' );

		// Get listing.
		$listing = $this->get_context( 'listing' );

		if ( is_admin() ) {
			$listing = Models\Listing::query()->get_by_id( get_post() );
		}

		if ( ! $listing ) {

			// Get vendor.
			$vendor = $this->get_context( 'vendor' );

			if ( is_admin() ) {
				$vendor = Models\Vendor::query()->get_by_id( get_post() );
			}

			if ( $vendor ) {

				// Set common flag.
				if ( ! $per_vendor ) {
					$this->common = true;

					$attributes['data-common'] = $this->common;
				}

				// Get listing.
				$listing = Models\Listing::query()->filter(
					[
						'status__in' => [ 'draft', 'pending', 'publish' ],
						'vendor'     => $vendor->get_id(),
					]
				)->get_first();
			}
		}

		if ( $listing ) {

			// Set view.
			if ( hivepress()->booking->is_time_enabled( $listing ) ) {
				$this->view = 'week';
			}

			// Set request context.
			$this->set_context( 'listing', $listing );

			// Set block URL.
			$attributes['data-block-url'] = hivepress()->router->get_url( 'listing_block_dates_action', [ 'listing_id' => $listing->get_id() ] );

			// Set unblock URL.
			$attributes['data-unblock-url'] = hivepress()->router->get_url( 'listing_unblock_dates_action', [ 'listing_id' => $listing->get_id() ] );

			// Set range URL.
			if ( get_option( 'hp_booking_enable_price' ) ) {
				$attributes['data-range-url'] = '#listing_range_dates_modal';
			}

			if ( 'week' === $this->view ) {

				// Set minimum time.
				$attributes['data-min-time'] = date( 'H:i:s', $listing->get_booking_min_time() );

				// Set maximum time.
				$max_time = $listing->get_booking_max_time();

				if ( ! $max_time ) {
					$max_time = DAY_IN_SECONDS - 1;
				}

				$attributes['data-max-time'] = date( 'H:i:s', $max_time );

				if ( $listing->get_booking_min_time() > $max_time ) {
					unset( $attributes['data-min-time'], $attributes['data-max-time'] );
				}

				// Set slot duration.
				$slot_duration = $listing->get_booking_slot_duration() + $listing->get_booking_slot_interval();

				if ( ! $slot_duration || $per_vendor || $this->common ) {
					$slot_duration = 30;
				}

				$attributes['data-slot-duration'] = date( 'H:i:s', $slot_duration * 60 );
			}
		}

		// Set view.
		$attributes['data-view'] = $this->view;

		// Set minimum date.
		$attributes['data-min-date'] = date( 'Y-m-d', strtotime( 'today' ) );

		// Set options.
		$attributes['data-options'] = wp_json_encode(
			[
				'firstDay' => absint( get_option( 'start_of_week' ) ),
			]
		);

		// Set component.
		$attributes['data-component'] = 'calendar';

		// Set class.
		$attributes['class'] = [ 'hp-calendar' ];

		// Set attributes.
		$this->attributes = hp\merge_arrays( $this->attributes, $attributes );

		parent::boot();
	}

	/**
	 * Renders block HTML.
	 *
	 * @return string
	 */
	public function render() {
		$output = '';

		// Get settings.
		$is_multiple = get_option( 'hp_booking_enable_quantity' );
		$is_daily    = get_option( 'hp_booking_enable_daily' );

		// Get listing.
		$listing = $this->get_context( 'listing' );

		if ( $listing ) {

			// Get listing IDs.
			$listing_ids = hivepress()->booking->get_listing_ids( $listing, $this->common );

			// Get ranges.
			$ranges = [];

			if ( get_option( 'hp_booking_enable_price' ) && ! $this->common ) {
				$booking_ranges = Models\Booking_Range::query()->filter(
					[
						'listing__in' => $listing_ids,
					]
				)->get();

				foreach ( $booking_ranges as $range ) {
					$ranges[] = [
						'start' => $range->get_start_time(),
						'end'   => $range->get_end_time(),
						'price' => html_entity_decode( (string) $range->display_price() ),
					];
				}
			}

			// Get bookings.
			$statuses = [ 'draft', 'pending', 'publish' ];

			if ( ! $this->common ) {
				$statuses[] = 'private';
			}

			$bookings = Models\Booking::query()->filter(
				[
					'status__in'  => $statuses,
					'listing__in' => $listing_ids,
				]
			)->get();

			// Get events.
			$events = [];

			foreach ( $bookings as $booking ) {
				$event = [
					'start' => $booking->get_start_time(),
					'end'   => $booking->get_end_time(),
				];

				if ( 'month' === $this->view ) {
					$event['allDay'] = true;
				}

				if ( $booking->get_status() === 'private' ) {
					$event = array_merge(
						$event,
						[
							'groupId'    => 'blocked',
							'display'    => 'background',
							'classNames' => [ 'fc-blocked' ],
						]
					);
				} else {
					$event = array_merge(
						$event,
						[
							'id'    => $booking->get_id(),
							'title' => '#' . $booking->get_id() . ( $is_multiple ? ' (' . $booking->get_quantity() . ')' : null ),
							'url'   => is_admin() ? hivepress()->router->get_admin_url( 'post', $booking->get_id() ) : hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ),
						]
					);

					if ( $is_daily ) {
						$event['end'] += 1;
					}

					if ( $this->common ) {
						$event['extendedProps'] = [
							'description' => $booking->get_listing__title(),
						];
					}
				}

				$event['start'] = date( 'Y-m-d H:i:s', $event['start'] );
				$event['end']   = date( 'Y-m-d H:i:s', $event['end'] );

				$events[] = $event;
			}

			// Render calendar.
			$output = '<div data-events="' . hp\esc_json( wp_json_encode( $events ) ) . '" data-ranges="' . hp\esc_json( wp_json_encode( $ranges ) ) . '" ' . hp\html_attributes( $this->attributes ) . '></div>';

			if ( get_option( 'hp_booking_enable_price' ) && ! $this->common ) {
				$output .= ( new Modal(
					[
						'name'   => 'listing_range_dates_modal',
						'title'  => esc_html__( 'Change Price', 'hivepress-bookings' ),

						'blocks' => [
							'listing_range_dates_form' => [
								'type'    => 'form',
								'form'    => 'listing_range_dates',
								'_order'  => 10,

								'context' => [
									'listing' => $listing,
								],
							],
						],
					]
				) )->render();
			}
		}

		return $output;
	}
}
