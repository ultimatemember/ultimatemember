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
				$this->set_action_error_and_redirect( 'super_admin' );
			}

			$action = sanitize_key( $_REQUEST['um_action'] );
			// phpcs:enable WordPress.Security.NonceVerification -- there is nonce verification below for each case
			switch ( $action ) {
				case 'approve_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "approve_user{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$this->set_action_error_and_redirect( 'edit_user' );
					}

					$result = UM()->common()->users()->approve( $user_id );
					if ( ! $result ) {
						$this->set_action_error_and_redirect( 'approve_user' );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'reactivate_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "reactivate_user{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$this->set_action_error_and_redirect( 'edit_user' );
					}

					$result = UM()->common()->users()->reactivate( $user_id );
					if ( ! $result ) {
						$this->set_action_error_and_redirect( 'reactivate_user' );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'put_user_as_pending':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "put_user_as_pending{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$this->set_action_error_and_redirect( 'edit_user' );
					}

					$result = UM()->common()->users()->set_as_pending( $user_id );
					if ( ! $result ) {
						$this->set_action_error_and_redirect( 'put_user_as_pending' );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'resend_user_activation':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "resend_user_activation{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$this->set_action_error_and_redirect( 'edit_user' );
					}

					$result = UM()->common()->users()->send_activation( $user_id, true );
					if ( ! $result ) {
						$this->set_action_error_and_redirect( 'resend_user_activation' );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'reject_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "reject_user{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$this->set_action_error_and_redirect( 'edit_user' );
					}

					$result = UM()->common()->users()->reject( $user_id );
					if ( ! $result ) {
						$this->set_action_error_and_redirect( 'reject_user' );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'deactivate_user':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "deactivate_user{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					if ( ! UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$this->set_action_error_and_redirect( 'edit_user' );
					}

					$result = UM()->common()->users()->deactivate( $user_id );
					if ( ! $result ) {
						$this->set_action_error_and_redirect( 'deactivate_user' );
					}

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'switch_user':
					if ( ! current_user_can( 'manage_options' ) ) {
						return;
					}

					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "switch_user{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					UM()->user()->auto_login( $user_id );

					um_safe_redirect( UM()->permalinks()->get_current_url( true ) );
					exit;
				case 'delete':
					if ( ! wp_verify_nonce( $_REQUEST['nonce'], "delete{$user_id}" ) ) {
						$this->set_action_error_and_redirect( 'expired' );
					}

					if ( ! UM()->roles()->um_current_user_can( 'delete', $user_id ) ) {
						$this->set_action_error_and_redirect( 'delete_user' );
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

		/**
		 * Store an action error message in a transient and redirect back to the profile page.
		 *
		 * Instead of calling wp_die() which shows a blank death page, this method
		 * stores an informative error message and redirects the admin back to the
		 * user's profile page where the notice will be displayed.
		 *
		 * @since 2.11.3
		 *
		 * @param string $code Error code to redirect.
		 */
		private function set_action_error_and_redirect( $code ) {
			$redirect_url = add_query_arg( 'um_action_error', $code, UM()->permalinks()->get_current_url( true ) );
			um_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 *
		 * @since 2.11.3
		 *
		 * @param string $code
		 *
		 * @return string
		 */
		public function get_error_message( $code ) {
			switch ( $code ) {
				case 'super_admin':
					$message = __( 'Super administrators can not be modified.', 'ultimate-member' );
					break;

				case 'expired':
					$message = __( 'The link you followed has expired.', 'ultimate-member' );
					break;

				case 'edit_user':
					$message = __( 'You do not have permission to edit this user.', 'ultimate-member' );
					break;

				case 'approve_user':
					$message = __( 'This user could not be approved.', 'ultimate-member' );
					break;

				case 'reactivate_user':
					$message = __( 'This user could not be reactivated.', 'ultimate-member' );
					break;

				case 'put_user_as_pending':
					$message = __( 'This user could not be set as pending review.', 'ultimate-member' );
					break;

				case 'resend_user_activation':
					$message = __( 'Activation email could not be sent for this user.', 'ultimate-member' );
					break;

				case 'reject_user':
					$message = __( 'This user could not be rejected.', 'ultimate-member' );
					break;

				case 'deactivate_user':
					$message = __( 'This user could not be deactivated.', 'ultimate-member' );
					break;

				case 'delete_user':
					$message = __( 'You do not have permission to delete this user.', 'ultimate-member' );
					break;

				default:
					$message = apply_filters( 'um_action_error_message_default', __( 'Something went wrong', 'ultimate-member' ), $code );
			}

			return apply_filters( 'um_action_error_message', $message, $code );
		}
	}
}
