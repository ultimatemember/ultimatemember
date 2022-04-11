<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Common
 *
 * @package umm\online\includes
 */
class Common {

	/**
	 * Common constructor.
	 */
	function __construct() {
		add_action( 'init', array( &$this, 'log' ), 1 );
		add_action( 'init', array( &$this, 'schedule_update' ), 2 );
	}


	/**
	 * Logs online user
	 */
	function log() {
		// Guest or not on frontend
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// User privacy do not allow that
		if ( UM()->module( 'online' )->user()->is_hidden_status( get_current_user_id() ) ) {
			return;
		}

		// We have a logged in user
		// Store the user as online with a timestamp of last seen
		UM()->module( 'online' )->users[ get_current_user_id() ] = current_time( 'timestamp' );

		// Save the new online users
		update_option( 'um_online_users', UM()->module( 'online' )->users );
	}


	function schedule_update() {
		// Send a maximum of once per period
		$minute_interval = apply_filters( 'um_online_interval', 15 ); // minutes

		$last_send = get_option( 'um_online_users_last_updated' );
		if ( $last_send && $last_send > strtotime( "-{$minute_interval} minutes" ) ) {
			return;
		}

		// We have to check if each user was last seen in the previous x
		if ( is_array( UM()->module( 'online' )->users ) ) {
			foreach( UM()->module( 'online' )->users as $user_id => $last_seen ) {
				if ( ( current_time('timestamp') - $last_seen ) > ( 60 * $minute_interval ) ) {
					// Time now is more than x since he was last seen
					// Remove user from online list
					unset( UM()->module( 'online' )->users[ $user_id ] );
				}
			}
			update_option( 'um_online_users', UM()->module( 'online' )->users );
		}

		update_option( 'um_online_users_last_updated', time() );
	}
}
