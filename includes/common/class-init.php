<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Init' ) ) {


	/**
	 * Class Init
	 *
	 * @package um\common
	 */
	class Init {


		/**
		 * Init constructor.
		 */
		function __construct() {
			// loading modules when UM core is loaded
			add_action( 'um_core_loaded', array( UM()->modules(), 'load_modules' ), 1 );
		}

		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		public function includes() {
			if ( is_admin() ) {
				$this->navmenu(); // includes in common but only for wp-admin and wp-ajax, not the frontend
				$this->theme_updater(); // includes in common but only for wp-admin and wp-ajax, not the frontend
			}

			$this->access()->hooks();
			$this->cpt()->hooks();
			$this->cron()->hooks();
			$this->gdpr();
			$this->mail()->hooks();
			$this->pages();
			$this->screen();

			$this->shortcodes();
			$this->user()->hooks();
		}

		/**
		 * @since 3.0
		 *
		 * @return Access()
		 */
		public function access() {
			if ( empty( UM()->classes['um\common\access'] ) ) {
				UM()->classes['um\common\access'] = new Access();
			}
			return UM()->classes['um\common\access'];
		}

		/**
		 * @since 3.0
		 *
		 * @return CPT()
		 */
		public function cpt() {
			if ( empty( UM()->classes['um\common\cpt'] ) ) {
				UM()->classes['um\common\cpt'] = new CPT();
			}
			return UM()->classes['um\common\cpt'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Cron()
		 */
		public function cron() {
			if ( empty( UM()->classes['um\common\cron'] ) ) {
				UM()->classes['um\common\cron'] = new Cron();
			}
			return UM()->classes['um\common\cron'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Field()
		 */
		public function field() {
			if ( empty( UM()->classes['um\common\field'] ) ) {
				UM()->classes['um\common\field'] = new Field();
			}
			return UM()->classes['um\common\field'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Navmenu()
		 */
		public function navmenu() {
			if ( empty( UM()->classes['um\common\navmenu'] ) ) {
				UM()->classes['um\common\navmenu'] = new Navmenu();
			}
			return UM()->classes['um\common\navmenu'];
		}

		/**
		 * @since 3.0
		 *
		 * @return User()
		 */
		public function user() {
			if ( empty( UM()->classes['um\common\user'] ) ) {
				UM()->classes['um\common\user'] = new User();
			}
			return UM()->classes['um\common\user'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Shortcodes()
		 */
		public function shortcodes() {
			if ( empty( UM()->classes['um\common\shortcodes'] ) ) {
				UM()->classes['um\common\shortcodes'] = new Shortcodes();
			}
			return UM()->classes['um\common\shortcodes'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Options()
		 */
		public function options() {
			if ( empty( UM()->classes['um\common\options'] ) ) {
				UM()->classes['um\common\options'] = new Options();
			}
			return UM()->classes['um\common\options'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Pages()
		 */
		public function pages() {
			if ( empty( UM()->classes['um\common\pages'] ) ) {
				UM()->classes['um\common\pages'] = new Pages();
			}
			return UM()->classes['um\common\pages'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Screen()
		 */
		public function screen() {
			if ( empty( UM()->classes['um\common\screen'] ) ) {
				UM()->classes['um\common\screen'] = new Screen();
			}
			return UM()->classes['um\common\screen'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Theme_Updater()
		 */
		public function theme_updater() {
			if ( empty( UM()->classes['um\common\theme_updater'] ) ) {
				UM()->classes['um\common\theme_updater'] = new Theme_Updater();
			}
			return UM()->classes['um\common\theme_updater'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Filesystem()
		 */
		public function filesystem() {
			if ( empty( UM()->classes['um\common\filesystem'] ) ) {
				UM()->classes['um\common\filesystem'] = new Filesystem();
			}
			return UM()->classes['um\common\filesystem'];
		}

		/**
		 * @since 3.0
		 *
		 * @return GDPR()
		 */
		public function gdpr() {
			if ( empty( UM()->classes['um\common\gdpr'] ) ) {
				UM()->classes['um\common\gdpr'] = new GDPR();
			}
			return UM()->classes['um\common\gdpr'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Mail()
		 */
		public function mail() {
			if ( empty( UM()->classes['um\common\mail'] ) ) {
				UM()->classes['um\common\mail'] = new Mail();
			}
			return UM()->classes['um\common\mail'];
		}
	}
}
