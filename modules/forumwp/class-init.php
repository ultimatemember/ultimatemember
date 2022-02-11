<?php
namespace umm\forumwp;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Init
 *
 * @package umm\forumwp
 */
final class Init {


	/**
	 * @var string
	 */
	private $slug = 'forumwp';


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
		$this->permissions();
		$this->profile();

		if ( UM()->is_request( 'admin' ) ) {
			$this->admin();
		}

		$this->activity();
		$this->notifications();
		$this->profile_completeness();
	}


	/**
	 * @return null|includes\cross_modules\Activity()
	 */
	function activity() {
		if ( ! UM()->modules()->is_active( 'activity' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\forumwp\includes\cross_modules\activity'] ) ) {
			UM()->classes['umm\forumwp\includes\cross_modules\activity'] = new includes\cross_modules\Activity();
		}
		return UM()->classes['umm\forumwp\includes\cross_modules\activity'];
	}


	/**
	 * @return null|includes\cross_modules\Notifications()
	 */
	function notifications() {
		if ( ! UM()->modules()->is_active( 'notifications' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\forumwp\includes\cross_modules\notifications'] ) ) {
			UM()->classes['umm\forumwp\includes\cross_modules\notifications'] = new includes\cross_modules\Notifications();
		}
		return UM()->classes['umm\forumwp\includes\cross_modules\notifications'];
	}


	/**
	 * @return null|includes\cross_modules\Profile_Completeness()
	 */
	function profile_completeness() {
		if ( ! UM()->modules()->is_active( 'profile_completeness' ) ) {
			return null;
		}

		if ( empty( UM()->classes['umm\forumwp\includes\cross_modules\profile_completeness'] ) ) {
			UM()->classes['umm\forumwp\includes\cross_modules\profile_completeness'] = new includes\cross_modules\Profile_Completeness();
		}
		return UM()->classes['umm\forumwp\includes\cross_modules\profile_completeness'];
	}


	/**
	 * @return includes\Permissions()
	 */
	function permissions() {
		if ( empty( UM()->classes['umm\forumwp\includes\permissions'] ) ) {
			UM()->classes['umm\forumwp\includes\permissions'] = new includes\Permissions();
		}
		return UM()->classes['umm\forumwp\includes\permissions'];
	}


	/**
	 * @return includes\Profile()
	 */
	function profile() {
		if ( empty( UM()->classes['umm\forumwp\includes\profile'] ) ) {
			UM()->classes['umm\forumwp\includes\profile'] = new includes\Profile();
		}
		return UM()->classes['umm\forumwp\includes\profile'];
	}


	/**
	 * @return includes\admin\Init()
	 */
	function admin() {
		if ( empty( UM()->classes['umm\forumwp\includes\admin\init'] ) ) {
			UM()->classes['umm\forumwp\includes\admin\init'] = new includes\admin\Init();
		}
		return UM()->classes['umm\forumwp\includes\admin\init'];
	}
}
