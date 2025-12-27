<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<h1 class="hp-listing__title">
	<?php
	/* translators: %s: booking number. */
	printf( esc_html__( 'Booking %s', 'hivepress-bookings' ), '#' . $booking->get_id() );
	?>
</h1>
