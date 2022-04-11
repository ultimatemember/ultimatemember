<?php
namespace um\ajax;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\ajax\Settings' ) ) {


	/**
	 * Class Settings
	 *
	 * @package um\ajax
	 */
	class Settings {


		/**
		 * Settings constructor.
		 */
		function __construct() {
			add_action( 'wp_ajax_um_same_page_update', array( $this, 'same_page_update_ajax' ) );
		}


		/**
		 * AJAX handler for the AJAX update fields
		 */
		public function same_page_update_ajax() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_POST['cb_func'] ) ) {
				wp_send_json_error( __( 'Wrong callback', 'ultimate-member' ) );
			}

			$cb_func = sanitize_key( $_POST['cb_func'] );

			do_action( 'um_same_page_update_ajax_' . $cb_func );

			// if there isn't callback above
			wp_send_json_error( __( 'Wrong callback', 'ultimate-member' ) );
		}
	}
}
