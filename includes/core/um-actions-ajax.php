<?php

	/**
	 * Run check if username exists
	 * @uses action hooks: wp_ajax_nopriv_ultimatemember_check_username_exists, wp_ajax_ultimatemember_check_username_exists
	 * @return boolean
	 */
	add_action('wp_ajax_nopriv_ultimatemember_check_username_exists', 'ultimatemember_check_username_exists');
	add_action('wp_ajax_ultimatemember_check_username_exists', 'ultimatemember_check_username_exists');
	function ultimatemember_check_username_exists() {
		$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
		$exists   = username_exists( $username );
		$exists   = apply_filters( 'um_validate_username_exists', $exists, $username );

		if( $exists ) {
			echo 1;
		} else {
			echo 0;
		}

		die();
	}