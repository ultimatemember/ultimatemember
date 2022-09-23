<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( UM()->builder()->form_id ) ) {
	UM()->builder()->form_id = $this->form_id;
}
?>

<!-- data-in_row="" data-in_sub_row="" data-in_column="" data-in_group="" data is used for builder handlers when trying to insert field in the selected place -->
<div class="um-admin-builder" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>" data-in_row="" data-in_sub_row="" data-in_column="" data-in_group="">

	<?php $fields = UM()->query()->get_attr( 'custom_fields', UM()->builder()->form_id ); ?>

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

<div class="um-col-demon-row" style="display:none;">

	<div class="um-admin-drag-row-icons">
		<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="fas fa-plus"></i></a>
		<a href="javascript:void(0);" class="um-admin-drag-row-edit um_admin_edit_field_popup um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member' ); ?>" data-arg1="row" data-arg2="<?php echo esc_attr( get_the_ID() ); ?>" data-field_type="row" data-form_id="<?php echo esc_attr( get_the_ID() ); ?>"><i class="fas fa-pencil-alt"></i></a>
		<span class="um-admin-drag-row-start"><i class="fas fa-arrows-alt"></i></span>
		<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-row"><i class="far fa-trash-alt"></i></a>
	</div>
	<div class="um-admin-clear"></div>

	<div class="um-admin-drag-rowsubs">
		<div class="um-admin-drag-rowsub">

			<div class="um-admin-drag-ctrls columns">
				<a href="javascript:void(0);" class="active" data-cols="1"></a>
				<a href="javascript:void(0);" data-cols="2"></a>
				<a href="javascript:void(0);" data-cols="3"></a>
			</div>

			<div class="um-admin-drag-rowsub-icons">
				<span class="um-admin-drag-rowsub-start"><i class="fas fa-arrows-alt"></i></span>
				<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-rowsub"><i class="far fa-trash-alt"></i></a>
			</div>

			<div class="um-admin-clear"></div>

			<div class="um-admin-drag-col"></div>

			<div class="um-admin-drag-col-dynamic"></div>

			<div class="um-admin-clear"></div>

		</div>
	</div>

</div>

<div class="um-col-demon-subrow" style="display:none;">

	<div class="um-admin-drag-ctrls columns">
		<a href="javascript:void(0);" class="active" data-cols="1"></a>
		<a href="javascript:void(0);" data-cols="2"></a>
		<a href="javascript:void(0);" data-cols="3"></a>
	</div>

	<div class="um-admin-drag-rowsub-icons">
		<span class="um-admin-drag-rowsub-start"><i class="fas fa-arrows-alt"></i></span>
		<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-rowsub"><i class="far fa-trash-alt"></i></a>
	</div>

	<div class="um-admin-clear"></div>

	<div class="um-admin-drag-col"></div>

	<div class="um-admin-drag-col-dynamic"></div>

	<div class="um-admin-clear"></div>

</div>
