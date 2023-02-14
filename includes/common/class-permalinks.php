<?php
namespace um\common;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Permalinks' ) ) {

	/**
	 * Class Permalinks.
	 *
	 * @package um\common
	 */
	class Permalinks {

		/**
		 * Permalinks constructor.
		 */
		public function __construct() {
		}

		/**
		 * Get current URL anywhere
		 *
		 * @param bool $no_query_params
		 *
		 * @return string
		 */
		public function get_current_url( $no_query_params = false ) {
			//use WP native function for fill $_SERVER variables by correct values
			wp_fix_server_vars();

			//check if WP-CLI there isn't set HTTP_HOST, use localhost instead
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'localhost';
			} else{
				if ( isset( $_SERVER['HTTP_HOST'] ) ) {
					$host = $_SERVER['HTTP_HOST'];
				} else {
					$host = 'localhost';
				}
			}

			$page_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $_SERVER['REQUEST_URI'];

			if ( true === $no_query_params ) {
				$page_url = strtok( $page_url, '?' );
			}

			/**
			 * Filters the current page URL.
			 *
			 * @since 3.0
			 * @hook um_get_current_page_url
			 *
			 * @param {string} $page_url Current page URL.
			 *
			 * @return {string} Filtered current page URL.
			 */
			return apply_filters( 'um_get_current_page_url', $page_url );
		}
	}
}
