<?php

	/***
	***	@Override avatars with a high priority
	***/
	function um_get_avatar($content, $id='', $size = '96', $avatar_class = '', $default = '', $alt = '') {
		
		um_fetch_user( $id );
		return um_user('profile_photo', $size);
	
	}

	add_filter('get_avatar', 'um_get_avatar', 10000000, 5); 