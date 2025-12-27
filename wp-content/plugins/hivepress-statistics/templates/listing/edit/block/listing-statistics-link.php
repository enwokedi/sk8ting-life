<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $listing->get_status() === 'publish' ) :
	?>
	<a href="<?php echo esc_url( hivepress()->router->get_url( 'listing_statistics_page', [ 'listing_id' => $listing->get_id() ] ) ); ?>" title="<?php esc_attr_e( 'Statistics', 'hivepress-statistics' ); ?>" class="hp-listing__action hp-listing__action--statistics hp-link"><i class="hp-icon fas fa-chart-pie"></i></a>
	<?php
endif;
