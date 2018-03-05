<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Does an action to user asap
 *
 * @param string $action
 */
function um_admin_user_action_hook( $action ) {
	switch ( $action ) {
			
		default:
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_custom_hook_{$action}
			 * @description Integration hook on user action
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_custom_hook_{$action}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_custom_hook_{$action}', 'my_admin_custom_hook', 10, 1 );
			 * function my_admin_after_main_notices( $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_admin_custom_hook_{$action}", UM()->user()->id );
			break;

		case 'um_put_as_pending':
			UM()->user()->pending();
			break;

		case 'um_approve_membership':
		case 'um_reenable':
			UM()->user()->approve();
			break;

		case 'um_reject_membership':
			UM()->user()->reject();
			break;

		case 'um_resend_activation':
			UM()->user()->email_pending();
			break;

		case 'um_deactivate':
			UM()->user()->deactivate();
			break;

		case 'um_delete':
			if ( is_admin() ) {
				wp_die( 'This action is not allowed in backend.', 'ultimate-member' );
			}
			UM()->user()->delete();
			break;
	}
}
add_action( 'um_admin_user_action_hook', 'um_admin_user_action_hook', 10, 1 );