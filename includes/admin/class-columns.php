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
		public function __construct() {
			add_filter( 'manage_edit-um_form_columns', array( &$this, 'manage_edit_um_form_columns' ) );
			add_action( 'manage_um_form_posts_custom_column', array( &$this, 'manage_um_form_posts_custom_column' ), 10, 3 );

			add_filter( 'post_row_actions', array( &$this, 'post_row_actions' ), 99, 2 );

			// Add a post display state for special UM pages.
			add_filter( 'display_post_states', array( &$this, 'add_display_post_states' ), 10, 2 );

			add_filter( 'post_row_actions', array( &$this, 'remove_quick_edit_row_action' ) );
			add_action( 'load-edit.php', array( &$this, 'posts_page' ) );

			add_filter( 'manage_users_columns', array( &$this, 'manage_users_columns' ) );
			add_filter( 'manage_users_custom_column', array( &$this, 'manage_users_custom_column' ), 10, 3 );

			$prefix = is_network_admin() ? 'network_admin_' : '';
			add_filter( "{$prefix}plugin_action_links_" . UM_PLUGIN, array( &$this, 'plugin_links' ) );
		}


		/**
		 * Check if there is UM Post Type and remove "Edit" bulk action
		 */
		public function posts_page() {
			global $current_screen;

			if ( empty( $current_screen ) || empty( $current_screen->id ) ) {
				return;
			}

			if ( ! UM()->admin()->screen()->is_own_post_type() ) {
				return;
			}

			add_filter( "bulk_actions-{$current_screen->id}", array( &$this, 'remove_edit_bulk_action' ) );
		}


		/**
		 * This will remove the "Edit" bulk action, which is actually quick edit, for all CPT registered via UM
		 *
		 * @param array $actions
		 *
		 * @return array;
		 */
		public function remove_edit_bulk_action( $actions ) {
			unset( $actions['edit'] );
			return $actions;
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
		 * This will remove the "Quick Edit" row action
		 *
		 * @param array $actions
		 *
		 * @return array
		 */
		public function remove_quick_edit_row_action( $actions ) {
			if ( UM()->admin()->screen()->is_own_post_type() ) {
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
		public function post_row_actions( $actions, $post ) {
			//check for your post type
			if ( 'um_form' === $post->post_type ) {
				$actions['um_duplicate'] = '<a href="' . esc_url( $this->duplicate_uri( $post->ID ) ) . '">' . esc_html__( 'Duplicate', 'ultimate-member' ) . '</a>';
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
		public function duplicate_uri( $id ) {
			$url = add_query_arg( array( 'post_type' => 'um_form', 'um_adm_action' => 'duplicate_form' ), admin_url( 'edit.php' ) );
			$url = add_query_arg( 'post_id', $id, $url );
			return $url;
		}


		/**
		 * Custom columns for Form
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		public function manage_edit_um_form_columns( $columns ) {
			$new_columns['cb']         = '<input type="checkbox" />';
			$new_columns['title']      = __( 'Title', 'ulitmate-member' );
			$new_columns['id']         = __( 'ID', 'ulitmate-member' );
			$new_columns['mode']       = __( 'Type', 'ulitmate-member' );
			$new_columns['is_default'] = __( 'Default', 'ulitmate-member' );
			$new_columns['shortcode']  = __( 'Shortcode', 'ulitmate-member' );
			$new_columns['date']       = __( 'Date', 'ulitmate-member' );

			return $new_columns;
		}


		/**
		 * Display custom columns for Form
		 *
		 * @param string $column_name
		 * @param int $id
		 */
		public function manage_um_form_posts_custom_column( $column_name, $id ) {
			switch ( $column_name ) {
				case 'id':
					echo '<span class="um-admin-number">' . esc_html( $id ) . '</span>';
					break;

				case 'shortcode':
					echo UM()->common()->shortcodes()->get_shortcode( $id );
					break;

				case 'is_default':
					$is_default = UM()->query()->get_attr( 'is_default', $id );
					echo empty( $is_default ) ? esc_html__( 'No', 'ultimate-member' ) : esc_html__( 'Yes', 'ultimate-member' );
					break;

				case 'mode':
					$mode = UM()->query()->get_attr( 'mode', $id );
					echo UM()->form()->display_form_type( $mode );
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
					$post_states[ 'um_predefined_page_' . $slug ] = sprintf( esc_html__( 'UM %s', 'ultimate-member' ), $data['title'] );
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
		public function plugin_links( $links ) {
			$more_links[] = '<a href="http://docs.ultimatemember.com/" target="_blank">' . esc_html__( 'Docs', 'ultimate-member' ) . '</a>';

			$url = add_query_arg( array( 'page' => 'ultimatemember' ), admin_url( 'admin.php' ) );
			$more_links[] = '<a href="' . esc_url( $url ) .'">' . esc_html__( 'Settings', 'ultimate-member' ) . '</a>';

			$links = $more_links + $links;
			return $links;
		}
	}
}
