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

		private static $api_map = array(
			'google-maps' => 'Google_Maps_Api',
		);

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

		/**
		 * @param string $api API identifier
		 *
		 * @return null|apis\Google_Maps_Api
		 */
		public function get( $api ) {
			if ( ! array_key_exists( $api, self::$api_map ) ) {
				return null;
			}

			if ( empty( UM()->classes[ "um\common\apis\\$api" ] ) ) {
				$class = '\um\common\apis\\' . self::$api_map[ $api ];

				UM()->classes[ "um\common\apis\\$api" ] = new $class();
			}
			return UM()->classes[ "um\common\apis\\$api" ];
		}
	}
}
