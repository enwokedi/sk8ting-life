<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$has_note = get_option( 'hp_listing_allow_purchase_note' ) && $listing->get_purchase_note();

if ( $booking->get_note() ) :
	?>
	<div class="hp-listing__description">
		<?php if ( $has_note ) : ?>
			<h4><?php esc_html_e( 'Customer Note', 'hivepress-bookings' ); ?></h4>
			<?php
		endif;

		echo $booking->display_note();
		?>
	</div>
	<?php
endif;

if ( $has_note && ( 'publish' === $booking->get_status() || get_current_user_id() === $listing->get_user__id() ) ) :
	?>
	<div class="hp-listing__description">
		<h4><?php esc_html_e( 'Booking Note', 'hivepress-bookings' ); ?></h4>
		<?php echo $listing->display_purchase_note(); ?>
	</div>
	<?php
endif;
