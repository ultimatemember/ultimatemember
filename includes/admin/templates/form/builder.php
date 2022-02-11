<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( UM()->builder()->form_id ) ) {
	UM()->builder()->form_id = $this->form_id;
} ?>

<!-- data-in_row="" data-in_sub_row="" data-in_column="" data-in_group="" data is used for builder handlers when trying to insert field in the selected place -->
<div class="um-admin-builder" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>" data-in_row="" data-in_sub_row="" data-in_column="" data-in_group="">

	<?php $fields = UM()->query()->get_attr( 'custom_fields', UM()->builder()->form_id ); ?>

<!--	<input type="hidden" id="form__um_custom_fields" name="form[_um_custom_fields]" value="--><?php //echo esc_attr( serialize( $fields ) ); ?><!--">-->

	<div class="um-admin-drag-ctrls-demo um-admin-drag-ctrls">
		<a href="javascript:void(0);" class="active um_admin_preview_form" data-form_id="<?php echo esc_attr( get_the_ID() ); ?>">
			<?php esc_html_e( 'Live Preview', 'ultimate-member' ); ?>
		</a>
	</div>

	<div class="um-admin-clear"></div>

	<div class="um-admin-drag">

		<div class="um-admin-drag-ajax" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>">
			<?php UM()->builder()->show_builder(); ?>
		</div>

		<div class="um-admin-drag-addrow um-admin-tipsy-n"
			 title="<?php esc_attr_e( 'Add Master Row', 'ultimate-member' ); ?>"
			 data-row_action="add_row">
			<i class="fas fa-plus"></i>
		</div>

	</div>

</div>
