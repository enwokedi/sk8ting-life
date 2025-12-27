<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $listing->get_status() === 'publish' && ! $listing->is_featured() ) :
	?>
	<a href="<?php echo esc_url( $listing_feature_url ); ?>" class="hp-listing__action hp-listing__action--feature hp-link"><i class="hp-icon fas fa-star"></i><span><?php esc_html_e( 'Feature', 'hivepress-memberships' ); ?></span></a>
	<?php
endif;
