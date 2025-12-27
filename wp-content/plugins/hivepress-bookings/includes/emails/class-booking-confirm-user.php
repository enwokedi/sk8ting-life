<?php
/**
 * Booking confirm user email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking confirm user email class.
 *
 * @class Booking_Confirm_User
 */
class Booking_Confirm_User extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				/* translators: %s: recipient. */
				'label'     => sprintf( esc_html__( 'Booking Confirmed (%s)', 'hivepress-bookings' ), hivepress()->translator->get_string( 'user' ) ),
				'recipient' => hivepress()->translator->get_string( 'user' ),
				'tokens'    => [ 'user_name', 'listing_title', 'booking_dates', 'booking_url', 'user', 'vendor', 'listing', 'booking' ],
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
				'body'    => esc_html__( 'Hi, %user_name%! Your booking of "%listing_title%" has been confirmed, click on the following link to view it: %booking_url%', 'hivepress-bookings' ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
