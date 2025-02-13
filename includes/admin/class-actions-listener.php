<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Actions_Listener' ) ) {

	/**
	 * Class Actions_Listener
	 *
	 * @package um\admin
	 */
	class Actions_Listener {

		/**
		 * USER_ACTIONS array containing different actions for managing users:
		 * - approve_user: Approve a user
		 * - reactivate_user: Reactivate a user account
		 * - put_user_as_pending: Set a user as pending
		 * - resend_user_activation: Resend activation email to a user
		 * - reject_user: Reject a user
		 * - deactivate_user: Deactivate a user account
		 */
		const USER_ACTIONS = array(
			'approve_user',
			'reactivate_user',
			'put_user_as_pending',
			'resend_user_activation',
			'reject_user',
			'deactivate_user',
		);

		/**
		 * Actions_Listener constructor.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'actions_listener' ) );
			add_filter( 'um_adm_action_individual_nonce_actions', array( $this, 'extends_individual_nonce_actions' ) ); // @todo remove soon after UM core update
		}

		/**
		 * Handle wp-admin actions
		 *
		 * @since 2.8.7
		 * @since 2.10.0 User should have 'edit_users' capability instead of 'manage_options'.
		 */
		public function actions_listener() {
			// phpcs:disable WordPress.Security.NonceVerification -- there is nonce verification below for each case
			if ( empty( $_REQUEST['um_adm_action'] ) ) {
				return;
			}

			$action = sanitize_key( $_REQUEST['um_adm_action'] );
			// phpcs:enable WordPress.Security.NonceVerification -- there is nonce verification below for each case

			if ( in_array( $action, self::USER_ACTIONS, true ) && ! current_user_can( 'edit_users' ) ) {
				return;
			}

			switch ( $action ) {
				case 'approve_user':
					if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
						die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
					}

					$user_id = absint( $_REQUEST['uid'] );

					check_admin_referer( "approve_user{$user_id}" );

					$redirect = wp_get_referer();
					if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$result = UM()->common()->users()->approve( $user_id );
						if ( $result ) {
							$redirect = add_query_arg(
								array(
									'update'         => 'um_approved',
									'approved_count' => 1,
								),
								$redirect
							);
						}
					}

					wp_safe_redirect( $redirect );
					exit;
				case 'reactivate_user':
					if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
						die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
					}

					$user_id = absint( $_REQUEST['uid'] );

					check_admin_referer( "reactivate_user{$user_id}" );

					$redirect = wp_get_referer();
					if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$result = UM()->common()->users()->reactivate( $user_id );
						if ( $result ) {
							$redirect = add_query_arg(
								array(
									'update'            => 'um_reactivated',
									'reactivated_count' => 1,
								),
								$redirect
							);
						}
					}
					wp_safe_redirect( $redirect );
					exit;
				case 'put_user_as_pending':
					if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
						die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
					}

					$user_id = absint( $_REQUEST['uid'] );

					check_admin_referer( "put_user_as_pending{$user_id}" );

					$redirect = wp_get_referer();
					if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$result = UM()->common()->users()->set_as_pending( $user_id );
						if ( $result ) {
							$redirect = add_query_arg(
								array(
									'update'        => 'um_pending',
									'pending_count' => 1,
								),
								$redirect
							);
						}
					}
					wp_safe_redirect( $redirect );
					exit;
				case 'resend_user_activation':
					if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
						die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
					}

					$user_id = absint( $_REQUEST['uid'] );

					check_admin_referer( "resend_user_activation{$user_id}" );

					$redirect = wp_get_referer();
					if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$result = UM()->common()->users()->send_activation( $user_id, true );
						if ( $result ) {
							$redirect = add_query_arg(
								array(
									'update' => 'um_resend_activation',
									'resend_activation_count' => 1,
								),
								$redirect
							);
						}
					}
					wp_safe_redirect( $redirect );
					exit;
				case 'reject_user':
					if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
						die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
					}

					$user_id = absint( $_REQUEST['uid'] );

					check_admin_referer( "reject_user{$user_id}" );

					$redirect = wp_get_referer();
					if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$result = UM()->common()->users()->reject( $user_id );
						if ( $result ) {
							$redirect = add_query_arg(
								array(
									'update'         => 'um_rejected',
									'rejected_count' => 1,
								),
								$redirect
							);
						}
					}
					wp_safe_redirect( $redirect );
					exit;
				case 'deactivate_user':
					if ( empty( $_REQUEST['uid'] ) || ! is_numeric( $_REQUEST['uid'] ) ) {
						die( esc_html__( 'Invalid user ID', 'ultimate-member' ) );
					}

					$user_id = absint( $_REQUEST['uid'] );

					check_admin_referer( "deactivate_user{$user_id}" );

					$redirect = wp_get_referer();
					if ( UM()->common()->users()->can_current_user_edit_user( $user_id ) ) {
						$result = UM()->common()->users()->deactivate( $user_id );
						if ( $result ) {
							$redirect = add_query_arg(
								array(
									'update'            => 'um_deactivate',
									'deactivated_count' => 1,
								),
								$redirect
							);
						}
					}
					wp_safe_redirect( $redirect );
					exit;
			}
		}

		/**
		 * Extends an array of actions with the predefined user actions.
		 *
		 * @param array $actions The original array of actions to extend.
		 *
		 * @return array The extended array containing additional user actions.
		 */
		public function extends_individual_nonce_actions( $actions ) {
			return array_merge( $actions, self::USER_ACTIONS );
		}
	}
}
