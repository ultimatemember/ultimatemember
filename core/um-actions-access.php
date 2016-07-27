<?php


	/***
	***	@Global Access Settings
	***/
	add_action('um_access_global_settings','um_access_global_settings');
	function um_access_global_settings() {
		global $post, $ultimatemember;

		$access = um_get_option('accessible');

		if ( $access == 2 && ! is_user_logged_in() ) {

			$redirect = um_get_option('access_redirect');
			if ( !$redirect ){
				$redirect = um_get_core_page('login');
			}

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

			if ( ( isset( $post->ID )  ) && ( in_array( $current_url, $redirects ) || in_array( $current_url_slash, $redirects ) ) ) {
				// allow
			}else {
				$ultimatemember->access->redirect_handler = $redirect;
			}



		}


		$current_page_type = um_get_current_page_type();
			
		do_action("um_access_post_type",$current_page_type);
		do_action("um_access_post_type_{$current_page_type}");


	}

	/***
	***	@Front page access settings
	***/
	add_action('um_access_frontpage_per_role','um_access_frontpage_per_role');
	function um_access_frontpage_per_role() {
		global $ultimatemember, $post;

		if ( is_admin() ) return;
		if ( ! is_front_page()  ) return;
		
		if ( ! isset( $um_post_id ) && isset( $post->ID ) ){
			$um_post_id = $post->ID;
		}

		if( ! isset( $um_post_id ) ){
			return;
		}

		$args = $ultimatemember->access->get_meta( $um_post_id );
		extract( $args );

		if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {

			$um_post_id = apply_filters('um_access_control_for_parent_posts', $um_post_id );

			$args = $ultimatemember->access->get_meta( $um_post_id );
			extract( $args );

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

				$redirect_to = esc_url( $access_redirect2 );
					
				if ( is_user_logged_in() ){
					$ultimatemember->access->allow_access = false;
				}

				if ( ! is_user_logged_in()  ){
					$ultimatemember->access->allow_access = true;
				}

				if( ! empty( $redirect_to  ) ){
					$ultimatemember->access->redirect_handler = esc_url( $redirect_to );
				}else{
					if ( ! is_user_logged_in() ){
						$ultimatemember->access->redirect_handler = um_get_core_page("login");
					}else{
						$ultimatemember->access->redirect_handler = um_get_core_page("user");
					}
				}


				break;

			case 2:

				if ( ! is_user_logged_in() ){

					if ( empty( $access_redirect ) ) {
						$access_redirect = um_get_core_page('login');
					}
					
					$redirect_to = esc_url( $access_redirect );
				}

				if ( is_user_logged_in() && isset( $access_roles ) && !empty( $access_roles ) ){
					$access_roles = unserialize( $access_roles );
					$access_roles = array_filter($access_roles);

					if ( !empty( $access_roles ) && !in_array( um_user('role'), $access_roles ) ) {
						if ( !$access_redirect ) {
							if ( is_user_logged_in() ) {
								$access_redirect = esc_url( site_url() );
							} else {
								$access_redirect = esc_url( um_get_core_page('login') );
							}
						}
						$redirect_to = esc_url( $access_redirect );
					}
				}
				
				$ultimatemember->access->redirect_handler = esc_url( $redirect_to );
				
				break;

		}

	}

	/***
	***	@Posts page access settings
	***/
	add_action('um_access_homepage_per_role','um_access_homepage_per_role');
	function um_access_homepage_per_role() {
		global $ultimatemember, $post;

		if ( is_admin() ) return;
		if ( ! is_home()  ) return;
		
		$access = um_get_option('accessible');

		$show_on_front = get_option( 'show_on_front' );

		if( $show_on_front == "page" ){

			$um_post_id = get_option( 'page_for_posts' );
			
			if ( $access == 2 && ! is_user_logged_in() ) {
				$ultimatemember->access->allow_access = false;
			}else{
				$ultimatemember->access->allow_access = true;
			}
		
		}else if( $show_on_front == "posts" ){
				$ultimatemember->access->allow_access = true;
		}



		if ( isset( $um_post_id ) ){
		
			$args = $ultimatemember->access->get_meta( $um_post_id );
			extract( $args );

			if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {

				$um_post_id = apply_filters('um_access_control_for_parent_posts', $um_post_id );

				$args = $ultimatemember->access->get_meta( $um_post_id );
				extract( $args );

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

					$redirect_to = esc_url( $access_redirect2 );
						
					if ( is_user_logged_in() ){
						$ultimatemember->access->allow_access = false;
					}

					if ( ! is_user_logged_in()  ){
						$ultimatemember->access->allow_access = true;
					}

					if( ! empty( $redirect_to  ) ){
						$ultimatemember->access->redirect_handler = esc_url( $redirect_to );
					}else{
						if ( ! is_user_logged_in() ){
							$ultimatemember->access->redirect_handler = um_get_core_page("login");
						}else{
							$ultimatemember->access->redirect_handler = um_get_core_page("user");
						}
					}


					break;

				case 2:

					if ( ! is_user_logged_in() ){

						if ( empty( $access_redirect ) ) {
							$access_redirect = um_get_core_page('login');
						}
						
						$redirect_to = esc_url( $access_redirect );
					}

					if ( is_user_logged_in() && isset( $access_roles ) && !empty( $access_roles ) ){
						$access_roles = unserialize( $access_roles );
						$access_roles = array_filter($access_roles);

						if ( !empty( $access_roles ) && !in_array( um_user('role'), $access_roles ) ) {
							if ( !$access_redirect ) {
								if ( is_user_logged_in() ) {
									$access_redirect = esc_url( site_url() );
								} else {
									$access_redirect = esc_url( um_get_core_page('login') );
								}
							}
							$redirect_to = esc_url( $access_redirect );
						}
					}
					
					$ultimatemember->access->redirect_handler = esc_url( $redirect_to );
					
					break;

			}
		}


	}


	/***
	***	@Archieves/Taxonomies/Categories access settings
	***/
	add_action('um_access_category_settings','um_access_category_settings');
	function um_access_category_settings() {
		global $post, $wp_query, $ultimatemember;

		if ( is_front_page() || 
				   is_home() || 
				   is_feed() || 
				   is_page() 
		) {
			
			return;

		}

		$access = um_get_option('accessible');
		$current_page_type = um_get_current_page_type();
		

		if( is_category()  && ! in_array( $current_page_type , array( 'day','month','year','author','archive' ) ) ){
			
			$um_category = get_the_category(); 
			$um_category = current( $um_category );
			$term_id = $um_category->term_id;
			
			if( isset( $term_id ) ){
				
				$opt = get_option("category_$term_id");
				
				if ( isset( $opt['_um_accessible'] ) ) {

					$redirect = false;
						
					switch( $opt['_um_accessible'] ) {

						case 0:
							
							$ultimatemember->access->allow_access = true;
							$ultimatemember->access->redirect_handler = ''; // open to everyone
							
							break;

						case 1:

							if ( is_user_logged_in() ){

								if( isset( $opt['_um_redirect'] ) ) {
									$redirect = esc_url( $opt['_um_redirect'] );
								}else{  
									$redirect = site_url();
								}
							}
							$ultimatemember->access->allow_access = false;
							$ultimatemember->access->redirect_handler = $redirect;
							
							if ( ! is_user_logged_in() && ! empty( $redirect ) ){
								$ultimatemember->access->allow_access = true;
							}

							break;

						case 2:

							if ( ! is_user_logged_in() ){

								if( isset( $opt['_um_redirect'] ) && ! empty( $opt['_um_redirect']  ) ){
									$redirect = esc_url( $opt['_um_redirect'] );
								}else{
								 	$redirect = um_get_core_page('login');
								}
								$ultimatemember->access->allow_access = false;
								$ultimatemember->access->redirect_handler = $redirect;
							}

							if ( is_user_logged_in() && isset( $opt['_um_roles'] ) && !empty( $opt['_um_roles'] ) ){
								if ( ! in_array( um_user('role'), $opt['_um_roles'] ) ) {

								
										if( isset( $opt['_um_redirect'] ) ){
											$redirect = esc_url( $opt['_um_redirect'] );
										}
									
										$ultimatemember->access->redirect_handler = $redirect;
								
								}
							}

					}
				}
			}

		} else if ( $access == 2 && ! is_user_logged_in() &&  is_archive() ) {

			$ultimatemember->access->allow_access =  false;
			$redirect = um_get_core_page('login');
			$ultimatemember->access->redirect_handler = $redirect;
		
		} else if ( is_tax() && get_post_taxonomies( $post ) ) {

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

			}
		}

	}

	/***
	***	@Posts/Page access settings
	***/
	add_action('um_access_post_settings','um_access_post_settings');
	function um_access_post_settings() {
		global $post, $ultimatemember;

		// woo commerce shop ID
		if( function_exists('is_shop') && is_shop() ) {

			$um_post_id = get_option('woocommerce_shop_page_id');

		} else if (  
				is_category() 	|| 
				is_archive() 	|| 
				is_search() 	|| 
				in_the_loop()  	|| 
				is_feed() 		|| 
				is_tax() 		||
				! get_post_type() ||
				! isset( $post->ID ) ||
				is_home()		||
				is_front_page()
		) {
			
			return;

		} 

		if ( !isset( $um_post_id ) ){
			$um_post_id = $post->ID;
		}

		$args = $ultimatemember->access->get_meta( $um_post_id );
		extract( $args );

		$categories = get_the_category( $post->ID );
   		// Check post category restriction
   		foreach( $categories as $cat ){

   				$opt = get_option("category_{$cat->term_id}");

				if ( isset( $opt['_um_accessible'] )  ) {
					switch( $opt['_um_accessible'] ) {

						case 0: // Open to everyone
							$ultimatemember->access->allow_access = true;
							$ultimatemember->access->redirect_handler = false; // open to everyone
							break;

						case 1: // Logged out users only
							
							if ( is_user_logged_in() )
								$ultimatemember->access->redirect_handler = ( isset( $opt['_um_redirect'] ) ) ? $opt['_um_redirect'] : site_url();

							if ( !is_user_logged_in() )
								$ultimatemember->access->allow_access = true;

							break;

						case 2: // Logged in users only

							if ( ! is_user_logged_in() ){
								$ultimatemember->access->redirect_handler = ( isset( $opt['_um_redirect'] ) && ! empty( $opt['_um_redirect']  ) ) ? $opt['_um_redirect'] : um_get_core_page('login');
							    $ultimatemember->access->allow_access = false;
							}

							if ( is_user_logged_in() ){
								
								if(  isset( $opt['_um_roles'] ) && !empty( $opt['_um_roles'] ) ){

									if (  in_array( um_user('role'), $opt['_um_roles'] ) ) {

										 $ultimatemember->access->allow_access = true;
								
									}else{

										$ultimatemember->access->redirect_handler = ( isset( $opt['_um_redirect'] ) && ! empty( $opt['_um_redirect'] ) ) ? $opt['_um_redirect'] : site_url();
										$ultimatemember->access->allow_access = false;
								
									}

								}else{ // if allowed all roles
									 $ultimatemember->access->allow_access = true;
								}

							}
						
					}


				} // end if isset( $opt['_um_accessible'] )

				// if one of the categories has enabled restriction, apply its settings to the current post
				if( $ultimatemember->access->allow_access == false ){
					return;
				}

		} // end foreach
   		
		if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {

			$um_post_id = apply_filters('um_access_control_for_parent_posts', $um_post_id );

			$args = $ultimatemember->access->get_meta( $um_post_id );
			extract( $args );

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

				$redirect_to = esc_url( $access_redirect2 );
					
				if ( is_user_logged_in() ){
					$ultimatemember->access->allow_access = false;
				}

				if ( ! is_user_logged_in()  ){
					$ultimatemember->access->allow_access = true;
				}

				if( ! empty( $redirect_to  ) ){
					$ultimatemember->access->redirect_handler = esc_url( $redirect_to );
				}else{
					if ( ! is_user_logged_in() ){
						$ultimatemember->access->redirect_handler = um_get_core_page("login");
					}else{
						$ultimatemember->access->redirect_handler = um_get_core_page("user");
					}
				}


				break;

			case 2:

				if ( ! is_user_logged_in() ){

					if ( empty( $access_redirect ) ) {
						$access_redirect = um_get_core_page('login');
					}
					
					$redirect_to = esc_url( $access_redirect );
				}

				if ( is_user_logged_in() && isset( $access_roles ) && !empty( $access_roles ) ){
					$access_roles = unserialize( $access_roles );
					$access_roles = array_filter($access_roles);

					if ( !empty( $access_roles ) && !in_array( um_user('role'), $access_roles ) ) {
						if ( !$access_redirect ) {
							if ( is_user_logged_in() ) {
								$access_redirect = esc_url( site_url() );
							} else {
								$access_redirect = esc_url( um_get_core_page('login') );
							}
						}
						$redirect_to = esc_url( $access_redirect );
					}
				}
				
				$ultimatemember->access->redirect_handler = esc_url( $redirect_to );
				
				break;

		}

		if( um_is_core_page('user') && ! is_user_logged_in() ){
		  		$ultimatemember->access->allow_access = false;
				$ultimatemember->access->redirect_handler = esc_url( $access_redirect );
				wp_redirect( $ultimatemember->access->redirect_handler );
				exit;
		}

	}

	/***
	*** @Profile Access
	***/
	add_action('um_access_profile','um_access_profile');
	function um_access_profile( $user_id ){

		if( ! um_is_myprofile() && um_is_core_page('user') && ! current_user_can('edit_users') ){
			
			um_fetch_user( $user_id );
			
			if( ! in_array( um_user('account_status'), array('approved') ) ){
				um_redirect_home();
			}

			um_reset_user();
			
		}

	}
