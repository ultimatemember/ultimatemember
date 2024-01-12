<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\ajax\Init' ) ) {

	/**
	 * Class Init
	 *
	 * @package um\ajax
	 */
	class Init {

		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		public function includes() {
			$this->pages();
			$this->secure();
		}

		/**
		 * @since 2.8.3
		 *
		 * @return Pages
		 */
		public function pages() {
			if ( empty( UM()->classes['um\ajax\pages'] ) ) {
				UM()->classes['um\ajax\pages'] = new Pages();
			}
			return UM()->classes['um\ajax\pages'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Secure
		 */
		public function secure() {
			if ( empty( UM()->classes['um\ajax\secure'] ) ) {
				UM()->classes['um\ajax\secure'] = new Secure();
			}
			return UM()->classes['um\ajax\secure'];
		}
	}
}
