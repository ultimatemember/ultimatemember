<?php

	/***
	***	@filter to customize errors
	***/
	add_filter('login_message', 'um_custom_wp_err_messages');
	function um_custom_wp_err_messages( $message) {

		if ( isset( $_REQUEST['err'] ) && !empty( $_REQUEST['err'] ) ) {
			switch( $_REQUEST['err'] ) {
				case 'blocked_email':
					$err = __('This email address has been blocked.','ultimatemember');
					break;
				case 'blocked_ip':
					$err = __('Your IP address has been blocked.','ultimatemember');
					break;
			}
		}
		
		if ( isset( $err ) ) {
			$message = '<div class="login" id="login_error">'.$err.'</div>';
		}
		
		return $message;
	}
	
	/***
	***	@check for blocked ip
	***/
	add_filter('authenticate', 'um_wp_form_errors_hook_ip_test', 10, 3);
	function um_wp_form_errors_hook_ip_test( $user, $username, $password ) {
		if (!empty($username)) {

			do_action("um_submit_form_errors_hook__blockedips", $args=array() );
			do_action("um_submit_form_errors_hook__blockedemails", $args=array('username' => $username ) );
			
		}

		return $user;
	}
	
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