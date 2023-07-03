<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Register' ) ) {

	/**
	 * Class Register
	 * @package um\core
	 */
	class Register {

		/**
		 * Register constructor.
		 */
		public function __construct() {
			add_action( 'um_after_register_fields', array( $this, 'add_nonce' ) );
			add_action( 'um_submit_form_register', array( $this, 'verify_nonce' ), 1, 2 );
		}

		/**
		 * Add registration form notice
		 */
		public function add_nonce() {
			wp_nonce_field( 'um_register_form' );
		}

		/**
		 * Verify nonce handler
		 *
		 * @param array $args
		 * @param array $form_data
		 */
		public function verify_nonce( $args, $form_data ) {
			/**
			 * Filters allow nonce verifying while UM Register submission.
			 *
			 * @param {bool}  $allow_nonce Is allowed verify nonce on register. By default, allowed = `true`.
			 * @param {array} $form_data   Form's metakeys. Since 2.6.7.
			 *
			 * @return {bool} Is allowed verify.
			 *
			 * @since 2.0
			 * @hook um_register_allow_nonce_verification
			 *
			 * @example <caption>Disable verifying nonce on the register page.</caption>
			 * add_filter( 'um_login_allow_nonce_verification', '__return_false' );
			 */
			$allow_nonce_verification = apply_filters( 'um_register_allow_nonce_verification', true, $form_data );
			if ( ! $allow_nonce_verification ) {
				return;
			}

			if ( empty( $args['_wpnonce'] ) || ! wp_verify_nonce( $args['_wpnonce'], 'um_register_form' ) ) {
				// @todo add hookdocs
				$url = apply_filters( 'um_register_invalid_nonce_redirect_url', add_query_arg( array( 'err' => 'invalid_nonce' ) ) );
				wp_safe_redirect( $url );
				exit;
			}
		}
	}
}
