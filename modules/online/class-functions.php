<?php
namespace umm\online;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Functions
 *
 * @package umm\online
 */
class Functions {


	/**
	 * @var array
	 */
	var $users = array();


	/**
	 * Functions constructor.
	 */
	function __construct() {
		// maybe flush if hasn't correct format
		$users = get_option( 'um_online_users', array() );
		if ( ! is_array( $users ) ) {
			delete_option( 'um_online_users' );
		}
		$this->users = get_option( 'um_online_users', array() );
	}


	/**
	 * Gets users online
	 *
	 * @param string $fields
	 *
	 * @return array
	 */
	function get_users( $fields = 'all' ) {
		// force flush if isn't array
		if ( ! is_array( $this->users ) ) {
			return array();
		}

		if ( 'ids' === $fields ) {
			return array_keys( $this->users );
		} elseif ( 'count' === $fields ) {
			return count( $this->users );
		} else {
			if ( ! empty( $this->users ) ) {
				arsort( $this->users ); // this will get us the last active user first
			}
			return $this->users;
		}
	}
}
