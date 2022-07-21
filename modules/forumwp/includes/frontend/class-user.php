<?php
namespace umm\forumwp\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class User
 *
 * @package umm\forumwp\includes\frontend
 */
class User {


	/**
	 * User constructor.
	 */
	public function __construct() {
		add_filter( 'fmwp_user_display_name', array( $this, 'change_display_name' ), 10, 2 );
		add_filter( 'fmwp_user_profile_link', array( $this, 'user_profile_link' ), 10, 2 );
	}


	/**
	 * @param string   $display_name
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	public function change_display_name( $display_name, $user ) {
		um_fetch_user( $user->ID );
		$d_name = um_user( 'display_name' );
		um_reset_user();

		return ! empty( $d_name ) ? $d_name : $display_name;
	}


	/**
	 * Change FMWP profile link to UM profile
	 *
	 * @param string $link
	 * @param int    $user_id
	 *
	 * @return string
	 */
	public function user_profile_link( $link, $user_id ) {
		$link = um_user_profile_url( $user_id );
		return $link;
	}
}
