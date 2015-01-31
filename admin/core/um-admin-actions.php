<?php

	/***
	***	@Hide registration notice
	***/
	add_action('um_admin_do_action__um_can_register_notice', 'um_admin_do_action__um_can_register_notice');
	function um_admin_do_action__um_can_register_notice( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		update_option( $action, 1 );
		exit( wp_redirect( remove_query_arg('um_adm_action') ) );
	}
	
	/***
	***	@Hide exif notice
	***/
	add_action('um_admin_do_action__um_show_exif_notice', 'um_admin_do_action__um_show_exif_notice');
	function um_admin_do_action__um_show_exif_notice( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		update_option( $action, 1 );
		exit( wp_redirect( remove_query_arg('um_adm_action') ) );
	}
	
	/***
	***	@Opt-in tracking
	***/
	add_action('um_admin_do_action__opt_into_tracking', 'um_admin_do_action__opt_into_tracking');
	function um_admin_do_action__opt_into_tracking( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		
		global $reduxConfig;
		$reduxConfig->ReduxFramework->set('allow_tracking', 1);
		
		update_option('um_tracking_notice', 1 );
		
		$tracking = new UM_Admin_Tracking();
		$tracking->send_checkin(true);
		
		exit( wp_redirect( remove_query_arg('um_adm_action') ) );
	}
	
	/***
	***	@Opt-out of tracking
	***/
	add_action('um_admin_do_action__opt_out_of_tracking', 'um_admin_do_action__opt_out_of_tracking');
	function um_admin_do_action__opt_out_of_tracking( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		
		global $reduxConfig;
		$reduxConfig->ReduxFramework->set('allow_tracking', 0);
		
		update_option('um_tracking_notice', 1 );
		
		exit( wp_redirect( remove_query_arg('um_adm_action') ) );
	}
	
	/***
	***	@Un-install UM completely
	***/
	add_action('um_admin_do_action__uninstall_ultimatemember', 'um_admin_do_action__uninstall_ultimatemember');
	function um_admin_do_action__uninstall_ultimatemember( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		
		$ultimatemember->uninstall->remove_um();
		
	}
	
	/***
	***	@various user actions
	***/
	add_action('um_admin_do_action__user_action', 'um_admin_do_action__user_action');
	function um_admin_do_action__user_action( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		if ( !isset( $_REQUEST['sub'] ) ) die();
		if ( !isset($_REQUEST['user_id']) ) die();
		
		um_fetch_user( $_REQUEST['user_id'] );
	
		$subaction = $_REQUEST['sub'];
		
		do_action("um_admin_user_action_hook", $subaction);
		do_action("um_admin_user_action_{$subaction}_hook");
		
		um_reset_user();
		
		wp_redirect( add_query_arg( 'update', 'user_updated', admin_url('?page=ultimatemember') ) );
		exit;
		
	}