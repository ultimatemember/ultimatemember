<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\frontend\Init' ) ) {

	/**
	 * Class Init
	 *
	 * @package um\frontend
	 */
	class Init {

		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		public function includes() {
			$this->actions_listener();
			$this->directory();
			$this->enqueue();
			if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
				$this->profile();
			} else {
				$this->modal();
			}
			$this->secure();
			$this->user_profile();
			$this->users();
		}

		/**
		 * @since 2.8.7
		 *
		 * @return Actions_Listener
		 */
		public function actions_listener() {
			if ( empty( UM()->classes['um\frontend\actions_listener'] ) ) {
				UM()->classes['um\frontend\actions_listener'] = new Actions_Listener();
			}

			return UM()->classes['um\frontend\actions_listener'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return Directory
		 */
		public function directory() {
			if ( empty( UM()->classes['um\frontend\directory'] ) ) {
				UM()->classes['um\frontend\directory'] = new Directory();
			}

			return UM()->classes['um\frontend\directory'];
		}

		/**
		 * @since 2.7.0
		 *
		 * @return Enqueue
		 */
		public function enqueue() {
			if ( empty( UM()->classes['um\frontend\enqueue'] ) ) {
				UM()->classes['um\frontend\enqueue'] = new Enqueue();
			}

			return UM()->classes['um\frontend\enqueue'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return form\Form
		 */
		public function form() {
			if ( empty( UM()->classes['um\frontend\form\form'] ) ) {
				UM()->classes['um\frontend\form\form'] = new form\Form();
			}

			return UM()->classes['um\frontend\form\form'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return Layouts
		 */
		public static function layouts() {
			if ( empty( UM()->classes['um\frontend\layouts'] ) ) {
				UM()->classes['um\frontend\layouts'] = new Layouts();
			}

			return UM()->classes['um\frontend\layouts'];
		}

		/**
		 * @since 2.8.6
		 *
		 * @return Modal
		 */
		public function modal() {
			if ( empty( UM()->classes['um\frontend\modal'] ) ) {
				UM()->classes['um\frontend\modal'] = new Modal();
			}

			return UM()->classes['um\frontend\modal'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return Profile
		 */
		public function profile() {
			if ( empty( UM()->classes['um\frontend\profile'] ) ) {
				UM()->classes['um\frontend\profile'] = new Profile();
			}

			return UM()->classes['um\frontend\profile'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Secure
		 */
		public function secure() {
			if ( empty( UM()->classes['um\frontend\secure'] ) ) {
				UM()->classes['um\frontend\secure'] = new Secure();
			}
			return UM()->classes['um\frontend\secure'];
		}

		/**
		 * @since 2.8.7
		 *
		 * @return User_Profile
		 */
		public function user_profile() {
			if ( empty( UM()->classes['um\frontend\user_profile'] ) ) {
				UM()->classes['um\frontend\user_profile'] = new User_Profile();
			}
			return UM()->classes['um\frontend\user_profile'];
		}

		/**
		 * @since 2.8.7
		 *
		 * @return Users
		 */
		public function users() {
			if ( empty( UM()->classes['um\frontend\users'] ) ) {
				UM()->classes['um\frontend\users'] = new Users();
			}
			return UM()->classes['um\frontend\users'];
		}
	}
}
