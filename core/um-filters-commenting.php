<?php

	/***
	***	@Control comment author display
	***/
	add_filter('get_comment_author_link', 'um_comment_link_to_profile', 10000 );
	function um_comment_link_to_profile( $return ) {
		global $comment, $ultimatemember;
		if ( isset( $comment->user_id ) && !empty( $comment->user_id ) ) {
			
			if ( isset( $ultimatemember->user->cached_user[ $comment->user_id ] ) && $ultimatemember->user->cached_user[ $comment->user_id ] ) {
				
				$return = '<a href="'. $ultimatemember->user->cached_user[$comment->user_id]['url'] . '">' . $ultimatemember->user->cached_user[$comment->user_id]['name'] . '</a>';
			
			} else {
				
				um_fetch_user($comment->user_id);
				$ultimatemember->user->cached_user[ $comment->user_id ] = array('url' => um_user_profile_url(), 'name' => um_user('display_name') );
				$return = '<a href="'. $ultimatemember->user->cached_user[$comment->user_id]['url'] . '">' . $ultimatemember->user->cached_user[$comment->user_id]['name'] . '</a>';
				um_reset_user();
				
			}
			
		}
		return $return;
	}