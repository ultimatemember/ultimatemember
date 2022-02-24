<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class User
 *
 * @package umm\online\includes
 */
class User {


	/**
	 * User constructor.
	 */
	function __construct() {
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
		if ( $_hide_online_status == 1 || ( isset( $_hide_online_status[0] ) && $_hide_online_status[0] == 'no' ) ) {
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


	/**
	 *
	 */
	function hooks() {
		add_action( 'um_delete_user',  array( $this, 'clear_online_user' ), 10, 1 );
		add_action( 'clear_auth_cookie', array( $this, 'clear_auth_cookie_clear_online_user' ), 10 );
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
}
