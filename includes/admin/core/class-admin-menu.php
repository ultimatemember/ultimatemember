<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		public function __construct() {
			add_action( 'admin_menu', array( &$this, 'primary_admin_menu' ), 0 );
			add_action( 'admin_menu', array( &$this, 'secondary_menu_items' ), 1000 );
			add_action( 'admin_menu', array( &$this, 'extension_menu' ), 9999 );

			add_action( 'admin_head', array( $this, 'menu_order_count' ) );

			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1000 );

			add_action( 'load-ultimate-member_page_um_options', array( &$this, 'maybe_settings_redirect' ) );
		}

		/**
		 * Trigger redirect on the Settings screen if there is a wrong tab or section.
		 *
		 * @since 2.8.2
		 */
		public function maybe_settings_redirect() {
			$current_tab    = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );

			$settings_struct = UM()->admin_settings()->settings_structure[ $current_tab ];

			// Remove not option hidden fields.
			if ( ! empty( $settings_struct['fields'] ) ) {
				foreach ( $settings_struct['fields'] as $field_key => $field_options ) {
					if ( isset( $field_options['is_option'] ) && false === $field_options['is_option'] ) {
						unset( $settings_struct['fields'][ $field_key ] );
					}
				}
			}

			if ( empty( $settings_struct['fields'] ) && empty( $settings_struct['sections'] ) && empty( $settings_struct['form_sections'] ) ) {
				wp_safe_redirect( add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) ) );
				exit;
			}

			if ( ! empty( $settings_struct['sections'] ) ) {
				if ( empty( $settings_struct['sections'][ $current_subtab ] ) ) {
					$args = array( 'page' => 'um_options' );
					if ( ! empty( $current_tab ) ) {
						$args['tab'] = $current_tab;
					}
					wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
					exit;
				}
			}
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

					// translators: %s: Review link.
					echo wp_kses( sprintf( __( 'If you like Ultimate Member please consider leaving a %s review. It will help us to grow the plugin and make it more popular. Thank you.', 'ultimate-member' ), $link ), UM()->get_allowed_html( 'admin_notice' ) );
					?>

					<script type="text/javascript">
						jQuery( document.body ).on('click', 'a.um-admin-rating-link', function() {
							jQuery.ajax({
								url: wp.ajax.settings.url,
								type: 'post',
								data: {
									action: 'um_rated',
									nonce: um_admin_scripts.nonce
								},
								success: function() {
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

			$count = UM()->query()->get_pending_users_count();
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
			if ( UM()->is_new_ui() ) {
				add_menu_page( __( 'UM Design BETA', 'ultimate-member' ), __( 'UM Design BETA', 'ultimate-member' ), 'manage_options', 'um-admin-design', array( &$this, 'admin_design_page' ), 'dashicons-admin-customizer' );
			}

			$this->pagehook = add_menu_page( __( 'Ultimate Member', 'ultimate-member' ), __( 'Ultimate Member', 'ultimate-member' ), 'manage_options', $this->slug, array( &$this, 'admin_page' ), 'dashicons-admin-users', '42.78578' );

			add_action( 'load-' . $this->pagehook, array( &$this, 'on_load_page' ) );

			add_submenu_page( $this->slug, __( 'Dashboard', 'ultimate-member' ), __( 'Dashboard', 'ultimate-member' ), 'manage_options', $this->slug, array( &$this, 'admin_page' ) );
		}

		public function admin_design_page() {
			?>
			<div id="um-admin-design-sample" class="wrap">
				<div class="notice notice-error">
					<h3 class="notice-title"><?php _e( 'Its error title' ); ?></h3>
					<p>Error description.</p>
				</div>

				<div class="notice-error notice">
					<p>Error notice.</p>
				</div>

				<div class="notice notice-error notice-alt">
					<p>Error alt notice.</p>
				</div>

				<div class="notice notice-error notice-large">
					<p>Error large notice.</p>
				</div>

				<div class="notice-info notice">
					<p>Info notice.</p>
				</div>

				<div class="notice-warning notice">
					<p>Warning notice.</p>
				</div>

				<div class="notice-success notice">
					<p>Success notice.</p>
				</div>

				<div class="notice-error notice is-dismissible">
					<p>Dismissible notice.</p>
				</div>
				<input type="submit" class="button button-primary" value="Primary button"/>
				<input type="button" class="button" value="Secondary button"/>
				<button type="submit" class="button button-primary">Primary button</button>
				<button type="button" class="button">Secondary button</button>
				<span class="spinner"></span>
				<span class="spinner is-active"></span>
				<div class="clear"></div>
			</div>
			<?php
		}


		/**
		 * Secondary admin menu (after settings)
		 */
		public function secondary_menu_items() {
			add_submenu_page( $this->slug, __( 'Settings', 'ultimate-member' ), __( 'Settings', 'ultimate-member' ), 'manage_options', 'um_options', array( UM()->admin_settings(), 'settings_page' ) );

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
				include_once UM_PATH . 'includes/admin/core/list-tables/roles-list-table.php';
			} elseif ( 'add' === sanitize_key( $_GET['tab'] ) || 'edit' === sanitize_key( $_GET['tab'] ) ) {
				include_once UM_PATH . 'includes/admin/templates/role/role-edit.php';
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
		 * Which admin page to show?
		 */
		function admin_page() {

			$page = ! empty( $_REQUEST['page'] ) ? sanitize_key( $_REQUEST['page'] ) : '';

			if ( $page == 'ultimatemember' ) { ?>

				<div id="um-metaboxes-general" class="wrap">

					<h1>Ultimate Member <sup><?php echo UM_VERSION; ?></sup></h1>

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
				<div class="clear"></div>

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

		/**
		 * Get a directory size
		 *
		 * @deprecated 3.0.0
		 * @param $directory
		 *
		 * @return float|int
		 */
		public function dir_size( $directory ) {
			_deprecated_function( __METHOD__, '3.0.0', 'UM()->common()->filesystem()->dir_size()' );
			if ( 'temp' === $directory ) {
				$directory = UM()->common()->filesystem()->get_tempdir();
				return UM()->common()->filesystem()::dir_size( $directory );
			}
			return 0;
		}
	}
}
