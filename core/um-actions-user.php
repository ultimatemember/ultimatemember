<?php

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