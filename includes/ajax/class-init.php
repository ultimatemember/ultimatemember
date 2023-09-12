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
			$this->field_group();
			$this->secure();
		}

		/**
		 * Check nonce
		 *
		 * @param bool|string $action
		 *
		 * @since 2.7.0
		 */
		public function check_nonce( $action = false ) {
			$nonce  = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : '';
			$action = empty( $action ) ? 'um-common-nonce' : $action;

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error( __( 'Wrong AJAX Nonce', 'ultimate-member' ) );
			}
		}

		/**
		 * @since 2.7.0
		 *
		 * @return Field_Group
		 */
		public function field_group() {
			if ( empty( UM()->classes['um\ajax\field_group'] ) ) {
				UM()->classes['um\ajax\field_group'] = new Field_Group();
			}
			return UM()->classes['um\ajax\field_group'];
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
