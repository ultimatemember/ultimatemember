<?php

namespace um\action_scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\action_scheduler\Init' ) ) {

	/**
	 * Class Init
	 *
	 * @package um\action_scheduler
	 */
	class Init {

		public function __construct() {
			$this->email();
		}

		/**
		 * @return Email
		 * @since 2.6.x
		 *
		 */
		public function email() {
			if ( empty( UM()->classes['um\action_scheduler\email'] ) ) {
				UM()->classes['um\action_scheduler\email'] = new Email();
			}

			return UM()->classes['um\action_scheduler\email'];
		}

		/**
		 * @return Proxy
		 * @since 2.6.x
		 *
		 */
		public function proxy() {
			if ( empty( UM()->classes['um\action_scheduler\proxy'] ) ) {
				UM()->classes['um\action_scheduler\proxy'] = new Proxy();
			}

			return UM()->classes['um\action_scheduler\proxy'];
		}
	}
}
