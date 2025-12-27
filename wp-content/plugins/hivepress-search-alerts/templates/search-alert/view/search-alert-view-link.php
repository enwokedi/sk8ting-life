<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<a href="<?php echo esc_url( add_query_arg( $search_alert->get_params(), home_url() ) ); ?>" title="<?php esc_attr_e( 'View Results', 'hivepress-search-alerts' ); ?>" class="hp-search-alert__action hp-search-alert__action--view hp-link"><i class="hp-icon fas fa-eye"></i></a>
