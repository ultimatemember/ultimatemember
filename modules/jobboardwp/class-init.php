<?php
namespace umm\jobboardwp;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\jobboardwp
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'jobboardwp';


	/**
	 * @var
	 */
	private static $instance;


	/**
	 * @return Init
	 */
	static public function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Init constructor.
	 */
	function __construct() {
		$this->profile();

		if ( UM()->is_request( 'admin' ) ) {
			$this->admin();
		} elseif ( UM()->is_request( 'frontend' ) ) {
			$this->enqueue();
		}

		$this->activity();
		$this->notifications();
		$this->private_messages();
		$this->user_bookmarks();
		$this->verified_users();
	}


	/**
	 * @return includes\Profile()
	 */
	function profile() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\profile'] ) ) {
			UM()->classes['umm\jobboardwp\includes\profile'] = new includes\Profile();
		}
		return UM()->classes['umm\jobboardwp\includes\profile'];
	}


	/**
	 * @return includes\Enqueue()
	 */
	function enqueue() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\enqueue'] ) ) {
			UM()->classes['umm\jobboardwp\includes\enqueue'] = new includes\Enqueue();
		}
		return UM()->classes['umm\jobboardwp\includes\enqueue'];
	}


	/**
	 * @return includes\admin\Init()
	 */
	function admin() {
		if ( empty( UM()->classes['umm\jobboardwp\includes\admin\init'] ) ) {
			UM()->classes['umm\jobboardwp\includes\admin\init'] = new includes\admin\Init();
		}
		return UM()->classes['umm\jobboardwp\includes\admin\init'];
	}


	/**
	 * @return null|includes\cross_modules\Activity()
	 */
	function activity() {
		if ( ! UM()->modules()->is_active( 'activity' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\activity'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\activity'] = new includes\cross_modules\Activity();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\activity'];
	}


	/**
	 * @return null|includes\cross_modules\Notifications()
	 */
	function notifications() {
		if ( ! UM()->modules()->is_active( 'notifications' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\notifications'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\notifications'] = new includes\cross_modules\Notifications();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\notifications'];
	}


	/**
	 * @return null|includes\cross_modules\Private_Messages()
	 */
	function private_messages() {
		if ( ! UM()->modules()->is_active( 'private_messages' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\private_messages'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\private_messages'] = new includes\cross_modules\Private_Messages();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\private_messages'];
	}


	/**
	 * @return null|includes\cross_modules\User_Bookmarks()
	 */
	function user_bookmarks() {
		if ( ! UM()->modules()->is_active( 'user_bookmarks' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\user_bookmarks'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\user_bookmarks'] = new includes\cross_modules\User_Bookmarks();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\user_bookmarks'];
	}


	/**
	 * @return null|includes\cross_modules\Verified_Users()
	 */
	function verified_users() {
		if ( ! UM()->modules()->is_active( 'verified_users' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\jobboardwp\includes\cross_modules\verified_users'] ) ) {
			UM()->classes['umm\jobboardwp\includes\cross_modules\verified_users'] = new includes\cross_modules\Verified_Users();
		}
		return UM()->classes['umm\jobboardwp\includes\cross_modules\verified_users'];
	}
}
