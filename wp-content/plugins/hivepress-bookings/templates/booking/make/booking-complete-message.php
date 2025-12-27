<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<p>
	<?php
	if ( $booking->get_status() === 'pending' ) :
		/* translators: %s: listing title. */
		printf( esc_html__( 'Thank you! Your booking request for "%s" has been sent and will be processed as soon as possible.', 'hivepress-bookings' ), $listing->get_title() );
	else :
		/* translators: %1$s: listing title, %2$s: booking number. */
		printf( esc_html__( 'Thank you! Your booking of "%1$s" has been confirmed, the booking reference number is %2$s.', 'hivepress-bookings' ), $listing->get_title(), '#' . $booking->get_id() );
	endif;
	?>
</p>
<button type="button" class="button button--primary alt" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ) ); ?>"><?php esc_html_e( 'View Booking', 'hivepress-bookings' ); ?></button>
