<?php

	/***
	***	@
	***/
	add_action('um_access_homepage_per_role','um_access_homepage_per_role');
	function um_access_homepage_per_role() {
		global $ultimatemember;
		
		if ( !is_user_logged_in() ) return;
		if ( is_admin() ) return;
		if ( um_user('default_homepage') ) return;
		if ( !um_user('redirect_homepage') ) return;
		
		if( is_home() || is_front_page() )
			$ultimatemember->access->redirect_handler = um_user('redirect_homepage');
	}
	
	/***
	***	@
	***/
	add_action('um_access_global_settings','um_access_global_settings');
	function um_access_global_settings() {
		global $post, $ultimatemember;
		
		$access = um_get_option('accessible');
		
		if ( $access == 2 && !is_user_logged_in() ) {
		
			$redirect = um_get_option('access_redirect');
			
			$redirects[] = trailingslashit( um_get_core_page('login') );
			$redirects[] = trailingslashit( um_get_option('access_redirect') );
			
			$exclude_uris = um_get_option('access_exclude_uris');
			
			if ( $exclude_uris ) {
				$redirects = array_merge( $redirects, $exclude_uris );
			}
			
			$current_url = trailingslashit( $ultimatemember->permalinks->get_current_url(true) );
		
			if ( isset( $post->ID ) && in_array( $current_url, $redirects ) ) {
			 // allow
			} else {
				$ultimatemember->access->redirect_handler = $redirect;
			}
		}

	}
	
	/***
	***	@
	***/
	add_action('um_access_post_settings','um_access_post_settings');
	function um_access_post_settings() {
		global $post, $ultimatemember;
		
		if ( !get_post_type() || !isset($post->ID) ) return;
		
		$args = $ultimatemember->access->get_meta();
		extract($args);
		
		if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) return;

		$redirect_to = null;

		if ( !isset( $accessible ) ) return;

		switch( $accessible ) {
			
			case 0:	
				$ultimatemember->access->allow_access = true;
				$ultimatemember->access->redirect_handler = false; // open to everyone
				break;
			
			case 1:
			
				if ( is_user_logged_in() )
					$redirect_to = $access_redirect2;
					
				if ( !is_user_logged_in() )
					$ultimatemember->access->allow_access = true;
					
				break;
				
			case 2:
				
				if ( !is_user_logged_in() ){
					if ( !$access_redirect ) $access_redirect = home_url();
					$redirect_to = $access_redirect;
				}
				
				if ( is_user_logged_in() && isset( $access_roles ) && !empty( $access_roles ) ){
					if ( !in_array( um_user('role'), unserialize( $access_roles ) ) ) {
						$redirect_to = $access_redirect;
					}
				}
				
				break;
				
		}
		
		if ( $redirect_to ) {
			$ultimatemember->access->redirect_handler = $redirect_to;
		}
		
	}