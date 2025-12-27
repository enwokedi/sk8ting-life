<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<a href="<?php echo esc_url( hivepress()->router->get_url( 'listing_import_page', [ 'reset' => true ] ) ); ?>" class="hp-form__action hp-form__action--listing-file-change hp-link"><i class="hp-icon fas fa-arrow-left"></i><span><?php esc_html_e( 'Change File', 'hivepress-import' ); ?></span></a>
