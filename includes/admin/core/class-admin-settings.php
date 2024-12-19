<?php
namespace um\admin\core;

use DateTimeZone;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Settings' ) ) {

	/**
	 * Class Admin_Settings
	 * @package um\admin\core
	 */
	class Admin_Settings {

		/**
		 * @var array
		 */
		public $settings_map;

		/**
		 * @var array
		 */
		public $settings_structure;

		/**
		 * @var
		 */
		private $previous_licenses;

		/**
		 * @var
		 */
		private $need_change_permalinks;

		private $gravatar_changed = false;

		/**
		 * Admin_Settings constructor.
		 */
		public function __construct() {
			//init settings structure
			add_action( 'admin_init', array( &$this, 'init_variables' ), 9 );

			// settings structure handlers
			add_action( 'um_settings_page_before_email__content', array( $this, 'settings_before_email_tab' ) );
			add_filter( 'um_settings_section_custom_fields', array( $this, 'email_section_custom_fields' ), 10, 2 );

			add_filter( 'um_settings_form_section_advanced_override_templates_override_templates_custom_content', array( $this, 'settings_override_templates_tab' ) );

			//custom content for licenses tab
			add_filter( 'um_settings_section_licenses__custom_content', array( $this, 'settings_licenses_tab' ), 10, 3 );

			add_filter( 'um_settings_structure', array( $this, 'sorting_licenses_options' ), 9999, 1 );

			//save handlers
			add_action( 'admin_init', array( $this, 'save_settings_handler' ), 10 );

			//save pages options
			add_action( 'um_settings_before_save', array( $this, 'check_permalinks_changes' ) );
			add_action( 'um_settings_save', array( $this, 'on_settings_save' ) );

			add_filter( 'um_change_settings_before_save', array( $this, 'save_email_templates' ) );

			//save licenses options
			add_action( 'um_settings_before_save', array( $this, 'before_licenses_save' ) );
			add_action( 'um_settings_save', array( $this, 'licenses_save' ) );

			add_filter( 'um_change_settings_before_save', array( $this, 'set_default_if_empty' ), 9, 1 );
			add_filter( 'um_change_settings_before_save', array( $this, 'remove_empty_values' ), 10, 1 );
		}


		public function same_page_update_ajax() {
			UM()->admin()->check_ajax_nonce();

			if ( empty( $_POST['cb_func'] ) ) {
				wp_send_json_error( __( 'Wrong callback', 'ultimate-member' ) );
			}

			$cb_func = sanitize_key( $_POST['cb_func'] );

			if ( 'um_usermeta_fields' === $cb_func ) {
				//first install metatable
				global $wpdb;

				$metakeys = array();
				foreach ( UM()->builtin()->all_user_fields as $all_user_field ) {
					if ( ! array_key_exists( 'metakey', $all_user_field ) ) {
						continue;
					}
					$metakeys[] = $all_user_field['metakey'];
				}

				$metakeys = apply_filters( 'um_metadata_same_page_update_ajax', $metakeys, UM()->builtin()->all_user_fields );

				if ( is_multisite() ) {

					$sites = get_sites( array( 'fields' => 'ids' ) );
					foreach ( $sites as $blog_id ) {
						$metakeys[] = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
					}
				} else {
					$blog_id    = get_current_blog_id();
					$metakeys[] = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
				}

				//member directory data
				$metakeys[] = 'um_member_directory_data';
				$metakeys[] = '_um_verified';
				$metakeys[] = '_money_spent';
				$metakeys[] = '_completed';
				$metakeys[] = '_reviews_avg';

				//myCred meta
				if ( function_exists( 'mycred_get_types' ) ) {
					$mycred_types = mycred_get_types();
					if ( ! empty( $mycred_types ) ) {
						foreach ( array_keys( $mycred_types ) as $point_type ) {
							$metakeys[] = $point_type;
						}
					}
				}

				$sortby_custom_keys = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_um_sortby_custom'" );
				if ( empty( $sortby_custom_keys ) ) {
					$sortby_custom_keys = array();
				}

				$sortby_custom_keys2 = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_um_sorting_fields'" );
				if ( ! empty( $sortby_custom_keys2 ) ) {
					foreach ( $sortby_custom_keys2 as $custom_val ) {
						$custom_val = maybe_unserialize( $custom_val );

						foreach ( $custom_val as $sort_value ) {
							if ( is_array( $sort_value ) ) {
								$field_keys           = array_keys( $sort_value );
								$sortby_custom_keys[] = $field_keys[0];
							}
						}
					}
				}

				if ( ! empty( $sortby_custom_keys ) ) {
					$sortby_custom_keys = array_unique( $sortby_custom_keys );
					$metakeys           = array_merge( $metakeys, $sortby_custom_keys );
				}

				$skip_fields = UM()->builtin()->get_fields_without_metakey();
				$skip_fields = array_merge( $skip_fields, UM()->member_directory()->core_search_fields );

				$real_usermeta = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta}" );
				$real_usermeta = ! empty( $real_usermeta ) ? $real_usermeta : array();
				$real_usermeta = array_merge( $real_usermeta, array( 'um_member_directory_data' ) );

				if ( ! empty( $sortby_custom_keys ) ) {
					$real_usermeta = array_merge( $real_usermeta, $sortby_custom_keys );
				}

				$wp_usermeta_option = array_intersect( array_diff( $metakeys, $skip_fields ), $real_usermeta );

				update_option( 'um_usermeta_fields', array_values( $wp_usermeta_option ) );

				update_option( 'um_member_directory_update_meta', time() );

				UM()->options()->update( 'member_directory_own_table', true );

				wp_send_json_success();
			} elseif ( 'um_get_metadata' === $cb_func ) {
				global $wpdb;

				$wp_usermeta_option = get_option( 'um_usermeta_fields', array() );

				$count = $wpdb->get_var(
					"SELECT COUNT(*)
					FROM {$wpdb->usermeta}
					WHERE meta_key IN ('" . implode( "','", $wp_usermeta_option ) . "')"
				);

				wp_send_json_success( array( 'count' => $count ) );
			} elseif ( 'um_update_metadata_per_page' === $cb_func ) {

				if ( empty( $_POST['page'] ) ) {
					wp_send_json_error( __( 'Wrong data', 'ultimate-member' ) );
				}

				$per_page           = 500;
				$wp_usermeta_option = get_option( 'um_usermeta_fields', array() );

				global $wpdb;
				$metadata = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT *
						FROM {$wpdb->usermeta}
						WHERE meta_key IN ('" . implode( "','", $wp_usermeta_option ) . "')
						LIMIT %d, %d",
						( absint( $_POST['page'] ) - 1 ) * $per_page,
						$per_page
					),
					ARRAY_A
				);

				$values = array();
				foreach ( $metadata as $metarow ) {
					$values[] = $wpdb->prepare( '(%d, %s, %s)', $metarow['user_id'], $metarow['meta_key'], $metarow['meta_value'] );
				}

				// maybe create table.
				$table_name = $wpdb->prefix . 'um_metadata';
				$query      = $wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$wpdb->esc_like( $table_name )
				);
				if ( $wpdb->get_var( $query ) !== $table_name ) {
					UM()->setup()->create_db();
				}

				if ( ! empty( $values ) ) {
					$wpdb->query(
						"INSERT INTO
						{$wpdb->prefix}um_metadata(user_id, um_key, um_value)
						VALUES " . implode( ',', $values )
					);
				}

				$from = ( absint( $_POST['page'] ) * $per_page ) - $per_page + 1;
				$to   = absint( $_POST['page'] ) * $per_page;
				// translators: %1$s is a metadata from name; %2$s is a metadata to.
				wp_send_json_success( array( 'message' => sprintf( __( 'Metadata from %1$s to %2$s was upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
			} else {
				do_action( 'um_same_page_update_ajax_action', $cb_func );
			}
		}

		/**
		 *
		 */
		public function init_variables() {
			$settings_map = array();

			$general_pages_fields = array(
				array(
					'id'        => 'pages_settings',
					'type'      => 'hidden',
					'value'     => true,
					'is_option' => false,
				),
			);

			foreach ( UM()->config()->get( 'predefined_pages' ) as $slug => $page ) {
				$page_id    = UM()->options()->get_predefined_page_option_key( $slug );
				$page_title = ! empty( $page['title'] ) ? $page['title'] : '';

				$options    = array();
				$page_value = '';

				$pre_result = apply_filters( 'um_admin_settings_pages_list_value', false, $page_id );
				if ( false === $pre_result ) {
					if ( ! empty( $opt_value = UM()->options()->get( $page_id ) ) ) {
						if ( 'publish' === get_post_status( $opt_value ) ) {
							$title = get_the_title( $opt_value );
							$title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;
							$title = sprintf( __( '%1$s (ID: %2$s)', 'ultimate-member' ), $title, $opt_value );

							$options    = array( $opt_value => $title );
							$page_value = $opt_value;
						}
					}
				} else {
					// `page_value` variable that we transfer from 3rd-party hook for getting filtered option value also
					$page_value = $pre_result['page_value'];
					unset( $pre_result['page_value'] );

					$options = $pre_result;
				}

				$page_setting_description = '';
				if ( ! empty( $page_value ) ) {
					$content = get_the_content( null, false, $page_value );
					switch ( $slug ) {
						case 'account':
							if ( $page_value === um_get_predefined_page_id( 'user' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Account page and User page must be separate pages.', 'ultimate-member' );
							} elseif ( ! has_shortcode( $content, 'ultimatemember_account' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Account page must contain shortcode <code>[ultimatemember_account]</code>.', 'ultimate-member' );
							} elseif ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'myaccount' ) === um_get_predefined_page_id( 'account' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Account page and WooCommerce "My account" page should be separate pages.', 'ultimate-member' );
							}
							break;
						case 'login':
							if ( $page_value === um_get_predefined_page_id( 'logout' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Login page and Logout page must be separate pages.', 'ultimate-member' );
							} elseif ( ! has_shortcode( $content, 'ultimatemember' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Login page must contain a login form shortcode. You can get existing shortcode or create a new one <a href="edit.php?post_type=um_form" target="_blank">here</a>.', 'ultimate-member' );
							}
							break;
						case 'logout':
							if ( $page_value === (int) get_option( 'page_on_front' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Home page and Logout page must be separate pages.', 'ultimate-member' );
							} elseif ( $page_value === um_get_predefined_page_id( 'login' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Login page and Logout page must be separate pages.', 'ultimate-member' );
							}
							break;
						case 'password-reset':
							if ( ! has_shortcode( $content, 'ultimatemember_password' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Password Reset page must contain shortcode <code>[ultimatemember_password]</code>.', 'ultimate-member' );
							}
							break;
						case 'register':
							if ( ! has_shortcode( $content, 'ultimatemember' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Register page must contain a registration form shortcode. You can get existing shortcode or create a new one <a href="edit.php?post_type=um_form" target="_blank">here</a>.', 'ultimate-member' );
							}
							break;
						case 'user':
							if ( $page_value === um_get_predefined_page_id( 'account' ) ) {
								$description = __( '<strong>Warning:</strong> Account page and User page must be separate pages.', 'ultimate-member' );
							} elseif ( ! has_shortcode( $content, 'ultimatemember' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> User page must contain a profile form shortcode. You can get existing shortcode or create a new one <a href="edit.php?post_type=um_form" target="_blank">here</a>.', 'ultimate-member' );
							}
							break;
						case 'members':
							if ( ! has_shortcode( $content, 'ultimatemember' ) ) {
								$page_setting_description = __( '<strong>Warning:</strong> Members page must contain a profile form shortcode. You can get existing shortcode or create a new one <a href="edit.php?post_type=um_directory" target="_blank">here</a>.', 'ultimate-member' );
							}
							break;
						default:
							$page_setting_description = apply_filters( 'um_pages_settings_description', $page_setting_description, $content, $slug );
							break;
					}
				}

				$general_pages_fields[] = array(
					'id'          => $page_id,
					'type'        => 'page_select',
					// translators: %s: Page title
					'label'       => sprintf( __( '%s page', 'ultimate-member' ), $page_title ),
					'options'     => $options,
					'value'       => $page_value,
					'placeholder' => __( 'Choose a page...', 'ultimate-member' ),
					'size'        => 'small',
					'description' => $page_setting_description,
				);

				$settings_map[ $page_id ] = array(
					'sanitize' => 'absint',
				);
			}

			$appearances_profile_menu_fields = array(
				array(
					'id'             => 'profile_menu',
					'type'           => 'checkbox',
					'label'          => __( 'Profile menu', 'ultimate-member' ),
					'checkbox_label' => __( 'Enable profile menu', 'ultimate-member' ),
				),
			);

			$settings_map['profile_menu'] = array(
				'sanitize' => 'bool',
			);

			$tabs = UM()->profile()->tabs();

			$tabs_options   = array();
			$tabs_condition = array();
			foreach ( $tabs as $id => $tab ) {

				if ( ! empty( $tab['hidden'] ) ) {
					continue;
				}

				if ( isset( $tab['name'] ) ) {
					$tabs_options[ $id ] = $tab['name'];
					$tabs_condition[]    = 'profile_tab_' . $id;
				}

				if ( isset( $tab['default_privacy'] ) ) {
					$fields = array(
						array(
							'id'             => 'profile_tab_' . $id,
							'type'           => 'checkbox',
							// translators: %s: Tab title
							'label'          => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
							// translators: %s: Tab title
							'checkbox_label' => sprintf( __( 'Enable %s Tab', 'ultimate-member' ), $tab['name'] ),
							'conditional'    => array( 'profile_menu', '=', 1 ),
							'data'           => array( 'fill_profile_menu_default_tab' => $id ),
						),
					);

					$settings_map[ 'profile_tab_' . $id ] = array(
						'sanitize' => 'bool',
					);
				} else {

					$fields = array(
						array(
							'id'             => 'profile_tab_' . $id,
							'type'           => 'checkbox',
							// translators: %s: Tab title
							'label'          => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
							'checkbox_label' => sprintf( __( 'Enable %s Tab', 'ultimate-member' ), $tab['name'] ),
							'conditional'    => array( 'profile_menu', '=', 1 ),
							'data'           => array( 'fill_profile_menu_default_tab' => $id ),
						),
						array(
							'id'          => 'profile_tab_' . $id . '_privacy',
							'type'        => 'select',
							// translators: %s: Tab title
							'label'       => sprintf( __( 'Who can see %s Tab?', 'ultimate-member' ), $tab['name'] ),
							'description' => __( 'Select which users can view this tab.', 'ultimate-member' ),
							'options'     => UM()->profile()->tabs_privacy(),
							'conditional' => array( 'profile_tab_' . $id, '=', 1 ),
							'size'        => 'small',
						),
						array(
							'id'          => 'profile_tab_' . $id . '_roles',
							'type'        => 'select',
							'multi'       => true,
							'label'       => __( 'Allowed roles', 'ultimate-member' ),
							'description' => __( 'Select the the user roles allowed to view this tab.', 'ultimate-member' ),
							'options'     => UM()->roles()->get_roles(),
							'placeholder' => __( 'Choose user roles...', 'ultimate-member' ),
							'conditional' => array( 'profile_tab_' . $id . '_privacy', '=', array( '4', '5' ) ),
							'size'        => 'small',
						),
					);

					$settings_map = array_merge(
						$settings_map,
						array(
							"profile_tab_{$id}"         => array(
								'sanitize' => 'bool',
							),
							"profile_tab_{$id}_privacy" => array(
								'sanitize' => array( UM()->admin(), 'sanitize_tabs_privacy' ),
							),
							"profile_tab_{$id}_roles"   => array(
								'sanitize' => array( UM()->admin(), 'sanitize_existed_role' ),
							),
						)
					);
				}

				$appearances_profile_menu_fields = array_merge( $appearances_profile_menu_fields, $fields );
			}

			$appearances_profile_menu_fields[] = array(
				'id'          => 'profile_menu_default_tab',
				'type'        => 'select',
				'label'       => __( 'Profile menu default tab', 'ultimate-member' ),
				'description' => __( 'This will be the default tab on user profile page.', 'ultimate-member' ),
				'options'     => $tabs_options,
				'conditional' => array( implode( '|', $tabs_condition ), '~', 1 ),
				'size'        => 'small',
			);

			$settings_map['profile_menu_default_tab'] = array(
				'sanitize' => 'key',
			);

			$appearances_profile_menu_fields = array_merge(
				$appearances_profile_menu_fields,
				array(
					array(
						'id'             => 'profile_menu_icons',
						'type'           => 'checkbox',
						'label'          => __( 'Menu icons in desktop view', 'ultimate-member' ),
						'checkbox_label' => __( 'Enable menu icons in desktop view', 'ultimate-member' ),
						'description'    => __( '"Desktop view" means the profile block\'s width lower than 800px.', 'ultimate-member' ),
						'conditional'    => array( 'profile_menu', '=', 1 ),
					),
				)
			);

			$settings_map['profile_menu_icons'] = array(
				'sanitize' => 'bool',
			);

			$post_types_options = array();
			$all_post_types     = get_post_types( array( 'public' => true ), 'objects' );
			foreach ( $all_post_types as $key => $post_type_data ) {
				$post_types_options[ $key ] = $post_type_data->labels->singular_name;
			}

			$duplicates         = array();
			$taxonomies_options = array();
			$exclude_taxonomies = UM()->excluded_taxonomies();
			$all_taxonomies     = get_taxonomies(
				array(
					'public'  => true,
					'show_ui' => true,
				),
				'objects'
			);
			foreach ( $all_taxonomies as $key => $taxonomy ) {
				if ( in_array( $key, $exclude_taxonomies, true ) ) {
					continue;
				}

				if ( ! in_array( $taxonomy->labels->singular_name, $duplicates, true ) ) {
					$duplicates[] = $taxonomy->labels->singular_name;
					$label        = $taxonomy->labels->singular_name;
				} else {
					$label = $taxonomy->labels->singular_name . ' (' . $key . ')';
				}

				$taxonomies_options[ $key ] = $label;
			}

			$restricted_access_post_metabox_value = array();
			$restricted_access_post_metabox       = UM()->options()->get( 'restricted_access_post_metabox' );
			if ( ! empty( $restricted_access_post_metabox ) && is_array( $restricted_access_post_metabox ) ) {
				foreach ( $restricted_access_post_metabox as $key => $value ) {
					if ( $value ) {
						$restricted_access_post_metabox_value[] = $key;
					}
				}
			}

			$restricted_access_taxonomy_metabox_value = array();
			$restricted_access_taxonomy_metabox       = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
			if ( ! empty( $restricted_access_taxonomy_metabox ) && is_array( $restricted_access_taxonomy_metabox ) ) {
				foreach ( $restricted_access_taxonomy_metabox as $key => $value ) {
					if ( $value ) {
						$restricted_access_taxonomy_metabox_value[] = $key;
					}
				}
			}

			$access_fields = array(
				array(
					'id'          => 'accessible',
					'type'        => 'select',
					'label'       => __( 'Global Site Access', 'ultimate-member' ),
					'description' => __( 'Globally control the access of your site, you can have separate restrict options per post/page by editing the desired item.', 'ultimate-member' ),
					'options'     => array(
						0 => __( 'Site accessible to Everyone', 'ultimate-member' ),
						2 => __( 'Site accessible to Logged In Users', 'ultimate-member' ),
					),
					'size'        => 'medium',
				),
				array(
					'id'          => 'access_redirect',
					'type'        => 'text',
					'label'       => __( 'Custom Redirect URL', 'ultimate-member' ),
					'description' => __( 'A logged out user will be redirected to this url If he is not permitted to access the site.', 'ultimate-member' ),
					'conditional' => array( 'accessible', '=', 2 ),
				),
				array(
					'id'                  => 'access_exclude_uris',
					'type'                => 'multi_text',
					'label'               => __( 'Exclude the following URLs', 'ultimate-member' ),
					'description'         => __( 'Here you can exclude URLs beside the redirect URI to be accessible to everyone.', 'ultimate-member' ),
					'add_text'            => __( 'Add New URL', 'ultimate-member' ),
					'conditional'         => array( 'accessible', '=', 2 ),
					'show_default_number' => 0,
				),
				array(
					'id'             => 'home_page_accessible',
					'type'           => 'checkbox',
					'label'          => __( 'Allow Homepage to be accessible', 'ultimate-member' ),
					'checkbox_label' => __( 'Accessible Homepage', 'ultimate-member' ),
					'conditional'    => array( 'accessible', '=', 2 ),
				),
				array(
					'id'             => 'category_page_accessible',
					'type'           => 'checkbox',
					'label'          => __( 'Allow Category pages to be accessible', 'ultimate-member' ),
					'checkbox_label' => __( 'Accessible Category pages', 'ultimate-member' ),
					'conditional'    => array( 'accessible', '=', 2 ),
				),
				array(
					'id'             => 'restricted_post_title_replace',
					'type'           => 'checkbox',
					'label'          => __( 'Restricted Post Title', 'ultimate-member' ),
					'checkbox_label' => __( 'Replace the restricted Post Title', 'ultimate-member' ),
					'description'    => __( 'Allow to replace the restricted post title to users that do not have permission to view the content.', 'ultimate-member' ),
				),
				array(
					'id'          => 'restricted_access_post_title',
					'type'        => 'text',
					'label'       => __( 'Restricted Access Post Title', 'ultimate-member' ),
					'description' => __( 'If enabled, the text entered below will replace the title of the post/page/CPT for users who do not have permission to view the restricted content. Please see this doc for more information on this.', 'ultimate-member' ),
					'conditional' => array( 'restricted_post_title_replace', '=', 1 ),
				),
				array(
					'id'          => 'restricted_access_message',
					'type'        => 'wp_editor',
					'label'       => __( 'Restricted Access Message', 'ultimate-member' ),
					'description' => __( 'This is the message shown to users that do not have permission to view the content.', 'ultimate-member' ),
				),
			);

			$settings_map = array_merge(
				$settings_map,
				array(
					'accessible'                    => array(
						'sanitize' => 'int',
					),
					'access_redirect'               => array(
						'sanitize' => 'url',
					),
					'access_exclude_uris'           => array(
						'sanitize' => 'url',
					),
					'home_page_accessible'          => array(
						'sanitize' => 'bool',
					),
					'category_page_accessible'      => array(
						'sanitize' => 'bool',
					),
					'restricted_post_title_replace' => array(
						'sanitize' => 'bool',
					),
					'restricted_access_post_title'  => array(
						'sanitize' => 'text',
					),
					'restricted_access_message'     => array(
						'sanitize' => 'wp_kses',
					),
				)
			);

			global $wp_version;
			if ( version_compare( $wp_version, '5.0', '>=' ) ) {
				$access_fields = array_merge(
					$access_fields,
					array(
						array(
							'id'             => 'restricted_blocks',
							'type'           => 'checkbox',
							'label'          => __( 'Restricted Gutenberg Blocks', 'ultimate-member' ),
							'checkbox_label' => __( 'Enable the "Content Restriction" settings for the Gutenberg Blocks', 'ultimate-member' ),
							'description'    => __( 'If enabled then allows to set the blocks restriction settings in wp-admin.', 'ultimate-member' ),
						),
						array(
							'id'          => 'restricted_block_message',
							'type'        => 'wp_editor',
							'label'       => __( 'Restricted Access Block Message', 'ultimate-member' ),
							'description' => __( 'This is the message shown to users that do not have permission to view the block\'s content.', 'ultimate-member' ),
							'conditional' => array( 'restricted_blocks', '=', 1 ),
						),
					)
				);

				$settings_map['restricted_blocks']        = array(
					'sanitize' => 'bool',
				);
				$settings_map['restricted_block_message'] = array(
					'sanitize' => 'wp_kses',
				);
			}

			$access_fields = array_merge(
				$access_fields,
				array(
					array(
						'id'    => 'restricted_access_post_metabox',
						'type'  => 'hidden',
						'value' => '',
					),
					array(
						'id'    => 'restricted_access_taxonomy_metabox',
						'type'  => 'hidden',
						'value' => '',
					),
					array(
						'id'          => 'restricted_access_post_metabox',
						'type'        => 'multi_checkbox',
						'label'       => __( 'Enable the "Content Restriction" settings for post types', 'ultimate-member' ),
						'description' => __( 'Check post types for which you plan to use the "Content Restriction" settings.', 'ultimate-member' ),
						'options'     => $post_types_options,
						'columns'     => 3,
						'value'       => $restricted_access_post_metabox_value,
						'default'     => UM()->options()->get_default( 'restricted_access_post_metabox' ),
					),
					array(
						'id'          => 'restricted_access_taxonomy_metabox',
						'type'        => 'multi_checkbox',
						'label'       => __( 'Enable the "Content Restriction" settings for taxonomies', 'ultimate-member' ),
						'description' => __( 'Check taxonomies for which you plan to use the "Content Restriction" settings.', 'ultimate-member' ),
						'options'     => $taxonomies_options,
						'columns'     => 3,
						'value'       => $restricted_access_taxonomy_metabox_value,
						'default'     => UM()->options()->get_default( 'restricted_access_taxonomy_metabox' ),
					),
				)
			);

			$settings_map = array_merge(
				$settings_map,
				array(
					'restricted_access_post_metabox'     => array(
						'sanitize' => 'key',
					),
					'restricted_access_taxonomy_metabox' => array(
						'sanitize' => 'key',
					),
				)
			);

			$latest_update   = get_option( 'um_member_directory_update_meta', false );
			$latest_truncate = get_option( 'um_member_directory_truncated', false );

			$same_page_update = array(
				'id'             => 'member_directory_own_table',
				'type'           => 'same_page_update',
				'label'          => __( 'Custom usermeta table', 'ultimate-member' ),
				'checkbox_label' => __( 'Enable custom table for usermeta', 'ultimate-member' ),
				'description'    => __( 'Check this box if you would like to enable the use of a custom table for user metadata. Improved performance for member directory searches.', 'ultimate-member' ),
			);

			if ( empty( $latest_update ) || ( ! empty( $latest_truncate ) && $latest_truncate > $latest_update ) ) {
				$same_page_update['upgrade_cb']          = 'sync_metatable';
				$same_page_update['upgrade_description'] = '<p>' . __( 'We recommend creating a backup of your site before running the update process. Do not exit the page before the update process has complete.', 'ultimate-member' ) . '</p>
<p>' . __( 'After clicking the <strong>"Run"</strong> button, the update process will start. All information will be displayed in the field below.', 'ultimate-member' ) . '</p>
<p>' . __( 'If the update was successful, you will see a corresponding message. Otherwise, contact technical support if the update failed.', 'ultimate-member' ) . '</p>';
			}

			$settings_map = array_merge(
				$settings_map,
				array(
					'permalink_base'                       => array(
						'sanitize' => 'key',
					),
					'permalink_base_custom_meta'           => array(
						'sanitize' => 'text',
					),
					'display_name'                         => array(
						'sanitize' => 'key',
					),
					'display_name_field'                   => array(
						'sanitize' => 'text',
					),
					'author_redirect'                      => array(
						'sanitize' => 'bool',
					),
					'members_page'                         => array(
						'sanitize' => 'bool',
					),
					'use_gravatars'                        => array(
						'sanitize' => 'bool',
					),
					'use_um_gravatar_default_builtin_image' => array(
						'sanitize' => 'key',
					),
					'use_um_gravatar_default_image'        => array(
						'sanitize' => 'bool',
					),
					'delete_comments'                      => array(
						'sanitize' => 'bool',
					),
					'toggle_password'                      => array(
						'sanitize' => 'bool',
					),
					'require_strongpass'                   => array(
						'sanitize' => 'bool',
					),
					'password_min_chars'                   => array(
						'sanitize' => 'absint',
					),
					'password_max_chars'                   => array(
						'sanitize' => 'absint',
					),
					'profile_noindex'                      => array(
						'sanitize' => 'bool',
					),
					'activation_link_expiry_time'          => array(
						'sanitize' => 'empty_absint',
					),
					'account_tab_password'                 => array(
						'sanitize' => 'bool',
					),
					'account_tab_privacy'                  => array(
						'sanitize' => 'bool',
					),
					'account_tab_delete'                   => array(
						'sanitize' => 'bool',
					),
					'delete_account_text'                  => array(
						'sanitize' => 'textarea',
					),
					'delete_account_no_pass_required_text' => array(
						'sanitize' => 'textarea',
					),
					'account_name'                         => array(
						'sanitize' => 'bool',
					),
					'account_name_disable'                 => array(
						'sanitize' => 'bool',
					),
					'account_name_require'                 => array(
						'sanitize' => 'bool',
					),
					'account_email'                        => array(
						'sanitize' => 'bool',
					),
					'account_general_password'             => array(
						'sanitize' => 'bool',
					),
					'account_hide_in_directory'            => array(
						'sanitize' => 'bool',
					),
					'account_hide_in_directory_default'    => array(
						'sanitize' => 'text',
					),
					'profile_photo_max_size'               => array(
						'sanitize' => 'absint',
					),
					'cover_photo_max_size'                 => array(
						'sanitize' => 'absint',
					),
					'photo_thumb_sizes'                    => array(
						'sanitize' => 'absint',
					),
					'cover_thumb_sizes'                    => array(
						'sanitize' => 'absint',
					),
					'image_orientation_by_exif'            => array(
						'sanitize' => 'bool',
					),
					'image_compression'                    => array(
						'sanitize' => 'absint',
					),
					'image_max_width'                      => array(
						'sanitize' => 'absint',
					),
					'cover_min_width'                      => array(
						'sanitize' => 'absint',
					),
					'enable_reset_password_limit'          => array(
						'sanitize' => 'bool',
					),
					'reset_password_limit_number'          => array(
						'sanitize' => 'absint',
					),
					'change_password_request_limit'        => array(
						'sanitize' => 'bool',
					),
					'only_approved_user_reset_password'    => array(
						'sanitize' => 'bool',
					),
					'blocked_emails'                       => array(
						'sanitize' => 'textarea',
					),
					'blocked_words'                        => array(
						'sanitize' => 'textarea',
					),
					'allowed_choice_callbacks'             => array(
						'sanitize' => 'textarea',
					),
					'allow_url_redirect_confirm'           => array(
						'sanitize' => 'bool',
					),
					'admin_email'                          => array(
						'sanitize' => 'text',
					),
					'mail_from'                            => array(
						'sanitize' => 'text',
					),
					'mail_from_addr'                       => array(
						'sanitize' => 'text',
					),
					'email_html'                           => array(
						'sanitize' => 'bool',
					),
					'profile_template'                     => array(
						'sanitize' => 'text',
					),
					'profile_max_width'                    => array(
						'sanitize' => 'text',
					),
					'profile_area_max_width'               => array(
						'sanitize' => 'text',
					),
					'profile_icons'                        => array(
						'sanitize' => 'key',
					),
					'profile_primary_btn_word'             => array(
						'sanitize' => 'text',
					),
					'profile_secondary_btn'                => array(
						'sanitize' => 'bool',
					),
					'profile_secondary_btn_word'           => array(
						'sanitize' => 'text',
					),
					'default_avatar'                       => array(
						'sanitize' => 'url',
					),
					'default_cover'                        => array(
						'sanitize' => 'url',
					),
					'disable_profile_photo_upload'         => array(
						'sanitize' => 'bool',
					),
					'profile_photosize'                    => array(
						'sanitize' => array( UM()->admin(), 'sanitize_photosize' ),
					),
					'profile_cover_enabled'                => array(
						'sanitize' => 'bool',
					),
					'profile_coversize'                    => array(
						'sanitize' => array( UM()->admin(), 'sanitize_cover_photosize' ),
					),
					'profile_cover_ratio'                  => array(
						'sanitize' => 'text',
					),
					'profile_show_metaicon'                => array(
						'sanitize' => 'bool',
					),
					'profile_show_name'                    => array(
						'sanitize' => 'bool',
					),
					'profile_show_social_links'            => array(
						'sanitize' => 'bool',
					),
					'profile_show_bio'                     => array(
						'sanitize' => 'bool',
					),
					'profile_show_html_bio'                => array(
						'sanitize' => 'bool',
					),
					'profile_bio_maxchars'                 => array(
						'sanitize' => 'absint',
					),
					'profile_header_menu'                  => array(
						'sanitize' => 'key',
					),
					'profile_empty_text'                   => array(
						'sanitize' => 'bool',
					),
					'profile_empty_text_emo'               => array(
						'sanitize' => 'bool',
					),
					'register_template'                    => array(
						'sanitize' => 'text',
					),
					'register_max_width'                   => array(
						'sanitize' => 'text',
					),
					'register_align'                       => array(
						'sanitize' => 'key',
					),
					'register_icons'                       => array(
						'sanitize' => 'key',
					),
					'register_primary_btn_word'            => array(
						'sanitize' => 'text',
					),
					'register_secondary_btn'               => array(
						'sanitize' => 'bool',
					),
					'register_secondary_btn_word'          => array(
						'sanitize' => 'text',
					),
					'register_secondary_btn_url'           => array(
						'sanitize' => 'url',
					),
					'register_role'                        => array(
						'sanitize' => 'key',
					),
					'login_template'                       => array(
						'sanitize' => 'text',
					),
					'login_max_width'                      => array(
						'sanitize' => 'text',
					),
					'login_align'                          => array(
						'sanitize' => 'key',
					),
					'login_icons'                          => array(
						'sanitize' => 'key',
					),
					'login_primary_btn_word'               => array(
						'sanitize' => 'text',
					),
					'login_secondary_btn'                  => array(
						'sanitize' => 'bool',
					),
					'login_secondary_btn_word'             => array(
						'sanitize' => 'text',
					),
					'login_secondary_btn_url'              => array(
						'sanitize' => 'url',
					),
					'login_forgot_pass_link'               => array(
						'sanitize' => 'bool',
					),
					'login_show_rememberme'                => array(
						'sanitize' => 'bool',
					),
					'form_asterisk'                        => array(
						'sanitize' => 'bool',
					),
					'profile_title'                        => array(
						'sanitize' => 'text',
					),
					'profile_desc'                         => array(
						'sanitize' => 'textarea',
					),
					'um_profile_object_cache_stop'         => array(
						'sanitize' => 'bool',
					),
					'enable_blocks'                        => array(
						'sanitize' => 'bool',
					),
					'enable_action_scheduler'              => array(
						'sanitize' => 'bool',
					),
					'rest_api_version'                     => array(
						'sanitize' => 'text',
					),
					'disable_restriction_pre_queries'      => array(
						'sanitize' => 'bool',
					),
					'uninstall_on_delete'                  => array(
						'sanitize' => 'bool',
					),
					'lock_register_forms'                  => array(
						'sanitize' => 'bool',
					),
					'display_login_form_notice'            => array(
						'sanitize' => 'bool',
					),
					'banned_capabilities'                  => array(
						'sanitize' => array( UM()->admin(), 'sanitize_wp_capabilities_assoc' ),
					),
					'secure_notify_admins_banned_accounts' => array(
						'sanitize' => 'bool',
					),
					'secure_notify_admins_banned_accounts__interval' => array(
						'sanitize' => 'key',
					),
					'secure_allowed_redirect_hosts'        => array(
						'sanitize' => 'textarea',
					),
				)
			);

			if ( false !== UM()->account()->is_notifications_tab_visible() ) {
				$settings_map['account_tab_notifications'] = array(
					'sanitize' => 'bool',
				);
			}

			$this->settings_map = apply_filters( 'um_settings_map', $settings_map );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_settings_structure
			 * @description Extend UM Settings
			 * @input_vars
			 * [{"var":"$settings","type":"array","desc":"UM Settings"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_settings_structure', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_settings_structure', 'my_settings_structure', 10, 1 );
			 * function my_settings_structure( $settings ) {
			 *     // your code here
			 *     return $settings;
			 * }
			 * ?>
			 */
			$this->settings_structure = apply_filters(
				'um_settings_structure',
				array(
					''            => array(
						'title'    => __( 'General', 'ultimate-member' ),
						'sections' => array(
							''        => array(
								'title'       => __( 'Pages', 'ultimate-member' ),
								// translators: %s: Link to UM Docs
								'description' => sprintf( __( 'This section enables you to assign a page to one of the core elements necessary for the plugin\'s proper function. The plugin automatically creates and configures the required pages upon installation.<br />You only need to use this tab if you accidentally deleted pages that were automatically created during the initial plugin activation. <a href="%s" target="_blank">Learn more about manually creating pages</a>.', 'ultimate-member' ), 'https://docs.ultimatemember.com/article/1903-creating-plugin-core-pages-manually' ),
								'fields'      => $general_pages_fields,
							),
							'users'   => array(
								'title'         => __( 'Users', 'ultimate-member' ),
								'form_sections' => array(
									'users'    => array(
										'title'       => __( 'Users', 'ultimate-member' ),
										'description' => __( 'General users settings.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'register_role',
												'type'    => 'select',
												'label'   => __( 'Registration Default Role', 'ultimate-member' ),
												'description' => __( 'This will be the role assigned to users registering through Ultimate Member registration forms. By default, this setting will follow the core WordPress setting "New User Default Role" unless you specify a different role.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_role' ),
												'options' => UM()->roles()->get_roles( __( 'Default', 'ultimate-member' ) ),
												'size'    => 'small',
											),
											array(
												'id'      => 'permalink_base',
												'type'    => 'select',
												'size'    => 'small',
												'label'   => __( 'Profile Permalink Base', 'ultimate-member' ),
												// translators: %s: Profile page URL
												'description' => sprintf( __( 'Here you can control the permalink structure of the user profile URL globally e.g. %s<strong>username</strong>/.', 'ultimate-member' ), trailingslashit( um_get_core_page( 'user' ) ) ),
												'options' => UM()->config()->permalink_base_options,
												'placeholder' => __( 'Select...', 'ultimate-member' ),
											),
											array(
												'id'    => 'permalink_base_custom_meta',
												'type'  => 'text',
												'label' => __( 'Profile Permalink Base Custom Meta Key', 'ultimate-member' ),
												'description' => __( 'Specify the custom field meta key that you want to use as profile permalink base. Meta value should be unique.', 'ultimate-member' ),
												'conditional' => array( 'permalink_base', '=', 'custom_meta' ),
												'size'  => 'medium',
											),
											array(
												'id'      => 'display_name',
												'type'    => 'select',
												'size'    => 'medium',
												'label'   => __( 'User Display Name', 'ultimate-member' ),
												'description' => __( 'This is the name that will be displayed for users on the front end of your site. Default setting uses first/last name as display name if it exists.', 'ultimate-member' ),
												'options' => UM()->config()->display_name_options,
												'placeholder' => __( 'Select...', 'ultimate-member' ),
											),
											array(
												'id'    => 'display_name_field',
												'type'  => 'text',
												'label' => __( 'Display Name Custom Field(s)', 'ultimate-member' ),
												'description' => __( 'Specify the custom field meta key or custom fields seperated by comma that you want to use to display users name on the frontend of your site.', 'ultimate-member' ),
												'conditional' => array( 'display_name', '=', 'field' ),
												'size'  => 'medium',
											),
											array(
												'id'    => 'author_redirect',
												'type'  => 'checkbox',
												'label' => __( 'Hide author pages', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable author page redirect to user profile', 'ultimate-member' ),
												'description' => __( 'If enabled, author pages will automatically redirect to the user\'s profile page.', 'ultimate-member' ),
											),
											array(
												'id'    => 'members_page',
												'type'  => 'checkbox',
												'label' => __( 'Members Directory', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable Members Directory', 'ultimate-member' ),
												'description' => __( 'Control whether to enable or disable member directories on this site.', 'ultimate-member' ),
											),
											array(
												'id'    => 'use_gravatars',
												'type'  => 'checkbox',
												'label' => __( 'Use Gravatar', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable Gravatar', 'ultimate-member' ),
												'description' => __( 'Do you want to use Gravatar instead of the default plugin profile photo (If the user did not upload a custom profile photo/avatar)?', 'ultimate-member' ),
											),
											array(
												'id'      => 'use_um_gravatar_default_builtin_image',
												'type'    => 'select',
												'label'   => __( 'Use Gravatar builtin image', 'ultimate-member' ),
												'description' => __( 'Gravatar has a number of built in options which you can also use as defaults.', 'ultimate-member' ),
												'options' => array(
													'default' => __( 'Default', 'ultimate-member' ),
													'404' => __( '404 ( File Not Found response )', 'ultimate-member' ),
													'mm'  => __( 'Mystery Man', 'ultimate-member' ),
													'identicon' => __( 'Identicon', 'ultimate-member' ),
													'monsterid' => __( 'Monsterid', 'ultimate-member' ),
													'wavatar' => __( 'Wavatar', 'ultimate-member' ),
													'retro' => __( 'Retro', 'ultimate-member' ),
													'blank' => __( 'Blank ( a transparent PNG image )', 'ultimate-member' ),
												),
												'conditional' => array( 'use_gravatars', '=', 1 ),
												'size'    => 'medium',
											),
											array(
												'id'    => 'use_um_gravatar_default_image',
												'type'  => 'checkbox',
												'label' => __( 'Replace Gravatar\'s Default avatar', 'ultimate-member' ),
												'checkbox_label' => __( 'Set Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
												'description' => __( 'Do you want to use the plugin default avatar instead of the gravatar default photo (If the user did not upload a custom profile photo/avatar).', 'ultimate-member' ),
												'conditional' => array( 'use_um_gravatar_default_builtin_image', '=', 'default' ),
											),
											array(
												'id'    => 'delete_comments',
												'type'  => 'checkbox',
												'label' => __( 'Delete user comments', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable deleting user comments after deleting a user', 'ultimate-member' ),
												'description' => __( 'Do you want to automatically delete a user\'s comments when they delete their account or are removed from the admin dashboard?', 'ultimate-member' ),
											),
										),
									),
									'password' => array(
										'title'       => __( 'Password', 'ultimate-member' ),
										'description' => __( 'Password & Security settings.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'toggle_password',
												'type'  => 'checkbox',
												'label' => __( 'Toggle Password Visibility', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable password show/hide icon on password field', 'ultimate-member' ),
												'description' => __( 'Enable users to view their inputted password before submitting the form.', 'ultimate-member' ),
											),
											array(
												'id'    => 'require_strongpass',
												'type'  => 'checkbox',
												'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable strong passwords', 'ultimate-member' ),
												'description' => __( 'Enable this option to apply strong password rules to all password fields (user registration, password reset and password change).', 'ultimate-member' ),
											),
											array(
												'id'    => 'password_min_chars',
												'type'  => 'number',
												'label' => __( 'Password minimum length', 'ultimate-member' ),
												'description' => __( 'Enter the minimum number of characters a user must use for their password. The default minimum characters is 8.', 'ultimate-member' ),
												'size'  => 'small',
												'conditional' => array( 'require_strongpass', '=', '1' ),
											),
											array(
												'id'    => 'password_max_chars',
												'type'  => 'number',
												'label' => __( 'Password maximum length', 'ultimate-member' ),
												'description' => __( 'Enter the maximum number of characters a user can use for their password. The default maximum characters is 30.', 'ultimate-member' ),
												'size'  => 'small',
												'conditional' => array( 'require_strongpass', '=', '1' ),
											),
											array(
												'id'    => 'activation_link_expiry_time',
												'type'  => 'number',
												'label' => __( 'Email activation link expiration (days)', 'ultimate-member' ),
												'description' => __( 'For user registrations requiring email confirmation via a link, how long should the activation link remain active before expiring? If this field is left blank, the activation link will not expire.', 'ultimate-member' ),
												'size'  => 'small',
											),
										),
									),
									'seo'      => array(
										'title'       => __( 'SEO', 'ultimate-member' ),
										'description' => __( 'SEO settings for the user profiles.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'profile_noindex',
												'type'    => 'select',
												'size'    => 'small',
												'label'   => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
												'description' => __( 'Hides the profile page for robots. This setting can be overridden by individual role settings.', 'ultimate-member' ),
												'options' => array(
													'0' => __( 'No', 'ultimate-member' ),
													'1' => __( 'Yes', 'ultimate-member' ),
												),
											),
											array(
												'id'    => 'profile_title',
												'type'  => 'text',
												'label' => __( 'User Profile Title', 'ultimate-member' ),
												'description' => __( 'This is the title that is displayed on a specific user profile.', 'ultimate-member' ),
												'size'  => 'medium',
											),
											array(
												'id'    => 'profile_desc',
												'type'  => 'textarea',
												'label' => __( 'User Profile Dynamic Meta Description', 'ultimate-member' ),
												'description' => __( 'This will be used in the meta description that is available for search-engines.', 'ultimate-member' ),
												'args'  => array(
													'textarea_rows' => 6,
												),
											),
										),
									),
								),
							),
							'account' => array(
								'title'         => __( 'Account', 'ultimate-member' ),
								'form_sections' => array(
									'account_tab'       => array(
										'title'       => __( 'Main account tab', 'ultimate-member' ),
										'description' => __( 'Allows you to control the fields on the main tab of Account page.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'account_name',
												'type'  => 'checkbox',
												'label' => __( 'Display First & Last name fields', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable to display First & Last name fields', 'ultimate-member' ),
												'description' => __( 'If enabled, the First & Last name fields will be shown on the account page.', 'ultimate-member' ),
											),
											array(
												'id'    => 'account_name_disable',
												'type'  => 'checkbox',
												'label' => __( 'Disable First & Last name field editing', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable to prevent First & Last name editing by users', 'ultimate-member' ),
												'description' => __( 'If enabled, this feature will prevent users from changing their first and last names on the account page.', 'ultimate-member' ),
												'conditional' => array( 'account_name', '=', '1' ),
											),
											array(
												'id'    => 'account_name_require',
												'type'  => 'checkbox',
												'label' => __( 'Require First & Last Name', 'ultimate-member' ),
												'checkbox_label' => __( 'First and last name fields are required', 'ultimate-member' ),
												'description' => __( 'If enabled, users will not be allowed to remove their first or last names when updating their account information.', 'ultimate-member' ),
												'conditional' => array( 'account_name', '=', '1' ),
											),
											array(
												'id'    => 'account_email',
												'type'  => 'checkbox',
												'label' => __( 'Allow users to change email', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable changing email via the account page', 'ultimate-member' ),
												'description' => __( 'If disabled, users will not be allowed to change their email address on the account page.', 'ultimate-member' ),
											),
											array(
												'id'    => 'account_general_password',
												'type'  => 'checkbox',
												'label' => __( 'Require password to update account', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable required password', 'ultimate-member' ),
												'description' => __( 'If enabled, users will need to enter their password when updating their information via the account page.', 'ultimate-member' ),
											),
										),
									),
									'password_tab'      => array(
										'title'       => __( 'Change password tab', 'ultimate-member' ),
										'description' => __( 'Enables you to toggle the change password tab on the account page.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'account_tab_password',
												'type'  => 'checkbox',
												'label' => __( 'Password Account Tab', 'ultimate-member' ),
												'checkbox_label' => __( 'Display password change account tab', 'ultimate-member' ),
												'description' => __( 'Enable or disable the "Password" tab on the account page.', 'ultimate-member' ),
											),
										),
									),
									'privacy_tab'       => array(
										'title'       => __( 'Privacy tab', 'ultimate-member' ),
										'description' => __( 'Enables you to toggle the privacy tab on the account page. Disable this tab to prevent users from altering their privacy settings.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'account_tab_privacy',
												'type'  => 'checkbox',
												'label' => __( 'Privacy Account Tab', 'ultimate-member' ),
												'checkbox_label' => __( 'Display privacy account tab', 'ultimate-member' ),
												'description' => __( 'Enable or disable the "Privacy" tab on the account page.', 'ultimate-member' ),
											),
											array(
												'id'    => 'account_hide_in_directory',
												'type'  => 'checkbox',
												'label' => __( 'Allow users to hide their profiles from directory', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable users ability to alter their profile visibility in member directories', 'ultimate-member' ),
												'description' => __( 'If enabled, this will allow users to change their profile visibility in the member directory from the account page.', 'ultimate-member' ),
												'conditional' => array( 'account_tab_privacy', '=', '1' ),
											),
											array(
												'id'      => 'account_hide_in_directory_default',
												'type'    => 'select',
												'label'   => __( 'Hide profiles from directory by default', 'ultimate-member' ),
												'description' => __( 'Set the default value for the "Hide my profile from directory" option.', 'ultimate-member' ),
												'options' => array(
													'No'  => __( 'No', 'ultimate-member' ),
													'Yes' => __( 'Yes', 'ultimate-member' ),
												),
												'size'    => 'small',
												'conditional' => array( 'account_hide_in_directory', '=', '1' ),
											),
										),
									),
									'notifications_tab' => array(
										'title'       => __( 'Notifications tab', 'ultimate-member' ),
										'description' => __( 'Enables you to toggle the notifications tab on the account page. Disable this tab to prevent users from altering their notifications settings.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'account_tab_notifications',
												'type'  => 'checkbox',
												'label' => __( 'Notifications Account Tab', 'ultimate-member' ),
												'checkbox_label' => __( 'Display notifications account tab', 'ultimate-member' ),
												'description' => __( 'Enable or disable the "Notifications" tab on the account page.', 'ultimate-member' ),
											),
										),
									),
									'delete_tab'        => array(
										'title'       => __( 'Delete tab', 'ultimate-member' ),
										'description' => __( 'Enables you to enable or disable the "Delete Account" tab on the account page. Disable this tab if you wish to prevent users from being able to delete their own accounts.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'account_tab_delete',
												'type'  => 'checkbox',
												'label' => __( 'Delete Account Tab', 'ultimate-member' ),
												'checkbox_label' => __( 'Display delete account tab', 'ultimate-member' ),
												'description' => __( 'Enable/disable the Delete account tab in account page.', 'ultimate-member' ),
											),
											array(
												'id'    => 'delete_account_text',
												'type'  => 'textarea', // bug with wp 4.4? should be editor
												'label' => __( 'Account Deletion Text', 'ultimate-member' ),
												'description' => __( 'This is the custom text that will be displayed to users before they delete their account from your website when their password is required to confirm account deletion.', 'ultimate-member' ),
												'args'  => array(
													'textarea_rows' => 6,
												),
											),
											array(
												'id'    => 'delete_account_no_pass_required_text',
												'type'  => 'textarea',
												'label' => __( 'Account Deletion Text', 'ultimate-member' ),
												'description' => __( 'This is the custom text that will be displayed to users before they delete their account from your website when no password is required to confirm account deletion.', 'ultimate-member' ),
												'args'  => array(
													'textarea_rows' => 6,
												),
											),
										),
									),
								),
							),
							'uploads' => array(
								'title'         => __( 'Uploads', 'ultimate-member' ),
								'form_sections' => array(
									'uploads'       => array(
										'title'       => __( 'Uploads', 'ultimate-member' ),
										'description' => __( 'This page allows you to manage user upload options, enabling you to optimize photos for your site.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'image_orientation_by_exif',
												'type'  => 'checkbox',
												'label' => __( 'Change image orientation', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable getting image orientation from EXIF data', 'ultimate-member' ),
												'description' => __( 'Rotate image to and use orientation by the camera EXIF data.', 'ultimate-member' ),
											),
											array(
												'id'    => 'image_compression',
												'type'  => 'text',
												'size'  => 'small',
												'label' => __( 'Image Quality', 'ultimate-member' ),
												'description' => __( 'Quality is used to determine quality of image uploads, and ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file). The default range is 60.', 'ultimate-member' ),
											),
											array(
												'id'    => 'image_max_width',
												'type'  => 'text',
												'size'  => 'small',
												'label' => __( 'Image Upload Maximum Width (px)', 'ultimate-member' ),
												'description' => __( 'Any image upload above this width will be resized to this limit automatically.', 'ultimate-member' ),
											),
										),
									),
									'profile_photo' => array(
										'title'       => __( 'Profile photo', 'ultimate-member' ),
										'description' => __( 'Allows you to control the profile photos sizes, thumbnails, etc.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'profile_photo_max_size',
												'type'  => 'text',
												'size'  => 'small',
												'label' => __( 'Profile Photo Maximum File Size (bytes)', 'ultimate-member' ),
												'description' => __( 'Sets a maximum size for the uploaded photo.', 'ultimate-member' ),
											),
											array(
												'id'       => 'photo_thumb_sizes',
												'type'     => 'multi_text',
												'size'     => 'small',
												'label'    => __( 'Profile Photo Thumbnail Sizes (px)', 'ultimate-member' ),
												'description' => __( 'Here you can define which thumbnail sizes will be created for each profile photo upload.', 'ultimate-member' ),
												'validate' => 'numeric',
												'add_text' => __( 'Add New Size', 'ultimate-member' ),
												'show_default_number' => 1,
											),
										),
									),
									'cover_photo'   => array(
										'title'       => __( 'Cover photo', 'ultimate-member' ),
										'description' => __( 'Allows you to control the cover photos sizes, thumbnails, etc.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'cover_photo_max_size',
												'type'  => 'text',
												'size'  => 'small',
												'label' => __( 'Cover Photo Maximum File Size (bytes)', 'ultimate-member' ),
												'description' => __( 'Sets a maximum size for the uploaded cover.', 'ultimate-member' ),
											),
											array(
												'id'    => 'cover_min_width',
												'type'  => 'text',
												'size'  => 'small',
												'label' => __( 'Cover Photo Minimum Width (px)', 'ultimate-member' ),
												'description' => __( 'This will be the minimum width for cover photo uploads.', 'ultimate-member' ),
											),
											array(
												'id'       => 'cover_thumb_sizes',
												'type'     => 'multi_text',
												'size'     => 'small',
												'label'    => __( 'Cover Photo Thumbnail Sizes (px)', 'ultimate-member' ),
												'description' => __( 'Here you can define which thumbnail sizes will be created for each cover photo upload.', 'ultimate-member' ),
												'validate' => 'numeric',
												'add_text' => __( 'Add New Size', 'ultimate-member' ),
												'show_default_number' => 1,
											),
										),
									),
								),
							),
						),
					),
					'access'      => array(
						'title'    => __( 'Access', 'ultimate-member' ),
						'sections' => array(
							''      => array(
								'title'       => __( 'Restriction Content', 'ultimate-member' ),
								'description' => __( 'Provides  settings for controlling access to your site.', 'ultimate-member' ),
								'fields'      => $access_fields,
							),
							'other' => array(
								'title'         => __( 'Other', 'ultimate-member' ),
								'form_sections' => array(
									'rp'      => array(
										'title'       => __( 'Reset Password', 'ultimate-member' ),
										'description' => __( 'Allows to manage reset password settings.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'enable_reset_password_limit',
												'type'  => 'checkbox',
												'label' => __( 'Password reset limit', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable the Reset Password Limit?', 'ultimate-member' ),
												'description' => __( 'If enabled, this sets a limit on the number of password resets a user can do.', 'ultimate-member' ),
											),
											array(
												'id'       => 'reset_password_limit_number',
												'type'     => 'text',
												'label'    => __( 'Enter password reset limit', 'ultimate-member' ),
												'description' => __( 'Set the maximum reset password limit. If reached the maximum limit, user will be locked from using this.', 'ultimate-member' ),
												'validate' => 'numeric',
												'conditional' => array( 'enable_reset_password_limit', '=', 1 ),
												'size'     => 'small',
											),
											array(
												'id'    => 'change_password_request_limit',
												'type'  => 'checkbox',
												'label' => __( 'Change Password request limit', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable limit for changing password', 'ultimate-member' ),
												'description' => __( 'This option adds rate limit when submitting the change password form in the Account page. Users are only allowed to submit 1 request per 30 minutes to prevent from any brute-force attacks or password guessing with the form.', 'ultimate-member' ),
											),
											array(
												'id'    => 'only_approved_user_reset_password',
												'type'  => 'checkbox',
												'label' => __( 'Only approved user Reset Password', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable reset password only for approved users', 'ultimate-member' ),
												'description' => __( 'This option makes possible to reset password only for approved user. Is used to prevent from any spam email attacks from not approved users.', 'ultimate-member' ),
											),
										),
									),
									'blocked' => array(
										'title'       => __( 'Blocked data when sign up', 'ultimate-member' ),
										'description' => __( 'Allows to manage blocked data of signed up user.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'blocked_emails',
												'type'  => 'textarea',
												'label' => __( 'Blocked Email Addresses (Enter one email per line)', 'ultimate-member' ),
												'description' => __( 'This will block the specified email addresses from being able to sign up or sign in to your site. To block an entire domain, use something like `*@domain.com`.', 'ultimate-member' ),
											),
											array(
												'id'    => 'blocked_words',
												'type'  => 'textarea',
												'label' => __( 'Blacklist Words (Enter one word per line)', 'ultimate-member' ),
												'description' => __( 'This option lets you specify blacklist of words to prevent anyone from signing up with such a word as their username.', 'ultimate-member' ),
											),
										),
									),
								),
							),
						),
					),
					'email'       => array(
						'title'         => __( 'Emails', 'ultimate-member' ),
						'form_sections' => array(
							'email_sender'   => array(
								'title'       => __( 'Email sender options', 'ultimate-member' ),
								'description' => __( 'How the sender appears in outgoing Ultimate Member emails.', 'ultimate-member' ),
								'fields'      => array(
									array(
										'id'          => 'admin_email',
										'type'        => 'text',
										'label'       => __( 'Admin Email Address', 'ultimate-member' ),
										'description' => __( 'e.g. admin@companyname.com.', 'ultimate-member' ),
									),
									array(
										'id'          => 'mail_from',
										'type'        => 'text',
										'label'       => __( 'Mail appears from', 'ultimate-member' ),
										'description' => __( 'e.g. Site Name.', 'ultimate-member' ),
									),
									array(
										'id'          => 'mail_from_addr',
										'type'        => 'text',
										'label'       => __( 'Mail appears from address', 'ultimate-member' ),
										'description' => __( 'e.g. admin@companyname.com.', 'ultimate-member' ),
									),
								),
							),
							'email_template' => array(
								'title'       => __( 'Email template', 'ultimate-member' ),
								'description' => __( 'Section to customize email templates settings.', 'ultimate-member' ),
								'fields'      => array(
									array(
										'id'             => 'email_html',
										'type'           => 'checkbox',
										'label'          => __( 'Content type', 'ultimate-member' ),
										'checkbox_label' => __( 'Enable HTML for Emails', 'ultimate-member' ),
										'description'    => __( 'If you plan use emails with HTML, please make sure that this option is enabled. Otherwise, HTML will be displayed as plain text.', 'ultimate-member' ),
									),
								),
							),
						),
					),
					'appearance'  => array(
						'title'    => __( 'Appearance', 'ultimate-member' ),
						'sections' => array(
							''                  => array(
								'title'         => __( 'Profile', 'ultimate-member' ),
								'form_sections' => array(
									'template'      => array(
										'title'       => __( 'Template', 'ultimate-member' ),
										// translators: %s: Link to UM docs
										'description' => sprintf( __( 'This section allows you to customize the user profile template and size. <a href="%s" target="_blank">Learn more about custom profile template creation</a>.', 'ultimate-member' ), 'https://docs.ultimatemember.com/article/120-adding-your-custom-profile-templates' ),
										'fields'      => array(
											array(
												'id'      => 'profile_template',
												'type'    => 'select',
												'label'   => __( 'Profile Default Template', 'ultimate-member' ),
												'description' => __( 'This will be the default template to output profile.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_template' ),
												'options' => UM()->shortcodes()->get_templates( 'profile' ),
												'size'    => 'small',
											),
											array(
												'id'      => 'profile_max_width',
												'type'    => 'text',
												'label'   => __( 'Profile Maximum Width', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_max_width' ),
												'description' => __( 'The maximum width this shortcode can take from the page width.', 'ultimate-member' ),
												'size'    => 'small',
											),
											array(
												'id'      => 'profile_area_max_width',
												'type'    => 'text',
												'label'   => __( 'Profile Area Maximum Width', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_area_max_width' ),
												'description' => __( 'The maximum width of the profile area inside profile (below profile header).', 'ultimate-member' ),
												'size'    => 'small',
											),
										),
									),
									'profile_photo' => array(
										'title'       => __( 'Profile photo', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the profile photo component on the user profile.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'default_avatar',
												'type'    => 'media',
												'label'   => __( 'Default Profile Photo', 'ultimate-member' ),
												'description' => __( 'You can change the default profile picture globally here. Please make sure that the photo is 300x300px.', 'ultimate-member' ),
												'upload_frame_title' => __( 'Select Default Profile Photo', 'ultimate-member' ),
												'default' => array(
													'url' => UM_URL . 'assets/img/default_avatar.jpg',
												),
											),
											array(
												'id'      => 'disable_profile_photo_upload',
												'type'    => 'checkbox',
												'label'   => __( 'Profile Photo Upload', 'ultimate-member' ),
												'checkbox_label' => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
												'description' => __( 'Switch on/off the profile photo uploader.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'disable_profile_photo_upload' ),
											),
											array(
												'id'      => 'profile_photosize',
												'type'    => 'select',
												'label'   => __( 'Profile Photo Size', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_photosize' ),
												'options' => UM()->files()->get_profile_photo_size( 'photo_thumb_sizes' ),
												'description' => __( 'The global default of profile photo size. This can be overridden by individual form settings.', 'ultimate-member' ),
												'size'    => 'small',
											),
										),
									),
									'cover_photo'   => array(
										'title'       => __( 'Cover photo', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the profile photo component on the user profile.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'default_cover',
												'type'    => 'media',
												'url'     => true,
												'preview' => false,
												'label'   => __( 'Default Cover Photo', 'ultimate-member' ),
												'description' => __( 'You can change the default cover photo globally here. Please make sure that the default cover is large enough and respects the ratio you are using for cover photos.', 'ultimate-member' ),
												'upload_frame_title' => __( 'Select Default Cover Photo', 'ultimate-member' ),
											),

											array(
												'id'      => 'profile_cover_enabled',
												'type'    => 'checkbox',
												'label'   => __( 'Profile Cover Photos', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable Cover Photos', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_cover_enabled' ),
												'description' => __( 'Switch on/off the profile cover photos.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_coversize',
												'type'    => 'select',
												'label'   => __( 'Profile Cover Size', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_coversize' ),
												'options' => UM()->files()->get_profile_photo_size( 'cover_thumb_sizes' ),
												'description' => __( 'The global default width of cover photo size. This can be overridden by individual form settings.', 'ultimate-member' ),
												'conditional' => array( 'profile_cover_enabled', '=', 1 ),
												'size'    => 'small',
											),
											array(
												'id'      => 'profile_cover_ratio',
												'type'    => 'select',
												'label'   => __( 'Profile Cover Ratio', 'ultimate-member' ),
												'description' => __( 'Choose global ratio for cover photos of profiles.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_cover_ratio' ),
												'options' => array(
													'1.6:1' => '1.6:1',
													'2.7:1' => '2.7:1',
													'2.2:1' => '2.2:1',
													'3.2:1' => '3.2:1',
												),
												'conditional' => array( 'profile_cover_enabled', '=', 1 ),
												'size'    => 'small',
											),
										),
									),
									'header'        => array(
										'title'       => __( 'Header', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the user profile header component.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'profile_show_metaicon',
												'type'    => 'checkbox',
												'label'   => __( 'Profile Header Meta Text Icon', 'ultimate-member' ),
												'checkbox_label' => __( 'Show icons in Profile Header Meta', 'ultimate-member' ),
												'default' => 0,
												'description' => __( 'Display field icons for related user meta fields in header or not.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_show_name',
												'type'    => 'checkbox',
												'label'   => __( 'Display name in profile header', 'ultimate-member' ),
												'checkbox_label' => __( 'Show display name in profile header', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_show_name' ),
												'description' => __( 'Switch on/off the user name on profile header.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_show_social_links',
												'type'    => 'checkbox',
												'label'   => __( 'Social links in profile header', 'ultimate-member' ),
												'checkbox_label' => __( 'Show social links in profile header', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_show_social_links' ),
												'description' => __( 'Switch on/off the social links on profile header.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_show_bio',
												'type'    => 'checkbox',
												'label'   => __( 'User description in profile header', 'ultimate-member' ),
												'checkbox_label' => __( 'Show user description in profile header', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_show_bio' ),
												'description' => __( 'Switch on/off the user description on profile header.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_bio_maxchars',
												'type'    => 'text',
												'label'   => __( 'User description maximum chars', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_bio_maxchars' ),
												'description' => __( 'Maximum number of characters to allow in user description field in header.', 'ultimate-member' ),
												'conditional' => array( 'profile_show_bio', '=', 1 ),
												'size'    => 'small',
											),
											array(
												'id'    => 'profile_show_html_bio',
												'type'  => 'checkbox',
												'label' => __( 'HTML support for user description', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable HTML support for user description', 'ultimate-member' ),
												'description' => __( 'Switch on/off to enable/disable support for HTML tags on user description.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_header_menu',
												'type'    => 'select',
												'label'   => __( 'Profile Header Menu Position', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_header_menu' ),
												'description' => __( 'For incompatible themes, please make the menu open from left instead of bottom by default.', 'ultimate-member' ),
												'options' => array(
													'bc' => __( 'Bottom of Icon', 'ultimate-member' ),
													'lc' => __( 'Left of Icon (right for RTL)', 'ultimate-member' ),
												),
												'size'    => 'small',
											),
										),
									),
									'fields'        => array(
										'title'       => __( 'Buttons & Fields', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the user profile buttons and fields layout.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'profile_primary_btn_word',
												'type'    => 'text',
												'label'   => __( 'Profile Primary Button Text', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_primary_btn_word' ),
												'description' => __( 'The text that is used for updating profile button.', 'ultimate-member' ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'profile_secondary_btn',
												'type'    => 'checkbox',
												'label'   => __( 'Profile Secondary Button', 'ultimate-member' ),
												'checkbox_label' => __( 'Show Profile Secondary Button', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_secondary_btn' ),
												'description' => __( 'Switch on/off the secondary button display in the form.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_secondary_btn_word',
												'type'    => 'text',
												'label'   => __( 'Profile Secondary Button Text', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_secondary_btn_word' ),
												'description' => __( 'The text that is used for cancelling update profile button.', 'ultimate-member' ),
												'conditional' => array( 'profile_secondary_btn', '=', 1 ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'profile_icons',
												'type'    => 'select',
												'label'   => __( 'Profile Field Icons', 'ultimate-member' ),
												'description' => __( 'This is applicable for edit mode only.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_icons' ),
												'options' => array(
													'field' => __( 'Show inside text field', 'ultimate-member' ),
													'label' => __( 'Show with label', 'ultimate-member' ),
													'off' => __( 'Turn off', 'ultimate-member' ),
												),
												'size'    => 'small',
											),
											array(
												'id'      => 'profile_empty_text',
												'type'    => 'checkbox',
												'label'   => __( 'Custom message on empty profile', 'ultimate-member' ),
												'checkbox_label' => __( 'Show a custom message if profile is empty', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_empty_text' ),
												'description' => __( 'Switch on/off the custom message that appears when the profile is empty.', 'ultimate-member' ),
											),
											array(
												'id'      => 'profile_empty_text_emo',
												'type'    => 'checkbox',
												'label'   => __( 'Custom message emoticon', 'ultimate-member' ),
												'checkbox_label' => __( 'Show the emoticon', 'ultimate-member' ),
												'default' => um_get_metadefault( 'profile_empty_text_emo' ),
												'description' => __( 'Switch on/off the emoticon (sad face) that appears above the message.', 'ultimate-member' ),
												'conditional' => array( 'profile_empty_text', '=', 1 ),
											),
										),
									),
								),
							),
							'profile_menu'      => array(
								'title'       => __( 'Profile Menu', 'ultimate-member' ),
								'description' => __( 'This section allows you to customize the user profiles menus on your site.', 'ultimate-member' ),
								'fields'      => $appearances_profile_menu_fields,
							),
							'registration_form' => array(
								'title'         => __( 'Registration Form', 'ultimate-member' ),
								'form_sections' => array(
									'template' => array(
										'title'       => __( 'Template', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the user registration template and size.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'register_template',
												'type'    => 'select',
												'label'   => __( 'Registration Default Template', 'ultimate-member' ),
												'description' => __( 'This will be the default template to output registration.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_template' ),
												'options' => UM()->shortcodes()->get_templates( 'register' ),
												'size'    => 'small',
											),
											array(
												'id'      => 'register_max_width',
												'type'    => 'text',
												'label'   => __( 'Registration Maximum Width', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_max_width' ),
												'description' => __( 'The maximum width this shortcode can take from the page width.', 'ultimate-member' ),
												'size'    => 'small',
											),
											array(
												'id'      => 'register_align',
												'type'    => 'select',
												'label'   => __( 'Registration Shortcode Alignment', 'ultimate-member' ),
												'description' => __( 'The shortcode is centered by default unless you specify otherwise here.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_align' ),
												'options' => array(
													'center' => __( 'Centered', 'ultimate-member' ),
													'left' => __( 'Left aligned', 'ultimate-member' ),
													'right' => __( 'Right aligned', 'ultimate-member' ),
												),
												'size'    => 'small',
											),
										),
									),
									'fields'   => array(
										'title'       => __( 'Buttons & Fields', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the user registration buttons and fields layout.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'register_primary_btn_word',
												'type'    => 'text',
												'label'   => __( 'Registration Primary Button Text', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_primary_btn_word' ),
												'description' => __( 'The text that is used for primary button text.', 'ultimate-member' ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'register_secondary_btn',
												'type'    => 'checkbox',
												'label'   => __( 'Registration Secondary Button', 'ultimate-member' ),
												'checkbox_label' => __( 'Show Registration Secondary Button', 'ultimate-member' ),
												'default' => 1,
												'description' => __( 'Switch on/off the secondary button display in the form.', 'ultimate-member' ),
											),
											array(
												'id'      => 'register_secondary_btn_word',
												'type'    => 'text',
												'label'   => __( 'Registration Secondary Button Text', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_secondary_btn_word' ),
												'description' => __( 'The text that is used for the secondary button text.', 'ultimate-member' ),
												'conditional' => array( 'register_secondary_btn', '=', 1 ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'register_secondary_btn_url',
												'type'    => 'text',
												'label'   => __( 'Registration Secondary Button URL', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_secondary_btn_url' ),
												'description' => __( 'You can replace default link for this button by entering custom URL.', 'ultimate-member' ),
												'conditional' => array( 'register_secondary_btn', '=', 1 ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'register_icons',
												'type'    => 'select',
												'label'   => __( 'Registration Field Icons', 'ultimate-member' ),
												'description' => __( 'This controls the display of field icons in the registration form.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'register_icons' ),
												'options' => array(
													'field' => __( 'Show inside text field', 'ultimate-member' ),
													'label' => __( 'Show with label', 'ultimate-member' ),
													'off' => __( 'Turn off', 'ultimate-member' ),
												),
												'size'    => 'small',
											),
										),
									),
								),
							),
							'login_form'        => array(
								'title'         => __( 'Login Form', 'ultimate-member' ),
								'form_sections' => array(
									'template' => array(
										'title'       => __( 'Template', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the user login template and size.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'login_template',
												'type'    => 'select',
												'label'   => __( 'Login Default Template', 'ultimate-member' ),
												'description' => __( 'This will be the default template to output login.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'login_template' ),
												'options' => UM()->shortcodes()->get_templates( 'login' ),
												'size'    => 'small',
											),
											array(
												'id'      => 'login_max_width',
												'type'    => 'text',
												'label'   => __( 'Login Maximum Width', 'ultimate-member' ),
												'default' => um_get_metadefault( 'login_max_width' ),
												'description' => __( 'The maximum width this shortcode can take from the page width.', 'ultimate-member' ),
												'size'    => 'small',
											),
											array(
												'id'      => 'login_align',
												'type'    => 'select',
												'label'   => __( 'Login Shortcode Alignment', 'ultimate-member' ),
												'description' => __( 'The shortcode is centered by default unless you specify otherwise here.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'login_align' ),
												'options' => array(
													'center' => __( 'Centered', 'ultimate-member' ),
													'left' => __( 'Left aligned', 'ultimate-member' ),
													'right' => __( 'Right aligned', 'ultimate-member' ),
												),
												'size'    => 'small',
											),
										),
									),
									'fields'   => array(
										'title'       => __( 'Buttons & Fields', 'ultimate-member' ),
										'description' => __( 'This section allows you to customize the user login buttons and fields layout.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'      => 'login_primary_btn_word',
												'type'    => 'text',
												'label'   => __( 'Login Primary Button Text', 'ultimate-member' ),
												'default' => um_get_metadefault( 'login_primary_btn_word' ),
												'description' => __( 'The text that is used for primary button text.', 'ultimate-member' ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'login_secondary_btn',
												'type'    => 'checkbox',
												'label'   => __( 'Login Secondary Button', 'ultimate-member' ),
												'checkbox_label' => __( 'Show Login Secondary Button', 'ultimate-member' ),
												'default' => 1,
												'description' => __( 'Switch on/off the secondary button display in the form.', 'ultimate-member' ),
											),
											array(
												'id'      => 'login_secondary_btn_word',
												'type'    => 'text',
												'label'   => __( 'Login Secondary Button Text', 'ultimate-member' ),
												'default' => um_get_metadefault( 'login_secondary_btn_word' ),
												'description' => __( 'The text that is used for the secondary button text.', 'ultimate-member' ),
												'conditional' => array( 'login_secondary_btn', '=', 1 ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'login_secondary_btn_url',
												'type'    => 'text',
												'label'   => __( 'Login Secondary Button URL', 'ultimate-member' ),
												'default' => um_get_metadefault( 'login_secondary_btn_url' ),
												'description' => __( 'You can replace default link for this button by entering custom URL.', 'ultimate-member' ),
												'conditional' => array( 'login_secondary_btn', '=', 1 ),
												'size'    => 'medium',
											),
											array(
												'id'      => 'login_forgot_pass_link',
												'type'    => 'checkbox',
												'label'   => __( 'Login Forgot Password Link', 'ultimate-member' ),
												'checkbox_label' => __( 'Show Login Forgot Password Link', 'ultimate-member' ),
												'default' => 1,
												'description' => __( 'Switch on/off the forgot password link in login form.', 'ultimate-member' ),
											),
											array(
												'id'      => 'login_show_rememberme',
												'type'    => 'checkbox',
												'label'   => __( '"Remember Me" checkbox', 'ultimate-member' ),
												'checkbox_label' => __( 'Show "Remember Me" checkbox', 'ultimate-member' ),
												'default' => 1,
												'description' => __( 'Allow users to choose If they want to stay signed in even after closing the browser. If you do not show this option, the default will be to not remember login session.', 'ultimate-member' ),
											),
											array(
												'id'      => 'login_icons',
												'type'    => 'select',
												'label'   => __( 'Login Field Icons', 'ultimate-member' ),
												'description' => __( 'This controls the display of field icons in the login form.', 'ultimate-member' ),
												'default' => um_get_metadefault( 'login_icons' ),
												'options' => array(
													'field' => __( 'Show inside text field', 'ultimate-member' ),
													'label' => __( 'Show with label', 'ultimate-member' ),
													'off' => __( 'Turn off', 'ultimate-member' ),
												),
												'size'    => 'small',
											),
										),
									),
								),
							),
						),
					),
					'extensions'  => array(
						'title' => __( 'Extensions', 'ultimate-member' ),
					),
					'licenses'    => array(
						'title' => __( 'Licenses', 'ultimate-member' ),
					),
					'advanced'    => array(
						'title'    => __( 'Advanced', 'ultimate-member' ),
						'sections' => array(
							''                   => array(
								'title'       => __( 'General', 'ultimate-member' ),
								// translators: %s: Link to UM docs
								'description' => sprintf( __( 'Advanced settings section is designed to help you fine-tune your website or add extra features. <a href="%s" target="_blank">Learn more about advanced settings section</a>.', 'ultimate-member' ), 'https://docs.ultimatemember.com/article/1902-advanced-tab' ),
								'fields'      => array(
									array(
										'id'             => 'form_asterisk',
										'type'           => 'checkbox',
										'label'          => __( 'Required fields\' asterisk', 'ultimate-member' ),
										'checkbox_label' => __( 'Show an asterisk for required fields', 'ultimate-member' ),
									),
									array(
										'id'             => 'um_profile_object_cache_stop',
										'type'           => 'checkbox',
										'label'          => __( 'Cache User Profile', 'ultimate-member' ),
										'checkbox_label' => __( 'Disable user data cache', 'ultimate-member' ),
										'description'    => __( 'Check this box if you would like to disable Ultimate Member user\'s cache.', 'ultimate-member' ),
									),
									array(
										'id'             => 'uninstall_on_delete',
										'type'           => 'checkbox',
										'label'          => __( 'Remove Data on Uninstall?', 'ultimate-member' ),
										'checkbox_label' => __( 'Enable flushing data', 'ultimate-member' ),
										'description'    => __( 'Check this box if you would like Ultimate Member to completely remove all of its data when the plugin/extensions are deleted.', 'ultimate-member' ),
									),
								),
							),
							'override_templates' => array(
								'title'         => __( 'Override templates', 'ultimate-member' ),
								'form_sections' => array(
									'override_templates' => array(
										'title'       => __( 'Override templates', 'ultimate-member' ),
										// translators: %s: Link to the docs article.
										'description' => sprintf( __( 'Each time we release an update, you\'ll find a list of changes made to the template files. <a href="%1$s" target="_blank">Learn more about overriding templates</a>.<br />You can easily check the status of the latest templates to see if they are up-to-date or need updating. <a href="%2$s" target="_blank">Learn more about fixing outdated templates</a>.', 'ultimate-member' ), 'https://docs.ultimatemember.com/article/1516-templates-map', 'https://docs.ultimatemember.com/article/1847-fixing-outdated-ultimate-member-templates' ),
										'fields'      => array(
											array(
												'id'   => 'override_templates_list_table',
												'type' => 'override_templates_list_table',
											),
										),
									),
								),
							),
							'features'           => array(
								'title'         => __( 'Features', 'ultimate-member' ),
								'form_sections' => array(
									'features'        => array(
										'title'       => __( 'Features', 'ultimate-member' ),
										'description' => __( 'Start using new features that are being progressively rolled out to improve the users management experience.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'enable_blocks',
												'type'  => 'checkbox',
												'label' => __( 'Gutenberg Blocks', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable Gutenberg Blocks', 'ultimate-member' ),
												'description' => __( 'Check this box if you would like to use Ultimate Member blocks in Gutenberg editor. Important some themes have the conflicts with Gutenberg editor.', 'ultimate-member' ),
											),
											$same_page_update,
										),
									),
									'beta_features'   => array(
										'title'       => __( 'Experimental features', 'ultimate-member' ),
										'description' => __( 'These features are either experimental or incomplete, enable them at your own risk!', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'enable_new_ui',
												'type'  => 'checkbox',
												'label' => __( 'Design scheme', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable new UI (for developers only)', 'ultimate-member' ),
												'description' => __( 'Check this box if you would like to enable new UI.', 'ultimate-member' ),
											),
											array(
												'id'    => 'enable_new_form_builder',
												'type'  => 'checkbox',
												'label' => __( 'Form Builder', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable new Form Builder (for developers only)', 'ultimate-member' ),
												'description' => __( 'Check this box if you would like to enable new Form Builder.', 'ultimate-member' ),
											),
											array(
												'id'    => 'enable_legacy_fonticons',
												'type'  => 'checkbox',
												'label' => __( 'Legacy fonticons', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable legacy fonticons', 'ultimate-member' ),
												'description' => __( 'Check this box if you would like to enable legacy Ultimate Member fonticons used outdated versions of FontAwesome and Ionicons libraries.', 'ultimate-member' ),
											),
										),
									),
									'legacy_features' => array(
										'title'       => __( 'Legacy features', 'ultimate-member' ),
										'description' => __( 'These features are related to the legacy logic or functionality. Please enable them only for the backward compatibility.', 'ultimate-member' ),
										'fields'      => array(
											// backward compatibility option leave it disabled for better security and ability to exclude posts/terms pre-query
											// otherwise we're filtering only results and restricted posts/terms can be visible
											array(
												'id'    => 'disable_restriction_pre_queries',
												'type'  => 'checkbox',
												'label' => __( 'Restriction content pre-queries', 'ultimate-member' ),
												'checkbox_label' => __( 'Disable pre-queries for restriction content logic', 'ultimate-member' ),
												'description' => __( 'Please enable this option only in the cases when you have big or unnecessary queries on your site with active restriction logic. If you want to exclude posts only from the results queries instead of pre_get_posts and fully-hidden post logic also please enable this option. It activates the restriction content logic until 2.2.x version without latest security enhancements.', 'ultimate-member' ),
											),
										),
									),
								),
							),
							'developers'         => array(
								'title'         => __( 'Developers', 'ultimate-member' ),
								'form_sections' => array(
									'developers' => array(
										'title'       => __( 'Developers', 'ultimate-member' ),
										'description' => __( 'This section is designed to modify settings that are tailored for developers. If you are not a developer, please be cautious when changing these settings.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'allowed_choice_callbacks',
												'type'  => 'textarea',
												'label' => __( 'Allowed Choice Callbacks (Enter one PHP function per line)', 'ultimate-member' ),
												'description' => __( 'This option lets you specify the choice callback functions to prevent anyone from using 3rd-party functions that may put your site at risk.', 'ultimate-member' ),
											),
											array(
												'id'      => 'rest_api_version',
												'type'    => 'select',
												'label'   => __( 'REST API Version', 'ultimate-member' ),
												'description' => __( 'This controls the REST API version, we recommend to use the last version.', 'ultimate-member' ),
												'options' => array(
													'1.0' => __( '1.0 version', 'ultimate-member' ),
													'2.0' => __( '2.0 version', 'ultimate-member' ),
												),
											),
										),
									),
									'redirect'   => array(
										'title'       => __( 'Redirect', 'ultimate-member' ),
										'description' => __( 'Allows to manage redirect settings.', 'ultimate-member' ),
										'fields'      => array(
											array(
												'id'    => 'allow_url_redirect_confirm',
												'type'  => 'checkbox',
												'label' => __( 'Allow external link redirect confirm', 'ultimate-member' ),
												'checkbox_label' => __( 'Enable JS.confirm for external links', 'ultimate-member' ),
												'description' => __( 'Using JS.confirm alert when you go to an external link.', 'ultimate-member' ),
											),
										),
									),
								),
							),
						),
					),
					'system_info' => array(
						'title' => __( 'System info', 'ultimate-member' ),
						'link'  => add_query_arg( array( 'tab' => 'debug' ), admin_url( 'site-health.php' ) ),
					),
				)
			);

			if ( false === UM()->account()->is_notifications_tab_visible() ) {
				unset( $this->settings_structure['']['sections']['account']['form_sections']['notifications_tab'] );
			}

			// Hide sub tab if there aren't custom templates in theme.
			$custom_templates = UM()->common()->theme()->get_custom_templates_list();
			if ( empty( $custom_templates ) ) {
				unset( $this->settings_structure['advanced']['sections']['override_templates'] );
			}

			if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE ) {

			} else {
				unset( $this->settings_structure['advanced']['sections']['features']['form_sections']['beta_features'] );
			}
		}

		/**
		 * @param array $settings
		 *
		 * @return array
		 */
		public function sorting_licenses_options( $settings ) {
			//sorting  licenses
			if ( ! empty( $settings['licenses']['fields'] ) ) {
				$licenses = $settings['licenses']['fields'];
				@uasort(
					$licenses,
					function ( $a, $b ) {
						return strnatcasecmp( $a['label'], $b['label'] );
					}
				);
				$settings['licenses']['fields'] = $licenses;
			}

			//sorting extensions by the title
			if ( ! empty( $settings['extensions']['sections'] ) ) {
				$extensions = $settings['extensions']['sections'];

				@uasort(
					$extensions,
					function ( $a, $b ) {
						return strnatcasecmp( $a['title'], $b['title'] );
					}
				);

				$keys = array_keys( $extensions );
				$temp = array(
					'' => $extensions[ $keys[0] ],
				);

				unset( $extensions[ $keys[0] ] );
				$extensions = $temp + $extensions;

				$settings['extensions']['sections'] = $extensions;
			}

			return $settings;
		}

		/**
		 * @param $tab
		 * @param $section
		 *
		 * @return array
		 */
		public function get_section_fields( $tab, $section ) {
			$custom_section_fields = apply_filters( 'um_settings_section_custom_fields', false, $tab, $section );
			if ( false !== $custom_section_fields ) {
				return $custom_section_fields;
			}

			if ( empty( $this->settings_structure[ $tab ] ) ) {
				return array();
			}

			if ( ! empty( $this->settings_structure[ $tab ]['sections'][ $section ]['form_sections'] ) ) {
				return array( 'form_sections' => $this->settings_structure[ $tab ]['sections'][ $section ]['form_sections'] );
			}

			if ( ! empty( $this->settings_structure[ $tab ]['sections'][ $section ]['fields'] ) ) {
				return $this->settings_structure[ $tab ]['sections'][ $section ]['fields'];
			}

			if ( ! empty( $this->settings_structure[ $tab ]['form_sections'] ) ) {
				return array( 'form_sections' => $this->settings_structure[ $tab ]['form_sections'] );
			}

			if ( ! empty( $this->settings_structure[ $tab ]['fields'] ) ) {
				return $this->settings_structure[ $tab ]['fields'];
			}

			return array();
		}

		/**
		 * Settings page callback.
		 */
		public function settings_page() {
			$current_tab    = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );

			$temp_structure = $this->settings_structure; // Don't remove this temp variable. Internal workaround for Email Tab integration.

			$custom_content = apply_filters( 'um_settings_section_' . $current_tab . '_' . $current_subtab . '_custom_content', false, $current_tab, $current_subtab );

			if ( false === $custom_content ) {
				$section_fields   = $this->get_section_fields( $current_tab, $current_subtab );
				$settings_section = $this->render_settings_section( $section_fields, $current_tab, $current_subtab );
			} else {
				$settings_section = $custom_content;
			}

			$this->settings_structure = $temp_structure; // Don't remove this temp variable. Internal workaround for Email Tab integration.

			echo '<div id="um-settings-wrap" class="wrap"><h1>' . esc_html__( 'Ultimate Member - Settings', 'ultimate-member' ) . '</h1>';

			echo $this->generate_tabs_menu() . $this->generate_subtabs_menu( $current_tab );

			echo '<div class="clear"></div>';
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_settings_page_before_{$current_tab}_{$current_subtab}_content
			 * @description Show some content before settings page content
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_settings_page_before_{$current_tab}_{$current_subtab}_content', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_settings_page_before_{$current_tab}_{$current_subtab}_content', 'my_settings_page_before', 10 );
			 * function my_settings_page_before() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_settings_page_before_{$current_tab}_{$current_subtab}_content" );

			$form_wrapper = true;
			if ( 'licenses' === $current_tab ) {
				$form_wrapper = false;
			}
			if ( 'advanced' === $current_tab && 'override_templates' === $current_subtab ) {
				$form_wrapper = false;
			}
			$form_wrapper = apply_filters( 'um_settings_default_form_wrapper', $form_wrapper, $current_tab, $current_subtab );
			if ( $form_wrapper ) {
				?>
				<form method="post" action="" name="um-settings-form" id="um-settings-form">
					<input type="hidden" value="save" name="um-settings-action" />

					<?php
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_settings_page_{$current_tab}_{$current_subtab}_before_section
			 * @description Show some content before section content at settings page
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_settings_page_{$current_tab}_{$current_subtab}_before_section', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_settings_page_{$current_tab}_{$current_subtab}_before_section', 'my_settings_page_before_section', 10 );
			 * function my_settings_page_before_section() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_settings_page_{$current_tab}_{$current_subtab}_before_section" );

			echo $settings_section;

			if ( $form_wrapper ) {
				$um_settings_nonce = wp_create_nonce( 'um-settings-nonce' );
				?>
					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'ultimate-member' ); ?>" />
						<input type="hidden" name="__umnonce" value="<?php echo esc_attr( $um_settings_nonce ); ?>" />
					</p>
				</form>
				<?php
			}
		}

		/**
		 * Generate pages tabs.
		 *
		 * @param string $page
		 * @return string
		 */
		private function generate_tabs_menu( $page = 'settings' ) {
			$tabs = '<nav class="nav-tab-wrapper um-nav-tab-wrapper">';

			switch ( $page ) {
				case 'settings':
					$menu_tabs = array();
					foreach ( $this->settings_structure as $slug => $tab ) {
						if ( ! empty( $tab['fields'] ) ) {
							foreach ( $tab['fields'] as $field_key => $field_options ) {
								if ( isset( $field_options['is_option'] ) && false === $field_options['is_option'] ) {
									unset( $tab['fields'][ $field_key ] );
								}
							}
						}

						if ( ! empty( $tab['fields'] ) || ! empty( $tab['sections'] ) || ! empty( $tab['form_sections'] ) ) {
							$menu_tabs[ $slug ] = $tab['title'];
						} elseif ( ! empty( $tab['link'] ) ) {
							$menu_tabs[ $slug ] = $tab['title'];
						}
					}

					$current_tab = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
					foreach ( $menu_tabs as $name => $label ) {
						if ( ! empty( $this->settings_structure[ $name ]['link'] ) ) {
							$active  = '';
							$tab_url = $this->settings_structure[ $name ]['link'];
						} else {
							$active = $current_tab === $name ? 'nav-tab-active' : '';
							$args   = array( 'page' => 'um_options' );
							if ( ! empty( $name ) ) {
								$args['tab'] = $name;
							}
							$tab_url = add_query_arg( $args, admin_url( 'admin.php' ) );
						}
						$tabs .= '<a href="' . esc_url( $tab_url ) . '" class="nav-tab ' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
					}

					break;
				default:
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_generate_tabs_menu_{$page}
					 * @description Generate tabs menu
					 * @input_vars
					 * [{"var":"$tabs","type":"array","desc":"UM menu tabs"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_generate_tabs_menu_{$page}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_filter( 'um_generate_tabs_menu_{$page}', 'my_tabs_menu', 10, 1 );
					 * function my_tabs_menu( $tabs ) {
					 *     // your code here
					 *     return $tabs;
					 * }
					 * ?>
					 */
					$tabs = apply_filters( 'um_generate_tabs_menu_' . $page, $tabs );
					break;
			}

			return $tabs . '</nav>';
		}

		/**
		 * @param string $tab
		 *
		 * @return string
		 */
		private function generate_subtabs_menu( $tab = '' ) {
			if ( empty( $this->settings_structure[ $tab ]['sections'] ) ) {
				return '';
			}

			$current_tab    = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );

			$menu_subtabs = array();
			foreach ( $this->settings_structure[ $tab ]['sections'] as $slug => $subtab ) {
				$menu_subtabs[ $slug ] = $subtab['title'];
			}

			$subtabs = array();
			foreach ( $menu_subtabs as $name => $label ) {
				$active = $current_subtab === $name ? 'current' : '';

				$args = array( 'page' => 'um_options' );
				if ( ! empty( $current_tab ) ) {
					$args['tab'] = $current_tab;
				}
				if ( ! empty( $name ) ) {
					$args['section'] = $name;
				}
				$tab_url = add_query_arg( $args, admin_url( 'admin.php' ) );

				$subtabs[] = '<a href="' . esc_url( $tab_url ) . '" class="' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
			}

			if ( empty( $subtabs ) ) {
				return '';
			}

			return '<ul class="subsubsub"><li>' . implode( ' | </li><li>', $subtabs ) . '</li></ul>';
		}

		/**
		 * Handler for settings forms
		 * when "Save Settings" button click
		 *
		 */
		function save_settings_handler() {

			if ( isset( $_POST['um-settings-action'] ) && 'save' === sanitize_key( $_POST['um-settings-action'] ) && ! empty( $_POST['um_options'] ) ) {

				$nonce = ! empty( $_POST['__umnonce'] ) ? $_POST['__umnonce'] : '';

				if ( ( ! wp_verify_nonce( $nonce, 'um-settings-nonce' ) || empty( $nonce ) ) || ! current_user_can( 'manage_options' ) ) {
					// This nonce is not valid.
					wp_die( __( 'Security Check', 'ultimate-member' ) );
				}

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_settings_before_save
				 * @description Before settings save action
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_settings_before_save', 'function_name', 10 );
				 * @example
				 * <?php
				 * add_action( 'um_settings_before_save', 'my_settings_before_save', 10 );
				 * function my_settings_before_save() {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_settings_before_save' );

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_change_settings_before_save
				 * @description Change settings before save
				 * @input_vars
				 * [{"var":"$settings","type":"array","desc":"UM Settings on save"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_change_settings_before_save', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_change_settings_before_save', 'my_change_settings_before_save', 10, 1 );
				 * function my_change_settings_before_save( $settings ) {
				 *     // your code here
				 *     return $settings;
				 * }
				 * ?>
				 */
				$settings = apply_filters( 'um_change_settings_before_save', $_POST['um_options'] );

				$settings = UM()->admin()->sanitize_options( $settings );

				foreach ( $settings as $key => $value ) {
					UM()->options()->update( $key, $value );
				}

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_settings_save
				 * @description After settings save action
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_settings_save', 'function_name', 10 );
				 * @example
				 * <?php
				 * add_action( 'um_settings_save', 'my_settings_save', 10 );
				 * function my_settings_save() {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_settings_save' );

				//redirect after save settings
				$arg = array(
					'page'   => 'um_options',
					'update' => 'um_settings_updated',
				);

				if ( ! empty( $_GET['tab'] ) ) {
					$arg['tab'] = sanitize_key( $_GET['tab'] );
				}

				if ( ! empty( $_GET['section'] ) ) {
					$arg['section'] = sanitize_key( $_GET['section'] );
				}

				um_js_redirect( add_query_arg( $arg, admin_url( 'admin.php' ) ) );
			}
		}

		public function set_default_if_empty( $settings ) {
			$tab = '';
			if ( ! empty( $_GET['tab'] ) ) {
				$tab = sanitize_key( $_GET['tab'] );
			}

			$section = '';
			if ( ! empty( $_GET['section'] ) ) {
				$section = sanitize_key( $_GET['section'] );
			}

			if ( 'access' === $tab && empty( $section ) ) {
				if ( ! array_key_exists( 'access_exclude_uris', $settings ) ) {
					$settings['access_exclude_uris'] = array();
				}
			}

			return $settings;
		}

		/**
		 * Remove empty values from multi text fields.
		 *
		 * @param array $settings
		 * @return array
		 */
		public function remove_empty_values( $settings ) {
			$tab = '';
			if ( ! empty( $_GET['tab'] ) ) {
				$tab = sanitize_key( $_GET['tab'] );
			}

			$section = '';
			if ( ! empty( $_GET['section'] ) ) {
				$section = sanitize_key( $_GET['section'] );
			}

			if ( ! empty( $this->settings_structure[ $tab ]['sections'][ $section ]['form_sections'] ) ) {
				$fields = array();
				foreach ( $this->settings_structure[ $tab ]['sections'][ $section ]['form_sections'] as $section_key => $section_data ) {
					if ( ! empty( $section_data['fields'] ) ) {
						$fields[] = $section_data['fields'];
					}
				}
				$fields = array_merge( ...$fields );
			} elseif ( ! empty( $this->settings_structure[ $tab ]['sections'][ $section ]['fields'] ) ) {
				$fields = $this->settings_structure[ $tab ]['sections'][ $section ]['fields'];
			} elseif ( ! empty( $this->settings_structure[ $tab ]['form_sections'] ) ) {
				$fields = array();
				foreach ( $this->settings_structure[ $tab ]['form_sections'] as $section_key => $section_data ) {
					if ( ! empty( $section_data['fields'] ) ) {
						$fields[] = $section_data['fields'];
					}
				}
				$fields = array_merge( ...$fields );
			} elseif ( ! empty( $this->settings_structure[ $tab ]['fields'] ) ) {
				$fields = $this->settings_structure[ $tab ]['fields'];
			}

			if ( empty( $fields ) ) {
				return $settings;
			}

			$filtered_settings = array();
			foreach ( $settings as $key => $value ) {
				$filtered_settings[ $key ] = $value;

				foreach ( $fields as $field ) {
					if ( $field['id'] === $key && array_key_exists( 'type', $field ) && 'multi_text' === $field['type'] ) {
						$filtered_settings[ $key ] = array_filter( $value );
					}
				}
			}

			return $filtered_settings;
		}

		/**
		 *
		 */
		function check_permalinks_changes() {
			if ( ! empty( $_POST['um_options']['permalink_base'] ) ) {
				if ( UM()->options()->get( 'permalink_base' ) !== $_POST['um_options']['permalink_base'] ) {
					$this->need_change_permalinks = true;
				}
			}

			// set variable if gravatar settings were changed
			// update for um_member_directory_data metakey
			if ( isset( $_POST['um_options']['use_gravatars'] ) ) {
				$use_gravatar = UM()->options()->get( 'use_gravatars' );
				if ( ( empty( $use_gravatar ) && ! empty( $_POST['um_options']['use_gravatars'] ) ) || ( ! empty( $use_gravatar ) && empty( $_POST['um_options']['use_gravatars'] ) ) ) {
					$this->gravatar_changed = true;
				}
			}
		}


		/**
		 *
		 */
		function on_settings_save() {
			if ( ! empty( $_POST['um_options'] ) ) {

				if ( ! empty( $_POST['um_options']['pages_settings'] ) ) {
					$post_ids = new \WP_Query(
						array(
							'post_type'      => 'page',
							'meta_query'     => array(
								array(
									'key'     => '_um_core',
									'compare' => 'EXISTS',
								),
							),
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
					);

					$post_ids = $post_ids->get_posts();

					if ( ! empty( $post_ids ) ) {
						foreach ( $post_ids as $post_id ) {
							delete_post_meta( $post_id, '_um_core' );
						}
					}

					foreach ( $_POST['um_options'] as $option_slug => $post_id ) {
						$slug = str_replace( 'core_', '', $option_slug );
						update_post_meta( $post_id, '_um_core', $slug );
					}

					// reset rewrite rules after re-save pages
					UM()->rewrite()->reset_rules();

				} elseif ( ! empty( $_POST['um_options']['permalink_base'] ) ) {
					if ( ! empty( $this->need_change_permalinks ) ) {
						$users = get_users(
							array(
								'fields' => 'ids',
							)
						);
						if ( ! empty( $users ) ) {
							foreach ( $users as $user_id ) {
								UM()->user()->generate_profile_slug( $user_id );
							}
						}
					}

					// update for um_member_directory_data metakey
					if ( isset( $_POST['um_options']['use_gravatars'] ) ) {
						if ( $this->gravatar_changed ) {
							global $wpdb;

							if ( ! empty( $_POST['um_options']['use_gravatars'] ) ) {

								$results = $wpdb->get_col(
									"SELECT u.ID FROM {$wpdb->users} AS u
									LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND um.meta_key = 'synced_gravatar_hashed_id' )
									LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
									WHERE um.meta_value != '' AND um.meta_value IS NOT NULL AND
										um2.meta_value LIKE '%s:13:\"profile_photo\";b:0;%'"
								);

							} else {

								$results = $wpdb->get_col(
									"SELECT u.ID FROM {$wpdb->users} AS u
									LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND ( um.meta_key = 'synced_profile_photo' || um.meta_key = 'profile_photo' ) )
									LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
									WHERE ( um.meta_value IS NULL OR um.meta_value = '' ) AND
										um2.meta_value LIKE '%s:13:\"profile_photo\";b:1;%'"
								);

							}

							if ( ! empty( $results ) ) {
								foreach ( $results as $user_id ) {
									$md_data = get_user_meta( $user_id, 'um_member_directory_data', true );
									if ( ! empty( $md_data ) && is_array( $md_data ) ) {
										$md_data['profile_photo'] = ! empty( $_POST['um_options']['use_gravatars'] );
										update_user_meta( $user_id, 'um_member_directory_data', $md_data );
									}
								}
							}
						}
					}
				} elseif ( isset( $_POST['um_options']['member_directory_own_table'] ) ) {
					if ( empty( $_POST['um_options']['member_directory_own_table'] ) ) {
						global $wpdb;

						$results = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}um_metadata LIMIT 1", ARRAY_A );

						if ( ! empty( $results ) ) {
							$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}um_metadata" );
						}

						update_option( 'um_member_directory_truncated', time() );
					}
				} elseif ( isset( $_POST['um_options']['account_hide_in_directory_default'] ) ) {

					global $wpdb;

					if ( $_POST['um_options']['account_hide_in_directory_default'] === 'No' ) {

						$results = $wpdb->get_col(
							"SELECT u.ID FROM {$wpdb->users} AS u
							LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND um.meta_key = 'hide_in_members' )
							LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
							WHERE um.meta_value IS NULL AND
								um2.meta_value LIKE '%s:15:\"hide_in_members\";b:1;%'"
						);

					} else {

						$results = $wpdb->get_col(
							"SELECT u.ID FROM {$wpdb->users} AS u
							LEFT JOIN {$wpdb->usermeta} AS um ON ( um.user_id = u.ID AND um.meta_key = 'hide_in_members' )
							LEFT JOIN {$wpdb->usermeta} AS um2 ON ( um2.user_id = u.ID AND um2.meta_key = 'um_member_directory_data' )
							WHERE um.meta_value IS NULL AND
								um2.meta_value LIKE '%s:15:\"hide_in_members\";b:0;%'"
						);

					}

					if ( ! empty( $results ) ) {
						foreach ( $results as $user_id ) {
							$md_data = get_user_meta( $user_id, 'um_member_directory_data', true );
							if ( ! empty( $md_data ) && is_array( $md_data ) ) {
								$md_data['hide_in_members'] = ( $_POST['um_options']['account_hide_in_directory_default'] === 'No' ) ? false : true;
								update_user_meta( $user_id, 'um_member_directory_data', $md_data );
							}
						}
					}
				}
			}
		}

		/**
		 *
		 */
		function before_licenses_save() {
			if ( empty( $_POST['um_options'] ) || empty( $_POST['licenses_settings'] ) ) {
				return;
			}

			foreach ( $_POST['um_options'] as $key => $value ) {
				$this->previous_licenses[ sanitize_key( $key ) ] = UM()->options()->get( $key );
			}
		}

		/**
		 *
		 */
		function licenses_save() {
			if ( empty( $_POST['um_options'] ) || empty( $_POST['licenses_settings'] ) ) {
				return;
			}

			foreach ( $_POST['um_options'] as $key => $value ) {
				$key   = sanitize_key( $key );
				$value = sanitize_text_field( $value );

				$edd_action  = '';
				$license_key = '';
				if ( empty( $this->previous_licenses[ $key ] ) && ! empty( $value ) || ( ! empty( $this->previous_licenses[ $key ] ) && ! empty( $value ) && $this->previous_licenses[ $key ] != $value ) ) {
					$edd_action  = 'activate_license';
					$license_key = $value;
				} elseif ( ! empty( $this->previous_licenses[ $key ] ) && empty( $value ) ) {
					$edd_action  = 'deactivate_license';
					$license_key = $this->previous_licenses[ $key ];
				} elseif ( ! empty( $this->previous_licenses[ $key ] ) && ! empty( $value ) ) {
					$edd_action  = 'check_license';
					$license_key = $value;
				}

				if ( empty( $edd_action ) ) {
					continue;
				}

				$item_name = false;
				$version   = false;
				$author    = false;
				foreach ( $this->settings_structure['licenses']['fields'] as $field_data ) {
					if ( $field_data['id'] == $key ) {
						$item_name = ! empty( $field_data['item_name'] ) ? $field_data['item_name'] : false;
						$version   = ! empty( $field_data['version'] ) ? $field_data['version'] : false;
						$author    = ! empty( $field_data['author'] ) ? $field_data['author'] : false;
					}
				}

				$api_params = array(
					'edd_action' => $edd_action,
					'license'    => $license_key,
					'item_name'  => $item_name,
					'version'    => $version,
					'author'     => $author,
					'url'        => home_url(),
				);

				$request = wp_remote_post(
					UM()->store_url,
					array(
						'timeout'   => UM()->request_timeout,
						'sslverify' => false,
						'body'      => $api_params,
					)
				);

				if ( ! is_wp_error( $request ) ) {
					$request = json_decode( wp_remote_retrieve_body( $request ) );
				} else {
					$request = wp_remote_post(
						UM()->store_url,
						array(
							'timeout'   => UM()->request_timeout,
							'sslverify' => true,
							'body'      => $api_params,
						)
					);

					if ( ! is_wp_error( $request ) ) {
						$request = json_decode( wp_remote_retrieve_body( $request ) );
					}
				}

				$request = ( $request ) ? maybe_unserialize( $request ) : false;

				if ( in_array( $edd_action, array( 'activate_license', 'check_license' ), true ) ) {
					update_option( "{$key}_edd_answer", $request );
				} else {
					delete_option( "{$key}_edd_answer" );
				}
			}
		}

		/**
		 * Adds email notifications list table before the email options list.
		 */
		public function settings_before_email_tab() {
			$email_key = empty( $_GET['email'] ) ? '' : sanitize_key( $_GET['email'] );
			$emails    = UM()->config()->email_notifications;

			if ( empty( $email_key ) || empty( $emails[ $email_key ] ) ) {
				include_once UM_PATH . 'includes/admin/core/list-tables/emails-list-table.php';
			}
		}

		/**
		 * Set settings field per email notification.
		 *
		 * @param bool|array $section_fields
		 * @param string     $tab
		 *
		 * @return bool|array
		 */
		public function email_section_custom_fields( $section_fields, $tab ) {
			if ( 'email' !== $tab ) {
				return $section_fields;
			}

			$email_key = empty( $_GET['email'] ) ? '' : sanitize_key( $_GET['email'] );
			$emails    = UM()->config()->email_notifications;

			if ( empty( $email_key ) || empty( $emails[ $email_key ] ) ) {
				return $section_fields;
			}

			$in_theme = UM()->mail()->template_in_theme( $email_key );

			$back_link = add_query_arg(
				array(
					'page' => 'um_options',
					'tab'  => 'email',
				),
				admin_url( 'admin.php' )
			);

			$this->settings_structure['email']['title']       = '<a class="um-back-button" href="' . esc_url( $back_link ) . '" title="' . esc_attr__( 'Back', 'ultimate-member' ) . '">&#8592;</a>' . $emails[ $email_key ]['title'];
			$this->settings_structure['email']['description'] = $emails[ $email_key ]['description'];

			$section_fields = array(
				array(
					'id'    => 'um_email_template',
					'type'  => 'hidden',
					'value' => $email_key,
				),
				array(
					'id'             => $email_key . '_on',
					'type'           => 'checkbox',
					'label'          => __( 'Enable/Disable', 'ultimate-member' ),
					'checkbox_label' => __( 'Enable this email notification', 'ultimate-member' ),
				),
				array(
					'id'          => $email_key . '_sub',
					'type'        => 'text',
					'label'       => __( 'Subject', 'ultimate-member' ),
					'conditional' => array( $email_key . '_on', '=', 1 ),
				),
				array(
					'id'          => $email_key,
					'type'        => 'email_template',
					'label'       => __( 'Email Content', 'ultimate-member' ),
					'conditional' => array( $email_key . '_on', '=', 1 ),
					'value'       => UM()->mail()->get_email_template( $email_key ),
					'in_theme'    => $in_theme,
				),
			);
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_settings_email_section_fields
			 * @description Extend UM Email Settings
			 * @input_vars
			 * [{"var":"$settings","type":"array","desc":"UM Email Settings"},
			 * {"var":"$email_key","type":"string","desc":"Email Key"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_settings_email_section_fields', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_settings_email_section_fields', 'my_admin_settings_email_section', 10, 2 );
			 * function my_admin_settings_email_section( $settings, $email_key ) {
			 *     // your code here
			 *     return $settings;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_admin_settings_email_section_fields', $section_fields, $email_key );
		}

		/**
		 * @param bool   $html
		 * @param string $current_tab
		 * @param string $current_subtab
		 *
		 * @return bool|string
		 */
		public function settings_licenses_tab( $html, $current_tab, $current_subtab ) {
			$section_fields = $this->get_section_fields( $current_tab, $current_subtab );
			if ( empty( $section_fields ) ) {
				return $html;
			}

			$um_settings_nonce = wp_create_nonce( 'um-settings-nonce' );
			ob_start();
			?>
			<div class="wrap-licenses">
				<table class="form-table um-settings-section">
					<tbody>
					<?php
					foreach ( $section_fields as $field_data ) {
						$option_value  = UM()->options()->get( $field_data['id'] );
						$default_value = isset( $field_data['default'] ) ? $field_data['default'] : '';
						$value         = ! empty( $option_value ) ? $option_value : $default_value;

						$license = get_option( "{$field_data['id']}_edd_answer" );

						if ( is_object( $license ) && ! empty( $value ) ) {
							// Activate_license 'invalid' on anything other than valid, so if there was an error capture it
							if ( is_wp_error( $license ) ) {
								$class       = 'error';
								$errors_data = array();
								$error_codes = $license->get_error_codes();
								if ( ! empty( $error_codes ) ) {
									foreach ( $error_codes as $error_code ) {
										$error_code_messages = $license->get_error_messages( $error_code );
										$error_code_messages = implode( ', ', $error_code_messages );
										// translators: %1$s is an error code; %2$s is an error message.
										$errors_data[] = sprintf( __( 'code: %1$s, message: %2$s;', 'ultimate-member' ), $error_code, $error_code_messages );
									}
								}
								$errors_data = ! empty( $errors_data ) ? implode( ' ', $errors_data ) : '';

								// translators: %1$s is an error data; %2$s is a support link.
								$messages[] = sprintf( __( 'There was an error with this license key: %1$s. Please <a href="%2$s">contact our support team</a>.', 'ultimate-member' ), $errors_data, 'https://ultimatemember.com/support' );

								$license_status = 'license-' . $class . '-notice';
							} elseif ( empty( $license->success ) ) {

								if ( ! empty( $license->error ) ) {
									switch ( $license->error ) {
										case 'expired':
											$class      = 'expired';
											$messages[] = sprintf(
												// translators: %1$s is an expiry date; %2$s is a renewal link.
												__( 'Your license key expired on %1$s. Please <a href="%2$s" target="_blank">renew your license key</a>.', 'ultimate-member' ),
												wp_date( get_option( 'date_format', 'F j, Y' ), strtotime( $license->expires ), new DateTimeZone( 'UTC' ) ),
												'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired'
											);

											$license_status = 'license-' . $class . '-notice';
											break;
										case 'revoked':
											$class      = 'error';
											$messages[] = sprintf(
												// translators: %s: support link name.
												__( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'ultimate-member' ),
												'https://ultimatemember.com/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
											);

											$license_status = 'license-' . $class . '-notice';
											break;
										case 'missing':
											$class      = 'error';
											$messages[] = sprintf(
												// translators: %s: account page.
												__( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'ultimate-member' ),
												'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
											);

											$license_status = 'license-' . $class . '-notice';
											break;
										case 'invalid':
										case 'site_inactive':
											$class      = 'error';
											$messages[] = sprintf(
												// translators: %1$s is a item name title; %2$s is a account page.
												__( 'Your %1$s is not active for this URL. Please <a href="%2$s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ultimate-member' ),
												$field_data['item_name'],
												'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
											);

											$license_status = 'license-' . $class . '-notice';
											break;
										case 'item_name_mismatch':
											$class = 'error';
											// translators: %s: item name.
											$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'ultimate-member' ), $field_data['item_name'] );

											$license_status = 'license-' . $class . '-notice';
											break;
										case 'no_activations_left':
											$class = 'error';
											// translators: %s: account link.
											$messages[] = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'ultimate-member' ), 'https://ultimatemember.com/account' );

											$license_status = 'license-' . $class . '-notice';
											break;
										case 'license_not_activable':
											$class      = 'error';
											$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'ultimate-member' );

											$license_status = 'license-' . $class . '-notice';
											break;
										default:
											$class = 'error';
											$error = ! empty( $license->error ) ? $license->error : __( 'unknown_error', 'ultimate-member' );
											// translators: %1$s is an error; %2$s is a support link.
											$messages[] = sprintf( __( 'There was an error with this license key: %1$s. Please <a href="%2$s">contact our support team</a>.', 'ultimate-member' ), $error, 'https://ultimatemember.com/support' );

											$license_status = 'license-' . $class . '-notice';
											break;
									}
								} else {
									$class = 'error';
									$error = ! empty( $license->error ) ? $license->error : __( 'unknown_error', 'ultimate-member' );
									// translators: %1$s is an error; %2$s is a support link.
									$messages[] = sprintf( __( 'There was an error with this license key: %1$s. Please <a href="%2$s">contact our support team</a>.', 'ultimate-member' ), $error, 'https://ultimatemember.com/support' );

									$license_status = 'license-' . $class . '-notice';
								}
							} elseif ( ! empty( $license->errors ) ) {
								$errors      = array_keys( $license->errors );
								$errors_data = array_values( $license->errors );

								$class       = 'error';
								$error       = ! empty( $errors[0] ) ? $errors[0] : __( 'unknown_error', 'ultimate-member' );
								$errors_data = ! empty( $errors_data[0][0] ) ? ', ' . $errors_data[0][0] : '';
								// translators: %1$s is an error; %2$s is a error data; %3$s is a support link.
								$messages[] = sprintf( __( 'There was an error with this license key: %1$s%2$s. Please <a href="%3$s">contact our support team</a>.', 'ultimate-member' ), $error, $errors_data, 'https://ultimatemember.com/support' );

								$license_status = 'license-' . $class . '-notice';

							} else {

								switch ( $license->license ) {
									case 'expired':
										$class      = 'expired';
										$messages[] = sprintf(
											// translators: %1$s is a expiry date; %2$s is a renew link.
											__( 'Your license key expired on %1$s. Please <a href="%2$s" target="_blank">renew your license key</a>.', 'ultimate-member' ),
											wp_date( get_option( 'date_format', 'F j, Y' ), strtotime( $license->expires ), new DateTimeZone( 'UTC' ) ),
											'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired'
										);

										$license_status = 'license-' . $class . '-notice';
										break;
									case 'revoked':
										$class      = 'error';
										$messages[] = sprintf(
											// translators: %s: support link name.
											__( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'ultimate-member' ),
											'https://ultimatemember.com/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
										);

										$license_status = 'license-' . $class . '-notice';
										break;
									case 'missing':
										$class      = 'error';
										$messages[] = sprintf(
											// translators: %s: account page.
											__( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'ultimate-member' ),
											'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
										);

										$license_status = 'license-' . $class . '-notice';
										break;
									case 'invalid':
									case 'site_inactive':
										$class      = 'error';
										$messages[] = sprintf(
											// translators: %1$s is a item name title; %2$s is a account page.
											__( 'Your %1$s is not active for this URL. Please <a href="%2$s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ultimate-member' ),
											$field_data['item_name'],
											'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
										);

										$license_status = 'license-' . $class . '-notice';
										break;
									case 'item_name_mismatch':
										$class = 'error';
										// translators: %s: item name.
										$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'ultimate-member' ), $field_data['item_name'] );

										$license_status = 'license-' . $class . '-notice';
										break;
									case 'no_activations_left':
										$class = 'error';
										// translators: %s: account link.
										$messages[] = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'ultimate-member' ), 'https://ultimatemember.com/account' );

										$license_status = 'license-' . $class . '-notice';
										break;
									case 'license_not_activable':
										$class      = 'error';
										$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'ultimate-member' );

										$license_status = 'license-' . $class . '-notice';
										break;
									case 'valid':
									default:
										$class = 'valid';

										$now        = time();
										$expiration = strtotime( $license->expires );

										if ( 'lifetime' === $license->expires ) {

											$messages[] = __( 'License key never expires.', 'ultimate-member' );

											$license_status = 'license-lifetime-notice';

										} elseif ( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {

											$messages[] = sprintf(
												// translators: %1$s is an expiry date; %2$s is a renewal link.
												__( 'Your license key expires soon! It expires on %1$s. <a href="%2$s" target="_blank">Renew your license key</a>.', 'ultimate-member' ),
												wp_date( get_option( 'date_format', 'F j, Y' ), strtotime( $license->expires ), new DateTimeZone( 'UTC' ) ),
												'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=renew'
											);

											$license_status = 'license-expires-soon-notice';

										} else {

											$messages[] = sprintf(
												// translators: %s: expiry date.
												__( 'Your license key expires on %s.', 'ultimate-member' ),
												wp_date( get_option( 'date_format', 'F j, Y' ), strtotime( $license->expires ), new DateTimeZone( 'UTC' ) )
											);

											$license_status = 'license-expiration-date-notice';

										}

										break;
								}
							}
						} else {
							$class = 'empty';

							$messages[] = sprintf(
								// translators: %s: item name.
								__( 'To receive updates, please enter your valid %s license key.', 'ultimate-member' ),
								$field_data['item_name']
							);

							$license_status = null;

						}
						?>

						<tr class="um-settings-line">
							<th><label for="um_options_<?php echo esc_attr( $field_data['id'] ); ?>"><?php echo esc_html( $field_data['label'] ); ?></label></th>
							<td>
								<form method="post" action="" name="um-settings-form" class="um-settings-form">
									<input type="hidden" value="save" name="um-settings-action" />
									<input type="hidden" name="licenses_settings" value="1" />
									<input type="hidden" name="__umnonce" value="<?php echo esc_attr( $um_settings_nonce ); ?>" />
									<input type="text" id="um_options_<?php echo esc_attr( $field_data['id'] ); ?>" name="um_options[<?php echo esc_attr( $field_data['id'] ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="um-option-field um-long-field" data-field_id="<?php echo esc_attr( $field_data['id'] ); ?>" />
									<?php
									if ( ! empty( $field_data['description'] ) ) {
										?>
										<div class="description"><?php echo esc_html( $field_data['description'] ); ?></div>
										<?php
									}

									if ( ! empty( $value ) && ( ( is_object( $license ) && isset( $license->license ) && 'valid' === $license->license ) || 'valid' === $license ) ) {
										?>
										<input type="button" class="button um_license_deactivate" id="<?php echo esc_attr( $field_data['id'] ); ?>_deactivate" value="<?php esc_attr_e( 'Clear License', 'ultimate-member' ); ?>"/>
										<?php
									} elseif ( empty( $value ) ) {
										?>
										<input type="submit" name="submit" id="submit" class="button button-primary um_license_activate" value="<?php esc_attr_e( 'Activate', 'ultimate-member' ); ?>" />
										<?php
									} else {
										?>
										<input type="submit" name="submit" id="submit" class="button button-primary um_license_reactivate" value="<?php esc_attr_e( 'Re-Activate', 'ultimate-member' ); ?>" />
										<input type="button" class="button um_license_deactivate" id="<?php echo esc_attr( $field_data['id'] ); ?>_deactivate" value="<?php esc_attr_e( 'Clear License', 'ultimate-member' ); ?>"/>
										<?php
									}

									if ( ! empty( $messages ) ) {
										foreach ( $messages as $message ) {
											?>
											<div class="edd-license-data edd-license-<?php echo esc_attr( $class . ' ' . $license_status ); ?>">
												<p><?php echo wp_kses( $message, UM()->get_allowed_html( 'admin_notice' ) ); ?></p>
											</div>
											<?php
										}
									}
									?>
								</form>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
			<?php
			return ob_get_clean();
		}

		/**
		 * HTML for Settings > Advanced > Override Templates tab.
		 *
		 * @return string
		 */
		public function settings_override_templates_tab() {
			$um_check_version = time();
			$custom_templates = get_transient( 'um_custom_templates_list' );
			if ( false !== $custom_templates && array_key_exists( 'time', $custom_templates ) ) {
				$um_check_version = $custom_templates['time'];
			}

			$check_url = add_query_arg(
				array(
					'um_adm_action' => 'check_templates_version',
					'_wpnonce'      => wp_create_nonce( 'check_templates_version' ),
				)
			);

			ob_start();
			?>
			<p>
				<a href="<?php echo esc_url( $check_url ); ?>" class="button" style="margin-right: 10px;">
					<?php esc_html_e( 'Re-check templates', 'ultimate-member' ); ?>
				</a>
				<?php
					// translators: %s: Last checking templates time.
					echo esc_html( sprintf( __( 'Last update: %s. You could re-check changes manually.', 'ultimate-member' ), wp_date( get_option( 'date_format', 'F j, Y' ) . ' ' . get_option( 'time_format', 'g:i a' ), $um_check_version ) ) );
				?>
			</p>
			<div class="clear"></div>
			<?php
			include_once UM_PATH . 'includes/admin/core/list-tables/version-template-list-table.php';
			return ob_get_clean();
		}

		/**
		 * Scan the template files.
		 *
		 * @param  string $template_path Path to the template directory.
		 * @return array
		 */
		public static function scan_template_files( $template_path ) {
			return UM()->common()->theme()::scan_template_files( $template_path );
		}

		/**
		 * Render settings section.
		 *
		 * @param array $section_fields
		 * @param string $current_tab
		 * @param string $current_subtab
		 *
		 * @return string
		 */
		public function render_settings_section( $section_fields, $current_tab, $current_subtab ) {
			$settings_section = '';

			if ( ! empty( $section_fields['form_sections'] ) ) {
				foreach ( $section_fields['form_sections'] as $section_key => $form_section_fields ) {
					if ( empty( $form_section_fields['fields'] ) ) {
						continue;
					}

					$custom_form_section_content = apply_filters( "um_settings_form_section_{$current_tab}_{$current_subtab}_{$section_key}_custom_content", false );

					ob_start();
					if ( ! empty( $form_section_fields['title'] ) ) {
						?>
						<h2 class="title"><?php echo wp_kses( $form_section_fields['title'], UM()->get_allowed_html( 'admin_notice' ) ); ?></h2>
						<?php
					}

					if ( ! empty( $form_section_fields['description'] ) ) {
						?>
						<p><?php echo wp_kses( $form_section_fields['description'], UM()->get_allowed_html( 'admin_notice' ) ); ?></p>
						<?php
					}

					if ( false === $custom_form_section_content ) {
						UM()->admin_forms_settings(
							array(
								'class'     => 'um_options-' . $current_tab . '-' . $current_subtab . '-' . $section_key . ' um-third-column',
								'prefix_id' => 'um_options',
								'fields'    => $form_section_fields['fields'],
							)
						)->render_form();
					} else {
						echo $custom_form_section_content;
					}

					$settings_section .= ob_get_clean();
				}
			} else {
				$settings_structure = $this->settings_structure[ $current_tab ];
				if ( ! empty( $settings_structure['sections'] ) ) {
					if ( ! empty( $settings_structure['sections'][ $current_subtab ] ) ) {
						$settings_subtab_structure = $settings_structure['sections'][ $current_subtab ];

						$section_title       = array_key_exists( 'title', $settings_subtab_structure ) ? $settings_subtab_structure['title'] : '';
						$section_description = array_key_exists( 'description', $settings_subtab_structure ) ? $settings_subtab_structure['description'] : '';
					}
				} else {
					$section_title       = array_key_exists( 'title', $settings_structure ) ? $settings_structure['title'] : '';
					$section_description = array_key_exists( 'description', $settings_structure ) ? $settings_structure['description'] : '';
				}

				ob_start();

				if ( ! empty( $section_title ) ) {
					?>
					<h2 class="title"><?php echo wp_kses( $section_title, UM()->get_allowed_html( 'admin_notice' ) ); ?></h2>
					<?php
				}

				if ( ! empty( $section_description ) ) {
					?>
					<p><?php echo wp_kses( $section_description, UM()->get_allowed_html( 'admin_notice' ) ); ?></p>
					<?php
				}

				UM()->admin_forms_settings(
					array(
						'class'     => 'um_options-' . $current_tab . '-' . $current_subtab . ' um-third-column',
						'prefix_id' => 'um_options',
						'fields'    => $section_fields,
					)
				)->render_form();

				$settings_section .= ob_get_clean();
			}

			return $settings_section;
		}

		/**
		 * @param array $settings
		 *
		 * @return array
		 */
		public function save_email_templates( $settings ) {
			if ( empty( $settings['um_email_template'] ) ) {
				return $settings;
			}

			$wp_default_protocols = wp_allowed_protocols();
			$protocols            = array_merge( $wp_default_protocols, array( 'data' ) );

			$template = $settings['um_email_template'];
			$content  = wp_kses( stripslashes( $settings[ $template ] ), 'post', $protocols );

			$theme_template_path = UM()->mail()->get_template_file( 'theme', $template );
			if ( ! file_exists( $theme_template_path ) ) {
				UM()->mail()->copy_email_template( $template );
			}

			if ( file_exists( $theme_template_path ) ) {
				$fp     = fopen( $theme_template_path, 'w' );
				$result = fputs( $fp, $content );
				fclose( $fp );
			}

			if ( isset( $result ) && $result !== false ) {
				unset( $settings['um_email_template'] );
				unset( $settings[ $template ] );
			}

			return $settings;
		}
	}
}
