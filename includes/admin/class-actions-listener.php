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
		 * Actions_Listener constructor.
		 */
		function __construct() {
			add_action( 'um_admin_do_action__user_cache', array( &$this, 'user_cache' ) );
			add_action( 'um_admin_do_action__purge_temp', array( &$this, 'purge_temp' ) );
			add_action( 'um_admin_do_action__manual_upgrades_request', array( &$this, 'manual_upgrades_request' ) );
			add_action( 'um_admin_do_action__duplicate_form', array( &$this, 'duplicate_form' ) );
			add_action( 'um_admin_do_action__um_hide_locale_notice', array( &$this, 'hide_notice' ) );
			add_action( 'um_admin_do_action__um_can_register_notice', array( &$this, 'hide_notice' ) );
			add_action( 'um_admin_do_action__um_hide_exif_notice', array( &$this, 'hide_notice' ) );
			add_action( 'um_admin_do_action__user_action', array( &$this, 'user_action' ) );

			add_action( 'um_admin_do_action__install_predefined_page', array( &$this, 'install_predefined_page' ) );
			add_action( 'um_admin_do_action__install_predefined_pages', array( &$this, 'install_predefined_pages' ) );

			//add_action( 'load-ultimate-member_page_um-modules', array( &$this, 'handle_modules_actions' ) );
			add_action( 'load-ultimate-member_page_um_options', array( &$this, 'handle_modules_actions_options' ) );
			add_action( 'load-ultimate-member_page_um_options', array( &$this, 'handle_email_notifications_actions' ) );
			add_action( 'load-ultimate-member_page_um_roles', array( &$this, 'handle_roles_actions' ) );
			//add_action( 'load-users.php', array( UM()->install(), 'set_default_user_status' ) ); for avoid the conflicts with \WP_Users_Query on the users.php page
		}


		/**
		 * Clear all users cache
		 *
		 * @param $action
		 */
		public function user_cache( $action ) {
			global $wpdb;
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'um_cache_userdata_%'" );

			$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'cleared_cache' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * Purge temp uploads dir
		 * @param $action
		 */
		public function purge_temp( $action ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			UM()->files()->remove_dir( UM()->files()->upload_temp );

			$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'purged_temp' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
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
					foreach( $blogs_ids as $b ) {
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

				$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'got_updates' ), admin_url( 'admin.php' ) );
			} else {
				$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'often_updates' ), admin_url( 'admin.php' ) );
			}
			exit( wp_redirect( $url ) );
		}


		/**
		 * Duplicate form
		 *
		 * @param $action
		 */
		function duplicate_form( $action ) {
			if ( ! is_admin() || ! current_user_can('manage_options') ) {
				die();
			}
			if ( ! isset( $_REQUEST['post_id'] ) || ! is_numeric( $_REQUEST['post_id'] ) ) {
				die();
			}

			$post_id = absint( $_REQUEST['post_id'] );

			$n = array(
				'post_type'     => 'um_form',
				'post_title'    => sprintf( __( 'Duplicate of %s', 'ultimate-member' ), get_the_title( $post_id ) ),
				'post_status'   => 'publish',
				'post_author'   => get_current_user_id(),
			);

			$n_id = wp_insert_post( $n );

			$n_fields = get_post_custom( $post_id );
			foreach ( $n_fields as $key => $value ) {

				if ( $key == '_um_custom_fields' ) {
					$the_value = unserialize( $value[0] );
				} else {
					$the_value = $value[0];
				}

				update_post_meta( $n_id, $key, $the_value );

			}

			delete_post_meta( $n_id, '_um_core' );

			$url = admin_url( 'edit.php?post_type=um_form' );
			$url = add_query_arg( 'update', 'form_duplicated', $url );

			exit( wp_redirect( $url ) );

		}



		/**
		 * Action to hide notices in admin
		 *
		 * @param $action
		 */
		function hide_notice( $action ) {
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
		function user_action( $action ) {
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
				$url = add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) );
				exit( wp_redirect( $url ) );
			}

			$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );

			$page_slug = array_key_exists( 'um_page_slug', $_REQUEST ) ? sanitize_key( $_REQUEST['um_page_slug'] ) : '';

			if ( empty( $page_slug ) || ! in_array( $page_slug, $predefined_pages, true ) ) {
				$url = add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) );
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

			$url = add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) );
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

			$url = add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) );
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
			if ( ! ( isset( $_GET['page'] ) && 'um_options' === $_GET['page'] && isset( $_GET['tab'] ) && 'modules' === $_GET['tab'] && ! isset( $_GET['section'] ) ) ) {
				return;
			}
			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$redirect = remove_query_arg( [ '_wp_http_referer' ], wp_unslash( $_REQUEST['_wp_http_referer'] ) );
			} else {
				$redirect = get_admin_url( null, 'admin.php?page=um_options&tab=modules' );
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

						wp_redirect( add_query_arg( 'msg', 'd', $redirect ) );
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

						foreach ( $role_keys as $k => $role_key ) {
							$role_meta = get_option( "um_role_{$role_key}_meta" );

							if ( ! empty( $role_meta['_um_is_custom'] ) ) {
								unset( $role_keys[ array_search( $role_key, $role_keys, true ) ] );
								continue;
							}

							delete_option( "um_role_{$role_key}_meta" );
						}

						wp_redirect( add_query_arg( 'msg', 'reset', $redirect ) );
						exit;
						break;
					}
				}
			}
		}
	}
}
