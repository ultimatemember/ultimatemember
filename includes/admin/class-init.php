<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Init' ) ) {


	/**
	 * Class Init
	 *
	 * @since 3.0
	 *
	 * @package um\admin
	 */
	class Init {


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
		 * Init constructor.
		 */
		public function __construct() {
			$this->templates_path = um_path . 'includes/admin/templates/';

			add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );

			add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ), 999 );
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
					'_um_register_role'                 => array(
						'sanitize' => 'key',
					),
					'_um_register_template'             => array(
						'sanitize' => 'text',
					),
					'_um_register_primary_btn_word'     => array(
						'sanitize' => 'text',
					),
					'_um_login_after_login'             => array(
						'sanitize' => 'key',
					),
					'_um_login_redirect_url'            => array(
						'sanitize' => 'url',
					),
					'_um_login_template'                => array(
						'sanitize' => 'text',
					),
					'_um_login_primary_btn_word'        => array(
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
						'sanitize' => 'text',
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
						'sanitize' => 'absint',
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
						'sanitize' => 'absint',
					),
					'_min_selections'                 => array(
						'sanitize' => 'absint',
					),
					'_max_entries'                    => array(
						'sanitize' => 'absint',
					),
					'_max_words'                      => array(
						'sanitize' => 'absint',
					),
					'_min'                            => array(
						'sanitize' => 'empty_int',
					),
					'_max'                            => array(
						'sanitize' => 'empty_int',
					),
					'_min_chars'                      => array(
						'sanitize' => 'absint',
					),
					'_max_chars'                      => array(
						'sanitize' => 'absint',
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
				if ( ! array_key_exists( $k, UM()->admin()->settings()->settings_map ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( ! array_key_exists( 'sanitize', UM()->admin()->settings()->settings_map[ $k ] ) ) {
					// @todo remove since 2.2.x and leave only continue
					$sanitized[ $k ] = $v;
					continue;
				}

				if ( is_callable( UM()->admin()->settings()->settings_map[ $k ]['sanitize'], true, $callable_name ) ) {
					add_filter( 'um_settings_sanitize_' . $k, UM()->admin()->settings()->settings_map[ $k ]['sanitize'], 10, 1 );
				}

				switch ( UM()->admin()->settings()->settings_map[ $k ]['sanitize'] ) {
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
			if ( $this->is_own_screen() ) {
				return "$classes um um-admin";
			}
			return $classes;
		}


		/**
		 * Init admin action/filters + request handlers
		 */
		function admin_init() {
			$this->init_variables();

			if ( is_admin() && current_user_can( 'manage_options' ) && ! empty( $_REQUEST['um_adm_action'] ) ) {
				$action = sanitize_key( $_REQUEST['um_adm_action'] );

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

			if ( ! empty( $_REQUEST['um_current_locale'] ) ) {
				$locale = sanitize_key( $_REQUEST['um_current_locale'] );
				do_action( 'um_admin_init_locale', $locale );
			}
		}


		/**
		 * Create classes' instances where __construct isn't empty for hooks init
		 *
		 * @used-by \UM::includes()
		 */
		function includes() {
			$this->enqueue();
			$this->actions_listener();
			$this->settings();
			$this->notices();
			$this->menu();
			$this->columns();
			$this->metabox();
			$this->site_health();
		}


		/**
		 * @since 3.0
		 *
		 * @return Site_Health
		 */
		function site_health() {
			if ( empty( UM()->classes['um\admin\site_health'] ) ) {
				UM()->classes['um\admin\site_health'] = new Site_Health();
			}
			return UM()->classes['um\admin\site_health'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Enqueue
		 */
		function enqueue() {
			if ( empty( UM()->classes['um\admin\enqueue'] ) ) {
				UM()->classes['um\admin\enqueue'] = new Enqueue();
			}
			return UM()->classes['um\admin\enqueue'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Actions_Listener
		 */
		function actions_listener() {
			if ( empty( UM()->classes['um\admin\actions_listener'] ) ) {
				UM()->classes['um\admin\actions_listener'] = new Actions_Listener();
			}
			return UM()->classes['um\admin\actions_listener'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Menu
		 */
		function menu() {
			if ( empty( UM()->classes['um\admin\menu'] ) ) {
				UM()->classes['um\admin\menu'] = new Menu();
			}
			return UM()->classes['um\admin\menu'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Metabox()
		 */
		function metabox() {
			if ( empty( UM()->classes['um\admin\metabox'] ) ) {
				UM()->classes['um\admin\metabox'] = new Metabox();
			}
			return UM()->classes['um\admin\metabox'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Notices()
		 */
		function notices() {
			if ( empty( UM()->classes['um\admin\notices'] ) ) {
				UM()->classes['um\admin\notices'] = new Notices();
			}
			return UM()->classes['um\admin\notices'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Columns()
		 */
		function columns() {
			if ( empty( UM()->classes['um\admin\columns'] ) ) {
				UM()->classes['um\admin\columns'] = new Columns();
			}
			return UM()->classes['um\admin\columns'];
		}


		/**
		 * @since 3.0
		 *
		 * @return Settings
		 */
		function settings() {
			if ( empty( UM()->classes['um\admin\settings'] ) ) {
				UM()->classes['um\admin\settings'] = new Settings();
			}
			return UM()->classes['um\admin\settings'];
		}


		/**
		 * Boolean check if we're viewing UM backend
		 *
		 * @since 3.0
		 *
		 * @return bool
		 */
		function is_own_screen() {
			global $current_screen;

			$is_um_screen = false;

			if ( ! empty( $current_screen ) ) {
				$screen_id = $current_screen->id;
				if ( strstr( $screen_id, 'ultimatemember' ) ||
				     strstr( $screen_id, 'um_' ) ||
				     strstr( $screen_id, 'user' ) ||
				     strstr( $screen_id, 'profile' ) ||
				     'nav-menus' === $screen_id ) {
					$is_um_screen = true;
				}
			}

			if ( $this->is_own_post_type() ) {
				$is_um_screen = true;
			}

			if ( $this->is_restricted_entity() ) {
				$is_um_screen = true;
			}

			return apply_filters( 'um_is_ultimatememeber_admin_screen', $is_um_screen );
		}


		/**
		 * Check if current page load UM post type
		 *
		 * @since 3.0
		 *
		 * @return bool
		 */
		function is_own_post_type() {
			$cpt = UM()->cpt_list();

			if ( isset( $_REQUEST['post_type'] ) ) {
				$post_type = sanitize_key( $_REQUEST['post_type'] );
				if ( in_array( $post_type, $cpt ) ) {
					return true;
				}
			} elseif ( isset( $_REQUEST['action'] ) && 'edit' === sanitize_key( $_REQUEST['action'] ) ) {
				$post_type = get_post_type();
				if ( in_array( $post_type, $cpt ) ) {
					return true;
				}
			}

			return false;
		}


		/**
		 * If page now show content with restricted post/taxonomy
		 *
		 * @since 3.0
		 *
		 * @return bool
		 */
		function is_restricted_entity() {
			$restricted_posts = UM()->options()->get( 'restricted_access_post_metabox' );
			$restricted_taxonomies = UM()->options()->get( 'restricted_access_taxonomy_metabox' );

			global $typenow, $taxnow;
			if ( ! empty( $typenow ) && ! empty( $restricted_posts[ $typenow ] ) ) {
				return true;
			}

			if ( ! empty( $taxnow ) && ! empty( $restricted_taxonomies[ $taxnow ] ) ) {
				return true;
			}

			return false;
		}

	}
}
