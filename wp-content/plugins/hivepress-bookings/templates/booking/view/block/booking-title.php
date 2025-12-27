<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<h3 class="hp-listing__title">
	<a href="<?php echo esc_url( hivepress()->router->get_url( 'booking_view_page', [ 'booking_id' => $booking->get_id() ] ) ); ?>">
		<?php
		/* translators: %s: booking number. */
		printf( esc_html__( 'Booking %s', 'hivepress-bookings' ), '#' . $booking->get_id() );
		?>
	</a>
</h3>
