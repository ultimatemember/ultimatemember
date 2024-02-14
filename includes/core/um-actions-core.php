<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes the requests of UM actions
 *
 */
function um_action_request_process() {
	if ( is_admin() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( ! isset( $_REQUEST['um_action'] ) ) {
		return;
	}

	$action = sanitize_key( $_REQUEST['um_action'] );

	$uid = 0;
	if ( isset( $_REQUEST['uid'] ) ) {
		$uid = absint( $_REQUEST['uid'] );
	}

	if ( ! empty( $uid ) && ! UM()->user()->user_exists_by_id( $uid ) ) {
		return;
	}

	if ( ! empty( $uid ) && is_super_admin( $uid ) ) {
		wp_die( esc_html__( 'Super administrators can not be modified.', 'ultimate-member' ) );
	}

	$role           = get_role( UM()->roles()->get_priority_user_role( get_current_user_id() ) );
	$can_edit_users = current_user_can( 'edit_users' ) && $role->has_cap( 'edit_users' );

	switch ( $action ) {
		default:
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_action_user_request_hook
			 * @description Integration for user actions
			 * @input_vars
			 * [{"var":"$action","type":"string","desc":"Action for user"},
			 * {"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_action_user_request_hook', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_action_user_request_hook', 'my_action_user_request', 10, 2 );
			 * function my_action_user_request( $action, $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_action_user_request_hook', $action, $uid );
			break;

		case 'edit':
			UM()->fields()->editing = true;
			if ( ! um_is_myprofile() ) {
				if ( ! UM()->roles()->um_current_user_can( 'edit', um_profile_id() ) ) {
					exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
				}
			} else {
				if ( ! um_can_edit_my_profile() ) {
					$url = um_edit_my_profile_cancel_uri();
					exit( wp_redirect( $url ) );
				}
			}
			break;

		case 'um_switch_user':
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			UM()->user()->auto_login( $uid );
			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;

		case 'um_reject_membership':
			if ( ! $can_edit_users ) {
				wp_die( esc_html__( 'You do not have permission to make this action.', 'ultimate-member' ) );
			}

			um_fetch_user( $uid );
			UM()->user()->reject();
			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;

		case 'um_approve_membership':
		case 'um_reenable':
			if ( ! $can_edit_users ) {
				wp_die( esc_html__( 'You do not have permission to make this action.', 'ultimate-member' ) );
			}

			um_fetch_user( $uid );

			add_filter( 'um_template_tags_patterns_hook', array( UM()->password(), 'add_placeholder' ), 10, 1 );
			add_filter( 'um_template_tags_replaces_hook', array( UM()->password(), 'add_replace_placeholder' ), 10, 1 );

			UM()->user()->approve();
			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;

		case 'um_put_as_pending':
			if ( ! $can_edit_users ) {
				wp_die( esc_html__( 'You do not have permission to make this action.', 'ultimate-member' ) );
			}

			um_fetch_user( $uid );
			UM()->user()->pending();
			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;

		case 'um_resend_activation':
			if ( ! $can_edit_users ) {
				wp_die( esc_html__( 'You do not have permission to make this action.', 'ultimate-member' ) );
			}

			add_filter( 'um_template_tags_patterns_hook', array( UM()->user(), 'add_activation_placeholder' ), 10, 1 );
			add_filter( 'um_template_tags_replaces_hook', array( UM()->user(), 'add_activation_replace_placeholder' ), 10, 1 );

			um_fetch_user( $uid );
			UM()->user()->email_pending();
			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;

		case 'um_deactivate':
			if ( ! $can_edit_users ) {
				wp_die( esc_html__( 'You do not have permission to make this action.', 'ultimate-member' ) );
			}

			um_fetch_user( $uid );
			UM()->user()->deactivate();
			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;

		case 'um_delete':
			if ( ! UM()->roles()->um_current_user_can( 'delete', $uid ) ) {
				wp_die( esc_html__( 'You do not have permission to delete this user.', 'ultimate-member' ) );
			}

			um_fetch_user( $uid );
			UM()->user()->delete();
			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;
		case 'um_approve_new_email':
			if ( ! $can_edit_users ) {
				wp_die( esc_html__( 'You do not have permission to make this action.', 'ultimate-member' ) );
			}

			$new_email = get_user_meta( $uid, 'um_changed_user_email', true );

			$args = array(
				'ID'         => $uid,
				'user_email' => sanitize_email( $new_email ),
			);
			wp_update_user( $args );

			delete_user_meta( $uid, 'um_changed_user_email' );
			delete_user_meta( $uid, 'um_changed_user_email_action' );

			if ( ! empty( UM()->options()->get( 'flush_login_sessions' ) ) ) {
				$sessions = WP_Session_Tokens::get_instance( $uid );
				$sessions->destroy_all();
			}

			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;
		case 'um_reject_new_email':
			if ( ! $can_edit_users ) {
				wp_die( esc_html__( 'You do not have permission to make this action.', 'ultimate-member' ) );
			}
			delete_user_meta( $uid, 'um_changed_user_email' );
			delete_user_meta( $uid, 'um_changed_user_email_action' );

			exit( wp_redirect( UM()->permalinks()->get_current_url( true ) ) );
			break;

	}
}
add_action( 'template_redirect', 'um_action_request_process', 10000 );
