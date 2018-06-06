<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Register' ) ) {


	/**
	 * Class Register
	 * @package um\core
	 */
	class Register {


		/**
		 * Register constructor.
		 */
		function __construct() {
			add_action( "um_after_register_fields",  array( $this, 'add_nonce' ) );
			add_action( "um_submit_form_register", array( $this, 'verify_nonce' ), 1, 1 );
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
		 * @param $args
		 *
		 * @return mixed
		 */
		public function verify_nonce( $args ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_register_allow_nonce_verification
			 * @description Enable/DIsable nonce verification of registration
			 * @input_vars
			 * [{"var":"$allow_nonce","type":"bool","desc":"Enable nonce"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_register_allow_nonce_verification', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_register_allow_nonce_verification', 'my_register_allow_nonce_verification', 10, 1 );
			 * function my_register_allow_nonce_verification( $allow_nonce ) {
			 *     // your code here
			 *     return $allow_nonce;
			 * }
			 * ?>
			 */
			$allow_nonce_verification = apply_filters( "um_register_allow_nonce_verification", true );

			if( ! $allow_nonce_verification  ){
				return $args;
			}

			if ( ! wp_verify_nonce( $args['_wpnonce'], 'um_register_form' ) || empty( $args['_wpnonce'] ) || ! isset( $args['_wpnonce'] ) ) {
				wp_die('Invalid Nonce.');
			}

			return $args;
		}

	}
}