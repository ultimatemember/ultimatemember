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
			add_filter( 'body_class', array( &$this, 'remove_admin_bar' ), 1000, 1 );
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
	}
}
