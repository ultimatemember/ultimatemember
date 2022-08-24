<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
			add_action( 'wp_ajax_um_purge_users_cache', array( $this, 'purge_users_cache' ) );
			add_action( 'wp_ajax_um_purge_temp_files', array( $this, 'purge_temp_files' ) );
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


		/**
		 *
		 */
		public function purge_users_cache() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
			}

			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'um_cache_userdata_%'" );

			wp_send_json_success( __( 'Your user cache is now removed.', 'ultimate-member' ) );
		}


		/**
		 *
		 */
		public function purge_temp_files() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
			}

			UM()->files()->remove_dir( UM()->files()->upload_temp );

			wp_send_json_success( __( 'Your temp uploads directory is now clean.', 'ultimate-member' ) );
		}
	}
}
