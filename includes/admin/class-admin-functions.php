<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Admin_Functions' ) ) {

	/**
	 * Class Admin_Functions
	 * @package um\admin\core
	 */
	class Admin_Functions {

		/**
		 * Check wp-admin nonce
		 *
		 * @param bool $action
		 */
		public function check_ajax_nonce( $action = false ) {
			$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : '';
			$action = empty( $action ) ? 'um-admin-nonce' : $action;

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error( esc_js( __( 'Wrong Nonce', 'ultimate-member' ) ) );
			}
		}

		/**
		 * Boolean check if we're viewing UM backend
		 *
		 * @deprecated 2.8.0
		 *
		 * @return bool
		 */
		public function is_um_screen() {
			_deprecated_function( __METHOD__, '2.8.0', 'UM()->admin()->screen()->is_own_screen()' );
			return UM()->admin()->screen()->is_own_screen();
		}

		/**
		 * Check if current page load UM post type
		 *
		 * @deprecated 2.8.0
		 *
		 * @return bool
		 */
		public function is_plugin_post_type() {
			_deprecated_function( __METHOD__, '2.8.0', 'UM()->admin()->screen()->is_own_post_type()' );
			return UM()->admin()->screen()->is_own_post_type();
		}

		/**
		 * If page now show content with restricted post/taxonomy
		 *
		 * @deprecated 2.8.0
		 *
		 * @return bool
		 */
		public function is_restricted_entity() {
			_deprecated_function( __METHOD__, '2.8.0', 'UM()->admin()->screen()->is_restricted_entity()' );
			return UM()->admin()->screen()->is_restricted_entity();
		}
	}
}
