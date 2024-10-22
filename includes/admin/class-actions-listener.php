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
		 */
		public function actions_listener() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! empty( $_REQUEST['um_adm_action'] ) ) {
				switch ( sanitize_key( $_REQUEST['um_adm_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- there is nonce verification below for each case
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
										'update' => 'um_reactivated',
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
										'update' => 'um_deactivate',
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
		}

		public function extends_individual_nonce_actions( $actions ) {
			$actions[] = 'approve_user';
			$actions[] = 'reactivate_user';
			$actions[] = 'put_user_as_pending';
			$actions[] = 'resend_user_activation';
			$actions[] = 'reject_user';
			$actions[] = 'deactivate_user';
			return $actions;
		}
	}
}
