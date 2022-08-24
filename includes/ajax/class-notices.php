<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\ajax\Notices' ) ) {


	/**
	 * Class Notices
	 *
	 * @package um\ajax
	 */
	class Notices {


		/**
		 * Notices constructor.
		 */
		function __construct() {
			add_action( 'wp_ajax_um_dismiss_notice', array( &$this, 'dismiss_notice' ) );
			add_action( 'wp_ajax_um_rated', array( &$this, 'hide_rated_footer' ) );
		}


		/**
		 * AJAX callback for dismiss the admin notice by the key
		 */
		function dismiss_notice() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator', 'ultimate-member' ) );
			}

			if ( empty( $_POST['key'] ) ) {
				wp_send_json_error( __( 'Wrong Data', 'ultimate-member' ) );
			}

			$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
			if ( ! is_array( $hidden_notices ) ) {
				$hidden_notices = array();
			}

			$hidden_notices[] = sanitize_key( $_POST['key'] );

			update_option( 'um_hidden_admin_notices', $hidden_notices );

			wp_send_json_success();
		}


		/**
		 * When user clicks the review link in backend
		 */
		function hide_rated_footer() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			update_option( 'um_admin_footer_text_rated', 1 );
			wp_send_json_success();
		}
	}
}
