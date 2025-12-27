<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $booking->get_status() === 'draft' && get_current_user_id() === $booking->get_user__id() ) :
	?>
	<button type="button" class="hp-listing__action hp-listing__action--pay button button--large button--primary alt" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'booking_pay_page', [ 'booking_id' => $booking->get_id() ] ) ); ?>"><?php esc_html_e( 'Pay Now', 'hivepress-bookings' ); ?></button>
	<?php
endif;
