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
			$this->account();
			if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
				$this->dev();
				$this->directory();
			}
			$this->files();
			$this->forms();
			$this->pages();
			$this->secure();
			$this->user();
		}

		/**
		 * @since 2.9.0
		 *
		 * @return Account
		 */
		public function account() {
			if ( empty( UM()->classes['um\ajax\account'] ) ) {
				UM()->classes['um\ajax\account'] = new Account();
			}
			return UM()->classes['um\ajax\account'];
		}

		/**
		 * @since 2.9.0
		 *
		 * @return Dev
		 */
		public function dev() {
			if ( empty( UM()->classes['um\ajax\dev'] ) ) {
				UM()->classes['um\ajax\dev'] = new Dev();
			}

			return UM()->classes['um\ajax\dev'];
		}

		/**
		 * @since 2.9.0
		 *
		 * @return Directory|Directory_Meta
		 */
		public function directory() {
			if ( empty( UM()->classes['um\ajax\directory'] ) ) {
				if ( UM()->options()->get( 'member_directory_own_table' ) ) {
					UM()->classes['um\ajax\directory'] = new Directory_Meta();
				} else {
					UM()->classes['um\ajax\directory'] = new Directory();
				}
			}

			return UM()->classes['um\ajax\directory'];
		}

		/**
		 * @since 2.8.6
		 *
		 * @return Forms
		 */
		public function forms() {
			if ( empty( UM()->classes['um\ajax\forms'] ) ) {
				UM()->classes['um\ajax\forms'] = new Forms();
			}
			return UM()->classes['um\ajax\forms'];
		}

		/**
		 * @since 2.9.0
		 *
		 * @return Files
		 */
		public function files() {
			if ( empty( UM()->classes['um\ajax\files'] ) ) {
				UM()->classes['um\ajax\files'] = new Files();
			}
			return UM()->classes['um\ajax\files'];
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

		/**
		 * @since 2.8.4
		 *
		 * @return User
		 */
		public function user() {
			if ( empty( UM()->classes['um\ajax\user'] ) ) {
				UM()->classes['um\ajax\user'] = new User();
			}
			return UM()->classes['um\ajax\user'];
		}

		public function esc_html_spaces( $html ) {
			$html = preg_replace(
				array( '/^\s+/im', '/\\r\\n/im', '/\\n/im', '/\\t+/im' ),
				array( '', ' ', ' ', ' ' ),
				$html
			);

			return $html;
		}
	}
}
