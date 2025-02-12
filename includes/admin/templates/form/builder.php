<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( UM()->builder()->form_id ) ) {
	UM()->builder()->form_id = $this->form_id;
}
?>

<div class="um-admin-builder" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>">

	<div class="um-admin-drag-ctrls-demo um-admin-drag-ctrls">
		<a href="#" class="active" data-modal="UM_preview_form" data-modal-size="larger"
			data-dynamic-content="um_admin_preview_form" data-arg1="<?php echo esc_attr( get_the_ID() ); ?>" data-arg2="">
			<?php esc_html_e( 'Live Preview Screen', 'ultimate-member' ); ?>
		</a>

		<a href="#" class="active" data-modal="UM_preview_form" data-modal-size="smaller"
			data-dynamic-content="um_admin_preview_form" data-arg1="<?php echo esc_attr( get_the_ID() ); ?>" data-arg2="">
			<?php esc_html_e( 'Live Preview Mobile', 'ultimate-member' ); ?>
		</a>
	</div>

	<div class="clear"></div>

	<div class="um-admin-drag">

		<div class="um-admin-drag-ajax" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>">
			<?php UM()->builder()->show_builder(); ?>
		</div>

		<div class="um-admin-drag-addrow um-tip-n" title="<?php esc_attr_e( 'Add Master Row', 'ultimate-member' ); ?>"
			data-row_action="add_row">
			<i class="um-icon-plus"></i>
		</div>

	</div>

</div>
