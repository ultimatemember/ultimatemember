<?php

	/***
	***	@Override avatars with a high priority
	***/
	function um_get_avatar($content, $id='', $size = '96', $avatar_class = '', $default = '', $alt = '') {

		if ( is_email( $id ) )
			$id = email_exists( $id );
		
		um_fetch_user( $id );
		$avatar = um_user('profile_photo', $size);
		um_reset_user();
		return $avatar;
	
	}

	add_filter('get_avatar', 'um_get_avatar', 10000000, 5); 