<?php namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\ajax\Init' ) ) {


	/**
	 * Class Init
	 *
	 * @package um\ajax
	 */
	class Init {


		/**
		 * Init constructor.
		 */
		function __construct() {

		}


		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		function includes() {
			$this->notices();
			$this->user();
			$this->builder();
			UM()->admin()->notices();
		}


		/**
		 * Check nonce
		 *
		 * @param bool|string $action
		 *
		 * @since 3.0
		 */
		function check_nonce( $action = false ) {
			$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : '';
			$action = empty( $action ) ? 'um-common-nonce' : $action;

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error( __( 'Wrong AJAX Nonce', 'ultimate-member' ) );
			}
		}


		/**
		 * @since 3.0
		 *
		 * @return Notices()
		 */
		function notices() {
			if ( empty( UM()->classes['um\ajax\notices'] ) ) {
				UM()->classes['um\ajax\notices'] = new Notices();
			}
			return UM()->classes['um\ajax\notices'];
		}


		/**
		 * @since 3.0
		 *
		 * @return User()
		 */
		function user() {
			if ( empty( UM()->classes['um\ajax\user'] ) ) {
				UM()->classes['um\ajax\user'] = new User();
			}
			return UM()->classes['um\ajax\user'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Builder()
		 */
		function builder() {
			if ( empty( UM()->classes['um\ajax\builder'] ) ) {
				UM()->classes['um\ajax\builder'] = new Builder();
			}
			return UM()->classes['um\ajax\builder'];
		}
	}
}
