<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<td class="hp-search-alert__category">
	<span>
		<?php
		if ( $search_alert->get_category__id() ) :
			echo esc_html( $search_alert->get_category__name() );
		else :
			esc_html_e( 'All Categories', 'hivepress-search-alerts' );
		endif;
		?>
	</span>
</td>
