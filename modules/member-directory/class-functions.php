<?php
namespace umm\member_directory;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Functions
 *
 * @package umm\member_directory
 */
class Functions {


	/**
	 * Functions constructor.
	 */
	function __construct() {
	}


	/**
	 * @return bool
	 */
	function get_hide_in_members_default() {
		$default = false;
		$option = UM()->options()->get( 'account_hide_in_directory_default' );
		if ( $option == 'Yes' ) {
			$default = true;
		}

		$default = apply_filters( 'um_member_directory_hide_in_members_default', $default );
		return $default;
	}


	/**
	 * @param int $id
	 *
	 * @return string
	 */
	function get_directory_hash( $id ) {
		$hash = substr( md5( $id ), 10, 5 );
		return $hash;
	}
}
