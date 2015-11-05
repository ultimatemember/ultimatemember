<?php

class UM_Profile {

	function __construct() {
	
		add_action('template_redirect', array(&$this, 'active_tab'), 10002);
		add_action('template_redirect', array(&$this, 'active_subnav'), 10002);
		
	}
	
	/***
	***	@all tab data
	***/
	function tabs(){
		return apply_filters('um_profile_tabs', $tabs = array() );
	}
	
	/***
	***	@tabs that are active
	***/
	function tabs_active(){
		$tabs = $this->tabs();
		foreach( $tabs as $id => $info ) {
			if ( !um_get_option('profile_tab_'.$id) && !isset( $info['_builtin'] ) && !isset( $info['custom'] ) )
				unset( $tabs[$id] );
		}
		return $tabs;
	}
	
	/***
	***	@primary tabs only
	***/
	function tabs_primary(){
		$tabs = $this->tabs();
		foreach( $tabs as $id => $info ){
			if ( isset( $info['name'] ) ) {
				$primary[$id] = $info['name'];
			}
		}
		return $primary;
	}
	
	/***
	***	@Activated tabs in backend
	***/
	function tabs_enabled(){
		$tabs = $this->tabs();
		foreach( $tabs as $id => $info ){
			if ( isset( $info['name'] ) ) {
				if ( um_get_option('profile_tab_'.$id) || isset( $info['_builtin'] ) ) {
					$primary[$id] = $info['name'];
				}
			}
		}
		return ( isset( $primary ) ) ? $primary : '';
	}
	
	/***
	***	@Get active_tab
	***/
	function active_tab() {

		$this->active_tab = um_get_option('profile_menu_default_tab');

		if ( get_query_var('profiletab') ) {
			$this->active_tab = get_query_var('profiletab');
		}
		
		$this->active_tab = apply_filters( 'um_profile_active_tab', $this->active_tab );

		return $this->active_tab;
	}
	
	/***
	***	@Get active active_subnav
	***/
	function active_subnav() {
		
		$this->active_subnav = null;
		
		if ( get_query_var('subnav') ) {
			$this->active_subnav = get_query_var('subnav');
		}
		
		return $this->active_subnav;
	}
	
	/***
	***	@Show meta in profile
	***/
	function show_meta( $array ) {
		global $ultimatemember;
		$output = '';
		
		foreach( $array as $key ) {
			$data = '';
			if ( $key && um_filtered_value( $key ) ) {
				
				if ( isset( $ultimatemember->builtin->all_user_fields[$key]['icon'] ) ) {
					$icon = $ultimatemember->builtin->all_user_fields[$key]['icon'];
				} else {
					$icon = '';
				}
				
				$icon = ( isset( $icon ) && !empty( $icon ) ) ? '<i class="'.$icon.'"></i>' : '';
				
				if ( !um_get_option('profile_show_metaicon') )
					$icon = '';
				
				$value = um_filtered_value( $key );
				
				$items[] = '<span>' . $icon . $value . '</span>';
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