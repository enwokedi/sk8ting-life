<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $membership_plan->get_description() ) :
	?>
	<div class="hp-membership-plan__description"><?php echo $membership_plan->display_description(); ?></div>
	<?php
endif;
