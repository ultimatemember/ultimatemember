<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Permalinks' ) ) {

	/**
	 * Class Permalinks
	 * @package um\core
	 */
	class Permalinks {

		/**
		 * @var
		 */
		var $core;

		/**
		 * @var
		 */
		var $current_url;

		/**
		 * Permalinks constructor.
		 */
		public function __construct() {
			add_action( 'init',  array( &$this, 'set_current_url' ), 0 );

			add_action( 'init',  array( &$this, 'check_for_querystrings' ), 1 );

			add_action( 'init',  array( &$this, 'activate_account_via_email_link' ), 1 );
		}

		/**
		 * Set current URL variable
		 */
		public function set_current_url() {
			$this->current_url = $this->get_current_url();
		}

		/**
		 * Get query as array
		 *
		 * @return array
		 */
		public function get_query_array() {
			$parts = parse_url( $this->get_current_url() );
			if ( isset( $parts['query'] ) ) {
				parse_str( $parts['query'], $query );
				return $query;
			}

			return array();
		}

		/**
		 * Get current URL anywhere
		 *
		 * @param bool $no_query_params
		 *
		 * @return string
		 */
		public function get_current_url( $no_query_params = false ) {
			//use WP native function for fill $_SERVER variables by correct values
			wp_fix_server_vars();

			//check if WP-CLI there isn't set HTTP_HOST, use localhost instead
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'localhost';
			} else {
				if ( isset( $_SERVER['HTTP_HOST'] ) ) {
					$host = $_SERVER['HTTP_HOST'];
				} else {
					$host = 'localhost';
				}
			}

			$page_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $_SERVER['REQUEST_URI'];

			if ( false !== $no_query_params ) {
				$page_url = strtok( $page_url, '?' );
			}

			/**
			 * Filters current page URL.
			 *
			 * @param {string} $page_url        Current page URL.
			 * @param {bool}   $no_query_params Ignore $_GET attributes in URL. false !== ignore.
			 *
			 * @return {string} Current page URL.
			 *
			 * @since 1.3.x
			 * @hook um_get_current_page_url
			 *
			 * @example <caption>Add your custom $_GET attribute to all links.</caption>
			 * function my_um_get_current_page_url( $page_url, $no_query_params ) {
			 *     $page_url = add_query_arg( '{attr_value}', '{attr_key}', $page_url ); // replace to your custom value and key.
			 *     return $page_url;
			 * }
			 * add_filter( 'um_get_current_page_url', 'my_um_get_current_page_url', 10, 2 );
			 */
			return apply_filters( 'um_get_current_page_url', $page_url, $no_query_params );
		}

		/**
		 * Activates an account via email
		 */
		public function activate_account_via_email_link() {
			if ( isset( $_REQUEST['act'] ) && 'activate_via_email' === sanitize_key( $_REQUEST['act'] ) && isset( $_REQUEST['hash'] ) && is_string( $_REQUEST['hash'] ) && strlen( $_REQUEST['hash'] ) == 40 &&
				 isset( $_REQUEST['user_id'] ) && is_numeric( $_REQUEST['user_id'] ) ) { // valid token

				$user_id = absint( $_REQUEST['user_id'] );
				if ( is_user_logged_in() && get_current_user_id() !== $user_id ) {
					// Cannot activate another user account. Please log out and try again.
					wp_safe_redirect( um_user_profile_url( get_current_user_id() ) );
					exit;
				}

				delete_option( "um_cache_userdata_{$user_id}" );

				$account_secret_hash = get_user_meta( $user_id, 'account_secret_hash', true );
				if ( empty( $account_secret_hash ) || strtolower( sanitize_text_field( $_REQUEST['hash'] ) ) !== strtolower( $account_secret_hash ) ) {
					wp_safe_redirect( add_query_arg( 'err', 'activation_link_used', um_get_core_page( 'login' ) ) );
					exit;
				}

				$account_secret_hash_expiry = get_user_meta( $user_id, 'account_secret_hash_expiry', true );
				if ( ! empty( $account_secret_hash_expiry ) && time() > $account_secret_hash_expiry ) {
					wp_safe_redirect( add_query_arg( 'err', 'activation_link_expired', um_get_core_page( 'login' ) ) );
					exit;
				}

				$redirect              = um_get_core_page( 'login', 'account_active' );
				$set_password_required = get_user_meta( $user_id, 'um_set_password_required', true );

				um_fetch_user( $user_id );
				UM()->common()->users()->approve( $user_id, true );
				if ( ! empty( $set_password_required ) ) {
					$redirect = um_user( 'password_reset_link' );
				}
				um_reset_user();

				$user_role      = UM()->roles()->get_priority_user_role( $user_id );
				$user_role_data = UM()->roles()->role_data( $user_role );

				// log in automatically
				$login = ! empty( $user_role_data['login_email_activate'] ); // Role setting "Login user after validating the activation link?"
				if ( ! is_user_logged_in() && $login ) {
					UM()->user()->auto_login( $user_id );
				}

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_email_confirmation
				 * @description Action on user activation
				 * @input_vars
				 * [{"var":"$user_id","type":"int","desc":"User ID"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_after_email_confirmation', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_after_email_confirmation', 'my_after_email_confirmation', 10, 1 );
				 * function my_after_email_confirmation( $user_id ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_email_confirmation', $user_id );

				if ( empty( $set_password_required ) ) {
					$redirect = empty( $user_role_data['url_email_activate'] ) ? um_get_core_page( 'login', 'account_active' ) : trim( $user_role_data['url_email_activate'] ); // Role setting "URL redirect after email activation"
				}
				$redirect = apply_filters( 'um_after_email_confirmation_redirect', $redirect, $user_id, $login );

				exit( wp_redirect( $redirect ) );
			}
		}

		/**
		 * Makes an activate link for any user
		 *
		 * @return bool|string
		 */
		public function activate_url() {
			if ( ! um_user( 'account_secret_hash' ) ) {
				return false;
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_activate_url
			 * @description Change activate user URL
			 * @input_vars
			 * [{"var":"$url","type":"string","desc":"Activate URL"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_activate_url', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_activate_url', 'my_activate_url', 10, 1 );
			 * function my_activate_url( $url ) {
			 *     // your code here
			 *     return $url;
			 * }
			 * ?>
			 */
			$url =  apply_filters( 'um_activate_url', home_url() );
			$url =  add_query_arg( 'act', 'activate_via_email', $url );
			$url =  add_query_arg( 'hash', um_user( 'account_secret_hash' ), $url );
			$url =  add_query_arg( 'user_id', um_user( 'ID' ), $url );

			return $url;
		}

		/**
		 * Checks for UM query strings
		 */
		public function check_for_querystrings() {
			if ( isset( $_REQUEST['message'] ) ) {
				UM()->shortcodes()->message_mode = true;
			}
		}

		/**
		 * Add a query param to url
		 *
		 * @param $key
		 * @param $value
		 *
		 * @return string
		 */
		public function add_query( $key, $value ) {
			$this->current_url = add_query_arg( $key, $value, $this->get_current_url() );
			return $this->current_url;
		}

		/**
		 * Remove a query param from url
		 *
		 * @param $key
		 * @param $value
		 *
		 * @return string
		 */
		public function remove_query( $key, $value ) {
			$this->current_url = remove_query_arg( $key, $this->current_url );
			return $this->current_url;
		}

		/**
		* @param string $slug
		*
		* @return int|bool
		*/
		public function slug_exists_user_id( $slug ) {
			global $wpdb;

			$permalink_base = UM()->options()->get( 'permalink_base' );
			if ( 'custom_meta' === $permalink_base ) {
				$custom_meta = UM()->options()->get( 'permalink_base_custom_meta' );
				if ( empty( $custom_meta ) ) {
					// Set default permalink base if custom meta is empty.
					$permalink_base = 'user_login';
					$meta_key       = 'um_user_profile_url_slug_' . $permalink_base;
				} else {
					$meta_key = $custom_meta;
				}
			} else {
				$meta_key = 'um_user_profile_url_slug_' . $permalink_base;
			}

			$user_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT user_id
					FROM {$wpdb->usermeta}
					WHERE meta_key = %s AND
						  meta_value = %s
					ORDER BY umeta_id ASC
					LIMIT 1",
					$meta_key,
					$slug
				)
			);

			if ( ! empty( $user_id ) ) {
				return absint( $user_id );
			}

			return false;
		}

		/**
		 * Get Profile Permalink
		 *
		 * @param  string $slug
		 *
		 * @return string
		 */
		public function profile_permalink( $slug ) {
			/**
			 * Filters user profile URL externally with own logic.
			 *
			 * @since 2.6.3
			 * @hook um_external_profile_url
			 *
			 * @param {bool|string} $profile_url Profile URL.
			 * @param {string}      $slug        User profile slug.
			 *
			 * @return {string} Profile URL.
			 *
			 * @example <caption>Change profile URL to your custom link and ignore native profile permalink handlers.</caption>
			 * function my_um_external_profile_url( $profile_url, $slug ) {
			 *     $profile_url = '{some your custom URL}'; // replace to your custom link.
			 *     return $profile_url;
			 * }
			 * add_filter( 'um_external_profile_url', 'my_um_external_profile_url', 10, 2 );
			 */
			$external_profile_url = apply_filters( 'um_external_profile_url', false, $slug );
			if ( false !== $external_profile_url ) {
				return ! empty( $external_profile_url ) ? $external_profile_url : '';
			}

			$page_id     = UM()->config()->permalinks['user'];
			$profile_url = get_permalink( $page_id );

			/**
			 * Filters the base URL of the UM profile page.
			 *
			 * @since 1.3.x
			 * @deprecated 2.6.3 Use <a href="https://developer.wordpress.org/reference/hooks/post_link/" target="_blank" title="'post_link' hook article on developer.wordpress.org">'post_link'</a> instead.
			 * @hook um_localize_permalink_filter
			 * @todo fully remove since 2.7.0
			 *
			 * @param {string} $profile_url Profile URL.
			 * @param {int}    $page_id     Profile Page ID.
			 *
			 * @return {string} Profile URL.
			 *
			 * @example <caption>Change profile base URL to your custom link.</caption>
			 * function my_localize_permalink( $profile_url, $page_id ) {
			 *     $profile_url = '{some your custom URL}'; // replace to your custom link.
			 *     return $profile_url;
			 * }
			 * add_filter( 'um_localize_permalink_filter', 'my_localize_permalink', 10, 2 );
			 */
			$profile_url = apply_filters( 'um_localize_permalink_filter', $profile_url, $page_id );

			if ( UM()->is_permalinks ) {
				$profile_url  = trailingslashit( untrailingslashit( $profile_url ) );
				$profile_url .= trailingslashit( strtolower( $slug ) );
			} else {
				$profile_url = add_query_arg( 'um_user', strtolower( $slug ), $profile_url );
			}

			/**
			 * Filters user profile URL.
			 *
			 * @since 2.6.3
			 * @hook um_profile_permalink
			 *
			 * @param {string} $profile_url Profile URL.
			 * @param {int}    $page_id     Profile Page ID.
			 * @param {string} $slug        User profile slug.
			 *
			 * @return {string} Profile URL.
			 *
			 * @example <caption>Change profile URL to your custom link.</caption>
			 * function my_um_profile_permalink( $profile_url, $page_id, $slug ) {
			 *     $profile_url = '{some your custom URL}'; // replace to your custom link.
			 *     return $profile_url;
			 * }
			 * add_filter( 'um_profile_permalink', 'my_um_profile_permalink', 10, 3 );
			 */
			$profile_url = apply_filters( 'um_profile_permalink', $profile_url, $page_id, $slug );

			return ! empty( $profile_url ) ? $profile_url : '';
		}

		/**
		 * Generate profile slug
		 *
		 * @param string $full_name
		 * @param string $first_name
		 * @param string $last_name
		 * @return string
		 */
		public function profile_slug( $full_name, $first_name, $last_name ) {
			$permalink_base = UM()->options()->get( 'permalink_base' );

			$user_in_url = '';

			$full_name = str_replace( array( "'", '&', '/' ), '', $full_name );

			switch ( $permalink_base ) {
				case 'name': // dotted
					$full_name_slug = $full_name;
					$difficulties = 0;

					if ( strpos( $full_name, '.' ) > -1 ) {
						$full_name = str_replace( ".", "_", $full_name );
						$difficulties++;
					}

					$full_name = strtolower( str_replace( " ", ".", $full_name ) );

					if ( strpos( $full_name, '_.' ) > -1 ) {
						$full_name = str_replace( '_.', '_', $full_name );
						$difficulties++;
					}

					$full_name_slug = str_replace( '-', '.', $full_name_slug );
					$full_name_slug = str_replace( ' ', '.', $full_name_slug );
					$full_name_slug = str_replace( '..', '.', $full_name_slug );

					if ( strpos( $full_name, '.' ) > -1 ) {
						$full_name = str_replace( '.', ' ', $full_name );
						$difficulties++;
					}

					$user_in_url = rawurlencode( $full_name_slug );

					break;

				case 'name_dash': // dashed
					$difficulties = 0;

					$full_name_slug = strtolower( $full_name );

					// if last name has dashed replace with underscore
					if ( strpos( $last_name, '-' ) > -1 && strpos( $full_name, '-' ) > -1 ) {
						$difficulties++;
						$full_name = str_replace( '-', '_', $full_name );
					}
					// if first name has dashed replace with underscore
					if ( strpos( $first_name, '-' ) > -1 && strpos( $full_name, '-' ) > -1 ) {
						$difficulties++;
						$full_name = str_replace( '-', '_', $full_name );
					}
					// if name has space, replace with dash
					$full_name_slug = str_replace( ' ', '-', $full_name_slug );

					// if name has period
					if ( strpos( $last_name, '.' ) > -1 && strpos( $full_name, '.' ) > -1 ) {
						$difficulties++;
					}

					$full_name_slug = str_replace( '.', '-', $full_name_slug );
					$full_name_slug = str_replace( '--', '-', $full_name_slug );

					$user_in_url = rawurlencode( $full_name_slug );

					break;

				case 'name_plus': // plus
					$difficulties = 0;

					$full_name_slug = strtolower( $full_name );

					// if last name has dashed replace with underscore
					if ( strpos( $last_name, '+' ) > -1 && strpos( $full_name, '+' ) > -1 ) {
						$difficulties++;
						$full_name = str_replace( '-', '_', $full_name );
					}
					// if first name has dashed replace with underscore
					if ( strpos( $first_name, '+' ) > -1 && strpos( $full_name, '+' ) > -1 ) {
						$difficulties++;
						$full_name = str_replace( '-', '_', $full_name );
					}
					if ( strpos( $last_name, '-' ) > -1 || strpos( $first_name, '-' ) > -1 || strpos( $full_name, '-' ) > -1 ) {
						$difficulties++;
					}
					// if name has space, replace with dash
					$full_name_slug = str_replace( ' ', '+', $full_name_slug );
					$full_name_slug = str_replace( '-', '+', $full_name_slug );

					// if name has period
					if ( strpos( $last_name, '.' ) > -1 && strpos( $full_name, '.' ) > -1 ) {
						$difficulties++;
					}

					$full_name_slug = str_replace( '.', '+', $full_name_slug );
					$full_name_slug = str_replace( '++', '+', $full_name_slug );

					$user_in_url = $full_name_slug;

					break;
			}

			return $user_in_url;
		}
	}
}
