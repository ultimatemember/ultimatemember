<div class="um-admin-metabox">

	<div class="">
		
		<p>
			<label class="um-admin-half"><?php _e('Use Infinite Scroll instead of pagination','ultimatemember'); ?></label>
			<span class="um-admin-half">
			
				<?php $this->ui_on_off('_um_infinitescroll', 0, true, 1, 'infinite-settings', 'pagination-settings'); ?>
				
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="infinite-settings um-admin-hide">
			<label class="um-admin-half"><?php _e('Number of profiles to show on first load','ultimatemember'); ?> <?php $this->tooltip('Number of member profiles to appear on the first load only'); ?></label>
			<span class="um-admin-half">
			
				<input type="text" name="_um_profiles_on_load" id="_um_profiles_on_load" value="<?php echo $ultimatemember->query->get_meta_value('_um_max_users', null, 12 ); ?>" class="small" />
			
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="infinite-settings um-admin-hide">
			<label class="um-admin-half"><?php _e('Number of profiles to show on load more','ultimatemember'); ?> <?php $this->tooltip('Number of member profiles to appear when user loads more profiles'); ?></label>
			<span class="um-admin-half">
			
				<input type="text" name="_um_profiles_load_more" id="_um_profiles_load_more" value="<?php echo $ultimatemember->query->get_meta_value('_um_max_users', null, 12 ); ?>" class="small" />
			
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p class="pagination-settings">
			<label class="um-admin-half"><?php _e('Number of profiles per page','ultimatemember'); ?> <?php $this->tooltip('Number of member profiles to appear on every page'); ?></label>
			<span class="um-admin-half">
			
				<input type="text" name="_um_profiles_per_page" id="_um_profiles_per_page" value="<?php echo $ultimatemember->query->get_meta_value('_um_profiles_per_page', null, 12); ?>" class="small" />
			
			</span>
		</p><div class="um-admin-clear"></div>
		
		<p>
			<label class="um-admin-half"><?php _e('Maximum number of profiles','ultimatemember'); ?> <?php $this->tooltip('Use this setting to control the maximum number of profiles to appear in this directory. Leave blank to disable this limit'); ?></label>
			<span class="um-admin-half">
				
				<input type="text" name="_um_max_users" id="_um_max_users" value="<?php echo $ultimatemember->query->get_meta_value('_um_max_users', null, 'na' ); ?>" class="small" />
				
			</span>
		</p><div class="um-admin-clear"></div>

	</div>
	
	<div class="um-admin-clear"></div>
	
</div>