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
			$this->secure();
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
