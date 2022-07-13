<?php
namespace umm\forumwp\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Reply
 *
 * @package umm\forumwp\includes\common
 */
class Reply {


	/**
	 * Reply constructor.
	 */
	public function __construct() {
		add_filter( 'fmwp_reply_disabled_reply_text', array( &$this, 'disable_reply_text' ), 10, 2 );
	}


	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public function disable_reply_text( $text ) {
		$current_user = um_user( 'ID' );

		um_fetch_user( get_current_user_id() );

		$disable_text = um_user( 'lock_create_forumwp_replies_notice' );
		if ( ! empty( $disable_text ) ) {
			$text = '<p>' . $disable_text . '</p>';
		}

		um_fetch_user( $current_user );
		return $text;
	}
}
