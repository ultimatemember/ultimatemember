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
			add_action( 'transition_post_status', array( &$this, 'change_default_form' ), 10, 3 );
		}

		/**
		 * Create taxonomies for use for UM
		 */
		public function create_post_types() {
			register_post_type(
				'um_form',
				array(
					'labels'       => array(
						'name'               => __( 'Forms', 'ultimate-member' ),
						'singular_name'      => __( 'Form', 'ultimate-member' ),
						'add_new'            => __( 'Add New', 'ultimate-member' ),
						'add_new_item'       => __( 'Add New Form', 'ultimate-member' ),
						'edit_item'          => __( 'Edit Form', 'ultimate-member' ),
						'not_found'          => __( 'You did not create any forms yet', 'ultimate-member' ),
						'not_found_in_trash' => __( 'Nothing found in Trash', 'ultimate-member' ),
						'search_items'       => __( 'Search Forms', 'ultimate-member' ),
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

			if ( UM()->options()->get( 'members_page' ) ) {
				register_post_type(
					'um_directory',
					array(
						'labels'       => array(
							'name'               => __( 'Member Directories', 'ultimate-member' ),
							'singular_name'      => __( 'Member Directory', 'ultimate-member' ),
							'add_new'            => __( 'Add New', 'ultimate-member' ),
							'add_new_item'       => __( 'Add New Member Directory', 'ultimate-member' ),
							'edit_item'          => __( 'Edit Member Directory', 'ultimate-member' ),
							'not_found'          => __( 'You did not create any member directories yet', 'ultimate-member' ),
							'not_found_in_trash' => __( 'Nothing found in Trash', 'ultimate-member' ),
							'search_items'       => __( 'Search Member Directories', 'ultimate-member' ),
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

		/**
		 * @since 2.8.0
		 * @return array
		 */
		public function get_list() {
			$cpt_list = array(
				'um_form',
			);
			if ( UM()->options()->get( 'members_page' ) ) {
				$cpt_list[] = 'um_directory';
			}
			/**
			 * Filters registered CPT in Ultimate Member.
			 *
			 * @since 2.0
			 * @hook um_cpt_list
			 *
			 * @param {array} $cpt_list CPT keys.
			 *
			 * @return {array} CPT keys.
			 *
			 * @example <caption>Add `my_cpt` CPT to UM CPT list.</caption>
			 * function um_custom_cpt_list( $cpt_list ) {
			 *     $cpt_list[] = '{my_cpt}';
			 *     return $cpt_list;
			 * }
			 * add_filter( 'um_cpt_list', 'um_custom_cpt_list' );
			 */
			return apply_filters( 'um_cpt_list', $cpt_list );
		}

		/**
		 * @param null|string $post_type
		 *
		 * @since 2.8.0
		 *
		 * @return array
		 */
		public function get_taxonomies_list( $post_type = null ) {
			$taxonomies = apply_filters( 'um_cpt_taxonomies_list', array() );

			if ( isset( $post_type ) ) {
				$taxonomies = array_key_exists( $post_type, $taxonomies ) ? $taxonomies[ $post_type ] : array();
			}
			return $taxonomies;
		}

		/**
		 * Update default forms IDs for predefined page installation on the forms or directory posts status update.
		 *
		 * @since 2.8.6
		 *
		 * @param string $new_status New post status
		 * @param string $old_status Old post status
		 * @param object $post       Post object
		 */
		public function change_default_form( $new_status, $old_status, $post ) {
			if ( ! in_array( $post->post_type, array( 'um_form', 'um_directory' ), true ) ) {
				return;
			}

			$key      = 'um_form' === $post->post_type ? 'um_core_forms' : 'um_core_directories';
			$core_ids = get_option( $key, array() );
			$post_id  = $post->ID;

			if ( 'publish' === $new_status ) {
				$mode = 'members';
				if ( 'um_form' === $post->post_type ) {
					$meta_value_mode = get_post_meta( $post_id, '_um_mode', true );
					if ( ! empty( $meta_value_mode ) ) {
						$mode = $meta_value_mode;
					} elseif ( ! empty( $_POST['form']['_um_mode'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- already verified here
						$mode = sanitize_key( $_POST['form']['_um_mode'] ); // phpcs:ignore WordPress.Security.NonceVerification -- already verified here
					}
				}

				// $mode can be empty on the first installation
				if ( empty( $mode ) ) {
					return;
				}

				// Set current published form or member directory as predefined in the case when old default doesn't exist or hasn't a `publish` status.
				if ( empty( $core_ids[ $mode ] ) || 'publish' !== get_post_status( $core_ids[ $mode ] ) ) {
					$core_ids[ $mode ] = $post_id;

					/**
					 * Filters Ultimate Member default forms IDs.
					 *
					 * @param {array}  $core_ids   Default form IDs.
					 * @param {object} $post       WP_Post object.
					 * @param {string} $new_status New status. 'publish|trash'
					 *
					 * @return {array} Default forms IDs.
					 *
					 * @since 2.8.6
					 * @hook um_change_default_forms_ids
					 *
					 * @example <caption>Set default profile form ID as 1.</caption>
					 * function my_um_change_default_forms_ids( $core_forms, $post, $new_status ) {
					 *     // your code here
					 *     $core_forms['profile'] = 1;
					 *     return $core_forms;
					 * }
					 * add_filter( 'um_change_default_forms_ids', 'my_um_change_default_forms_ids', 10, 3 );
					 */
					$core_ids = apply_filters( 'um_change_default_forms_ids', $core_ids, $post, $new_status );
					update_option( $key, $core_ids );
				}
			} elseif ( 'trash' === $new_status ) {
				$mode = 'members';
				if ( 'um_form' === $post->post_type ) {
					$meta_value_mode = get_post_meta( $post_id, '_um_mode', true );
					$mode            = $meta_value_mode;
				}

				if ( absint( $post_id ) === absint( $core_ids[ $mode ] ) ) {
					// Find the first publish form or directory to set it as default for predefined page.
					$args = array(
						'post_type'      => $post->post_type,
						'meta_key'       => '_um_mode',
						'meta_value'     => 'members' !== $mode ? $mode : 'directory',
						'posts_per_page' => 1,
						'orderby'        => 'date',
						'post_status'    => 'publish',
						'order'          => 'DESC',
						'fields'         => 'ids',
						'post__not_in'   => array( $post_id ),
					);

					$forms = get_posts( $args );
					if ( ! empty( $forms ) ) {
						$new_post_id       = $forms[0];
						$core_ids[ $mode ] = $new_post_id;

						/** This filter is documented in includes/common/class-cpt.php */
						$core_ids = apply_filters( 'um_change_default_forms_ids', $core_ids, $post, $new_status );
						update_option( $key, $core_ids );
					}
				}
			}
		}
	}
}
