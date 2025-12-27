<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$time  = $membership->get_expired_time();
$label = esc_html__( 'Expired on %s', 'hivepress-memberships' );

if ( isset( $subscription ) ) :
	if ( $subscription->get_date( 'next_payment' ) ) :
		$time = strtotime( $subscription->get_date( 'next_payment' ) );
	elseif ( $subscription->get_date( 'end' ) ) :
		$time = strtotime( $subscription->get_date( 'end' ) );
	endif;
endif;

if ( $time > time() ) :
	if ( isset( $subscription ) && $subscription->get_date( 'next_payment' ) ) :
		$label = esc_html__( 'Renews on %s', 'hivepress-memberships' );
	else :
		$label = esc_html__( 'Expires on %s', 'hivepress-memberships' );
	endif;
endif;

if ( $time ) :
	?>
	<time class="hp-membership__expired-date hp-meta" datetime="<?php echo esc_attr( date( 'Y-m-d H:i:s', $time ) ); ?>">
		<?php
		/* translators: %s: date. */
		printf( $label, date_i18n( get_option( 'date_format' ), $time ) );
		?>
	</time>
	<?php
endif;
