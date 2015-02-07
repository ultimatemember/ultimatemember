<?php

	/***
	***	@after user uploads, clean up uploads dir
	***/
	add_action('um_after_user_upload','um_remove_unused_uploads', 10);
	function um_remove_unused_uploads( $user_id ) {
		global $ultimatemember;
		um_fetch_user( $user_id );
		$array = $ultimatemember->user->profile;
		$files = glob( um_user_uploads_dir() . '*', GLOB_BRACE);
		foreach($files as $file) {
			$str = basename($file);
			if ( !strstr( $str, 'profile_photo') && !strstr( $str, 'cover_photo') && !preg_grep('/' . $str . '/', $array ) )
				unlink( $file );
		}
		um_reset_user();
	}

	/***
	***	@listen to a new user creation in backend
	***/
	add_action( 'user_register', 'um_new_user_via_wpadmin', 10, 1 );
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