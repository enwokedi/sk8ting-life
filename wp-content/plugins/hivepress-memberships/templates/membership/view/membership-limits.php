<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( $membership->get_status() === 'publish' ) :
	$fields = $membership->_get_fields( 'view_block_secondary' );

	if ( hivepress()->request->get_context( 'vendor_id' ) ) :
		unset( $fields['user_message_limit'] );
	else :
		unset( $fields['vendor_message_limit'] );
	endif;
	?>
	<div class="hp-membership__limits">
		<?php
		foreach ( $fields as $field ) :
			if ( ! is_null( $field->get_value() ) ) :
				?>
				<div class="hp-membership__limit hp-meta">
					<?php echo $field->display(); ?>
				</div>
				<?php
			endif;
		endforeach;
		?>
	</div>
	<?php
endif;
