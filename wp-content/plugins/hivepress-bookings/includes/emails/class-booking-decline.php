<?php
/**
 * Booking decline email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking decline email class.
 *
 * @class Booking_Decline
 */
class Booking_Decline extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'     => esc_html__( 'Booking Declined', 'hivepress-bookings' ),
				'recipient' => hivepress()->translator->get_string( 'user' ),
				'tokens'    => [ 'user_name', 'listing_title', 'booking_dates', 'booking_url', 'decline_reason', 'user', 'vendor', 'listing', 'booking' ],
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
				'subject' => esc_html__( 'Booking Declined', 'hivepress-bookings' ),
				'body'    => esc_html__( 'Hi, %user_name%! Your booking request for "%listing_title%" %booking_url% has been declined for the following reason: %decline_reason%', 'hivepress-bookings' ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
