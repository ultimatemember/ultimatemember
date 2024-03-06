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
			$this->enqueue();
			$this->secure();
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
		 * @since 2.8.4
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
		 * @since 2.8.4
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
	}
}
