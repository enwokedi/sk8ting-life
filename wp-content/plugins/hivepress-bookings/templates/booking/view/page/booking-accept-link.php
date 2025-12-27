<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $booking->get_status() === 'pending' && get_current_user_id() === $listing->get_user__id() ) :
	?>
	<button type="button" class="hp-listing__action hp-listing__action--accept button button--large button--primary alt" data-component="link" data-url="#booking_accept_modal"><?php esc_html_e( 'Accept Booking', 'hivepress-bookings' ); ?></button>
	<?php
endif;
