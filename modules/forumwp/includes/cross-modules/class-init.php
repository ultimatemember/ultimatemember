<?php namespace umm\forumwp\includes\cross_modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\forumwp\includes\cross_modules
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
	}


	/**
	 *
	 */
	public function includes() {
		$this->activity();
		$this->profile_completeness();
		$this->real_time_notifications();
	}


	/**
	 * @return Activity()
	 */
	function activity() {
		if ( ! UM()->modules()->is_active( 'activity' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\forumwp\includes\cross_modules\activity'] ) ) {
			UM()->classes['umm\forumwp\includes\cross_modules\activity'] = new Activity();
		}
		return UM()->classes['umm\forumwp\includes\cross_modules\activity'];
	}


	/**
	 * @return Profile_Completeness()
	 */
	function profile_completeness() {
		if ( ! UM()->modules()->is_active( 'profile-completeness' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\forumwp\includes\cross_modules\profile_completeness'] ) ) {
			UM()->classes['umm\forumwp\includes\cross_modules\profile_completeness'] = new Profile_Completeness();
		}
		return UM()->classes['umm\forumwp\includes\cross_modules\profile_completeness'];
	}


	/**
	 * @return Real_Time_Notifications()
	 */
	function real_time_notifications() {
		if ( ! UM()->modules()->is_active( 'real-time-notifications' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\forumwp\includes\cross_modules\real_time_notifications'] ) ) {
			UM()->classes['umm\forumwp\includes\cross_modules\real_time_notifications'] = new Real_Time_Notifications();
		}
		return UM()->classes['umm\forumwp\includes\cross_modules\real_time_notifications'];
	}
}
