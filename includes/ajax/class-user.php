<?php
namespace um\ajax;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\ajax\User' ) ) {


	/**
	 * Class User
	 *
	 * @package um\ajax
	 */
	class User {


		/**
		 * User constructor.
		 */
		function __construct() {
			add_action( 'wp_ajax_um_admin_review_registration', array( &$this, 'admin_review_registration' ) );
		}


		/**
		 *
		 */
		function admin_review_registration() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['user_id'] ) ) {
				wp_send_json_error( __( 'Invalid user ID.', 'ultimate-member' ) );
			}

			$user_id = absint( $_REQUEST['user_id'] );

			if ( ! um_can_view_profile( $user_id ) ) {
				wp_send_json_success( '' );
			}

			um_fetch_user( $user_id );

			UM()->user()->preview = true;

			$output = um_user_submitted_registration_formatted( true );

			um_reset_user();

			wp_send_json_success( $output );
		}
	}
}
