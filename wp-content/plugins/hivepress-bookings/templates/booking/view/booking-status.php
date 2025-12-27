<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $booking->display_status() ) :
	?>
	<div class="hp-listing__status hp-status hp-status--<?php echo esc_attr( $booking->get_status() ); ?>">
		<span><?php echo esc_html( $booking->display_status() ); ?></span>
	</div>
	<?php
endif;
