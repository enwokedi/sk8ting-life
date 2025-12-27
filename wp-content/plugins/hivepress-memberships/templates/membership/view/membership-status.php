<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $membership->display_status() ) :
	?>
	<div class="hp-membership__status hp-status hp-status--<?php echo esc_attr( $membership->get_status() ); ?>">
		<span><?php echo esc_html( $membership->display_status() ); ?></span>
	</div>
	<?php
endif;
