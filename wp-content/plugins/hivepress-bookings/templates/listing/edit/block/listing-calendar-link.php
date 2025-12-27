<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $listing->get_status() !== 'pending' ) :
	?>
	<a href="<?php echo esc_url( hivepress()->router->get_url( 'listing_calendar_page', [ 'listing_id' => $listing->get_id() ] ) ); ?>" title="<?php esc_attr_e( 'Calendar', 'hivepress-bookings' ); ?>" class="hp-listing__action hp-listing__action--calendar hp-link"><i class="hp-icon fas fa-calendar-alt"></i></a>
	<?php
endif;
