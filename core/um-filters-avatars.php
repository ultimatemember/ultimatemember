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

		if ( ! $avatar && um_get_option('use_gravatars') ) {
			
			$default = get_option( 'avatar_default', 'mystery' );
			if ( $default == 'gravatar_default' ) {
				$default = '';
			}
			
			$rating = get_option('avatar_rating');
			if ( !empty( $rating ) ) {
				$rating = "&amp;r={$rating}";
			}
			
			if( um_get_option('use_gravatars') && ! um_user('synced_profile_photo') && ! $has_profile_photo ){
						$avatar_url  = um_get_domain_protocol().'gravatar.com/avatar/'.um_user('synced_gravatar_hashed_id');
						$avatar_url = add_query_arg('s',400, $avatar_url);
						$gravatar_type = um_get_option('use_um_gravatar_default_builtin_image');
						
						if( $gravatar_type == 'default' ){
							if( um_get_option('use_um_gravatar_default_image') ){
								$avatar_url = add_query_arg('d', um_get_default_avatar_uri(), $avatar_url  );
							}
						}else{
								$avatar_url = add_query_arg('d', $gravatar_type, $avatar_url  );
						}
						
			}
			
			$avatar = '<img src="' .$avatar_url .'?d='. $default . '&amp;s=' . $size . $rating .'" class="func-um_get_avatar gravatar avatar avatar-'.$size.' um-avatar" width="'.$size.'" height="'.$size.'" alt="" />';
			
		}else if( empty( $avatar ) ){
			$default_avatar_uri = um_get_default_avatar_uri();

			$avatar = '<img src="' .$default_avatar_uri  .'" class="gravatar avatar avatar-'.$size.' um-avatar" width="'.$size.'" height="'.$size.'" alt="" />';
		}

		return $avatar;
	
	}

