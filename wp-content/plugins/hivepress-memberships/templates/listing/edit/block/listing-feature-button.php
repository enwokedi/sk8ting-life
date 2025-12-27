<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $listing->get_status() === 'publish' ) :
	if ( ! $listing->is_featured() ) :
		?>
		<a href="<?php echo esc_url( $listing_feature_url ); ?>" title="<?php esc_attr_e( 'Feature', 'hivepress-memberships' ); ?>" class="hp-listing__action hp-listing__action--feature hp-link"><i class="hp-icon fas fa-star"></i></a>
	<?php else : ?>
		<span title="<?php echo esc_attr_x( 'Featured', 'listing', 'hivepress-memberships' ); ?>" class="hp-listing__action hp-listing__action--feature hp-link" data-state="active"><i class="hp-icon fas fa-star"></i></span>
		<?php
	endif;
endif;
