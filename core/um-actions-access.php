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
		
		if( is_front_page() ) {
			$ultimatemember->access->redirect_handler = um_user('redirect_homepage');
		}
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
			if ( !$redirect ) 
				$redirect = um_get_core_page('login');
			
			$redirects[] = untrailingslashit( um_get_core_page('login') );
			$redirects[] = untrailingslashit( um_get_option('access_redirect') );

			$exclude_uris = um_get_option('access_exclude_uris');
			
			if ( $exclude_uris ) {
				$redirects = array_merge( $redirects, $exclude_uris );
			}

			$redirects = array_unique( $redirects );
			
			$current_url = $ultimatemember->permalinks->get_current_url( get_option('permalink_structure') );
			$current_url = untrailingslashit( $current_url );
			$current_url_slash = trailingslashit( $current_url );
			
			if ( ( isset( $post->ID ) || is_home() ) && ( in_array( $current_url, $redirects ) || in_array( $current_url_slash, $redirects ) ) ) {
				// allow
			} else {
				$ultimatemember->access->redirect_handler = $redirect;
			}
			
		}

	}
	
	/***
	***	@
	***/
	add_action('um_access_category_settings','um_access_category_settings');
	function um_access_category_settings() {
		global $post, $wp_query, $ultimatemember;

		if( is_front_page() || is_home() ){
			return;
		}

		if ( is_single() || get_post_taxonomies( $post ) ) {
		

			$taxonomies = get_post_taxonomies( $post );
			$categories_ids = array();
			
			foreach ($taxonomies as $key => $value) {
				$term_list = wp_get_post_terms($post->ID, $value, array("fields" => "ids"));
				foreach( $term_list  as $term_id ){
					array_push( $categories_ids , $term_id);
				}
			}


			foreach( $categories_ids as $term => $term_id ) {
				
				$opt = get_option("category_$term_id");
				
				if ( isset( $opt['_um_accessible'] ) ) {
					switch( $opt['_um_accessible'] ) {
						
						case 0:	
							$ultimatemember->access->allow_access = true;
							$ultimatemember->access->redirect_handler = false; // open to everyone
							break;
				
						case 1:
							
							if ( is_user_logged_in() )
								$ultimatemember->access->redirect_handler = ( isset( $opt['_um_redirect'] ) ) ? $opt['_um_redirect'] : site_url();
							
							if ( !is_user_logged_in() )
								$ultimatemember->access->allow_access = true;
							
							break;
							
						case 2:
						
							if ( ! is_user_logged_in() )
								$ultimatemember->access->redirect_handler = ( isset( $opt['_um_redirect'] ) && ! empty( $opt['_um_redirect']  ) ) ? $opt['_um_redirect'] : um_get_core_page('login');
							
							if ( is_user_logged_in() && isset( $opt['_um_roles'] ) && !empty( $opt['_um_roles'] ) ){
								if ( !in_array( um_user('role'), $opt['_um_roles'] ) ) {
									
									if ( is_user_logged_in() )
										$ultimatemember->access->redirect_handler = ( isset( $opt['_um_redirect'] ) ) ? $opt['_um_redirect'] : site_url();
									
									if ( !is_user_logged_in() )
										$ultimatemember->access->redirect_handler =  um_get_core_page('login');
								}
							}
							
					}
				}

				if( is_archive() ){
					$ultimatemember->access->allow_access = true;
					$ultimatemember->access->redirect_handler = false; // open to everyone
				}
			}
		}
	}
	
	/***
	***	@
	***/
	add_action('um_access_post_settings','um_access_post_settings');
	function um_access_post_settings() {
		global $post, $ultimatemember;

		// woo commerce shop ID
		if( function_exists('is_shop') && is_shop() ) {
			
			$post_id = get_option('woocommerce_shop_page_id');
		
		} else if ( is_archive() || is_front_page() || is_home() || is_search() || in_the_loop() ) {
			
			return;

		} else {
		
			if ( !get_post_type() || !isset($post->ID) ) return;

		}

		

		if ( !isset( $post_id ) )
			$post_id = $post->ID;

		$args = $ultimatemember->access->get_meta( $post_id );
		
		extract($args);

		if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {
			
			$post_id = apply_filters('um_access_control_for_parent_posts', $post_id );
			
			$args = $ultimatemember->access->get_meta( $post_id );
			extract($args);

			if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {
				return;
			}
			
		}

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
					if ( !$access_redirect ) $access_redirect = um_get_core_page('login');
					$redirect_to = $access_redirect;
				}

				if ( is_user_logged_in() && isset( $access_roles ) && !empty( $access_roles ) ){
					if ( !in_array( um_user('role'), unserialize( $access_roles ) ) ) {
						if ( !$access_redirect ) {
							if ( is_user_logged_in() ) {
								$access_redirect = site_url();
							} else {
								$access_redirect = um_get_core_page('login');
							}
						}
						$redirect_to = $access_redirect;
					}
				}
				
				break;
				
		}
		
		if ( $redirect_to ) {
			if ( is_feed() ) {

			} else {
				$ultimatemember->access->allow_access = false;
				$ultimatemember->access->redirect_handler = $redirect_to;
			}
		}

	}