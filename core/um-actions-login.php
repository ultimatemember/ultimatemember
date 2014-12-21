<?php

	/***
	***	@login user
	***/
	add_action('um_user_login', 'um_user_login', 10, 2);
	function um_user_login($user_id, $args){
		global $ultimatemember;

		um_fetch_user( $user_id );
		
		$ultimatemember->user->auto_login( $user_id );
	
	}
	
	/***
	***	@form processing
	***/
	add_action('um_submit_form_login', 'um_submit_form_login', 10);
	function um_submit_form_login($args){
		global $ultimatemember;
		
		if ( !isset($ultimatemember->form->errors) ) do_action('um_user_login', $args);
		
		do_action('um_user_login_extra_hook', $args );
		
	}

	/***
	***	@Show the submit button
	***/
	add_action('um_after_login_fields', 'um_add_submit_button_to_login', 1000);
	function um_add_submit_button_to_login($args){
		global $ultimatemember;
		
		// DO NOT add when reviewing user's details
		if ( $ultimatemember->user->preview == true && is_admin() ) return;
		
		?>
		
		<div class="um-col-alt">

			<?php if ( isset($args['secondary_btn']) && $args['secondary_btn'] != 0 ) { ?>
			
			<div class="um-left um-half"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>" class="um-button" /></div>
			<div class="um-right um-half"><a href="<?php echo um_get_core_page('register'); ?>" class="um-button um-alt"><?php echo $args['secondary_btn_word']; ?></a></div>
			
			<?php } else { ?>
			
			<div class="um-center"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>" class="um-button" /></div>
			
			<?php } ?>
			
			<div class="um-clear"></div>
			
		</div>
	
		<?php
	}

	/***
	***	@Display a forgot password link
	***/
	add_action('um_after_login_fields', 'um_after_login_submit', 1001);
	function um_after_login_submit(){ ?>
		
		<div class="um-col-alt-b">
			<a href="<?php echo um_get_core_page('password-reset'); ?>" class="um-link-alt"><?php _e('Forgot your password?','ultimatemember'); ?></a>
		</div>
		
		<?php
	}
	
	/***
	***	@Show Fields
	***/
	add_action('um_main_login_fields', 'um_add_login_fields', 100);
	function um_add_login_fields($args){
		global $ultimatemember;
		
		echo $ultimatemember->fields->display( 'login', $args );
		
	}