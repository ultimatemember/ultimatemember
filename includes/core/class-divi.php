<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Divi' ) ) {


	/**
	 * Class Divi
	 *
	 * Adds Ultimate Member content restriction controls to the Divi Builder
	 * "Visibility" option group so whole sections, rows, columns and modules
	 * can be hidden from selected visitors.
	 *
	 * @package um\core
	 *
	 * @since 2.15.0
	 */
	class Divi {


		/**
		 * Option name that tracks which UM version last regenerated the Divi field cache.
		 *
		 * @var string
		 *
		 * @since 2.15.0
		 */
		const FIELDS_VERSION_OPTION = 'um_divi_fields_version';


		/**
		 * Cached role options for the multiple buttons field.
		 *
		 * @var array|null
		 *
		 * @since 2.15.0
		 */
		private $roles_options = null;


		/**
		 * Cached account status options for the multiple buttons field.
		 *
		 * @var array|null
		 *
		 * @since 2.15.0
		 */
		private $statuses_options = null;


		/**
		 * Divi constructor.
		 *
		 * @since 2.15.0
		 */
		public function __construct() {
			// The Divi theme loads its builder on `init` at priority 0, so detection has to
			// happen after that. Hooking at priority 10 also keeps UM off non-Divi requests.
			add_action( 'init', array( &$this, 'init' ), 10 );
		}


		/**
		 * Register the integration once a Divi builder has been detected.
		 *
		 * @since 2.15.0
		 */
		public function init() {
			if ( ! $this->is_divi_active() ) {
				return;
			}

			add_filter( 'et_builder_module_general_fields', array( &$this, 'add_visibility_fields' ) );
			add_filter( 'et_module_process_display_conditions', array( &$this, 'maybe_hide_element' ), 20, 3 );
			// Run just after UM's own content restriction handler (priority 1000) so that runs first.
			add_action( 'template_redirect', array( &$this, 'maybe_redirect' ), 1001 );
			add_action( 'admin_init', array( &$this, 'maybe_regenerate_fields_cache' ) );
		}


		/**
		 * Whether a Divi builder (theme or Divi Builder plugin) is loaded.
		 *
		 * @since 2.15.0
		 *
		 * @return bool
		 */
		public function is_divi_active() {
			return defined( 'ET_BUILDER_PRODUCT_VERSION' ) || defined( 'ET_BUILDER_THEME' ) || function_exists( 'et_pb_is_pagebuilder_used' ) || class_exists( 'ET_Builder_Element' );
		}


		/**
		 * Add UM restriction fields to every Divi element's "Visibility" option group.
		 *
		 * The group already exists for sections, rows, columns and modules in the
		 * Advanced tab, so the fields only need to target the same tab and toggle.
		 *
		 * @since 2.15.0
		 *
		 * @param array $general_fields General fields shared by every builder element.
		 *
		 * @return array
		 */
		public function add_visibility_fields( $general_fields ) {
			$roles = $this->get_roles_options();

			$fields = array(
				'um_restrict_enable'       => array(
					'label'           => __( 'Ultimate Member: Restrict this element', 'ultimate-member' ),
					'description'     => __( 'Show or hide this element for visitors based on their login state, user role and Ultimate Member account status.', 'ultimate-member' ),
					'type'            => 'yes_no_button',
					'option_category' => 'configuration',
					'options'         => array(
						'on'  => __( 'Yes', 'ultimate-member' ),
						'off' => __( 'No', 'ultimate-member' ),
					),
					'default'         => 'off',
					'affects'         => array(
						'um_restrict_who',
						'um_restrict_roles',
						'um_restrict_status',
						'um_restrict_redirect',
						'um_restrict_redirect_url',
					),
					'tab_slug'        => 'custom_css',
					'toggle_slug'     => 'visibility',
					'priority'        => 50,
				),
				'um_restrict_who'          => array(
					'label'           => __( 'Who can see it', 'ultimate-member' ),
					'description'     => __( 'Restrict this element by login state.', 'ultimate-member' ),
					'type'            => 'select',
					'option_category' => 'configuration',
					'options'         => array(
						'0' => __( 'Everyone', 'ultimate-member' ),
						'1' => __( 'Logged in users only', 'ultimate-member' ),
						'2' => __( 'Logged out visitors only', 'ultimate-member' ),
					),
					'default'         => '0',
					'show_if'         => array( 'um_restrict_enable' => 'on' ),
					'tab_slug'        => 'custom_css',
					'toggle_slug'     => 'visibility',
					'priority'        => 51,
				),
				'um_restrict_roles'        => array(
					'label'           => __( 'User roles', 'ultimate-member' ),
					'description'     => __( 'Only show this element to the selected roles. Applies to logged in users, leave all unselected to skip the role check.', 'ultimate-member' ),
					'type'            => 'multiple_buttons',
					'option_category' => 'configuration',
					'options'         => $roles,
					'toggleable'      => true,
					'multi_selection' => true,
					'default'         => '',
					'show_if'         => array( 'um_restrict_enable' => 'on' ),
					'tab_slug'        => 'custom_css',
					'toggle_slug'     => 'visibility',
					'priority'        => 52,
				),
				'um_restrict_status'       => array(
					'label'           => __( 'Account statuses', 'ultimate-member' ),
					'description'     => __( 'Only show this element to users with the selected Ultimate Member account statuses. Logged out visitors have no account status, so they are hidden when this is set.', 'ultimate-member' ),
					'type'            => 'multiple_buttons',
					'option_category' => 'configuration',
					'options'         => $this->get_statuses_options(),
					'toggleable'      => true,
					'multi_selection' => true,
					'default'         => '',
					'show_if'         => array( 'um_restrict_enable' => 'on' ),
					'tab_slug'        => 'custom_css',
					'toggle_slug'     => 'visibility',
					'priority'        => 53,
				),
				'um_restrict_redirect'     => array(
					'label'           => __( 'If access is denied', 'ultimate-member' ),
					'description'     => __( 'Send the visitor to another page instead of just hiding this element.', 'ultimate-member' ),
					'type'            => 'select',
					'option_category' => 'configuration',
					'options'         => array(
						'none'   => __( 'Hide the element only', 'ultimate-member' ),
						'login'  => __( 'Redirect to the login page', 'ultimate-member' ),
						'custom' => __( 'Redirect to a custom URL', 'ultimate-member' ),
					),
					'default'         => 'none',
					'show_if'         => array( 'um_restrict_enable' => 'on' ),
					'tab_slug'        => 'custom_css',
					'toggle_slug'     => 'visibility',
					'priority'        => 54,
				),
				'um_restrict_redirect_url' => array(
					'label'           => __( 'Custom redirect URL', 'ultimate-member' ),
					'description'     => __( 'The URL denied visitors are sent to.', 'ultimate-member' ),
					'type'            => 'text',
					'option_category' => 'configuration',
					'default'         => '',
					'show_if'         => array(
						'um_restrict_enable'   => 'on',
						'um_restrict_redirect' => 'custom',
					),
					'tab_slug'        => 'custom_css',
					'toggle_slug'     => 'visibility',
					'priority'        => 55,
				),
			);

			return array_merge( $general_fields, $fields );
		}


		/**
		 * Role options for the multiple buttons field.
		 *
		 * @since 2.15.0
		 *
		 * @return array
		 */
		public function get_roles_options() {
			if ( null !== $this->roles_options ) {
				return $this->roles_options;
			}

			$options = array();

			$roles = UM()->roles()->get_roles();
			if ( ! is_array( $roles ) ) {
				$roles = array();
			}

			/**
			 * Filters the roles available in the Divi content restriction field.
			 *
			 * @since 2.15.0
			 * @hook  um_divi_restrict_roles
			 *
			 * @param {array} $roles Roles in the format 'role_key' => 'Role Label'.
			 *
			 * @return {array} Roles.
			 */
			$roles = apply_filters( 'um_divi_restrict_roles', $roles );

			foreach ( $roles as $role_key => $role_label ) {
				$options[ $role_key ] = array( 'title' => $role_label );
			}

			$this->roles_options = $options;

			return $options;
		}


		/**
		 * Account status options for the multiple buttons field.
		 *
		 * @since 2.15.0
		 *
		 * @return array
		 */
		public function get_statuses_options() {
			if ( null !== $this->statuses_options ) {
				return $this->statuses_options;
			}

			$options = array();

			/**
			 * Filters the account statuses available in the Divi content restriction field.
			 *
			 * @since 2.15.0
			 * @hook  um_divi_restrict_statuses
			 *
			 * @param {array} $statuses Account statuses in the format 'status_key' => 'Status Label'.
			 *
			 * @return {array} Account statuses.
			 */
			$statuses = apply_filters( 'um_divi_restrict_statuses', UM()->common()->users()->statuses_list() );

			foreach ( $statuses as $status_key => $status_label ) {
				$options[ $status_key ] = array( 'title' => $status_label );
			}

			$this->statuses_options = $options;

			return $options;
		}


		/**
		 * Hide the rendered element when the current visitor is not allowed to see it.
		 *
		 * Runs after Divi's own display conditions handler (priority 10) so both sets of
		 * conditions can hide the same element.
		 *
		 * @since 2.15.0
		 *
		 * @param string              $output           Rendered element output.
		 * @param string              $render_method    How the element is being rendered.
		 * @param \ET_Builder_Element $element_instance Current element instance.
		 *
		 * @return string
		 */
		public function maybe_hide_element( $output, $render_method, $element_instance ) {
			// Only touch real frontend output, never the data the builder renders for the editor.
			if ( 'render' !== $render_method ) {
				return $output;
			}

			if ( $this->is_restriction_bypassed() ) {
				return $output;
			}

			$props = $this->get_element_props( $element_instance );
			if ( empty( $props['um_restrict_enable'] ) || 'on' !== $props['um_restrict_enable'] ) {
				return $output;
			}

			if ( ! $this->user_can_view( $props ) ) {
				return '';
			}

			return $output;
		}


		/**
		 * Read the restriction props from an element instance.
		 *
		 * @since 2.15.0
		 *
		 * @param mixed $element_instance Element instance passed by the Divi filter.
		 *
		 * @return array
		 */
		public function get_element_props( $element_instance ) {
			if ( ! is_object( $element_instance ) || ! isset( $element_instance->props ) || ! is_array( $element_instance->props ) ) {
				return array();
			}

			return $element_instance->props;
		}


		/**
		 * Whether the current request must not be restricted.
		 *
		 * Users who can manage the site are always bypassed so they cannot lock themselves
		 * out of their own layouts. The Visual Builder and admin requests are bypassed so the
		 * element stays editable and visible while it is being built.
		 *
		 * @since 2.15.0
		 *
		 * @return bool
		 */
		public function is_restriction_bypassed() {
			if ( is_admin() ) {
				return true;
			}

			if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
				return true;
			}

			if ( function_exists( 'et_fb_is_enabled' ) && et_fb_is_enabled() ) {
				return true;
			}

			/**
			 * Filters whether the current request bypasses Divi content restrictions.
			 *
			 * @since 2.15.0
			 * @hook  um_divi_restrict_bypass
			 *
			 * @param {bool} $bypass True when restrictions are bypassed for this request.
			 *
			 * @return {bool} Bypass flag.
			 */
			if ( apply_filters( 'um_divi_restrict_bypass', false ) ) {
				return true;
			}

			$bypass_admin = true;

			/**
			 * Filters whether administrators bypass Divi content restrictions.
			 *
			 * @since 2.15.0
			 * @hook  um_divi_restrict_bypass_admin
			 *
			 * @param {bool} $bypass_admin True when administrators always see restricted elements.
			 *
			 * @return {bool} Administrator bypass flag.
			 */
			$bypass_admin = apply_filters( 'um_divi_restrict_bypass_admin', $bypass_admin );

			if ( $bypass_admin && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
				return true;
			}

			return false;
		}


		/**
		 * Decide whether the current visitor can see an element.
		 *
		 * All configured conditions have to be met. An empty condition is skipped, so
		 * only the conditions that were actually selected are evaluated.
		 *
		 * @since 2.15.0
		 *
		 * @param array $props Element props.
		 *
		 * @return bool
		 */
		public function user_can_view( $props ) {
			$can_view = true;

			// Login state.
			$who = isset( $props['um_restrict_who'] ) ? (string) $props['um_restrict_who'] : '0';

			if ( '1' === $who && ! is_user_logged_in() ) {
				$can_view = false;
			} elseif ( '2' === $who && is_user_logged_in() ) {
				$can_view = false;
			}

			$roles    = $this->parse_multi_value( isset( $props['um_restrict_roles'] ) ? $props['um_restrict_roles'] : '' );
			$statuses = $this->parse_multi_value( isset( $props['um_restrict_status'] ) ? $props['um_restrict_status'] : '' );

			if ( ! empty( $roles ) || ! empty( $statuses ) ) {
				if ( ! is_user_logged_in() ) {
					// Roles and account statuses only exist for logged in users.
					$can_view = false;
				} else {
					$user_id = get_current_user_id();

					if ( ! empty( $roles ) ) {
						$user       = get_userdata( $user_id );
						$user_roles = ( $user && ! empty( $user->roles ) ) ? array_values( $user->roles ) : array();

						if ( empty( array_intersect( $roles, $user_roles ) ) ) {
							$can_view = false;
						}
					}

					if ( $can_view && ! empty( $statuses ) ) {
						$account_status = UM()->common()->users()->get_status( $user_id );

						if ( ! in_array( $account_status, $statuses, true ) ) {
							$can_view = false;
						}
					}
				}
			}

			/**
			 * Filters whether the current visitor can see a restricted Divi element.
			 *
			 * @since 2.15.0
			 * @hook  um_divi_restrict_can_view
			 *
			 * @param {bool}  $can_view Whether the element can be shown.
			 * @param {array} $props    Element props.
			 *
			 * @return {bool} Whether the element can be shown.
			 */
			return (bool) apply_filters( 'um_divi_restrict_can_view', $can_view, $props );
		}


		/**
		 * Split a pipe separated multiple buttons value into a list.
		 *
		 * @since 2.15.0
		 *
		 * @param mixed $value Stored prop value.
		 *
		 * @return array
		 */
		public function parse_multi_value( $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				return array();
			}

			$values = array_map( 'trim', explode( '|', $value ) );

			return array_values( array_filter( $values, 'strlen' ) );
		}


		/**
		 * Redirect a denied visitor when the element asks for it.
		 *
		 * Headers are already sent by the time elements render, so the redirect has to be
		 * resolved from the stored shortcode attributes before output starts.
		 *
		 * @since 2.15.0
		 */
		public function maybe_redirect() {
			if ( is_admin() || ! is_singular() ) {
				return;
			}

			if ( $this->is_restriction_bypassed() ) {
				return;
			}

			$post_id = get_queried_object_id();
			if ( empty( $post_id ) ) {
				return;
			}

			// Never send a visitor away from the UM core pages, it would break those flows.
			$post = get_post( $post_id );
			if ( um_is_core_post( $post, 'login' ) || um_is_core_post( $post, 'register' ) || um_is_core_post( $post, 'password-reset' ) ) {
				return;
			}

			if ( function_exists( 'et_pb_is_pagebuilder_used' ) && ! et_pb_is_pagebuilder_used( $post_id ) ) {
				return;
			}

			$content = get_post_field( 'post_content', $post_id );
			if ( ! is_string( $content ) || false === strpos( $content, 'um_restrict_enable' ) ) {
				return;
			}

			$attributes = $this->get_shortcode_attributes( $content );

			foreach ( $attributes as $atts ) {
				if ( empty( $atts['um_restrict_enable'] ) || 'on' !== $atts['um_restrict_enable'] ) {
					continue;
				}

				$redirect = isset( $atts['um_restrict_redirect'] ) ? $atts['um_restrict_redirect'] : 'none';
				if ( 'none' === $redirect ) {
					continue;
				}

				if ( $this->user_can_view( $atts ) ) {
					continue;
				}

				$this->redirect_denied_visitor( $redirect, isset( $atts['um_restrict_redirect_url'] ) ? $atts['um_restrict_redirect_url'] : '' );
			}
		}


		/**
		 * Extract the attribute string of every shortcode in the content and parse it.
		 *
		 * @since 2.15.0
		 *
		 * @param string $content Post content.
		 *
		 * @return array
		 */
		public function get_shortcode_attributes( $content ) {
			$attributes = array();

			$pattern = get_shortcode_regex();
			if ( empty( $pattern ) ) {
				return $attributes;
			}

			if ( ! preg_match_all( '/' . $pattern . '/s', $content, $matches ) ) {
				return $attributes;
			}

			if ( empty( $matches[3] ) ) {
				return $attributes;
			}

			foreach ( $matches[3] as $atts_string ) {
				if ( false === strpos( $atts_string, 'um_restrict_enable' ) ) {
					continue;
				}

				$atts = shortcode_parse_atts( $atts_string );
				if ( is_array( $atts ) ) {
					$attributes[] = $atts;
				}
			}

			return $attributes;
		}


		/**
		 * Send the visitor to the configured URL.
		 *
		 * @since 2.15.0
		 *
		 * @param string $redirect     Redirect type.
		 * @param string $custom_url   Custom redirect URL.
		 */
		public function redirect_denied_visitor( $redirect, $custom_url ) {
			$url = '';

			if ( 'login' === $redirect ) {
				$url = um_get_core_page( 'login' );

				if ( empty( $url ) ) {
					$url = wp_login_url();
				}
			} elseif ( 'custom' === $redirect ) {
				$url = esc_url_raw( trim( $custom_url ) );
			}

			/**
			 * Filters the URL denied visitors are redirected to.
			 *
			 * @since 2.15.0
			 * @hook  um_divi_restrict_redirect_url
			 *
			 * @param {string} $url          Redirect URL.
			 * @param {string} $redirect     Redirect type.
			 * @param {string} $custom_url   Custom redirect URL.
			 *
			 * @return {string} Redirect URL.
			 */
			$url = apply_filters( 'um_divi_restrict_redirect_url', $url, $redirect, $custom_url );

			if ( empty( $url ) ) {
				return;
			}

			// Never redirect to the page the visitor is already on, it would loop.
			if ( $this->is_current_url( $url ) ) {
				return;
			}

			// Two pages that redirect to each other would loop forever. A short-lived cookie
			// scoped to the target URL shows this visitor was just sent there, so stop the
			// second bounce. It is set only after the checks above passed.
			$loop_cookie = 'um_divi_bounced_' . substr( md5( $url ), 0, 8 );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput -- cookie presence marker only, its value is never trusted
			if ( isset( $_COOKIE[ $loop_cookie ] ) ) {
				return;
			}

			// wp_safe_redirect() only follows URLs on the site host. When the site owner
			// points a custom redirect somewhere else, let them allow that host explicitly.
			if ( 'custom' === $redirect ) {
				$host = wp_parse_url( $url, PHP_URL_HOST );
				if ( ! empty( $host ) ) {
					add_filter(
						'allowed_redirect_hosts',
						function ( $hosts ) use ( $host ) {
							$hosts[] = $host;

							return $hosts;
						}
					);
				}
			}

			setcookie( $loop_cookie, '1', time() + 30, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );

			wp_safe_redirect( $url );
			exit;
		}


		/**
		 * Whether a URL points to the current request.
		 *
		 * @since 2.15.0
		 *
		 * @param string $url URL to compare.
		 *
		 * @return bool
		 */
		public function is_current_url( $url ) {
			$current = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$current = wp_parse_url( $current, PHP_URL_PATH );
			$target  = wp_parse_url( $url, PHP_URL_PATH );

			if ( empty( $target ) ) {
				return false;
			}

			return untrailingslashit( (string) $current ) === untrailingslashit( (string) $target );
		}


		/**
		 * Rebuild Divi's cached element definitions when the field list changed.
		 *
		 * Fields added through the general fields filter are part of the cached module
		 * definition, so without this the new fields never show up on sites that already
		 * have a builder cache.
		 *
		 * @since 2.15.0
		 */
		public function maybe_regenerate_fields_cache() {
			if ( get_option( self::FIELDS_VERSION_OPTION ) === UM_VERSION ) {
				return;
			}

			if ( ! function_exists( 'et_pb_force_regenerate_templates' ) ) {
				return;
			}

			et_pb_force_regenerate_templates();

			update_option( self::FIELDS_VERSION_OPTION, UM_VERSION, false );
		}
	}
}
