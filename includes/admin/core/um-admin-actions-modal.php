<?php

	/***
	***	@Put status handler in modal
	***/
	add_action('um_admin_field_modal_header', 'um_admin_add_message_handlers');
	function um_admin_add_message_handlers(){ ?><div class="um-admin-error-block"></div><div class="um-admin-success-block"></div> <?php }
	
	/***
	***	@Footer of modal
	***/
	add_action('um_admin_field_modal_footer', 'um_admin_add_conditional_support', 10, 4);
	function um_admin_add_conditional_support( $form_id, $field_args, $in_edit, $edit_array ){
		$metabox = UM()->metabox();

		if ( isset( $field_args['conditional_support'] ) && $field_args['conditional_support'] == 0 )
			return;
		
		?>
		
		<div class="um-admin-btn-toggle">
		
			<?php if ( $in_edit ) { $metabox->in_edit = true;  $metabox->edit_array = $edit_array; ?>
			<a href="#"><i class="um-icon-plus"></i><?php _e('Manage conditional fields support'); ?></a> <?php UM()->tooltip( __( 'Here you can setup conditional logic to show/hide this field based on specific fields value or conditions', 'ultimate-member' ) ); ?>
			<?php } else { ?>
			<a href="#"><i class="um-icon-plus"></i><?php _e('Add conditional fields support'); ?></a> <?php UM()->tooltip( __( 'Here you can setup conditional logic to show/hide this field based on specific fields value or conditions', 'ultimate-member' ) ); ?>
			<?php } ?>
			
			<div class="um-admin-btn-content">
			
				<p class="um-admin-reset-conditions"><a href="#" class="button button-primary"><?php _e('Reset all rules','ultimate-member'); ?></a></p>
				<div class="um-admin-clear"></div>
				
				<?php
				
				if ( isset( $edit_array['conditions'] ) ){
					
					foreach( $edit_array['conditions'] as $k => $arr ) {

						if ( $k == 0 ) $k = '';
				?>
				
				<div class="um-admin-cur-condition">
				
				<?php $metabox->field_input( '_conditional_action' . $k, $form_id ); ?>
				<?php $metabox->field_input( '_conditional_field' . $k , $form_id ); ?>
				<?php $metabox->field_input( '_conditional_operator' . $k, $form_id ); ?>
				<?php $metabox->field_input( '_conditional_value' . $k, $form_id ); ?>
				
				<?php if ( $k == '' ) { ?>
				<p><a href="#" class="um-admin-new-condition button um-admin-tipsy-n" title="Add new condition"><i class="um-icon-plus" style="margin-right:0!important"></i></a></p>
				<?php } else { ?>
				<p><a href="#" class="um-admin-remove-condition button um-admin-tipsy-n" title="Remove condition"><i class="um-icon-close" style="margin-right:0!important"></i></a></p>
				<?php } ?>
				
				<div class="um-admin-clear"></div>
				</div>
				
				<?php
				
					}
					
				} else {
				
				?>
			
				<div class="um-admin-cur-condition">
				
				<?php $metabox->field_input( '_conditional_action', $form_id ); ?>
				<?php $metabox->field_input( '_conditional_field', $form_id ); ?>
				<?php $metabox->field_input( '_conditional_operator', $form_id ); ?>
				<?php $metabox->field_input( '_conditional_value', $form_id ); ?>
				
				<p><a href="#" class="um-admin-new-condition button um-admin-tipsy-n" title="Add new condition"><i class="um-icon-plus" style="margin-right:0!important"></i></a></p>
				
				<div class="um-admin-clear"></div>
				</div>
				
				<?php } ?>

			</div>
			
		</div>

		<?php
		
	}