<?php
namespace um\admin\core;

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

			//admin menu
			add_action( 'admin_menu', array( &$this, 'primary_admin_menu' ), 0 );

			//settings structure handlers
			add_action( 'um_settings_page_before_email__content', array( $this, 'settings_before_email_tab' ) );
			add_filter( 'um_settings_section_email__content', array( $this, 'settings_email_tab' ), 10, 1 );

			//enqueue wp_media for profiles tab
			add_action( 'um_settings_page_appearance__before_section', array( $this, 'settings_appearance_profile_tab' ) );

			//custom content for licenses tab
			add_filter( 'um_settings_section_licenses__content', array( $this, 'settings_licenses_tab' ), 10, 2 );

			add_filter( 'um_settings_section_install_info__content', array( $this, 'settings_install_info_tab' ), 10, 2 );


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

			add_action( 'admin_init', array( &$this, 'um_download_install_info' ) );
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

				if ( ! empty( $values ) ) {
					$wpdb->query(
						"INSERT INTO
						{$wpdb->prefix}um_metadata(user_id, um_key, um_value)
						VALUES " . implode( ',', $values )
					);
				}

				$from = ( absint( $_POST['page'] ) * $per_page ) - $per_page + 1;
				$to   = absint( $_POST['page'] ) * $per_page;

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

			$core_pages = UM()->config()->core_pages;

			foreach ( $core_pages as $page_s => $page ) {
				$have_pages = UM()->query()->wp_pages();
				$page_id    = UM()->options()->get_core_page_id( $page_s );

				$page_title = ! empty( $page['title'] ) ? $page['title'] : '';

				if ( 'reached_maximum_limit' === $have_pages ) {
					$general_pages_fields[] = array(
						'id'          => $page_id,
						'type'        => 'text',
						// translators: %s: Page title
						'label'       => sprintf( __( '%s page', 'ultimate-member' ), $page_title ),
						'placeholder' => __( 'Add page ID', 'ultimate-member' ),
						'compiler'    => true,
						'size'        => 'small',
					);
				} else {
					$general_pages_fields[] = array(
						'id'          => $page_id,
						'type'        => 'select',
						// translators: %s: Page title
						'label'       => sprintf( __( '%s page', 'ultimate-member' ), $page_title ),
						'options'     => UM()->query()->wp_pages(),
						'placeholder' => __( 'Choose a page...', 'ultimate-member' ),
						'compiler'    => true,
						'size'        => 'small',
					);
				}


				$settings_map[ $page_id ] = array(
					'sanitize' => 'absint',
				);
			}

			$appearances_profile_menu_fields = array(
				array(
					'id'    => 'profile_menu',
					'type'  => 'checkbox',
					'label' => __( 'Enable profile menu', 'ultimate-member' ),
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
							'id'          => 'profile_tab_' . $id,
							'type'        => 'checkbox',
							// translators: %s: Tab title
							'label'       => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
							'conditional' => array( 'profile_menu', '=', 1 ),
							'data'        => array( 'fill_profile_menu_default_tab' => $id ),
						),
					);

					$settings_map[ 'profile_tab_' . $id ] = array(
						'sanitize' => 'bool',
					);
				} else {

					$fields = array(
						array(
							'id'          => 'profile_tab_' . $id,
							'type'        => 'checkbox',
							// translators: %s: Tab title
							'label'       => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
							'conditional' => array( 'profile_menu', '=', 1 ),
							'data'        => array( 'fill_profile_menu_default_tab' => $id ),
						),
						array(
							'id'          => 'profile_tab_' . $id . '_privacy',
							'type'        => 'select',
							// translators: %s: Tab title
							'label'       => sprintf( __( 'Who can see %s Tab?', 'ultimate-member' ), $tab['name'] ),
							'tooltip'     => __( 'Select which users can view this tab.', 'ultimate-member' ),
							'options'     => UM()->profile()->tabs_privacy(),
							'conditional' => array( 'profile_tab_' . $id, '=', 1 ),
							'size'        => 'small',
						),
						array(
							'id'          => 'profile_tab_' . $id . '_roles',
							'type'        => 'select',
							'multi'       => true,
							'label'       => __( 'Allowed roles', 'ultimate-member' ),
							'tooltip'     => __( 'Select the the user roles allowed to view this tab.', 'ultimate-member' ),
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
				'tooltip'     => __( 'This will be the default tab on user profile page', 'ultimate-member' ),
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
						'id'          => 'profile_menu_icons',
						'type'        => 'checkbox',
						'label'       => __( 'Enable menu icons in desktop view', 'ultimate-member' ),
						'conditional' => array( 'profile_menu', '=', 1 ),
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
			$all_taxonomies     = get_taxonomies( array( 'public' => true ), 'objects' );
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
					'id'      => 'accessible',
					'type'    => 'select',
					'label'   => __( 'Global Site Access', 'ultimate-member' ),
					'tooltip' => __( 'Globally control the access of your site, you can have separate restrict options per post/page by editing the desired item.', 'ultimate-member' ),
					'options' => array(
						0 => __( 'Site accessible to Everyone', 'ultimate-member' ),
						2 => __( 'Site accessible to Logged In Users', 'ultimate-member' ),
					),
					'size'    => 'medium',
				),
				array(
					'id'          => 'access_redirect',
					'type'        => 'text',
					'label'       => __( 'Custom Redirect URL', 'ultimate-member' ),
					'tooltip'     => __( 'A logged out user will be redirected to this url If he is not permitted to access the site', 'ultimate-member' ),
					'conditional' => array( 'accessible', '=', 2 ),
				),
				array(
					'id'                  => 'access_exclude_uris',
					'type'                => 'multi_text',
					'label'               => __( 'Exclude the following URLs', 'ultimate-member' ),
					'tooltip'             => __( 'Here you can exclude URLs beside the redirect URI to be accessible to everyone', 'ultimate-member' ),
					'add_text'            => __( 'Add New URL', 'ultimate-member' ),
					'conditional'         => array( 'accessible', '=', 2 ),
					'show_default_number' => 0,
				),
				array(
					'id'          => 'home_page_accessible',
					'type'        => 'checkbox',
					'label'       => __( 'Allow Homepage to be accessible', 'ultimate-member' ),
					'conditional' => array( 'accessible', '=', 2 ),
				),
				array(
					'id'          => 'category_page_accessible',
					'type'        => 'checkbox',
					'label'       => __( 'Allow Category pages to be accessible', 'ultimate-member' ),
					'conditional' => array( 'accessible', '=', 2 ),
				),
				array(
					'id'      => 'restricted_post_title_replace',
					'type'    => 'checkbox',
					'label'   => __( 'Replace the restricted Post Title', 'ultimate-member' ),
					'tooltip' => __( 'Allow to replace the restricted post title to users that do not have permission to view the content', 'ultimate-member' ),
				),
				array(
					'id'          => 'restricted_access_post_title',
					'type'        => 'text',
					'label'       => __( 'Restricted Access Post Title', 'ultimate-member' ),
					'tooltip'     => __( 'This is the post title shown to users that do not have permission to view the content', 'ultimate-member' ),
					'conditional' => array( 'restricted_post_title_replace', '=', 1 ),
				),
				array(
					'id'      => 'restricted_access_message',
					'type'    => 'wp_editor',
					'label'   => __( 'Restricted Access Message', 'ultimate-member' ),
					'tooltip' => __( 'This is the message shown to users that do not have permission to view the content', 'ultimate-member' ),
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
							'id'    => 'restricted_blocks',
							'type'  => 'checkbox',
							'label' => __( 'Enable the "Content Restriction" settings for the Gutenberg Blocks', 'ultimate-member' ),
						),
						array(
							'id'          => 'restricted_block_message',
							'type'        => 'textarea',
							'label'       => __( 'Restricted Access Block Message', 'ultimate-member' ),
							'tooltip'     => __( 'This is the message shown to users that do not have permission to view the block\'s content', 'ultimate-member' ),
							'conditional' => array( 'restricted_blocks', '=', 1 ),
						),
					)
				);

				$settings_map['restricted_blocks']        = array(
					'sanitize' => 'bool',
				);
				$settings_map['restricted_block_message'] = array(
					'sanitize' => 'textarea',
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
						'id'      => 'restricted_access_post_metabox',
						'type'    => 'multi_checkbox',
						'label'   => __( 'Enable the "Content Restriction" settings for post types', 'ultimate-member' ),
						'tooltip' => __( 'Check post types for which you plan to use the "Content Restriction" settings', 'ultimate-member' ),
						'options' => $post_types_options,
						'columns' => 3,
						'value'   => $restricted_access_post_metabox_value,
						'default' => UM()->options()->get_default( 'restricted_access_post_metabox' ),
					),
					array(
						'id'      => 'restricted_access_taxonomy_metabox',
						'type'    => 'multi_checkbox',
						'label'   => __( 'Enable the "Content Restriction" settings for taxonomies', 'ultimate-member' ),
						'tooltip' => __( 'Check taxonomies for which you plan to use the "Content Restriction" settings', 'ultimate-member' ),
						'options' => $taxonomies_options,
						'columns' => 3,
						'value'   => $restricted_access_taxonomy_metabox_value,
						'default' => UM()->options()->get_default( 'restricted_access_taxonomy_metabox' ),
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
				'id'      => 'member_directory_own_table',
				'type'    => 'same_page_update',
				'label'   => __( 'Enable custom table for usermeta', 'ultimate-member' ),
				'tooltip' => __( 'Check this box if you would like to enable the use of a custom table for user metadata. Improved performance for member directory searches.', 'ultimate-member' ),
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
					'permalink_base'                        => array(
						'sanitize' => 'key',
					),
					'display_name'                          => array(
						'sanitize' => 'key',
					),
					'display_name_field'                    => array(
						'sanitize' => 'text',
					),
					'author_redirect'                       => array(
						'sanitize' => 'bool',
					),
					'members_page'                          => array(
						'sanitize' => 'bool',
					),
					'use_gravatars'                         => array(
						'sanitize' => 'bool',
					),
					'use_um_gravatar_default_builtin_image' => array(
						'sanitize' => 'key',
					),
					'use_um_gravatar_default_image'         => array(
						'sanitize' => 'bool',
					),
					'require_strongpass'                    => array(
						'sanitize' => 'bool',
					),
					'password_min_chars'                    => array(
						'sanitize' => 'absint',
					),
					'password_max_chars'                    => array(
						'sanitize' => 'absint',
					),
					'profile_noindex'                       => array(
						'sanitize' => 'bool',
					),
					'activation_link_expiry_time'           => array(
						'sanitize' => 'absint',
					),
					'account_tab_password'                  => array(
						'sanitize' => 'bool',
					),
					'account_tab_privacy'                   => array(
						'sanitize' => 'bool',
					),
					'account_tab_notifications'             => array(
						'sanitize' => 'bool',
					),
					'account_tab_delete'                    => array(
						'sanitize' => 'bool',
					),
					'delete_account_text'                   => array(
						'sanitize' => 'textarea',
					),
					'delete_account_no_pass_required_text'  => array(
						'sanitize' => 'textarea',
					),
					'account_name'                          => array(
						'sanitize' => 'bool',
					),
					'account_name_disable'                  => array(
						'sanitize' => 'bool',
					),
					'account_name_require'                  => array(
						'sanitize' => 'bool',
					),
					'account_email'                         => array(
						'sanitize' => 'bool',
					),
					'account_general_password'              => array(
						'sanitize' => 'bool',
					),
					'account_hide_in_directory'             => array(
						'sanitize' => 'bool',
					),
					'account_hide_in_directory_default'     => array(
						'sanitize' => 'text',
					),
					'profile_photo_max_size'                => array(
						'sanitize' => 'absint',
					),
					'cover_photo_max_size'                  => array(
						'sanitize' => 'absint',
					),
					'photo_thumb_sizes'                     => array(
						'sanitize' => 'absint',
					),
					'cover_thumb_sizes'                     => array(
						'sanitize' => 'absint',
					),
					'image_orientation_by_exif'             => array(
						'sanitize' => 'bool',
					),
					'image_compression'                     => array(
						'sanitize' => 'absint',
					),
					'image_max_width'                       => array(
						'sanitize' => 'absint',
					),
					'cover_min_width'                       => array(
						'sanitize' => 'absint',
					),
					'enable_reset_password_limit'           => array(
						'sanitize' => 'bool',
					),
					'reset_password_limit_number'           => array(
						'sanitize' => 'absint',
					),
					'blocked_emails'                        => array(
						'sanitize' => 'textarea',
					),
					'blocked_words'                         => array(
						'sanitize' => 'textarea',
					),
					'allowed_choice_callbacks'              => array(
						'sanitize' => 'textarea',
					),
					'allow_url_redirect_confirm'            => array(
						'sanitize' => 'bool',
					),
					'admin_email'                           => array(
						'sanitize' => 'text',
					),
					'mail_from'                             => array(
						'sanitize' => 'text',
					),
					'mail_from_addr'                        => array(
						'sanitize' => 'text',
					),
					'email_html'                            => array(
						'sanitize' => 'bool',
					),
					'profile_template'                      => array(
						'sanitize' => 'text',
					),
					'profile_max_width'                     => array(
						'sanitize' => 'text',
					),
					'profile_area_max_width'                => array(
						'sanitize' => 'text',
					),
					'profile_icons'                         => array(
						'sanitize' => 'key',
					),
					'profile_primary_btn_word'              => array(
						'sanitize' => 'text',
					),
					'profile_secondary_btn'                 => array(
						'sanitize' => 'bool',
					),
					'profile_secondary_btn_word'            => array(
						'sanitize' => 'text',
					),
					'default_avatar'                        => array(
						'sanitize' => 'url',
					),
					'default_cover'                         => array(
						'sanitize' => 'url',
					),
					'disable_profile_photo_upload'          => array(
						'sanitize' => 'bool',
					),
					'profile_photosize'                     => array(
						'sanitize' => array( UM()->admin(), 'sanitize_photosize' ),
					),
					'profile_cover_enabled'                 => array(
						'sanitize' => 'bool',
					),
					'profile_coversize'                     => array(
						'sanitize' => array( UM()->admin(), 'sanitize_cover_photosize' ),
					),
					'profile_cover_ratio'                   => array(
						'sanitize' => 'text',
					),
					'profile_show_metaicon'                 => array(
						'sanitize' => 'bool',
					),
					'profile_show_name'                     => array(
						'sanitize' => 'bool',
					),
					'profile_show_social_links'             => array(
						'sanitize' => 'bool',
					),
					'profile_show_bio'                      => array(
						'sanitize' => 'bool',
					),
					'profile_show_html_bio'                 => array(
						'sanitize' => 'bool',
					),
					'profile_bio_maxchars'                  => array(
						'sanitize' => 'absint',
					),
					'profile_header_menu'                   => array(
						'sanitize' => 'key',
					),
					'profile_empty_text'                    => array(
						'sanitize' => 'bool',
					),
					'profile_empty_text_emo'                => array(
						'sanitize' => 'bool',
					),
					'register_template'                     => array(
						'sanitize' => 'text',
					),
					'register_max_width'                    => array(
						'sanitize' => 'text',
					),
					'register_align'                        => array(
						'sanitize' => 'key',
					),
					'register_icons'                        => array(
						'sanitize' => 'key',
					),
					'register_primary_btn_word'             => array(
						'sanitize' => 'text',
					),
					'register_secondary_btn'                => array(
						'sanitize' => 'bool',
					),
					'register_secondary_btn_word'           => array(
						'sanitize' => 'text',
					),
					'register_secondary_btn_url'            => array(
						'sanitize' => 'url',
					),
					'register_role'                         => array(
						'sanitize' => 'key',
					),
					'login_template'                        => array(
						'sanitize' => 'text',
					),
					'login_max_width'                       => array(
						'sanitize' => 'text',
					),
					'login_align'                           => array(
						'sanitize' => 'key',
					),
					'login_icons'                           => array(
						'sanitize' => 'key',
					),
					'login_primary_btn_word'                => array(
						'sanitize' => 'text',
					),
					'login_secondary_btn'                   => array(
						'sanitize' => 'bool',
					),
					'login_secondary_btn_word'              => array(
						'sanitize' => 'text',
					),
					'login_secondary_btn_url'               => array(
						'sanitize' => 'url',
					),
					'login_forgot_pass_link'                => array(
						'sanitize' => 'bool',
					),
					'login_show_rememberme'                 => array(
						'sanitize' => 'bool',
					),
					'form_asterisk'                         => array(
						'sanitize' => 'bool',
					),
					'profile_title'                         => array(
						'sanitize' => 'text',
					),
					'profile_desc'                          => array(
						'sanitize' => 'textarea',
					),
					'um_profile_object_cache_stop'          => array(
						'sanitize' => 'bool',
					),
					'enable_blocks'                         => array(
						'sanitize' => 'bool',
					),
					'rest_api_version'                      => array(
						'sanitize' => 'text',
					),
					'uninstall_on_delete'                   => array(
						'sanitize' => 'bool',
					),
				)
			);

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
					''             => array(
						'title'    => __( 'General', 'ultimate-member' ),
						'sections' => array(
							''        => array(
								'title'  => __( 'Pages', 'ultimate-member' ),
								'fields' => $general_pages_fields,
							),
							'users'   => array(
								'title'  => __( 'Users', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'          => 'permalink_base',
										'type'        => 'select',
										'size'        => 'small',
										'label'       => __( 'Profile Permalink Base', 'ultimate-member' ),
										// translators: %s: Profile page URL
										'tooltip'     => sprintf( __( 'Here you can control the permalink structure of the user profile URL globally e.g. %s<strong>username</strong>/', 'ultimate-member' ), trailingslashit( um_get_core_page( 'user' ) ) ),
										'options'     => array(
											'user_login' => __( 'Username', 'ultimate-member' ),
											'name'       => __( 'First and Last Name with \'.\'', 'ultimate-member' ),
											'name_dash'  => __( 'First and Last Name with \'-\'', 'ultimate-member' ),
											'name_plus'  => __( 'First and Last Name with \'+\'', 'ultimate-member' ),
											'user_id'    => __( 'User ID', 'ultimate-member' ),
										),
										'placeholder' => __( 'Select...', 'ultimate-member' ),
									),
									array(
										'id'          => 'display_name',
										'type'        => 'select',
										'size'        => 'medium',
										'label'       => __( 'User Display Name', 'ultimate-member' ),
										'tooltip'     => __( 'This is the name that will be displayed for users on the front end of your site. Default setting uses first/last name as display name if it exists', 'ultimate-member' ),
										'options'     => array(
											'default'        => __( 'Default WP Display Name', 'ultimate-member' ),
											'nickname'       => __( 'Nickname', 'ultimate-member' ),
											'username'       => __( 'Username', 'ultimate-member' ),
											'full_name'      => __( 'First name & last name', 'ultimate-member' ),
											'sur_name'       => __( 'Last name & first name', 'ultimate-member' ),
											'initial_name'   => __( 'First name & first initial of last name', 'ultimate-member' ),
											'initial_name_f' => __( 'First initial of first name & last name', 'ultimate-member' ),
											'first_name'     => __( 'First name only', 'ultimate-member' ),
											'field'          => __( 'Custom field(s)', 'ultimate-member' ),
										),
										'placeholder' => __( 'Select...', 'ultimate-member' ),
									),
									array(
										'id'          => 'display_name_field',
										'type'        => 'text',
										'label'       => __( 'Display Name Custom Field(s)', 'ultimate-member' ),
										'tooltip'     => __( 'Specify the custom field meta key or custom fields seperated by comma that you want to use to display users name on the frontend of your site', 'ultimate-member' ),
										'conditional' => array( 'display_name', '=', 'field' ),
									),
									array(
										'id'      => 'author_redirect',
										'type'    => 'checkbox',
										'label'   => __( 'Automatically redirect author page to their profile?', 'ultimate-member' ),
										'tooltip' => __( 'If enabled, author pages will automatically redirect to the user\'s profile page', 'ultimate-member' ),
									),
									array(
										'id'      => 'members_page',
										'type'    => 'checkbox',
										'label'   => __( 'Enable Members Directory', 'ultimate-member' ),
										'tooltip' => __( 'Control whether to enable or disable member directories on this site', 'ultimate-member' ),
									),
									array(
										'id'      => 'use_gravatars',
										'type'    => 'checkbox',
										'label'   => __( 'Use Gravatars?', 'ultimate-member' ),
										'tooltip' => __( 'Do you want to use gravatars instead of the default plugin profile photo (If the user did not upload a custom profile photo / avatar)', 'ultimate-member' ),
									),
									array(
										'id'          => 'use_um_gravatar_default_builtin_image',
										'type'        => 'select',
										'label'       => __( 'Use Gravatar builtin image', 'ultimate-member' ),
										'tooltip'     => __( 'Gravatar has a number of built in options which you can also use as defaults', 'ultimate-member' ),
										'options'     => array(
											'default'   => __( 'Default', 'ultimate-member' ),
											'404'       => __( '404 ( File Not Found response )', 'ultimate-member' ),
											'mm'        => __( 'Mystery Man', 'ultimate-member' ),
											'identicon' => __( 'Identicon', 'ultimate-member' ),
											'monsterid' => __( 'Monsterid', 'ultimate-member' ),
											'wavatar'   => __( 'Wavatar', 'ultimate-member' ),
											'retro'     => __( 'Retro', 'ultimate-member' ),
											'blank'     => __( 'Blank ( a transparent PNG image )', 'ultimate-member' ),
										),
										'conditional' => array( 'use_gravatars', '=', 1 ),
										'size'        => 'medium',
									),
									array(
										'id'          => 'use_um_gravatar_default_image',
										'type'        => 'checkbox',
										'label'       => __( 'Use Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
										'tooltip'     => __( 'Do you want to use the plugin default avatar instead of the gravatar default photo (If the user did not upload a custom profile photo / avatar)', 'ultimate-member' ),
										'conditional' => array( 'use_um_gravatar_default_builtin_image', '=', 'default' ),
									),
									array(
										'id'      => 'require_strongpass',
										'type'    => 'checkbox',
										'label'   => __( 'Require a strong password?', 'ultimate-member' ),
										'tooltip' => __( 'Enable or disable a strong password rules common for all Ultimate Member forms.', 'ultimate-member' ),
									),
									array(
										'id'      => 'password_min_chars',
										'type'    => 'number',
										'label'   => __( 'Password minimum length', 'ultimate-member' ),
										'tooltip' => __( 'If you want to enable a minimum number of characters to be in password. User password field in the UM forms has own settings for that. Leave empty to use default value 8', 'ultimate-member' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'password_max_chars',
										'type'    => 'number',
										'label'   => __( 'Password maximum length', 'ultimate-member' ),
										'tooltip' => __( 'If you want to enable a maximum number of characters to be in password. User password field in the UM forms has own settings for that. Leave empty to use default value 30', 'ultimate-member' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'profile_noindex',
										'type'    => 'select',
										'size'    => 'small',
										'label'   => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
										'tooltip' => __( 'Hides the profile page for robots. This setting can be overridden by individual role settings.', 'ultimate-member' ),
										'options' => array(
											'0' => __( 'No', 'ultimate-member' ),
											'1' => __( 'Yes', 'ultimate-member' ),
										),
									),
									array(
										'id'      => 'activation_link_expiry_time',
										'type'    => 'number',
										'label'   => __( 'Activation link lifetime', 'ultimate-member' ),
										'tooltip' => __( 'How long does an activation link live in seconds? Leave empty for endless links.', 'ultimate-member' ),
										'size'    => 'small',
									),
								),
							),
							'account' => array(
								'title'  => __( 'Account', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'      => 'account_tab_password',
										'type'    => 'checkbox',
										'label'   => __( 'Password Account Tab', 'ultimate-member' ),
										'tooltip' => __( 'Enable/disable the Password account tab in account page', 'ultimate-member' ),
									),
									array(
										'id'      => 'account_tab_privacy',
										'type'    => 'checkbox',
										'label'   => __( 'Privacy Account Tab', 'ultimate-member' ),
										'tooltip' => __( 'Enable/disable the Privacy account tab in account page', 'ultimate-member' ),
									),
									array(
										'id'      => 'account_tab_notifications',
										'type'    => 'checkbox',
										'label'   => __( 'Notifications Account Tab', 'ultimate-member' ),
										'tooltip' => __( 'Enable/disable the Notifications account tab in account page', 'ultimate-member' ),
									),
									array(
										'id'      => 'account_tab_delete',
										'type'    => 'checkbox',
										'label'   => __( 'Delete Account Tab', 'ultimate-member' ),
										'tooltip' => __( 'Enable/disable the Delete account tab in account page', 'ultimate-member' ),
									),
									array(
										'id'      => 'delete_account_text',
										'type'    => 'textarea', // bug with wp 4.4? should be editor
										'label'   => __( 'Account Deletion Custom Text', 'ultimate-member' ),
										'tooltip' => __( 'This is custom text that will be displayed to users before they delete their accounts from your site when password is required.', 'ultimate-member' ),
										'args'    => array(
											'textarea_rows' => 6,
										),
									),
									array(
										'id'      => 'delete_account_no_pass_required_text',
										'type'    => 'textarea',
										'label'   => __( 'Account Deletion without password Custom Text', 'ultimate-member' ),
										'tooltip' => __( 'This is custom text that will be displayed to users before they delete their accounts from your site when password isn\'t required.', 'ultimate-member' ),
										'args'    => array(
											'textarea_rows' => 6,
										),
									),
									array(
										'id'      => 'account_name',
										'type'    => 'checkbox',
										'label'   => __( 'Add a First & Last Name fields', 'ultimate-member' ),
										'tooltip' => __( 'Whether to enable these fields on the user account page by default or hide them.', 'ultimate-member' ),
									),
									array(
										'id'          => 'account_name_disable',
										'type'        => 'checkbox',
										'label'       => __( 'Disable First & Last Name fields', 'ultimate-member' ),
										'tooltip'     => __( 'Whether to allow users changing their first and last name in account page.', 'ultimate-member' ),
										'conditional' => array( 'account_name', '=', '1' ),
									),
									array(
										'id'          => 'account_name_require',
										'type'        => 'checkbox',
										'label'       => __( 'Require First & Last Name', 'ultimate-member' ),
										'tooltip'     => __( 'Require first and last name?', 'ultimate-member' ),
										'conditional' => array( 'account_name', '=', '1' ),
									),
									array(
										'id'      => 'account_email',
										'type'    => 'checkbox',
										'label'   => __( 'Allow users to change e-mail', 'ultimate-member' ),
										'tooltip' => __( 'Whether to allow users changing their email in account page.', 'ultimate-member' ),
									),
									array(
										'id'      => 'account_general_password',
										'type'    => 'checkbox',
										'label'   => __( 'Password is required?', 'ultimate-member' ),
										'tooltip' => __( 'Password is required to save account data.', 'ultimate-member' ),
									),
									array(
										'id'          => 'account_hide_in_directory',
										'type'        => 'checkbox',
										'label'       => __( 'Allow users to hide their profiles from directory', 'ultimate-member' ),
										'tooltip'     => __( 'Whether to allow users changing their profile visibility from member directory in account page.', 'ultimate-member' ),
										'conditional' => array( 'account_tab_privacy', '=', '1' ),
									),
									array(
										'id'          => 'account_hide_in_directory_default',
										'type'        => 'select',
										'label'       => __( 'Hide profiles from directory by default', 'ultimate-member' ),
										'tooltip'     => __( 'Set default value for the "Hide my profile from directory" option', 'ultimate-member' ),
										'options'     => array(
											'No'  => __( 'No', 'ultimate-member' ),
											'Yes' => __( 'Yes', 'ultimate-member' ),
										),
										'size'        => 'small',
										'conditional' => array( 'account_hide_in_directory', '=', '1' ),
									),
								),
							),
							'uploads' => array(
								'title'  => __( 'Uploads', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'      => 'profile_photo_max_size',
										'type'    => 'text',
										'size'    => 'small',
										'label'   => __( 'Profile Photo Maximum File Size (bytes)', 'ultimate-member' ),
										'tooltip' => __( 'Sets a maximum size for the uploaded photo', 'ultimate-member' ),
									),
									array(
										'id'      => 'cover_photo_max_size',
										'type'    => 'text',
										'size'    => 'small',
										'label'   => __( 'Cover Photo Maximum File Size (bytes)', 'ultimate-member' ),
										'tooltip' => __( 'Sets a maximum size for the uploaded cover', 'ultimate-member' ),
									),
									array(
										'id'                  => 'photo_thumb_sizes',
										'type'                => 'multi_text',
										'size'                => 'small',
										'label'               => __( 'Profile Photo Thumbnail Sizes (px)', 'ultimate-member' ),
										'tooltip'             => __( 'Here you can define which thumbnail sizes will be created for each profile photo upload.', 'ultimate-member' ),
										'validate'            => 'numeric',
										'add_text'            => __( 'Add New Size', 'ultimate-member' ),
										'show_default_number' => 1,
									),
									array(
										'id'                  => 'cover_thumb_sizes',
										'type'                => 'multi_text',
										'size'                => 'small',
										'label'               => __( 'Cover Photo Thumbnail Sizes (px)', 'ultimate-member' ),
										'tooltip'             => __( 'Here you can define which thumbnail sizes will be created for each cover photo upload.', 'ultimate-member' ),
										'validate'            => 'numeric',
										'add_text'            => __( 'Add New Size', 'ultimate-member' ),
										'show_default_number' => 1,
									),
									array(
										'id'      => 'image_orientation_by_exif',
										'type'    => 'checkbox',
										'label'   => __( 'Change image orientation', 'ultimate-member' ),
										'tooltip' => __( 'Rotate image to and use orientation by the camera EXIF data.', 'ultimate-member' ),
									),
									array(
										'id'      => 'image_compression',
										'type'    => 'text',
										'size'    => 'small',
										'label'   => __( 'Image Quality', 'ultimate-member' ),
										'tooltip' => __( 'Quality is used to determine quality of image uploads, and ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file). The default range is 60.', 'ultimate-member' ),
									),

									array(
										'id'      => 'image_max_width',
										'type'    => 'text',
										'size'    => 'small',
										'label'   => __( 'Image Upload Maximum Width (px)', 'ultimate-member' ),
										'tooltip' => __( 'Any image upload above this width will be resized to this limit automatically.', 'ultimate-member' ),
									),

									array(
										'id'      => 'cover_min_width',
										'type'    => 'text',
										'size'    => 'small',
										'label'   => __( 'Cover Photo Minimum Width (px)', 'ultimate-member' ),
										'tooltip' => __( 'This will be the minimum width for cover photo uploads', 'ultimate-member' ),
									),
								),
							),
						),
					),
					'access'       => array(
						'title'    => __( 'Access', 'ultimate-member' ),
						'sections' => array(
							''      => array(
								'title'  => __( 'Restriction Content', 'ultimate-member' ),
								'fields' => $access_fields,
							),
							'other' => array(
								'title'  => __( 'Other', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'    => 'enable_reset_password_limit',
										'type'  => 'checkbox',
										'label' => __( 'Enable the Reset Password Limit?', 'ultimate-member' ),
									),
									array(
										'id'          => 'reset_password_limit_number',
										'type'        => 'text',
										'label'       => __( 'Reset Password Limit', 'ultimate-member' ),
										'tooltip'     => __( 'Set the maximum reset password limit. If reached the maximum limit, user will be locked from using this.', 'ultimate-member' ),
										'validate'    => 'numeric',
										'conditional' => array( 'enable_reset_password_limit', '=', 1 ),
										'size'        => 'small',
									),
									array(
										'id'      => 'blocked_emails',
										'type'    => 'textarea',
										'label'   => __( 'Blocked Email Addresses (Enter one email per line)', 'ultimate-member' ),
										'tooltip' => __( 'This will block the specified e-mail addresses from being able to sign up or sign in to your site. To block an entire domain, use something like *@domain.com', 'ultimate-member' ),
									),
									array(
										'id'      => 'blocked_words',
										'type'    => 'textarea',
										'label'   => __( 'Blacklist Words (Enter one word per line)', 'ultimate-member' ),
										'tooltip' => __( 'This option lets you specify blacklist of words to prevent anyone from signing up with such a word as their username', 'ultimate-member' ),
									),
									array(
										'id'      => 'allowed_choice_callbacks',
										'type'    => 'textarea',
										'label'   => __( 'Allowed Choice Callbacks (Enter one PHP function per line)', 'ultimate-member' ),
										'tooltip' => __( 'This option lets you specify the choice callback functions to prevent anyone from using 3rd-party functions that may put your site at risk.', 'ultimate-member' ),
									),
									array(
										'id'      => 'allow_url_redirect_confirm',
										'type'    => 'checkbox',
										'label'   => __( 'Allow external link redirect confirm', 'ultimate-member' ),
										'tooltip' => __( 'Using JS.confirm alert when you go to an external link.', 'ultimate-member' ),
									),
								),
							),
						),
					),
					'email'        => array(
						'title'  => __( 'Email', 'ultimate-member' ),
						'fields' => array(
							array(
								'id'      => 'admin_email',
								'type'    => 'text',
								'label'   => __( 'Admin E-mail Address', 'ultimate-member' ),
								'tooltip' => __( 'e.g. admin@companyname.com', 'ultimate-member' ),
							),
							array(
								'id'      => 'mail_from',
								'type'    => 'text',
								'label'   => __( 'Mail appears from', 'ultimate-member' ),
								'tooltip' => __( 'e.g. Site Name', 'ultimate-member' ),
							),
							array(
								'id'      => 'mail_from_addr',
								'type'    => 'text',
								'label'   => __( 'Mail appears from address', 'ultimate-member' ),
								'tooltip' => __( 'e.g. admin@companyname.com', 'ultimate-member' ),
							),
							array(
								'id'      => 'email_html',
								'type'    => 'checkbox',
								'label'   => __( 'Use HTML for E-mails?', 'ultimate-member' ),
								'tooltip' => __( 'If you plan use e-mails with HTML, please make sure that this option is enabled. Otherwise, HTML will be displayed as plain text.', 'ultimate-member' ),
							),
						),
					),
					'appearance'   => array(
						'title'    => __( 'Appearance', 'ultimate-member' ),
						'sections' => array(
							''                  => array(
								'title'  => __( 'Profile', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'      => 'profile_template',
										'type'    => 'select',
										'label'   => __( 'Profile Default Template', 'ultimate-member' ),
										'tooltip' => __( 'This will be the default template to output profile', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_template' ),
										'options' => UM()->shortcodes()->get_templates( 'profile' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'profile_max_width',
										'type'    => 'text',
										'label'   => __( 'Profile Maximum Width', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_max_width' ),
										'tooltip' => __( 'The maximum width this shortcode can take from the page width', 'ultimate-member' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'profile_area_max_width',
										'type'    => 'text',
										'label'   => __( 'Profile Area Maximum Width', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_area_max_width' ),
										'tooltip' => __( 'The maximum width of the profile area inside profile (below profile header)', 'ultimate-member' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'profile_icons',
										'type'    => 'select',
										'label'   => __( 'Profile Field Icons', 'ultimate-member' ),
										'tooltip' => __( 'This is applicable for edit mode only', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_icons' ),
										'options' => array(
											'field' => __( 'Show inside text field', 'ultimate-member' ),
											'label' => __( 'Show with label', 'ultimate-member' ),
											'off'   => __( 'Turn off', 'ultimate-member' ),
										),
										'size'    => 'small',
									),
									array(
										'id'      => 'profile_primary_btn_word',
										'type'    => 'text',
										'label'   => __( 'Profile Primary Button Text', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_primary_btn_word' ),
										'tooltip' => __( 'The text that is used for updating profile button', 'ultimate-member' ),
										'size'    => 'medium',
									),
									array(
										'id'      => 'profile_secondary_btn',
										'type'    => 'checkbox',
										'label'   => __( 'Profile Secondary Button', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_secondary_btn' ),
										'tooltip' => __( 'Switch on/off the secondary button display in the form', 'ultimate-member' ),
									),
									array(
										'id'          => 'profile_secondary_btn_word',
										'type'        => 'text',
										'label'       => __( 'Profile Secondary Button Text', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'profile_secondary_btn_word' ),
										'tooltip'     => __( 'The text that is used for cancelling update profile button', 'ultimate-member' ),
										'conditional' => array( 'profile_secondary_btn', '=', 1 ),
										'size'        => 'medium',
									),
									array(
										'id'                 => 'default_avatar',
										'type'               => 'media',
										'label'              => __( 'Default Profile Photo', 'ultimate-member' ),
										'tooltip'            => __( 'You can change the default profile picture globally here. Please make sure that the photo is 300x300px.', 'ultimate-member' ),
										'upload_frame_title' => __( 'Select Default Profile Photo', 'ultimate-member' ),
										'default'            => array(
											'url' => um_url . 'assets/img/default_avatar.jpg',
										),
									),
									array(
										'id'                 => 'default_cover',
										'type'               => 'media',
										'url'                => true,
										'preview'            => false,
										'label'              => __( 'Default Cover Photo', 'ultimate-member' ),
										'tooltip'            => __( 'You can change the default cover photo globally here. Please make sure that the default cover is large enough and respects the ratio you are using for cover photos.', 'ultimate-member' ),
										'upload_frame_title' => __( 'Select Default Cover Photo', 'ultimate-member' ),
									),
									array(
										'id'      => 'disable_profile_photo_upload',
										'type'    => 'checkbox',
										'label'   => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
										'tooltip' => __( 'Switch on/off the profile photo uploader', 'ultimate-member' ),
										'default' => um_get_metadefault( 'disable_profile_photo_upload' ),
									),
									array(
										'id'      => 'profile_photosize',
										'type'    => 'select',
										'label'   => __( 'Profile Photo Size', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_photosize' ),
										'options' => UM()->files()->get_profile_photo_size( 'photo_thumb_sizes' ),
										'tooltip' => __( 'The global default of profile photo size. This can be overridden by individual form settings', 'ultimate-member' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'profile_cover_enabled',
										'type'    => 'checkbox',
										'label'   => __( 'Profile Cover Photos', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_cover_enabled' ),
										'tooltip' => __( 'Switch on/off the profile cover photos', 'ultimate-member' ),
									),
									array(
										'id'          => 'profile_coversize',
										'type'        => 'select',
										'label'       => __( 'Profile Cover Size', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'profile_coversize' ),
										'options'     => UM()->files()->get_profile_photo_size( 'cover_thumb_sizes' ),
										'tooltip'     => __( 'The global default width of cover photo size. This can be overridden by individual form settings', 'ultimate-member' ),
										'conditional' => array( 'profile_cover_enabled', '=', 1 ),
										'size'        => 'small',
									),
									array(
										'id'          => 'profile_cover_ratio',
										'type'        => 'select',
										'label'       => __( 'Profile Cover Ratio', 'ultimate-member' ),
										'tooltip'     => __( 'Choose global ratio for cover photos of profiles', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'profile_cover_ratio' ),
										'options'     => array(
											'1.6:1' => '1.6:1',
											'2.7:1' => '2.7:1',
											'2.2:1' => '2.2:1',
											'3.2:1' => '3.2:1',
										),
										'conditional' => array( 'profile_cover_enabled', '=', 1 ),
										'size'        => 'small',
									),
									array(
										'id'      => 'profile_show_metaicon',
										'type'    => 'checkbox',
										'label'   => __( 'Profile Header Meta Text Icon', 'ultimate-member' ),
										'default' => 0,
										'tooltip' => __( 'Display field icons for related user meta fields in header or not', 'ultimate-member' ),
									),
									array(
										'id'      => 'profile_show_name',
										'type'    => 'checkbox',
										'label'   => __( 'Show display name in profile header', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_show_name' ),
										'tooltip' => __( 'Switch on/off the user name on profile header', 'ultimate-member' ),
									),
									array(
										'id'      => 'profile_show_social_links',
										'type'    => 'checkbox',
										'label'   => __( 'Show social links in profile header', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_show_social_links' ),
										'tooltip' => __( 'Switch on/off the social links on profile header', 'ultimate-member' ),
									),
									array(
										'id'      => 'profile_show_bio',
										'type'    => 'checkbox',
										'label'   => __( 'Show user description in header', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_show_bio' ),
										'tooltip' => __( 'Switch on/off the user description on profile header', 'ultimate-member' ),
									),
									array(
										'id'      => 'profile_show_html_bio',
										'type'    => 'checkbox',
										'label'   => __( 'Enable HTML support for user description', 'ultimate-member' ),
										'tooltip' => __( 'Switch on/off to enable/disable support for html tags on user description.', 'ultimate-member' ),
									),
									array(
										'id'          => 'profile_bio_maxchars',
										'type'        => 'text',
										'label'       => __( 'User description maximum chars', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'profile_bio_maxchars' ),
										'tooltip'     => __( 'Maximum number of characters to allow in user description field in header.', 'ultimate-member' ),
										'conditional' => array( 'profile_show_bio', '=', 1 ),
										'size'        => 'small',
									),
									array(
										'id'      => 'profile_header_menu',
										'type'    => 'select',
										'label'   => __( 'Profile Header Menu Position', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_header_menu' ),
										'tooltip' => __( 'For incompatible themes, please make the menu open from left instead of bottom by default.', 'ultimate-member' ),
										'options' => array(
											'bc' => __( 'Bottom of Icon', 'ultimate-member' ),
											'lc' => __( 'Left of Icon (right for RTL)', 'ultimate-member' ),
										),
										'size'    => 'small',
									),
									array(
										'id'      => 'profile_empty_text',
										'type'    => 'checkbox',
										'label'   => __( 'Show a custom message if profile is empty', 'ultimate-member' ),
										'default' => um_get_metadefault( 'profile_empty_text' ),
										'tooltip' => __( 'Switch on/off the custom message that appears when the profile is empty', 'ultimate-member' ),
									),
									array(
										'id'          => 'profile_empty_text_emo',
										'type'        => 'checkbox',
										'label'       => __( 'Show the emoticon', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'profile_empty_text_emo' ),
										'tooltip'     => __( 'Switch on/off the emoticon (sad face) that appears above the message', 'ultimate-member' ),
										'conditional' => array( 'profile_empty_text', '=', 1 ),
									),
								),
							),
							'profile_menu'      => array(
								'title'  => __( 'Profile Menu', 'ultimate-member' ),
								'fields' => $appearances_profile_menu_fields,
							),
							'registration_form' => array(
								'title'  => __( 'Registration Form', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'      => 'register_template',
										'type'    => 'select',
										'label'   => __( 'Registration Default Template', 'ultimate-member' ),
										'tooltip' => __( 'This will be the default template to output registration', 'ultimate-member' ),
										'default' => um_get_metadefault( 'register_template' ),
										'options' => UM()->shortcodes()->get_templates( 'register' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'register_max_width',
										'type'    => 'text',
										'label'   => __( 'Registration Maximum Width', 'ultimate-member' ),
										'default' => um_get_metadefault( 'register_max_width' ),
										'tooltip' => __( 'The maximum width this shortcode can take from the page width', 'ultimate-member' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'register_align',
										'type'    => 'select',
										'label'   => __( 'Registration Shortcode Alignment', 'ultimate-member' ),
										'tooltip' => __( 'The shortcode is centered by default unless you specify otherwise here', 'ultimate-member' ),
										'default' => um_get_metadefault( 'register_align' ),
										'options' => array(
											'center' => __( 'Centered', 'ultimate-member' ),
											'left'   => __( 'Left aligned', 'ultimate-member' ),
											'right'  => __( 'Right aligned', 'ultimate-member' ),
										),
										'size'    => 'small',
									),
									array(
										'id'      => 'register_icons',
										'type'    => 'select',
										'label'   => __( 'Registration Field Icons', 'ultimate-member' ),
										'tooltip' => __( 'This controls the display of field icons in the registration form', 'ultimate-member' ),
										'default' => um_get_metadefault( 'register_icons' ),
										'options' => array(
											'field' => __( 'Show inside text field', 'ultimate-member' ),
											'label' => __( 'Show with label', 'ultimate-member' ),
											'off'   => __( 'Turn off', 'ultimate-member' ),
										),
										'size'    => 'small',
									),
									array(
										'id'      => 'register_primary_btn_word',
										'type'    => 'text',
										'label'   => __( 'Registration Primary Button Text', 'ultimate-member' ),
										'default' => um_get_metadefault( 'register_primary_btn_word' ),
										'tooltip' => __( 'The text that is used for primary button text', 'ultimate-member' ),
										'size'    => 'medium',
									),
									array(
										'id'      => 'register_secondary_btn',
										'type'    => 'checkbox',
										'label'   => __( 'Registration Secondary Button', 'ultimate-member' ),
										'default' => 1,
										'tooltip' => __( 'Switch on/off the secondary button display in the form', 'ultimate-member' ),
									),
									array(
										'id'          => 'register_secondary_btn_word',
										'type'        => 'text',
										'label'       => __( 'Registration Secondary Button Text', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'register_secondary_btn_word' ),
										'tooltip'     => __( 'The text that is used for the secondary button text', 'ultimate-member' ),
										'conditional' => array( 'register_secondary_btn', '=', 1 ),
										'size'        => 'medium',
									),
									array(
										'id'          => 'register_secondary_btn_url',
										'type'        => 'text',
										'label'       => __( 'Registration Secondary Button URL', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'register_secondary_btn_url' ),
										'tooltip'     => __( 'You can replace default link for this button by entering custom URL', 'ultimate-member' ),
										'conditional' => array( 'register_secondary_btn', '=', 1 ),
										'size'        => 'medium',
									),
									array(
										'id'      => 'register_role',
										'type'    => 'select',
										'label'   => __( 'Registration Default Role', 'ultimate-member' ),
										'tooltip' => __( 'This will be the default role assigned to users registering thru registration form', 'ultimate-member' ),
										'default' => um_get_metadefault( 'register_role' ),
										'options' => UM()->roles()->get_roles( __( 'Default', 'ultimate-member' ) ),
										'size'    => 'small',
									),
								),
							),
							'login_form'        => array(
								'title'  => __( 'Login Form', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'      => 'login_template',
										'type'    => 'select',
										'label'   => __( 'Login Default Template', 'ultimate-member' ),
										'tooltip' => __( 'This will be the default template to output login', 'ultimate-member' ),
										'default' => um_get_metadefault( 'login_template' ),
										'options' => UM()->shortcodes()->get_templates( 'login' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'login_max_width',
										'type'    => 'text',
										'label'   => __( 'Login Maximum Width', 'ultimate-member' ),
										'default' => um_get_metadefault( 'login_max_width' ),
										'tooltip' => __( 'The maximum width this shortcode can take from the page width', 'ultimate-member' ),
										'size'    => 'small',
									),
									array(
										'id'      => 'login_align',
										'type'    => 'select',
										'label'   => __( 'Login Shortcode Alignment', 'ultimate-member' ),
										'tooltip' => __( 'The shortcode is centered by default unless you specify otherwise here', 'ultimate-member' ),
										'default' => um_get_metadefault( 'login_align' ),
										'options' => array(
											'center' => __( 'Centered', 'ultimate-member' ),
											'left'   => __( 'Left aligned', 'ultimate-member' ),
											'right'  => __( 'Right aligned', 'ultimate-member' ),
										),
										'size'    => 'small',
									),
									array(
										'id'      => 'login_icons',
										'type'    => 'select',
										'label'   => __( 'Login Field Icons', 'ultimate-member' ),
										'tooltip' => __( 'This controls the display of field icons in the login form', 'ultimate-member' ),
										'default' => um_get_metadefault( 'login_icons' ),
										'options' => array(
											'field' => __( 'Show inside text field', 'ultimate-member' ),
											'label' => __( 'Show with label', 'ultimate-member' ),
											'off'   => __( 'Turn off', 'ultimate-member' ),
										),
										'size'    => 'small',
									),
									array(
										'id'      => 'login_primary_btn_word',
										'type'    => 'text',
										'label'   => __( 'Login Primary Button Text', 'ultimate-member' ),
										'default' => um_get_metadefault( 'login_primary_btn_word' ),
										'tooltip' => __( 'The text that is used for primary button text', 'ultimate-member' ),
										'size'    => 'medium',
									),
									array(
										'id'      => 'login_secondary_btn',
										'type'    => 'checkbox',
										'label'   => __( 'Login Secondary Button', 'ultimate-member' ),
										'default' => 1,
										'tooltip' => __( 'Switch on/off the secondary button display in the form', 'ultimate-member' ),
									),
									array(
										'id'          => 'login_secondary_btn_word',
										'type'        => 'text',
										'label'       => __( 'Login Secondary Button Text', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'login_secondary_btn_word' ),
										'tooltip'     => __( 'The text that is used for the secondary button text', 'ultimate-member' ),
										'conditional' => array( 'login_secondary_btn', '=', 1 ),
										'size'        => 'medium',
									),
									array(
										'id'          => 'login_secondary_btn_url',
										'type'        => 'text',
										'label'       => __( 'Login Secondary Button URL', 'ultimate-member' ),
										'default'     => um_get_metadefault( 'login_secondary_btn_url' ),
										'tooltip'     => __( 'You can replace default link for this button by entering custom URL', 'ultimate-member' ),
										'conditional' => array( 'login_secondary_btn', '=', 1 ),
										'size'        => 'medium',
									),
									array(
										'id'      => 'login_forgot_pass_link',
										'type'    => 'checkbox',
										'label'   => __( 'Login Forgot Password Link', 'ultimate-member' ),
										'default' => 1,
										'tooltip' => __( 'Switch on/off the forgot password link in login form', 'ultimate-member' ),
									),
									array(
										'id'      => 'login_show_rememberme',
										'type'    => 'checkbox',
										'label'   => __( 'Show "Remember Me"', 'ultimate-member' ),
										'default' => 1,
										'tooltip' => __( 'Allow users to choose If they want to stay signed in even after closing the browser. If you do not show this option, the default will be to not remember login session.', 'ultimate-member' ),
									),
								),
							),
						),
					),
					'extensions'   => array(
						'title' => __( 'Extensions', 'ultimate-member' ),
					),
					'licenses'     => array(
						'title' => __( 'Licenses', 'ultimate-member' ),
					),
					'misc'         => array(
						'title'  => __( 'Misc', 'ultimate-member' ),
						'fields' => array(
							array(
								'id'    => 'form_asterisk',
								'type'  => 'checkbox',
								'label' => __( 'Show an asterisk for required fields', 'ultimate-member' ),
							),
							array(
								'id'      => 'profile_title',
								'type'    => 'text',
								'label'   => __( 'User Profile Title', 'ultimate-member' ),
								'tooltip' => __( 'This is the title that is displayed on a specific user profile', 'ultimate-member' ),
								'size'    => 'medium',
							),
							array(
								'id'      => 'profile_desc',
								'type'    => 'textarea',
								'label'   => __( 'User Profile Dynamic Meta Description', 'ultimate-member' ),
								'tooltip' => __( 'This will be used in the meta description that is available for search-engines.', 'ultimate-member' ),
								'args'    => array(
									'textarea_rows' => 6,
								),
							),
							array(
								'id'      => 'um_profile_object_cache_stop',
								'type'    => 'checkbox',
								'label'   => __( 'Disable Cache User Profile', 'ultimate-member' ),
								'tooltip' => __( 'Check this box if you would like to disable Ultimate Member user\'s cache.', 'ultimate-member' ),
							),
							array(
								'id'      => 'enable_blocks',
								'type'    => 'checkbox',
								'label'   => __( 'Enable Gutenberg Blocks', 'ultimate-member' ),
								'tooltip' => __( 'Check this box if you would like to use Ultimate Member blocks in Gutenberg editor. Important some themes have the conflicts with Gutenberg editor.', 'ultimate-member' ),
							),
							array(
								'id'      => 'rest_api_version',
								'type'    => 'select',
								'label'   => __( 'REST API version', 'ultimate-member' ),
								'tooltip' => __( 'This controls the REST API version, we recommend to use the last version', 'ultimate-member' ),
								'options' => array(
									'1.0' => __( '1.0 version', 'ultimate-member' ),
									'2.0' => __( '2.0 version', 'ultimate-member' ),
								),
							),
							// backward compatibility option leave it disabled for better security and ability to exclude posts/terms pre-query
							// otherwise we filtering only results and restricted posts/terms can be visible
							array(
								'id'      => 'disable_restriction_pre_queries',
								'type'    => 'checkbox',
								'label'   => __( 'Disable pre-queries for restriction content logic (advanced)', 'ultimate-member' ),
								'tooltip' => __( 'Please enable this option only in the cases when you have big or unnecessary queries on your site with active restriction logic. If you want to exclude posts only from the results queries instead of pre_get_posts and fully-hidden post logic also please enable this option. It activates the restriction content logic until 2.2.x version without latest security enhancements', 'ultimate-member' ),
							),
							$same_page_update,
							array(
								'id'      => 'uninstall_on_delete',
								'type'    => 'checkbox',
								'label'   => __( 'Remove Data on Uninstall?', 'ultimate-member' ),
								'tooltip' => __( 'Check this box if you would like Ultimate Member to completely remove all of its data when the plugin/extensions are deleted.', 'ultimate-member' ),
							),
						),
					),
					'install_info' => array(
						'title'  => __( 'Install Info', 'ultimate-member' ),
						'fields' => array(
							array(
								'type' => 'install_info',
							),
						),
					),
				)
			);

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
				@uasort( $licenses, function( $a, $b ) {
					return strnatcasecmp( $a['label'], $b['label'] );
				} );
				$settings['licenses']['fields'] = $licenses;
			}

			//sorting extensions by the title
			if ( ! empty( $settings['extensions']['sections'] ) ) {
				$extensions = $settings['extensions']['sections'];

				@uasort( $extensions, function( $a, $b ) {
					return strnatcasecmp( $a['title'], $b['title'] );
				} );

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
		function get_section_fields( $tab, $section ) {

			if ( empty( $this->settings_structure[ $tab ] ) ) {
				return array();
			}

			if ( ! empty( $this->settings_structure[ $tab ]['sections'][ $section ]['fields'] ) ) {
				return $this->settings_structure[ $tab ]['sections'][ $section ]['fields'];
			} elseif ( ! empty( $this->settings_structure[ $tab ]['fields'] ) ) {
				return $this->settings_structure[ $tab ]['fields'];
			}

			return array();
		}


		/**
		 * Setup admin menu
		 */
		function primary_admin_menu() {
			add_submenu_page( 'ultimatemember', __( 'Settings', 'ultimate-member' ), __( 'Settings', 'ultimate-member' ), 'manage_options', 'um_options', array( &$this, 'settings_page' ) );
		}


		/**
		 * Settings page callback
		 */
		function settings_page() {
			$current_tab = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );

			$settings_struct = $this->settings_structure[ $current_tab ];

			//remove not option hidden fields
			if ( ! empty( $settings_struct['fields'] ) ) {
				foreach ( $settings_struct['fields'] as $field_key => $field_options ) {

					if ( isset( $field_options['is_option'] ) && $field_options['is_option'] === false ) {
						unset( $settings_struct['fields'][ $field_key ] );
					}

				}
			}

			if ( empty( $settings_struct['fields'] ) && empty( $settings_struct['sections'] ) ) {
				um_js_redirect( add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) ) );
			}

			if ( ! empty( $settings_struct['sections'] ) ) {
				if ( empty( $settings_struct['sections'][ $current_subtab ] ) ) {
					um_js_redirect( add_query_arg( array( 'page' => 'um_options', 'tab' => $current_tab ), admin_url( 'admin.php' ) ) );
				}
			}

			echo '<div id="um-settings-wrap" class="wrap"><h2>' .  __( 'Ultimate Member - Settings', 'ultimate-member' ) . '</h2>';

			echo $this->generate_tabs_menu() . $this->generate_subtabs_menu( $current_tab );

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
			do_action( "um_settings_page_before_" . $current_tab . "_" . $current_subtab . "_content" );

			if ( in_array( $current_tab, apply_filters('um_settings_custom_tabs', array( 'licenses', 'install_info' ) ) ) || in_array( $current_subtab, apply_filters( 'um_settings_custom_subtabs', array(), $current_tab ) ) ) {

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
				do_action( "um_settings_page_" . $current_tab . "_" . $current_subtab . "_before_section" );

				$section_fields = $this->get_section_fields( $current_tab, $current_subtab );
				$settings_section = $this->render_settings_section( $section_fields, $current_tab, $current_subtab );

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_settings_section_{$current_tab}_{$current_subtab}_content
				 *
				 * @description Render settings section
				 * @input_vars
				 * [{"var":"$content","type":"string","desc":"Section content"},
				 * {"var":"$section_fields","type":"array","desc":"Section Fields"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_settings_section_{$current_tab}_{$current_subtab}_content', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_settings_section_{$current_tab}_{$current_subtab}_content', 'my_settings_section', 10, 2 );
				 * function my_settings_section( $content ) {
				 *     // your code here
				 *     return $content;
				 * }
				 * ?>
				 */
				echo apply_filters( 'um_settings_section_' . $current_tab . '_' . $current_subtab . '_content',
					$settings_section,
					$section_fields
				);

			} else { ?>

				<form method="post" action="" name="um-settings-form" id="um-settings-form">
					<input type="hidden" value="save" name="um-settings-action" />

					<?php
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
					do_action( "um_settings_page_" . $current_tab . "_" . $current_subtab . "_before_section" );

					$section_fields = $this->get_section_fields( $current_tab, $current_subtab );
					$settings_section = $this->render_settings_section( $section_fields, $current_tab, $current_subtab );

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_settings_section_{$current_tab}_{$current_subtab}_content
					 * @description Render settings section
					 * @input_vars
					 * [{"var":"$content","type":"string","desc":"Section content"},
					 * {"var":"$section_fields","type":"array","desc":"Section Fields"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_settings_section_{$current_tab}_{$current_subtab}_content', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_settings_section_{$current_tab}_{$current_subtab}_content', 'my_settings_section', 10, 2 );
					 * function my_settings_section( $content ) {
					 *     // your code here
					 *     return $content;
					 * }
					 * ?>
					 */
					echo apply_filters( 'um_settings_section_' . $current_tab . '_' . $current_subtab . '_content',
						$settings_section,
						$section_fields
					); ?>


					<p class="submit">
						<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'ultimate-member' ) ?>" />
						<?php $um_settings_nonce = wp_create_nonce( 'um-settings-nonce' ); ?>
						<input type="hidden" name="__umnonce" value="<?php echo esc_attr( $um_settings_nonce ); ?>" />
					</p>
				</form>

			<?php }
		}


		/**
		 * Generate pages tabs
		 *
		 * @param string $page
		 * @return string
		 */
		function generate_tabs_menu( $page = 'settings' ) {

			$tabs = '<h2 class="nav-tab-wrapper um-nav-tab-wrapper">';

			switch( $page ) {
				case 'settings':
					$menu_tabs = array();
					foreach ( $this->settings_structure as $slug => $tab ) {
						if ( ! empty( $tab['fields'] ) ) {
							foreach ( $tab['fields'] as $field_key => $field_options ) {
								if ( isset( $field_options['is_option'] ) && $field_options['is_option'] === false ) {
									unset( $tab['fields'][ $field_key ] );
								}
							}
						}

						if ( ! empty( $tab['fields'] ) || ! empty( $tab['sections'] ) ) {
							$menu_tabs[ $slug ] = $tab['title'];
						}
					}

					$current_tab = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
					foreach ( $menu_tabs as $name => $label ) {
						$active = ( $current_tab == $name ) ? 'nav-tab-active' : '';
						$tabs .= '<a href="' . esc_url( admin_url( 'admin.php?page=um_options' . ( empty( $name ) ? '' : '&tab=' . $name ) ) ) . '" class="nav-tab ' . esc_attr( $active ) . '">' .
								 $label .
								 '</a>';
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

			return $tabs . '</h2>';
		}


		/**
		 * @param string $tab
		 *
		 * @return string
		 */
		function generate_subtabs_menu( $tab = '' ) {
			if ( empty( $this->settings_structure[ $tab ]['sections'] ) ) {
				return '';
			}

			$menu_subtabs = array();
			foreach ( $this->settings_structure[ $tab ]['sections'] as $slug => $subtab ) {
				$menu_subtabs[ $slug ] = $subtab['title'];
			}

			$subtabs = '<div><ul class="subsubsub">';

			$current_tab = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );
			foreach ( $menu_subtabs as $name => $label ) {
				$active = ( $current_subtab == $name ) ? 'current' : '';
				$subtabs .= '<a href="' . esc_url( admin_url( 'admin.php?page=um_options' . ( empty( $current_tab ) ? '' : '&tab=' . $current_tab ) . ( empty( $name ) ? '' : '&section=' . $name ) ) ) . '" class="' . $active . '">'
							. $label .
							'</a> | ';
			}

			return substr( $subtabs, 0, -3 ) . '</ul></div>';
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
				do_action( "um_settings_before_save" );

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
					'update' => 'settings_updated',
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


		function set_default_if_empty( $settings ) {
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
		 * Remove empty values from multi text fields
		 *
		 * @param $settings
		 * @return array
		 */
		function remove_empty_values( $settings ) {
			$tab = '';
			if ( ! empty( $_GET['tab'] ) ) {
				$tab = sanitize_key( $_GET['tab'] );
			}

			$section = '';
			if ( ! empty( $_GET['section'] ) ) {
				$section = sanitize_key( $_GET['section'] );
			}

			if ( isset( $this->settings_structure[ $tab ]['sections'][ $section ]['fields'] ) ) {
				$fields = $this->settings_structure[ $tab ]['sections'][ $section ]['fields'];
			} else {
				$fields = $this->settings_structure[ $tab ]['fields'];
			}

			if ( empty( $fields ) ) {
				return $settings;
			}


			$filtered_settings = array();
			foreach ( $settings as $key => $value ) {

				$filtered_settings[ $key ] = $value;

				foreach ( $fields as $field ) {
					if ( $field['id'] == $key && isset( $field['type'] ) && $field['type'] == 'multi_text' ) {
						$filtered_settings[ $key ] = array_filter( $settings[ $key ] );
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
					$post_ids = new \WP_Query( array(
						'post_type' => 'page',
						'meta_query' => array(
							array(
								'key'       => '_um_core',
								'compare'   => 'EXISTS'
							)
						),
						'posts_per_page' => -1,
						'fields'        => 'ids'
					) );

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
						$users = get_users( array(
							'fields' => 'ids',
						) );
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
									if ( ! empty( $md_data ) ) {
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
							$wpdb->query("TRUNCATE TABLE {$wpdb->prefix}um_metadata" );
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
							if ( ! empty( $md_data ) ) {
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
				$key = sanitize_key( $key );
				$value = sanitize_text_field( $value );

				$edd_action = '';
				$license_key = '';
				if ( empty( $this->previous_licenses[ $key ] ) && ! empty( $value ) || ( ! empty( $this->previous_licenses[ $key ] ) && ! empty( $value ) && $this->previous_licenses[ $key ] != $value ) ) {
					$edd_action = 'activate_license';
					$license_key = $value;
				} elseif ( ! empty( $this->previous_licenses[ $key ] ) && empty( $value ) ) {
					$edd_action = 'deactivate_license';
					$license_key = $this->previous_licenses[ $key ];
				} elseif ( ! empty( $this->previous_licenses[ $key ] ) && ! empty( $value ) ) {
					$edd_action = 'check_license';
					$license_key = $value;
				}

				if ( empty( $edd_action ) ) {
					continue;
				}

				$item_name = false;
				$version = false;
				$author = false;
				foreach ( $this->settings_structure['licenses']['fields'] as $field_data ) {
					if ( $field_data['id'] == $key ) {
						$item_name = ! empty( $field_data['item_name'] ) ? $field_data['item_name'] : false;
						$version = ! empty( $field_data['version'] ) ? $field_data['version'] : false;
						$author = ! empty( $field_data['author'] ) ? $field_data['author'] : false;
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
						'body'      => $api_params
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
							'body'      => $api_params
						)
					);

					if ( ! is_wp_error( $request ) ) {
						$request = json_decode( wp_remote_retrieve_body( $request ) );
					}
				}

				$request = ( $request ) ? maybe_unserialize( $request ) : false;

				if ( $edd_action == 'activate_license' || $edd_action == 'check_license' ) {
					update_option( "{$key}_edd_answer", $request );
				} else {
					delete_option( "{$key}_edd_answer" );
				}

			}
		}


		/**
		 *
		 */
		function settings_before_email_tab() {
			$email_key = empty( $_GET['email'] ) ? '' : sanitize_key( $_GET['email'] );
			$emails = UM()->config()->email_notifications;

			if ( empty( $email_key ) || empty( $emails[ $email_key ] ) ) {
				include_once um_path . 'includes/admin/core/list-tables/emails-list-table.php';
			}
		}


		/**
		 * @param $section
		 *
		 * @return string
		 */
		function settings_email_tab( $section ) {
			$email_key = empty( $_GET['email'] ) ? '' : sanitize_key( $_GET['email'] );
			$emails = UM()->config()->email_notifications;

			if ( empty( $email_key ) || empty( $emails[ $email_key ] ) ) {
				return $section;
			}

			$in_theme = UM()->mail()->template_in_theme( $email_key );

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
			$section_fields = apply_filters( 'um_admin_settings_email_section_fields', array(
				array(
					'id'    => 'um_email_template',
					'type'  => 'hidden',
					'value' => $email_key,
				),
				array(
					'id'        => $email_key . '_on',
					'type'      => 'checkbox',
					'label'     => $emails[ $email_key ]['title'],
					'tooltip'   => $emails[ $email_key ]['description'],
				),
				array(
					'id'            => $email_key . '_sub',
					'type'          => 'text',
					'label'         => __( 'Subject Line', 'ultimate-member' ),
					'conditional'   => array( $email_key . '_on', '=', 1 ),
					'tooltip'       => __( 'This is the subject line of the e-mail', 'ultimate-member' ),
				),
				array(
					'id'            => $email_key,
					'type'          => 'email_template',
					'label'         => __( 'Message Body', 'ultimate-member' ),
					'conditional'   => array( $email_key . '_on', '=', 1 ),
					'tooltip'       => __( 'This is the content of the e-mail', 'ultimate-member' ),
					'value'         => UM()->mail()->get_email_template( $email_key ),
					'in_theme'      => $in_theme
				),
			), $email_key );

			return $this->render_settings_section( $section_fields, 'email', $email_key );
		}


		/**
		 *
		 */
		function settings_appearance_profile_tab() {
			wp_enqueue_media();
		}


		/**
		 * @param $html
		 * @param $section_fields
		 *
		 * @return string
		 */
		function settings_licenses_tab( $html, $section_fields ) {
			ob_start(); ?>

			<div class="wrap-licenses">
				<input type="hidden" id="licenses_settings" name="licenses_settings" value="1">
				<?php $um_settings_nonce = wp_create_nonce( 'um-settings-nonce' ); ?>
				<input type="hidden" name="__umnonce" value="<?php echo esc_attr( $um_settings_nonce ); ?>" />
				<table class="form-table um-settings-section">
					<tbody>
					<?php foreach ( $section_fields as $field_data ) {
						$option_value = UM()->options()->get( $field_data['id'] );
						$value = isset( $option_value ) && ! empty( $option_value ) ? $option_value : ( isset( $field_data['default'] ) ? $field_data['default'] : '' );

						$license = get_option( "{$field_data['id']}_edd_answer" );

						if ( is_object( $license ) && ! empty( $value ) ) {
							// activate_license 'invalid' on anything other than valid, so if there was an error capture it
							if ( empty( $license->success ) ) {

								if ( ! empty( $license->error ) ) {
									switch ( $license->error ) {

										case 'expired' :

											$class = 'expired';
											$messages[] = sprintf(
												__( 'Your license key expired on %s. Please <a href="%s" target="_blank">renew your license key</a>.', 'ultimate-member' ),
												date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
												'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired'
											);

											$license_status = 'license-' . $class . '-notice';

											break;

										case 'revoked' :

											$class = 'error';
											$messages[] = sprintf(
												__( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'ultimate-member' ),
												'https://ultimatemember.com/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
											);

											$license_status = 'license-' . $class . '-notice';

											break;

										case 'missing' :

											$class = 'error';
											$messages[] = sprintf(
												__( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'ultimate-member' ),
												'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
											);

											$license_status = 'license-' . $class . '-notice';

											break;

										case 'invalid' :
										case 'site_inactive' :

											$class = 'error';
											$messages[] = sprintf(
												__( 'Your %s is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ultimate-member' ),
												$field_data['item_name'],
												'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
											);

											$license_status = 'license-' . $class . '-notice';

											break;

										case 'item_name_mismatch' :

											$class = 'error';
											$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'ultimate-member' ), $field_data['item_name'] );

											$license_status = 'license-' . $class . '-notice';

											break;

										case 'no_activations_left':

											$class = 'error';
											$messages[] = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'ultimate-member' ), 'https://ultimatemember.com/account' );

											$license_status = 'license-' . $class . '-notice';

											break;

										case 'license_not_activable':

											$class = 'error';
											$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'ultimate-member' );

											$license_status = 'license-' . $class . '-notice';
											break;

										default :

											$class = 'error';
											$error = ! empty(  $license->error ) ? $license->error : __( 'unknown_error', 'ultimate-member' );
											$messages[] = sprintf( __( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', 'ultimate-member' ), $error, 'https://ultimatemember.com/support' );

											$license_status = 'license-' . $class . '-notice';
											break;
									}
								} else {
									$class = 'error';
									$error = ! empty( $license->error ) ? $license->error : __( 'unknown_error', 'ultimate-member' );
									$messages[] = sprintf( __( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', 'ultimate-member' ), $error, 'https://ultimatemember.com/support' );

									$license_status = 'license-' . $class . '-notice';
								}

							} elseif ( ! empty( $license->errors ) ) {

								$errors = array_keys( $license->errors );
								$errors_data = array_values( $license->errors );

								$class = 'error';
								$error = ! empty( $errors[0] ) ? $errors[0] : __( 'unknown_error', 'ultimate-member' );
								$errors_data = ! empty( $errors_data[0][0] ) ? ', ' . $errors_data[0][0] : '';
								$messages[] = sprintf( __( 'There was an error with this license key: %s%s. Please <a href="%s">contact our support team</a>.', 'ultimate-member' ), $error, $errors_data, 'https://ultimatemember.com/support' );

								$license_status = 'license-' . $class . '-notice';

							} else {

								switch( $license->license ) {

									case 'expired' :

										$class = 'expired';
										$messages[] = sprintf(
											__( 'Your license key expired on %s. Please <a href="%s" target="_blank">renew your license key</a>.', 'ultimate-member' ),
											date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
											'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired'
										);

										$license_status = 'license-' . $class . '-notice';

										break;

									case 'revoked' :

										$class = 'error';
										$messages[] = sprintf(
											__( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'ultimate-member' ),
											'https://ultimatemember.com/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
										);

										$license_status = 'license-' . $class . '-notice';

										break;

									case 'missing' :

										$class = 'error';
										$messages[] = sprintf(
											__( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'ultimate-member' ),
											'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
										);

										$license_status = 'license-' . $class . '-notice';

										break;

									case 'invalid' :
									case 'site_inactive' :

										$class = 'error';
										$messages[] = sprintf(
											__( 'Your %s is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'ultimate-member' ),
											$field_data['item_name'],
											'https://ultimatemember.com/account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
										);

										$license_status = 'license-' . $class . '-notice';

										break;

									case 'item_name_mismatch' :

										$class = 'error';
										$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'ultimate-member' ), $field_data['item_name'] );

										$license_status = 'license-' . $class . '-notice';

										break;

									case 'no_activations_left':

										$class = 'error';
										$messages[] = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'ultimate-member' ), 'https://ultimatemember.com/account' );

										$license_status = 'license-' . $class . '-notice';

										break;

									case 'license_not_activable':

										$class = 'error';
										$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'ultimate-member' );

										$license_status = 'license-' . $class . '-notice';
										break;

									case 'valid' :
									default:

										$class = 'valid';

										$now        = current_time( 'timestamp' );
										$expiration = strtotime( $license->expires, $now );

										if( 'lifetime' === $license->expires ) {

											$messages[] = __( 'License key never expires.', 'ultimate-member' );

											$license_status = 'license-lifetime-notice';

										} elseif( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {

											$messages[] = sprintf(
												__( 'Your license key expires soon! It expires on %s. <a href="%s" target="_blank">Renew your license key</a>.', 'ultimate-member' ),
												date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
												'https://ultimatemember.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=renew'
											);

											$license_status = 'license-expires-soon-notice';

										} else {

											$messages[] = sprintf(
												__( 'Your license key expires on %s.', 'ultimate-member' ),
												date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) )
											);

											$license_status = 'license-expiration-date-notice';

										}

										break;

								}

							}

						} else {
							$class = 'empty';

							$messages[] = sprintf(
								__( 'To receive updates, please enter your valid %s license key.', 'ultimate-member' ),
								$field_data['item_name']
							);

							$license_status = null;

						} ?>

						<tr class="um-settings-line">
							<th><label for="um_options_<?php echo esc_attr( $field_data['id'] ) ?>"><?php echo esc_html( $field_data['label'] ) ?></label></th>
							<td>
								<form method="post" action="" name="um-settings-form" class="um-settings-form">
									<input type="hidden" value="save" name="um-settings-action" />
									<input type="hidden" name="licenses_settings" value="1" />
									<?php $um_settings_nonce = wp_create_nonce( 'um-settings-nonce' ); ?>
									<input type="hidden" name="__umnonce" value="<?php echo esc_attr( $um_settings_nonce ); ?>" />
									<input type="text" id="um_options_<?php echo esc_attr( $field_data['id'] ) ?>" name="um_options[<?php echo esc_attr( $field_data['id'] ) ?>]" value="<?php echo $value ?>" class="um-option-field um-long-field" data-field_id="<?php echo esc_attr( $field_data['id'] ) ?>" />
									<?php if ( ! empty( $field_data['description'] ) ) { ?>
										<div class="description"><?php echo $field_data['description'] ?></div>
									<?php } ?>

									<?php if ( ! empty( $value ) && ( ( is_object( $license ) && 'valid' == $license->license ) || 'valid' == $license ) ) { ?>
										<input type="button" class="button um_license_deactivate" id="<?php echo esc_attr( $field_data['id'] ) ?>_deactivate" value="<?php esc_attr_e( 'Clear License',  'ultimate-member' ) ?>"/>
									<?php } elseif ( empty( $value ) ) { ?>
										<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Activate', 'ultimate-member' ) ?>" />
									<?php } else { ?>
										<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Re-Activate', 'ultimate-member' ) ?>" />
										<input type="button" class="button um_license_deactivate" id="<?php echo esc_attr( $field_data['id'] ) ?>_deactivate" value="<?php esc_attr_e( 'Clear License',  'ultimate-member' ) ?>"/>
									<?php }

									if ( ! empty( $messages ) ) {
										foreach ( $messages as $message ) { ?>
											<div class="edd-license-data edd-license-<?php echo esc_attr( $class . ' ' . $license_status ) ?>">
												<p><?php echo $message ?></p>
											</div>
										<?php }
									} ?>
								</form>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
			<?php $section = ob_get_clean();

			return $section;
		}


		/**
		 * @param $html
		 * @param $section_fields
		 */
		function settings_install_info_tab( $html, $section_fields ) {
			global $wpdb;

			if ( ! class_exists( '\Browser' ) )
				require_once um_path . 'includes/lib/browser.php';

			// Detect browser
			$browser = new \Browser();

			// Get theme info
			$theme_data = wp_get_theme();
			$theme      = $theme_data->Name . ' ' . $theme_data->Version;

			// Identify Hosting Provider
			$host = um_get_host();

			um_fetch_user( get_current_user_id() );

			if ( isset( $this->content ) ) {
				echo $this->content;
			} else { ?>

				<h3>Install Info</h3>

				<form action="" method="post" dir="ltr">
					<textarea style="width:70%; height:400px;" readonly="readonly" onclick="this.focus();this.select()" id="install-info-textarea" name="um-install-info" title="<?php _e( 'To copy the Install info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'ultimate-member' ); ?>">
### Begin Install Info ###

## Please include this information when posting support requests ##

<?php
/**
 * UM hook
 *
 * @type action
 * @title um_install_info_before
 * @description Before install info settings
 * @change_log
 * ["Since: 2.0"]
 * @usage add_action( 'um_install_info_before', 'function_name', 10 );
 * @example
 * <?php
 * add_action( 'um_install_info_before', 'my_install_info_before', 10 );
 * function my_install_info_before() {
 *     // your code here
 * }
 * ?>
 */
do_action( 'um_install_info_before' ); ?>

--- Site Info ---

Site URL:					<?php echo site_url() . "\n"; ?>
Home URL:					<?php echo home_url() . "\n"; ?>
Multisite:					<?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

--- Hosting Provider ---

<?php if( $host ) : ?>
Host:						<?php echo $host . "\n"; ?>
<?php endif; ?>

--- User Browser ---

<?php echo $browser ; ?>

---- Current User Details --

<?php $user = wp_get_current_user(); ?>
Role: <?php echo implode( ', ', um_user( 'roles' ) ). "\n"; ?>


--- WordPress Configurations ---

Version:						<?php echo get_bloginfo( 'version' ) . "\n"; ?>
Language:					<?php echo get_locale()."\n"; ?>
Permalink Structure:			<?php echo get_option( 'permalink_structure' ) . "\n"; ?>
Active Theme:				<?php echo $theme . "\n"; ?>
<?php $show_on_front = get_option( 'show_on_front' ); ?>
<?php if( $show_on_front == "posts" ): ?>
Show On Front:				<?php echo get_option( 'show_on_front' ) . "/static\n" ?>
<?php elseif( $show_on_front == "page" ): ?>
Page On Front:				<?php $id = get_option( 'page_on_front' ); echo get_the_title( $id ) . ' (#' . $id . ')' . "\n" ?>
Page For Posts:				<?php $id = get_option( 'page_for_posts' ); echo get_the_title( $id ) . ' (#' . $id . ')' . "\n" ?>
<?php endif; ?>
ABSPATH:					<?php echo ABSPATH."\n"; ?>
<?php $wp_count_posts = wp_count_posts(); ?>
All Posts/Pages:				<?php echo array_sum((array)$wp_count_posts)."\n";?>
<?php
$request['cmd'] = '_notify-validate';

$params = array(
'sslverify'		=> false,
'timeout'		=> 60,
'user-agent'	=> 'UltimateMember/' . ultimatemember_version,
'body'			=> $request
);

$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
$WP_REMOTE_POST =  'wp_remote_post() works' . "\n";
} else {
$WP_REMOTE_POST =  'wp_remote_post() does not work' . "\n";
}
?>
WP Remote Post:           		<?php echo $WP_REMOTE_POST; ?>
WP_DEBUG:                 			<?php echo defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>
WP Table Prefix:          			<?php echo "Length: ". strlen( $wpdb->prefix ); echo ", Status:"; if ( strlen( $wpdb->prefix )>16 ) {echo " ERROR: Too Long";} else {echo " Acceptable";} echo "\n"; ?>
Memory Limit:   				<?php echo ( um_let_to_num( WP_MEMORY_LIMIT )/( 1024 ) )."MB"; ?><?php echo "\n"; ?>


--- UM Configurations ---

Version:						<?php echo ultimatemember_version . "\n"; ?>
Upgraded From:            		<?php echo get_option( 'um_last_version_upgrade', 'None' ) . "\n"; ?>
Current URL Method:			<?php echo UM()->options()->get( 'current_url_method' ). "\n"; ?>
Cache User Profile:			<?php if( UM()->options()->get( 'um_profile_object_cache_stop' ) == 1 ){ echo "No"; }else{ echo "Yes"; } echo "\n"; ?>
Generate Slugs on Directories:	<?php if( UM()->options()->get( 'um_generate_slug_in_directory' ) == 1 ){ echo "No"; }else{ echo "Yes"; } echo "\n"; ?>
Force UTF-8 Encoding: 		<?php if( UM()->options()->get( 'um_force_utf8_strings' ) == 1 ){ echo "Yes"; }else{ echo "No"; } echo "\n"; ?>
JS/CSS Compression: 			<?php if ( defined('SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) { echo "Yes"; }else{ echo "No"; } echo "\n"; ?>
<?php if( is_multisite() ): ?>
	Network Structure:			<?php echo UM()->options()->get( 'network_permalink_structure' ). "\n"; ?>
<?php endif; ?>
Port Forwarding in URL: 		<?php if( UM()->options()->get( 'um_port_forwarding_url' ) == 1 ){ echo "Yes"; }else{ echo "No"; } echo "\n"; ?>
Exclude CSS/JS on Home: 		<?php if( UM()->options()->get( 'js_css_exlcude_home' ) == 1 ){ echo "Yes"; }else{ echo "No"; } echo "\n"; ?>


--- UM Pages Configuration ---

<?php
/**
 * UM hook
 *
 * @type action
 * @title um_install_info_before_page_config
 * @description Before page config install info
 * @change_log
 * ["Since: 2.0"]
 * @usage add_action( 'um_install_info_before_page_config', 'function_name', 10 );
 * @example
 * <?php
 * add_action( 'um_install_info_before_page_config', 'my_install_info_before_page_config', 10 );
 * function my_install_info_before_page_config() {
 *     // your code here
 * }
 * ?>
 */
do_action( "um_install_info_before_page_config" ); ?>
User:						<?php echo get_permalink( UM()->options()->get('core_user') ) . "\n"; ?>
Account:						<?php echo get_permalink( UM()->options()->get('core_account') ) . "\n"; ?>
Members:					<?php echo get_permalink( UM()->options()->get('core_members') ) . "\n"; ?>
Register:						<?php echo get_permalink( UM()->options()->get('core_register') ) . "\n"; ?>
Login:						<?php echo get_permalink( UM()->options()->get('core_login') ) . "\n"; ?>
Logout:						<?php echo get_permalink( UM()->options()->get('core_logout') ) . "\n"; ?>
Password Reset:				<?php echo get_permalink( UM()->options()->get('core_password-reset') ) . "\n"; ?>
<?php
/**
 * UM hook
 *
 * @type action
 * @title um_install_info_after_page_config
 * @description After page config install info
 * @change_log
 * ["Since: 2.0"]
 * @usage add_action( 'um_install_info_after_page_config', 'function_name', 10 );
 * @example
 * <?php
 * add_action( 'um_install_info_after_page_config', 'my_install_info_after_page_config', 10 );
 * function my_install_info_after_page_config() {
 *     // your code here
 * }
 * ?>
 */
do_action( "um_install_info_after_page_config" ); ?>


--- UM Users Configuration ---

Default New User Role: 		<?php  echo UM()->options()->get('register_role') . "\n"; ?>
Profile Permalink Base:		<?php  echo UM()->options()->get('permalink_base') . "\n"; ?>
User Display Name:			<?php  echo UM()->options()->get('display_name') . "\n"; ?>
Force Name to Uppercase:		<?php echo $this->info_value( UM()->options()->get('force_display_name_capitlized'), 'yesno', true ); ?>
Redirect author to profile: 		<?php echo $this->info_value( UM()->options()->get('author_redirect'), 'yesno', true ); ?>
Enable Members Directory:	<?php echo $this->info_value( UM()->options()->get('members_page'), 'yesno', true ); ?>
Use Gravatars: 				<?php echo $this->info_value( UM()->options()->get('use_gravatars'), 'yesno', true ); ?>
<?php if( UM()->options()->get('use_gravatars') ): ?>Gravatar builtin image:		<?php  echo UM()->options()->get('use_um_gravatar_default_builtin_image') . "\n"; ?>
	UM Avatar as blank Gravatar: 	<?php echo $this->info_value( UM()->options()->get('use_um_gravatar_default_image'), 'yesno', true ); ?><?php endif; ?>
Require a strong password: 	<?php echo $this->info_value( UM()->options()->get('require_strongpass'), 'onoff', true ); ?>


--- UM Access Configuration ---

Panic Key: 								<?php  echo UM()->options()->get('panic_key') . "\n"; ?>
Global Site Access:						<?php  $arr = array('Site accessible to Everyone','','Site accessible to Logged In Users'); echo $arr[ (int) UM()->options()->get('accessible') ] . "\n"; ?>
<?php if( UM()->options()->get('accessible') == 2 ) { ?>
Custom Redirect URL:						<?php echo UM()->options()->get('access_redirect')."\n";?>
Exclude the following URLs:<?php echo "\t\t\t\t".implode("\t\n\t\t\t\t\t\t\t\t\t\t",UM()->options()->get('access_exclude_uris') )."\n";?>
<?php } ?>
Backend Login Screen for Guests:			<?php echo $this->info_value( UM()->options()->get('wpadmin_login'), 'yesno', true ); ?>
<?php if( ! UM()->options()->get('wpadmin_login') ) { ?>
Redirect to alternative login page:			<?php if( UM()->options()->get('wpadmin_login_redirect') == 'um_login_page' ){ echo um_get_core_page('login')."\n"; }else{ echo UM()->options()->get('wpadmin_login_redirect_url')."\n"; }?>
<?php } ?>
Backend Register Screen for Guests:		<?php echo $this->info_value( UM()->options()->get('wpadmin_register'), 'yesno', true ); ?>
<?php if( ! UM()->options()->get('wpadmin_register') ) { ?>
Redirect to alternative register page:		<?php if( UM()->options()->get('wpadmin_register_redirect') == 'um_register_page' ){ echo um_get_core_page('register')."\n"; }else{ echo UM()->options()->get('wpadmin_register_redirect_url')."\n"; }?>
<?php } ?>
Access Control widget for Admins only: 		<?php echo $this->info_value( UM()->options()->get('access_widget_admin_only'), 'yesno', true ); ?>
Enable the Reset Password Limit:			<?php echo $this->info_value( UM()->options()->get('enable_reset_password_limit'), 'yesno', true ); ?>
<?php if( UM()->options()->get('enable_reset_password_limit') ) { ?>
Reset Password Limit: <?php echo UM()->options()->get('reset_password_limit_number') ?>
Disable Reset Password Limit for Admins: <?php echo $this->info_value( UM()->options()->get('disable_admin_reset_password_limit'), 'yesno', true ) ?>
<?php } ?>
<?php $blocked_ips = UM()->options()->get('blocked_ips'); if( ! empty( $blocked_ips ) ){ ?>
Blocked IP Addresses: 					<?php echo  count( explode("\n",UM()->options()->get('blocked_ips') ) )."\n"; ?>
<?php } ?>
<?php $blocked_emails = UM()->options()->get('blocked_emails'); if( ! empty( $blocked_emails ) ){ ?>
Blocked Email Addresses: 					<?php echo  count( explode("\n",UM()->options()->get('blocked_emails') ) )."\n"; ?>
<?php } ?>
<?php $blocked_words =  UM()->options()->get('blocked_words'); if( ! empty( $blocked_words ) ){ ?>
Blacklist Words: 							<?php echo  count( explode("\n",UM()->options()->get('blocked_words') ) )."\n"; ?>
<?php } ?>


--- UM Email Configurations ---

Mail appears from:  			<?php $mail_from = UM()->options()->get('mail_from'); if( ! empty( $mail_from ) ){echo UM()->options()->get('mail_from');}else{echo "-";}; echo "\n";?>
Mail appears from address:  	<?php $mail_from_addr = UM()->options()->get('mail_from_addr'); if( ! empty( $mail_from_addr ) ){echo UM()->options()->get('mail_from_addr');}else{echo "-";}; echo "\n";?>
Use HTML for E-mails:   		<?php echo $this->info_value( UM()->options()->get('email_html'), 'yesno', true ); ?>
Account Welcome Email:  		<?php echo $this->info_value( UM()->options()->get('welcome_email_on'), 'yesno', true ); ?>
Account Activation Email:   	<?php echo $this->info_value( UM()->options()->get('checkmail_email_on'), 'yesno', true ); ?>
Pending Review Email:   		<?php echo $this->info_value( UM()->options()->get('pending_email_on'), 'yesno', true ); ?>
Account Approved Email: 		<?php echo $this->info_value( UM()->options()->get('approved_email_on'), 'yesno', true ); ?>
Account Rejected Email: 		<?php echo $this->info_value( UM()->options()->get('rejected_email_on'), 'yesno', true ); ?>
Account Deactivated Email:  	<?php echo $this->info_value( UM()->options()->get('inactive_email_on'), 'yesno', true ); ?>
Account Deleted Email:  		<?php echo $this->info_value( UM()->options()->get('deletion_email_on'), 'yesno', true ); ?>
Password Reset Email:   		<?php echo $this->info_value( UM()->options()->get('resetpw_email_on'), 'yesno', true ); ?>
Password Changed Email: 		<?php echo $this->info_value( UM()->options()->get('changedpw_email_on'), 'yesno', true ); ?>


--- UM Total Users ---

				<?php $result = count_users();
				echo 'All Users('.$result['total_users'].")\n";
				foreach( $result['avail_roles'] as $role => $count ) {
					echo $role."(".$count.")\n";
				} ?>


--- UM Roles ---

				<?php foreach( UM()->roles()->get_roles() as $role_id => $role ) {
					echo $role." ({$role_id})\n";
				} ?>


--- UM Custom Templates ---

				<?php // Show templates that have been copied to the theme's edd_templates dir
				$dir = get_stylesheet_directory() . '/ultimate-member/templates/*.php';
				if ( ! empty( $dir ) ) {
					$found = glob( $dir );
					if ( ! empty( $found ) ) {
						foreach ( glob( $dir ) as $file ) {
							echo "File: " . $file  . "\n";
						}
					} else {
						echo 'N/A'."\n";
					}
				} ?>


--- UM Email HTML Templates ---

				<?php $dir = get_stylesheet_directory() . '/ultimate-member/templates/emails/*.html';

				if ( ! empty( $dir ) ) {
					$found =  glob( $dir );
					if ( ! empty( $found ) ){
						foreach ( glob( $dir ) as $file ) {
							echo "File: ". $file  . "\n";
						}
					} else {
						echo 'N/A'."\n";
					}
				} ?>


--- Web Server Configurations ---

PHP Version:              			<?php echo PHP_VERSION . "\n"; ?>
MySQL Version:            			<?php echo $wpdb->db_version() . "\n"; ?>
Web Server Info:          			<?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>


--- PHP Configurations ---

PHP Memory Limit:         			<?php echo ini_get( 'memory_limit' ) . "\n"; ?>
PHP Upload Max Size:      			<?php echo ini_get( 'upload_max_filesize' ) . "\n"; ?>
PHP Post Max Size:        			<?php echo ini_get( 'post_max_size' ) . "\n"; ?>
PHP Upload Max Filesize:  			<?php echo ini_get( 'upload_max_filesize' ) . "\n"; ?>
PHP Time Limit:           			<?php echo ini_get( 'max_execution_time' ) . "\n"; ?>
PHP Max Input Vars:       			<?php echo ini_get( 'max_input_vars' ) . "\n"; ?>
PHP Arg Separator:        			<?php echo ini_get( 'arg_separator.output' ) . "\n"; ?>
PHP Allow URL File Open:  			<?php echo ini_get( 'allow_url_fopen' ) ? "Yes\n" : "No\n"; ?>


--- Web Server Extensions/Modules ---

DISPLAY ERRORS:           			<?php echo ( ini_get( 'display_errors' ) ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A'; ?><?php echo "\n"; ?>
FSOCKOPEN:                			<?php echo ( function_exists( 'fsockopen' ) ) ? 'Your server supports fsockopen.' : 'Your server does not support fsockopen.'; ?><?php echo "\n"; ?>
cURL:                     			<?php echo ( function_exists( 'curl_init' ) ) ? 'Your server supports cURL.' : 'Your server does not support cURL.'; ?><?php echo "\n"; ?>
SOAP Client:              			<?php echo ( class_exists( 'SoapClient' ) ) ? 'Your server has the SOAP Client enabled.' : 'Your server does not have the SOAP Client enabled.'; ?><?php echo "\n"; ?>
SUHOSIN:                  			<?php echo ( extension_loaded( 'suhosin' ) ) ? 'Your server has SUHOSIN installed.' : 'Your server does not have SUHOSIN installed.'; ?><?php echo "\n"; ?>
GD Library:               			<?php echo ( extension_loaded( 'gd' ) && function_exists('gd_info') ) ? 'PHP GD library is installed on your web server.' : 'PHP GD library is NOT installed on your web server.'; ?><?php echo "\n"; ?>
Mail:                     			<?php echo ( function_exists('mail') ) ? 'PHP mail function exist on your web server.' : 'PHP mail function doesn\'t exist on your web server.'; ?><?php echo "\n"; ?>
Exif:				          <?php echo ( extension_loaded( 'exif' ) && function_exists('exif_imagetype') ) ? 'PHP Exif library is installed on your web server.' : 'PHP Exif library is NOT installed on your web server.'; ?><?php echo "\n"; ?>


--- Session Configurations ---

Session:                  			<?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
Session Name:             			<?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
Cookie Path:              			<?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
Save Path:                			<?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
Use Cookies:              			<?php echo ini_get( 'session.use_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
Use Only Cookies:         			<?php echo ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>


--- WordPress Active Plugins ---

				<?php $plugins = get_plugins();
				$active_plugins = get_option( 'active_plugins', array() );

				foreach ( $plugins as $plugin_path => $plugin ) {
					// If the plugin isn't active, don't show it.
					if ( ! in_array( $plugin_path, $active_plugins ) )
						continue;

					echo $plugin['Name'] . ': ' . $plugin['Version'] ."\n";
				}

				if ( is_multisite() ) { ?>

--- WordPress Network Active Plugins ---

					<?php $plugins = wp_get_active_network_plugins();
					$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

					foreach ( $plugins as $plugin_path ) {
						$plugin_base = plugin_basename( $plugin_path );

						// If the plugin isn't active, don't show it.
						if ( ! array_key_exists( $plugin_base, $active_plugins ) )
							continue;

						$plugin = get_plugin_data( $plugin_path );

						echo $plugin['Name'] . ' :' . $plugin['Version'] . "\n";
					}

				}

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_install_info_after
				 * @description After install info
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_install_info_after', 'function_name', 10 );
				 * @example
				 * <?php
				 * add_action( 'um_install_info_after', 'my_install_info_after', 10 );
				 * function my_install_info_after() {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_install_info_after' ); ?>

### End Install Info ###
					</textarea>
					<p class="submit">
						<input type="hidden" name="um-addon-hook" value="download_install_info" />
						<?php submit_button( 'Download Install Info File', 'primary', 'download_install_info', false ); ?>
					</p>
				</form>

			<?php }
		}


		/**
		 *
		 */
		function um_download_install_info() {
			if ( ! empty( $_POST['download_install_info'] ) ) {
				nocache_headers();

				header( "Content-type: text/plain" );
				header( 'Content-Disposition: attachment; filename="ultimatemember-install-info.txt"' );

				echo wp_strip_all_tags( sanitize_textarea_field( $_POST['um-install-info'] ) );
				exit;
			}
		}


		/**
		 * @param string $raw_value
		 * @param string $type
		 * @param string $default
		 *
		 * @return string
		 */
		function info_value( $raw_value = '', $type = 'yesno', $default = '' ) {

			if ( $type == 'yesno' ) {
				$raw_value = ( $default == $raw_value ) ? "Yes" : "No";
			} elseif( $type == 'onoff' ) {
				$raw_value = ( $default == $raw_value ) ? "On" : "Off";
			}

			return $raw_value."\n";
		}


		/**
		 * Render settings section
		 *
		 * @param array $section_fields
		 * @param string $current_tab
		 * @param string $current_subtab
		 *
		 * @return string
		 */
		function render_settings_section( $section_fields, $current_tab, $current_subtab ) {
			ob_start();

			UM()->admin_forms_settings( array(
				'class'     => 'um_options-' . $current_tab . '-' . $current_subtab . ' um-third-column',
				'prefix_id' => 'um_options',
				'fields'    => $section_fields
			) )->render_form(); ?>

			<?php $section = ob_get_clean();

			return $section;
		}


		/**
		 * @param array $settings
		 *
		 * @return array
		 */
		function save_email_templates( $settings ) {

			if ( empty( $settings['um_email_template'] ) ) {
				return $settings;
			}

			$template = $settings['um_email_template'];
			$content = wp_kses_post( stripslashes( $settings[ $template ] ) );

			$theme_template_path = UM()->mail()->get_template_file( 'theme', $template );

			if ( ! file_exists( $theme_template_path ) ) {
				UM()->mail()->copy_email_template( $template );
			}

			$fp = fopen( $theme_template_path, "w" );
			$result = fputs( $fp, $content );
			fclose( $fp );

			if ( $result !== false ) {
				unset( $settings['um_email_template'] );
				unset( $settings[ $template ] );
			}

			return $settings;
		}
	}
}
