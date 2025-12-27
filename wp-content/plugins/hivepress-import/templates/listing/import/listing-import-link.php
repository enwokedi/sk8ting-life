<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<button type="button" class="hp-listing__action hp-listing__action--import button button--secondary" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'listing_import_page' ) ); ?>"><span><?php echo esc_html( hivepress()->translator->get_string( 'import_listings' ) ); ?></span></button>&nbsp;&nbsp;
