<?php
namespace umm\forumwp\includes;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Permissions
 *
 * @package umm\forumwp\includes
 */
class Permissions {


	/**
	 * Permissions constructor.
	 */
	function __construct() {
		add_filter( 'fmwp_user_can_create_topic', array( &$this, 'can_create_topic' ), 10, 3 );
		add_filter( 'fmwp_user_can_create_reply', array( &$this, 'can_create_reply' ), 10, 3 );
		add_filter( 'fmwp_reply_disabled_reply_text', array( &$this, 'disable_reply_text' ), 10, 2 );
		add_filter( 'fmwp_create_topic_disabled_text', array( &$this, 'disable_topic_text' ), 10, 2 );
	}


	/**
	 * Change user permissions to create topic
	 *
	 * @param $can_create
	 * @param $user_id
	 * @param $forum_id
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
	 * @param $can_create
	 * @param $user_id
	 * @param $topic_id
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


	/**
	 * @param $text
	 * @param $topic_id
	 *
	 * @return mixed
	 */
	function disable_reply_text( $text, $topic_id ) {
		$current_user = um_user( 'ID' );

		um_fetch_user( get_current_user_id() );

		$disable_text = um_user( 'lock_create_forumwp_replies_notice' );
		if ( ! empty( $disable_text ) ) {
			$text = '<p>' . $disable_text . '</p>';
		}

		um_fetch_user( $current_user );
		return $text;
	}


	/**
	 * @param $text
	 * @param $topic_id
	 *
	 * @return mixed
	 */
	function disable_topic_text( $text, $topic_id ) {
		$current_user = um_user( 'ID' );

		um_fetch_user( get_current_user_id() );

		$disable_text = um_user( 'lock_create_forumwp_topics_notice' );
		if ( ! empty( $disable_text ) ) {
			$text = '<p>' . $disable_text . '</p>';
		}

		um_fetch_user( $current_user );
		return $text;
	}
}
