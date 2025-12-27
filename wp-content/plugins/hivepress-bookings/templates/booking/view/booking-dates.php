<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="hp-booking__dates hp-listing__attribute hp-listing__attribute--dates">
	<span><?php echo esc_html( $booking->display_dates() ); ?></span>
	<?php if ( get_option( 'hp_booking_enable_timezone' ) && $listing->get_booking_timezone() ) : ?>
		<small>(<?php echo esc_html( $listing->display_booking_timezone() ); ?>)</small>
	<?php endif; ?>
</div>
