<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Run check if username exists
 * @uses action hooks: wp_ajax_nopriv_ultimatemember_check_username_exists, wp_ajax_ultimatemember_check_username_exists
 * @return boolean
 */
function ultimatemember_check_username_exists() {
	UM()->check_ajax_nonce();

	$username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
	$exists   = username_exists( $username );

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_validate_username_exists
	 * @description Change username exists validation
	 * @input_vars
	 * [{"var":"$exists","type":"bool","desc":"Exists?"},
	 * {"var":"$username","type":"string","desc":"Username"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_validate_username_exists', 'function_name', 10, 2 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_validate_username_exists', 'my_validate_username_exists', 10, 2 );
	 * function my_account_pre_updating_profile( $exists, $username ) {
	 *     // your code here
	 *     return $exists;
	 * }
	 * ?>
	 */
	$exists = apply_filters( 'um_validate_username_exists', $exists, $username );

	if ( $exists ) {
		echo 1;
	} else {
		echo 0;
	}

	die();
}
add_action('wp_ajax_nopriv_ultimatemember_check_username_exists', 'ultimatemember_check_username_exists');
add_action('wp_ajax_ultimatemember_check_username_exists', 'ultimatemember_check_username_exists');