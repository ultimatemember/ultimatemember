<?php

class UM_Access {

	function __construct() {
	
		$this->redirect_handler = false;
		$this->allow_access = false;

		add_action('template_redirect',  array(&$this, 'template_redirect'), 1000 );
		
	}
	
	/***
	***	@do actions based on priority
	***/
	function template_redirect() {
		global $post, $ultimatemember;

		do_action('um_access_homepage_per_role');
		
		do_action('um_access_global_settings');
		
		do_action('um_access_category_settings');
		
		do_action('um_access_post_settings');
		
		if ( $this->redirect_handler && !$this->allow_access &&  ! um_is_core_page('login') ) {
			
			// login page add protected page automatically

			if ( strstr( $this->redirect_handler, um_get_core_page('login') ) ){
				$curr = $ultimatemember->permalinks->get_current_url();
				$this->redirect_handler = add_query_arg('redirect_to', urlencode_deep($curr), $this->redirect_handler);
				$this->redirect_handler = esc_url( $this->redirect_handler );
			}
			
			wp_redirect( $this->redirect_handler );
		
		}
		
	}
	
	/***
	***	@get meta
	***/
	function get_meta( $post_id ) {
		global $post;
		$meta = get_post_custom( $post_id );
		if ( isset( $meta ) && is_array( $meta ) ) {
			foreach ($meta as $k => $v){
				if ( strstr($k, '_um_') ) {
					$k = str_replace('_um_', '', $k);
					$array[$k] = $v[0];
				}
			}
		}
		if ( isset( $array ) )
			return (array)$array;
		else
			return array('');
	}

}