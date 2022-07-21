<?php
namespace umm\online\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class User
 *
 * @package umm\online\includes\common
 */
class User {


	/**
	 * User constructor.
	 */
	public function __construct() {
	}


	/**
	 *
	 */
	public function hooks() {
		add_action( 'init', array( &$this, 'log' ), 1 );
		add_action( 'init', array( &$this, 'schedule_update' ), 2 );
		add_action( 'um_delete_user',  array( $this, 'clear_online_user' ), 10, 1 );
		add_action( 'clear_auth_cookie', array( $this, 'clear_auth_cookie_clear_online_user' ), 10 );
	}


	/**
	 * Logs online user
	 */
	public function log() {
		// Guest or not on frontend
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		// User privacy do not allow that
		if ( $this->is_hidden_status( get_current_user_id() ) ) {
			return;
		}

		// We have a logged in user
		// Store the user as online with a timestamp of last seen
		UM()->module( 'online' )->users[ get_current_user_id() ] = current_time( 'timestamp' );

		// Save the new online users
		update_option( 'um_online_users', UM()->module( 'online' )->users );
	}


	/**
	 * Schedule update users online status
	 */
	public function schedule_update() {
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


	/**
	 * Make the user offline
	 *
	 * @param $user_id
	 */
	function clear_online_user( $user_id ) {
		$online_users = UM()->module( 'online' )->get_users();

		if ( array_key_exists( $user_id, $online_users ) ) {
			unset( $online_users[ $user_id ] );
			update_option( 'um_online_users', $online_users );
			update_option( 'um_online_users_last_updated', time() );
		}
	}


	/**
	 * Remove online user on logout process
	 */
	function clear_auth_cookie_clear_online_user() {
		$userinfo = wp_get_current_user();

		if ( ! empty( $userinfo->ID ) ) {
			$this->clear_online_user( $userinfo->ID );
		}
	}


	/**
	 * If user set hidden online status
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	function is_hidden_status( $user_id ) {
		$_hide_online_status = get_user_meta( $user_id, '_hide_online_status', true );
		if ( $_hide_online_status == 1 || ( isset( $_hide_online_status[0] ) && 'no' === $_hide_online_status[0] ) ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Checks if user is online
	 *
	 * @param $user_id
	 *
	 * @return bool
	 */
	function is_online( $user_id ) {
		return isset( UM()->module( 'online' )->users[ $user_id ] );
	}
}
