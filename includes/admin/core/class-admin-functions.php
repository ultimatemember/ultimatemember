<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\admin\core\Admin_Functions' ) ) {


	/**
	 * Class Admin_Functions
	 * @package um\admin\core
	 */
	class Admin_Functions {


		/**
		 * Admin_Functions constructor.
		 */
		function __construct() {
			add_action( 'parent_file', array( &$this, 'parent_file' ), 9 );
			add_filter( 'gettext', array( &$this, 'gettext' ), 10, 4 );
			add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		}


		/**
		 * Updated post messages
		 *
		 * @param array $messages
		 *
		 * @return array
		 */
		function post_updated_messages( $messages ) {
			global $post_ID;

			$post_type = get_post_type( $post_ID );

			if ( $post_type == 'um_form' ) {
				$messages['um_form'] = array(
					0   => '',
					1   => __( 'Form updated.', 'ultimate-member' ),
					2   => __( 'Custom field updated.', 'ultimate-member' ),
					3   => __( 'Custom field deleted.', 'ultimate-member' ),
					4   => __( 'Form updated.', 'ultimate-member' ),
					5   => isset( $_GET['revision'] ) ? __( 'Form restored to revision.', 'ultimate-member' ) : false,
					6   => __( 'Form created.', 'ultimate-member' ),
					7   => __( 'Form saved.', 'ultimate-member' ),
					8   => __( 'Form submitted.', 'ultimate-member' ),
					9   => __( 'Form scheduled.', 'ultimate-member' ),
					10  => __( 'Form draft updated.', 'ultimate-member' ),
				);
			}

			return $messages;
		}


		/**
		 * Gettext filters
		 *
		 * @param $translation
		 * @param $text
		 * @param $domain
		 *
		 * @return string
		 */
		function gettext( $translation, $text, $domain ) {
			global $post;
			if ( isset( $post->post_type ) && UM()->admin()->is_plugin_post_type() ) {
				$translations = get_translations_for_domain( $domain );
				if ( $text == 'Publish' ) {
					return $translations->translate( 'Create' );
				} elseif ( $text == 'Move to Trash' ) {
					return $translations->translate( 'Delete' );
				}
			}

			return $translation;
		}


		/**
		 * Fix parent file for correct highlighting
		 *
		 * @param $parent_file
		 *
		 * @return string
		 */
		function parent_file( $parent_file ) {
			global $current_screen;
			$screen_id = $current_screen->id;
			if ( strstr( $screen_id, 'um_' ) ) {
				$parent_file = 'ultimatemember';
			}
			return $parent_file;
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
	}
}