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
		 * Check wp-admin nonce
		 *
		 * @param bool $action
		 */
		function check_ajax_nonce( $action = false ) {
			$nonce = isset( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : '';
			$action = empty( $action ) ? 'um-admin-nonce' : $action;

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error( esc_js( __( 'Wrong Nonce', 'ultimate-member' ) ) );
			}
		}


		/**
		 * Boolean check if we're viewing UM backend
		 *
		 * @return bool
		 */
		function is_um_screen() {
			global $current_screen;
			$screen_id = $current_screen->id;

			$is_um_screen = false;

			if ( strstr( $screen_id, 'ultimatemember') ||
			     strstr( $screen_id, 'um_') ||
			     strstr( $screen_id, 'user' ) ||
			     strstr( $screen_id, 'profile' ) ||
			     $screen_id == 'nav-menus' ) {
				$is_um_screen = true;
			}

			if ( $this->is_plugin_post_type() ) {
				$is_um_screen = true;
			}

			if ( $this->is_restricted_entity() ) {
				$is_um_screen = true;
			}

			return apply_filters( 'um_is_ultimatememeber_admin_screen', $is_um_screen );
		}


		/**
		 * Check if current page load UM post type
		 *
		 * @return bool
		 */
		function is_plugin_post_type() {
			$cpt = UM()->cpt_list();

			if ( isset( $_REQUEST['post_type'] ) ) {
				$post_type = sanitize_key( $_REQUEST['post_type'] );
				if ( in_array( $post_type, $cpt ) ) {
					return true;
				}
			} elseif ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit' ) {
				$post_type = get_post_type();
				if ( in_array( $post_type, $cpt ) ) {
					return true;
				}
			}

			return false;
		}


		/**
		 * If page now show content with restricted post/taxonomy
		 *
		 * @return bool
		 */
		function is_restricted_entity() {
			$restricted_posts = UM()->options()->get( 'restricted_access_post_metabox' );
			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );

			global $typenow, $taxnow;
			if ( ! empty( $typenow ) && ! empty( $restricted_posts[ $typenow ] ) ) {
				return true;
			}

			if ( ! empty( $taxnow ) && ! empty( $restricted_taxonomies[ $taxnow ] ) ) {
				return true;
			}

			return false;
		}
	}
}