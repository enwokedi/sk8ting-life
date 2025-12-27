<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<a href="<?php echo esc_url( hivepress()->router->get_url( 'listing_view_page', [ 'listing_id' => $listing->get_id() ] ) ); ?>" target="_blank" class="hp-booking__listing hp-booking__listing--<?php echo esc_attr( $listing->get_status() ); ?> hp-link">
	<i class="hp-icon fas fa-external-link-alt"></i>
	<span><?php echo esc_html( $listing->get_title() ); ?></span>
</a>
