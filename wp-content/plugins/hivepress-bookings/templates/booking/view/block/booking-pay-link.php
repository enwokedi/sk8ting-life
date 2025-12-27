<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $booking->get_status() === 'draft' && get_current_user_id() === $booking->get_user__id() ) :
	?>
	<a href="<?php echo esc_url( hivepress()->router->get_url( 'booking_pay_page', [ 'booking_id' => $booking->get_id() ] ) ); ?>" title="<?php esc_attr_e( 'Pay Now', 'hivepress-bookings' ); ?>" class="hp-listing__action hp-listing__action--pay"><i class="hp-icon fas fa-credit-card"></i></a>
	<?php
endif;
