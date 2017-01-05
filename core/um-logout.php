<?php

class UM_Logout {

	function __construct() {
		
		add_action('template_redirect', array(&$this, 'logout_page'), 10000 );
		
	}
	
	/***
	***	@Logout via logout page
	***/
	function logout_page() {


		$has_translation 	= false;
		$language_code 		= '';

		if ( function_exists('icl_object_id') || function_exists('icl_get_current_language')  ) {

				$logout_page_id = $ultimatemember->permalinks->core['logout'];
				$logout = get_post( $logout_page_id );
				
				if ( isset( $logout->post_name ) ) {

					$logout_slug = $logout->post_name;
					
					if( function_exists('icl_get_current_language') ){
							$language_code = icl_get_current_language();
					}else if( function_exists('icl_object_id') && defined('ICL_LANGUAGE_CODE') ){
							$language_code = ICL_LANGUAGE_CODE;
					}

					// Logout page translated slug
					$lang_post_id = icl_object_id( $logout->ID, 'post', FALSE, $language_code );
					$lang_post_obj = get_post( $lang_post_id );
					if( isset( $lang_post_obj->post_name ) ){
						$has_translation = true;
					}


				}
		}
	
		if ( um_is_core_page('logout') || $has_translation ) {
			
			if ( is_user_logged_in() ) {
				
				if ( isset( $_REQUEST['redirect_to'] ) && $_REQUEST['redirect_to'] !== '' ) {
					wp_logout();
					session_unset();
					exit( wp_redirect( $_REQUEST['redirect_to'] ) );
				} else if ( um_user('after_logout') == 'redirect_home' ) {
					wp_logout();
					session_unset();
					exit( wp_redirect( home_url( $language_code ) ) );
				} else {
					wp_logout();
					session_unset();
					exit( wp_redirect( um_user('logout_redirect_url') ) );
					
				}

			} else {
				exit( wp_redirect( home_url( $language_code ) ) );
			}
			
		}
		
	}

}