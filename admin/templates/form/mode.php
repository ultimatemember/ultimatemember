<?php $is_core = get_post_meta( get_the_ID(), '_um_core', true ); ?>

<div class="um-admin-boxed-links um-admin-ajaxlink <?php if ( $is_core ) echo 'is-core-form'; ?>">

	<?php if ( $is_core ) { ?>
	<p><?php _e('<strong>Note:</strong> Form type cannot be changed for the default forms.','ultimatemember'); ?></p>
	<?php } ?>
	
	<a href="#" data-role="register"><?php echo $ultimatemember->form->display_form_type_icon('register', get_the_ID() ); ?><?php _e('Registration Form','ultimatemember'); ?></a>
	
	<a href="#" data-role="profile"><?php echo $ultimatemember->form->display_form_type_icon('profile', get_the_ID() ); ?><?php _e('Profile Form','ultimatemember'); ?></a>

	<a href="#" data-role="login"><?php echo $ultimatemember->form->display_form_type_icon('login', get_the_ID() ); ?><?php _e('Login Form','ultimatemember'); ?></a>
	
	<input type="hidden" name="_um_mode" id="_um_mode" value="<?php echo $ultimatemember->query->get_meta_value('_um_mode', null, 'register' ); ?>" />
	
</div><div class="um-admin-clear"></div>