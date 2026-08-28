<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Account
 *
 * @package um\ajax
 */
class Account {

	/**
	 * Account constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_personal_data_export', array( &$this, 'export_request' ) );
		add_action( 'wp_ajax_um_personal_data_erase', array( &$this, 'erase_request' ) );
	}

	public function export_request() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'um-export-data' ) ) {
			wp_send_json_error( __( 'Wrong nonce.', 'ultimate-member' ) );
		}

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		if ( empty( $user ) ) {
			wp_send_json_error( __( 'Wrong user.', 'ultimate-member' ) );
		}

		if ( UM()->account()->current_password_is_required( 'privacy_download_data' ) ) {
			$password = ! empty( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
			$hash     = $user->data->user_pass;
			if ( ! wp_check_password( $password, $hash ) ) {
				wp_send_json_error(
					array(
						'single_user_password-export-request' => __( 'The password you entered is incorrect.', 'ultimate-member' ),
					)
				);
			}
		}

		$request_id = wp_create_user_request( $user->data->user_email, 'export_personal_data' );
		update_post_meta( $request_id, 'um_account_request', true );

		if ( empty( $request_id ) ) {
			wp_send_json_error( __( 'Wrong request.', 'ultimate-member' ) );
		}

		if ( is_wp_error( $request_id ) ) {
			wp_send_json_error( $request_id->get_error_message() );
		}

		wp_send_user_request( $request_id );

		wp_send_json_success( __( 'A confirmation email has been sent to your email. Click the link within the email to confirm your export request.', 'ultimate-member' ) );
	}

	public function erase_request() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'um-erase-data' ) ) {
			wp_send_json_error( __( 'Wrong nonce.', 'ultimate-member' ) );
		}

		$user_id = get_current_user_id();
		$user    = get_userdata( $user_id );
		if ( empty( $user ) ) {
			wp_send_json_error( __( 'Wrong user.', 'ultimate-member' ) );
		}

		if ( UM()->account()->current_password_is_required( 'privacy_erase_data' ) ) {
			$password = ! empty( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
			$hash     = $user->data->user_pass;
			if ( ! wp_check_password( $password, $hash ) ) {
				wp_send_json_error(
					array(
						'single_user_password-erase-request' => __( 'The password you entered is incorrect.', 'ultimate-member' ),
					)
				);
			}
		}

		$request_id = wp_create_user_request( $user->data->user_email, 'remove_personal_data' );
		update_post_meta( $request_id, 'um_account_request', true );

		if ( empty( $request_id ) ) {
			wp_send_json_error( __( 'Wrong request.', 'ultimate-member' ) );
		}

		if ( is_wp_error( $request_id ) ) {
			wp_send_json_error( $request_id->get_error_message() );
		}

		wp_send_user_request( $request_id );

		wp_send_json_success( __( 'A confirmation email has been sent to your email. Click the link within the email to confirm your deletion request.', 'ultimate-member' ) );
	}
}
