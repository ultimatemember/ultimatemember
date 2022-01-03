<?php namespace um\frontend;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\frontend\Common' ) ) {


	/**
	 * Class Common
	 *
	 * @package um\frontend
	 */
	class Common {


		/**
		 * Common constructor.
		 */
		function __construct() {
		}


		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @since 3.0
		 *
		 * @used-by \UM::includes()
		 */
		function includes() {
			$this->actions_listener();
			$this->enqueue();
		}


		/**
		 * @since 3.0
		 *
		 * @return Enqueue
		 */
		function enqueue() {
			if ( empty( UM()->classes['um\frontend\enqueue'] ) ) {
				UM()->classes['um\frontend\enqueue'] = new Enqueue();
			}

			return UM()->classes['um\frontend\enqueue'];
		}


		/**
		 * @since 2.0
		 *
		 * @return Actions_Listener
		 */
		function actions_listener() {
			if ( empty( UM()->classes['um\frontend\actions_listener'] ) ) {
				UM()->classes['um\frontend\actions_listener'] = new Actions_Listener();
			}
			return UM()->classes['um\frontend\actions_listener'];
		}

	}
}
