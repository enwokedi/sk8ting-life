<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<p>
	<?php
	if ( ! $listing_import_errors ) :
		esc_html_e( 'Import has been successfully finished.', 'hivepress-import' );
	elseif ( $listing_import_count ) :
		esc_html_e( 'Import has finished with errors.', 'hivepress-import' );
	else :
		esc_html_e( 'Import has failed with errors.', 'hivepress-import' );
	endif;
	?>
</p>
<?php if ( $listing_import_count ) : ?>
	<button type="button" class="button" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'listings_edit_page' ) ); ?>"><?php echo esc_html( hivepress()->translator->get_string( 'view_listings' ) . ' (' . $listing_import_count . ')' ); ?></button>
<?php else : ?>
	<button type="button" class="button" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'listing_import_page' ) ); ?>"><?php esc_html_e( 'Change File', 'hivepress-import' ); ?></button>
	<?php
endif;

if ( $listing_import_errors ) :
	echo '<pre>';

	foreach ( $listing_import_errors as $index => $messages ) :
		/* translators: %d: row number. */
		echo '<strong>' . esc_html( sprintf( __( 'Row #%d', 'hivepress-import' ), $index ) ) . '</strong>' . PHP_EOL;

		foreach ( $messages as $message ) :
			echo '  - ' . esc_html( $message ) . PHP_EOL;
		endforeach;
	endforeach;

	echo '</pre>';
endif;
