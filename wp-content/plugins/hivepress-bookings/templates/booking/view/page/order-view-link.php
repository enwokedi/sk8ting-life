<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( isset( $order ) ) :
	$order_url = get_current_user_id() === $booking->get_user__id() ? $order->get_view_order_url() : hivepress()->router->get_url( 'order_edit_page', [ 'order_id' => $order->get_id() ] );
	?>
	<a href="<?php echo esc_url( $order_url ); ?>" class="hp-listing__action hp-listing__action--order hp-link">
		<i class="hp-icon fas fa-eye"></i>
		<span><?php echo esc_html( hivepress()->translator->get_string( 'view_order' ) ); ?></span>
	</a>
	<?php
endif;
