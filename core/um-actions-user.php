<?php

	/***
	***	@listen to a new user deletion
	***/
	function um_notify_delete_user($user_id) {

		global $ultimatemember;

		// send the admin an email
		$ultimatemember->mail->send( um_admin_email(), 'notification_deletion' );

		// increase the num. of deleted accounts that day
		$UM_deleted_users = get_option('um_deleted_users');
		if (!isset($UM_deleted_users[ date('Y-m-d') ] )){
			$UM_deleted_users[ date('Y-m-d') ] = 1;
		} else {
			$UM_deleted_users[ date('Y-m-d') ] = $UM_deleted_users[ date('Y-m-d') ] +1;
		}

		update_option('um_deleted_users', $UM_deleted_users);

	}
	add_action("delete_user", "um_notify_delete_user");

	/***
	***	@listen to a new user creation in backend
	***/
	function um_new_user_via_wpadmin( $user_id ) {
		
		if ( is_admin() ) {
		
			global $ultimatemember;
			
			if ( isset( $_POST['role'] ) && $_POST['role'] == 'administrator' ) {
				$args['role'] = 'admin';
			} else {
				$args['role'] = 'member';
			}

			do_action('um_after_new_user_register', $user_id, $args);
			
			do_action('um_update_profile_full_name', $_POST);

		}
		
	}
	add_action( 'user_register', 'um_new_user_via_wpadmin', 10, 1 );