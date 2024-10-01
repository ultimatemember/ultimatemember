<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Users
 *
 * @package um\frontend
 */
class Users {

	public function hooks() {
	}

	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function get_actions_list( $user_id ) {
		$actions = array();

		um_fetch_user( $user_id );

		$priority_role = UM()->roles()->get_priority_user_role( get_current_user_id() );
		$role          = get_role( $priority_role );

		$can_edit_users = null !== $role && current_user_can( 'edit_users' ) && $role->has_cap( 'edit_users' );
		if ( $can_edit_users ) {
			if ( UM()->common()->users()->can_be_approved( $user_id ) ) {
				$actions['approve_user'] = array( 'label' => __( 'Approve Membership', 'ultimate-member' ) );
			}
			if ( UM()->common()->users()->can_be_rejected( $user_id ) ) {
				$actions['reject_user'] = array( 'label' => __( 'Reject Membership', 'ultimate-member' ) );
			}
			if ( UM()->common()->users()->can_be_reactivated( $user_id ) ) {
				$actions['reactivate_user'] = array( 'label' => __( 'Reactivate this account', 'ultimate-member' ) );
			}
			if ( UM()->common()->users()->can_be_set_as_pending( $user_id ) ) {
				$actions['put_user_as_pending'] = array( 'label' => __( 'Put as Pending Review', 'ultimate-member' ) );
			}
			if ( UM()->common()->users()->can_activation_send( $user_id ) ) {
				$title = __( 'Send activation email', 'ultimate-member' );
				if ( UM()->common()->users()->has_status( $user_id, 'awaiting_email_confirmation' ) ) {
					$title = __( 'Resend activation email', 'ultimate-member' );
				}
				$actions['resend_user_activation'] = array( 'label' => $title );
			}
			if ( UM()->common()->users()->can_be_deactivated( $user_id ) ) {
				$actions['deactivate_user'] = array( 'label' => __( 'Deactivate this account', 'ultimate-member' ) );
			}
		}

		if ( UM()->roles()->um_current_user_can( 'delete', $user_id ) ) {
			$actions['delete'] = array( 'label' => __( 'Delete this user', 'ultimate-member' ) );
		}

		if ( current_user_can( 'manage_options' ) && ! is_super_admin( $user_id ) ) {
			$actions['switch_user'] = array( 'label' => __( 'Login as this user', 'ultimate-member' ) );
		}

		/**
		 * Filters users actions list in Ultimate Member frontend.
		 *
		 * @since 1.3.x
		 * @hook um_admin_user_actions_hook
		 *
		 * @param {array} $actions CPT keys.
		 * @param {int}   $user_id User ID.
		 *
		 * @return {array} CPT keys.
		 *
		 * @example <caption>Add `um_custom_action` action to the users actions list on frontend.</caption>
		 * function um_custom_admin_user_actions_hook( $actions, $user_id ) {
		 *     $actions['um_custom_action'] = array( 'label' => 'um_custom_action_label' );
		 *     return $actions;
		 * }
		 * add_filter( 'um_admin_user_actions_hook', 'um_custom_admin_user_actions_hook', 10, 2 );
		 */
		return apply_filters( 'um_admin_user_actions_hook', $actions, $user_id );
	}
}
