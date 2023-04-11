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
		public $restriction_post_meta;

		/**
		 * Init constructor.
		 */
		public function __construct() {
			$this->templates_path = UM_PATH . 'includes/admin/templates/';

			add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );
		}

		/**
		 *
		 */
		public function init_variables() {
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
		public function sanitize_avatar( $value ) {
			if ( ! is_array( $value ) ) {
				$value = sanitize_text_field( $value );
				return $value;
			}

			if ( array_key_exists( 'url', $value ) ) {
				$value['url'] = esc_url_raw( $value['url'] );
			}

			if ( array_key_exists( 'id', $value ) ) {
				$value['id'] = absint( $value['id'] );
			}

			if ( array_key_exists( 'width', $value ) ) {
				$value['width'] = absint( $value['width'] );
			}

			if ( array_key_exists( 'height', $value ) ) {
				$value['height'] = absint( $value['height'] );
			}

			if ( array_key_exists( 'thumbnail', $value ) ) {
				$value['thumbnail'] = sanitize_text_field( $value['thumbnail'] );
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
						if ( is_array( $v ) ) {
							$sanitized[ $k ] = array_map( 'sanitize_text_field', $v );
						} else {
							$sanitized[ $k ] = sanitize_text_field( $v );
						}
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
					case 'color':
						$sanitized[ $k ] = sanitize_hex_color( $v );
						break;
				}
			}

			$data = $sanitized;

			$data = apply_filters( 'um_save_settings_sanitize', $data );

			return $data;
		}

		/**
		 * Init admin action/filters + request handlers
		 */
		public function admin_init() {
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
		public function includes() {
			$this->actions_listener();
			$this->builder();
			$this->columns();
			$this->db_upgrade();
			$this->enqueue();
			$this->gdpr();
			$this->menu();
			$this->metabox();
			$this->notices();
			$this->settings();
			$this->site_health();
			$this->users_columns();
			$this->field_group()->hooks();
		}

		/**
		 * @since 3.0
		 *
		 * @return Builder
		 */
		function builder() {
			if ( empty( UM()->classes['um\admin\builder'] ) ) {
				UM()->classes['um\admin\builder'] = new Builder();
			}
			return UM()->classes['um\admin\builder'];
		}

		/**
		 * @since 3.0
		 *
		 * @param bool|array $data
		 *
		 * @return Forms
		 */
		function forms( $data = false ) {
			if ( ! isset( UM()->classes[ 'um\admin\forms_' . $data['class'] ] ) || empty( UM()->classes[ 'um\admin\forms_' . $data['class'] ] ) ) {
				UM()->classes[ 'um\admin\forms_' . $data['class'] ] = new Forms( $data );
			}
			return UM()->classes[ 'um\admin\forms_' . $data['class'] ];
		}

		/**
		 * @since 3.0
		 *
		 * @param bool|array $data
		 *
		 * @return Forms_Settings
		 */
		function forms_settings( $data = false ) {
			if ( ! isset( UM()->classes[ 'um\admin\forms_settings_' . $data['class'] ] ) || empty( UM()->classes[ 'um\admin\forms_settings_' . $data['class'] ] ) ) {
				UM()->classes[ 'um\admin\forms_settings_' . $data['class'] ] = new Forms_Settings( $data );
			}
			return UM()->classes[ 'um\admin\forms_settings_' . $data['class'] ];
		}

		/**
		 * @since 3.0
		 *
		 * @return Users_Columns
		 */
		public function users_columns() {
			if ( empty( UM()->classes['um\admin\users_columns'] ) ) {
				UM()->classes['um\admin\users_columns'] = new Users_Columns();
			}
			return UM()->classes['um\admin\users_columns'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Site_Health
		 */
		public function site_health() {
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
		public function enqueue() {
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
		public function actions_listener() {
			if ( empty( UM()->classes['um\admin\actions_listener'] ) ) {
				UM()->classes['um\admin\actions_listener'] = new Actions_Listener();
			}
			return UM()->classes['um\admin\actions_listener'];
		}

		/**
		 * @since 3.0
		 *
		 * @return GDPR
		 */
		public function gdpr() {
			if ( empty( UM()->classes['um\admin\gdpr'] ) ) {
				UM()->classes['um\admin\gdpr'] = new GDPR();
			}
			return UM()->classes['um\admin\gdpr'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Menu
		 */
		public function menu() {
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
		public function metabox() {
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
		public function notices() {
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
		public function columns() {
			if ( empty( UM()->classes['um\admin\columns'] ) ) {
				UM()->classes['um\admin\columns'] = new Columns();
			}
			return UM()->classes['um\admin\columns'];
		}

		/**
		 * @since 3.0
		 *
		 * @return DB_Upgrade()
		 */
		public function db_upgrade() {
			if ( empty( UM()->classes['um\admin\db_upgrade'] ) ) {
				UM()->classes['um\admin\db_upgrade'] = new DB_Upgrade();
			}
			return UM()->classes['um\admin\db_upgrade'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Screen
		 */
		public function screen() {
			if ( empty( UM()->classes['um\admin\screen'] ) ) {
				UM()->classes['um\admin\screen'] = new Screen();
			}
			return UM()->classes['um\admin\screen'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Settings
		 */
		public function settings() {
			if ( empty( UM()->classes['um\admin\settings'] ) ) {
				UM()->classes['um\admin\settings'] = new Settings();
			}
			return UM()->classes['um\admin\settings'];
		}

		/**
		 * @since 3.0
		 *
		 * @return Field_Group
		 */
		public function field_group() {
			if ( empty( UM()->classes['um\admin\field_group'] ) ) {
				UM()->classes['um\admin\field_group'] = new Field_Group();
			}
			return UM()->classes['um\admin\field_group'];
		}
	}
}
