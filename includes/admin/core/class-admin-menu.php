<?php
namespace um\admin\core;


use \RecursiveDirectoryIterator;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\core\Admin_Menu' ) ) {


	/**
	 * Class Admin_Menu
	 * @package um\admin\core
	 */
	class Admin_Menu {


		/**
		 * @var string
		 */
		var $pagehook;
		var $slug = 'ultimatemember';


		/**
		 * Admin_Menu constructor.
		 */
		function __construct() {
			add_action( 'admin_menu', array( &$this, 'primary_admin_menu' ), 0 );
			add_action( 'admin_menu', array( &$this, 'secondary_menu_items' ), 1000 );
			add_action( 'admin_menu', array( &$this, 'extension_menu' ), 9999 );

			add_action( 'admin_head', array( $this, 'menu_order_count' ) );

			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1000 );
		}


		/**
		 * Change the admin footer text on UM admin pages
		 *
		 * @param $footer_text
		 *
		 * @return string
		 */
		public function admin_footer_text( $footer_text ) {
			$current_screen = get_current_screen();

			// Add the dashboard pages
			$um_pages[] = 'toplevel_page_ultimatemember';
			$um_pages[] = 'ultimate-member_page_um_options';
			$um_pages[] = 'edit-um_form';
			$um_pages[] = 'edit-um_role';
			$um_pages[] = 'edit-um_directory';
			$um_pages[] = 'ultimate-member_page_ultimatemember-extensions';

			if ( isset( $current_screen->id ) && in_array( $current_screen->id, $um_pages ) ) {
				// Change the footer text
				if ( ! get_option( 'um_admin_footer_text_rated' ) ) {

					ob_start(); ?>
						<a href="https://wordpress.org/support/plugin/ultimate-member/reviews/?filter=5" target="_blank" class="um-admin-rating-link" data-rated="<?php esc_attr_e( 'Thanks :)', 'ultimate-member' ) ?>">
							&#9733;&#9733;&#9733;&#9733;&#9733;
						</a>
					<?php $link = ob_get_clean();

					ob_start();

					printf( __( 'If you like Ultimate Member please consider leaving a %s review. It will help us to grow the plugin and make it more popular. Thank you.', 'ultimate-member' ), $link ) ?>

					<script type="text/javascript">
						jQuery( 'a.um-admin-rating-link' ).click(function() {
							jQuery.ajax({
								url: wp.ajax.settings.url,
								type: 'post',
								data: {
									action: 'um_rated',
									nonce: um_admin_scripts.nonce
								},
								success: function(){

								}
							});
							jQuery(this).parent().text( jQuery( this ).data( 'rated' ) );
						});
					</script>

					<?php $footer_text = ob_get_clean();
				}
			}

			return $footer_text;
		}


		/**
		 * When user clicks the review link in backend
		 */
		function ultimatemember_rated() {
			UM()->admin()->check_ajax_nonce();

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			update_option( 'um_admin_footer_text_rated', 1 );
			wp_send_json_success();
		}


		/**
		 * Manage order of admin menu items
		 */
		public function menu_order_count() {
			global $menu, $submenu;

			if ( ! current_user_can( 'list_users' ) ) {
				return;
			}

			$count = UM()->user()->get_pending_users_count();
			if ( is_array( $menu ) ) {
				foreach ( $menu as $key => $menu_item ) {
					if ( 0 === strpos( $menu_item[0], _x( 'Users', 'Admin menu name' ) ) ) {
						$menu[ $key ][0] .= ' <span class="update-plugins count-' . $count . '"><span class="processing-count">' . $count . '</span></span>';
					}
				}
			}

			if ( is_array( $submenu ) && isset( $submenu['users.php'] ) ) {
				foreach ( $submenu['users.php'] as $key => $menu_item ) {
					if ( 0 === strpos( $menu_item[0], _x( 'All Users', 'Admin menu name' ) ) ) {
						$submenu['users.php'][ $key ][0] .= ' <span class="update-plugins count-' .$count . '"><span class="processing-count">' . $count . '</span></span>';
					}
				}
			}
		}


		/**
		 * Setup admin menu
		 */
		function primary_admin_menu() {
			$this->pagehook = add_menu_page( __( 'Ultimate Member', 'ultimate-member' ), __( 'Ultimate Member', 'ultimate-member' ), 'manage_options', $this->slug, array( &$this, 'admin_page' ), 'dashicons-admin-users', '42.78578');

			add_action( 'load-' . $this->pagehook, array( &$this, 'on_load_page' ) );

			add_submenu_page( $this->slug, __( 'Dashboard', 'ultimate-member' ), __( 'Dashboard', 'ultimate-member' ), 'manage_options', $this->slug, array( &$this, 'admin_page' ) );
		}


		/**
		 * Secondary admin menu (after settings)
		 */
		function secondary_menu_items() {
			add_submenu_page( $this->slug, __( 'Forms', 'ultimate-member' ), __( 'Forms', 'ultimate-member' ), 'manage_options', 'edit.php?post_type=um_form', '' );

			add_submenu_page( $this->slug, __( 'User Roles', 'ultimate-member' ), __( 'User Roles', 'ultimate-member' ), 'manage_options', 'um_roles', array( &$this, 'um_roles_pages' ) );

			if ( UM()->options()->get( 'members_page' ) ) {
				add_submenu_page( $this->slug, __( 'Member Directories', 'ultimate-member' ), __( 'Member Directories', 'ultimate-member' ), 'manage_options', 'edit.php?post_type=um_directory', '' );
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_extend_admin_menu
			 * @description Extend UM menu
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_extend_admin_menu', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_extend_admin_menu', 'my_extend_admin_menu', 10 );
			 * function my_extend_admin_menu() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_extend_admin_menu' );
		}


		/**
		 * Role page menu callback
		 */
		function um_roles_pages() {
			if ( empty( $_GET['tab'] ) ) {
				include_once um_path . 'includes/admin/core/list-tables/roles-list-table.php';
			} elseif ( sanitize_key( $_GET['tab'] ) == 'add' || sanitize_key( $_GET['tab'] ) == 'edit' ) {
				include_once um_path . 'includes/admin/templates/role/role-edit.php';
			} else {
				um_js_redirect( add_query_arg( array( 'page' => 'um_roles' ), get_admin_url( 'admin.php' ) ) );
			}
		}


		/**
		 * Extension menu
		 */
		function extension_menu() {
			add_submenu_page( $this->slug, __( 'Extensions', 'ultimate-member' ), '<span style="color: #00B9EB">' .__( 'Extensions', 'ultimate-member' ) . '</span>', 'manage_options', $this->slug . '-extensions', array( &$this, 'admin_page' ) );
		}


		/**
		 * Load metabox stuff
		 */
		function on_load_page() {
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );

			/** custom metaboxes for dashboard defined here **/
			add_meta_box( 'um-metaboxes-contentbox-1', __( 'Users Overview', 'ultimate-member' ), array( &$this, 'users_overview' ), $this->pagehook, 'core', 'core' );

			add_meta_box( 'um-metaboxes-sidebox-1', __( 'Purge Temp Files', 'ultimate-member' ), array( &$this, 'purge_temp' ), $this->pagehook, 'side', 'core' );

			add_meta_box( 'um-metaboxes-sidebox-2', __( 'User Cache', 'ultimate-member' ), array( &$this, 'user_cache' ), $this->pagehook, 'side', 'core' );

			//If there are active and licensed extensions - show metabox for upgrade it
			$exts = UM()->plugin_updater()->get_active_plugins();
			if ( 0 < count( $exts ) ) {
				add_meta_box( 'um-metaboxes-sidebox-3', __( 'Upgrade\'s Manual Request', 'ultimate-member' ), array( &$this, 'upgrade_request' ), $this->pagehook, 'side', 'core' );
			}
		}


		/**
		 *
		 */
		function users_overview() {
			include_once UM()->admin()->templates_path . 'dashboard/users.php';
		}


		/**
		 *
		 */
		function purge_temp() {
			include_once UM()->admin()->templates_path . 'dashboard/purge.php';
		}


		/**
		 *
		 */
		function upgrade_request() {
			include_once UM()->admin()->templates_path . 'dashboard/upgrade-request.php';
		}


		/**
		 *
		 */
		function user_cache() {
			include_once UM()->admin()->templates_path . 'dashboard/cache.php';
		}


		/**
		 * Get a directory size
		 *
		 * @param $directory
		 *
		 * @return float|int
		 */
		function dir_size( $directory ) {
			if ( $directory == 'temp' ) {
				$directory = UM()->files()->upload_temp;
				$size = 0;

				foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory ) ) as $file ) {
					$size += $file->getSize();
				}
				return round ( $size / 1048576, 2);
			}
			return 0;
		}


		/**
		 * Which admin page to show?
		 */
		function admin_page() {

			$page = ! empty( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';

			if ( $page == 'ultimatemember' ) { ?>

				<div id="um-metaboxes-general" class="wrap">

					<h1>Ultimate Member <sup><?php echo ultimatemember_version; ?></sup></h1>

					<?php wp_nonce_field( 'um-metaboxes-general' ); ?>
					<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
					<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

					<input type="hidden" name="action" value="save_um_metaboxes_general" />

					<div id="dashboard-widgets-wrap">

						<div id="dashboard-widgets" class="metabox-holder um-metabox-holder">

							<div id="postbox-container-1" class="postbox-container"><?php do_meta_boxes( $this->pagehook, 'core', null );  ?></div>
							<div id="postbox-container-2" class="postbox-container"><?php do_meta_boxes( $this->pagehook, 'normal', null ); ?></div>
							<div id="postbox-container-3" class="postbox-container"><?php do_meta_boxes( $this->pagehook, 'side', null ); ?></div>

						</div>

					</div>

				</div>
				<div class="um-admin-clear"></div>

				<script type="text/javascript">
					//<![CDATA[
					jQuery(document).ready( function($) {
						// postboxes setup
						postboxes.add_postbox_toggles('<?php echo esc_js( $this->pagehook ); ?>');
					});
					//]]>
				</script>

			<?php } elseif ( $page == 'ultimatemember-extensions' ) {

				include_once UM()->admin()->templates_path . 'extensions.php';

			}

		}

	}
}