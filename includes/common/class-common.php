<?php
namespace um\common;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\common\Common' ) ) {


	/**
	 * Class Common
	 * @package um\common
	 */
	class Common {


		/**
		 * Common constructor.
		 */
		function __construct() {

		}


		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \FMWP::includes()
		 */
		function includes() {

		}


	}
}
