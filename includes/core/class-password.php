<?php
namespace um\core;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Password' ) ) {


	/**
	 * Class Password
	 * @package um\core
	 */
	class Password {


		/**
		 * Password constructor.
		 */
		function __construct() {
			add_action( 'template_redirect', array( &$this, 'form_init' ), 10001 );
			add_action( 'um_change_password_errors_hook', array( &$this, 'um_change_password_errors_hook' ) );
			add_action( 'um_change_password_process_hook', array( &$this,'um_change_password_process_hook' ) );
		}


		/**
		 * Check if a legitimate password change request is in action
		 *
		 *
		 * @return bool
		 */
		function is_change_request() {
			if ( isset( $_POST['_um_account'] ) == 1 && isset( $_POST['_um_account_tab'] ) && sanitize_key( $_POST['_um_account_tab'] ) === 'password' ) {
				return true;
			}
			return false;
		}


		/**
		 * Password page form
		 */
		public function form_init() {
			// handle here Account page for now
			if ( $this->is_change_request() ) {
				UM()->form()->post_form = $_POST;

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_change_password_errors_hook
				 * @description Action on change password submit form
				 * @input_vars
				 * [{"var":"$post","type":"array","desc":"Form submitted"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_change_password_errors_hook', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_change_password_errors_hook', 'my_change_password_errors', 10, 1 );
				 * function my_change_password_errors( $post ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_change_password_errors_hook', UM()->form()->post_form );

				if ( ! isset( UM()->form()->errors ) ) {

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_change_password_process_hook
					 * @description Action on change password success submit form
					 * @input_vars
					 * [{"var":"$post","type":"array","desc":"Form submitted"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_change_password_process_hook', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_change_password_process_hook', 'my_change_password_process', 10, 1 );
					 * function my_change_password_process( $post ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_change_password_process_hook', UM()->form()->post_form );

				}
			}
		}


		/**
		 * Error handler: changing password
		 *
		 * @param $args
		 */
		public function um_change_password_errors_hook( $args ) {
			if ( isset( $args[ UM()->honeypot ] ) && '' !== $args[ UM()->honeypot ] ) {
				wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
			}

			if ( ! is_user_logged_in() && isset( $args ) && ! um_is_predefined_page( 'password-reset' ) ||
			     is_user_logged_in() && isset( $args['user_id'] ) && absint( $args['user_id'] ) !== get_current_user_id() ) {
				wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
			}

			if ( isset( $args['user_password'] ) && empty( $args['user_password'] ) ) {
				UM()->form()->add_error( 'user_password', __( 'You must enter a new password', 'ultimate-member' ) );
			}

			if ( isset( $args['user_password'] ) ) {
				$args['user_password'] = sanitize_text_field( $args['user_password'] );
			}
			if ( isset( $args['confirm_user_password'] ) ) {
				$args['confirm_user_password'] = sanitize_text_field( $args['confirm_user_password'] );
			}

			if ( UM()->options()->get( 'require_strongpass' ) ) {

				$min_length = UM()->options()->get( 'password_min_chars' );
				$min_length = ! empty( $min_length ) ? $min_length : 8;
				$max_length = UM()->options()->get( 'password_max_chars' );
				$max_length = ! empty( $max_length ) ? $max_length : 30;

				if ( mb_strlen( $args['user_password'] ) < $min_length ) {
					UM()->form()->add_error( 'user_password', sprintf( __( 'Your password must contain at least %d characters', 'ultimate-member' ), $min_length ) );
				}

				if ( mb_strlen( $args['user_password'] ) > $max_length ) {
					UM()->form()->add_error( 'user_password', sprintf( __( 'Your password must contain less than %d characters', 'ultimate-member' ), $max_length ) );
				}

				if ( ! UM()->validation()->strong_pass( $args['user_password'] ) ) {
					UM()->form()->add_error( 'user_password', __( 'Your password must contain at least one lowercase letter, one capital letter and one number', 'ultimate-member' ) );
				}
			}

			if ( isset( $args['confirm_user_password'] ) && empty( $args['confirm_user_password'] ) ) {
				UM()->form()->add_error( 'confirm_user_password', __( 'You must confirm your new password', 'ultimate-member' ) );
			}

			if ( isset( $args['user_password'] ) && isset( $args['confirm_user_password'] ) && $args['user_password'] !== $args['confirm_user_password'] ) {
				UM()->form()->add_error( 'confirm_user_password', __( 'Your passwords do not match', 'ultimate-member' ) );
			}

		}


		/**
		 * Process a change request
		 *
		 * @param $args
		 */
		public function um_change_password_process_hook( $args ) {
			if ( isset( $args['_um_password_change'] ) && $args['_um_password_change'] == 1 ) {

				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
				$user = get_userdata( absint( $args['user_id'] ) );

				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

					if ( $user->user_login != $rp_login ) {
						$user = false;
					} else {
						$user = check_password_reset_key( $rp_key, $rp_login );
						if ( isset( $args['user_password'] ) && ! hash_equals( $rp_key, $args['rp_key'] ) ) {
							$user = false;
						}
					}
				} else {
					$user = false;
				}

				if ( ! $user || is_wp_error( $user ) ) {
					UM()->setcookie( $rp_cookie, false );
					if ( $user && $user->get_error_code() === 'expired_key' ) {
						wp_redirect( add_query_arg( array( 'updated' => 'expiredkey' ), get_permalink() ) );
					} else {
						wp_redirect( add_query_arg( array( 'updated' => 'invalidkey' ), get_permalink() ) );
					}
					exit;
				}


				$errors = new \WP_Error();
				/**
				 * Fires before the password reset procedure is validated.
				 *
				 * @since 3.5.0
				 *
				 * @param object           $errors WP Error object.
				 * @param \WP_User|\WP_Error $user   WP_User object if the login and reset key match. WP_Error object otherwise.
				 */
				do_action( 'validate_password_reset', $errors, $user );

				if ( ( ! $errors->get_error_code() ) ) {
					reset_password( $user, sanitize_text_field( $args['user_password'] ) );

					// send the Password Changed Email
					UM()->user()->password_changed();

					UM()->common()->user()->flush_reset_password_attempts( $user->ID );

					UM()->setcookie( $rp_cookie, false );

					// logout
					if ( is_user_logged_in() ) {
						wp_logout();
					}

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_after_changing_user_password
					 * @description Hook that runs after user change their password
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_after_changing_user_password', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_after_changing_user_password', 'my_after_changing_user_password', 10, 1 );
					 * function my_user_login_extra( $user_id ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_after_changing_user_password', absint( $args['user_id'] ) );

					exit( wp_redirect( add_query_arg( array( 'updated' => 'password_changed' ), um_get_predefined_page_url('login' ) ) ) );
				}
			}
		}


		/**
		 * Disable page caching and set or clear cookie
		 *
		 * @param string $name
		 * @param string $value
		 * @param int $expire
		 * @param string $path
		 */
		public function setcookie( $name, $value = '', $expire = 0, $path = '' ) {
			if ( empty( $value ) ) {
				$expire = time() - YEAR_IN_SECONDS;
			}
			if ( empty( $path ) ) {
				list( $path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
			}

			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}

			nocache_headers();
			setcookie( $name, $value, $expire, $path, COOKIE_DOMAIN, is_ssl(), true );
		}


		/**
		 * UM Placeholders for reset password
		 *
		 * @param $placeholders
		 *
		 * @return array
		 */
		function add_placeholder( $placeholders ) {
			$placeholders[] = '{password_reset_link}';
			$placeholders[] = '{password}';
			return $placeholders;
		}


		/**
		 * UM Replace Placeholders for reset password
		 *
		 * @param $replace_placeholders
		 *
		 * @return array
		 */
		function add_replace_placeholder( $replace_placeholders ) {
			$replace_placeholders[] = um_user( 'password_reset_link' );
			$replace_placeholders[] = esc_html__( 'Your set password', 'ultimate-member' );
			return $replace_placeholders;
		}
	}
}
