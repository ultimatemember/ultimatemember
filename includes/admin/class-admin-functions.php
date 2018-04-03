<?php
namespace um\admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\admin\Admin_Functions' ) ) {


	/**
	 * Class Admin_Functions
	 * @package um\admin\core
	 */
	class Admin_Functions {


		/**
		 * Admin_Functions constructor.
		 */
		function __construct() {

		}


		/**
		 * Boolean check if we're viewing UM backend
		 *
		 * @todo global for all admin classes
		 * @return bool
		 */
		function is_UM_admin_screen() {
			global $current_screen;
			$screen_id = $current_screen->id;
			if ( is_admin() && ( strstr( $screen_id, 'ultimatemember') || strstr( $screen_id, 'um_') || strstr($screen_id, 'user') || strstr($screen_id, 'profile') ) )
				return true;
			return false;
		}


		/**
		 * Check if current page load UM post type
		 *
		 * @return bool
		 */
		function is_plugin_post_type() {
			if ( isset( $_REQUEST['post_type'] ) ) {
				$post_type = $_REQUEST['post_type'];
				if ( in_array( $post_type, array( 'um_form','um_directory' ) ) ) {
					return true;
				}
			} elseif ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) {
				$post_type = get_post_type();
				if ( in_array( $post_type, array( 'um_form', 'um_directory' ) ) ) {
					return true;
				}
			}

			return false;
		}
	}
}