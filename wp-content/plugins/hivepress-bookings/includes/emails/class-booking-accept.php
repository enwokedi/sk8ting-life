<?php
/**
 * Booking accept email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking accept email class.
 *
 * @class Booking_Accept
 */
class Booking_Accept extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'     => esc_html__( 'Booking Accepted', 'hivepress-bookings' ),
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
				'subject' => esc_html__( 'Booking Accepted', 'hivepress-bookings' ),
				'body'    => esc_html__( 'Hi, %user_name%! Your booking request for "%listing_title%" has been accepted, click on the following link to view it: %booking_url%', 'hivepress-bookings' ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
