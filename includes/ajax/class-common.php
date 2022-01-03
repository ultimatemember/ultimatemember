<?php namespace um\ajax;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\ajax\Common' ) ) {


	/**
	 * Class Common
	 *
	 * @package um\ajax
	 */
	class Common {


		/**
		 * Common constructor.
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
			UM()->admin()->notices();
		}


		/**
		 * Check nonce
		 *
		 * @param bool|string $action
		 *
		 * @since 1.0
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
	}
}
