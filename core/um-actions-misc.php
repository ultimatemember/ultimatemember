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
		$output = '';
		
		if ( isset( $_REQUEST['updated'] ) && !empty( $_REQUEST['updated'] ) ) {
			switch( $_REQUEST['updated'] ) {
				case 'password_changed':
					$success = __('You have successfully changed your password.','ultimatemember');
					break;
			}
		}
		
		if ( isset( $_REQUEST['err'] ) && !empty( $_REQUEST['err'] ) ) {
			switch( $_REQUEST['err'] ) {
				case 'inactive':
					$err = __('Your account has been disabled.','ultimatemember');
					break;
				case 'awaiting_admin_review':
					$err = __('Your account has not been approved yet.','ultimatemember');
					break;
				case 'awaiting_email_confirmation':
					$err = __('Your account is awaiting e-mail verifications.','ultimatemember');
					break;
				case 'rejected':
					$err = __('Your membership request has been rejected.','ultimatemember');
					break;
			}
		}
		
		if ( isset( $err ) ) {
			$output .= '<p class="um-notice err">' . $err . '</p>';
		}
		
		echo $output;
		
	}