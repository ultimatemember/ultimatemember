<?php

	/***
	***	@add a force redirect to from $_get
	***/
	add_action('um_after_form_fields', 'um_browser_url_redirect_to');
	function um_browser_url_redirect_to($args) {
	
		global $ultimatemember;
		
		if ( isset( $_REQUEST['redirect_to'] ) && !empty( $_REQUEST['redirect_to'] ) ) {
		
			echo '<input type="hidden" name="redirect_to" id="redirect_to" value="'.$_REQUEST['redirect_to'].'" />';

		}

		if ( isset( $args['after_login'] ) && !empty( $args['after_login'] ) ) {
			
			switch( $args['after_login'] ) {
				
				case 'redirect_admin':
					$url = admin_url();
					break;
					
				case 'redirect_profile':
					$url = um_user_profile_url();
					break;
				
				case 'redirect_url':
					$url = $args['redirect_url'];
					break;
					
				case 'refresh':
					$url = $ultimatemember->permalinks->get_current_url();
					break;
					
			}

			echo '<input type="hidden" name="redirect_to" id="redirect_to" value="' . $url . '" />';
			
		}
		
	}
	
	/***
	***	@add a notice to form
	***/
	add_action('um_before_form', 'um_add_update_notice', 500 );
	function um_add_update_notice($args){
		global $ultimatemember;
		extract($args);
		if ( isset( $_REQUEST['updated'] ) && !empty( $_REQUEST['updated'] ) ) {
		
			switch( $_REQUEST['updated'] ) {
			
				case 'password_changed':
					$msg = __('You have successfully changed your password.','ultimatemember');
					break;
					
			}
		
		}
		
		if ( isset( $msg ) ) {
		
			echo '<p class="um-notice success">' . $msg . '</p>';
			
		}
		
	}