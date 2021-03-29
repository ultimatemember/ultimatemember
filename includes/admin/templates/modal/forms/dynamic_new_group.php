<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<div id="UM_add_group" style="display:none">

	<form action="" method="post" class="um_add_field">

		<div class="um-admin-modal-head">
			<h3><?php _e( 'Add a New Field Group', 'ultimate-member' ); ?></h3>
		</div>

		<div class="um-admin-modal-body um-admin-metabox"></div>

		<div class="um-admin-modal-foot">
			<input type="submit" value="<?php esc_attr_e( 'Add', 'ultimate-member' ); ?>" class="button-primary" />
			<input type="hidden" name="action" value="um_update_field" />
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-admin-nonce' ) ) ?>" />
			<a href="javascript:void(0);" data-action="UM_remove_modal" class="button"><?php _e( 'Cancel', 'ultimate-member' ); ?></a>
		</div>

	</form>

</div>