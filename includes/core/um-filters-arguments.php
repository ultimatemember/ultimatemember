<?php

	/***
	***	@conditional logout form
	***/
	add_filter('um_shortcode_args_filter', 'um_display_logout_form', 99);
	function um_display_logout_form( $args ) {
		if ( is_user_logged_in() && isset( $args['mode'] ) && $args['mode'] == 'login' ) {
			
			if ( get_current_user_id() != um_user('ID' ) ) {
				um_fetch_user( get_current_user_id() );
			}
			
			$args['template'] = 'logout';
		
		}
		
		return $args;
		
	}
	
	/***
	***	@filter for shortcode args
	***/
	add_filter('um_shortcode_args_filter', 'um_shortcode_args_filter', 99);
	function um_shortcode_args_filter( $args ) {

		if ( UM()->shortcodes()->message_mode == true ) {
			
			$args['template'] = 'message';
			$roleID = esc_attr( $_REQUEST['um_role'] );
			$role = UM()->roles()->role_data( $roleID );
			$status = $role["status"];
			$message = $role["{$status}_message"];
			UM()->shortcodes()->custom_message = $message;
			
		}
		
		foreach( $args as $k => $v ) {
            $args[$k] = maybe_unserialize( $args[$k] );
		}
		
		return $args;
		
	}