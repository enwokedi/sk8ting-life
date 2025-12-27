<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $membership && ! $membership_plan->get_product__id() && ( $membership->get_status() !== 'draft' || ! get_option( 'hp_membership_renew_free' ) ) ) :
	?>
	<button type="button" class="hp-membership-plan__select-button button" disabled><?php echo esc_html( $membership->display_status() ); ?></button>
<?php else : ?>
	<button type="button" class="hp-membership-plan__select-button button button--primary alt" data-component="link" data-url="<?php echo esc_url( hivepress()->router->get_url( 'membership_plan_select_page', [ 'membership_plan_id' => $membership_plan->get_id(), 'redirect' => hivepress()->router->get_redirect_url() ] ) ); ?>">
		<span>
			<?php
			if ( $membership ) :
				esc_html_e( 'Renew Plan', 'hivepress-memberships' );
			elseif ( $membership_plan->get_product__id() ) :
				esc_html_e( 'Buy Plan', 'hivepress-memberships' );
			else :
				esc_html_e( 'Select Plan', 'hivepress-memberships' );
			endif;
			?>
		</span>
	</button>
	<?php
endif;
