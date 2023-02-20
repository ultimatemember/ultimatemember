<?php namespace um\frontend;

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
		 * Init constructor.
		 */
		public function __construct() {
		}

		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @since 3.0
		 *
		 * @used-by \UM::includes()
		 */
		public function includes() {
			$this->actions_listener();
			$this->enqueue();
			$this->forms();
			$this->account();
			$this->user()->hooks();
		}

		/**
		 * @since 3.0
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
		 * @since 3.0
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
		 * @since 3.0
		 *
		 * @return User
		 */
		public function user() {
			if ( empty( UM()->classes['um\frontend\user'] ) ) {
				UM()->classes['um\frontend\user'] = new User();
			}

			return UM()->classes['um\frontend\user'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Forms
		 */
		public function forms() {
			if ( empty( UM()->classes['um\frontend\forms'] ) ) {
				UM()->classes['um\frontend\forms'] = new Forms();
			}

			return UM()->classes['um\frontend\forms'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Account
		 */
		public function account() {
			if ( empty( UM()->classes['um\frontend\account'] ) ) {
				UM()->classes['um\frontend\account'] = new Account();
			}

			return UM()->classes['um\frontend\account'];
		}

		/**
		 * @since 3.0
		 *
		 * @param array|bool $data
		 *
		 * @return Form
		 */
		public function form( $data = false ) {
			if ( empty( UM()->classes[ 'um\frontend\form' . $data['id'] ] ) ) {
				UM()->classes[ 'um\frontend\form' . $data['id'] ] = new Form( $data );
			}

			return UM()->classes[ 'um\frontend\form' . $data['id'] ];
		}
	}
}
