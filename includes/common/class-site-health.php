<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Site_Health' ) ) {

	/**
	 * Class Site_Health
	 *
	 * @package um\common
	 *
	 * @since 2.6.8
	 */
	class Site_Health {

		/**
		 * Site_Health constructor.
		 *
		 * @since 2.6.8
		 */
		public function __construct() {
			add_filter( 'site_status_test_php_modules', array( $this, 'add_required_modules' ) );
		}

		/**
		 * Extends required PHP libraries.
		 *
		 * @param array $modules
		 *
		 * @return array
		 */
		public function add_required_modules( $modules ) {
			$modules['mbstring']['required'] = true;
			$modules['exif']['required']     = true;
			$modules['iconv']['required']    = true;
			return $modules;
		}
	}
}
