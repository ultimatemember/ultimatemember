<?php
namespace umm\forumwp\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Topic
 *
 * @package umm\forumwp\includes\common
 */
class Topic {


	/**
	 * Topic constructor.
	 */
	public function __construct() {
		add_filter( 'fmwp_create_topic_disabled_text', array( &$this, 'disable_topic_text' ), 10, 1 );
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public function disable_topic_text( $text ) {
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
