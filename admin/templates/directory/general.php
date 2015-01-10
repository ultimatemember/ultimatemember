<?php

	$meta = get_post_custom( get_the_ID() );
	foreach( $meta as $k => $v ) {
		if ( strstr( $k, '_um_' ) && !is_array( $v[0] ) ) {
			//print "'$k' => '" . $v[0] . "',<br />";
		}
	}
	
?>

<div class="um-admin-metabox">

	<div class="">
	
		<input type="hidden" name="_um_mode" id="_um_mode" value="directory" />
	
		<p>
			<label class="um-admin-half"><?php _e('User Roles to Display','ultimatemember'); ?> <?php $this->tooltip('If you do not want to show all members, select only user roles to appear in this directory'); ?></label>
			<span class="um-admin-half">
			
				<select multiple="multiple" name="_um_roles[]" id="_um_roles" class="umaf-selectjs" style="width: 300px">
					<?php foreach($ultimatemember->query->get_roles() as $key => $value) { ?>
					<option value="<?php echo $key; ?>" <?php selected($key, $ultimatemember->query->get_meta_value('_um_roles', $key ) ); ?>><?php echo $value; ?></option>
					<?php } ?>	
				</select>
				
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p>
			<label class="um-admin-half"><?php _e('Only show members who have uploaded a profile photo','ultimatemember'); ?></label>
			<span class="um-admin-half">
			
				<?php $this->ui_on_off('_um_has_profile_photo'); ?>
				
			</span>
		</p><div class="um-admin-clear"></div>

		<p>
			<label class="um-admin-half"><?php _e('Only show members who have uploaded a cover photo','ultimatemember'); ?></label>
			<span class="um-admin-half">
			
				<?php $this->ui_on_off('_um_has_cover_photo'); ?>
				
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p>
			<label class="um-admin-half"><?php _e('Sort users by','ultimatemember'); ?> <?php $this->tooltip('Sort users by a specific parameter in the directory'); ?></label>
			<span class="um-admin-half">
			
				<select name="_um_sortby" id="_um_sortby" class="umaf-selectjs um-adm-conditional" style="width: 300px" data-cond1='other' data-cond1-show='custom-field'>
					<option value="user_registered_desc" <?php selected('user_registered_desc', $ultimatemember->query->get_meta_value('_um_sortby') ); ?>>New users first</option>
					<option value="user_registered_asc" <?php selected('user_registered_asc', $ultimatemember->query->get_meta_value('_um_sortby') ); ?>>Old users first</option>
					<option value="display_name" <?php selected('display_name', $ultimatemember->query->get_meta_value('_um_sortby') ); ?>>Display Name</option>
					<option value="first_name" <?php selected('first_name', $ultimatemember->query->get_meta_value('_um_sortby') ); ?>>First Name</option>
					<option value="last_name" <?php selected('last_name', $ultimatemember->query->get_meta_value('_um_sortby') ); ?>>Last Name</option>
					<option value="random" <?php selected('random', $ultimatemember->query->get_meta_value('_um_sortby') ); ?>>Random</option>
					<option value="other" <?php selected('other', $ultimatemember->query->get_meta_value('_um_sortby') ); ?>>Other (custom field)</option>
				</select>
				
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="custom-field">
			<label class="um-admin-half"><?php _e('Meta key','ultimatemember'); ?> <?php $this->tooltip('To sort by a custom field, enter the meta key of field here'); ?></label>
			<span class="um-admin-half">
			
				<input type="text" name="_um_sortby_custom" id="_um_sortby_custom" value="<?php echo $ultimatemember->query->get_meta_value('_um_sortby_custom', null, 'na' ); ?>" />
				
			</span>
		</p><div class="um-admin-clear"></div>
		
	</div>
	
	<div class="um-admin-clear"></div>
	
</div>