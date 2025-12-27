<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $booking->get_status() === 'trash' ) :
	$user_name = $booking->is_canceled() ? $booking->get_user__display_name() : $listing->get_user__display_name();
	?>
	<time class="hp-listing__created-date hp-meta" datetime="<?php echo esc_attr( $booking->get_modified_date() ); ?>">
		<?php
		/* translators: %1$s: cancellation date, %2$s: user name. */
		printf( esc_html__( 'Canceled on %1$s by %2$s', 'hivepress-bookings' ), $booking->display_modified_date(), $user_name );
		?>
	</time>
	<?php
endif;
