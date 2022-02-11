<?php
namespace umm\forumwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Activity
 *
 * @package umm\forumwp\includes\cross_modules
 */
class Activity {


	/**
	 * Activity constructor.
	 */
	function __construct() {
		add_filter( 'um_activity_global_actions', array( &$this, 'social_activity_action' ) );
	}

	/**
	 * Add new activity action
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	function social_activity_action( $actions ) {
		$actions['new-forumwp-topic'] = __( 'New forum topic', 'ultimate-member' );
		$actions['new-forumwp-reply'] = __( 'New topic reply', 'ultimate-member' );
		return $actions;
	}
}
