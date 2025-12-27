<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ( in_array( $booking->get_status(), [ 'draft', 'publish' ], true ) || ( $booking->get_status() === 'pending' && get_current_user_id() === $booking->get_user__id() ) ) && $booking->get_end_time() >= time() ) :
	?>
	<a href="#booking_cancel_modal" class="hp-listing__action hp-listing__action--cancel hp-link">
		<i class="hp-icon fas fa-times"></i>
		<span><?php esc_html_e( 'Cancel Booking', 'hivepress-bookings' ); ?></span>
	</a>
	<?php
endif;
