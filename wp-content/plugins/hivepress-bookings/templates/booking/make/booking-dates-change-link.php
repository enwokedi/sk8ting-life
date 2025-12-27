<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<a href="<?php echo esc_url( hivepress()->router->get_url( 'listing_view_page', [ 'listing_id' => $listing->get_id() ] ) ); ?>" class="hp-form__action hp-form__action--booking-dates-change hp-link">
	<i class="hp-icon fas fa-arrow-left"></i>
	<span><?php hivepress()->booking->is_time_enabled( $listing ) ? esc_html_e( 'Change Time', 'hivepress-bookings' ) : esc_html_e( 'Change Dates', 'hivepress-bookings' ); ?></span>
</a>
