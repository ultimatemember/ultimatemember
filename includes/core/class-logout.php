<?php
namespace um\core;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Logout' ) ) {


	/**
	 * Class Logout
	 * @package um\core
	 */
	class Logout {


		/**
		 * Logout constructor.
		 */
		function __construct() {
			add_action( 'template_redirect', array( &$this, 'logout_page' ), 10000 );

			// Add the logout nonce to the menu links of the Logout page.
			add_filter( 'nav_menu_link_attributes', array( &$this, 'add_nonce_to_menu_link' ), 10, 2 );
			add_filter( 'render_block_data', array( &$this, 'add_nonce_to_navigation_block' ) );
		}

		/**
		 * Check whether the URL points to the Logout page.
		 *
		 * @since 2.14.4
		 *
		 * @param string $url URL.
		 *
		 * @return bool
		 */
		public function is_logout_page_url( $url ) {
			$logout_url = um_get_core_page( 'logout' );
			if ( empty( $url ) || empty( $logout_url ) ) {
				return false;
			}

			$normalize = static function ( $value ) {
				$parts = wp_parse_url( $value );
				if ( empty( $parts ) ) {
					return '';
				}

				$host = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
				$path = isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';

				// Plain permalinks: the page is identified by the `page_id` query argument.
				$page_id = '';
				if ( ! empty( $parts['query'] ) ) {
					wp_parse_str( $parts['query'], $query );
					if ( ! empty( $query['page_id'] ) ) {
						$page_id = absint( $query['page_id'] );
					}
				}

				return $host . $path . '|' . $page_id;
			};

			return $normalize( $url ) === $normalize( $logout_url );
		}

		/**
		 * Add the logout nonce to a URL of the Logout page, so the user is logged out without confirmation.
		 *
		 * @since 2.14.4
		 *
		 * @param string $url URL.
		 *
		 * @return string
		 */
		public function maybe_add_nonce( $url ) {
			if ( ! is_user_logged_in() || ! $this->is_logout_page_url( $url ) ) {
				return $url;
			}

			return add_query_arg( '_wpnonce', wp_create_nonce( 'log-out' ), $url );
		}

		/**
		 * Classic menus: add the logout nonce to the links of the Logout page.
		 *
		 * @since 2.14.4
		 *
		 * @param array    $atts Link attributes.
		 * @param \WP_Post $item Menu item.
		 *
		 * @return array
		 */
		public function add_nonce_to_menu_link( $atts, $item ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Filter signature.
			if ( ! empty( $atts['href'] ) ) {
				$atts['href'] = $this->maybe_add_nonce( $atts['href'] );
			}
			return $atts;
		}

		/**
		 * Navigation block: add the logout nonce to the links of the Logout page.
		 *
		 * @since 2.14.4
		 *
		 * @param array $parsed_block Parsed block.
		 *
		 * @return array
		 */
		public function add_nonce_to_navigation_block( $parsed_block ) {
			if ( empty( $parsed_block['blockName'] ) || ! in_array( $parsed_block['blockName'], array( 'core/navigation-link', 'core/navigation-submenu' ), true ) ) {
				return $parsed_block;
			}

			if ( ! empty( $parsed_block['attrs']['url'] ) ) {
				$parsed_block['attrs']['url'] = $this->maybe_add_nonce( $parsed_block['attrs']['url'] );
			}

			return $parsed_block;
		}

		/**
		 * Verify the logout nonce of the current request.
		 *
		 * @since 2.14.4
		 *
		 * @return bool
		 */
		private function is_valid_request() {
			/**
			 * Filters whether the Logout page verifies the logout nonce.
			 *
			 * Without a valid nonce the user isn't logged out right away, but is asked to confirm the logout.
			 * It protects against logging out users by a link or an image on another site (CSRF).
			 *
			 * @param {bool} $allow_nonce_verification Verify the nonce. Default true.
			 *
			 * @return {bool} Whether to verify the nonce.
			 *
			 * @since 2.14.4
			 * @hook um_logout_allow_nonce_verification
			 *
			 * @example <caption>Log out without the nonce verification (not recommended).</caption>
			 * add_filter( 'um_logout_allow_nonce_verification', '__return_false' );
			 */
			$allow_nonce_verification = apply_filters( 'um_logout_allow_nonce_verification', true );
			if ( ! $allow_nonce_verification ) {
				return true;
			}

			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
			return false !== wp_verify_nonce( $nonce, 'log-out' );
		}

		/**
		 * Ask the user to confirm the logout (request without a valid nonce).
		 *
		 * @since 2.14.4
		 */
		private function confirm_logout() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce is verified on the confirmed request.
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

			$html = '<p>' . sprintf(
				/* translators: %s: Site title. */
				esc_html__( 'You are attempting to log out of %s', 'ultimate-member' ),
				esc_html( get_bloginfo( 'name' ) )
			) . '</p>';
			$html .= '<p>' . sprintf(
				/* translators: %s: Logout URL. */
				wp_kses( __( 'Do you really want to <a href="%s">log out</a>?', 'ultimate-member' ), array( 'a' => array( 'href' => array() ) ) ),
				esc_url( um_get_logout_url( $redirect_to ) )
			) . '</p>';

			wp_die( $html, esc_html__( 'Log out', 'ultimate-member' ), array( 'response' => 403 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		}


		/**
		 * @param $redirect_url
		 * @param $status
		 *
		 * @return false|string
		 */
		function safe_redirect_default( $redirect_url, $status ) {
			$login_page_id = UM()->config()->permalinks['login'];
			return get_permalink( $login_page_id );
		}


		/**
		 * Logout via logout page
		 */
		function logout_page() {
			if ( is_home() ) {
				return;
			}

			$trid = 0;
			//$language_code = '';
			if ( UM()->external_integrations()->is_wpml_active() ) {
				global $sitepress;
				$default_lang = $sitepress->get_default_language();

				/*$language_code = $sitepress->get_current_language();
				if ( $language_code == $default_lang ) {
					$language_code = '';
				}*/

				$current_page_ID = get_the_ID();
				if ( function_exists( 'icl_object_id' ) ) {
					$trid = icl_object_id( $current_page_ID, 'page', true, $default_lang );
				} else {
					$trid = wpml_object_id_filter( $current_page_ID, 'page', true, $default_lang );
				}
			}

			$logout_page_id = UM()->config()->permalinks['logout'];
			if ( um_is_core_page( 'logout' ) || ( $trid > 0 && $trid == $logout_page_id ) ) {

				if ( is_user_logged_in() ) {

					if ( ! $this->is_valid_request() ) {
						$this->confirm_logout();
						return;
					}

					add_filter( 'wp_safe_redirect_fallback', array( &$this, 'safe_redirect_default' ), 10, 2 );

					if ( isset( $_REQUEST['redirect_to'] ) && '' !== $_REQUEST['redirect_to'] ) {
						wp_destroy_current_session();
						wp_logout();
						session_unset();
						um_safe_redirect( esc_url_raw( $_REQUEST['redirect_to'] ) );
					} elseif ( 'redirect_home' === um_user( 'after_logout' ) ) {
						wp_destroy_current_session();
						wp_logout();
						session_unset();
						wp_safe_redirect( home_url() );
						exit;
					} else {
						/**
						 * Filters URL for redirect after logout.
						 *
						 * @param {string} $logout_redirect_url URL for redirect after logout.
						 * @param {int}    $user_id             User ID who logged out.
						 *
						 * @return {string} Redirect URL.
						 *
						 * @since 2.0
						 * @hook um_logout_redirect_url
						 *
						 * @example <caption>Change URL for redirect after logout.</caption>
						 * function my_logout_redirect_url( $logout_redirect_url, $user_id ) {
						 *     return '{your_custom_url}';
						 * }
						 * add_filter( 'um_logout_redirect_url', 'my_logout_redirect_url', 10, 2 );
						 */
						$redirect_url = apply_filters( 'um_logout_redirect_url', um_user( 'logout_redirect_url' ), um_user( 'ID' ) );
						wp_destroy_current_session();
						wp_logout();
						session_unset();
						um_safe_redirect( $redirect_url );
					}
				} else {
					add_filter( 'wp_safe_redirect_fallback', array( &$this, 'safe_redirect_default' ), 10, 2 );
					wp_safe_redirect( home_url() );
					exit;
				}
			}

		}

	}
}
