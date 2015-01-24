<?php

class UM_Profile {

	function __construct() {
	
		add_action('template_redirect', array(&$this, 'active_tab'), 10002);

		$this->active_tab = 'main';
		
	}
	
	/***
	***	@Global tabs
	***/
	function tabs(){
		return apply_filters('um_profile_tabs', $tabs = array() );
	}
	
	/***
	***	@Get active tab
	***/
	function active_tab() {
		
		if ( get_query_var('profiletab') ) {
			$this->active_tab = get_query_var('profiletab');
		}
		
		if ( get_query_var('subnav') ) {
			$this->active_subnav = get_query_var('subnav');
		}
		
	}
	
	/***
	***	@Show meta in profile
	***/
	function show_meta( $array ) {
		global $ultimatemember;
		$output = '';
		
		foreach( $array as $key ) {
			$data = '';
			if ( $key && um_user( $key ) ) {
				
				$value = um_filtered_value( $key );
				
				$items[] = '<span>' . $value . '</span>';
				$items[] = '<span class="b">&bull;</span>';
				
			}
		}

		if ( isset( $items ) ) {
			array_pop($items);
			foreach( $items as $item ) {
				$output .= $item;
			}
		}

		return $output;
	}

}