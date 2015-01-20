<div class="um-admin-metabox">

	<div class="">
		
		<p>
			<label class="um-admin-half"><?php _e('Can edit their profile?','ultimatemember'); ?> <?php $this->tooltip( __('Can this role edit his own profile?','ultimatemember') ); ?></label>
			<span class="um-admin-half"><?php $this->ui_on_off('_um_can_edit_profile', 1); ?></span>
		</p><div class="um-admin-clear"></div>
	
		<p>
			<label class="um-admin-half"><?php _e('Can delete their account?','ultimatemember'); ?> <?php $this->tooltip( __('Allow this role to delete their account and end their membership on your site','ultimatemember') ); ?></label>
			<span class="um-admin-half"><?php $this->ui_on_off('_um_can_delete_profile', 1); ?></span>
		</p><div class="um-admin-clear"></div>
		
	</div>
	
	<div class="um-admin-clear"></div>
	
</div>