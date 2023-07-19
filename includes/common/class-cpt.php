<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\CPT' ) ) {

	/**
	 * Class CPT
	 *
	 * @package um\common
	 *
	 * @since 2.6.8
	 */
	class CPT {

		public function hooks() {
			add_action( 'init', array( &$this, 'create_post_types' ), 1 );
		}

		/**
		 * Create taxonomies for use for UM
		 */
		public function create_post_types() {
			register_post_type(
				'um_form',
				array(
					'labels'        => array(
						'name'                  => __( 'Forms', 'ultimate-member' ),
						'singular_name'         => __( 'Form', 'ultimate-member' ),
						'add_new'               => __( 'Add New', 'ultimate-member' ),
						'add_new_item'          => __( 'Add New Form', 'ultimate-member' ),
						'edit_item'             => __( 'Edit Form', 'ultimate-member' ),
						'not_found'             => __( 'You did not create any forms yet', 'ultimate-member' ),
						'not_found_in_trash'    => __( 'Nothing found in Trash', 'ultimate-member' ),
						'search_items'          => __( 'Search Forms', 'ultimate-member' ),
					),
					'capabilities'  => array(
						'edit_post'          => 'manage_options',
						'read_post'          => 'manage_options',
						'delete_post'        => 'manage_options',
						'edit_posts'         => 'manage_options',
						'edit_others_posts'  => 'manage_options',
						'delete_posts'       => 'manage_options',
						'publish_posts'      => 'manage_options',
						'read_private_posts' => 'manage_options',
					),
					'show_ui'       => true,
					'show_in_menu'  => false,
					'public'        => false,
					'show_in_rest'  => true,
					'supports'      => array( 'title' ),
				)
			);

			if ( UM()->options()->get( 'members_page' ) ) {
				register_post_type(
					'um_directory',
					array(
						'labels'        => array(
							'name'                  => __( 'Member Directories', 'ultimate-member' ),
							'singular_name'         => __( 'Member Directory', 'ultimate-member' ),
							'add_new'               => __( 'Add New', 'ultimate-member' ),
							'add_new_item'          => __( 'Add New Member Directory', 'ultimate-member' ),
							'edit_item'             => __( 'Edit Member Directory', 'ultimate-member' ),
							'not_found'             => __( 'You did not create any member directories yet', 'ultimate-member' ),
							'not_found_in_trash'    => __( 'Nothing found in Trash', 'ultimate-member' ),
							'search_items'          => __( 'Search Member Directories', 'ultimate-member' ),
						),
						'capabilities'  => array(
							'edit_post'          => 'manage_options',
							'read_post'          => 'manage_options',
							'delete_post'        => 'manage_options',
							'edit_posts'         => 'manage_options',
							'edit_others_posts'  => 'manage_options',
							'delete_posts'       => 'manage_options',
							'publish_posts'      => 'manage_options',
							'read_private_posts' => 'manage_options',
						),
						'show_ui'       => true,
						'show_in_menu'  => false,
						'public'        => false,
						'show_in_rest'  => true,
						'supports'      => array( 'title' ),
					)
				);
			}
		}
	}
}
