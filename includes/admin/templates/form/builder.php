<?php if ( ! defined( 'ABSPATH' ) ) exit;


if ( empty( UM()->builder()->form_id ) ) {
	UM()->builder()->form_id = $this->form_id;
} ?>

<div class="um-admin-builder" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>">

	<?php $fields = UM()->query()->get_attr( 'custom_fields', UM()->builder()->form_id ); ?>

	<input type="hidden" id="form__um_custom_fields" name="form[_um_custom_fields]" value="<?php echo esc_attr( serialize( $fields ) ); ?>">

	<div class="um-admin-drag-ctrls-demo um-admin-drag-ctrls">

		<a href="javascript:void(0);" class="active" data-modal="UM_preview_form"
		   data-modal-size="smaller" data-dynamic-content="um_admin_preview_form"
		   data-arg1="<?php esc_attr( UM()->builder()->form_id ); ?>"
		   data-arg2=""><?php _e( 'Live Preview', 'ultimate-member' ); ?></a>

	</div>

	<div class="um-admin-clear"></div>

	<div class="um-admin-drag">

		<div class="um-admin-drag-ajax" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>">

			<?php UM()->builder()->show_builder(); ?>

		</div>

		<div class="um-admin-drag-addrow um-admin-tipsy-n"
			 title="<?php esc_attr_e( 'Add Master Row', 'ultimate-member' ); ?>"
			 data-row_action="add_row">
			<i class="um-icon-plus"></i>
		</div>

	</div>

</div>