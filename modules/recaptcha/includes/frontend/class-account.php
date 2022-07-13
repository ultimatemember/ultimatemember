<?php
namespace umm\recaptcha\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Account
 *
 * @package umm\recaptcha\includes\frontend
 */
class Account {


	/**
	 * Account constructor.
	 */
	function __construct() {
		add_action( 'um_before_signon_after_account_changes', array( &$this, 'remove_authenticate_recaptcha_action' ) );
	}


	/**
	 * Remove handling reCAPTCHA on the re-authentication when password is changed on the Account page
	 */
	function remove_authenticate_recaptcha_action() {
		remove_action( 'wp_authenticate', array( UM()->module( 'recaptcha' )->common()->wp_login_php(), 'validate_authentication' ), 2 );
	}
}
