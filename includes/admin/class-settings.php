<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Settings' ) ) {


	/**
	 * Class Admin_Settings
	 *
	 * @package um\admin
	 */
	class Settings {


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


		/**
		 * Admin_Settings constructor.
		 */
		public function __construct() {
			//init settings structure
			add_action( 'admin_init', array( &$this, 'init_variables' ), 9 );

			//settings structure handlers
			add_action( 'um_settings_page_before_email__content', array( $this, 'settings_before_email_tab' ) );
			add_filter( 'um_settings_section_email__content', array( $this, 'settings_email_tab' ), 10, 1 );

			//enqueue wp_media for profiles tab
			add_action( 'um_settings_page_appearance__before_section', array( $this, 'settings_appearance_profile_tab' ) );

			add_filter( 'um_settings_structure', array( $this, 'sorting_licenses_options' ), 9999, 1 );



			//save handlers
			add_action( 'admin_init', array( $this, 'save_settings_handler' ), 10 );

			//save pages options
			add_action( 'um_settings_before_save', array( $this, 'check_permalinks_changes' ) );
			add_action( 'um_settings_save', array( $this, 'on_settings_save' ) );

			add_filter( 'um_change_settings_before_save', array( $this, 'set_default_if_empty' ), 9, 1 );
			add_filter( 'um_change_settings_before_save', array( $this, 'remove_empty_values' ), 10, 1 );
			add_filter( 'um_change_settings_before_save', array( $this, 'save_email_templates' ) );

			add_filter( 'um_settings_custom_subtabs', array( $this, 'settings_custom_subtabs' ), 20, 2 );
			add_filter( 'um_settings_section_modules__content', array( $this, 'settings_modules_section' ), 20, 2 );
		}


		/**
		 * Filter: Set 'uexport' and 'uimport' tabs as pages with custom content
		 * @hook um_settings_custom_subtabs
		 * @param array $tabs
		 * @return array
		 */
		public function settings_custom_subtabs( $subtabs, $tab ) {
			if ( 'modules' === $tab ) {
				$subtabs = array_merge( $subtabs, array( '' ) );
			}
			return $subtabs;
		}


		/**
		 * Filter: Print Export page content
		 * @param string $settings_section
		 * @param string $section_fields
		 */
		public function settings_modules_section( $settings_section, $section_fields ) {
			include_once UM_PATH . 'includes/admin/core/list-tables/modules-list-table.php';
		}


		/**
		 *
		 */
		public function init_variables() {
			global $wpdb;

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
						$title = get_the_title( $opt_value );
						$title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;
						$title = sprintf( __( '%s (ID: %s)', 'ultimate-member' ), $title, $opt_value );

						$options    = array( $opt_value => $title );
						$page_value = $opt_value;
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

			$post_types_options = array();
			$all_post_types     = get_post_types( array( 'public' => true ), 'objects' );
			foreach ( $all_post_types as $key => $post_type_data ) {
				$post_types_options[ $key ] = $post_type_data->labels->singular_name;
			}

			$duplicates         = array();
			$taxonomies_options = array();
			$exclude_taxonomies = UM()->common()->access()->excluded_taxonomies();
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
					'id'          => 'accessible',
					'type'        => 'select',
					'label'       => __( 'Global Site Access', 'ultimate-member' ),
					'description' => __( 'This setting is applied site-wide. You can override this setting for individual posts/pages/CPTs by enabling Content Restriction for posts/pages/CPTs below and then editing an individual post/page/CPT and applying content restriction settings to that specific post/page/CPT.', 'ultimate-member' ),
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
					'description' => __( 'A logged out user will be redirected to this url If he is not permitted to access the site', 'ultimate-member' ),
					'conditional' => array( 'accessible', '=', 2 ),
				),
				array(
					'id'                  => 'access_exclude_uris',
					'type'                => 'multi_text',
					'label'               => __( 'Exclude the following URLs', 'ultimate-member' ),
					'description'         => __( 'Here you can exclude URLs beside the redirect URI to be accessible to everyone', 'ultimate-member' ),
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
					'id'          => 'restricted_post_title_replace',
					'type'        => 'checkbox',
					'label'       => __( 'Restricted Content Titles', 'ultimate-member' ),
					'description' => __( 'If enabled, the text entered below will replace the title of the post/page/CPT for users who do not have permission to view the restricted content. Please see this <a href="https://docs.ultimatemember.com/article/1736-content-restriction" target="_blank">doc</a> for more information on this.', 'ultimate-member' ),
				),
				array(
					'id'          => 'restricted_access_post_title',
					'type'        => 'text',
					'label'       => __( 'Restricted Content Title Text', 'ultimate-member' ),
					'description' => __( 'If enabled, the text entered below will replace the title of the post/page/CPT for users who do not have permission to view the restricted content. Please see this <a href="https://docs.ultimatemember.com/article/1736-content-restriction" target="_blank">doc</a> for more information on this.', 'ultimate-member' ),
					'conditional' => array( 'restricted_post_title_replace', '=', 1 ),
				),
				array(
					'id'          => 'restricted_access_message',
					'type'        => 'wp_editor',
					'label'       => __( 'Restricted Access Message', 'ultimate-member' ),
					'description' => __( 'This is the message shown to users that do not have permission to view the content', 'ultimate-member' ),
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
						'description' => __( 'This is the message shown to users that do not have permission to view the block\'s content', 'ultimate-member' ),
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
						'description' => __( 'Check post types for which you plan to use the "Content Restriction" settings', 'ultimate-member' ),
						'options'     => $post_types_options,
						'columns'     => 3,
						'value'       => $restricted_access_post_metabox_value,
						'default'     => UM()->options()->get_default( 'restricted_access_post_metabox' ),
					),
					array(
						'id'          => 'restricted_access_taxonomy_metabox',
						'type'        => 'multi_checkbox',
						'label'       => __( 'Enable the "Content Restriction" settings for taxonomies', 'ultimate-member' ),
						'description' => __( 'Check taxonomies for which you plan to use the "Content Restriction" settings', 'ultimate-member' ),
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
					'delete_account_password_requires'      => array(
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
					'enable_version_3_design'               => array(
						'sanitize' => 'bool',
					),
					'default_avatar'                        => array(
						'sanitize' => 'url',
					),
					'default_cover'                         => array(
						'sanitize' => 'url',
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
					'disable_restriction_pre_queries'       => array(
						'sanitize' => 'bool',
					),
					'uninstall_on_delete'                   => array(
						'sanitize' => 'bool',
					),
				)
			);

			foreach ( array_keys( UM()->config()->get( 'email_notifications' ) ) as $email_key ) {
				$settings_map[ $email_key . '_on' ] = array(
					'sanitize' => 'bool',
				);
				$settings_map[ $email_key . '_sub' ] = array(
					'sanitize' => 'text',
				);
				$settings_map[ $email_key ] = array(
					'sanitize' => 'wp_kses',
				);
			}

			$settings_map['pages_settings'] = array(
				'sanitize' => 'bool',
			);

			$settings_map['um_email_template'] = array(
				'sanitize' => 'key',
			);

			$this->settings_map = apply_filters( 'um_settings_map', $settings_map );

			$misc_fields = array(
				array(
					'id'    => 'form_asterisk',
					'type'  => 'checkbox',
					'label' => __( 'Show an asterisk for required fields', 'ultimate-member' ),
				),
				array(
					'id'          => 'profile_title',
					'type'        => 'text',
					'label'       => __( 'User Profile Title', 'ultimate-member' ),
					'description' => __( 'This is the title that is displayed on a specific user profile', 'ultimate-member' ),
					'size'        => 'medium',
				),
				array(
					'id'          => 'profile_desc',
					'type'        => 'textarea',
					'label'       => __( 'User Profile Dynamic Meta Description', 'ultimate-member' ),
					'description' => __( 'This will be used in the meta description that is available for search-engines.', 'ultimate-member' ),
					'args'        => array(
						'textarea_rows' => 6,
					),
				),
				array(
					'id'          => 'um_profile_object_cache_stop',
					'type'        => 'checkbox',
					'label'       => __( 'Disable Cache User Profile', 'ultimate-member' ),
					'description' => __( 'Check this box if you would like to disable Ultimate Member user\'s cache.', 'ultimate-member' ),
				),
				array(
					'id'          => 'enable_blocks',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Gutenberg Blocks', 'ultimate-member' ),
					'description' => __( 'Check this box if you would like to use Ultimate Member blocks in Gutenberg editor. Important some themes have the conflicts with Gutenberg editor.', 'ultimate-member' ),
				),
				// backward compatibility option leave it disabled for better security and ability to exclude posts/terms pre-query
				// otherwise we filtering only results and restricted posts/terms can be visible
				array(
					'id'          => 'disable_restriction_pre_queries',
					'type'        => 'checkbox',
					'label'       => __( 'Disable pre-queries for restriction content logic (advanced)', 'ultimate-member' ),
					'description' => __( 'Please enable this option only in the cases when you have big or unnecessary queries on your site with active restriction logic. If you want to exclude posts only from the results queries instead of pre_get_posts and fully-hidden post logic also please enable this option. It activates the restriction content logic until 2.2.x version without latest security enhancements', 'ultimate-member' ),
				),
				array(
					'id'          => 'uninstall_on_delete',
					'type'        => 'checkbox',
					'label'       => __( 'Remove Data on Uninstall?', 'ultimate-member' ),
					'description' => __( 'Check this box if you would like Ultimate Member to completely remove all of its data when the plugin/extensions are deleted.', 'ultimate-member' ),
				),
			);

			$is_legacy = get_option( 'um_is_legacy' );
			if ( $is_legacy ) {
				$disabled_designs = array();

				$extension_plugins = UM()->config()->get( 'extension_plugins' );
				$active_plugins    = (array) get_option( 'active_plugins', array() );
				if ( is_multisite() ) {
					$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
				}

				// disable option for v3 designs while some of the old extensions are active
				$active_extension_plugins = array_intersect( $active_plugins, $extension_plugins );
				if ( ! empty( $active_extension_plugins ) ) {
					$disabled_designs[] = 1;
				}

				// disable option for legacy designs while some of the modules are active
				if ( ! UM()->is_legacy ) {
					$modules = UM()->modules()->get_list();
					if ( ! empty( $modules ) ) {
						foreach ( $modules as $slug => $data ) {
							if ( UM()->modules()->is_active( $slug ) ) {
								$disabled_designs[] = 0;
								break;
							}
						}
					}
				}

				$misc_fields = array_merge(
					array(
						array(
							'id'               => 'enable_version_3_design',
							'type'             => 'select',
							'label'            => __( 'Version', 'ultimate-member' ),
							'description'      => __( 'This is the version of Ultimate Member you are using.', 'ultimate-member' ),
							'options'          => array(
								0 => __( 'Version 1.x - 2.x design (Legacy)', 'ultimate-member' ),
								1 => __( 'Version 3', 'ultimate-member' ),
							),
							'disabled_options' => $disabled_designs,
							'size'             => 'small',
						),
					),
					$misc_fields
				);
			}

			$users_fields = array(
				array(
					'id'          => 'permalink_base',
					'type'        => 'select',
					'size'        => 'small',
					'label'       => __( 'Profile Permalink Base', 'ultimate-member' ),
					// translators: %s: Profile page URL
					'description' => sprintf( __( 'Select what permalink structure to use for user profile URL globally e.g. %s<strong>username</strong>/.', 'ultimate-member' ), trailingslashit( um_get_predefined_page_url( 'user' ) ) ),
					'options'     => array(
						'user_login' => __( 'Username', 'ultimate-member' ),
						'name'       => __( 'First and Last Name with \'.\'', 'ultimate-member' ),
						'name_dash'  => __( 'First and Last Name with \'-\'', 'ultimate-member' ),
						'name_plus'  => __( 'First and Last Name with \'+\'', 'ultimate-member' ),
						'user_id'    => __( 'User ID', 'ultimate-member' ),
					),
				),
				array(
					'id'          => 'display_name',
					'type'        => 'select',
					'size'        => 'medium',
					'label'       => __( 'User Display Name', 'ultimate-member' ),
					'description' => __( 'This is the name that will be displayed for users on the front end of your site. Default setting uses first/last name as display name if it exists.', 'ultimate-member' ),
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
				),
				array(
					'id'          => 'display_name_field',
					'type'        => 'text',
					'label'       => __( 'Display Name Custom Field(s)', 'ultimate-member' ),
					'description' => __( 'Specify the custom field meta key or custom fields seperated by comma that you want to use to display users name on the frontend of your site.', 'ultimate-member' ),
					'conditional' => array( 'display_name', '=', 'field' ),
				),
				array(
					'id'          => 'author_redirect',
					'type'        => 'checkbox',
					'label'       => __( 'Automatically redirect author page to their profile?', 'ultimate-member' ),
					'description' => __( 'If enabled, author pages will automatically redirect to the user\'s profile page.', 'ultimate-member' ),
				),
				array(
					'id'                 => 'default_avatar',
					'type'               => 'media',
					'label'              => __( 'Default Profile Photo', 'ultimate-member' ),
					'description'        => __( 'You can change the default profile picture globally here. Please make sure that the photo is 300x300px.', 'ultimate-member' ),
					'upload_frame_title' => __( 'Select Default Profile Photo', 'ultimate-member' ),
					'default'            => array(
						'url' => UM_URL . 'assets/img/default_avatar.jpg',
					),
				),
				array(
					'id'                 => 'default_cover',
					'type'               => 'media',
					'url'                => true,
					'preview'            => false,
					'label'              => __( 'Default Cover Photo', 'ultimate-member' ),
					'description'        => __( 'You can change the default cover photo globally here. Please make sure that the default cover is large enough and respects the ratio you are using for cover photos.', 'ultimate-member' ),
					'upload_frame_title' => __( 'Select Default Cover Photo', 'ultimate-member' ),
				),
				array(
					'id'          => 'use_gravatars',
					'type'        => 'checkbox',
					'label'       => __( 'Use Gravatars', 'ultimate-member' ),
					'description' => __( 'Enable this option if you want to use gravatars instead of the default plugin profile photo (If the user did not upload a custom profile photo/avatar).', 'ultimate-member' ),
				),
				array(
					'id'          => 'use_um_gravatar_default_builtin_image',
					'type'        => 'select',
					'label'       => __( 'Use Gravatar builtin image', 'ultimate-member' ),
					'description' => __( 'Gravatar has a number of built in options which you can also use as defaults.', 'ultimate-member' ),
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
					'description' => __( 'Do you want to use the plugin default avatar instead of the gravatar default photo (If the user did not upload a custom profile photo / avatar).', 'ultimate-member' ),
					'conditional' => array( 'use_um_gravatar_default_builtin_image', '=', 'default' ),
				),
				array(
					'id'          => 'require_strongpass',
					'type'        => 'checkbox',
					'label'       => __( 'Require Strong Passwords', 'ultimate-member' ),
					'description' => __( 'Enable this option to apply strong password rules to all password fields (user registration, password reset and password change).', 'ultimate-member' ),
				),
				array(
					'id'          => 'password_min_chars',
					'type'        => 'number',
					'label'       => __( 'Password minimum length', 'ultimate-member' ),
					'description' => __( 'Enter the minimum number of characters a user must use for their password. The default minimum characters is 8.', 'ultimate-member' ),
					'size'        => 'small',
					'conditional' => array( 'require_strongpass', '=', '1' ),
				),
				array(
					'id'          => 'password_max_chars',
					'type'        => 'number',
					'label'       => __( 'Password maximum length', 'ultimate-member' ),
					'description' => __( 'Enter the maximum number of characters a user can use for their password. The default maximum characters is 30.', 'ultimate-member' ),
					'size'        => 'small',
					'conditional' => array( 'require_strongpass', '=', '1' ),
				),
				array(
					'id'          => 'profile_noindex',
					'type'        => 'select',
					'size'        => 'small',
					'label'       => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'description' => __( 'Hides the profile page for robots. This setting can be overridden by individual role settings.', 'ultimate-member' ),
					'options'     => array(
						0 => __( 'No', 'ultimate-member' ),
						1 => __( 'Yes', 'ultimate-member' ),
					),
				),
				array(
					'id'          => 'activation_link_expiry_time',
					'type'        => 'number',
					'label'       => __( 'Email activation link expiration (days)', 'ultimate-member' ),
					'description' => __( 'For user registrations that require an email link to be clicked to confirm account. How long would you like the activation link to be active for before it expires? If this field is left blank the activation link will not expire.', 'ultimate-member' ),
					'size'        => 'small',
				),
			);

			$cache_count = $wpdb->get_var(
				"SELECT COUNT( option_id ) 
				FROM {$wpdb->options} 
				WHERE option_name LIKE 'um_cache_userdata_%'"
			);

			if ( $cache_count ) {
				$users_fields[] = array(
					'id'          => 'purge_users_cache',
					'type'        => 'ajax_button',
					'label'       => __( 'User Cache', 'ultimate-member' ),
					'value'       => sprintf( __( 'Clear cache of %s users', 'ultimate-member' ), $cache_count ),
					'description' => __( 'Run this task from time to time to keep your DB clean.', 'ultimate-member' ) ,
					'size'        => 'small',
				);
			}

			$uploads_fields = array(
				array(
					'id'          => 'profile_photo_max_size',
					'type'        => 'text',
					'size'        => 'small',
					'label'       => __( 'Profile Photo Maximum File Size (bytes)', 'ultimate-member' ),
					'description' => __( 'Sets a maximum size for the uploaded photo', 'ultimate-member' ),
				),
				array(
					'id'          => 'cover_min_width',
					'type'        => 'text',
					'size'        => 'small',
					'label'       => __( 'Cover Photo Minimum Width (px)', 'ultimate-member' ),
					'description' => __( 'This will be the minimum width for cover photo uploads', 'ultimate-member' ),
				),
				array(
					'id'          => 'cover_photo_max_size',
					'type'        => 'text',
					'size'        => 'small',
					'label'       => __( 'Cover Photo Maximum File Size (bytes)', 'ultimate-member' ),
					'description' => __( 'Sets a maximum size for the uploaded cover', 'ultimate-member' ),
				),
				array(
					'id'                  => 'photo_thumb_sizes',
					'type'                => 'multi_text',
					'size'                => 'small',
					'label'               => __( 'Profile Photo Thumbnail Sizes (px)', 'ultimate-member' ),
					'description'         => __( 'Here you can define which thumbnail sizes will be created for each profile photo upload.', 'ultimate-member' ),
					'validate'            => 'numeric',
					'add_text'            => __( 'Add New Size', 'ultimate-member' ),
					'show_default_number' => 1,
				),
				array(
					'id'                  => 'cover_thumb_sizes',
					'type'                => 'multi_text',
					'size'                => 'small',
					'label'               => __( 'Cover Photo Thumbnail Sizes (px)', 'ultimate-member' ),
					'description'         => __( 'Here you can define which thumbnail sizes will be created for each cover photo upload.', 'ultimate-member' ),
					'validate'            => 'numeric',
					'add_text'            => __( 'Add New Size', 'ultimate-member' ),
					'show_default_number' => 1,
				),
				array(
					'id'          => 'image_orientation_by_exif',
					'type'        => 'checkbox',
					'label'       => __( 'Change image orientation', 'ultimate-member' ),
					'description' => __( 'Rotate image to and use orientation by the camera EXIF data.', 'ultimate-member' ),
				),
				array(
					'id'          => 'image_compression',
					'type'        => 'text',
					'size'        => 'small',
					'label'       => __( 'Image Quality', 'ultimate-member' ),
					'description' => __( 'Quality is used to determine quality of image uploads, and ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file). The default range is 60.', 'ultimate-member' ),
				),
				array(
					'id'          => 'image_max_width',
					'type'        => 'text',
					'size'        => 'small',
					'label'       => __( 'Image Upload Maximum Width (px)', 'ultimate-member' ),
					'description' => __( 'Any image upload above this width will be resized to this limit automatically.', 'ultimate-member' ),
				),
				array(
					'id'          => 'profile_photosize',
					'type'        => 'select',
					'label'       => __( 'Profile Photo Size', 'ultimate-member' ),
					'default'     => um_get_metadefault( 'profile_photosize' ),
					'options'     => UM()->files()->get_profile_photo_size( 'photo_thumb_sizes' ),
					'description' => __( 'The global default of profile photo size', 'ultimate-member' ),
					'size'        => 'small',
				),
				array(
					'id'          => 'profile_coversize',
					'type'        => 'select',
					'label'       => __( 'Profile Cover Size', 'ultimate-member' ),
					'default'     => um_get_metadefault( 'profile_coversize' ),
					'options'     => UM()->files()->get_profile_photo_size( 'cover_thumb_sizes' ),
					'description' => __( 'The global default width of cover photo size', 'ultimate-member' ),
					'size'        => 'small',
				),
				array(
					'id'          => 'profile_cover_ratio',
					'type'        => 'select',
					'label'       => __( 'Profile Cover Ratio', 'ultimate-member' ),
					'description' => __( 'Choose global ratio for cover photos of profiles', 'ultimate-member' ),
					'default'     => um_get_metadefault( 'profile_cover_ratio' ),
					'options'     => array(
						'1.6:1' => '1.6:1',
						'2.7:1' => '2.7:1',
						'2.2:1' => '2.2:1',
						'3.2:1' => '3.2:1',
					),
					'size'        => 'small',
				),
			);

			$temp_dir_size = UM()->common()->filesystem()->dir_size( 'temp' );
			if ( $temp_dir_size > 0.1 ) {
				$uploads_fields[] = array(
					'id'          => 'purge_temp_files',
					'type'        => 'ajax_button',
					'label'       => __( 'Purge Temp Files', 'ultimate-member' ),
					'value'       => __( 'Purge Temp', 'ultimate-member' ),
					'description' => sprintf( __( 'You can free up %s MB by purging your temp upload directory.', 'ultimate-member' ), $temp_dir_size ),
					'size'        => 'small',
				);
			} else {
				$uploads_fields[] = array(
					'id'    => 'purge_temp_files',
					'type'  => 'info_text',
					'label' => __( 'Purge Temp Files', 'ultimate-member' ),
					'value' => __( 'Your temp uploads directory is clean. There is nothing to purge.', 'ultimate-member' ),
				);
			}

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
								'fields' => $users_fields,
							),
							'account' => array(
								'title'  => __( 'Account', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'          => 'account_tab_password',
										'type'        => 'checkbox',
										'label'       => __( 'Password Account Tab', 'ultimate-member' ),
										'description' => __( 'Enable/disable the Password account tab on the account page.', 'ultimate-member' ),
									),
									array(
										'id'          => 'account_tab_privacy',
										'type'        => 'checkbox',
										'label'       => __( 'Privacy Account Tab', 'ultimate-member' ),
										'description' => __( 'Enable/disable the Privacy account tab on the account page.', 'ultimate-member' ),
									),
									array(
										'id'          => 'account_tab_notifications',
										'type'        => 'checkbox',
										'label'       => __( 'Notifications Account Tab', 'ultimate-member' ),
										'description' => __( 'Enable/disable the Notifications account tab on the account page.', 'ultimate-member' ),
									),
									array(
										'id'          => 'account_tab_delete',
										'type'        => 'checkbox',
										'label'       => __( 'Delete Account Tab', 'ultimate-member' ),
										'description' => __( 'Enable/disable the Delete account tab on the account page.', 'ultimate-member' ),
									),
									array(
										'id'          => 'delete_account_password_requires',
										'type'        => 'checkbox',
										'label'       => __( 'Account deletion password requires', 'ultimate-member' ),
										'description' => __( 'Enable/disable the requirement to enter a password when deleting an account.', 'ultimate-member' ),
										'conditional' => array( 'account_tab_delete', '=', '1' ),
									),
									array(
										'id'          => 'delete_account_text',
										'type'        => 'textarea', // bug with wp 4.4? should be editor
										'label'       => __( 'Account Deletion Text', 'ultimate-member' ),
										'description' => __( 'This is the custom text that will be displayed to users before they delete their account from your website when their password is required to confirm account deletion.', 'ultimate-member' ),
										'args'        => array(
											'textarea_rows' => 6,
										),
										'conditional' => array( 'delete_account_password_requires', '=', '1' ),
									),
									array(
										'id'          => 'delete_account_no_pass_required_text',
										'type'        => 'textarea',
										'label'       => __( 'Account Deletion Text', 'ultimate-member' ),
										'description' => __( 'This is the custom text that will be displayed to users before they delete their account from your website when no password is required to confirm account deletion.', 'ultimate-member' ),
										'args'        => array(
											'textarea_rows' => 6,
										),
										'conditional' => array( 'delete_account_password_requires', '=', '0' ),
									),
									array(
										'id'          => 'account_name',
										'type'        => 'checkbox',
										'label'       => __( 'Display First & Last name fields', 'ultimate-member' ),
										'description' => __( 'If enabled, the First & Last name fields will be shown on the account page.', 'ultimate-member' ),
									),
									array(
										'id'          => 'account_name_disable',
										'type'        => 'checkbox',
										'label'       => __( 'Disable First & Last name field editing', 'ultimate-member' ),
										'description' => __( 'If enabled, this will prevent users from changing their First & Last name fields on the account page.', 'ultimate-member' ),
										'conditional' => array( 'account_name', '=', '1' ),
									),
									array(
										'id'          => 'account_name_require',
										'type'        => 'checkbox',
										'label'       => __( 'Require First & Last Name', 'ultimate-member' ),
										'description' => __( 'If enabled, users will not be allowed to remove their first or last names when updating their account page.', 'ultimate-member' ),
										'conditional' => array( 'account_name', '=', '1' ),
									),
									array(
										'id'          => 'account_email',
										'type'        => 'checkbox',
										'label'       => __( 'Allow users to change email', 'ultimate-member' ),
										'description' => __( 'If disabled, users will not be allowed to change their email address on the account page.', 'ultimate-member' ),
									),
									array(
										'id'          => 'account_general_password',
										'type'        => 'checkbox',
										'label'       => __( 'Require password to update account', 'ultimate-member' ),
										'description' => __( 'If enabled, users will need to enter their password when updating their information via the account page.', 'ultimate-member' ),
									),
								),
							),
							'uploads' => array(
								'title'  => __( 'Uploads', 'ultimate-member' ),
								'fields' => $uploads_fields,
							),
						),
					),
					'access'       => array(
						'title'    => __( 'Access', 'ultimate-member' ),
						'sections' => array(
							''      => array(
								'title'  => __( 'Content Restriction', 'ultimate-member' ),
								'fields' => $access_fields,
							),
							'other' => array(
								'title'  => __( 'Other', 'ultimate-member' ),
								'fields' => array(
									array(
										'id'          => 'enable_reset_password_limit',
										'type'        => 'checkbox',
										'label'       => __( 'Password reset limit', 'ultimate-member' ),
										'description' => __( 'If enabled, this sets a limit on the number of password resets a user can do.', 'ultimate-member' ),
									),
									array(
										'id'          => 'reset_password_limit_number',
										'type'        => 'text',
										'label'       => __( 'Enter password reset limit', 'ultimate-member' ),
										'description' => __( 'Set the maximum reset password limit. If reached the maximum limit, user will be locked from using this.', 'ultimate-member' ),
										'validate'    => 'numeric',
										'conditional' => array( 'enable_reset_password_limit', '=', 1 ),
										'size'        => 'small',
									),
									array(
										'id'          => 'blocked_emails',
										'type'        => 'textarea',
										'label'       => __( 'Blocked Email Addresses', 'ultimate-member' ),
										'description' => __( 'Please enter one email per line. This will block the specified email addresses from being able to sign up or sign in to your website. To block an entire domain, add an asterix before the <code>@</code> e.g. <code>*@domain.com</code>.', 'ultimate-member' ),
										'args'        => array(
											'textarea_rows' => 10,
										),
									),
									array(
										'id'          => 'blocked_words',
										'type'        => 'textarea',
										'label'       => __( 'Banned Usernames', 'ultimate-member' ),
										'description' => __( 'This option lets you specify words that will be blocked when a user tries to register using one of these words.', 'ultimate-member' ),
										'args'        => array(
											'textarea_rows' => 10,
										),
									),
								),
							),
						),
					),
					'email'        => array(
						'title'  => __( 'Email', 'ultimate-member' ),
						'fields' => array(
							array(
								'id'          => 'admin_email',
								'type'        => 'text',
								'label'       => __( 'Admin E-mail Address', 'ultimate-member' ),
								'description' => __( 'e.g. admin@companyname.com', 'ultimate-member' ),
							),
							array(
								'id'          => 'mail_from',
								'type'        => 'text',
								'label'       => __( 'Mail appears from', 'ultimate-member' ),
								'description' => __( 'e.g. Site Name', 'ultimate-member' ),
							),
							array(
								'id'          => 'mail_from_addr',
								'type'        => 'text',
								'label'       => __( 'Mail appears from address', 'ultimate-member' ),
								'description' => __( 'e.g. admin@companyname.com', 'ultimate-member' ),
							),
							array(
								'id'          => 'email_html',
								'type'        => 'checkbox',
								'label'       => __( 'Use HTML for E-mails?', 'ultimate-member' ),
								'description' => __( 'If you plan use e-mails with HTML, please make sure that this option is enabled. Otherwise, HTML will be displayed as plain text.', 'ultimate-member' ),
							),
						),
					),
					'modules'      => array(
						'title' => __( 'Modules', 'ultimate-member' ),
					),
					'licenses'     => array(
						'title' => __( 'Licenses', 'ultimate-member' ),
					),
					'misc'         => array(
						'title'  => __( 'Misc', 'ultimate-member' ),
						'fields' => $misc_fields,
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
			// sorting licenses
			if ( ! empty( $settings['licenses']['fields'] ) ) {
				$licenses = $settings['licenses']['fields'];
				@uasort( $licenses, function( $a, $b ) {
					return strnatcasecmp( $a['label'], $b['label'] );
				} );
				$settings['licenses']['fields'] = $licenses;
			}

			// sorting modules by the title
			if ( ! empty( $settings['modules']['sections'] ) ) {
				$modules = $settings['modules']['sections'];

				@uasort( $modules, function( $a, $b ) {
					return strnatcasecmp( $a['title'], $b['title'] );
				} );

				$modules = array(
					'' => array(
						'title'  => __( 'Modules', 'ultimate-member' ),
					)
				) + $modules;

				$settings['modules']['sections'] = $modules;
			} else {
				$modules = array(
					'' => array(
						'title'  => __( 'Modules', 'ultimate-member' ),
					)
				);

				$settings['modules']['sections'] = $modules;
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
		 * Settings page callback
		 */
		function settings_page() {
			$current_tab    = empty( $_GET['tab'] ) ? '' : sanitize_key( $_GET['tab'] );
			$current_subtab = empty( $_GET['section'] ) ? '' : sanitize_key( $_GET['section'] );

			echo '<div id="um-settings-wrap" class="wrap"><h2>' .  esc_html__( 'Ultimate Member - Settings', 'ultimate-member' ) . '</h2>';

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

			if ( in_array( $current_subtab, apply_filters( 'um_settings_custom_subtabs', array(), $current_tab ) ) ) {

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
						$tabs .= '<a href="' . esc_url( admin_url( 'admin.php?page=ultimatemember' . ( empty( $name ) ? '' : '&tab=' . $name ) ) ) . '" class="nav-tab ' . esc_attr( $active ) . '">' . $label . '</a>';
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
				$subtabs .= '<a href="' . esc_url( admin_url( 'admin.php?page=ultimatemember' . ( empty( $current_tab ) ? '' : '&tab=' . $current_tab ) . ( empty( $name ) ? '' : '&section=' . $name ) ) ) . '" class="' . $active . '">' . $label . '</a> | ';
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
				do_action( 'um_settings_before_save' );

				$settings = UM()->admin()->sanitize_options( $_POST['um_options'] );

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
				$settings = apply_filters( 'um_change_settings_before_save', $settings );

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
					'page'   => 'ultimatemember',
					'update' => 'settings_updated',
				);

				if ( ! empty( $_GET['tab'] ) ) {
					$arg['tab'] = sanitize_key( $_GET['tab'] );
				}

				if ( ! empty( $_GET['section'] ) ) {
					$arg['section'] = sanitize_key( $_GET['section'] );
				}

				wp_redirect( add_query_arg( $arg, admin_url( 'admin.php' ) ) );
				exit;
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
		}


		/**
		 *
		 */
		function on_settings_save() {
			if ( ! empty( $_POST['um_options'] ) ) {
				if ( ! empty( $_POST['um_options']['pages_settings'] ) ) {
					$post_ids = new \WP_Query( array(
						'post_type'      => 'page',
						'meta_query'     => array(
							array(
								'key'     => '_um_core',
								'compare' => 'EXISTS',
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

					foreach ( $_POST['um_options'] as $option_slug => $post_id ) {
						$slug = str_replace( 'core_', '', sanitize_key( $option_slug ) );
						update_post_meta( absint( $post_id ), '_um_core', $slug );
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
				}
			}
		}


		/**
		 *
		 */
		function settings_before_email_tab() {
			$email_key = empty( $_GET['email'] ) ? '' : sanitize_key( $_GET['email'] );
			$emails = UM()->config()->get( 'email_notifications' );

			if ( empty( $email_key ) || empty( $emails[ $email_key ] ) ) {
				include_once UM_PATH . 'includes/admin/core/list-tables/emails-list-table.php';
			}
		}


		/**
		 * @param $section
		 *
		 * @return string
		 */
		function settings_email_tab( $section ) {
			$email_key = empty( $_GET['email'] ) ? '' : sanitize_key( $_GET['email'] );
			$emails = UM()->config()->get( 'email_notifications' );

			if ( empty( $email_key ) || empty( $emails[ $email_key ] ) ) {
				return $section;
			}

			// Avoid the request to the wrong directory with email templates. Getting module email templates inside module
			$populate_custom_template = apply_filters( 'um_admin_settings_email_template_content', false, $email_key );
			if ( false === $populate_custom_template ) {
				// then get built-in core template not in the module
				$email_content = UM()->options()->get( 'email_html' ) ? um_get_template_html( "emails/{$email_key}.php" ) : nl2br( um_get_template_html( "emails/plain/{$email_key}.php" ) );
			} else {
				$email_content = $populate_custom_template;
			}

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
			$section_fields = apply_filters(
				'um_admin_settings_email_section_fields',
				array(
					array(
						'id'    => 'um_email_template',
						'type'  => 'hidden',
						'value' => $email_key,
					),
					array(
						'id'          => $email_key . '_on',
						'type'        => 'checkbox',
						'label'       => $emails[ $email_key ]['title'],
						'description' => $emails[ $email_key ]['description'],
					),
					array(
						'id'          => $email_key . '_sub',
						'type'        => 'text',
						'label'       => __( 'Subject Line', 'ultimate-member' ),
						'conditional' => array( $email_key . '_on', '=', 1 ),
						'description' => __( 'This is the subject line of the e-mail', 'ultimate-member' ),
					),
					array(
						'id'          => $email_key,
						'type'        => 'email_template',
						'label'       => __( 'Message Body', 'ultimate-member' ),
						'conditional' => array( $email_key . '_on', '=', 1 ),
						'description' => __( 'This is the content of the e-mail', 'ultimate-member' ),
						'value'       => $email_content,
					),
				),
				$email_key
			);

			return $this->render_settings_section( $section_fields, 'email', $email_key );
		}


		/**
		 *
		 */
		function settings_appearance_profile_tab() {
			wp_enqueue_media();
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

			$email_key = $settings['um_email_template'];
			$content   = stripslashes( $settings[ $email_key ] );

			$template_name = um_get_email_template( $email_key );
			$module = um_get_email_template_module( $email_key );

			$template_path = UM()->template_path( $module );

			$template_locations = array(
				trailingslashit( $template_path ) . $template_name,
			);

			$template_locations = apply_filters( 'um_pre_template_locations', $template_locations, $template_name, $module, $template_path );

			// build multisite blog_ids priority paths
			if ( is_multisite() ) {
				$blog_id = get_current_blog_id();

				$ms_template_locations = array_map( function( $item ) use ( $template_path, $blog_id ) {
					return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $blog_id . '/', $item );
				}, $template_locations );

				$template_locations = array_merge( $ms_template_locations, $template_locations );
			}

			$template_locations = apply_filters( 'um_template_locations', $template_locations, $template_name, $module, $template_path );

			$template_locations = array_map( 'wp_normalize_path', $template_locations );

			$template_locations = apply_filters( 'um_save_email_templates_locations', $template_locations, $template_name, $module, $template_path );

			$custom_path = apply_filters( 'um_template_structure_custom_path', false, $template_name, $module );
			if ( false === $custom_path || ! is_dir( $custom_path ) ) {
				$template_exists = locate_template( $template_locations );
			} else {
				$template_exists = um_locate_template_custom_path( $template_locations, $custom_path );
			}

			if ( empty( $template_exists ) ) {
				if ( false === $custom_path || ! is_dir( $custom_path ) ) {
					$base_dir = trailingslashit( get_stylesheet_directory() );
				} else {
					$base_dir = trailingslashit( $custom_path );
				}
				$template_exists = $base_dir . $template_locations[0];

				$template_exists       = wp_normalize_path( $template_exists );
				$default_template_path = wp_normalize_path( trailingslashit( UM()->default_templates_path( $module ) ) . $template_name );

				if ( file_exists( $default_template_path ) ) {
					$folders = explode( DIRECTORY_SEPARATOR, $template_locations[0] );
					$folders = array_splice( $folders, 0, count( $folders ) - 1 );
					$cur_folder = '';

					foreach ( $folders as $folder ) {
						$prev_dir = $cur_folder;
						$cur_folder .= $folder . DIRECTORY_SEPARATOR;
						if ( ! is_dir( $base_dir . $cur_folder ) && wp_is_writable( $base_dir . $prev_dir ) ) {
							mkdir( $base_dir . $cur_folder, 0777 );
						}
					}

					copy( $default_template_path, $template_exists );
				}
			}

			if ( wp_is_writable( $template_exists ) ) {
				$fp = fopen( $template_exists, "w" );
				fputs( $fp, $content );
				fclose( $fp );
			}

			unset( $settings['um_email_template'] );
			unset( $settings[ $email_key ] );

			return $settings;
		}
	}
}
