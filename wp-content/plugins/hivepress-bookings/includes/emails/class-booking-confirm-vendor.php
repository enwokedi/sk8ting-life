<?php
/**
 * Booking confirm vendor email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking confirm vendor email class.
 *
 * @class Booking_Confirm_Vendor
 */
class Booking_Confirm_Vendor extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				/* translators: %s: recipient. */
				'label'     => sprintf( esc_html__( 'Booking Confirmed (%s)', 'hivepress-bookings' ), hivepress()->translator->get_string( 'vendor' ) ),
				'recipient' => hivepress()->translator->get_string( 'vendor' ),
				'tokens'    => [ 'user_name', 'listing_title', 'booking_dates', 'booking_url', 'user', 'listing', 'booking' ],
			],
			$meta
		);

		parent::init( $meta );
	}

	/**
	 * Class constructor.
	 *
	 * @param array $args Email arguments.
	 */
	public function __construct( $args = [] ) {
		$args = hp\merge_arrays(
			[
				'subject' => esc_html__( 'Booking Confirmed', 'hivepress-bookings' ),
				'body'    => esc_html__( 'Hi, %user_name%! A booking of "%listing_title%" has been confirmed, click on the following link to view it: %booking_url%', 'hivepress-bookings' ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
