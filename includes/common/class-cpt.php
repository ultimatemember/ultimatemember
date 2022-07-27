<?php namespace um\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\common\CPT' ) ) {


	/**
	 * Class CPT
	 *
	 * @package um\common
	 */
	class CPT {


		/**
		 * CPT constructor.
		 *
		 * @since 3.0
		 */
		public function __construct() {
		}


		public function hooks() {
			add_action( 'init', array( &$this, 'create_post_types' ), 1 );
		}


		/**
		 * Create UM's CPT
		 */
		public function create_post_types() {
			register_post_type(
				'um_form',
				array(
					'labels'       => array(
						'name'                     => __( 'Forms', 'ultimate-member' ),
						'singular_name'            => __( 'Form', 'ultimate-member' ),
						'add_new'                  => __( 'Add New', 'ultimate-member' ),
						'add_new_item'             => __( 'Add New Form', 'ultimate-member' ),
						'edit_item'                => __( 'Edit Form', 'ultimate-member' ),
						'not_found'                => __( 'You did not create any forms yet', 'ultimate-member' ),
						'not_found_in_trash'       => __( 'Nothing forms found in Trash', 'ultimate-member' ),
						'search_items'             => __( 'Search Forms', 'ultimate-member' ),
						'new_item'                 => __( 'New Form', 'ultimate-member' ),
						'view_item'                => __( 'View Form', 'ultimate-member' ),
						'insert_into_item'         => __( 'Insert into Form', 'ultimate-member' ),
						'uploaded_to_this_item'    => __( 'Uploaded to this Form', 'ultimate-member' ),
						'filter_items_list'        => __( 'Filter Forms List', 'ultimate-member' ),
						'items_list_navigation'    => __( 'Forms List Navigation', 'ultimate-member' ),
						'items_list'               => __( 'Forms List', 'ultimate-member' ),
						'view_items'               => __( 'View Forms', 'ultimate-member' ),
						'attributes'               => __( 'Form Attributes', 'ultimate-member' ),
						'item_updated'             => __( 'Form updated.', 'ultimate-member' ),
						'item_published'           => __( 'Form created.', 'ultimate-member' ),
						'item_published_privately' => __( 'Form created privately.', 'ultimate-member' ),
						'item_reverted_to_draft'   => __( 'Form reverted to draft.', 'ultimate-member' ),
						'item_scheduled'           => __( 'Form scheduled.', 'ultimate-member' ),
					),
					'capabilities' => array(
						'edit_post'          => 'manage_options',
						'read_post'          => 'manage_options',
						'delete_post'        => 'manage_options',
						'edit_posts'         => 'manage_options',
						'edit_others_posts'  => 'manage_options',
						'delete_posts'       => 'manage_options',
						'publish_posts'      => 'manage_options',
						'read_private_posts' => 'manage_options',
					),
					'show_ui'      => true,
					'show_in_menu' => false,
					'public'       => false,
					'show_in_rest' => true,
					'supports'     => array( 'title' ),
				)
			);
		}


		/**
		 * @return array
		 */
		public function get_list() {
			$cpt = apply_filters( 'um_cpt_list', array( 'um_form' ) );
			return $cpt;
		}


		/**
		 * @param null|string $post_type
		 * @return array
		 */
		public function get_taxonomies_list( $post_type = null ) {
			$taxonomies = apply_filters( 'um_cpt_taxonomies_list', array() );

			if ( isset( $post_type ) ) {
				$taxonomies = array_key_exists( $post_type, $taxonomies ) ? $taxonomies[ $post_type ] : array();
			}
			return $taxonomies;
		}
	}
}
