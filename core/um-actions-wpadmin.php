<?php

	/***
	***	@checks if user can access the backend
	***/
	function um_block_wpadmin_by_user_role(){
		global $ultimatemember;
		if( is_admin() && !defined('DOING_AJAX') && um_user('ID') && !um_user('can_access_wpadmin')){
			wp_redirect(home_url());
			exit;
		}
	}
	add_action('init','um_block_wpadmin_by_user_role', 99);
	
	/***
	***	@hide admin bar appropriately
	***/
	function um_control_admin_bar(){
		if( !is_admin() && !um_user('can_access_wpadmin')) {
			return false;
		} else {
			return true;
		}
	}
	add_filter( 'show_admin_bar' , 'um_control_admin_bar');