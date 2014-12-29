<?php

	/***
	***	@filter for shortcode args
	***/
	add_filter('um_shortcode_args_filter', 'um_shortcode_args_filter', 99);
	function um_shortcode_args_filter( $args ) {
		global $ultimatemember;

		if ($ultimatemember->shortcodes->message_mode == true) {
			$args['template'] = 'message';
			$ultimatemember->shortcodes->custom_message = um_user( um_user('status')  . '_message' );
			um_reset_user();
		}
		
		foreach( $args as $k => $v ) {
			if ( $ultimatemember->validation->is_serialized( $args[$k] ) ) {
				if ( !empty( $args[$k] ) ) {
					$args[$k] = unserialize( $args[$k] );
				}
			}
		}
		
		return $args;
		
	}