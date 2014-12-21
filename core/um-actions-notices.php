<?php

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