<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<a href="#search_alert_delete_modal_<?php echo esc_attr( $search_alert->get_id() ); ?>" title="<?php esc_attr_e( 'Delete Search', 'hivepress-search-alerts' ); ?>" class="hp-search-alert__action hp-search-alert__action--delete hp-link"><i class="hp-icon fas fa-times"></i></a>
