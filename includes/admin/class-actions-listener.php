<?php
namespace um\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\Actions_Listener' ) ) {


	/**
	 * Class Actions_Listener
	 *
	 * @since 3.0
	 *
	 * @package um\admin
	 */
	class Actions_Listener {

		/**
		 * @var null
		 */
		public $field_groups_error = null;

		/**
		 * @var null
		 */
		public $field_group_submission = null;

		/**
		 * Actions_Listener constructor.
		 */
		public function __construct() {
			add_action( 'um_admin_do_action__manual_upgrades_request', array( &$this, 'manual_upgrades_request' ) );
			add_action( 'um_admin_do_action__duplicate_form', array( &$this, 'duplicate_form' ) );
			add_action( 'um_admin_do_action__um_hide_locale_notice', array( &$this, 'hide_notice' ) );
			add_action( 'um_admin_do_action__um_can_register_notice', array( &$this, 'hide_notice' ) );
			add_action( 'um_admin_do_action__um_hide_exif_notice', array( &$this, 'hide_notice' ) );
			add_action( 'um_admin_do_action__user_action', array( &$this, 'user_action' ) );

			add_action( 'um_admin_do_action__install_predefined_page', array( &$this, 'install_predefined_page' ) );
			add_action( 'um_admin_do_action__install_predefined_pages', array( &$this, 'install_predefined_pages' ) );

			add_action( 'load-toplevel_page_ultimatemember', array( &$this, 'handle_modules_actions_options' ) );
			add_action( 'load-toplevel_page_ultimatemember', array( &$this, 'handle_email_notifications_actions' ) );
			add_action( 'load-ultimate-member_page_um_roles', array( &$this, 'handle_roles_actions' ) );

			add_action( 'load-ultimate-member_page_um_field_groups', array( &$this, 'handle_save_field_group' ) );
			add_action( 'load-ultimate-member_page_um_field_groups', array( &$this, 'handle_field_groups_actions' ) );
		}

		/**
		 *
		 */
		public function manual_upgrades_request() {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			$last_request = get_option( 'um_last_manual_upgrades_request', false );

			if ( empty( $last_request ) || time() > $last_request + DAY_IN_SECONDS ) {
				if ( is_multisite() ) {
					$blogs_ids = get_sites();
					foreach ( $blogs_ids as $b ) {
						switch_to_blog( $b->blog_id );
						wp_clean_update_cache();

						UM()->plugin_updater()->um_checklicenses();

						update_option( 'um_last_manual_upgrades_request', time() );
						restore_current_blog();
					}
				} else {
					wp_clean_update_cache();

					UM()->plugin_updater()->um_checklicenses();

					update_option( 'um_last_manual_upgrades_request', time() );
				}

				$url = add_query_arg(
					array(
						'page'   => 'ultimatemember',
						'update' => 'got_updates',
					),
					admin_url( 'admin.php' )
				);
			} else {
				$url = add_query_arg(
					array(
						'page'   => 'ultimatemember',
						'update' => 'often_updates',
					),
					admin_url( 'admin.php' )
				);
			}
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Duplicate form
		 *
		 * @param $action
		 */
		public function duplicate_form( $action ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}
			if ( ! isset( $_REQUEST['post_id'] ) || ! is_numeric( $_REQUEST['post_id'] ) ) {
				die();
			}

			$post_id = absint( $_REQUEST['post_id'] );

			$n = array(
				'post_type'   => 'um_form',
				'post_title'  => sprintf( __( 'Duplicate of %s', 'ultimate-member' ), get_the_title( $post_id ) ),
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
			);

			$n_id = wp_insert_post( $n );

			$n_fields = get_post_custom( $post_id );
			foreach ( $n_fields as $key => $value ) {

				if ( '_um_custom_fields' === $key ) {
					$the_value = unserialize( $value[0] );
				} else {
					$the_value = $value[0];
				}

				update_post_meta( $n_id, $key, $the_value );

			}

			delete_post_meta( $n_id, '_um_core' );

			$url = admin_url( 'edit.php?post_type=um_form' );
			$url = add_query_arg( 'update', 'form_duplicated', $url );

			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Action to hide notices in admin
		 *
		 * @param $action
		 */
		public function hide_notice( $action ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			update_option( $action, 1 );
			exit( wp_redirect( remove_query_arg( 'um_adm_action' ) ) );
		}

		/**
		 * Various user actions
		 *
		 * @param $action
		 */
		public function user_action( $action ) {
			if ( ! is_admin() || ! current_user_can( 'edit_users' ) ) {
				die();
			}
			if ( ! isset( $_REQUEST['sub'] ) ) {
				die();
			}
			if ( ! isset( $_REQUEST['user_id'] ) ) {
				die();
			}

			um_fetch_user( absint( $_REQUEST['user_id'] ) );

			$subaction = sanitize_key( $_REQUEST['sub'] );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_user_action_hook
			 * @description Action on bulk user subaction
			 * @input_vars
			 * [{"var":"$subaction","type":"string","desc":"Bulk Subaction"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_user_action_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_user_action_hook', 'my_admin_user_action', 10, 1 );
			 * function my_admin_user_action( $subaction ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_user_action_hook', $subaction );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_user_action_{$subaction}_hook
			 * @description Action on bulk user subaction
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_user_action_{$subaction}_hook', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_user_action_{$subaction}_hook', 'my_admin_user_action', 10 );
			 * function my_admin_user_action() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_admin_user_action_{$subaction}_hook" );

			um_reset_user();

			wp_redirect( add_query_arg( 'update', 'user_updated', admin_url( '?page=ultimatemember' ) ) );
			exit;
		}


		/**
		 *
		 */
		function dismiss_wrong_pages() {
			//check empty pages in settings
			$empty_pages = array();

			$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );
			foreach ( $predefined_pages as $slug ) {
				$page_id = um_get_predefined_page_id( $slug );
				if ( ! $page_id ) {
					$empty_pages[] = $slug;
					continue;
				}

				$page = get_post( $page_id );
				if ( ! $page ) {
					$empty_pages[] = $slug;
					continue;
				}
			}

			//if there aren't empty pages - then hide pages notice
			if ( empty( $empty_pages ) ) {
				$hidden_notices = get_option( 'um_hidden_admin_notices', array() );
				if ( ! is_array( $hidden_notices ) ) {
					$hidden_notices = array();
				}

				$hidden_notices[] = 'wrong_pages';

				update_option( 'um_hidden_admin_notices', $hidden_notices );
			}
		}


		/**
		 * Install selected predefined page
		 */
		function install_predefined_page() {
			if ( ! is_admin() ) {
				die();
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				$url = add_query_arg( array( 'page' => 'ultimatemember' ), admin_url( 'admin.php' ) );
				exit( wp_redirect( $url ) );
			}

			$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );

			$page_slug = array_key_exists( 'um_page_slug', $_REQUEST ) ? sanitize_key( $_REQUEST['um_page_slug'] ) : '';

			if ( empty( $page_slug ) || ! in_array( $page_slug, $predefined_pages, true ) ) {
				$url = add_query_arg( array( 'page' => 'ultimatemember' ), admin_url( 'admin.php' ) );
				exit( wp_redirect( $url ) );
			}

			$post_ids = new \WP_Query( array(
				'post_type'      => 'page',
				'meta_query'     => array(
					array(
						'key'   => '_um_core',
						'value' => $page_slug,
					)
				),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );

			$post_ids = $post_ids->get_posts();

			if ( ! empty( $post_ids ) ) {
				foreach ( $post_ids as $post_id ) {
					delete_post_meta( $post_id, '_um_core' );
				}
			}

			UM()->install()->predefined_page( $page_slug );

			// Auto dismiss 'wrong_pages' notice if it's visible
			$this->dismiss_wrong_pages();

			$url = add_query_arg( array( 'page' => 'ultimatemember' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * Core pages installation
		 */
		function install_predefined_pages() {
			if ( ! is_admin() ) {
				die();
			}

			UM()->install()->predefined_pages();

			// Auto dismiss 'wrong_pages' notice if it's visible
			$this->dismiss_wrong_pages();

			$url = add_query_arg( array( 'page' => 'ultimatemember' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * @since 3.0
		 */
		function handle_email_notifications_actions() {
			if ( ! isset( $_GET['tab'] ) || 'email' !== sanitize_key( $_GET['tab'] ) ) {
				return;
			}

			//remove extra query arg
			if ( ! empty( $_GET['_wp_http_referer'] ) ) {
				exit( wp_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
			}
		}


		/**
		 * Handles Modules list table
		 *
		 * @since 3.0
		 *
		 * @uses Modules::activate() UM()->modules()->activate( $slug )
		 * @uses Modules::deactivate() UM()->modules()->deactivate( $slug )
		 * @uses Modules::flush_data() UM()->modules()->flush_data( $slug )
		 */
		function handle_modules_actions() {
			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = remove_query_arg( [ '_wp_http_referer' ], wp_unslash( $_REQUEST['_wp_http_referer'] ) );
			} else {
				$redirect = get_admin_url( null, 'admin.php?page=um-modules' );
			}

			if ( isset( $_GET['action'] ) ) {
				switch ( sanitize_key( $_GET['action'] ) ) {
					case 'activate': {
						// Activate module
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single activate
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_activate' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) ) {
							// bulk activate
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->activate( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'a', $redirect ) ) );
						break;
					}
					case 'deactivate': {
						// Deactivate module
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single deactivate
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_deactivate' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) )  {
							// bulk deactivate
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->deactivate( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'd', $redirect ) ) );
						break;
					}
					case 'flush-data': {
						// Flush module's data
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single flush
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_flush' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) )  {
							// bulk flush
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->flush_data( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'f', $redirect ) ) );
						break;
					}
				}
			}

			//remove extra query arg
			if ( ! empty( $_GET['_wp_http_referer'] ) ) {
				exit( wp_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
			}
		}



		/**
		 * Handles Modules list table
		 *
		 * @since 3.0
		 *
		 * @uses Modules::activate() UM()->modules()->activate( $slug )
		 * @uses Modules::deactivate() UM()->modules()->deactivate( $slug )
		 * @uses Modules::flush_data() UM()->modules()->flush_data( $slug )
		 */
		function handle_modules_actions_options() {
			if ( ! ( isset( $_GET['page'] ) && 'ultimatemember' === $_GET['page'] && isset( $_GET['tab'] ) && 'modules' === $_GET['tab'] && ! isset( $_GET['section'] ) ) ) {
				return;
			}
			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = remove_query_arg( [ '_wp_http_referer' ], wp_unslash( $_REQUEST['_wp_http_referer'] ) );
			} else {
				$redirect = get_admin_url( null, 'admin.php?page=ultimatemember&tab=modules' );
			}

			if ( isset( $_GET['action'] ) ) {
				switch ( sanitize_key( $_GET['action'] ) ) {
					case 'activate': {
						// Activate module
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single activate
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_activate' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) ) {
							// bulk activate
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->activate( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'a', $redirect ) ) );
						break;
					}
					case 'deactivate': {
						// Deactivate module
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single deactivate
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_deactivate' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) )  {
							// bulk deactivate
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->deactivate( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'd', $redirect ) ) );
						break;
					}
					case 'flush-data': {
						// Flush module's data
						$slugs = [];
						if ( isset( $_GET['slug'] ) ) {
							// single flush
							$slug = sanitize_key( $_GET['slug'] );

							if ( empty( $slug ) ) {
								exit( wp_redirect( $redirect ) );
							}

							check_admin_referer( 'um_module_flush' . $slug . get_current_user_id() );
							$slugs = [ $slug ];
						} elseif( isset( $_REQUEST['item'] ) )  {
							// bulk flush
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Modules', 'ultimate-member' ) ) );
							$slugs = array_map( 'sanitize_key', $_REQUEST['item'] );
						}

						if ( ! count( $slugs ) ) {
							exit( wp_redirect( $redirect ) );
						}

						$results = 0;
						foreach ( $slugs as $slug ) {
							if ( UM()->modules()->flush_data( $slug ) ) {
								$results++;
							}
						}

						if ( ! $results ) {
							exit( wp_redirect( $redirect ) );
						}

						exit( wp_redirect( add_query_arg( 'msg', 'f', $redirect ) ) );
						break;
					}
				}
			}

			//remove extra query arg
			if ( ! empty( $_GET['_wp_http_referer'] ) ) {
				exit( wp_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
			}
		}


		/**
		 * @since 3.0
		 */
		function handle_roles_actions() {
			if ( ! empty( $_GET['tab'] ) ) {
				return;
			}

			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = remove_query_arg( array( '_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
			} else {
				$redirect = get_admin_url() . 'admin.php?page=um_roles';
			}

			if ( isset( $_GET['action'] ) ) {
				switch ( sanitize_key( $_GET['action'] ) ) {
					/* delete action */
					case 'delete': {
						// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
						// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
						$role_keys = array();
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_role_delete' . sanitize_title( $_REQUEST['id'] ) . get_current_user_id() );
							$role_keys = (array) sanitize_title( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Roles', 'ultimate-member' ) ) );
							$role_keys = array_map( 'sanitize_title', $_REQUEST['item'] );
						}

						if ( ! count( $role_keys ) ) {
							wp_redirect( $redirect );
							exit;
						}

						$deleted_count = 0;
						$um_roles = get_option( 'um_roles', array() );

						$um_custom_roles = array();
						foreach ( $role_keys as $k => $role_key ) {
							$role_meta = get_option( "um_role_{$role_key}_meta" );

							if ( empty( $role_meta['_um_is_custom'] ) ) {
								continue;
							}

							delete_option( "um_role_{$role_key}_meta" );
							$um_roles = array_diff( $um_roles, array( $role_key ) );

							$roleID            = 'um_' . $role_key;
							$um_custom_roles[] = $roleID;

							//check if role exist before removing it
							if ( get_role( $roleID ) ) {
								$deleted_count++;
								remove_role( $roleID );
							}
						}

						//set for users with deleted roles role "Subscriber"
						$args = array(
							'blog_id'     => get_current_blog_id(),
							'role__in'    => $um_custom_roles,
							'number'      => -1,
							'count_total' => false,
							'fields'      => 'ids',
						);
						$users_to_subscriber = get_users( $args );
						if ( ! empty( $users_to_subscriber ) ) {
							foreach ( $users_to_subscriber as $user_id ) {
								$object_user = get_userdata( $user_id );

								if ( ! empty( $object_user ) ) {
									foreach ( $um_custom_roles as $roleID ) {
										$object_user->remove_role( $roleID );
									}
								}

								//update user role if it's empty
								if ( empty( $object_user->roles ) ) {
									wp_update_user(
										array(
											'ID'   => $user_id,
											'role' => 'subscriber',
										)
									);
								}
							}
						}

						update_option( 'um_roles', $um_roles );

						if ( 0 === $deleted_count ) {
							wp_redirect( $redirect );
							exit;
						}

						wp_redirect( add_query_arg( array( 'msg' => 'd', 'count' => $deleted_count ), $redirect ) );
						exit;
						break;
					}
					case 'reset': {
						// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
						// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
						$role_keys = array();
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_role_reset' . sanitize_title( $_REQUEST['id'] ) . get_current_user_id() );
							$role_keys = (array) sanitize_title( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Roles', 'ultimate-member' ) ) );
							$role_keys = array_map( 'sanitize_title', $_REQUEST['item'] );
						}

						if ( ! count( $role_keys ) ) {
							wp_redirect( $redirect );
							exit;
						}

						$flushed_count = 0;

						foreach ( $role_keys as $k => $role_key ) {
							$role_meta = get_option( "um_role_{$role_key}_meta" );

							if ( ! empty( $role_meta['_um_is_custom'] ) ) {
								unset( $role_keys[ array_search( $role_key, $role_keys, true ) ] );
								continue;
							}

							$flushed_count++;
							delete_option( "um_role_{$role_key}_meta" );
						}

						if ( 0 === $flushed_count ) {
							wp_redirect( $redirect );
							exit;
						}

						wp_redirect( add_query_arg( array( 'msg' => 'reset', 'count' => $flushed_count ), $redirect ) );
						exit;
						break;
					}
				}
			}
		}

		private function sanitize( $data ) {
			$sanitize_map = array(
				'title'       => 'text',
				'description' => 'textarea',
			);

			foreach ( $data as $key => &$value ) {
				if ( 'fields' === $key ) {
					foreach ( $value as $field_id => &$field_row ) {
						if ( empty( $field_row['type'] ) ) {
							continue;
						}

						// get sanitizing map based on the field type
						$field_settings      = UM()->admin()->field_group()->get_field_settings( sanitize_key( $field_row['type'] ) );
						$field_settings      = call_user_func_array('array_merge', array_values( $field_settings ) );
						$fields_sanitize_map = array_column( $field_settings, 'sanitize', 'id' );

						$fields_sanitize_map['id']        = 'empty_absint';
						$fields_sanitize_map['order']     = 'absint';
						$fields_sanitize_map['parent_id'] = 'text';

						foreach ( $field_row as $field_setting_key => &$field_setting_value ) {
							if ( ! array_key_exists( $field_setting_key, $fields_sanitize_map ) ) {
								continue;
							}
							switch ( $fields_sanitize_map[ $field_setting_key ] ) {
								default:
									$field_setting_value = apply_filters( 'um_groups_fields_sanitize_field_' . $field_setting_key, $field_setting_value );
									break;
								case 'int':
									$field_setting_value = (int) $field_setting_value;
									break;
								case 'empty_int':
									$field_setting_value = ( '' !== $field_setting_value ) ? (int) $field_setting_value : '';
									break;
								case 'bool':
									$field_setting_value = (bool) $field_setting_value;
									break;
								case 'url':
									if ( is_array( $field_setting_value ) ) {
										$field_setting_value = array_map( 'esc_url_raw', $field_setting_value );
									} else {
										$field_setting_value = esc_url_raw( $field_setting_value );
									}
									break;
								case 'text':
									$field_setting_value = sanitize_text_field( $field_setting_value );
									break;
								case 'textarea':
									$field_setting_value = sanitize_textarea_field( $field_setting_value );
									break;
								case 'wp_kses':
									$field_setting_value = wp_kses_post( $field_setting_value );
									break;
								case 'color':
									$field_setting_value = sanitize_hex_color( $field_setting_value );
									break;
								case 'key':
									if ( is_array( $field_setting_value ) ) {
										$field_setting_value = array_map( 'sanitize_key', $field_setting_value );
									} else {
										$field_setting_value = sanitize_key( $field_setting_value );
									}
									break;
								case 'absint':
									if ( is_array( $field_setting_value ) ) {
										$field_setting_value = array_map( 'absint', $field_setting_value );
									} else {
										$field_setting_value = absint( $field_setting_value );
									}
									break;
								case 'empty_absint':
									if ( is_array( $field_setting_value ) ) {
										$field_setting_value = array_map( 'absint', $field_setting_value );
									} else {
										$field_setting_value = ( '' !== $field_setting_value ) ? absint( $field_setting_value ) : '';
									}
									break;
								case 'conditional_rules':
									if ( is_array( $field_setting_value ) ) {
										if ( array_key_exists( '{group_key}', $field_setting_value ) ) {
											// just a case if something went wrong with disabled and JS handlers
											unset( $field_setting_value['{group_key}'] );
										}

										foreach ( $field_setting_value as $cond_group_k => &$cond_group ) {
											foreach ( $cond_group as $cond_row_k => &$cond_row ) {
												if ( ! array_key_exists( 'field', $cond_row ) || ! array_key_exists( 'condition', $cond_row ) || ! array_key_exists( 'value', $cond_row ) ) {
													continue;
												}
												$cond_row['field']     = sanitize_text_field( $cond_row['field'] ); // Don't change `sanitize_text_field` to `absint` because new fields can have break data.
												$cond_row['condition'] = sanitize_text_field( $cond_row['condition'] );
												// Remove if rule isn't filled.
												if ( empty( $cond_row['field'] ) || empty( $cond_row['condition'] ) ) {
													unset( $field_setting_value[ $cond_group_k ][ $cond_row_k ] );
													continue;
												}
												$cond_row['value'] = sanitize_text_field( $cond_row['value'] );
											}
										}
									}
									break;
								case 'options':
									if ( is_array( $field_setting_value ) ) {
										if ( array_key_exists( 'keys', $field_setting_value ) ) {
											if ( is_array( $field_setting_value['keys'] ) ) {
												if ( array_key_exists( '{{index}}', $field_setting_value['keys'] ) ) {
													unset( $field_setting_value['keys']['{{index}}'] );
												}

												$field_setting_value['keys'] = array_map( 'sanitize_text_field', $field_setting_value['keys'] );
											}
										}

										if ( array_key_exists( 'values', $field_setting_value ) ) {
											if ( is_array( $field_setting_value['values'] ) ) {
												if ( array_key_exists( '{{index}}', $field_setting_value['values'] ) ) {
													unset( $field_setting_value['values']['{{index}}'] );
												}

												$field_setting_value['values'] = array_map( 'sanitize_text_field', $field_setting_value['values'] );
											}
										}

										if ( array_key_exists( 'default_value', $field_setting_value ) ) {
											if ( ! empty( $field_setting_value['default_value'] ) && is_array( $field_setting_value['default_value'] ) ) {
												$field_setting_value['default_value'] = array_map( 'absint', array_keys( $field_setting_value['default_value'] ) );
											}
										}
									}
									break;
							}
						}
					}
				} else {
					if ( ! array_key_exists( $key, $sanitize_map ) ) {
						continue;
					}
					switch ( $sanitize_map[ $key ] ) {
						default:
							$value = apply_filters( 'um_groups_fields_sanitize_' . $key, $value );
							break;
						case 'int':
							$value = (int) $value;
							break;
						case 'empty_int':
							$value = ( '' !== $value ) ? (int) $value : '';
							break;
						case 'bool':
							$value = (bool) $value;
							break;
						case 'url':
							if ( is_array( $value ) ) {
								$value = array_map( 'esc_url_raw', $value );
							} else {
								$value = esc_url_raw( $value );
							}
							break;
						case 'text':
							$value = sanitize_text_field( $value );
							break;
						case 'textarea':
							$value = sanitize_textarea_field( $value );
							break;
						case 'wp_kses':
							$value = wp_kses_post( $value );
							break;
						case 'color':
							$value = sanitize_hex_color( $value );
							break;
						case 'key':
							if ( is_array( $value ) ) {
								$value = array_map( 'sanitize_key', $value );
							} else {
								$value = sanitize_key( $value );
							}
							break;
						case 'absint':
							if ( is_array( $value ) ) {
								$value = array_map( 'absint', $value );
							} else {
								$value = absint( $value );
							}
							break;
						case 'empty_absint':
							if ( is_array( $value ) ) {
								$value = array_map( 'absint', $value );
							} else {
								$value = ( '' !== $value ) ? absint( $value ) : '';
							}
							break;
					}
				}
			}

			return $data;
		}

		/**
		 * @return bool
		 */
		private function conditions_are_met( $conditional_settings, $submitted_data ) {
			if ( 3 !== count( $conditional_settings ) ) {
				return false;
			}

			$cond_field_key       = $conditional_settings[0];
			$cond_field_condition = $conditional_settings[1];
			$cond_field_value     = $conditional_settings[2];

			if ( ! array_key_exists( $cond_field_key, $submitted_data ) ) {
				return false;
			}

			static $field_settings;
			if ( empty( $field_settings ) ) {
				$field_settings_tabs = UM()->admin()->field_group()->get_field_settings( sanitize_key( $submitted_data['type'] ) );
				$field_settings      = call_user_func_array( 'array_merge', array_values( $field_settings_tabs ) );
			}

			// Check parents for conditionals logic.
			if ( ! empty( $field_settings[ $cond_field_key ]['conditional'] ) ) {
				if ( ! $this->conditions_are_met( $field_settings[ $cond_field_key ]['conditional'], $submitted_data ) ) {
					return false;
				}
			}

			switch ( $cond_field_condition ) {
				case '=':
					if ( is_array( $cond_field_value ) ) {
						if ( ! in_array( $submitted_data[ $cond_field_key ], $cond_field_value, true ) ) {
							return false;
						}
					} else {
						if ( $cond_field_value !== $submitted_data[ $cond_field_key ] ) {
							return false;
						}
					}
					break;
				case '!=':
					if ( is_array( $cond_field_value ) ) {
						if ( in_array( $submitted_data[ $cond_field_key ], $cond_field_value, true ) ) {
							return false;
						}
					} else {
						if ( $cond_field_value === $submitted_data[ $cond_field_key ] ) {
							return false;
						}
					}
					break;
			}

			return true;
		}

		private function validate( $data ) {
			if ( empty( $data['title'] ) ) {
				$this->field_groups_error = array(
					'field'   => 'title',
					'message' => __( 'Title cannot be empty.', 'ultimate-member' ),
				);
			}

			if ( ! empty( $this->field_groups_error ) ) {
				return $this->field_groups_error;
			}

			if ( empty( $data['fields'] ) ) {
				$this->field_groups_error = array(
					'field'   => 'um-admin-form-fields',
					'message' => __( 'Fields cannot be empty.', 'ultimate-member' ),
				);
			}

			if ( ! empty( $this->field_groups_error ) ) {
				return $this->field_groups_error;
			}

			$submitted_fields = $data['fields'];
			foreach ( $data['fields'] as $k => $field_data ) {
				$field_settings_tabs = UM()->admin()->field_group()->get_field_settings( sanitize_key( $field_data['type'] ) );
				$field_settings      = call_user_func_array( 'array_merge', array_values( $field_settings_tabs ) );

				// Checking for required validation
				$fields_required_map = array_column( $field_settings, 'required', 'id' );
				foreach ( $fields_required_map as $field_id => $required ) {
					if ( empty( $required ) ) {
						continue;
					}

					if ( ! empty( $field_settings[ $field_id ]['conditional'] ) ) {
						// Skip hidden by conditional logic fields for required marker.
						if ( ! $this->conditions_are_met( $field_settings[ $field_id ]['conditional'], $field_data ) ) {
							continue;
						}
					}

					if ( 'repeater' === $field_data['type'] && 'fields' === $field_id ) {
						$child_exists = false;
						foreach ( $submitted_fields as $f_data ) {
							if ( array_key_exists( 'parent_id', $f_data ) && (string) $f_data['parent_id'] === (string) $k ) {
								$child_exists = true;
							}
						}

						if ( ! $child_exists ) {
							$this->field_groups_error = array(
								'field'   => 'um-admin-form-' . $field_id,
								'message' => __( 'Sub fields cannot be empty.', 'ultimate-member' ),
							);
						}
					} else {
						if ( empty( $field_data[ $field_id ] ) ) {
							$set_tab = '';
							foreach ( $field_settings_tabs as $tab_key => $tab_settings ) {
								if ( in_array( $field_id, array_keys( $tab_settings ), true ) ) {
									$set_tab = $tab_key;
									break;
								}
							}
							$this->field_groups_error = array(
								'field'   => 'field_groupfields' . $k . $set_tab . '_' . $field_id,
								// translators: %s - Field label
								'message' => sprintf( __( '"%s" field cannot be empty.', 'ultimate-member' ), $field_settings[ $field_id ]['label'] ),
							);
						}
					}

					if ( ! empty( $this->field_groups_error ) ) {
						return $this->field_groups_error;
					}
				}

				// Checking for type requirements (numeric)
				$fields_validate_by_type_map = array_column( $field_settings, 'type', 'id' );
				foreach ( $fields_validate_by_type_map as $field_id => $type ) {
					if ( empty( $field_data[ $field_id ] ) ) {
						continue;
					}

					if ( ! empty( $field_settings[ $field_id ]['conditional'] ) ) {
						// Skip hidden by conditional logic fields for required marker.
						if ( ! $this->conditions_are_met( $field_settings[ $field_id ]['conditional'], $field_data ) ) {
							continue;
						}
					}

					if ( 'number' === $type ) {
						if ( ! is_numeric( $field_data[ $field_id ] ) ) {
							$set_tab = '';
							foreach ( $field_settings_tabs as $tab_key => $tab_settings ) {
								if ( in_array( $field_id, array_keys( $tab_settings ), true ) ) {
									$set_tab = $tab_key;
									break;
								}
							}
							$this->field_groups_error = array(
								'field'   => 'field_groupfields' . $k . $set_tab . '_' . $field_id,
								// translators: %s - Field label
								'message' => sprintf( __( '"%s" field must be numeric.', 'ultimate-member' ), $field_settings[ $field_id ]['label'] ),
							);
						}
					} elseif ( 'select' === $type ) {
						$field_options = array_values( $field_settings[ $field_id ]['options'] );
						if ( is_array( $field_options[0] ) ) {
							// with optgroups
							$field_options = call_user_func_array( 'array_merge', array_column( $field_settings[ $field_id ]['options'], 'options' ) );
							$valid_options = array_map( 'strval', array_keys( $field_options ) );
						} else {
							$valid_options = array_map( 'strval', array_keys( $field_settings[ $field_id ]['options'] ) );
						}

						$set_tab = '';
						foreach ( $field_settings_tabs as $tab_key => $tab_settings ) {
							if ( in_array( $field_id, array_keys( $tab_settings ), true ) ) {
								$set_tab = $tab_key;
								break;
							}
						}

						if ( is_array( $field_data[ $field_id ] ) ) {
							$mapped_value      = array_map( 'strval', $field_data[ $field_id ] );
							$options_intersect = array_intersect( $mapped_value, $valid_options );

							if ( empty( $options_intersect ) ) {
								$this->field_groups_error = array(
									'field'   => 'field_groupfields' . $k . $set_tab . '_' . $field_id,
									// translators: %s - Field label
									'message' => sprintf( __( '"%s" field must be in options range.', 'ultimate-member' ), $field_settings[ $field_id ]['label'] ),
								);
							}
						} else {
							if ( ! in_array( (string) $field_data[ $field_id ], $valid_options, true ) ) {
								$this->field_groups_error = array(
									'field'   => 'field_groupfields' . $k . $set_tab . '_' . $field_id,
									// translators: %s - Field label
									'message' => sprintf( __( '"%s" field must be in options range.', 'ultimate-member' ), $field_settings[ $field_id ]['label'] ),
								);
							}
						}
					}

					if ( ! empty( $this->field_groups_error ) ) {
						return $this->field_groups_error;
					}
				}

				// Checking for custom validation callbacks in validate
				$fields_validate_map = array_column( $field_settings, 'validate', 'id' );
				foreach ( $fields_validate_map as $field_id => $validate ) {
					if ( empty( $validate ) ) {
						continue;
					}

					if ( ! empty( $field_settings[ $field_id ]['conditional'] ) ) {
						// Skip hidden by conditional logic fields for required marker.
						if ( ! $this->conditions_are_met( $field_settings[ $field_id ]['conditional'], $field_data ) ) {
							continue;
						}
					}

					foreach ( $validate as $validation_callback ) {
						if ( ! is_callable( $validation_callback ) ) {
							continue;
						}
						$validation_result = call_user_func_array( $validation_callback, array( $field_data[ $field_id ], $data['fields'], $field_id ) );
						if ( false === $validation_result ) {
							continue;
						}

						$set_tab = '';
						foreach ( $field_settings_tabs as $tab_key => $tab_settings ) {
							if ( in_array( $field_id, array_keys( $tab_settings ), true ) ) {
								$set_tab = $tab_key;
								break;
							}
						}

						$this->field_groups_error = array(
							'field'   => 'field_groupfields' . $k . $set_tab . '_' . $field_id,
							'message' => $validation_result,
						);

						return $this->field_groups_error;
					}
				}
			}

			return true;
		}

		public function handle_save_field_group() {
			if ( empty( $_GET['tab'] ) || ! in_array( sanitize_key( $_GET['tab'] ), array( 'edit', 'add' ) ) ) {
				return;
			}

			if ( empty( $_POST['um_admin_action'] ) || 'save_field_group' !== sanitize_key( $_POST['um_admin_action'] ) ) {
				return;
			}

			$redirect = get_admin_url() . 'admin.php?page=um_field_groups&tab=' . sanitize_key( $_GET['tab'] );

			if ( empty( $_POST['um_nonce'] ) ) {
				wp_safe_redirect( add_query_arg( array( 'msg' => 'empty_nonce' ), $redirect ) );
				exit();
			}

			if ( empty( $_POST['field_group'] ) ) {
				wp_safe_redirect( add_query_arg( array( 'msg' => 'wrong_data' ), $redirect ) );
				exit();
			}

			$action = sanitize_key( $_GET['tab'] ); // 'edit' or 'add'
			if ( 'edit' === $action ) {
				if ( empty( $_GET['id'] ) ) {
					return;
				}

				$group_id = absint( $_GET['id'] );
				if ( empty( $group_id ) ) {
					wp_safe_redirect( add_query_arg( array( 'msg' => 'wrong_id' ), $redirect ) );
					exit();
				}

				if ( empty( $_POST['field_group']['id'] ) || $group_id !== absint( $_POST['field_group']['id'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'msg' => 'wrong_id' ), $redirect ) );
					exit();
				}

				if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-edit-field-group' ) ) {
					wp_safe_redirect( add_query_arg( array( 'msg' => 'wrong_nonce' ), $redirect ) );
					exit();
				}

				// Remove extra slashes by WordPress native function.
				$_POST['field_group'] = wp_unslash( $_POST['field_group'] );

				// Sanitize data by WordPress native functions.
				$data = $this->sanitize( $_POST['field_group'] );

				$this->field_group_submission = $data;

				// Validate data sending for fields.
				$result = $this->validate( $data );

				if ( true !== $result ) {
					// @todo validation of the fields
					return;
				}

				// $data below is sanitized based on fields types etc.
				$title       = ! empty( $data['title'] ) ? $data['title'] : __( '(no title)', 'ultimate-member' );
				$description = ! empty( $data['description'] ) ? $data['description'] : '';

				$args = array(
					'id'          => $group_id,
					'title'       => $title,
					'description' => $description,
				);

				$field_group_id = UM()->admin()->field_group()->update( $args );
				if ( ! empty( $field_group_id ) ) {
					if ( ! empty( $data['fields'] ) ) {
						// delete permanently the fields that were removed from builder
						$group_fields = UM()->admin()->field_group()->get_fields( $field_group_id );
						if ( ! empty( $group_fields ) ) {
							$field_ids = array_column( $group_fields, 'id' );
							if ( ! empty( $field_ids ) ) {
								$new_field_ids    = array_column( $data['fields'], 'id' );
								$fields_to_delete = array_diff( $field_ids, $new_field_ids );
								if ( ! empty( $fields_to_delete ) ) {
									UM()->admin()->field_group()->delete_field( $fields_to_delete );
								}
							}
						}

						//$id_parent_accoss = array();

						foreach ( $data['fields'] as $submit_key => $group_field ) {
							if ( empty( $group_field['id'] ) ) {
								// add new field
								$meta = $group_field;
								unset( $meta['id'] );
								unset( $meta['title'] );
								unset( $meta['type'] );
								unset( $meta['parent_id'] );

								if ( array_key_exists( 'conditional_rules', $meta ) ) {
									foreach ( $meta['conditional_rules'] as &$cond_group ) {
										foreach ( $cond_group as &$cond_row ) {
											$field_data = UM()->admin()->field_group()->get_field_data( $cond_row['field'] );
											if ( false === $field_data ) {
												if ( isset( UM()->admin()->field_group()->submission_ids_assoc[ $cond_row['field'] ] ) ) {
													$cond_row['field'] = UM()->admin()->field_group()->submission_ids_assoc[ $cond_row['field'] ];
												}
											}
										}
									}
								}

								$field_args = array(
									'group_id'  => $field_group_id,
									'title'     => $group_field['title'],
									'type'      => $group_field['type'],
									//'parent_id' => isset( $id_parent_accoss[ $group_field['parent_id'] ] ) ? $id_parent_accoss[ $group_field['parent_id'] ] : 0,
									'parent_id' => isset( UM()->admin()->field_group()->submission_ids_assoc[ $group_field['parent_id'] ] ) ? UM()->admin()->field_group()->submission_ids_assoc[ $group_field['parent_id'] ] : 0,
									'meta'      => $meta,
								);

								$f_id = UM()->admin()->field_group()->add_field( $field_args );

								//$id_parent_accoss[ $submit_key ] = $f_id;
								UM()->admin()->field_group()->submission_ids_assoc[ $submit_key ] = $f_id;
							} else {
								// update field
								$meta = $group_field;
								unset( $meta['id'] );
								unset( $meta['title'] );
								unset( $meta['type'] );
								unset( $meta['parent_id'] );

								if ( array_key_exists( 'conditional_rules', $meta ) ) {
									foreach ( $meta['conditional_rules'] as &$cond_group ) {
										foreach ( $cond_group as &$cond_row ) {
											$field_data = UM()->admin()->field_group()->get_field_data( $cond_row['field'] );
											if ( false === $field_data ) {
												if ( isset( UM()->admin()->field_group()->submission_ids_assoc[ $cond_row['field'] ] ) ) {
													$cond_row['field'] = UM()->admin()->field_group()->submission_ids_assoc[ $cond_row['field'] ];
												}
											}
										}
									}
								}

								$field_args = array(
									'id'    => $group_field['id'],
									'title' => $group_field['title'],
									'type'  => $group_field['type'],
									'meta'  => $meta,
								);
								UM()->admin()->field_group()->update_field( $field_args );

								//$id_parent_accoss[ $submit_key ] = $group_field['id'];
								UM()->admin()->field_group()->submission_ids_assoc[ $submit_key ] = $group_field['id'];
							}
						}
					}

					wp_safe_redirect( add_query_arg( array( 'id' => $field_group_id, 'msg' => 'u' ), $redirect ) );
					exit;
				}
			} elseif ( 'add' === $action ) {
				if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-add-field-group' ) ) {
					wp_safe_redirect( add_query_arg( array( 'msg' => 'wrong_nonce' ), $redirect ) );
					exit;
				}

				// Remove extra slashes by WordPress native function.
				$_POST['field_group'] = wp_unslash( $_POST['field_group'] );

				$data = $this->sanitize( $_POST['field_group'] );

				$this->field_group_submission = $data;

				// Validate data sending for fields.
				$result = $this->validate( $data );

				if ( true !== $result ) {
					// @todo validation of the fields
					return;
				}

				$title       = ! empty( $data['title'] ) ? sanitize_text_field( $data['title'] ) : __( '(no title)', 'ultimate-member' );
				$description = ! empty( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';

				// default status = active
				$args = array(
					'title'       => $title,
					'description' => $description,
				);

				$field_group_id = UM()->admin()->field_group()->create( $args );
				if ( ! empty( $field_group_id ) ) {
					if ( ! empty( $data['fields'] ) ) {
						// $id_parent_accoss = array();

						foreach ( $data['fields'] as $submit_key => $group_field ) {
							// add new field
							$meta = $group_field;
							unset( $meta['id'] );
							unset( $meta['title'] );
							unset( $meta['type'] );
							unset( $meta['parent_id'] );

							if ( array_key_exists( 'conditional_rules', $meta ) ) {
								foreach ( $meta['conditional_rules'] as &$cond_group ) {
									foreach ( $cond_group as &$cond_row ) {
										$field_data = UM()->admin()->field_group()->get_field_data( $cond_row['field'] );
										if ( false === $field_data ) {
											if ( isset( UM()->admin()->field_group()->submission_ids_assoc[ $cond_row['field'] ] ) ) {
												$cond_row['field'] = UM()->admin()->field_group()->submission_ids_assoc[ $cond_row['field'] ];
											}
										}
									}
								}
							}

							$field_args = array(
								'group_id'  => $field_group_id,
								'title'     => $group_field['title'],
								'type'      => $group_field['type'],
								//'parent_id' => isset( $id_parent_accoss[ $group_field['parent_id'] ] ) ? $id_parent_accoss[ $group_field['parent_id'] ] : 0,
								'parent_id' => isset( UM()->admin()->field_group()->submission_ids_assoc[ $group_field['parent_id'] ] ) ? UM()->admin()->field_group()->submission_ids_assoc[ $group_field['parent_id'] ] : 0,
								'meta'      => $meta,
							);

							$f_id = UM()->admin()->field_group()->add_field( $field_args );

							//$id_parent_accoss[ $submit_key ] = $f_id;
							UM()->admin()->field_group()->submission_ids_assoc[ $submit_key ] = $f_id;
						}
					}

					wp_redirect( add_query_arg( array( 'id' => $field_group_id, 'msg' => 'a' ), get_admin_url() . 'admin.php?page=um_field_groups&tab=edit' ) );
					exit;
				}
			}
		}

		public function handle_field_groups_actions() {
			if ( ! empty( $_GET['tab'] ) ) {
				return;
			}

			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = remove_query_arg( array( '_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
			} else {
				$redirect = get_admin_url() . 'admin.php?page=um_field_groups';
			}

			if ( isset( $_GET['action'] ) ) {
				switch ( sanitize_key( $_GET['action'] ) ) {
					case 'delete': {
						$field_group_ids = array();
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_field_group_delete' . absint( $_REQUEST['id'] ) . get_current_user_id() );
							$field_group_ids = (array) absint( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Field Groups', 'ultimate-member' ) ) );
							$field_group_ids = array_map( 'absint', $_REQUEST['item'] );
						}

						if ( ! count( $field_group_ids ) ) {
							wp_redirect( $redirect );
							exit;
						}

						$deleted_count = 0;
						foreach ( $field_group_ids as $field_group_id ) {
							if ( UM()->admin()->field_group()->delete_group( $field_group_id ) ) {
								$deleted_count ++;
							}
						}

						if ( $deleted_count > 0 ) {
							wp_redirect( add_query_arg( array( 'msg' => 'd', 'count' => $deleted_count ), $redirect ) );
						} else {
							wp_redirect( $redirect );
						}
						exit;
						break;
					}
					case 'activate': {
						$field_group_ids = array();
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_field_group_activate' . absint( $_REQUEST['id'] ) . get_current_user_id() );
							$field_group_ids = (array) absint( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Field Groups', 'ultimate-member' ) ) );
							$field_group_ids = array_map( 'absint', $_REQUEST['item'] );
						}

						if ( ! count( $field_group_ids ) ) {
							wp_redirect( $redirect );
							exit;
						}

						$activated_count = 0;
						foreach ( $field_group_ids as $field_group_id ) {
							if ( UM()->admin()->field_group()->activate_group( $field_group_id ) ) {
								$activated_count ++;
							}
						}

						if ( $activated_count > 0 ) {
							wp_redirect( add_query_arg( array( 'msg' => 'a', 'count' => $activated_count ), $redirect ) );
						} else {
							wp_redirect( $redirect );
						}
						exit;
						break;
					}
					case 'deactivate': {
						$field_group_ids = array();
						if ( isset( $_REQUEST['id'] ) ) {
							check_admin_referer( 'um_field_group_deactivate' . absint( $_REQUEST['id'] ) . get_current_user_id() );
							$field_group_ids = (array) absint( $_REQUEST['id'] );
						} elseif ( isset( $_REQUEST['item'] ) ) {
							check_admin_referer( 'bulk-' . sanitize_key( __( 'Field Groups', 'ultimate-member' ) ) );
							$field_group_ids = array_map( 'absint', $_REQUEST['item'] );
						}

						if ( ! count( $field_group_ids ) ) {
							wp_redirect( $redirect );
							exit;
						}

						$deactivated_count = 0;
						foreach ( $field_group_ids as $field_group_id ) {
							if ( UM()->admin()->field_group()->deactivate_group( $field_group_id ) ) {
								$deactivated_count ++;
							}
						}

						if ( $deactivated_count > 0 ) {
							wp_redirect( add_query_arg( array( 'msg' => 'da', 'count' => $deactivated_count ), $redirect ) );
						} else {
							wp_redirect( $redirect );
						}
						exit;
						break;
					}
				}
			}
		}
	}
}
