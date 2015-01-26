<?php

	/***
	***	@Do not apply to backend default avatars
	***/
	add_filter('avatar_defaults', 'um_avatar_defaults', 99999 );
    function um_avatar_defaults($avatar_defaults) {
        remove_filter('get_avatar', 'um_get_avatar', 99999, 5);
        return $avatar_defaults;
    }
	
	/***
	***	@Override avatars with a high priority
	***/
	add_filter('get_avatar', 'um_get_avatar', 99999, 5); 
	function um_get_avatar($avatar = '', $id_or_email='', $size = '96', $avatar_class = '', $default = '', $alt = '') {

		if ( is_numeric($id_or_email) )
			$user_id = (int) $id_or_email;
		elseif ( is_string( $id_or_email ) && ( $user = get_user_by( 'email', $id_or_email ) ) )
			$user_id = $user->ID;
		elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) )
			$user_id = (int) $id_or_email->user_id;
		if ( empty( $user_id ) )
			return $avatar;

		um_fetch_user( $user_id );

		$avatar = um_user('profile_photo', $size);

		if ( !um_profile('profile_photo') && um_get_option('use_gravatars') ) {
			if ( is_ssl() ) {
				$protocol = 'https://';
			} else {
				$protocol = 'http://';
			}
			
			$default = get_option( 'avatar_default', 'mystery' );
			if ( $default == 'gravatar_default' ) {
				$default = '';
			}
			
			$avatar = '<img src="' . $protocol . 'gravatar.com/avatar/' . md5( um_user('user_email') ) . 
			'?d='. $default . '&amp;s=' . $size . '" class="gravatar avatar avatar-'.$size.' um-avatar" width="'.$size.'" height="'.$size.'" alt="" />';
			
		}
		
		um_reset_user();
		return $avatar;
	
	}