<?php

	/***
	***	@Get a random string
	***/
	function um_random_string_( $length = 10 ) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $result;
	}
	
	/***
	***	@Get all UM roles in array
	***/
	function um_get_roles_( $add_default = false ) {
		global $ultimatemember;
		$roles = array();
		
		if ($add_default) $roles[0] = $add_default;
		
		$args = array(
			'post_type' => 'um_role',
			'posts_per_page' => -1,
			'post_status' => array('publish'),
		);
		$results = new WP_Query($args);
		if ($results->posts){
			foreach($results->posts as $post) { setup_postdata($post);
			
				if ( $ultimatemember->query->is_core( $post->ID ) ){
					$roles[ $ultimatemember->query->is_core( $post->ID ) ] = $post->post_title;
				} else {
					$roles[ $post->post_name ] = $post->post_title;
				}
				
			}
		} else {
		
			$roles['member'] = 'Member';
			$roles['admin'] = 'Admin';
			
		}
	
		return $roles;
	}