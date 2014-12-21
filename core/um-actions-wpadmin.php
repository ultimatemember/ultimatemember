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
	
	/***
	***	@show admin bar menu for plugin
	***/
	add_action('admin_bar_menu', 'um_plugin_admin_bar', 99999999 );
	function um_plugin_admin_bar() {
	
		global $wp_admin_bar;

		if( !is_super_admin() || !is_admin_bar_showing() || is_admin() ) return;

		$args = array(
			'id'    	=> 'um_parent',
			'title' 	=> '<span class="ab-icon"></span><span class="ab-label">Ultimate Member</span>',
			'href'  	=> admin_url('admin.php?page=ultimatemember-about'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );

		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub1',
			'title' 	=> 'About this plugin',
			'href'  	=> admin_url('admin.php?page=ultimatemember-about'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub2',
			'title' 	=> 'Getting Started',
			'href'  	=> admin_url('admin.php?page=ultimatemember-start'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub3',
			'title' 	=> 'Dashboard',
			'href'  	=> admin_url('admin.php?page=ultimatemember'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub4',
			'title' 	=> 'Settings',
			'href'  	=> admin_url('admin.php?page=um_options'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub5',
			'title' 	=> 'Forms',
			'href'  	=> admin_url('edit.php?post_type=um_form'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub6',
			'title' 	=> 'Member Levels',
			'href'  	=> admin_url('edit.php?post_type=um_role'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub7',
			'title' 	=> 'Member Directories',
			'href'  	=> admin_url('edit.php?post_type=um_directory'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
		$args = array(
			'parent'	=> 'um_parent',
			'id'    	=> 'um_sub8',
			'title' 	=> 'Members',
			'href'  	=> admin_url('users.php'),
			'meta'  	=> array( )
		);
		
		$wp_admin_bar->add_node( $args );
		
	}