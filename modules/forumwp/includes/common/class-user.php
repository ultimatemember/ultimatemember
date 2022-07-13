<?php
namespace umm\forumwp\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class User
 *
 * @package umm\forumwp\includes\common
 */
class User {


	/**
	 * User constructor.
	 */
	public function __construct() {
		add_filter( 'fmwp_user_can_create_topic', array( &$this, 'can_create_topic' ), 10, 3 );
		add_filter( 'fmwp_user_can_create_reply', array( &$this, 'can_create_reply' ), 10, 3 );
	}


	/**
	 * Change user permissions to create topic
	 *
	 * @param bool $can_create
	 * @param int  $user_id
	 * @param int  $forum_id
	 *
	 * @return bool
	 */
	function can_create_topic( $can_create, $user_id, $forum_id ) {
		$current_user = um_user( 'ID' );

		um_fetch_user( $user_id );

		if ( um_user( 'disable_create_forumwp_topics' ) ) {
			$can_create = false;
		}

		$roles_can_create = get_post_meta( $forum_id, '_um_forumwp_can_topic', true );
		if ( ! empty( $roles_can_create ) && ! user_can( $user_id, 'administrator' ) ) {
			$current_user_roles = um_user( 'roles' );
			if ( ! empty( $current_user_roles ) && count( array_intersect( $current_user_roles, $roles_can_create ) ) == 0 ) {
				$can_create = false;
			}
		}

		um_fetch_user( $current_user );

		return $can_create;
	}


	/**
	 * Change user permissions to create reply
	 *
	 * @param bool $can_create
	 * @param int  $user_id
	 * @param int  $topic_id
	 *
	 * @return bool
	 */
	function can_create_reply( $can_create, $user_id, $topic_id ) {
		$current_user = um_user( 'ID' );

		um_fetch_user( $user_id );

		if ( um_user( 'disable_create_forumwp_replies' ) ) {
			$can_create = false;
		}

		$forum_id = FMWP()->common()->topic()->get_forum_id( $topic_id );
		$roles_can_create = get_post_meta( $forum_id, '_um_forumwp_can_reply', true );
		if ( ! empty( $roles_can_create ) && ! user_can( $user_id, 'administrator' ) ) {
			$current_user_roles = um_user( 'roles' );
			if ( ! empty( $current_user_roles ) && count( array_intersect( $current_user_roles, $roles_can_create ) ) == 0 ) {
				$can_create = false;
			}
		}

		um_fetch_user( $current_user );

		return $can_create;
	}
}
