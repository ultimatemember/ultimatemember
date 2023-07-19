<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Login' ) ) {

	/**
	 * Class Login
	 *
	 * @package um\core
	 */
	class Login {

		/**
		 * @var string
		 */
		public $auth_id = '';

		/**
		 * Login constructor.
		 */
		public function __construct() {
			add_action( 'um_after_login_fields', array( $this, 'add_nonce' ) );
			add_action( 'um_submit_form_login', array( $this, 'verify_nonce' ), 1, 2 );
		}

		/**
		 * Add registration form notice
		 */
		public function add_nonce() {
			wp_nonce_field( 'um_login_form' );
		}

		/**
		 * Verify nonce handler
		 *
		 * @param array $args
		 * @param array $form_data
		 */
		public function verify_nonce( $args, $form_data ) {
			/**
			 * Filters allow nonce verifying while UM Login submission.
			 *
			 * @param {bool}  $allow_nonce Is allowed verify nonce on login. By default, allowed = `true`.
			 * @param {array} $form_data   Form's metakeys. Since 2.6.7.
			 *
			 * @return {bool} Is allowed verify.
			 *
			 * @since 2.0
			 * @hook um_login_allow_nonce_verification
			 *
			 * @example <caption>Disable verifying nonce on the login page.</caption>
			 * add_filter( 'um_login_allow_nonce_verification', '__return_false' );
			 */
			$allow_nonce_verification = apply_filters( 'um_login_allow_nonce_verification', true, $form_data );
			if ( ! $allow_nonce_verification ) {
				return;
			}

			if ( empty( $args['_wpnonce'] ) || ! wp_verify_nonce( $args['_wpnonce'], 'um_login_form' ) ) {
				/**
				 * Filters URL for redirect if login form nonce isn't verified.
				 *
				 * @param {string} $error_url URL for redirect if login form nonce isn't verified.
				 *
				 * @return {string} URL for redirect.
				 *
				 * @since 2.0
				 * @hook um_login_invalid_nonce_redirect_url
				 *
				 * @example <caption>Change URL for redirect if login form nonce isn't verified.</caption>
				 * function my_um_login_invalid_nonce_redirect_url( $error_url ) {
				 *     return '{your_custom_url}';
				 * }
				 * add_filter( 'um_login_invalid_nonce_redirect_url', 'my_um_login_invalid_nonce_redirect_url' );
				 */
				$url = apply_filters( 'um_login_invalid_nonce_redirect_url', add_query_arg( array( 'err' => 'invalid_nonce' ) ) );
				um_safe_redirect( $url );
				exit;
			}
		}
	}
}
