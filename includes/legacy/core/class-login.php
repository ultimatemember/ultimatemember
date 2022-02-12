<?php
namespace um\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Login' ) ) {


	/**
	 * Class Login
	 *
	 * @package um\core
	 */
	class Login {


		/**
		 * Logged-in user ID
		 */
		var $auth_id = '';


		/**
		 * Register constructor.
		 */
		function __construct() {
			add_action( 'um_after_login_fields',  array( $this, 'add_nonce' ) );
			add_action( 'um_submit_form_login', array( $this, 'verify_nonce' ), 1, 1 );
		}


		/**
		 * Add registration form notice
		 */
		function add_nonce() {
			wp_nonce_field( 'um_login_form' );
		}


		/**
		 * Verify nonce handler
		 *
		 * @param $args
		 *
		 * @return mixed
		 */
		function verify_nonce( $args ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_login_allow_nonce_verification
			 * @description Enable/Disable nonce verification of login
			 * @input_vars
			 * [{"var":"$allow_nonce","type":"bool","desc":"Enable nonce"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_login_allow_nonce_verification', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_login_allow_nonce_verification', 'my_login_allow_nonce_verification', 10, 1 );
			 * function my_login_allow_nonce_verification( $allow_nonce ) {
			 *     // your code here
			 *     return $allow_nonce;
			 * }
			 * ?>
			 */
			$allow_nonce_verification = apply_filters( 'um_login_allow_nonce_verification', true );

			if ( ! $allow_nonce_verification  ) {
				return $args;
			}

			if ( ! wp_verify_nonce( $args['_wpnonce'], 'um_login_form' ) || empty( $args['_wpnonce'] ) || ! isset( $args['_wpnonce'] ) ) {
				$url = apply_filters( 'um_login_invalid_nonce_redirect_url', add_query_arg( [ 'err' => 'invalid_nonce' ] ) );
				exit( wp_redirect( $url ) );
			}

			return $args;
		}

	}

}