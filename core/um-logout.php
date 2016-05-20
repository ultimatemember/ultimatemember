<?php

class UM_Logout {

	function __construct() {
		
		add_action('template_redirect', array(&$this, 'logout_page'), 10000 );
		
	}
	
	/***
	***	@Logout via logout page
	***/
	function logout_page() {
	
		if ( um_is_core_page('logout') ) {
			
			if ( is_user_logged_in() ) {
				
				if ( isset($_REQUEST['redirect_to']) && $_REQUEST['redirect_to'] !== '' ) {
					wp_logout();
					session_unset();
					exit( wp_redirect( $_REQUEST['redirect_to'] ) );
				} else if ( um_user('after_logout') == 'redirect_home' ) {
					wp_logout();
					session_unset();
					um_redirect_home();
				} else {
					wp_logout();
					session_unset();
					exit( wp_redirect( um_user('logout_redirect_url') ) );
				}

			} else {
				um_redirect_home();
			}
			
		}
		
	}

}