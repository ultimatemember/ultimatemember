<?php

	/***
	***	@login checks thru the wordpress admin login
	***/
	add_filter('authenticate', 'um_wp_form_errors_hook_logincheck', 999, 3);
	function um_wp_form_errors_hook_logincheck( $user, $username, $password ) {
		
		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
		
		if ( isset( $user->ID ) ) {
		
			um_fetch_user( $user->ID );
			$status = um_user('account_status');

			switch( $status ) {
				case 'inactive':
					return new WP_Error( $status, __('Your account has been disabled.','ultimatemember') );
					break;
				case 'awaiting_admin_review':
					return new WP_Error( $status, __('Your account has not been approved yet.','ultimatemember') );
					break;
				case 'awaiting_email_confirmation':
					return new WP_Error( $status, __('Your account is awaiting e-mail verifications.','ultimatemember') );
					break;
				case 'rejected':
					return new WP_Error( $status, __('Your membership request has been rejected.','ultimatemember') );
					break;
			}
			
		}

		return wp_authenticate_username_password( null, $username, $password );

	}