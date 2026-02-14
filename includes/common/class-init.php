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
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		public function includes() {
			$this->actions();

			$this->cpt()->hooks();
			$this->filesystem()->hooks();
			$this->guest();
			$this->rewrite();
			$this->screen();
			$this->secure()->hooks();
			$this->site_health();
			$this->theme()->hooks();
			$this->users()->hooks();
		}

		/**
		 * Init actions that can be scheduled via Action Scheduler.
		 *
		 * @since 2.9.0
		 */
		private function actions() {
			if ( empty( UM()->classes['um\common\actions\emails'] ) ) {
				UM()->classes['um\common\actions\emails'] = new actions\Emails();
			}

			if ( empty( UM()->classes['um\common\actions\users'] ) ) {
				UM()->classes['um\common\actions\users'] = new actions\Users();
			}
			// Other classes init here as soon as possible.
		}

		/**
		 * @since 2.9.3
		 *
		 * @return APIs
		 */
		public function apis() {
			if ( empty( UM()->classes['um\common\apis'] ) ) {
				UM()->classes['um\common\apis'] = new APIs();
			}
			return UM()->classes['um\common\apis'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return CPT
		 */
		public function cpt() {
			if ( empty( UM()->classes['um\common\cpt'] ) ) {
				UM()->classes['um\common\cpt'] = new CPT();
			}
			return UM()->classes['um\common\cpt'];
		}

		/**
		 * @since 2.8.7
		 *
		 * @return Filesystem
		 */
		public function filesystem() {
			if ( empty( UM()->classes['um\common\filesystem'] ) ) {
				UM()->classes['um\common\filesystem'] = new Filesystem();
			}
			return UM()->classes['um\common\filesystem'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return Guest
		 */
		public function guest() {
			if ( empty( UM()->classes['um\common\guest'] ) ) {
				UM()->classes['um\common\guest'] = new Guest();
			}
			return UM()->classes['um\common\guest'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return Rewrite
		 */
		public function rewrite() {
			if ( empty( UM()->classes['um\common\rewrite'] ) ) {
				UM()->classes['um\common\rewrite'] = new Rewrite();
			}
			return UM()->classes['um\common\rewrite'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Screen
		 */
		public function screen() {
			if ( empty( UM()->classes['um\common\screen'] ) ) {
				UM()->classes['um\common\screen'] = new Screen();
			}
			return UM()->classes['um\common\screen'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Secure
		 */
		public function secure() {
			if ( empty( UM()->classes['um\common\secure'] ) ) {
				UM()->classes['um\common\secure'] = new Secure();
			}
			return UM()->classes['um\common\secure'];
		}

		/**
		 * @return Shortcodes
		 *
		 * @since 3.0.0
		 */
		public function shortcodes() {
			if ( empty( UM()->classes['um\common\shortcodes'] ) ) {
				UM()->classes['um\common\shortcodes'] = new Shortcodes();
			}

			return UM()->classes['um\common\shortcodes'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Site_Health
		 */
		public function site_health() {
			if ( empty( UM()->classes['um\common\site_health'] ) ) {
				UM()->classes['um\common\site_health'] = new Site_Health();
			}
			return UM()->classes['um\common\site_health'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return Color
		 */
		public static function color() {
			if ( empty( UM()->classes['um\common\color'] ) ) {
				UM()->classes['um\common\color'] = new Color();
			}
			return UM()->classes['um\common\color'];
		}

		/**
		 * @since 2.8.3
		 *
		 * @return Theme
		 */
		public function theme() {
			if ( empty( UM()->classes['um\common\theme'] ) ) {
				UM()->classes['um\common\theme'] = new Theme();
			}
			return UM()->classes['um\common\theme'];
		}

		/**
		 * @since 3.0.0
		 *
		 * @return Uploader
		 */
		public function uploader() {
			if ( empty( UM()->classes['um\common\uploader'] ) ) {
				UM()->classes['um\common\uploader'] = new Uploader();
			}
			return UM()->classes['um\common\uploader'];
		}

		/**
		 * @since 2.8.7
		 *
		 * @return Users
		 */
		public function users() {
			if ( empty( UM()->classes['um\common\users'] ) ) {
				UM()->classes['um\common\users'] = new Users();
			}
			return UM()->classes['um\common\users'];
		}
	}
}
