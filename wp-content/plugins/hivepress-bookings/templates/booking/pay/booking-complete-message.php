<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<p>
	<?php
	if ( $booking->get_status() === 'publish' ) :
		/* translators: %1$s: listing title, %2$s: booking number. */
		printf( esc_html__( 'Thank you for your payment! Your booking of "%1$s" has been confirmed, the booking reference number is %2$s.', 'hivepress-bookings' ), $listing->get_title(), '#' . $booking->get_id() );
	else :
		esc_html_e( 'Thank you for your payment! Once the payment is processed, you will receive a booking confirmation via email.', 'hivepress-bookings' );
	endif;
	?>
</p>
<button type="button" class="button button--primary alt" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ) ); ?>"><?php esc_html_e( 'View Booking', 'hivepress-bookings' ); ?></button>
