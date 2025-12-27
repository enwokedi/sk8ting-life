<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( hivepress()->request->get_context( 'listing_count' ) ) :
	?>
	<button type="button" class="hp-listing__action hp-listing__action--export button" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'listing_export_page' ) ); ?>"><span><?php echo esc_html( hivepress()->translator->get_string( 'export_listings' ) ); ?></span></button>
	<?php
endif;
