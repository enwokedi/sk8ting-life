<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $booking->get_status() === 'pending' && get_current_user_id() === $listing->get_user__id() ) :
	?>
	<a href="#booking_decline_modal" class="hp-listing__action hp-listing__action--decline hp-link">
		<i class="hp-icon fas fa-times"></i>
		<span><?php esc_html_e( 'Decline Booking', 'hivepress-bookings' ); ?></span>
	</a>
	<?php
endif;
