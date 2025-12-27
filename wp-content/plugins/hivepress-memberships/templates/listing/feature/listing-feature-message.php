<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<p><?php echo esc_html( hivepress()->translator->get_string( 'confirm_listing_featuring' ) ); ?></p>
<button type="button" class="hp-button hp-button--wide button button--large button--primary alt" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'listing_feature_page', [ 'listing_id' => $listing->get_id() ] ) ); ?>"><?php echo esc_html( hivepress()->translator->get_string( 'feature_listing' ) ); ?></button>
