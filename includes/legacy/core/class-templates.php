<?php
namespace um\core;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'Templates' ) ) {


	/**
	 * Class Templates
	 * @package um\core
	 */
	class Templates {

		function __construct() {

		}


		/**
		 * Get template path
		 *
		 *
		 * @param $slug
		 * @return string
		 */
		function get_template( $slug ) {
			$file_list = um_path . "templates/{$slug}.php";
			$theme_file = get_stylesheet_directory() . "/ultimate-member/templates/{$slug}.php";

			if ( file_exists( $theme_file ) ) {
				$file_list = $theme_file;
			}

			return $file_list;
		}
	}
}