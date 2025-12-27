<?php
/**
 * Booking request email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking request email class.
 *
 * @class Booking_Request
 */
class Booking_Request extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'     => esc_html__( 'Booking Requested', 'hivepress-bookings' ),
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
				'subject' => esc_html__( 'Booking Requested', 'hivepress-bookings' ),
				'body'    => esc_html__( 'Hi, %user_name%! You\'ve received a new booking request for "%listing_title%", click on the following link to view it: %booking_url%', 'hivepress-bookings' ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
