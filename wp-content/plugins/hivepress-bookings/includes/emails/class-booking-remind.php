<?php
/**
 * Booking remind email.
 *
 * @package HivePress\Emails
 */

namespace HivePress\Emails;

use HivePress\Helpers as hp;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Booking remind email class.
 *
 * @class Booking_Remind
 */
class Booking_Remind extends Email {

	/**
	 * Class initializer.
	 *
	 * @param array $meta Form meta.
	 */
	public static function init( $meta = [] ) {
		$meta = hp\merge_arrays(
			[
				'label'     => esc_html__( 'Booking Reminder', 'hivepress-bookings' ),
				'recipient' => hivepress()->translator->get_string( 'user' ),
				'tokens'    => [ 'user_name', 'booking_number', 'booking_dates', 'booking_url', 'user', 'vendor', 'listing', 'booking' ],
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
				'subject' => esc_html__( 'Booking Reminder', 'hivepress-bookings' ),
			],
			$args
		);

		parent::__construct( $args );
	}
}
