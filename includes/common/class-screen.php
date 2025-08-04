<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Screen' ) ) {

	/**
	 * Class Screen
	 *
	 * @package um\common
	 */
	class Screen {

		/**
		 * Screen constructor.
		 */
		public function __construct() {
			add_filter( 'body_class', array( &$this, 'remove_admin_bar' ), 1000 );
			add_action( 'send_headers', array( &$this, 'avoid_mobile_cache' ) );
		}

		/**
		 * Remove admin bar classes
		 *
		 * @param array $classes
		 *
		 * @return array
		 */
		public function remove_admin_bar( $classes ) {
			if ( is_user_logged_in() ) {
				if ( um_user( 'can_not_see_adminbar' ) ) {
					$search = array_search( 'admin-bar', $classes, true );
					if ( ! empty( $search ) ) {
						unset( $classes[ $search ] );
					}
				}
			}

			return $classes;
		}

		/**
		 * Avoid caching for mobile devices on specific pages.
		 */
		public function avoid_mobile_cache() {
			if ( ! wp_is_mobile() ) {
				return;
			}

			if ( um_is_predefined_page( 'login' ) || um_is_predefined_page( 'register' ) || um_is_predefined_page( 'account' ) ) {
				header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
				header( 'Cache-Control: post-check=0, pre-check=0', false );
				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );
			}
		}
	}
}
