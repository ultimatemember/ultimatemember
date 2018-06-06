<?php if ( empty( UM()->builder()->form_id ) ) {
	UM()->builder()->form_id = $this->form_id;
} ?>

<div class="um-admin-builder" data-form_id="<?php echo UM()->builder()->form_id; ?>">

	<div class="um-admin-drag-ctrls-demo um-admin-drag-ctrls">
		
		<a href="#" class="active" data-modal="UM_preview_form" data-modal-size="smaller" data-dynamic-content="um_admin_preview_form" data-arg1="<?php the_ID(); ?>" data-arg2=""><?php _e('Live Preview','ultimate-member'); ?></a>
		
	</div>
	
	<div class="um-admin-clear"></div>
	
	<div class="um-admin-drag">
		
		<div class="um-admin-drag-ajax" data-form_id="<?php echo UM()->builder()->form_id; ?>">
		
			<?php UM()->builder()->show_builder(); ?>
			
		</div>
		
		<div class="um-admin-drag-addrow um-admin-tipsy-n" title="<?php _e('Add Master Row','ultimate-member'); ?>" data-row_action="add_row"><i class="um-icon-plus"></i></div>
	
	</div>

</div>