<div class="um-admin-boxed-links um-admin-ajaxlink">

	<a href="#" data-role="register"><?php echo $ultimatemember->form->display_form_type_icon('register', get_the_ID() ); ?><?php _e('Registration Form','ultimatemember'); ?></a>
	
	<a href="#" data-role="profile"><?php echo $ultimatemember->form->display_form_type_icon('profile', get_the_ID() ); ?><?php _e('Profile Form','ultimatemember'); ?></a>

	<a href="#" data-role="login"><?php echo $ultimatemember->form->display_form_type_icon('login', get_the_ID() ); ?><?php _e('Login Form','ultimatemember'); ?></a>
	
	<input type="hidden" name="_um_mode" id="_um_mode" value="<?php echo $ultimatemember->query->get_meta_value('_um_mode', null, 'register' ); ?>" />
	
</div><div class="um-admin-clear"></div>