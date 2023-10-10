<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Admin' ) ) {

	/**
	 * Class Admin
	 * @package um\admin
	 */
	class Admin extends Admin_Functions {

		/**
		 * @var string
		 */
		public $templates_path;


		/**
		 * @var array
		 */
		public $role_meta;


		/**
		 * @var array
		 */
		public $restriction_term_meta;


		/**
		 * @var array
		 */
		public $member_directory_meta;


		/**
		 * @var array
		 */
		public $form_meta;


		/**
		 * @var array
		 */
		public $builder_input;


		/**
		 * @var array
		 */
		public $restriction_post_meta;


		/**
		 * Admin constructor.
		 */
		public function __construct() {
			parent::__construct();

			$this->templates_path = UM_PATH . 'includes/admin/templates/';

			add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );

			$prefix = is_network_admin() ? 'network_admin_' : '';
			add_filter( "{$prefix}plugin_action_links_" . UM_PLUGIN, array( &$this, 'plugin_links' ) );

			add_action( 'um_admin_do_action__user_cache', array( &$this, 'user_cache' ) );
			add_action( 'um_admin_do_action__user_status_cache', array( &$this, 'user_status_cache' ) );
			add_action( 'um_admin_do_action__purge_temp', array( &$this, 'purge_temp' ) );
			add_action( 'um_admin_do_action__manual_upgrades_request', array( &$this, 'manual_upgrades_request' ) );
			add_action( 'um_admin_do_action__duplicate_form', array( &$this, 'duplicate_form' ) );
			add_action( 'um_admin_do_action__user_action', array( &$this, 'user_action' ) );
			add_action( 'um_admin_do_action__check_templates_version', array( &$this, 'check_templates_version' ) );

			add_action( 'um_admin_do_action__install_core_pages', array( &$this, 'install_core_pages' ) );

			add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ), 999 );

			add_action( 'parent_file', array( &$this, 'parent_file' ), 9 );
			add_filter( 'gettext', array( &$this, 'gettext' ), 10, 4 );
			add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		}

		public function includes() {
			$this->enqueue();
			$this->notices();
			$this->secure();
			$this->site_health();
		}

		function init_variables() {
			$this->role_meta = apply_filters(
				'um_role_meta_map',
				array(
					'_um_priority'                   => array(
						'sanitize' => 'int',
					),
					'_um_can_access_wpadmin'         => array(
						'sanitize' => 'bool',
					),
					'_um_can_not_see_adminbar'       => array(
						'sanitize' => 'bool',
					),
					'_um_can_edit_everyone'          => array(
						'sanitize' => 'bool',
					),
					'_um_can_edit_roles'             => array(
						'sanitize' => array( $this, 'sanitize_existed_role' ),
					),
					'_um_can_delete_everyone'        => array(
						'sanitize' => 'bool',
					),
					'_um_can_delete_roles'           => array(
						'sanitize' => array( $this, 'sanitize_existed_role' ),
					),
					'_um_can_edit_profile'           => array(
						'sanitize' => 'bool',
					),
					'_um_can_delete_profile'         => array(
						'sanitize' => 'bool',
					),
					'_um_can_view_all'               => array(
						'sanitize' => 'bool',
					),
					'_um_can_view_roles'             => array(
						'sanitize' => array( $this, 'sanitize_existed_role' ),
					),
					'_um_can_make_private_profile'   => array(
						'sanitize' => 'bool',
					),
					'_um_can_access_private_profile' => array(
						'sanitize' => 'bool',
					),
					'_um_profile_noindex'            => array(
						'sanitize' => array( $this, 'sanitize_profile_noindex' ),
					),
					'_um_default_homepage'           => array(
						'sanitize' => 'bool',
					),
					'_um_redirect_homepage'          => array(
						'sanitize' => 'url',
					),
					'_um_status'                     => array(
						'sanitize' => 'sanitize_array_key',
						'default'  => 'approved',
						'array'    => array( 'approved', 'checkmail', 'pending' ),
					),
					'_um_auto_approve_act'           => array(
						'sanitize' => 'sanitize_array_key',
						'default'  => 'redirect_profile',
						'array'    => array( 'redirect_profile', 'redirect_url' ),
					),
					'_um_auto_approve_url'           => array(
						'sanitize' => 'url',
					),
					'_um_login_email_activate'       => array(
						'sanitize' => 'bool',
					),
					'_um_checkmail_action'           => array(
						'sanitize' => 'sanitize_array_key',
						'default'  => 'show_message',
						'array'    => array( 'show_message', 'redirect_url' ),
					),
					'_um_checkmail_message'          => array(
						'sanitize' => 'textarea',
					),
					'_um_checkmail_url'              => array(
						'sanitize' => 'url',
					),
					'_um_url_email_activate'         => array(
						'sanitize' => 'url',
					),
					'_um_pending_action'             => array(
						'sanitize' => 'sanitize_array_key',
						'default'  => 'show_message',
						'array'    => array( 'show_message', 'redirect_url' ),
					),
					'_um_pending_message'            => array(
						'sanitize' => 'textarea',
					),
					'_um_pending_url'                => array(
						'sanitize' => 'url',
					),
					'_um_after_login'                => array(
						'sanitize' => 'sanitize_array_key',
						'default'  => 'redirect_profile',
						'array'    => array( 'redirect_profile', 'redirect_url', 'refresh', 'redirect_admin' ),
					),
					'_um_login_redirect_url'         => array(
						'sanitize' => 'url',
					),
					'_um_after_logout'               => array(
						'sanitize' => 'sanitize_array_key',
						'default'  => 'redirect_home',
						'array'    => array( 'redirect_home', 'redirect_url' ),
					),
					'_um_logout_redirect_url'        => array(
						'sanitize' => 'url',
					),
					'_um_after_delete'               => array(
						'sanitize' => 'sanitize_array_key',
						'default'  => 'redirect_home',
						'array'    => array( 'redirect_home', 'redirect_url' ),
					),
					'_um_delete_redirect_url'        => array(
						'sanitize' => 'url',
					),
					'wp_capabilities'                => array(
						'sanitize' => array( $this, 'sanitize_wp_capabilities' ),
					),
				)
			);

			$this->restriction_post_meta = apply_filters(
				'um_restriction_post_meta_map',
				array(
					'_um_custom_access_settings'     => array(
						'sanitize' => 'bool',
					),
					'_um_accessible'                 => array(
						'sanitize' => 'int',
					),
					'_um_access_roles'               => array(
						'sanitize' => array( $this, 'sanitize_restriction_existed_role' ),
					),
					'_um_noaccess_action'            => array(
						'sanitize' => 'int',
					),
					'_um_restrict_by_custom_message' => array(
						'sanitize' => 'int',
					),
					'_um_restrict_custom_message'    => array(
						'sanitize' => 'wp_kses',
					),
					'_um_access_redirect'            => array(
						'sanitize' => 'int',
					),
					'_um_access_redirect_url'        => array(
						'sanitize' => 'url',
					),
					'_um_access_hide_from_queries'   => array(
						'sanitize' => 'bool',
					),
				)
			);

			$this->restriction_term_meta = apply_filters(
				'um_restriction_term_meta_map',
				array(
					'_um_custom_access_settings'     => array(
						'sanitize' => 'bool',
					),
					'_um_accessible'                 => array(
						'sanitize' => 'int',
					),
					'_um_access_roles'               => array(
						'sanitize' => array( $this, 'sanitize_restriction_existed_role' ),
					),
					'_um_noaccess_action'            => array(
						'sanitize' => 'int',
					),
					'_um_restrict_by_custom_message' => array(
						'sanitize' => 'int',
					),
					'_um_restrict_custom_message'    => array(
						'sanitize' => 'wp_kses',
					),
					'_um_access_redirect'            => array(
						'sanitize' => 'int',
					),
					'_um_access_redirect_url'        => array(
						'sanitize' => 'url',
					),
					'_um_access_hide_from_queries'   => array(
						'sanitize' => 'bool',
					),
				)
			);

			$this->member_directory_meta = apply_filters(
				'um_member_directory_meta_map',
				array(
					'_um_directory_template'       => array(
						'sanitize' => 'text',
					),
					'_um_mode'                     => array(
						'sanitize' => 'key',
					),
					'_um_view_types'               => array(
						'sanitize' => array( $this, 'sanitize_md_view_types' ),
					),
					'_um_default_view'             => array(
						'sanitize' => 'key',
					),
					'_um_roles'                    => array(
						'sanitize' => array( $this, 'sanitize_restriction_existed_role' ),
					),
					'_um_has_profile_photo'        => array(
						'sanitize' => 'bool',
					),
					'_um_show_these_users'         => array(
						'sanitize' => 'textarea',
					),
					'_um_exclude_these_users'      => array(
						'sanitize' => 'textarea',
					),
					'_um_must_search'              => array(
						'sanitize' => 'bool',
					),
					'_um_max_users'                => array(
						'sanitize' => 'absint',
					),
					'_um_profiles_per_page'        => array(
						'sanitize' => 'absint',
					),
					'_um_profiles_per_page_mobile' => array(
						'sanitize' => 'absint',
					),
					'_um_directory_header'         => array(
						'sanitize' => 'text',
					),
					'_um_directory_header_single'  => array(
						'sanitize' => 'text',
					),
					'_um_directory_no_users'       => array(
						'sanitize' => 'text',
					),
					'_um_profile_photo'            => array(
						'sanitize' => 'bool',
					),
					'_um_cover_photos'             => array(
						'sanitize' => 'bool',
					),
					'_um_show_name'                => array(
						'sanitize' => 'bool',
					),
					'_um_show_tagline'             => array(
						'sanitize' => 'bool',
					),
					'_um_tagline_fields'           => array(
						'sanitize' => array( $this, 'sanitize_user_field' ),
					),
					'_um_show_userinfo'            => array(
						'sanitize' => 'bool',
					),
					'_um_reveal_fields'            => array(
						'sanitize' => array( $this, 'sanitize_user_field' ),
					),
					'_um_show_social'              => array(
						'sanitize' => 'bool',
					),
					'_um_userinfo_animate'         => array(
						'sanitize' => 'bool',
					),
					'_um_search'                   => array(
						'sanitize' => 'bool',
					),
					'_um_roles_can_search'         => array(
						'sanitize' => array( $this, 'sanitize_restriction_existed_role' ),
					),
					'_um_filters'                  => array(
						'sanitize' => 'bool',
					),
					'_um_roles_can_filter'         => array(
						'sanitize' => array( $this, 'sanitize_restriction_existed_role' ),
					),
					'_um_search_fields'            => array(
						'sanitize' => array( $this, 'sanitize_filter_fields' ),
					),
					'_um_filters_expanded'         => array(
						'sanitize' => 'bool',
					),
					'_um_filters_is_collapsible'   => array(
						'sanitize' => 'bool',
					),
					'_um_search_filters'           => array(
						'sanitize' => array( $this, 'sanitize_filter_fields' ),
					),
					'_um_sortby'                   => array(
						'sanitize' => 'text',
					),
					'_um_sortby_custom'            => array(
						'sanitize' => 'text',
					),
					'_um_sortby_custom_label'      => array(
						'sanitize' => 'text',
					),
					'_um_sortby_custom_type'       => array(
						'sanitize' => 'text',
					),
					'_um_sortby_custom_order'      => array(
						'sanitize' => 'text',
					),
					'_um_enable_sorting'           => array(
						'sanitize' => 'bool',
					),
					'_um_sorting_fields'           => array(
						'sanitize' => array( $this, 'sanitize_md_sorting_fields' ),
					),
				)
			);

			$this->form_meta = apply_filters(
				'um_form_meta_map',
				array(
					'_um_mode'                          => array(
						'sanitize' => 'key',
					),
					'_um_register_use_gdpr'             => array(
						'sanitize' => 'bool',
					),
					'_um_register_use_gdpr_content_id'  => array(
						'sanitize' => 'absint',
					),
					'_um_register_use_gdpr_toggle_show' => array(
						'sanitize' => 'text',
					),
					'_um_register_use_gdpr_toggle_hide' => array(
						'sanitize' => 'text',
					),
					'_um_register_use_gdpr_agreement'   => array(
						'sanitize' => 'text',
					),
					'_um_register_use_gdpr_error_text'  => array(
						'sanitize' => 'text',
					),
					'_um_register_use_custom_settings'  => array(
						'sanitize' => 'bool',
					),
					'_um_register_role'                 => array(
						'sanitize' => 'key',
					),
					'_um_register_template'             => array(
						'sanitize' => 'text',
					),
					'_um_register_max_width'            => array(
						'sanitize' => 'text',
					),
					'_um_register_icons'                => array(
						'sanitize' => 'key',
					),
					'_um_register_primary_btn_word'     => array(
						'sanitize' => 'text',
					),
					'_um_register_secondary_btn'        => array(
						'sanitize' => 'bool',
					),
					'_um_register_secondary_btn_word'   => array(
						'sanitize' => 'text',
					),
					'_um_login_after_login'             => array(
						'sanitize' => 'key',
					),
					'_um_login_redirect_url'            => array(
						'sanitize' => 'url',
					),
					'_um_login_use_custom_settings'     => array(
						'sanitize' => 'bool',
					),
					'_um_login_template'                => array(
						'sanitize' => 'text',
					),
					'_um_login_max_width'               => array(
						'sanitize' => 'text',
					),
					'_um_login_icons'                   => array(
						'sanitize' => 'key',
					),
					'_um_login_primary_btn_word'        => array(
						'sanitize' => 'text',
					),
					'_um_login_secondary_btn'           => array(
						'sanitize' => 'bool',
					),
					'_um_login_secondary_btn_word'      => array(
						'sanitize' => 'text',
					),
					'_um_login_forgot_pass_link'        => array(
						'sanitize' => 'bool',
					),
					'_um_login_show_rememberme'         => array(
						'sanitize' => 'bool',
					),
					'_um_profile_metafields'            => array(
						'sanitize' => array( $this, 'sanitize_user_field' ),
					),
					'_um_profile_use_custom_settings'   => array(
						'sanitize' => 'bool',
					),
					'_um_profile_role'                  => array(
						'sanitize' => array( $this, 'sanitize_existed_role' ),
					),
					'_um_profile_template'              => array(
						'sanitize' => 'text',
					),
					'_um_profile_max_width'             => array(
						'sanitize' => 'text',
					),
					'_um_profile_area_max_width'        => array(
						'sanitize' => 'text',
					),
					'_um_profile_icons'                 => array(
						'sanitize' => 'key',
					),
					'_um_profile_primary_btn_word'      => array(
						'sanitize' => 'text',
					),
					'_um_profile_secondary_btn'         => array(
						'sanitize' => 'bool',
					),
					'_um_profile_secondary_btn_word'    => array(
						'sanitize' => 'text',
					),
					'_um_profile_cover_enabled'         => array(
						'sanitize' => 'bool',
					),
					'_um_profile_coversize'             => array(
						'sanitize' => 'absint',
					),
					'_um_profile_cover_ratio'           => array(
						'sanitize' => 'text',
					),
					'_um_profile_disable_photo_upload'  => array(
						'sanitize' => 'bool',
					),
					'_um_profile_photosize'             => array(
						'sanitize' => array( $this, 'sanitize_photosize' ),
					),
					'_um_profile_photo_required'        => array(
						'sanitize' => 'bool',
					),
					'_um_profile_show_name'             => array(
						'sanitize' => 'bool',
					),
					'_um_profile_show_social_links'     => array(
						'sanitize' => 'bool',
					),
					'_um_profile_show_bio'              => array(
						'sanitize' => 'bool',
					),

				)
			);

			$this->builder_input = apply_filters(
				'um_builder_input_map',
				array(
					'_in_row'                         => array(
						'sanitize' => 'key',
					),
					'_in_sub_row'                     => array(
						'sanitize' => 'absint',
					),
					'_in_column'                      => array(
						'sanitize' => 'absint',
					),
					'_in_group'                       => array(
						'sanitize' => 'absint',
					),
					'_visibility'                     => array(
						'sanitize' => 'key',
					),
					'_conditional_action'             => array(
						'sanitize' => 'key',
					),
					'_conditional_action1'            => array(
						'sanitize' => 'key',
					),
					'_conditional_action2'            => array(
						'sanitize' => 'key',
					),
					'_conditional_action3'            => array(
						'sanitize' => 'key',
					),
					'_conditional_action4'            => array(
						'sanitize' => 'key',
					),
					'_conditional_field'              => array(
						'sanitize' => 'text',
					),
					'_conditional_field1'             => array(
						'sanitize' => 'text',
					),
					'_conditional_field2'             => array(
						'sanitize' => 'text',
					),
					'_conditional_field3'             => array(
						'sanitize' => 'text',
					),
					'_conditional_field4'             => array(
						'sanitize' => 'text',
					),
					'_conditional_operator'           => array(
						'sanitize' => 'text',
					),
					'_conditional_operator1'          => array(
						'sanitize' => 'text',
					),
					'_conditional_operator2'          => array(
						'sanitize' => 'text',
					),
					'_conditional_operator3'          => array(
						'sanitize' => 'text',
					),
					'_conditional_operator4'          => array(
						'sanitize' => 'text',
					),
					'_conditional_value'              => array(
						'sanitize' => 'text',
					),
					'_conditional_value1'             => array(
						'sanitize' => 'text',
					),
					'_conditional_value2'             => array(
						'sanitize' => 'text',
					),
					'_conditional_value3'             => array(
						'sanitize' => 'text',
					),
					'_conditional_value4'             => array(
						'sanitize' => 'text',
					),
					'_validate'                       => array(
						'sanitize' => 'key',
					),
					'_custom_validate'                => array(
						'sanitize' => 'text',
					),
					'_icon'                           => array(
						'sanitize' => 'key',
					),
					'_css_class'                      => array(
						'sanitize' => 'text',
					),
					'_width'                          => array(
						'sanitize' => 'absint',
					),
					'_divider_text'                   => array(
						'sanitize' => 'text',
					),
					'_padding'                        => array(
						'sanitize' => 'text',
					),
					'_margin'                         => array(
						'sanitize' => 'text',
					),
					'_border'                         => array(
						'sanitize' => 'text',
					),
					'_borderstyle'                    => array(
						'sanitize' => 'key',
					),
					'_borderradius'                   => array(
						'sanitize' => 'text',
					),
					'_bordercolor'                    => array(
						'sanitize' => 'text',
					),
					'_heading'                        => array(
						'sanitize' => 'bool',
					),
					'_heading_text'                   => array(
						'sanitize' => 'text',
					),
					'_background'                     => array(
						'sanitize' => 'text',
					),
					'_heading_background_color'       => array(
						'sanitize' => 'text',
					),
					'_heading_text_color'             => array(
						'sanitize' => 'text',
					),
					'_text_color'                     => array(
						'sanitize' => 'text',
					),
					'_icon_color'                     => array(
						'sanitize' => 'text',
					),
					'_color'                          => array(
						'sanitize' => 'text',
					),
					'_url_text'                       => array(
						'sanitize' => 'text',
					),
					'_url_target'                     => array(
						'sanitize' => 'key',
					),
					'_url_rel'                        => array(
						'sanitize' => 'key',
					),
					'_force_good_pass'                => array(
						'sanitize' => 'bool',
					),
					'_force_confirm_pass'             => array(
						'sanitize' => 'bool',
					),
					'_style'                          => array(
						'sanitize' => 'key',
					),
					'_intervals'                      => array(
						'sanitize' => 'absint',
					),
					'_format'                         => array(
						'sanitize' => 'text',
					),
					'_format_custom'                  => array(
						'sanitize' => 'text',
					),
					'_pretty_format'                  => array(
						'sanitize' => 'bool',
					),
					'_disabled_weekdays'              => array(
						'sanitize' => 'absint',
					),
					'_years'                          => array(
						'sanitize' => 'absint',
					),
					'_years_x'                        => array(
						'sanitize' => 'key',
					),
					'_range_start'                    => array(
						'sanitize' => 'text',
					),
					'_range_end'                      => array(
						'sanitize' => 'text',
					),
					'_range'                          => array(
						'sanitize' => 'key',
					),
					'_content'                        => array(
						'sanitize' => 'textarea',
					),
					'_crop'                           => array(
						'sanitize' => 'int',
					),
					'_allowed_types'                  => array(
						'sanitize' => 'key',
					),
					'_upload_text'                    => array(
						'sanitize' => 'text',
					),
					'_upload_help_text'               => array(
						'sanitize' => 'text',
					),
					'_button_text'                    => array(
						'sanitize' => 'text',
					),
					'_max_size'                       => array(
						'sanitize' => 'empty_absint',
					),
					'_height'                         => array(
						'sanitize' => 'text',
					),
					'_spacing'                        => array(
						'sanitize' => 'text',
					),
					'_is_multi'                       => array(
						'sanitize' => 'bool',
					),
					'_max_selections'                 => array(
						'sanitize' => 'empty_absint',
					),
					'_min_selections'                 => array(
						'sanitize' => 'empty_absint',
					),
					'_max_entries'                    => array(
						'sanitize' => 'empty_absint',
					),
					'_max_words'                      => array(
						'sanitize' => 'empty_absint',
					),
					'_min'                            => array(
						'sanitize' => 'empty_int',
					),
					'_max'                            => array(
						'sanitize' => 'empty_int',
					),
					'_min_chars'                      => array(
						'sanitize' => 'empty_absint',
					),
					'_max_chars'                      => array(
						'sanitize' => 'empty_absint',
					),
					'_html'                           => array(
						'sanitize' => 'bool',
					),
					'_options'                        => array(
						'sanitize' => 'textarea',
					),
					'_title'                          => array(
						'sanitize' => 'text',
					),
					'_id'                             => array(
						'sanitize' => 'text',
					),
					'_metakey'                        => array(
						'sanitize' => 'text',
					),
					'_help'                           => array(
						'sanitize' => 'text',
					),
					'_default'                        => array(
						'sanitize' => 'text',
					),
					'_label'                          => array(
						'sanitize' => 'text',
					),
					'_label_confirm_pass'             => array(
						'sanitize' => 'text',
					),
					'_placeholder'                    => array(
						'sanitize' => 'text',
					),
					'_public'                         => array(
						'sanitize' => 'text',
					),
					'_roles'                          => array(
						'sanitize' => array( $this, 'sanitize_existed_role' ),
					),
					'_required'                       => array(
						'sanitize' => 'bool',
					),
					'_editable'                       => array(
						'sanitize' => 'bool',
					),
					'_number'                         => array(
						'sanitize' => 'absint',
					),
					'_custom_dropdown_options_source' => array(
						'sanitize' => 'text',
					),
					'_parent_dropdown_relationship'   => array(
						'sanitize' => 'text',
					),
				)
			);
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_md_sorting_fields( $value ) {
			$filter_fields = array_merge( UM()->member_directory()->sort_fields, array( 'other' => __( 'Other (Custom Field)', 'ultimate-member' ) ) );
			$filter_fields = array_keys( $filter_fields );

			if ( '' !== $value ) {
				$value = array_filter(
					$value,
					function( $v, $k ) use ( $filter_fields ) {
						if ( 'other_data' === $k ) {
							return true;
						} else {
							return in_array( sanitize_text_field( $v ), $filter_fields, true );
						}
					},
					ARRAY_FILTER_USE_BOTH
				);

				$value = array_map(
					function( $item ) {
						if ( is_array( $item ) ) {
							if ( isset( $item['meta_key'] ) ) {
								$item['meta_key'] = sanitize_text_field( $item['meta_key'] );
							}
							if ( isset( $item['label'] ) ) {
								$item['label'] = sanitize_text_field( $item['label'] );
							}
							if ( isset( $item['order'] ) ) {
								$item['order'] = sanitize_text_field( $item['order'] );
							}
							if ( isset( $item['data_type'] ) ) {
								$item['data_type'] = sanitize_text_field( $item['data_type'] );
							}

							return $item;
						} else {
							return sanitize_text_field( $item );
						}
					},
					$value
				);
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_filter_fields( $value ) {
			$filter_fields = array_keys( UM()->member_directory()->filter_fields );

			if ( '' !== $value ) {
				$value = array_filter(
					$value,
					function( $v, $k ) use ( $filter_fields ) {
						return in_array( sanitize_text_field( $v ), $filter_fields, true );
					},
					ARRAY_FILTER_USE_BOTH
				);

				$value = array_map( 'sanitize_text_field', $value );
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_user_field( $value ) {
			$user_fields = array_keys( UM()->builtin()->all_user_fields() );

			if ( '' !== $value ) {
				$value = array_filter(
					$value,
					function( $v, $k ) use ( $user_fields ) {
						return in_array( sanitize_text_field( $v ), $user_fields, true );
					},
					ARRAY_FILTER_USE_BOTH
				);

				$value = array_map( 'sanitize_text_field', $value );
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_md_view_types( $value ) {
			$view_types = array_map(
				function ( $item ) {
					return $item['title'];
				},
				UM()->member_directory()->view_types
			);
			$view_types = array_keys( $view_types );

			if ( '' !== $value ) {
				$value = array_filter(
					$value,
					function( $v, $k ) use ( $view_types ) {
						return in_array( sanitize_key( $k ), $view_types, true ) && 1 === (int) $v;
					},
					ARRAY_FILTER_USE_BOTH
				);

				$value = array_map( 'sanitize_key', $value );
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_photosize( $value ) {
			$sizes = UM()->files()->get_profile_photo_size( 'photo_thumb_sizes' );
			$sizes = array_keys( $sizes );

			if ( '' !== $value ) {
				$value = in_array( absint( $value ), $sizes, true ) ? absint( $value ) : '';
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_cover_photosize( $value ) {
			$sizes = UM()->files()->get_profile_photo_size( 'cover_thumb_sizes' );
			$sizes = array_keys( $sizes );

			if ( '' !== $value ) {
				$value = in_array( absint( $value ), $sizes, true ) ? absint( $value ) : '';
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_restriction_existed_role( $value ) {
			$all_roles = array_keys( UM()->roles()->get_roles() );

			if ( '' !== $value ) {
				$value = array_filter(
					$value,
					function( $v, $k ) use ( $all_roles ) {
						return in_array( sanitize_key( $k ), $all_roles, true ) && 1 === (int) $v;
					},
					ARRAY_FILTER_USE_BOTH
				);

				$value = array_map( 'sanitize_key', $value );
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_existed_role( $value ) {
			$all_roles = array_keys( UM()->roles()->get_roles() );

			if ( '' !== $value ) {
				$value = array_filter(
					$value,
					function( $v, $k ) use ( $all_roles ) {
						return in_array( sanitize_key( $v ), $all_roles, true );
					},
					ARRAY_FILTER_USE_BOTH
				);

				$value = array_map( 'sanitize_key', $value );
			}

			return $value;
		}


		/**
		 * @param array|string $value
		 *
		 * @return array|string
		 */
		public function sanitize_tabs_privacy( $value ) {
			$all_privacy = array_keys( UM()->profile()->tabs_privacy() );

			if ( '' !== $value ) {
				$value = in_array( absint( $value ), $all_privacy, true ) ? absint( $value ) : '';
			}

			return $value;
		}


		/**
		 * @param $value
		 *
		 * @return bool|string
		 */
		public function sanitize_profile_noindex( $value ) {
			$value = '' !== $value ? (bool) $value : $value;
			return $value;
		}

		/**
		 * @param $value
		 *
		 * @return array
		 */
		public function sanitize_wp_capabilities( $value ) {
			$value = array_map( 'boolval', array_filter( $value ) );
			return $value;
		}

		/**
		 * @param $value
		 *
		 * @return array
		 */
		public function sanitize_wp_capabilities_assoc( $value ) {
			$value = array_map( 'sanitize_key', array_filter( $value ) );
			return $value;
		}

		/**
		 * Sanitize role meta fields when wp-admin form has been submitted
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function sanitize_role_meta( $data ) {
			$sanitized = array();
			foreach ( $data as $k => $v ) {
				if ( ! array_key_exists( $k, $this->role_meta ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', $this->role_meta[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( $this->role_meta[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_role_meta_sanitize_' . $k, $this->role_meta[ $k ]['sanitize'], 10, 1 );
				}

				switch ( $this->role_meta[ $k ]['sanitize'] ) {
					default:
						$sanitized[ $k ] = apply_filters( 'um_role_meta_sanitize_' . $k, $data[ $k ] );
						break;
					case 'int':
						$sanitized[ $k ] = (int) $v;
						break;
					case 'bool':
						$sanitized[ $k ] = (bool) $v;
						break;
					case 'url':
						$sanitized[ $k ] = esc_url_raw( $v );
						break;
					case 'textarea':
						$sanitized[ $k ] = sanitize_textarea_field( $v );
						break;
					case 'sanitize_array_key':
						if ( ! array_key_exists( 'default', $this->role_meta[ $k ] ) || ! array_key_exists( 'array', $this->role_meta[ $k ] ) ) {
							continue 2;
						}

						$sanitized[ $k ] = ! in_array( sanitize_key( $v ), $this->role_meta[ $k ]['array'], true ) ? $this->role_meta[ $k ]['default'] : sanitize_key( $v );
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_role_meta_sanitize', $data );

			return $data;
		}


		/**
		 * Sanitize post restriction meta fields when wp-admin form has been submitted
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function sanitize_post_restriction_meta( $data ) {
			$sanitized = array();
			foreach ( $data as $k => $v ) {
				if ( ! array_key_exists( $k, $this->restriction_post_meta ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', $this->restriction_post_meta[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( $this->restriction_post_meta[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_restriction_post_meta_sanitize_' . $k, $this->restriction_post_meta[ $k ]['sanitize'], 10, 1 );
				}

				switch ( $this->restriction_post_meta[ $k ]['sanitize'] ) {
					default:
						$sanitized[ $k ] = apply_filters( 'um_restriction_post_meta_sanitize_' . $k, $data[ $k ] );
						break;
					case 'int':
						$sanitized[ $k ] = (int) $v;
						break;
					case 'bool':
						$sanitized[ $k ] = (bool) $v;
						break;
					case 'url':
						$sanitized[ $k ] = esc_url_raw( $v );
						break;
					case 'textarea':
						$sanitized[ $k ] = sanitize_textarea_field( $v );
						break;
					case 'wp_kses':
						$sanitized[ $k ] = wp_kses_post( $v );
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_restriction_post_meta_sanitize', $data );

			return $data;
		}


		/**
		 * Sanitize term restriction meta fields when wp-admin form has been submitted
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function sanitize_term_restriction_meta( $data ) {
			$sanitized = array();
			foreach ( $data as $k => $v ) {
				if ( ! array_key_exists( $k, $this->restriction_term_meta ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', $this->restriction_term_meta[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( $this->restriction_term_meta[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_restriction_term_meta_sanitize_' . $k, $this->restriction_term_meta[ $k ]['sanitize'], 10, 1 );
				}

				switch ( $this->restriction_term_meta[ $k ]['sanitize'] ) {
					default:
						$sanitized[ $k ] = apply_filters( 'um_restriction_term_meta_sanitize_' . $k, $data[ $k ] );
						break;
					case 'int':
						$sanitized[ $k ] = (int) $v;
						break;
					case 'bool':
						$sanitized[ $k ] = (bool) $v;
						break;
					case 'url':
						$sanitized[ $k ] = esc_url_raw( $v );
						break;
					case 'textarea':
						$sanitized[ $k ] = sanitize_textarea_field( $v );
						break;
					case 'wp_kses':
						$sanitized[ $k ] = wp_kses_post( $v );
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_restriction_term_meta_sanitize', $data );

			return $data;
		}


		/**
		 * Sanitize member directory meta when wp-admin form has been submitted
		 *
		 * @todo checking all sanitize types
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function sanitize_member_directory_meta( $data ) {
			$sanitized = array();
			foreach ( $data as $k => $v ) {
				if ( ! array_key_exists( $k, $this->member_directory_meta ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', $this->member_directory_meta[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( $this->member_directory_meta[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_member_directory_meta_sanitize_' . $k, $this->member_directory_meta[ $k ]['sanitize'], 10, 1 );
				}

				switch ( $this->member_directory_meta[ $k ]['sanitize'] ) {
					default:
						$sanitized[ $k ] = apply_filters( 'um_member_directory_meta_sanitize_' . $k, $data[ $k ] );
						break;
					case 'int':
						$sanitized[ $k ] = (int) $v;
						break;
					case 'bool':
						$sanitized[ $k ] = (bool) $v;
						break;
					case 'url':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'esc_url_raw', $v );
						} else {
							$sanitized[ $k ] = esc_url_raw( $v );
						}
						break;
					case 'text':
						$sanitized[ $k ] = sanitize_text_field( $v );
						break;
					case 'textarea':
						$sanitized[ $k ] = sanitize_textarea_field( $v );
						break;
					case 'key':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'sanitize_key', $v );
						} else {
							$sanitized[ $k ] = sanitize_key( $v );
						}
						break;
					case 'absint':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'absint', $v );
						} else {
							$sanitized[ $k ] = absint( $v );
						}
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_member_directory_meta_sanitize', $data );

			return $data;
		}


		/**
		 * Sanitize builder field meta when wp-admin form has been submitted
		 *
		 * @todo checking all sanitize types
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function sanitize_builder_field_meta( $data ) {
			$sanitized = array();
			foreach ( $data as $k => $v ) {
				if ( ! array_key_exists( $k, $this->builder_input ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', $this->builder_input[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( $this->builder_input[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_builder_input_sanitize_' . $k, $this->builder_input[ $k ]['sanitize'], 10, 1 );
				}

				switch ( $this->builder_input[ $k ]['sanitize'] ) {
					default:
						$sanitized[ $k ] = apply_filters( 'um_builder_input_sanitize_' . $k, $data[ $k ] );
						break;
					case 'int':
						$sanitized[ $k ] = (int) $v;
						break;
					case 'empty_int':
						$sanitized[ $k ] = ( '' !== $v ) ? (int) $v : '';
						break;
					case 'bool':
						$sanitized[ $k ] = (bool) $v;
						break;
					case 'url':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'esc_url_raw', $v );
						} else {
							$sanitized[ $k ] = esc_url_raw( $v );
						}
						break;
					case 'text':
						$sanitized[ $k ] = sanitize_text_field( $v );
						break;
					case 'textarea':
						$sanitized[ $k ] = sanitize_textarea_field( $v );
						break;
					case 'wp_kses':
						$sanitized[ $k ] = wp_kses_post( $v );
						break;
					case 'key':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'sanitize_key', $v );
						} else {
							$sanitized[ $k ] = sanitize_key( $v );
						}
						break;
					case 'absint':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'absint', $v );
						} else {
							$sanitized[ $k ] = absint( $v );
						}
						break;
					case 'empty_absint':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'absint', $v );
						} else {
							$sanitized[ $k ] = ( '' !== $v ) ? absint( $v ) : '';
						}
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_builder_input_sanitize', $data );

			return $data;
		}


		/**
		 * Sanitize form meta when wp-admin form has been submitted
		 *
		 * @todo checking all sanitize types
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function sanitize_form_meta( $data ) {
			$sanitized = array();
			foreach ( $data as $k => $v ) {
				if ( ! array_key_exists( $k, $this->form_meta ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', $this->form_meta[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( $this->form_meta[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_form_meta_sanitize_' . $k, $this->form_meta[ $k ]['sanitize'], 10, 1 );
				}

				switch ( $this->form_meta[ $k ]['sanitize'] ) {
					default:
						$sanitized[ $k ] = apply_filters( 'um_form_meta_sanitize_' . $k, $data[ $k ] );
						break;
					case 'int':
						$sanitized[ $k ] = (int) $v;
						break;
					case 'bool':
						$sanitized[ $k ] = (bool) $v;
						break;
					case 'url':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'esc_url_raw', $v );
						} else {
							$sanitized[ $k ] = esc_url_raw( $v );
						}
						break;
					case 'text':
						$sanitized[ $k ] = sanitize_text_field( $v );
						break;
					case 'textarea':
						$sanitized[ $k ] = sanitize_textarea_field( $v );
						break;
					case 'key':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'sanitize_key', $v );
						} else {
							$sanitized[ $k ] = sanitize_key( $v );
						}
						break;
					case 'absint':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'absint', $v );
						} else {
							$sanitized[ $k ] = absint( $v );
						}
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_form_meta_sanitize', $data );

			return $data;
		}


		/**
		 * Sanitize options when wp-admin form has been submitted
		 *
		 * @todo checking all sanitize types
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		public function sanitize_options( $data ) {
			$sanitized = array();
			foreach ( $data as $k => $v ) {
				if ( ! array_key_exists( $k, UM()->admin_settings()->settings_map ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', UM()->admin_settings()->settings_map[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( UM()->admin_settings()->settings_map[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_settings_sanitize_' . $k, UM()->admin_settings()->settings_map[ $k ]['sanitize'], 10, 1 );
				}

				switch ( UM()->admin_settings()->settings_map[ $k ]['sanitize'] ) {
					default:
						$sanitized[ $k ] = apply_filters( 'um_settings_sanitize_' . $k, $v );
						break;
					case 'int':
						$sanitized[ $k ] = (int) $v;
						break;
					case 'absint':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'absint', $v );
						} else {
							$sanitized[ $k ] = absint( $v );
						}
						break;
					case 'key':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'sanitize_key', $v );
						} else {
							$sanitized[ $k ] = sanitize_key( $v );
						}
						break;
					case 'bool':
						$sanitized[ $k ] = (bool) $v;
						break;
					case 'url':
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'esc_url_raw', $v );
						} else {
							$sanitized[ $k ] = esc_url_raw( $v );
						}
						break;
					case 'wp_kses':
						$sanitized[ $k ] = wp_kses_post( $v );
						break;
					case 'textarea':
						$sanitized[ $k ] = sanitize_textarea_field( $v );
						break;
					case 'text':
						$sanitized[ $k ] = sanitize_text_field( $v );
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_settings_sanitize', $data );

			return $data;
		}


		/**
		 * Adds class to our admin pages
		 *
		 * @param $classes
		 *
		 * @return string
		 */
		public function admin_body_class( $classes ) {
			if ( $this->is_um_screen() ) {
				return "$classes um-admin";
			}
			return $classes;
		}

		/**
		 *
		 */
		public function manual_upgrades_request() {
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
						'update' => 'um_got_updates',
					),
					admin_url( 'admin.php' )
				);
			} else {
				$url = add_query_arg(
					array(
						'page'   => 'ultimatemember',
						'update' => 'um_often_updates',
					),
					admin_url( 'admin.php' )
				);
			}
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Core pages installation.
		 */
		public function install_core_pages() {
			UM()->setup()->install_default_pages();

			//check empty pages in settings
			$empty_pages = array();

			$pages = UM()->config()->permalinks;
			if ( $pages && is_array( $pages ) ) {
				foreach ( $pages as $slug => $page_id ) {
					$page = get_post( $page_id );

					if ( ! isset( $page->ID ) && array_key_exists( $slug, UM()->config()->core_pages ) ) {
						$empty_pages[] = $slug;
					}
				}
			}

			//if there aren't empty pages - then hide pages notice
			if ( empty( $empty_pages ) ) {
				$hidden_notices   = get_option( 'um_hidden_admin_notices', array() );
				$hidden_notices[] = 'wrong_pages';

				update_option( 'um_hidden_admin_notices', $hidden_notices );
			}

			$url = add_query_arg( array( 'page' => 'um_options' ), admin_url( 'admin.php' ) );
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Clear all users cache.
		 */
		public function user_cache() {
			global $wpdb;

			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'um_cache_userdata_%'" );

			$url = add_query_arg(
				array(
					'page'   => 'ultimatemember',
					'update' => 'um_cleared_cache',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Clear all users statuses count cache.
		 */
		public function user_status_cache() {
			$statuses = array(
				'approved',
				'awaiting_admin_review',
				'awaiting_email_confirmation',
				'inactive',
				'rejected',
				'pending_dot', // not real status key, just for the transient
				'unassigned', // not real status key, just for the transient
			);

			foreach ( $statuses as $status ) {
				delete_transient( "um_count_users_{$status}" );
			}

			do_action( 'um_flush_user_status_cache' );

			$url = add_query_arg(
				array(
					'page'   => 'ultimatemember',
					'update' => 'um_cleared_status_cache',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Purge temp uploads dir.
		 */
		public function purge_temp() {
			UM()->files()->remove_dir( UM()->files()->upload_temp );

			$url = add_query_arg(
				array(
					'page'   => 'ultimatemember',
					'update' => 'um_purged_temp',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Duplicate form
		 */
		public function duplicate_form() {
			if ( empty( $_REQUEST['post_id'] ) || empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], "um-duplicate_form{$_REQUEST['post_id']}" ) ) {
				die( esc_html__( 'Security check', 'ultimate-member' ) );
			}

			if ( ! is_numeric( $_REQUEST['post_id'] ) ) {
				die( esc_html__( 'Wrong ID', 'ultimate-member' ) );
			}

			$post_id = absint( $_REQUEST['post_id'] );

			$n = array(
				'post_type'   => 'um_form',
				// translators: %s - Form title
				'post_title'  => sprintf( __( 'Duplicate of %s', 'ultimate-member' ), get_the_title( $post_id ) ),
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
			);

			$n_id = wp_insert_post( $n );

			$n_fields = get_post_custom( $post_id );
			foreach ( $n_fields as $key => $value ) {
				if ( '_um_custom_fields' === $key ) {
					$the_value = maybe_unserialize( $value[0] );
				} else {
					$the_value = $value[0];
				}

				update_post_meta( $n_id, $key, $the_value );
			}

			delete_post_meta( $n_id, '_um_core' );

			$url = add_query_arg(
				array(
					'post_type' => 'um_form',
					'update'    => 'um_form_duplicated',
				),
				admin_url( 'edit.php' )
			);
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Various user actions.
		 */
		public function user_action() {
			if ( ! current_user_can( 'edit_users' ) ) {
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

			wp_safe_redirect( add_query_arg( 'update', 'um_user_updated', admin_url( '?page=ultimatemember' ) ) );
			exit;
		}

		/**
		 * Manual check templates versions.
		 */
		public function check_templates_version() {
			$templates = UM()->admin_settings()->get_override_templates( true );
			$out_date  = false;

			foreach ( $templates as $template ) {
				if ( 0 === $template['status_code'] ) {
					$out_date = true;
					break;
				}
			}

			if ( false === $out_date ) {
				delete_option( 'um_override_templates_outdated' );
			}

			$url = add_query_arg(
				array(
					'page' => 'um_options',
					'tab'  => 'override_templates',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Add any custom links to plugin page.
		 *
		 * @param array $links
		 *
		 * @return array
		 */
		public function plugin_links( $links ) {
			$more_links[] = '<a href="http://docs.ultimatemember.com/">' . esc_html__( 'Docs', 'ultimate-member' ) . '</a>';
			$more_links[] = '<a href="' . admin_url() . 'admin.php?page=um_options">' . esc_html__( 'Settings', 'ultimate-member' ) . '</a>';

			$links = $more_links + $links;
			return $links;
		}

		/**
		 * Init admin action/filters + request handlers
		 */
		public function admin_init() {
			$this->init_variables();

			if ( ! empty( $_REQUEST['um_adm_action'] ) && is_admin() && current_user_can( 'manage_options' ) ) {
				$action = sanitize_key( $_REQUEST['um_adm_action'] );

				$individual_nonce_actions = array(
					'user_action',
					'duplicate_form',
				);
				$individual_nonce_actions = apply_filters( 'um_adm_action_individual_nonce_actions', $individual_nonce_actions );

				// Some actions have their own nonce. Verify individually.
				if ( ! in_array( $action, $individual_nonce_actions, true ) ) {
					if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], $action ) ) {
						wp_die( esc_attr__( 'Security Check', 'ultimate-member' ) );
					}
				}

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_admin_do_action__
				 * @description Make some action on custom admin action
				 * @input_vars
				 * [{"var":"$action","type":"string","desc":"Admin Action"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_admin_do_action__', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_admin_do_action__', 'my_admin_do_action', 10, 1 );
				 * function my_admin_do_action( $action ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_admin_do_action__', $action );
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_admin_do_action__{$action}
				 * @description Make some action on custom admin $action
				 * @input_vars
				 * [{"var":"$action","type":"string","desc":"Admin Action"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_admin_do_action__{$action}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_admin_do_action__{$action}', 'my_admin_do_action', 10, 1 );
				 * function my_admin_do_action( $action ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_admin_do_action__{$action}", $action );
			}
		}

		/**
		 * Updated post messages
		 *
		 * @param array $messages
		 *
		 * @return array
		 */
		public function post_updated_messages( $messages ) {
			global $post_ID;

			$post_type = get_post_type( $post_ID );

			if ( 'um_form' === $post_type ) {
				$messages['um_form'] = array(
					0  => '',
					1  => __( 'Form updated.', 'ultimate-member' ),
					2  => __( 'Custom field updated.', 'ultimate-member' ),
					3  => __( 'Custom field deleted.', 'ultimate-member' ),
					4  => __( 'Form updated.', 'ultimate-member' ),
					5  => isset( $_GET['revision'] ) ? __( 'Form restored to revision.', 'ultimate-member' ) : false,
					6  => __( 'Form created.', 'ultimate-member' ),
					7  => __( 'Form saved.', 'ultimate-member' ),
					8  => __( 'Form submitted.', 'ultimate-member' ),
					9  => __( 'Form scheduled.', 'ultimate-member' ),
					10 => __( 'Form draft updated.', 'ultimate-member' ),
				);
			}

			return $messages;
		}

		/**
		 * Gettext filters
		 *
		 * @param $translation
		 * @param $text
		 * @param $domain
		 *
		 * @return string
		 */
		function gettext( $translation, $text, $domain ) {
			global $post;
			if ( isset( $post->post_type ) && $this->is_plugin_post_type() ) {
				$translations = get_translations_for_domain( $domain );
				if ( $text == 'Publish' ) {
					return $translations->translate( 'Create' );
				} elseif ( $text == 'Move to Trash' ) {
					return $translations->translate( 'Delete' );
				}
			}

			return $translation;
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

		/**
		 * @since 2.7.0
		 *
		 * @return Enqueue
		 */
		public function enqueue() {
			if ( empty( UM()->classes['um\admin\enqueue'] ) ) {
				UM()->classes['um\admin\enqueue'] = new Enqueue();
			}
			return UM()->classes['um\admin\enqueue'];
		}

		/**
		 * @since 2.0
		 *
		 * @return core\Admin_Notices()
		 */
		public function notices() {
			if ( empty( UM()->classes['admin_notices'] ) ) {
				UM()->classes['admin_notices'] = new core\Admin_Notices();
			}
			return UM()->classes['admin_notices'];
		}

		/**
		 * @since 2.6.8
		 *
		 * @return Secure
		 */
		public function secure() {
			if ( empty( UM()->classes['um\admin\secure'] ) ) {
				UM()->classes['um\admin\secure'] = new Secure();
			}
			return UM()->classes['um\admin\secure'];
		}

		/**
		 * @since 2.7.0
		 *
		 * @return Site_Health
		 */
		public function site_health() {
			if ( empty( UM()->classes['um\admin\site_health'] ) ) {
				UM()->classes['um\admin\site_health'] = new Site_Health();
			}
			return UM()->classes['um\admin\site_health'];
		}
	}
}
