<div class="um-admin-metabox">
	
	<p><label for="_um_css_profile_card_bg"><?php _e('Profile card background','ultimatemember'); ?></label>
		<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_css_profile_card_bg', null, 'na'); ?>" class="um-admin-colorpicker" name="_um_css_profile_card_bg" id="_um_css_profile_card_bg" />
	</p>
	
	<p><label for="_um_css_profile_card_text"><?php _e('Profile card text','ultimatemember'); ?></label>
		<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_css_profile_card_text', null, 'na'); ?>" class="um-admin-colorpicker" name="_um_css_profile_card_text" id="_um_css_profile_card_text" />
	</p>
	
	<p><label for="_um_css_card_bordercolor"><?php _e('Profile card border color','ultimatemember'); ?></label>
		<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_css_card_bordercolor', null, 'na'); ?>" class="um-admin-colorpicker" name="_um_css_card_bordercolor" id="_um_css_card_bordercolor" />
	</p>
	
	<p><label for="_um_css_img_bordercolor"><?php _e('Profile photo border color','ultimatemember'); ?></label>
		<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_css_img_bordercolor', null, 'na'); ?>" class="um-admin-colorpicker" name="_um_css_img_bordercolor" id="_um_css_img_bordercolor" />
	</p>
	
	<p><label for="_um_css_card_thickness"><?php _e('Profile card border thickness','ultimatemember'); ?></label>
		<input type="text" value="<?php echo $ultimatemember->query->get_meta_value('_um_css_card_thickness', null, '1px'); ?>" name="_um_css_card_thickness" id="_um_css_card_thickness" />
	</p>
	
</div>