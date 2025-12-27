<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$username = $booking->get_user__display_name();

if ( get_option( 'hp_user_enable_display' ) ) :
	$username = '<a href="' . esc_url( hivepress()->router->get_url( 'user_view_page', [ 'username' => $booking->get_user__username() ] ) ) . '">' . $username . '</a>';
endif;
?>
<time class="hp-listing__created-date hp-meta" datetime="<?php echo esc_attr( $booking->get_created_date() ); ?>">
	<?php
	/* translators: %1$s: booking date, %2$s: user name. */
	printf( esc_html__( 'Booked on %1$s by %2$s', 'hivepress-bookings' ), $booking->display_created_date(), $username );
	?>
</time>
