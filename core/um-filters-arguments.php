<?php

	/***
	***	@filter for shortcode args
	***/
	add_filter('um_shortcode_args_filter', 'um_shortcode_args_filter', 99);
	function um_shortcode_args_filter( $array ) {
		global $ultimatemember;
		
		// checks for message mode
		if ($ultimatemember->shortcodes->message_mode == true) {
			$array['template'] = 'message';
			$ultimatemember->shortcodes->custom_message = um_user( um_user('status')  . '_message' );
			um_reset_user();
		}
		
		return $array;
		
	}