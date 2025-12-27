<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( isset( $booking_price ) ) :
	?>
	<div class="hp-booking__price hp-listing__attribute hp-listing__attribute--price">
		<?php echo $booking_price; ?>
	</div>
	<?php
endif;

if ( isset( $booking_extras ) ) :
	?>
	<div class="hp-listing__attribute hp-listing__attribute--price-extras">
		<?php echo esc_html( $booking_extras ); ?>
	</div>
	<?php
endif;
