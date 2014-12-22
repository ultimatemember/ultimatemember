<?php

class UM_Access {

	function __construct() {
	
		// hook into template redirects [home]
		add_action('template_redirect',  array(&$this, 'custom_homepage_per_role'), 1000);

		// hook into template redirects
		add_action('template_redirect',  array(&$this, 'post_page_access_control'), 999);
		
	}
	
	/***
	***	@get meta
	***/
	function get_meta() {
		global $post;
		$post_id = $post->ID;
		$meta = get_post_custom( $post_id );
		foreach ($meta as $k => $v){
			if ( strstr($k, '_um_') ) {
				$k = str_replace('_um_', '', $k);
				$array[$k] = $v[0];
			}
		}
		if ( isset( $array ) )
			return (array)$array;
		else
			return array('');
	}
	
	/***
	***	@custom homepage per role
	***/
	function custom_homepage_per_role(){
	
		if ( !is_user_logged_in() ) return;
		if ( is_admin() ) return;
		if ( um_user('default_homepage') ) return;
		if ( !um_user('redirect_homepage') ) return;
		
		if( is_home() || is_front_page() )
			exit( wp_redirect( um_user('redirect_homepage') ) );
		
	}
	
	/***
	***	@the main restrict function
	***/
	function post_page_access_control(){
		global $post;
		
		if ( !get_post_type() || !isset($post->ID) ) return;
		
		$args = $this->get_meta();
		extract($args);

		$redirect_to = null;

		if ( !isset( $accessible ) ) return;
		
		switch( $accessible ) {
			
			case 0:
				break;
			
			case 1:
			
				if ( is_user_logged_in() )
					$redirect_to = $access_redirect2;
				break;
				
			case 2:
				
				if ( !is_user_logged_in() )
					$redirect_to = $access_redirect;
					
				if ( isset( $access_roles ) && !empty( $access_roles ) )
					if ( !in_array( um_user('role'), unserialize( $access_roles ) ) )
						$redirect_to = $access_redirect;
						
				break;
				
		}
		
		if ( $redirect_to ) {
			wp_redirect( $redirect_to );
			exit;
		}
		
	}

}