<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\frontend\Actions_Listener' ) ) {

	/**
	 * Class Actions_Listener
	 *
	 * @package um\frontend
	 */
	class Actions_Listener {

		/**
		 * Actions_Listener constructor.
		 */
		public function __construct() {
			add_action( 'wp_loaded', array( $this, 'actions_listener' ) );
		}

		/**
		 * Handle frontend actions
		 *
		 * @since 2.8.7
		 */
		public function actions_listener() {
			if ( ! is_user_logged_in() ) {
				return;
			}
			// phpcs:disable WordPress.Security.NonceVerification -- there is nonce verification below for each case
			if ( empty( $_REQUEST['um_action'] ) || empty( $_REQUEST['nonce'] ) ) {
				return;
			}

			$user_id = 0;
			if ( isset( $_REQUEST['uid'] ) ) {
				$user_id = absint( $_REQUEST['uid'] );
			}

			if ( ! empty( $user_id ) && ! UM()->common()->users()::user_exists( $user_id ) ) {
				return;
			}

			if ( get_current_user_id() === $user_id ) {
				return;
			}

			if ( ! empty( $user_id ) && is_super_admin( $user_id ) ) {
				wp_die( esc_html__( 'Super administrators can not be modified.', 'ultimate-member' ) );
			}

			$action = sanitize_key( $_REQUEST['um_action'] );
			// phpcs:enable WordPress.Security.NonceVerification -- there is nonce verification below for each case
			switch ( $action ) {
				case 'approve_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "approve_user{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						wp_die( esc_html__( 'You do not have permission to edit this user.', 'ultimate-member' ) );
					}

					$result = UM()->common()->users()->approve( $user_id );
					if ( ! $result ) {
						wp_die( esc_html__( 'Something went wrong.', 'ultimate-member' ) );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'reactivate_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "reactivate_user{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						wp_die( esc_html__( 'You do not have permission to edit this user.', 'ultimate-member' ) );
					}

					$result = UM()->common()->users()->reactivate( $user_id );
					if ( ! $result ) {
						wp_die( esc_html__( 'Something went wrong.', 'ultimate-member' ) );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'put_user_as_pending':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "put_user_as_pending{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						wp_die( esc_html__( 'You do not have permission to edit this user.', 'ultimate-member' ) );
					}

					$result = UM()->common()->users()->set_as_pending( $user_id );
					if ( ! $result ) {
						wp_die( esc_html__( 'Something went wrong.', 'ultimate-member' ) );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'resend_user_activation':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "resend_user_activation{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						wp_die( esc_html__( 'You do not have permission to edit this user.', 'ultimate-member' ) );
					}

					$result = UM()->common()->users()->send_activation( $user_id, true );
					if ( ! $result ) {
						wp_die( esc_html__( 'Something went wrong.', 'ultimate-member' ) );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'reject_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "reject_user{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						wp_die( esc_html__( 'You do not have permission to edit this user.', 'ultimate-member' ) );
					}

					$result = UM()->common()->users()->reject( $user_id );
					if ( ! $result ) {
						wp_die( esc_html__( 'Something went wrong.', 'ultimate-member' ) );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'deactivate_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "deactivate_user{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						wp_die( esc_html__( 'You do not have permission to edit this user.', 'ultimate-member' ) );
					}

					$result = UM()->common()->users()->deactivate( $user_id );
					if ( ! $result ) {
						wp_die( esc_html__( 'Something went wrong.', 'ultimate-member' ) );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'switch_user':
					if ( ! current_user_can( 'manage_options' ) ) {
						return;
					}

					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "switch_user{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					UM()->user()->auto_login( $user_id );

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'delete':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "delete{$user_id}" ) ) {
						wp_die( esc_html__( 'The link you followed has expired.', 'ultimate-member' ) );
					}

					if ( ! UM()->roles()->um_current_user_can( 'delete', $user_id ) ) {
						wp_die( esc_html__( 'You do not have permission to delete this user.', 'ultimate-member' ) );
					}

					um_fetch_user( $user_id );
					UM()->user()->delete();

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				default:
					/**
					 * Fires to handle 3rd-party user actions from User Profile.
					 *
					 * Note: Please verify nonce and redirect after action individually in 3rd-party handler.
					 *
					 * @since 1.3.x
					 * @hook um_action_user_request_hook
					 *
					 * @param {string} $action  User action key.
					 * @param {int}    $user_id User ID.
					 *
					 * @example <caption>Update `some_custom_meta` user meta on `my_custom_action`.</caption>
					 * function um_action_user_request_hook( $action, $user_id ) {
					 *     if ( 'my_custom_action' === $action ) {
					 *         update_user_meta( $user_id, 'some_custom_meta', true );
					 *     }
					 * }
					 * add_action( 'um_action_user_request_hook', 'um_action_user_request_hook', 10, 2 );
					 */
					do_action( 'um_action_user_request_hook', $action, $user_id );
					break;
			}
		}
	}
}
