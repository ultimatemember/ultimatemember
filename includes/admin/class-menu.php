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
		var $slug = 'ultimatemember';

		public $um_roles_error = '';
		public $um_roles_data = array();


		/**
		 * Admin_Menu constructor.
		 */
		function __construct() {
			add_action( 'admin_menu', array( &$this, 'add_menu_items' ), 1000 );

			add_action( 'load-ultimate-member_page_um_roles', array( &$this, 'maybe_role_redirect' ) );
			add_action( 'load-toplevel_page_ultimatemember', array( &$this, 'maybe_settings_redirect' ) );

			add_action( 'admin_head', array( $this, 'menu_order_count' ) );

			add_filter( 'admin_body_class', array( &$this, 'selected_menu' ), 10, 1 );
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
		function add_menu_items() {
			add_menu_page( __( 'Ultimate Member', 'ultimate-member' ), __( 'Ultimate Member', 'ultimate-member' ), 'manage_options', $this->slug, array( UM()->admin()->settings(), 'settings_page' ), 'dashicons-admin-users', '42.78578' );

			add_submenu_page( $this->slug, __( 'Settings', 'ultimate-member' ), __( 'Settings', 'ultimate-member' ), 'manage_options', $this->slug, array( UM()->admin()->settings(), 'settings_page' ) );

			add_submenu_page( $this->slug, __( 'Fields Groups', 'ultimate-member' ), __( 'Fields Groups', 'ultimate-member' ), 'manage_options', 'um_fields_groups', array( &$this, 'fields_groups_page' ) );

			add_submenu_page( $this->slug, __( 'Forms', 'ultimate-member' ), __( 'Forms', 'ultimate-member' ), 'manage_options', 'edit.php?post_type=um_form' );

			add_submenu_page( $this->slug, __( 'User Roles', 'ultimate-member' ), __( 'User Roles', 'ultimate-member' ), 'manage_options', 'um_roles', array( &$this, 'um_roles_pages' ) );

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

			if ( ! UM()->is_pro_plugin_active() ) {
				add_submenu_page( $this->slug, __( 'Upgrade to Pro', 'ultimate-member' ), '<span style="color: #7856ff">' . __( 'Upgrade to Pro', 'ultimate-member' ) . '</span>', 'manage_options', 'https://ultimatemember.com/pricing/', '' );
			}
		}

		/**
		 * Fields groups page menu callback
		 */
		function fields_groups_page() {
			if ( empty( $_GET['tab'] ) ) {
				include_once UM_PATH . 'includes/admin/templates/fields-group/groups-list.php';
			} elseif ( in_array( sanitize_key( $_GET['tab'] ), array( 'add', 'edit' ), true ) ) {
				include_once UM_PATH . 'includes/admin/templates/fields-group/group-edit.php';
			}
		}

		/**
		 * Role page menu callback
		 */
		function um_roles_pages() {
			if ( empty( $_GET['tab'] ) ) {
				include_once UM_PATH . 'includes/admin/templates/role/roles-list.php';
			} elseif ( in_array( sanitize_key( $_GET['tab'] ), array( 'add', 'edit' ), true ) ) {
				include_once UM_PATH . 'includes/admin/templates/role/role-edit.php';
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

			$settings_struct = UM()->admin()->settings()->settings_structure[ $current_tab ];

			//remove not option hidden fields
			if ( ! empty( $settings_struct['fields'] ) ) {
				foreach ( $settings_struct['fields'] as $field_key => $field_options ) {

					if ( isset( $field_options['is_option'] ) && $field_options['is_option'] === false ) {
						unset( $settings_struct['fields'][ $field_key ] );
					}

				}
			}

			if ( empty( $settings_struct['fields'] ) && empty( $settings_struct['sections'] ) ) {
				wp_redirect( add_query_arg( array( 'page' => 'ultimatemember' ), admin_url( 'admin.php' ) ) );
				exit;
			}

			if ( ! empty( $settings_struct['sections'] ) ) {
				if ( empty( $settings_struct['sections'][ $current_subtab ] ) ) {
					$args = array( 'page' => 'ultimatemember' );
					if ( ! empty( $current_tab ) ) {
						$args['tab'] = $current_tab;
					}
					wp_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
					exit;
				}
			}
		}

		/**
		 * Made selected UM menu on Add/Edit CPT and Term Taxonomies
		 *
		 * @param string $classes
		 *
		 * @return string
		 *
		 * @since 1.0
		 */
		function selected_menu( $classes ) {
			global $submenu, $pagenow;

			if ( isset( $submenu['ultimatemember'] ) ) {
				if ( isset( $_GET['post_type'] ) && in_array( sanitize_key( $_GET['post_type'] ), UM()->common()->cpt()->get_list(), true ) ) {
					add_filter( 'parent_file', array( &$this, 'change_parent_file' ), 200, 1 );
				}

				if ( 'post.php' === $pagenow && ( isset( $_GET['post'] ) && in_array( get_post_type( sanitize_key( $_GET['post'] ) ), UM()->common()->cpt()->get_list(), true ) ) ) {
					add_filter( 'parent_file', array( &$this, 'change_parent_file' ), 200, 1 );
				}

				add_filter( 'submenu_file', array( &$this, 'change_submenu_file' ), 200, 2 );
			}

			return $classes;
		}


		/**
		 * Return admin submenu variable for display pages
		 *
		 * @param string $parent_file
		 *
		 * @return string
		 *
		 * @since 3.0
		 */
		function change_parent_file( $parent_file ) {
			global $pagenow;

			if ( 'edit-tags.php' !== $pagenow && 'term.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
				$pagenow = 'admin.php';
			}

			$parent_file = 'ultimatemember';

			return $parent_file;
		}


		/**
		 * Return admin submenu variable for display pages
		 *
		 * @param string $submenu_file
		 * @param string $parent_file
		 *
		 * @return string
		 *
		 * @since 3.0
		 */
		function change_submenu_file( $submenu_file, $parent_file ) {
			global $pagenow;

			if ( 'edit-tags.php' === $pagenow || 'term.php' === $pagenow || 'post-new.php' === $pagenow ) {
				if ( 'ultimatemember' === $parent_file ) {
					if ( isset( $_GET['post_type'] ) && in_array( sanitize_key( $_GET['post_type'] ), UM()->common()->cpt()->get_list(), true ) && isset( $_GET['taxonomy'] ) && in_array( sanitize_key( $_GET['taxonomy'] ), UM()->common()->cpt()->get_taxonomies_list( sanitize_key( $_GET['post_type'] ) ), true ) ) {
						$submenu_file = 'edit-tags.php?taxonomy=' . sanitize_key( $_GET['taxonomy'] ) . '&post_type=' . sanitize_key( $_GET['post_type'] );
					} elseif ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && in_array( sanitize_key( $_GET['post_type'] ), UM()->common()->cpt()->get_list(), true ) ) {
						$submenu_file = 'edit.php?post_type=' . sanitize_key( $_GET['post_type'] );
					}

					$pagenow = 'admin.php';
				}
			}

			return $submenu_file;
		}
	}
}
