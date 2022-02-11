<?php
namespace umm\recaptcha\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Private_Messages
 *
 * @package umm\recaptcha\includes\cross_modules
 */
class Private_Messages {


	/**
	 * Private_Messages constructor.
	 */
	function __construct() {
		add_action( 'um_pre_directory_shortcode', array( &$this, 'um_recaptcha_directory_enqueue_scripts' ), 10, 1 );
	}


	/**
	 * reCAPTCHA scripts/styles enqueue in member directory if login popup from Private messages button is visible
	 *
	 * @param array $args
	 */
	function um_recaptcha_directory_enqueue_scripts( $args ) {
		if ( ! $this->captcha_allowed( $args ) ) {
			return;
		}

		if ( is_user_logged_in() || empty( $args['show_pm_button'] ) ) {
			return;
		}

		UM()->reCAPTCHA()->enqueue()->wp_enqueue_scripts();
	}
}
