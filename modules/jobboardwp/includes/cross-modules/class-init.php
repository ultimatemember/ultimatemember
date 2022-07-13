<?php
namespace umm\jobboardwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\jobboardwp\includes\cross_modules
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
		$this->private_messages();
		$this->real_time_notifications();
		$this->user_bookmarks();
		$this->verified_users();
	}


	/**
	 * @return null|Activity()
	 */
	function activity() {
		if ( ! UM()->modules()->is_active( 'activity' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\activity'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\activity'] = new Activity();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\activity'];
	}


	/**
	 * @return null|Private_Messages()
	 */
	function private_messages() {
		if ( ! UM()->modules()->is_active( 'private-messages' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\private_messages'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\private_messages'] = new Private_Messages();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\private_messages'];
	}


	/**
	 * @return null|Real_Time_Notifications()
	 */
	function real_time_notifications() {
		if ( ! UM()->modules()->is_active( 'real-time-notifications' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\real_time_notifications'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\real_time_notifications'] = new Real_Time_Notifications();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\real_time_notifications'];
	}


	/**
	 * @return null|User_Bookmarks()
	 */
	function user_bookmarks() {
		if ( ! UM()->modules()->is_active( 'user-bookmarks' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\user_bookmarks'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\user_bookmarks'] = new User_Bookmarks();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\user_bookmarks'];
	}


	/**
	 * @return null|Verified_Users()
	 */
	function verified_users() {
		if ( ! UM()->modules()->is_active( 'verified-users' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\verified_users'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\verified_users'] = new Verified_Users();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\verified_users'];
	}
}
