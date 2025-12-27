<?php
/**
 * Booking controller.
 *
 * @package HivePress\Controllers
 */

namespace HivePress\Controllers;

use HivePress\Helpers as hp;
use HivePress\Blocks;
use HivePress\Models;
use HivePress\Forms;
use HivePress\Emails;
use Spatie\IcalendarGenerator;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking controller class.
 *
 * @class Booking
 */
final class Booking extends Controller {

	/**
	 * Class constructor.
	 *
	 * @param array $args Controller arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'routes' => [
					'bookings_resource'            => [
						'path' => '/bookings',
						'rest' => true,
					],

					'booking_resource'             => [
						'base' => 'bookings_resource',
						'path' => '/(?P<booking_id>\d+)',
						'rest' => true,
					],

					'booking_update_action'        => [
						'base'   => 'booking_resource',
						'method' => 'POST',
						'action' => [ $this, 'update_booking' ],
						'rest'   => true,
					],

					'booking_accept_action'        => [
						'base'   => 'booking_resource',
						'path'   => '/accept',
						'method' => 'POST',
						'action' => [ $this, 'accept_booking' ],
						'rest'   => true,
					],

					'booking_decline_action'       => [
						'base'   => 'booking_resource',
						'path'   => '/decline',
						'method' => 'POST',
						'action' => [ $this, 'decline_booking' ],
						'rest'   => true,
					],

					'booking_cancel_action'        => [
						'base'   => 'booking_resource',
						'method' => 'DELETE',
						'action' => [ $this, 'cancel_booking' ],
						'rest'   => true,
					],

					'listing_slots_resource'       => [
						'base'   => 'listing_resource',
						'path'   => '/slots',
						'method' => 'GET',
						'action' => [ $this, 'get_listing_slots' ],
						'rest'   => true,
					],

					'listing_block_dates_action'   => [
						'base'   => 'listing_resource',
						'path'   => '/block-dates',
						'method' => 'POST',
						'action' => [ $this, 'block_dates' ],
						'rest'   => true,
					],

					'listing_unblock_dates_action' => [
						'base'   => 'listing_resource',
						'path'   => '/unblock-dates',
						'method' => 'POST',
						'action' => [ $this, 'unblock_dates' ],
						'rest'   => true,
					],

					'listing_range_dates_action'   => [
						'base'   => 'listing_resource',
						'path'   => '/range-dates',
						'method' => 'POST',
						'action' => [ $this, 'range_dates' ],
						'rest'   => true,
					],

					'bookings_view_page'           => [
						'title'     => esc_html__( 'Bookings', 'hivepress-bookings' ),
						'base'      => 'user_account_page',
						'path'      => '/bookings',
						'redirect'  => [ $this, 'redirect_bookings_view_page' ],
						'action'    => [ $this, 'render_bookings_view_page' ],
						'paginated' => true,
					],

					'booking_view_page'            => [
						'base'     => 'bookings_view_page',
						'path'     => '/(?P<booking_id>\d+)',
						'title'    => [ $this, 'get_booking_view_title' ],
						'redirect' => [ $this, 'redirect_booking_view_page' ],
						'action'   => [ $this, 'render_booking_view_page' ],
					],

					'booking_make_page'            => [
						'path'     => '/make-booking',
						'redirect' => [ $this, 'redirect_booking_make_page' ],
					],

					'booking_make_details_page'    => [
						'title'    => hivepress()->translator->get_string( 'add_details_imperative' ),
						'base'     => 'booking_make_page',
						'path'     => '/details',
						'redirect' => [ $this, 'redirect_booking_make_details_page' ],
						'action'   => [ $this, 'render_booking_make_details_page' ],
					],

					'booking_make_complete_page'   => [
						'base'     => 'booking_make_page',
						'path'     => '/complete',
						'title'    => [ $this, 'get_booking_make_complete_title' ],
						'redirect' => [ $this, 'redirect_booking_make_complete_page' ],
						'action'   => [ $this, 'render_booking_make_complete_page' ],
					],

					'booking_pay_page'             => [
						'base'     => 'booking_view_page',
						'path'     => '/pay',
						'redirect' => [ $this, 'redirect_booking_pay_page' ],
					],

					'booking_pay_complete_page'    => [
						'base'     => 'booking_pay_page',
						'path'     => '/complete',
						'title'    => [ $this, 'get_booking_pay_complete_title' ],
						'redirect' => [ $this, 'redirect_booking_pay_complete_page' ],
						'action'   => [ $this, 'render_booking_pay_complete_page' ],
					],

					'vendor_calendar_page'         => [
						'title'    => esc_html__( 'Calendar', 'hivepress-bookings' ),
						'base'     => 'vendor_account_page',
						'path'     => '/calendar',
						'redirect' => [ $this, 'redirect_vendor_calendar_page' ],
						'action'   => [ $this, 'render_vendor_calendar_page' ],
					],

					'vendor_calendar_file'         => [
						'base'     => 'user_account_page',
						'path'     => '/vendors/(?P<vendor_id>\d+)/calendar/ics',
						'redirect' => [ $this, 'redirect_listing_calendar_file' ],
						'action'   => [ $this, 'render_listing_calendar_file' ],
					],

					'listing_calendar_page'        => [
						'base'     => 'listing_edit_page',
						'path'     => '/calendar',
						'title'    => [ $this, 'get_listing_calendar_title' ],
						'redirect' => [ $this, 'redirect_listing_calendar_page' ],
						'action'   => [ $this, 'render_listing_calendar_page' ],
					],

					'listing_calendar_file'        => [
						'base'     => 'listing_calendar_page',
						'path'     => '/ics',
						'redirect' => [ $this, 'redirect_listing_calendar_file' ],
						'action'   => [ $this, 'render_listing_calendar_file' ],
					],

					'listing_bookings_page'        => [
						'base'      => 'listing_edit_page',
						'path'      => '/bookings',
						'title'     => [ $this, 'get_listing_calendar_title' ],
						'redirect'  => [ $this, 'redirect_listing_bookings_page' ],
						'action'    => [ $this, 'render_listing_bookings_page' ],
						'paginated' => true,
					],
				],
			],
			$args
		);

		parent::__construct( $args );
	}

	/**
	 * Updates booking.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function update_booking( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $request->get_param( 'booking_id' ) );

		if ( ! $booking ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_others_posts' ) && ( get_current_user_id() !== $booking->get_user__id() || ! in_array( $booking->get_status(), [ 'auto-draft' ], true ) ) ) {
			return hp\rest_error( 403 );
		}

		// Validate form.
		$form = null;

		if ( $booking->get_status() === 'auto-draft' ) {
			$form = new Forms\Booking_Confirm( [ 'model' => $booking ] );
		} else {
			$form = new Forms\Booking_Update( [ 'model' => $booking ] );
		}

		$form->set_values( $request->get_params() );

		if ( ! $form->validate() ) {
			return hp\rest_error( 400, $form->get_errors() );
		}

		// Get time range.
		$start_time = $booking->get_start_time();
		$end_time   = $booking->get_end_time();

		if ( $form->get_value( 'start_time' ) ) {
			$start_time = $form->get_value( 'start_time' );
		}

		if ( $form->get_value( 'end_time' ) ) {
			$end_time = $form->get_value( 'end_time' );
		}

		if ( $start_time >= $end_time ) {
			return hp\rest_error( 400, esc_html__( 'The start date can\'t be later than the end date.', 'hivepress-bookings' ) );
		}

		// @todo validate time slots and week days.
		if ( $booking->get_status() === 'auto-draft' ) {

			// Get listing.
			$listing = $booking->get_listing();

			if ( ! $listing || $listing->get_status() !== 'publish' ) {
				return hp\rest_error( 400 );
			}

			// Get time range.
			$min_time  = strtotime( 'today' ) + $listing->get_booking_offset() * DAY_IN_SECONDS;
			$max_time  = strtotime( 'today' ) + $listing->get_booking_window() * DAY_IN_SECONDS;
			$diff_time = $end_time - $start_time;

			// Get settings.
			$date_format = get_option( 'date_format' );
			$is_daily    = get_option( 'hp_booking_enable_daily' );

			// Check start time.
			if ( $start_time < $min_time ) {

				/* translators: %s: date. */
				return hp\rest_error( 400, sprintf( esc_html__( 'The start date can\'t be earlier than %s.', 'hivepress-bookings' ), date_i18n( $date_format, $min_time ) ) );
			}

			// Check end time.
			if ( $listing->get_booking_window() && $end_time > $max_time ) {

				/* translators: %s: date. */
				return hp\rest_error( 400, sprintf( esc_html__( 'The end date can\'t be later than %s.', 'hivepress-bookings' ), date_i18n( $date_format, $max_time ) ) );
			}

			// Check minimum length.
			$min_length = $listing->get_booking_min_length() * DAY_IN_SECONDS;

			if ( $is_daily ) {
				--$min_length;
			}

			if ( $listing->get_booking_min_length() && $diff_time < $min_length ) {
				return hp\rest_error( 400, esc_html__( 'The booking period is too short.', 'hivepress-bookings' ) );
			}

			// Check maximum length.
			$max_length = $listing->get_booking_max_length() * DAY_IN_SECONDS;

			if ( $is_daily ) {
				--$max_length;
			}

			if ( $listing->get_booking_max_length() && $diff_time > $max_length ) {
				return hp\rest_error( 400, esc_html__( 'The booking period is too long.', 'hivepress-bookings' ) );
			}

			// Check availability.
			$booking_query = hivepress()->booking->get_overlapping_query( $start_time, $end_time, $listing );

			$is_booked = false;

			if ( get_option( 'hp_booking_enable_capacity' ) && $listing->get_booking_max_quantity() ) {
				$booking_quantity = $booking->get_quantity();

				foreach ( $booking_query->get() as $overlap_booking ) {
					if ( $overlap_booking->get_status() === 'private' ) {
						$booking_quantity = 1000000;

						break;
					} else {
						$booking_quantity += $overlap_booking->get_quantity();
					}
				}

				$is_booked = $booking_quantity > $listing->get_booking_max_quantity();
			} else {
				$is_booked = $booking_query->get_first_id();
			}

			if ( $is_booked ) {
				return hp\rest_error( 400, esc_html__( 'These dates are not available for booking.', 'hivepress-bookings' ) );
			}
		}

		// Update booking.
		$booking->fill( $form->get_values() );

		if ( ! $booking->get_title() ) {
			$booking->set_title( '#' . $booking->get_id() );
		}

		if ( ! $booking->save() ) {
			return hp\rest_error( 400, $booking->_get_errors() );
		}

		return hp\rest_response(
			200,
			[
				'id' => $booking->get_id(),
			]
		);
	}

	/**
	 * Accepts booking.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function accept_booking( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $request->get_param( 'booking_id' ) );

		if ( ! $booking || $booking->get_status() !== 'pending' ) {
			return hp\rest_error( 404 );
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $listing ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_others_posts' ) && get_current_user_id() !== $listing->get_user__id() ) {
			return hp\rest_error( 403 );
		}

		// Get status.
		$status = 'publish';

		if ( hivepress()->get_version( 'marketplace' ) && ! is_null( $listing->get_price() ) ) {
			$status = 'draft';
		}

		// Update booking.
		$booking->set_status( $status );

		if ( ! $booking->save_status() ) {
			return hp\rest_error( 400, $booking->_get_errors() );
		}

		// Send email.
		if ( 'draft' === $status ) {
			$user = $booking->get_user();

			( new Emails\Booking_Accept(
				[
					'recipient' => $user->get_email(),

					'tokens'    => [
						'user'          => $user,
						'listing'       => $listing,
						'booking'       => $booking,
						'vendor'        => $listing->get_vendor(),
						'user_name'     => $user->get_display_name(),
						'listing_title' => $listing->get_title(),
						'booking_dates' => $booking->display_dates(),
						'booking_url'   => hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ),
					],
				]
			) )->send();
		}

		return hp\rest_response(
			200,
			[
				'id' => $booking->get_id(),
			]
		);
	}

	/**
	 * Declines booking.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function decline_booking( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $request->get_param( 'booking_id' ) );

		if ( ! $booking || $booking->get_status() !== 'pending' ) {
			return hp\rest_error( 404 );
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $listing ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_others_posts' ) && get_current_user_id() !== $listing->get_user__id() ) {
			return hp\rest_error( 403 );
		}

		// Validate form.
		$form = ( new Forms\Booking_Decline() )->set_values( $request->get_params() );

		if ( ! $form->validate() ) {
			return hp\rest_error( 400, $form->get_errors() );
		}

		// Delete booking.
		if ( ! $booking->trash() ) {
			return hp\rest_error( 400 );
		}

		// Send email.
		$user = $booking->get_user();

		( new Emails\Booking_Decline(
			[
				'recipient' => $user->get_email(),

				'tokens'    => [
					'user'           => $user,
					'listing'        => $listing,
					'booking'        => $booking,
					'vendor'         => $listing->get_vendor(),
					'user_name'      => $user->get_display_name(),
					'listing_title'  => $listing->get_title(),
					'booking_dates'  => $booking->display_dates(),
					'booking_url'    => hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ),
					'decline_reason' => $form->get_value( 'reason' ),
				],
			]
		) )->send();

		return hp\rest_response( 204 );
	}

	/**
	 * Cancels booking.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function cancel_booking( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( $request->get_param( 'booking_id' ) );

		if ( ! $booking || ! in_array( $booking->get_status(), [ 'draft', 'pending', 'publish' ], true ) || $booking->get_end_time() < time() ) {
			return hp\rest_error( 404 );
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $listing ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( ! current_user_can( 'delete_others_posts' ) && ! in_array( get_current_user_id(), [ $booking->get_user__id(), $listing->get_user__id() ], true ) ) {
			return hp\rest_error( 403 );
		}

		// Update booking.
		if ( get_current_user_id() === $booking->get_user__id() ) {
			$booking->set_canceled( true )->save_canceled();
		}

		// Delete booking.
		if ( ! $booking->trash() ) {
			return hp\rest_error( 400 );
		}

		return hp\rest_response( 204 );
	}

	/**
	 * Gets listing slots.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function get_listing_slots( $request ) {

		// Check permissions.
		if ( ! get_option( 'hp_booking_enable_time' ) ) {
			return;
		}

		// Get date time.
		$date_time = strtotime( (string) $request->get_param( 'parent_value' ) );

		if ( ! $date_time ) {
			return hp\rest_response( 200, [ [ 'error' => esc_html__( 'The date is not selected.', 'hivepress-bookings' ) ] ] );
		}

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( $request->get_param( 'listing_id' ) );

		if ( ! $listing || ! hivepress()->booking->is_booking_enabled( $listing ) || ! hivepress()->booking->is_time_enabled( $listing ) ) {
			return hp\rest_error( 404 );
		}

		if ( is_null( $listing->get_booking_min_time() ) || is_null( $listing->get_booking_max_time() ) || ! $listing->get_booking_slot_duration() ) {
			return hp\rest_response( 200, [ [ 'error' => esc_html__( 'Time slots are not set up.', 'hivepress-bookings' ) ] ] );
		}

		// Get time format.
		$time_format = get_option( 'time_format' );

		// Get time range.
		$current_time = hivepress()->booking->get_shifted_time( $listing, time() );
		$start_time   = $listing->get_booking_min_time();
		$max_time     = $listing->get_booking_max_time() ? $listing->get_booking_max_time() : DAY_IN_SECONDS;

		if ( $start_time >= $max_time ) {
			$max_time += DAY_IN_SECONDS;
		}

		// Get booked times.
		$booked_times = array_map(
			function( $booking ) use ( $date_time ) {
				return [
					$booking->get_start_time() - $date_time,
					$booking->get_end_time() - $date_time,
					$booking->get_status(),
					$booking->get_quantity(),
				];
			},
			Models\Booking::query()->filter(
				[
					'listing__in'    => hivepress()->booking->get_listing_ids( $listing ),
					'status__in'     => hivepress()->booking->get_blocked_statuses(),
					'start_time__lt' => $date_time + DAY_IN_SECONDS,
					'end_time__gte'  => $date_time,
				]
			)->get()
			->serialize()
		);

		// Get results.
		$results = [];

		$is_multiple = get_option( 'hp_booking_enable_capacity' ) && $listing->get_booking_max_quantity();

		while ( $start_time < $max_time ) {

			// Get end time.
			$end_time = $start_time + $listing->get_booking_slot_duration() * 60;

			if ( $end_time > $max_time ) {
				break;
			}

			$end_time += $listing->get_booking_slot_interval() * 60;

			// Get booking count.
			$booking_count = 0;

			foreach ( $booked_times as $booked_time ) {
				if ( ( $start_time >= $booked_time[0] && $start_time < $booked_time[1] ) || ( $end_time > $booked_time[0] && $end_time <= $booked_time[1] ) || ( $start_time < $booked_time[0] && $end_time > $booked_time[1] ) ) {
					if ( 'private' === $booked_time[2] ) {
						$booking_count = 1000000;

						break;
					} elseif ( $is_multiple ) {
						$booking_count += $booked_time[3];
					} else {
						++$booking_count;
					}

					if ( ! $is_multiple ) {
						break;
					}
				}
			}

			// Check availability.
			$is_booked = (bool) $booking_count;

			if ( $is_multiple ) {
				$is_booked = $booking_count >= $listing->get_booking_max_quantity();
			}

			if ( ! $is_booked ) {

				// Get slot time.
				$slot_time = $date_time + $start_time;

				if ( $slot_time > $current_time ) {

					// Add result.
					$results[] = [
						'id'   => $start_time,
						'text' => date_i18n( $time_format, $slot_time ) . ' - ' . date_i18n( $time_format, $slot_time + $listing->get_booking_slot_duration() * 60 ),
					];
				}
			}

			$start_time = $end_time;
		}

		return hp\rest_response( 200, $results );
	}

	/**
	 * Blocks dates.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function block_dates( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get time range.
		$start_time = strtotime( $request->get_param( 'start_date' ) );
		$end_time   = strtotime( $request->get_param( 'end_date' ) );

		if ( ! $start_time || ! $end_time || $start_time > $end_time || $start_time < strtotime( 'today' ) || $end_time > strtotime( 'today' ) + YEAR_IN_SECONDS * 2 ) {
			return hp\rest_error( 400 );
		}

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( $request->get_param( 'listing_id' ) );

		if ( ! $listing ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_others_posts' ) && ( get_current_user_id() !== $listing->get_user__id() || ! in_array( $listing->get_status(), [ 'draft', 'publish' ], true ) || ! hivepress()->booking->is_booking_enabled( $listing ) ) ) {
			return hp\rest_error( 403 );
		}

		// Get bookings.
		$bookings = hivepress()->booking->get_overlapping_query( $start_time, $end_time, $listing )->get();

		// Check bookings.
		foreach ( $bookings as $booking ) {
			if ( $booking->get_status() !== 'private' ) {
				return hp\rest_error( 400 );
			}
		}

		// Update bookings.
		$booking_id = $bookings->get_first_id();

		foreach ( $bookings as $booking ) {

			// Adjust dates.
			if ( $booking->get_start_time() < $start_time ) {
				$start_time = $booking->get_start_time();
			}

			if ( $booking->get_end_time() > $end_time ) {
				$end_time = $booking->get_end_time();
			}

			// Delete booking.
			if ( $booking->get_id() !== $booking_id ) {
				$booking->delete();
			}
		}

		// Add booking.
		$booking = $bookings->get_first();

		if ( ! $booking ) {
			$booking = new Models\Booking();
		}

		$booking->fill(
			[
				'start_time' => $start_time,
				'end_time'   => $end_time,
				'status'     => 'private',
				'user'       => $listing->get_user__id(),
				'listing'    => $listing->get_id(),
			]
		);

		if ( ! $booking->save( [ 'start_time', 'end_time', 'status', 'user', 'listing' ] ) ) {
			return hp\rest_error( 400, $booking->_get_errors() );
		}

		return hp\rest_response(
			200,
			[
				'id' => $listing->get_id(),
			]
		);
	}

	/**
	 * Unblocks dates.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function unblock_dates( $request ) {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Get time range.
		$start_time = strtotime( $request->get_param( 'start_date' ) );
		$end_time   = strtotime( $request->get_param( 'end_date' ) );

		if ( ! $start_time || ! $end_time || $start_time > $end_time || $start_time < strtotime( 'today' ) || $end_time > strtotime( 'today' ) + YEAR_IN_SECONDS * 2 ) {
			return hp\rest_error( 400 );
		}

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( $request->get_param( 'listing_id' ) );

		if ( ! $listing ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_others_posts' ) && ( get_current_user_id() !== $listing->get_user__id() || ! in_array( $listing->get_status(), [ 'draft', 'publish' ], true ) || ! hivepress()->booking->is_booking_enabled( $listing ) ) ) {
			return hp\rest_error( 403 );
		}

		// Get bookings.
		$bookings = hivepress()->booking->get_overlapping_query( $start_time, $end_time, $listing )->get();

		// Check bookings.
		foreach ( $bookings as $booking ) {
			if ( $booking->get_status() !== 'private' ) {
				return hp\rest_error( 400 );
			}
		}

		// Update bookings.
		foreach ( $bookings as $booking ) {
			if ( $booking->get_start_time() >= $start_time && $booking->get_end_time() <= $end_time ) {

				// Delete booking.
				$booking->delete();
			} else {

				// Split booking.
				if ( $booking->get_start_time() < $start_time ) {
					if ( $booking->get_end_time() > $end_time ) {

						// Add booking.
						$new_booking = ( new Models\Booking() )->fill(
							[
								'start_time' => $end_time,
								'end_time'   => $booking->get_end_time(),
								'status'     => 'private',
								'user'       => $listing->get_user__id(),
								'listing'    => $listing->get_id(),
							]
						);

						if ( ! $new_booking->save( [ 'start_time', 'end_time', 'status', 'user', 'listing' ] ) ) {
							return hp\rest_error( 400, $new_booking->_get_errors() );
						}
					}

					$booking->set_end_time( $start_time );
				} elseif ( $booking->get_end_time() > $end_time ) {
					$booking->set_start_time( $end_time );
				}

				if ( ! $booking->save( [ 'start_time', 'end_time' ] ) ) {
					return hp\rest_error( 400, $booking->_get_errors() );
				}
			}
		}

		return hp\rest_response(
			200,
			[
				'id' => $listing->get_id(),
			]
		);
	}

	/**
	 * Ranges dates.
	 *
	 * @param WP_REST_Request $request API request.
	 * @return WP_Rest_Response
	 */
	public function range_dates( $request ) {

		// Check permissions.
		if ( ! get_option( 'hp_booking_enable_price' ) ) {
			return hp\rest_error( 403 );
		}

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hp\rest_error( 401 );
		}

		// Validate form.
		$form = ( new Forms\Listing_Range_Dates() )->set_values( $request->get_params() );

		if ( ! $form->validate() ) {
			return hp\rest_error( 400, $form->get_errors() );
		}

		// Get time range.
		$start_time = strtotime( $form->get_value( 'start_date' ) );
		$end_time   = strtotime( $form->get_value( 'end_date' ) );

		if ( ! $start_time || ! $end_time || $start_time > $end_time || $start_time < strtotime( 'today' ) || $end_time > strtotime( 'today' ) + YEAR_IN_SECONDS * 2 ) {
			return hp\rest_error( 400 );
		}

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( $request->get_param( 'listing_id' ) );

		if ( ! $listing ) {
			return hp\rest_error( 404 );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_others_posts' ) && ( get_current_user_id() !== $listing->get_user__id() || ! in_array( $listing->get_status(), [ 'draft', 'publish' ], true ) || ! hivepress()->booking->is_booking_enabled( $listing ) ) ) {
			return hp\rest_error( 403 );
		}

		// Get ranges.
		$ranges = hivepress()->booking->get_overlapping_query( $start_time, $end_time, $listing, true )->get();

		// Update ranges.
		foreach ( $ranges as $range ) {
			if ( $range->get_start_time() >= $start_time && $range->get_end_time() <= $end_time ) {

				// Delete range.
				$range->delete();
			} else {

				// Split range.
				if ( $range->get_start_time() < $start_time ) {
					if ( $range->get_end_time() > $end_time ) {

						// Add range.
						$new_range = ( new Models\Booking_Range() )->fill(
							[
								'start_time' => $end_time,
								'end_time'   => $range->get_end_time(),
								'price'      => $range->get_price(),
								'user'       => $listing->get_user__id(),
								'listing'    => $listing->get_id(),
							]
						);

						if ( ! $new_range->save() ) {
							return hp\rest_error( 400, $new_range->_get_errors() );
						}
					}

					$range->set_end_time( $start_time );
				} elseif ( $range->get_end_time() > $end_time ) {
					$range->set_start_time( $end_time );
				}

				if ( ! $range->save( [ 'start_time', 'end_time' ] ) ) {
					return hp\rest_error( 400, $range->_get_errors() );
				}
			}
		}

		// Add range.
		if ( $form->get_value( 'price' ) !== $listing->get_price() ) {
			$new_range = ( new Models\Booking_Range() )->fill(
				[
					'start_time' => $start_time,
					'end_time'   => $end_time,
					'price'      => $form->get_value( 'price' ),
					'user'       => $listing->get_user__id(),
					'listing'    => $listing->get_id(),
				]
			);

			if ( ! $new_range->save() ) {
				return hp\rest_error( 400, $new_range->_get_errors() );
			}
		}

		return hp\rest_response(
			200,
			[
				'id' => $listing->get_id(),
			]
		);
	}

	/**
	 * Redirects bookings view page.
	 *
	 * @return mixed
	 */
	public function redirect_bookings_view_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Check bookings.
		if ( ! hivepress()->request->get_context( 'booking_count' ) ) {
			return hivepress()->router->get_url( 'user_account_page' );
		}

		return false;
	}

	/**
	 * Renders bookings view page.
	 *
	 * @return string
	 */
	public function render_bookings_view_page() {
		global $wpdb;

		// Get listing IDs.
		$listing_ids = [];

		if ( hivepress()->request->get_context( 'listing_count' ) ) {
			$listing_ids = Models\Listing::query()->filter(
				[
					'status__in' => [ 'draft', 'pending', 'publish' ],
					'user'       => get_current_user_id(),
				]
			)->get_ids();
		}

		if ( ! $listing_ids ) {
			$listing_ids = [ 0 ];
		}

		// Set placeholder.
		$placeholder = implode( ', ', array_fill( 0, count( $listing_ids ), '%d' ) );

		// Get booking IDs.
		$booking_ids = array_column(
			$wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					WHERE post_type = %s AND ( post_author = %d OR post_parent IN ( {$placeholder} ) );",
					array_merge(
						[
							'hp_booking',
							get_current_user_id(),
						],
						$listing_ids
					)
				),
				ARRAY_A
			),
			'ID'
		);

		// Query bookings.
		hivepress()->request->set_context(
			'post_query',
			Models\Booking::query()->filter(
				[
					'id__in'     => $booking_ids,
					'status__in' => [ 'trash', 'draft', 'pending', 'publish' ],
				]
			)->order( [ 'start_time' => 'asc' ] )
			->limit( 10 )
			->paginate( hivepress()->request->get_page_number() )
			->set_args( [ 'hp_sort' => true ] )
			->get_args()
		);

		// Render template.
		return ( new Blocks\Template(
			[
				'template' => 'bookings_view_page',

				'context'  => [
					'bookings' => [],
				],
			]
		) )->render();
	}

	/**
	 * Gets booking view title.
	 *
	 * @return string
	 */
	public function get_booking_view_title() {
		$title = null;

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( hivepress()->request->get_param( 'booking_id' ) );

		// Set title.
		if ( $booking ) {

			/* translators: %s: booking number. */
			$title = sprintf( esc_html__( 'Booking %s', 'hivepress-bookings' ), '#' . $booking->get_id() );
		}

		// Set request context.
		hivepress()->request->set_context( 'booking', $booking );

		return $title;
	}

	/**
	 * Redirects booking view page.
	 *
	 * @return mixed
	 */
	public function redirect_booking_view_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Get booking.
		$booking = hivepress()->request->get_context( 'booking' );

		if ( ! $booking || ! in_array( $booking->get_status(), [ 'trash', 'draft', 'pending', 'publish' ], true ) ) {
			return hivepress()->router->get_url( 'bookings_view_page' );
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $listing ) {
			return hivepress()->router->get_url( 'bookings_view_page' );
		}

		// Set request context.
		hivepress()->request->set_context( 'listing', $listing );

		// Check permissions.
		if ( ! in_array( get_current_user_id(), [ $booking->get_user__id(), $listing->get_user__id() ], true ) ) {
			return hivepress()->router->get_url( 'bookings_view_page' );
		}

		if ( hivepress()->get_version( 'marketplace' ) ) {
			if ( in_array( $booking->get_status(), [ 'pending', 'draft' ] ) ) {

				// Get cached price.
				$price = hivepress()->cache->get_post_cache( $booking->get_id(), 'price', 'models/booking' );

				if ( is_null( $price ) ) {

					// Get quantity.
					$quantity = hivepress()->booking->get_booking_quantity( $booking->get_start_time(), $booking->get_end_time(), $listing );

					// Get meta.
					$meta = hivepress()->booking->get_cart_meta( $booking, $listing );

					if ( hivepress()->marketplace->add_to_cart( $listing, [ '_quantity' => $quantity ], $meta ) ) {

						// Set price.
						$price = wc_price( WC()->cart->get_total( 'edit' ) - WC()->cart->get_total_tax() );

						// @todo move to Marketplace for consistency.
						if ( hivepress()->marketplace->is_recurring( $listing ) ) {
							$price .= ' / ' . $listing->display_subscription_period();
						}

						// Empty cart.
						WC()->cart->empty_cart();

						// Cache price.
						hivepress()->cache->set_post_cache( $booking->get_id(), 'price', 'models/booking', $price );
					}
				}

				// Set request context.
				hivepress()->request->set_context( 'booking_price', $price );
			} else {

				// Get order.
				$order = hp\get_first_array_value(
					wc_get_orders(
						[
							'limit'      => 1,
							'meta_key'   => 'hp_booking',
							'meta_value' => $booking->get_id(),
						]
					)
				);

				if ( $order ) {

					// Set request context.
					hivepress()->request->set_context( 'order', $order );
					hivepress()->request->set_context( 'booking_price', $order->get_formatted_order_total() );
				}
			}

			// Get extras.
			$extras = null;

			if ( get_option( 'hp_listing_allow_price_extras' ) && $booking->get_price_extras() ) {
				$extras = implode( ', ', array_column( $booking->get_price_extras(), 'name' ) );
			}

			if ( get_option( 'hp_listing_allow_price_tiers' ) && $booking->get_price_tiers() ) {
				if ( $extras ) {
					$extras = ' (' . $extras . ')';
				}

				$extras = implode( ', ', array_column( $booking->get_price_tiers(), 'name' ) ) . $extras;
			}

			// Set request context.
			hivepress()->request->set_context( 'booking_extras', $extras );
		}

		return false;
	}

	/**
	 * Renders booking view page.
	 *
	 * @return string
	 */
	public function render_booking_view_page() {

		// Get booking.
		$booking = hivepress()->request->get_context( 'booking' );

		// Get user.
		$user = $booking->get_user();

		return ( new Blocks\Template(
			[
				'template' => 'booking_view_page',

				'context'  => [
					'booking_price'  => hivepress()->request->get_context( 'booking_price' ),
					'booking_extras' => hivepress()->request->get_context( 'booking_extras' ),
					'booking'        => $booking,
					'listing'        => hivepress()->request->get_context( 'listing' ),
					'order'          => hivepress()->request->get_context( 'order' ),
					'user'           => $user,
				],
			]
		) )->render();
	}

	/**
	 * Redirects booking make page.
	 *
	 * @return mixed
	 */
	public function redirect_booking_make_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Get booking.
		$booking = Models\Booking::query()->filter(
			[
				'status'  => 'auto-draft',
				'drafted' => true,
				'user'    => get_current_user_id(),
			]
		)->get_first();

		if ( ! $booking ) {

			// Add booking.
			$booking = ( new Models\Booking() )->fill(
				[
					'status'  => 'auto-draft',
					'drafted' => true,
					'user'    => get_current_user_id(),
				]
			);

			if ( ! $booking->save( [ 'status', 'drafted', 'user' ] ) ) {
				wp_die( esc_html__( 'The booking can\'t be made', 'hivepress-bookings' ) );
			}
		}

		if ( hivepress()->router->get_current_route_name() === 'booking_make_page' ) {
			$fields = [ 'start_time', 'end_time', 'listing' ];

			// Get listing.
			$listing = Models\Listing::query()->get_by_id( absint( hp\get_array_value( $_GET, 'listing' ) ) );

			if ( ! $listing || $listing->get_status() !== 'publish' || ! hivepress()->booking->is_booking_enabled( $listing ) ) {
				wp_die( hivepress()->translator->get_string( 'no_listings_found' ) );
			}

			$booking->set_listing( $listing->get_id() );

			// Get time range.
			list($start_time, $end_time) = hivepress()->booking->get_booking_times( $_GET, $listing );

			$current_time = hivepress()->booking->get_shifted_time( $listing, time() );

			if ( ! hivepress()->booking->is_time_enabled( $listing ) ) {
				$current_time = strtotime( date( 'Y-m-d', $current_time ) );
			}

			// Set time range.
			if ( $start_time && $end_time && $end_time >= $start_time && $start_time >= $current_time ) {
				$booking->fill(
					[
						'start_time' => $start_time,
						'end_time'   => $end_time,
					]
				);
			}

			// Set quantity.
			if ( get_option( 'hp_booking_enable_quantity' ) ) {
				$quantity = absint( hp\get_array_value( $_GET, '_quantity', 1 ) );

				if ( $quantity < 1 ) {
					$quantity = 1;
				}

				$booking->set_quantity( $quantity );

				$fields[] = 'quantity';
			}

			// Set tiers.
			if ( get_option( 'hp_listing_allow_price_tiers' ) && $listing->get_price_tiers() ) {
				$tier = hp\get_array_value( $listing->get_price_tiers(), absint( hp\get_array_value( $_GET, '_tier' ) ) );

				if ( $tier ) {
					$booking->set_price_tiers( [ $tier ] );

					$fields[] = 'price_tiers';
				}
			}

			// Set extras.
			if ( get_option( 'hp_listing_allow_price_extras' ) && $listing->get_price_extras() ) {
				$extra_ids = hp\get_array_value( $_GET, '_extras', [] );

				foreach ( $listing->get_price_extras() as $index => $item ) {
					if ( hp\get_array_value( $item, 'required' ) ) {
						$extra_ids[] = $index;
					}
				}

				if ( $extra_ids ) {
					$extras = array_intersect_key( $listing->get_price_extras(), array_flip( array_map( 'absint', (array) $extra_ids ) ) );

					if ( $extras ) {
						$booking->set_price_extras( $extras );

						$fields[] = 'price_extras';
					}
				}
			}

			// Update booking.
			if ( ! $booking->save( $fields ) ) {
				wp_die( esc_html__( 'The booking can\'t be made', 'hivepress-bookings' ) );
			}
		}

		// Get listing.
		if ( ! isset( $listing ) ) {
			$listing = $booking->get_listing();
		}

		if ( ! $listing || $listing->get_status() !== 'publish' ) {
			wp_die( hivepress()->translator->get_string( 'no_listings_found' ) );
		}

		// Set request context.
		hivepress()->request->set_context( 'booking', $booking );
		hivepress()->request->set_context( 'listing', $listing );

		return true;
	}

	/**
	 * Redirects booking make details page.
	 *
	 * @return mixed
	 */
	public function redirect_booking_make_details_page() {

		// Get booking.
		$booking = hivepress()->request->get_context( 'booking' );

		// Check booking.
		if ( $booking->get_title() && $booking->validate() ) {
			return true;
		}

		return false;
	}

	/**
	 * Renders booking make details page.
	 *
	 * @return string
	 */
	public function render_booking_make_details_page() {
		return ( new Blocks\Template(
			[
				'template' => 'booking_make_details_page',

				'context'  => [
					'booking' => hivepress()->request->get_context( 'booking' ),
					'listing' => hivepress()->request->get_context( 'listing' ),
				],
			]
		) )->render();
	}

	/**
	 * Gets booking make complete title.
	 *
	 * @return string
	 */
	public function get_booking_make_complete_title() {
		$title = null;

		// Get listing.
		$listing = hivepress()->request->get_context( 'listing' );

		// Set title.
		if ( $listing->is_booking_moderated() ) {
			$title = esc_html__( 'Booking Requested', 'hivepress-bookings' );
		} else {
			$title = esc_html__( 'Booking Confirmed', 'hivepress-bookings' );
		}

		return $title;
	}

	/**
	 * Redirects booking make complete page.
	 *
	 * @return mixed
	 */
	public function redirect_booking_make_complete_page() {

		// Get booking.
		$booking = hivepress()->request->get_context( 'booking' );

		// Get listing.
		$listing = hivepress()->request->get_context( 'listing' );

		// Get status.
		$status = 'publish';

		if ( $listing->is_booking_moderated() ) {
			$status = 'pending';
		} elseif ( hivepress()->get_version( 'marketplace' ) && ! is_null( $listing->get_price() ) ) {
			$status = 'draft';
		}

		// Update booking.
		$booking->fill(
			[
				'status'  => $status,
				'drafted' => null,
			]
		)->save( [ 'status', 'drafted' ] );

		// Redirect to checkout.
		if ( 'draft' === $status ) {
			return hivepress()->router->get_url( 'booking_pay_page', [ 'booking_id' => $booking->get_id() ] );
		}

		return false;
	}

	/**
	 * Renders booking make complete page.
	 *
	 * @return string
	 */
	public function render_booking_make_complete_page() {
		return ( new Blocks\Template(
			[
				'template' => 'booking_make_complete_page',

				'context'  => [
					'booking' => hivepress()->request->get_context( 'booking' ),
					'listing' => hivepress()->request->get_context( 'listing' ),
				],
			]
		) )->render();
	}

	/**
	 * Redirects booking pay page.
	 *
	 * @return mixed
	 */
	public function redirect_booking_pay_page() {

		// Check Marketplace status.
		if ( ! hivepress()->get_version( 'marketplace' ) ) {
			wp_die( esc_html__( 'The payment can\'t be made.', 'hivepress-bookings' ) );
		}

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( hivepress()->request->get_param( 'booking_id' ) );

		if ( ! $booking || $booking->get_status() !== 'draft' || ! $booking->get_listing__id() ) {
			wp_die( esc_html__( 'No bookings found.', 'hivepress-bookings' ) );
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $listing || $listing->get_status() !== 'publish' || ! hivepress()->booking->is_booking_enabled( $listing ) ) {
			wp_die( hivepress()->translator->get_string( 'no_listings_found' ) );
		}

		// Get quantity.
		$quantity = hivepress()->booking->get_booking_quantity( $booking->get_start_time(), $booking->get_end_time(), $listing );

		// Get meta.
		$meta = hivepress()->booking->get_cart_meta( $booking, $listing );

		// Add to cart.
		if ( ! hivepress()->marketplace->add_to_cart( $listing, [ '_quantity' => $quantity ], $meta ) ) {
			wp_die( esc_html__( 'The payment can\'t be made.', 'hivepress-bookings' ) );
		}

		return wc_get_page_permalink( 'checkout' );
	}

	/**
	 * Gets booking pay complete title.
	 *
	 * @return string
	 */
	public function get_booking_pay_complete_title() {
		$title = null;

		// Get booking.
		$booking = Models\Booking::query()->get_by_id( hivepress()->request->get_param( 'booking_id' ) );

		// Set title.
		if ( $booking ) {
			if ( $booking->get_status() === 'publish' ) {
				$title = esc_html__( 'Booking Confirmed', 'hivepress-bookings' );
			} else {
				$title = esc_html__( 'Booking Received', 'hivepress-bookings' );
			}
		}

		// Set request context.
		hivepress()->request->set_context( 'booking', $booking );

		return $title;
	}

	/**
	 * Redirects booking pay complete page.
	 *
	 * @return mixed
	 */
	public function redirect_booking_pay_complete_page() {

		// Check Marketplace status.
		if ( ! hivepress()->get_version( 'marketplace' ) ) {
			wp_die( esc_html__( 'The payment can\'t be made.', 'hivepress-bookings' ) );
		}

		// Get booking.
		$booking = hivepress()->request->get_context( 'booking' );

		if ( ! $booking || ! in_array( $booking->get_status(), [ 'draft', 'publish' ], true ) ) {
			wp_die( esc_html__( 'No bookings found.', 'hivepress-bookings' ) );
		}

		// Get listing.
		$listing = $booking->get_listing();

		if ( ! $listing || $listing->get_status() !== 'publish' ) {
			wp_die( hivepress()->translator->get_string( 'no_listings_found' ) );
		}

		// Set request context.
		hivepress()->request->set_context( 'listing', $listing );

		return false;
	}

	/**
	 * Renders booking pay complete page.
	 *
	 * @return string
	 */
	public function render_booking_pay_complete_page() {
		return ( new Blocks\Template(
			[
				'template' => 'booking_pay_complete_page',

				'context'  => [
					'booking' => hivepress()->request->get_context( 'booking' ),
					'listing' => hivepress()->request->get_context( 'listing' ),
				],
			]
		) )->render();
	}

	/**
	 * Gets listing calendar title.
	 *
	 * @return string
	 */
	public function get_listing_calendar_title() {
		$title = null;

		// Get listing.
		$listing = Models\Listing::query()->get_by_id( hivepress()->request->get_param( 'listing_id' ) );

		// Set title.
		if ( $listing ) {
			$title = $listing->get_title();
		}

		// Set request context.
		hivepress()->request->set_context( 'listing', $listing );

		return $title;
	}

	/**
	 * Redirects vendor calendar page.
	 *
	 * @return mixed
	 */
	public function redirect_vendor_calendar_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Get vendor.
		$vendor = Models\Vendor::query()->filter(
			[
				'status__in' => [ 'draft', 'pending', 'publish' ],
				'user'       => get_current_user_id(),
			]
		)->get_first();

		if ( ! $vendor ) {
			wp_die( hivepress()->translator->get_string( 'no_vendors_found' ) );
		}

		// Set request context.
		hivepress()->request->set_context( 'vendor', $vendor );

		return false;
	}

	/**
	 * Renders vendor calendar page.
	 *
	 * @return string
	 */
	public function render_vendor_calendar_page() {
		return ( new Blocks\Template(
			[
				'template' => 'vendor_calendar_page',

				'context'  => [
					'vendor' => hivepress()->request->get_context( 'vendor' ),
				],
			]
		) )->render();
	}

	/**
	 * Redirects listing calendar page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_calendar_page() {

		// Check permissions.
		if ( get_option( 'hp_booking_per_vendor' ) ) {
			return hivepress()->router->get_url( 'vendor_calendar_page' );
		}

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Get listing.
		$listing = hivepress()->request->get_context( 'listing' );

		if ( ! $listing || get_current_user_id() !== $listing->get_user__id() || ! in_array( $listing->get_status(), [ 'draft', 'publish' ], true ) || ! hivepress()->booking->is_booking_enabled( $listing ) ) {
			return hivepress()->router->get_url( 'listings_edit_page' );
		}

		return false;
	}

	/**
	 * Renders listing calendar page.
	 *
	 * @return string
	 */
	public function render_listing_calendar_page() {
		return ( new Blocks\Template(
			[
				'template' => 'listing_calendar_page',

				'context'  => [
					'listing' => hivepress()->request->get_context( 'listing' ),
				],
			]
		) )->render();
	}

	/**
	 * Redirects listing calendar file.
	 *
	 * @return mixed
	 */
	public function redirect_listing_calendar_file() {

		// Check settings.
		if ( ! get_option( 'hp_booking_allow_sync' ) ) {
			wp_die( esc_html__( 'The calendar export is not allowed.', 'hivepress-bookings' ) );
		}

		// Get listing.
		$listing = null;

		if ( get_option( 'hp_booking_per_vendor' ) ) {
			$vendor_id = hivepress()->request->get_param( 'vendor_id' );

			if ( $vendor_id ) {
				$listing = Models\Listing::query()->filter(
					[
						'status__in' => [ 'draft', 'pending', 'publish' ],
						'vendor'     => $vendor_id,
					]
				)->get_first();
			}
		} else {
			$listing = Models\Listing::query()->get_by_id( hivepress()->request->get_param( 'listing_id' ) );
		}

		// Check listing.
		if ( ! $listing || ! in_array( $listing->get_status(), [ 'draft', 'pending', 'publish' ] ) || ! hivepress()->booking->is_booking_enabled( $listing ) ) {
			wp_die( hivepress()->translator->get_string( 'no_listings_found' ) );
		}

		// Check access key.
		$access_key = sanitize_text_field( hp\get_array_value( $_GET, 'access_key' ) );

		if ( ! $access_key || hivepress()->booking->get_calendar_key( $listing->get_vendor__id() ) !== $access_key ) {
			wp_die( esc_html__( 'The access key is missing or incorrect.', 'hivepress-bookings' ) );
		}

		// Set request context.
		hivepress()->request->set_context( 'listing', $listing );

		return false;
	}

	/**
	 * Renders listing calendar file.
	 */
	public function render_listing_calendar_file() {

		// Get listing.
		$listing = hivepress()->request->get_context( 'listing' );

		// Get settings.
		$is_time     = hivepress()->booking->is_time_enabled( $listing );
		$is_daily    = get_option( 'hp_booking_enable_daily' );
		$is_multiple = get_option( 'hp_booking_enable_quantity' );

		// Get date format.
		$format = 'Y-m-d';

		if ( $is_time ) {
			$format .= ' H:i:s';
		}

		// Get time zone.
		$timezone = null;

		if ( $listing->get_booking_timezone() ) {
			$timezone = new \DateTimeZone( $listing->get_booking_timezone() );
		}

		// Get bookings.
		$bookings = Models\Booking::query()->filter(
			[
				'listing__in'   => hivepress()->booking->get_listing_ids( $listing ),
				'status__in'    => hivepress()->booking->get_blocked_statuses(),
				'end_time__gte' => strtotime( 'today' ),
			]
		)->get();

		// Create calendar.
		$calendar = IcalendarGenerator\Components\Calendar::create()->refreshInterval( 5 );

		if ( $timezone ) {
			$calendar->withoutAutoTimezoneComponents();
		} else {
			$calendar->withoutTimezone();
		}

		foreach ( $bookings as $booking ) {

			// Get time range.
			$start_time = $booking->get_start_time();
			$end_time   = $booking->get_end_time();

			if ( $is_daily && $booking->get_status() !== 'private' ) {
				++$end_time;
			}

			// Create event.
			$event = IcalendarGenerator\Components\Event::create()
				->uniqueIdentifier( md5( $booking->get_id() . wp_salt() ) )
				->createdAt( new \DateTime( $booking->get_created_date_gmt() ) )
				->startsAt( new \DateTime( date( $format, $start_time ), $timezone ) )
				->endsAt( new \DateTime( date( $format, $end_time ), $timezone ) );

			if ( ! $is_time ) {
				$event->fullDay();
			}

			if ( $booking->get_status() === 'private' ) {
				$event->name( esc_html_x( 'Blocked', 'dates', 'hivepress-bookings' ) );
			} else {

				// Get user.
				$user = $booking->get_user();

				// Set details.
				$event->name( $user->get_display_name() . ( $is_multiple ? ' (' . $booking->get_quantity() . ')' : null ) )
					->description( hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ) )
					->attendee( $user->get_email(), $user->get_display_name() );
			}

			// Add event.
			$calendar->event( $event );
		}

		// Output file.
		header( 'Content-type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename=calendar.ics' );

		echo $calendar->get();

		exit;
	}

	/**
	 * Redirects listing bookings page.
	 *
	 * @return mixed
	 */
	public function redirect_listing_bookings_page() {

		// Check authentication.
		if ( ! is_user_logged_in() ) {
			return hivepress()->router->get_return_url( 'user_login_page' );
		}

		// Get listing.
		$listing = hivepress()->request->get_context( 'listing' );

		if ( ! $listing || get_current_user_id() !== $listing->get_user__id() || ! in_array( $listing->get_status(), [ 'draft', 'publish' ], true ) ) {
			return hivepress()->router->get_url( 'listings_edit_page' );
		}

		// Check bookings.
		if ( ! hivepress()->cache->get_post_cache( $listing->get_id(), 'booking_count', 'models/booking' ) ) {
			return hivepress()->router->get_url( 'listing_edit_page', [ 'listing_id' => $listing->get_id() ] );
		}

		return false;
	}

	/**
	 * Renders listing bookings page.
	 *
	 * @return string
	 */
	public function render_listing_bookings_page() {

		// Get listing.
		$listing = hivepress()->request->get_context( 'listing' );

		// Query bookings.
		hivepress()->request->set_context(
			'post_query',
			Models\Booking::query()->filter(
				[
					'status__in' => [ 'trash', 'draft', 'pending', 'publish' ],
					'listing'    => $listing->get_id(),
				]
			)->order( [ 'start_time' => 'asc' ] )
			->limit( 10 )
			->paginate( hivepress()->request->get_page_number() )
			->set_args( [ 'hp_sort' => true ] )
			->get_args()
		);

		// Render template.
		return ( new Blocks\Template(
			[
				'template' => 'listing_bookings_page',

				'context'  => [
					'listing'  => $listing,
					'bookings' => [],
				],
			]
		) )->render();
	}
}
