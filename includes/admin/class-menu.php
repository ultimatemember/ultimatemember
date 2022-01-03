<?php
namespace um\admin;


use \RecursiveDirectoryIterator;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\Menu' ) ) {


	/**
	 * Class Menu
	 * @package um\admin
	 */
	class Menu {


		/**
		 * @var string
		 */
		var $pagehook;
		var $slug = 'ultimatemember';

		public $um_roles_error = '';
		public $um_roles_data = array();


		/**
		 * Admin_Menu constructor.
		 */
		function __construct() {
			add_action( 'admin_menu', array( &$this, 'primary_admin_menu' ), 0 );
			add_action( 'admin_menu', array( &$this, 'secondary_menu_items' ), 1000 );
			add_action( 'admin_menu', array( &$this, 'modules_menu' ), 99999 );

			add_action( 'load-ultimate-member_page_um_roles', array( &$this, 'maybe_role_redirect' ) );
			add_action( 'load-ultimate-member_page_um_options', array( &$this, 'maybe_settings_redirect' ) );

			add_action( 'admin_head', array( $this, 'menu_order_count' ) );

			add_action( 'parent_file', array( &$this, 'parent_file' ), 9 );
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
			} elseif ( in_array( sanitize_key( $_GET['tab'] ), array( 'add', 'edit' ), true ) ) {
				include_once um_path . 'includes/admin/templates/role/role-edit.php';
			}
		}


		/**
		 * Trigger redirect on the Roles screen if there is a wrong tab
		 */
		function maybe_role_redirect() {
			if ( empty( $_GET['tab'] ) ) {
				//remove extra query arg on the roles list table
				if ( ! empty( $_GET['_wp_http_referer'] ) ) {
					wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
					exit;
				}

				return;
			}

			if ( ! in_array( sanitize_key( $_GET['tab'] ), array( 'add', 'edit' ), true ) ) {
				wp_redirect( add_query_arg( array( 'page' => 'um_roles' ), admin_url( 'admin.php' ) ) );
				exit;
			} else {
				// Add or Edit User Role form submission
				if ( ! empty( $_POST['role'] ) ) {
					$id       = '';
					$redirect = '';

					if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
						if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-add-role' ) ) {
							$this->um_roles_error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
						}
					} else {
						if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-edit-role' ) ) {
							$this->um_roles_error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
						}
					}

					if ( empty( $this->um_roles_error ) ) {

						$data = UM()->admin()->sanitize_role_meta( $_POST['role'] );

						if ( 'add' === sanitize_key( $_GET['tab'] ) ) {

							$data['name'] = trim( esc_html( strip_tags( $data['name'] ) ) );

							if ( empty( $data['name'] ) ) {
								$this->um_roles_error .= __( 'Title is empty!', 'ultimate-member' ) . '<br />';
							}

							if ( preg_match( "/^[\p{Latin}\d\-_ ]+$/i", $data['name'] ) ) {
								// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
								// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
								$id = sanitize_title( $data['name'] );
							} else {
								$auto_increment = UM()->options()->get( 'custom_roles_increment' );
								$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
								$id             = 'custom_role_' . $auto_increment;
							}

							$redirect = add_query_arg( array( 'page' => 'um_roles', 'tab' => 'edit', 'id' => $id, 'msg' => 'a' ), admin_url( 'admin.php' ) );
						} elseif ( 'edit' === sanitize_key( $_GET['tab'] ) && ! empty( $_GET['id'] ) ) {
							// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
							// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
							$id = sanitize_title( $_GET['id'] );

							$pre_role_meta = get_option( "um_role_{$id}_meta", array() );
							if ( isset( $pre_role_meta['name'] ) ) {
								$data['name'] = $pre_role_meta['name'];
							}

							$redirect = add_query_arg( array( 'page' => 'um_roles', 'tab' => 'edit', 'id' => $id, 'msg'=> 'u' ), admin_url( 'admin.php' ) );
						}


						$all_roles = array_keys( get_editable_roles() );
						if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
							if ( in_array( 'um_' . $id, $all_roles, true ) || in_array( $id, $all_roles, true ) ) {
								$this->um_roles_error .= __( 'Role already exists!', 'ultimate-member' ) . '<br />';
							}
						}

						$this->um_roles_data = $data;

						if ( '' === $this->um_roles_error ) {

							if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
								$roles   = get_option( 'um_roles', array() );
								$roles[] = $id;

								update_option( 'um_roles', $roles );

								if ( isset( $auto_increment ) ) {
									$auto_increment++;
									UM()->options()->update( 'custom_roles_increment', $auto_increment );
								}
							}

							$role_meta = $data;
							unset( $role_meta['id'] );

							update_option( "um_role_{$id}_meta", $role_meta );

							UM()->user()->remove_cache_all_users();

							wp_redirect( $redirect );
							exit;
						}
					}
				}
			}
		}


		/**
		 * Trigger redirect on the Settings screen if there is a wrong tab or section
		 */
		function maybe_settings_redirect() {
			$current_tab = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );

			$settings_struct = UM()->admin_settings()->settings_structure[ $current_tab ];

			//remove not option hidden fields
			if ( ! empty( $settings_struct['fields'] ) ) {
				foreach ( $settings_struct['fields'] as $field_key => $field_options ) {

					if ( isset( $field_options['is_option'] ) && $field_options['is_option'] === false ) {
						unset( $settings_struct['fields'][ $field_key ] );
					}

				}
			}

			if ( empty( $settings_struct['fields'] ) && empty( $settings_struct['sections'] ) ) {
				wp_redirect( add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) ) );
				exit;
			}

			if ( ! empty( $settings_struct['sections'] ) ) {
				if ( empty( $settings_struct['sections'][ $current_subtab ] ) ) {
					$args = array( 'page' => 'um_options' );
					if ( ! empty( $current_tab ) ) {
						$args['tab'] = $current_tab;
					}
					wp_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
					exit;
				}
			}
		}


		/**
		 * Role page menu callback
		 */
		function modules_page() {
			include_once um_path . 'includes/admin/core/list-tables/modules-list-table.php';
		}


		/**
		 * Extension menu
		 */
		function modules_menu() {
		    $modules = UM()->modules()->get_list();
		    if ( empty( $modules ) ) {
		        return;
            }

			add_submenu_page( $this->slug, __( 'Modules', 'ultimate-member' ), '<span style="color: #00B9EB">' . __( 'Modules', 'ultimate-member' ) . '</span>', 'manage_options', 'um-modules', [ &$this, 'modules_page' ] );
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
					$filename = $file->getFilename();
					if ( $filename == '.' || $filename == '..' ) {
						continue;
					}

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

			<?php }

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

	}
}
