<?php

	/***
	***	@purge temp
	***/
	add_action('um_admin_do_action__purge_temp', 'um_admin_do_action__purge_temp');
	function um_admin_do_action__purge_temp( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		
		$ultimatemember->files->remove_dir( $ultimatemember->files->upload_temp );
		
		$url = remove_query_arg('um_adm_action', $ultimatemember->permalinks->get_current_url() );
		$url = add_query_arg('update','purged_temp',$url);
		exit( wp_redirect($url) );
	}
	
	/***
	***	@duplicate form
	***/
	add_action('um_admin_do_action__duplicate_form', 'um_admin_do_action__duplicate_form');
	function um_admin_do_action__duplicate_form( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		if ( !isset($_REQUEST['post_id']) || !is_numeric( $_REQUEST['post_id'] ) ) die();
		
		$post_id = $_REQUEST['post_id'];
		
		$n = array(
			'post_type' 	  	=> 'um_form',
			'post_title'		=> sprintf(__('Duplicate of %s','ultimatemember'), get_the_title($post_id) ),
			'post_status'		=> 'publish',
			'post_author'   	=> um_user('ID'),
		);

		$n_id = wp_insert_post( $n );
		
		$n_fields = get_post_custom( $post_id );
		foreach ( $n_fields as $key => $value ) {
			
			if ( $key == '_um_custom_fields' ) {
				$the_value = unserialize( $value[0] );
			} else {
				$the_value = $value[0];
			}
		
			update_post_meta( $n_id, $key, $the_value );
			
		}
		
		delete_post_meta($n_id, '_um_core');
		
		$url = admin_url('edit.php?post_type=um_form');
		$url = add_query_arg('update','form_duplicated',$url);
		
		exit( wp_redirect( $url ) );
		
	}

	/***
	***	@download a language remotely
	***/
	add_action('um_admin_do_action__um_language_downloader', 'um_admin_do_action__um_language_downloader');
	function um_admin_do_action__um_language_downloader( $action ){
		global $ultimatemember;
		if ( !is_admin() || !current_user_can('manage_options') ) die();
		
		$locale = get_option('WPLANG');
		if ( !$locale ) return;
		if ( file_exists( WP_LANG_DIR . '/plugins/ultimatemember-'.$locale.'.mo' ) ) return;
		if ( !isset( $ultimatemember->available_languages[$locale] ) ) return;
		
		$path = $ultimatemember->files->upload_basedir;
		$path = str_replace('/uploads/ultimatemember','',$path);
		$path = $path . '/languages/plugins/';
		$path = str_replace('//','/',$path);
		
		$remote = 'https://ultimatemember.com/wp-content/languages/plugins/ultimatemember-' . $locale . '.po';
		$remote2 = 'https://ultimatemember.com/wp-content/languages/plugins/ultimatemember-' . $locale . '.mo';

		$remote_tmp = download_url( $remote, $timeout = 300 );
		copy( $remote_tmp, $path . 'ultimatemember-' . $locale . '.po' );
		unlink( $remote_tmp );
		
		$remote2_tmp = download_url( $remote2, $timeout = 300 );
		copy( $remote2_tmp, $path . 'ultimatemember-' . $locale . '.mo' );
		unlink( $remote2_tmp );

		exit( wp_redirect( remove_query_arg('um_adm_action') ) );
		
	}
	
	/***
	***	@Action to hide notices in admin
	***/
	add_action('um_admin_do_action__um_hide_locale_notice', 'um_admin_do_action__hide_notice');
	add_action('um_admin_do_action__um_can_register_notice', 'um_admin_do_action__hide_notice');
	add_action('um_admin_do_action__um_hide_exif_notice', 'um_admin_do_action__hide_notice');
	function um_admin_do_action__hide_notice( $action ){
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