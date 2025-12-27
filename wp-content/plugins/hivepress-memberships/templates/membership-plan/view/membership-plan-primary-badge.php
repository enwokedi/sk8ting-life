<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $membership_plan->is_primary() ) :
	?>
	<i class="hp-membership-plan__primary-badge hp-icon fas fa-check-circle" title="<?php echo esc_attr_x( 'Recommended', 'plan', 'hivepress-memberships' ); ?>"></i>
	<?php
endif;
