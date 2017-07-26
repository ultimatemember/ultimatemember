<?php
	/**
	 * Global Access Settings
	 */
	add_action('um_access_global_settings','um_access_global_settings');
	function um_access_global_settings() {
		global $post;

		$access = um_get_option('accessible');

		if ( $access == 2 && ! is_user_logged_in() ) {

			$redirect = um_get_option( 'access_redirect' );
			if ( ! $redirect )
				$redirect = um_get_core_page('login');

			$redirects[] = untrailingslashit( um_get_core_page('login') );
			$redirects[] = untrailingslashit( um_get_option( 'access_redirect' ) );

			$exclude_uris = um_get_option( 'access_exclude_uris' );
			if ( $exclude_uris )
				$redirects = array_merge( $redirects, $exclude_uris );

			$redirects = array_unique( $redirects );

			$current_url = UM()->permalinks()->get_current_url( get_option( 'permalink_structure' ) );
			$current_url = untrailingslashit( $current_url );
			$current_url_slash = trailingslashit( $current_url );

			if ( isset( $post->ID ) && ( in_array( $current_url, $redirects ) || in_array( $current_url_slash, $redirects ) ) ) {
				// allow
			}else {
				UM()->access()->redirect_handler = UM()->access()->set_referer( $redirect, "global" );
			}

			// Disallow access in homepage
			if( /*is_front_page() ||*/ is_home() ){
				$home_page_accessible = um_get_option( "home_page_accessible" );
				if ( $home_page_accessible == 0 ) {
					UM()->access()->redirect_handler = UM()->access()->set_referer( $redirect, "global" );

					wp_redirect( UM()->access()->redirect_handler ); exit;
				}
				
			}

			// Disallow access in category pages
			if ( is_category() ){
				$category_page_accessible = um_get_option("category_page_accessible");
				if ( $category_page_accessible == 0 ) {
					UM()->access()->redirect_handler = UM()->access()->set_referer( $redirect, "global" );
					wp_redirect( UM()->access()->redirect_handler ); exit;
				} else {
                    UM()->access()->allow_access = true;
                }
			}
		}


		$current_page_type = um_get_current_page_type();
			
		do_action("um_access_post_type",$current_page_type);
		do_action("um_access_post_type_{$current_page_type}");
	}

	/**
	 * Custom User homepage redirection
	 */
	add_action( "um_access_user_custom_homepage", "um_access_user_custom_homepage" );
	function um_access_user_custom_homepage() {
		if( ! is_user_logged_in() ) return;
		if ( ! is_home() ) return;

		$role_meta = UM()->roles()->role_data( um_user( 'role' ) );
		
		if ( empty( $role_meta['default_homepage'] ) ) {

            $redirect_to = ! empty( $role_meta['redirect_homepage'] ) ? $role_meta['redirect_homepage'] : um_get_core_page( 'user' );

            $redirect_to = UM()->access()->set_referer( $redirect_to, "custom_homepage" );

            wp_redirect( $redirect_to );
            exit;

		}
	}

	/**
	 * Front page access settings
	 */
	add_action('um_access_frontpage_per_role','um_access_frontpage_per_role');
	function um_access_frontpage_per_role() {
		global $post;

		if ( is_admin() ) return;
		/*if ( ! is_front_page()  ) return;*/
		if(  is_404() ) return;
		
		if ( ! isset( $um_post_id ) && isset( $post->ID ) ){
			$um_post_id = $post->ID;
		}

		if( ! isset( $um_post_id ) ){
			return;
		}

		$args = UM()->access()->get_meta( $um_post_id );
		extract( $args );

		if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {

			$um_post_id = apply_filters('um_access_control_for_parent_posts', $um_post_id );

			$args = UM()->access()->get_meta( $um_post_id );
			extract( $args );

			if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {
				return;
			}

		}

		$redirect_to = null;

		if ( !isset( $accessible ) ) return;

		switch( $accessible ) {

			case 0:
				UM()->access()->allow_access = true;
				UM()->access()->redirect_handler = false; // open to everyone

				break;

			case 1:

				$redirect_to = $access_redirect2;
					
				if ( is_user_logged_in() ){
					UM()->access()->allow_access = false;
				}

				if ( ! is_user_logged_in()  ){
					UM()->access()->allow_access = true;
				}

				if( ! empty( $redirect_to  ) ){
					$redirect_to = UM()->access()->set_referer( $redirect_to, "frontpage_per_role_1a" );
					UM()->access()->redirect_handler = esc_url( $redirect_to );
				}else{
					if ( ! is_user_logged_in() ){
						$redirect_to = um_get_core_page("login");
					}else{
						$redirect_to = um_get_core_page("user");
					}

					$redirect_to = UM()->access()->set_referer( $redirect_to, "frontpage_per_role_1b" );
					UM()->access()->redirect_handler = esc_url( $redirect_to );
				}


				break;

			case 2:

				if ( ! is_user_logged_in() ){

					if ( empty( $access_redirect ) ) {
						$access_redirect = um_get_core_page('login');
					}
					
					$redirect_to = $access_redirect;
					$redirect_to = UM()->access()->set_referer( $redirect_to, "frontpage_per_role_2a" );
				
				}

				if ( is_user_logged_in() && isset( $access_roles ) && !empty( $access_roles ) ){
					$access_roles = unserialize( $access_roles );
					$access_roles = array_filter($access_roles);

					if ( !empty( $access_roles ) && !in_array( um_user('role'), $access_roles ) ) {
						if ( empty( $access_redirect ) ) {
							if ( is_user_logged_in() ) {
								$access_redirect = site_url();
							} else {
								$access_redirect = um_get_core_page('login');
							}
						}
						$redirect_to = esc_url( $access_redirect );
						$redirect_to = UM()->access()->set_referer( $redirect_to, "frontpage_per_role_2b" );
				
					}
				}

					
				UM()->access()->redirect_handler = esc_url( $redirect_to );
				
				break;

		}

	}

	/**
	 * Posts page access settings
	 */
	add_action('um_access_homepage_per_role','um_access_homepage_per_role');
	function um_access_homepage_per_role() {
		global $post;

		if ( is_admin() ) return;
		if ( ! is_home() ) return;
		if ( is_404() ) return;
		
		$access = um_get_option('accessible');

		$show_on_front = get_option( 'show_on_front' );

		if( $show_on_front == "page" ){

			$um_post_id = get_option( 'page_for_posts' );
			
			if ( $access == 2 && ! is_user_logged_in() ) {
				UM()->access()->allow_access = false;
			}else{
				UM()->access()->allow_access = true;
			}
		
		}else if( $show_on_front == "posts" ){
            UM()->access()->allow_access = true;
		}



		if ( isset( $um_post_id ) ){
		
			$args = UM()->access()->get_meta( $um_post_id );
			extract( $args );

			if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {

				$um_post_id = apply_filters('um_access_control_for_parent_posts', $um_post_id );

				$args = UM()->access()->get_meta( $um_post_id );
				extract( $args );

				if ( !isset( $args['custom_access_settings'] ) || $args['custom_access_settings'] == 0 ) {
					return;
				}

			}

			$redirect_to = null;

			if ( !isset( $accessible ) ) return;

			switch( $accessible ) {

				case 0:
					UM()->access()->allow_access = true;
					UM()->access()->redirect_handler = false; // open to everyone

					break;

				case 1:

					$redirect_to = esc_url( $access_redirect2 );
						
					if ( is_user_logged_in() ){
						UM()->access()->allow_access = false;
					}

					if ( ! is_user_logged_in()  ){
						UM()->access()->allow_access = true;
					}

					if( ! empty( $redirect_to  ) ){
						$redirect_to = UM()->access()->set_referer( $redirect_to, "homepage_per_role_1a" );
						UM()->access()->redirect_handler = esc_url( $redirect_to );
					}else{
						$redirect_to = null;
						if ( ! is_user_logged_in() ){
							$redirect_to = um_get_core_page("login");
						}else{
							$redirect_to = um_get_core_page("user");
						}
						$redirect_to = UM()->access()->set_referer( $redirect_to, "homepage_per_role_1b" );
						UM()->access()->redirect_handler = esc_url( $redirect_to );
					}


					break;

				case 2:

					if ( ! is_user_logged_in() ){

						if ( empty( $access_redirect ) ) {
							$access_redirect = um_get_core_page('login');
						}
						
						$redirect_to = $access_redirect;
						$redirect_to = UM()->access()->set_referer( $redirect_to, "homepage_per_role_2a" );
					}

					if ( is_user_logged_in() && isset( $access_roles ) && !empty( $access_roles ) ){
						$access_roles = unserialize( $access_roles );
						$access_roles = array_filter($access_roles);

						if ( !empty( $access_roles ) && !in_array( um_user('role'), $access_roles ) ) {
							if ( !$access_redirect ) {
								if ( is_user_logged_in() ) {
									$access_redirect = site_url();
								} else {
									$access_redirect = um_get_core_page('login');
								}
							}

							$redirect_to = $access_redirect;
							$redirect_to = UM()->access()->set_referer( $redirect_to, "homepage_per_role_2b" );
					
						}
					}
					UM()->access()->redirect_handler = esc_url( $redirect_to );
					
					break;

			}
		}
	}


	/**
	 * Profile Access
	 */
	add_action( 'um_access_profile', 'um_access_profile' );
	function um_access_profile( $user_id ) {

		if ( ! um_is_myprofile() && um_is_core_page( 'user' ) && ! current_user_can( 'edit_users' ) ) {
			
			um_fetch_user( $user_id );

			if ( ! in_array( um_user( 'account_status' ), array( 'approved' ) ) ) {
				um_redirect_home();
			}

			um_reset_user();
			
		}
	}