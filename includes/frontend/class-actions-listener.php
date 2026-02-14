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
			add_action( 'wp_footer', array( $this, 'display_action_error_notice' ) );
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
						$status = UM()->common()->users()->get_status( $user_id, 'formatted' );
						/* translators: %s: current user account status */
						$message = sprintf( __( 'This user could not be approved because their current status is: %s.', 'ultimate-member' ), $status );
						$this->set_action_error_and_redirect( $message, $user_id );
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
						$status = UM()->common()->users()->get_status( $user_id, 'formatted' );
						/* translators: %s: current user account status */
						$message = sprintf( __( 'This user could not be reactivated because their current status is: %s.', 'ultimate-member' ), $status );
						$this->set_action_error_and_redirect( $message, $user_id );
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
						$status = UM()->common()->users()->get_status( $user_id, 'formatted' );
						/* translators: %s: current user account status */
						$message = sprintf( __( 'This user could not be set as pending review because their current status is: %s.', 'ultimate-member' ), $status );
						$this->set_action_error_and_redirect( $message, $user_id );
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
						$message = __( 'The activation email could not be sent for this user. Please try again.', 'ultimate-member' );
						$this->set_action_error_and_redirect( $message, $user_id );
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
						$status = UM()->common()->users()->get_status( $user_id, 'formatted' );
						/* translators: %s: current user account status */
						$message = sprintf( __( 'This user could not be rejected because their current status is: %s.', 'ultimate-member' ), $status );
						$this->set_action_error_and_redirect( $message, $user_id );
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
						$status = UM()->common()->users()->get_status( $user_id, 'formatted' );
						/* translators: %s: current user account status */
						$message = sprintf( __( 'This user could not be deactivated because their current status is: %s.', 'ultimate-member' ), $status );
						$this->set_action_error_and_redirect( $message, $user_id );
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

		/**
		 * Store an action error message in a transient and redirect back to the profile page.
		 *
		 * Instead of calling wp_die() which shows a blank death page, this method
		 * stores an informative error message and redirects the admin back to the
		 * user's profile page where the notice will be displayed.
		 *
		 * @since 2.10.0
		 *
		 * @param string $message Error message to display.
		 * @param int    $user_id The target user ID.
		 */
		private function set_action_error_and_redirect( $message, $user_id ) {
			$current_user_id = get_current_user_id();
			$transient_key   = 'um_action_error_' . $current_user_id;

			set_transient( $transient_key, $message, 30 );

			$redirect_url = add_query_arg( 'um_action_error', '1', UM()->permalinks()->get_current_url( true ) );
			um_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Display an action error notice on the frontend.
		 *
		 * Hooked to `wp_footer`. When a frontend action fails (e.g., approving a user
		 * whose status was already changed from the backend), this method retrieves
		 * the stored error message from a transient and displays it as a dismissible
		 * overlay notice.
		 *
		 * @since 2.10.0
		 */
		public function display_action_error_notice() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only used to check for display flag.
			if ( empty( $_GET['um_action_error'] ) ) {
				return;
			}

			if ( ! is_user_logged_in() ) {
				return;
			}

			$current_user_id = get_current_user_id();
			$transient_key   = 'um_action_error_' . $current_user_id;
			$message         = get_transient( $transient_key );

			if ( empty( $message ) ) {
				return;
			}

			delete_transient( $transient_key );
			?>
			<p class="um-notice warning" id="um-action-error-notice" style="position:fixed;top:40px;left:50%;transform:translateX(-50%);z-index:999999;max-width:600px;width:90%;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
				<i class="um-icon-close" onclick="this.parentElement.remove();"></i>
				<?php echo esc_html( $message ); ?>
			</p>
			<?php
		}
	}
}
