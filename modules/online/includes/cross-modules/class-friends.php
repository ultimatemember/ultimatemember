<?php
namespace umm\online\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Friends
 *
 * @package umm\online\includes\cross_modules
 */
class Friends {


	/**
	 * Friends constructor.
	 */
	public function __construct() {
		add_filter( 'um_friends_online_users', array( $this, 'get_online_users' ) );
	}


	/**
	 * Return an array of online users ID
	 *
	 * @param array $online_user_ids
	 *
	 * @return array
	 */
	public function get_online_users( $online_user_ids = array() ) {
		$online = UM()->module( 'online' )->get_users( 'ids' );
		$online_user_ids = array_merge( $online_user_ids, $online );

		return $online_user_ids;
	}
}
