<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Init' ) ) {


	/**
	 * Class Init
	 *
	 * @package um\common
	 */
	class Init {


		/**
		 * Init constructor.
		 */
		function __construct() {
			// loading modules when UM core is loaded
			add_action( 'um_core_loaded', array( UM()->modules(), 'load_modules' ), 1 );

			add_action( 'init', array( &$this, 'create_post_types' ), 1 );

			add_filter( 'body_class', array( &$this, 'remove_admin_bar' ), 1000, 1 );
		}


		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		function includes() {

		}


		/**
		 * @since 3.0
		 *
		 * @return Field()
		 */
		function field() {
			if ( empty( UM()->classes['um\common\field'] ) ) {
				UM()->classes['um\common\field'] = new Field();
			}
			return UM()->classes['um\common\field'];
		}


		/**
		 * @since 3.0
		 *
		 * @return User()
		 */
		function user() {
			if ( empty( UM()->classes['um\common\user'] ) ) {
				UM()->classes['um\common\user'] = new User();
			}
			return UM()->classes['um\common\user'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Filesystem()
		 */
		function filesystem() {
			if ( empty( UM()->classes['um\common\filesystem'] ) ) {
				UM()->classes['um\common\filesystem'] = new Filesystem();
			}
			return UM()->classes['um\common\filesystem'];
		}


		/**
		 * Remove admin bar classes
		 *
		 * @param array $classes
		 *
		 * @return array
		 */
		function remove_admin_bar( $classes ) {

			if ( is_user_logged_in() ) {
				if ( um_user( 'can_not_see_adminbar' ) ) {
					$search = array_search( 'admin-bar', $classes );
					if ( ! empty( $search ) ) {
						unset( $classes[ $search ] );
					}
				}
			}

			return $classes;
		}


		/**
		 * Create UM's CPT
		 */
		function create_post_types() {
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
	}
}
