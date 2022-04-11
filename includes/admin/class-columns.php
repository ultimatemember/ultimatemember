<?php
namespace um\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\Columns' ) ) {


	/**
	 * Class Columns
	 * @package um\admin
	 */
	class Columns {


		/**
		 * Columns constructor.
		 */
		function __construct() {

			add_filter( 'manage_edit-um_form_columns', array( &$this, 'manage_edit_um_form_columns' ) );
			add_action( 'manage_um_form_posts_custom_column', array( &$this, 'manage_um_form_posts_custom_column' ), 10, 3 );

			add_filter( 'post_row_actions', array( &$this, 'post_row_actions' ), 99, 2 );

			// Add a post display state for special UM pages.
			add_filter( 'display_post_states', array( &$this, 'add_display_post_states' ), 10, 2 );

			add_filter( 'post_row_actions', array( &$this, 'remove_bulk_actions_um_form_inline' ) );

			add_filter( 'manage_users_columns', array( &$this, 'manage_users_columns' ) );

			add_filter( 'manage_users_custom_column', array( &$this, 'manage_users_custom_column' ), 10, 3 );

			$prefix = is_network_admin() ? 'network_admin_' : '';
			add_filter( "{$prefix}plugin_action_links_" . um_plugin, array( &$this, 'plugin_links' ) );
		}


		/**
		 * Filter: Add column 'Status'
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		public function manage_users_columns( $columns ) {
			$columns['account_status'] = __( 'Status', 'ultimate-member' );
			return $columns;
		}


		/**
		 * Filter: Show column 'Status'
		 *
		 * @param string $val
		 * @param string $column_name
		 * @param int $user_id
		 *
		 * @return string
		 */
		public function manage_users_custom_column( $val, $column_name, $user_id ) {
			if ( $column_name == 'account_status' ) {
				um_fetch_user( $user_id );
				$value = um_user( 'account_status_name' );
				um_reset_user();
				return $value;
			}
			return $val;
		}


		/**
		 * This will remove the "Edit" bulk action, which is actually quick edit.
		 *
		 * @param array $actions
		 *
		 * @return array;
		 */
		function remove_bulk_actions_um_form_inline( $actions ) {
			if ( UM()->admin()->is_own_post_type() ) {
				unset( $actions['inline hide-if-no-js'] );
				return $actions;
			}
			return $actions;
		}


		/**
		 * Custom row actions
		 *
		 * @param array $actions
		 * @param \WP_Post $post
		 *
		 * @return mixed
		 */
		function post_row_actions( $actions, $post ) {
			//check for your post type
			if ( $post->post_type == "um_form" ) {
				$actions['um_duplicate'] = '<a href="' . esc_url( $this->duplicate_uri( $post->ID ) ) . '">' . __( 'Duplicate', 'ultimate-member' ) . '</a>';
			}
			return $actions;
		}


		/**
		 * Duplicate a form
		 *
		 * @param int $id
		 *
		 * @return string
		 */
		function duplicate_uri( $id ) {
			$url = add_query_arg('um_adm_action', 'duplicate_form', admin_url('edit.php?post_type=um_form') );
			$url = add_query_arg('post_id', $id, $url);
			return $url;
		}


		/**
		 * Custom columns for Form
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		function manage_edit_um_form_columns( $columns ) {
			$new_columns['cb'] = '<input type="checkbox" />';
			$new_columns['title'] = __( 'Title', 'ulitmate-member' );
			$new_columns['id'] = __('ID', 'ulitmate-member' );
			$new_columns['mode'] = __( 'Type', 'ulitmate-member' );
			$new_columns['is_default'] = __( 'Default', 'ulitmate-member' );
			$new_columns['shortcode'] = __( 'Shortcode', 'ulitmate-member' );
			$new_columns['date'] = __( 'Date', 'ulitmate-member' );

			return $new_columns;
		}


		/**
		 * Display custom columns for Form
		 *
		 * @param string $column_name
		 * @param int $id
		 */
		function manage_um_form_posts_custom_column( $column_name, $id ) {
			switch ( $column_name ) {
				case 'id':
					echo '<span class="um-admin-number">'.$id.'</span>';
					break;

				case 'shortcode':
					$is_default = UM()->query()->get_attr( 'is_default', $id );

					if ( $is_default ) {
						echo UM()->shortcodes()->get_default_shortcode( $id );
					} else {
						echo UM()->shortcodes()->get_shortcode( $id );
					}

					break;

				case 'is_default':
					$is_default = UM()->query()->get_attr( 'is_default', $id );
					echo empty( $is_default ) ? __( 'No', 'ultimate-member' ) : __( 'Yes', 'ultimate-member' );
					break;

				case 'mode':
					$mode = UM()->query()->get_attr( 'mode', $id );
					echo UM()->form()->display_form_type( $mode, $id );
					break;
			}
		}


		/**
		 * Add a post display state for special UM pages in the page list table.
		 *
		 * @param array $post_states An array of post display states.
		 * @param \WP_Post $post The current post object.
		 *
		 * @return mixed
		 */
		public function add_display_post_states( $post_states, $post ) {
			foreach ( UM()->config()->get( 'predefined_pages' ) as $slug => $data ) {
				if ( um_is_predefined_page( $slug, $post ) ) {
					/* translators: %s: UM predefined page title */
					$post_states[ 'um_predefined_page_' . $slug ] = sprintf( __( 'UM %s', 'ultimate-member' ), $data['title'] );
				}
			}

			return $post_states;
		}


		/**
		 * Add custom links to plugin page
		 *
		 * @param array $links
		 *
		 * @return array
		 */
		function plugin_links( $links ) {
			$more_links[] = '<a href="http://docs.ultimatemember.com/">' . __( 'Docs', 'ultimate-member' ) . '</a>';
			$more_links[] = '<a href="'.admin_url().'admin.php?page=um_options">' . __( 'Settings', 'ultimate-member' ) . '</a>';

			$links = $more_links + $links;
			return $links;
		}

	}
}
