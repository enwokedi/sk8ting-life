<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<td class="hp-search-alert__query">
	<strong><?php echo $search_alert->get_query() ? esc_html( $search_alert->get_query() ) : '&mdash;'; ?></strong>
</td>
