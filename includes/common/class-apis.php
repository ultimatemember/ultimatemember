<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\APIs' ) ) {

	/**
	 * Class APIs
	 *
	 * @package um\common
	 *
	 * @since 2.9.3
	 */
	class APIs {

		/**
		 * @param string $api API identifier
		 *
		 * @return false
		 */
		public static function has_api( $api ) {
			$exists = false;
			return apply_filters( 'um_has_api', $exists, $api );
		}

		/**
		 * @param string $api API identifier
		 *
		 * @return false
		 */
		public static function is_active( $api ) {
			$active = false;
			return apply_filters( 'um_api_is_active', $active, $api );
		}
	}
}
