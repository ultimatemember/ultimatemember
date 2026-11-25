<?php
namespace um\core;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Password' ) ) {

	/**
	 * Class Password
	 * @package um\core
	 */
	class Password {

		/**
		 * @var bool
		 */
		private $change_password = false;

		/**
		 * Password constructor.
		 */
		public function __construct() {
			add_shortcode( 'ultimatemember_password', array( &$this, 'ultimatemember_password' ) );

			add_action( 'template_redirect', array( &$this, 'set_password_mode' ), 10001 );
			add_action( 'template_redirect', array( &$this, 'handle_reset_password_form' ), 10002 );
			add_action( 'template_redirect', array( &$this, 'handle_change_password_form' ), 10002 );

			add_action( 'um_reset_password_errors_hook', array( &$this, 'um_reset_password_errors_hook' ) );
			add_action( 'um_reset_password_process_hook', array( &$this, 'um_reset_password_process_hook' ) );

			add_action( 'um_change_password_errors_hook', array( &$this, 'um_change_password_errors_hook' ) );
			add_action( 'um_change_password_process_hook', array( &$this, 'um_change_password_process_hook' ) );
		}

		/**
		 * Get Reset URL
		 *
		 * @param int|null $user_id
		 *
		 * @return string
		 */
		public function reset_url( $user_id = null ) {
			static $reset_keys = array();

			if ( is_null( $user_id ) ) {
				$user_id = um_user( 'ID' );
			}

			delete_option( "um_cache_userdata_{$user_id}" );

			// New reset password key via WordPress native field. It maybe already exists here but generated twice to make sure that emailed with a proper and fresh hash.
			// But doing that only once in 1 request using static variable. Different email placeholders can use reset_url() and we have to use 1 time generated to avoid invalid keys.
			$user_data = get_userdata( $user_id );
			if ( empty( $user_data ) ) {
				return '';
			}

			if ( empty( $reset_keys[ $user_id ] ) ) {
				$reset_keys[ $user_id ] = UM()->common()->users()->maybe_generate_password_reset_key( $user_data );
			}

			// This link looks like WordPress native link e.g. wp-login.php?action=rp&key={hash}&login={user_login}
			return add_query_arg(
				array(
					'act'   => 'reset_password',
					'hash'  => $reset_keys[ $user_id ],
					'login' => rawurlencode( $user_data->user_login ),
				),
				um_get_core_page( 'password-reset' )
			);
		}


		/**
		 * Add class based on shortcode
		 *
		 * @param string $mode
		 *
		 * @return string
		 */
		function get_class( $mode ) {

			$classes = 'um-'.$mode;

			if ( is_admin() ) {
				$classes .= ' um-in-admin';
			}

			if ( true === UM()->fields()->editing ) {
				$classes .= ' um-editing';
			}

			if ( true === UM()->fields()->viewing ) {
				$classes .= ' um-viewing';
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_form_official_classes__hook
			 * @description Change form additional classes
			 * @input_vars
			 * [{"var":"$classes","type":"string","desc":"Form additional classes"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_form_official_classes__hook', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_form_official_classes__hook', 'my_form_official_classes', 10, 1 );
			 * function my_form_official_classes( $classes ) {
			 *     // your code here
			 *     return $classes;
			 * }
			 * ?>
			 */
			$classes = apply_filters( 'um_form_official_classes__hook', $classes );
			return $classes;
		}

		/**
		 * Shortcode
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function ultimatemember_password( $args = array() ) {
			/** There is possible to use 'shortcode_atts_ultimatemember_password' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
			$args = shortcode_atts(
				array(
					'template'  => 'password-reset',
					'mode'      => 'password',
					'form_id'   => 'um_password_id',
					'max_width' => '450px',
					'align'     => 'center',
				),
				$args,
				'ultimatemember_password'
			);

			if ( empty( $args['use_custom_settings'] ) ) {
				$args = array_merge( $args, UM()->shortcodes()->get_css_args( $args ) );
			} else {
				$args = array_merge( UM()->shortcodes()->get_css_args( $args ), $args );
			}
			/**
			 * Filters extend Reset Password Arguments
			 *
			 * @since 1.3.x
			 * @hook  um_reset_password_shortcode_args_filter
			 *
			 * @param {array} $args Shortcode arguments.
			 *
			 * @return {array} Shortcode arguments.
			 *
			 * @example <caption>Extend Reset Password Arguments.</caption>
			 * function my_reset_password_shortcode_args( $args ) {
			 *     // your code here
			 *     return $args;
			 * }
			 * add_filter( 'um_reset_password_shortcode_args_filter', 'my_reset_password_shortcode_args', 10, 1 );
			 */
			$args = apply_filters( 'um_reset_password_shortcode_args_filter', $args );

			if ( false !== $this->change_password ) {
				// then COOKIE are valid then get data from them and populate hidden fields for the password reset form
				$args['rp_mode']  = 'pw_change';
				$args['template'] = 'password-change';
				$args['rp_key']   = '';
				$rp_cookie        = 'wp-resetpass-' . COOKIEHASH;
				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

					$args['login']  = $rp_login;
					$args['rp_key'] = $rp_key;

					$rp_user_obj = get_user_by( 'login', $rp_login );
					if ( false !== $rp_user_obj ) {
						$set_password_required = get_user_meta( $rp_user_obj->ID, 'um_set_password_required', true );
						if ( ! empty( $set_password_required ) ) {
							$args['rp_mode'] = 'pw_set';
						}
					}
				}
			}

			if ( empty( $args['mode'] ) || empty( $args['template'] ) ) {
				return '';
			}

			UM()->fields()->set_id = absint( $args['form_id'] );

			ob_start();

			/** This filter is documented in includes/core/class-shortcodes.php */
			do_action( "um_pre_{$args['mode']}_shortcode", $args );
			/** This filter is documented in includes/core/class-shortcodes.php */
			do_action( 'um_before_form_is_loaded', $args );
			/** This filter is documented in includes/core/class-shortcodes.php */
			do_action( "um_before_{$args['mode']}_form_is_loaded", $args );

			UM()->shortcodes()->template_load( $args['template'], $args );

			if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
				UM()->shortcodes()->dynamic_css( $args );
			}

			return ob_get_clean();
		}

		/**
		 * Set mode on the password page and validate $rp_cookie.
		 *
		 * @return void
		 */
		public function set_password_mode() {
			if ( um_is_core_page( 'password-reset' ) ) {
				UM()->fields()->set_mode = 'password';

				// validate $rp_cookie and hash via check_password_reset_key
				if ( isset( $_REQUEST['act'] ) && 'reset_password' === sanitize_key( $_REQUEST['act'] ) ) {
					wp_fix_server_vars();

					$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

					if ( isset( $_GET['hash'] ) && isset( $_GET['login'] ) ) {
						$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['hash'] ) );
						$this->setcookie( $rp_cookie, $value );
						// Not `um_safe_redirect()` because password-reset page is predefined page and is situated on the same host.
						wp_safe_redirect( remove_query_arg( array( 'hash', 'login' ) ) );
						exit;
					}

					if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
						list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

						$user = check_password_reset_key( $rp_key, $rp_login );

						if ( isset( $_POST['user_password'] ) && ! hash_equals( $rp_key, $_POST['rp_key'] ) ) {
							$user = false;
						}
					} else {
						$user = false;
					}

					if ( ! $user || is_wp_error( $user ) ) {
						$this->setcookie( $rp_cookie, false );
						if ( $user && 'expired_key' === $user->get_error_code() ) {
							// Not `um_safe_redirect()` because password-reset page is predefined page and is situated on the same host.
							wp_safe_redirect(
								add_query_arg(
									array( 'updated' => 'expiredkey' ),
									um_get_core_page( 'password-reset' )
								)
							);
						} else {
							// Not `um_safe_redirect()` because password-reset page is predefined page and is situated on the same host.
							wp_safe_redirect(
								add_query_arg(
									array( 'updated' => 'invalidkey' ),
									um_get_core_page( 'password-reset' )
								)
							);
						}
						exit;
					}

					// this variable is used for populating the reset password form via the hash and login
					$this->change_password = true;
				}
			}
		}

		/**
		 * Reset Password form
		 */
		public function handle_reset_password_form() {
			if ( empty( $_POST['_um_password_reset'] ) ) {
				return;
			}

			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'um_password_reset_form' ) ) {
				/**
				 * Filters URL for redirect if reset password form nonce isn't verified.
				 *
				 * @param {string} $error_url URL for redirect if reset password form nonce isn't verified.
				 *
				 * @return {string} URL for redirect.
				 *
				 * @since 2.14.3
				 * @hook um_reset_password_invalid_nonce_redirect_url
				 *
				 * @example <caption>Change URL for redirect if reset password form nonce isn't verified.</caption>
				 * function my_um_reset_password_invalid_nonce_redirect_url( $error_url ) {
				 *     return '{your_custom_url}';
				 * }
				 * add_filter( 'um_reset_password_invalid_nonce_redirect_url', 'my_um_reset_password_invalid_nonce_redirect_url' );
				 */
				$url = apply_filters( 'um_reset_password_invalid_nonce_redirect_url', add_query_arg( array( 'err' => 'invalid_nonce' ) ) );
				um_safe_redirect( $url );
			}

			$form_data = array(
				'mode' => 'password',
			);

			$post_form = wp_unslash( $_POST );

			$defaults  = array(
				UM()->honeypot => '',
				'username_b'   => '',
				'mode'         => 'password',
			);
			$post_form = array_intersect_key( $post_form, $defaults ); // keep only necessary fields in the $_POST.

			UM()->form()->post_form = wp_parse_args( $post_form, $defaults );

			/**
			 * Fires for handle validate errors on the reset password form submit.
			 *
			 * @since 1.3.x
			 * @since 2.6.8 Added $form_data attribute.
			 *
			 * @hook um_reset_password_errors_hook
			 *
			 * @param {array} $submission_data Form submitted data.
			 * @param {array} $form_data       Form data. Since 2.6.8
			 *
			 * @example <caption>Make any custom validation on password reset form.</caption>
			 * function my_reset_password_errors( $submission_data, $form_data ) {
			 *     // your code here
			 * }
			 * add_action( 'um_reset_password_errors_hook', 'my_reset_password_errors', 10, 2 );
			 */
			do_action( 'um_reset_password_errors_hook', UM()->form()->post_form, $form_data );

			if ( ! isset( UM()->form()->errors ) ) {
				/**
				 * Fires for handle the reset password form when submitted data is valid.
				 *
				 * @since 1.3.x
				 * @since 2.6.8 Added $form_data attribute.
				 *
				 * @hook um_reset_password_process_hook
				 *
				 * @param {array} $submission_data Form submitted data.
				 * @param {array} $form_data       Form data. Since 2.6.8
				 *
				 * @example <caption>Make any custom action when password reset form is submitted.</caption>
				 * function my_reset_password_process( $submission_data, $form_data ) {
				 *     // your code here
				 * }
				 * add_action( 'um_reset_password_process_hook', 'my_reset_password_process', 10, 2 );
				 */
				do_action( 'um_reset_password_process_hook', UM()->form()->post_form, $form_data );
			}
		}

		/**
		 * Error handler: reset password
		 *
		 * @param array $args
		 */
		public function um_reset_password_errors_hook( $args ) {
			if ( isset( $args[ UM()->honeypot ] ) && '' !== $args[ UM()->honeypot ] ) {
				wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
			}

			$user = ! empty( $args['username_b'] ) ? trim( sanitize_text_field( $args['username_b'] ) ) : '';
			if ( empty( $user ) ) {
				UM()->form()->add_error( 'username_b', __( 'Please provide your username or email', 'ultimate-member' ) );
			}

			if ( ( ! is_email( $user ) && username_exists( $user ) ) || ( is_email( $user ) && email_exists( $user ) ) ) {
				if ( is_email( $user ) ) {
					$user_id = email_exists( $user );
				} else {
					$user_id = username_exists( $user );
				}

				$attempts = (int) get_user_meta( $user_id, 'password_rst_attempts', true );
				$is_admin = user_can( absint( $user_id ), 'manage_options' );

				if ( UM()->options()->get( 'enable_reset_password_limit' ) ) { // if reset password limit is set
					if ( ! ( UM()->options()->get( 'disable_admin_reset_password_limit' ) && $is_admin ) ) {
						// Doesn't trigger this when a user has admin capabilities and when reset password limit is disabled for admins
						$limit = UM()->options()->get( 'reset_password_limit_number' );
						if ( $attempts >= $limit ) {
							UM()->form()->add_error( 'username_b', __( 'You have reached the limit for requesting password change for this user already. Contact support if you cannot open the email', 'ultimate-member' ) );
						} else {
							update_user_meta( $user_id, 'password_rst_attempts', $attempts + 1 );
						}
					}
				}
			}
		}

		/**
		 * Process a new request
		 *
		 * @param array $args
		 */
		public function um_reset_password_process_hook( $args ) {
			$user = ! empty( $args['username_b'] ) ? trim( sanitize_text_field( $args['username_b'] ) ) : null;

			if ( username_exists( $user ) ) {
				$data = get_user_by( 'login', $user );
			} elseif ( email_exists( $user ) ) {
				$data = get_user_by( 'email', $user );
			}

			if ( isset( $data ) && is_a( $data, '\WP_User' ) ) {
				um_fetch_user( $data->ID );

				if ( false === UM()->options()->get( 'only_approved_user_reset_password' ) || UM()->common()->users()->has_status( $data->ID, 'approved' ) ) {
					UM()->user()->password_reset( $data->ID );
				}
			}

			wp_safe_redirect( um_get_core_page( 'password-reset', 'checkemail' ) );
			exit;
		}

		/**
		 * Password change form
		 */
		public function handle_change_password_form() {
			if ( empty( $_POST['_um_password_change'] ) ) {
				return;
			}

			$login = ! empty( $_POST['login'] ) ? sanitize_text_field( wp_unslash( $_POST['login'] ) ) : '';
			if ( empty( $login ) ) {
				return;
			}

			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'um_password_change' . $login ) ) {
				/**
				 * Filters URL for redirect if change password form nonce isn't verified.
				 *
				 * @param {string} $error_url URL for redirect if change password form nonce isn't verified.
				 *
				 * @return {string} URL for redirect.
				 *
				 * @since 2.14.3
				 * @hook um_change_password_invalid_nonce_redirect_url
				 *
				 * @example <caption>Change URL for redirect if change password form nonce isn't verified.</caption>
				 * function my_um_change_password_invalid_nonce_redirect_url( $error_url ) {
				 *     return '{your_custom_url}';
				 * }
				 * add_filter( 'um_change_password_invalid_nonce_redirect_url', 'my_um_change_password_invalid_nonce_redirect_url' );
				 */
				$url = apply_filters( 'um_change_password_invalid_nonce_redirect_url', add_query_arg( array( 'err' => 'invalid_nonce' ) ) );
				um_safe_redirect( $url );
			}

			$formdata = wp_unslash( $_POST );

			$defaults = array(
				UM()->honeypot          => '',
				'user_password'         => '',
				'confirm_user_password' => '',
				'login'                 => '',
				'rp_key'                => '',
				'mode'                  => 'password',
				'form_id'               => 'um_password_id',
			);
			$formdata = array_intersect_key( $formdata, $defaults ); // keep only necessary fields in the $_POST.

			// Don't un-slash passwords in manner of WordPress native password field.
			$fields_map = array(
				'user_password',
				'confirm_user_password',
			);
			$formdata   = UM()->form()::ignore_formdata_unslash( $formdata, $fields_map );

			UM()->form()->post_form = wp_parse_args( $formdata, $defaults );

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
			do_action( 'um_change_password_errors_hook', UM()->form()->post_form, 'password_reset' );

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

		/**
		 * Error handler: changing password
		 *
		 * It works only for the Reset Password Shortcode
		 *
		 * @param array  $args
		 */
		public function um_change_password_errors_hook( $args ) {
			if ( isset( $args[ UM()->honeypot ] ) && '' !== $args[ UM()->honeypot ] ) {
				wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
			}

			if ( isset( $args['user_password'] ) ) {
				$args['user_password'] = trim( $args['user_password'] );
			}

			if ( isset( $args['confirm_user_password'] ) ) {
				$args['confirm_user_password'] = trim( $args['confirm_user_password'] );
			}

			if ( empty( $args['user_password'] ) ) {
				UM()->form()->add_error( 'user_password', __( 'Password is required', 'ultimate-member' ) );
				return;
			}

			if ( empty( $args['confirm_user_password'] ) ) {
				UM()->form()->add_error( 'confirm_user_password', __( 'Password confirmation is required', 'ultimate-member' ) );
				return;
			}

			// Check for "\" in password.
			if ( false !== strpos( wp_unslash( $args['user_password'] ), '\\' ) ) {
				UM()->form()->add_error( 'user_password', __( 'Passwords may not contain the character "\\".', 'ultimate-member' ) );
				return;
			}

			if ( $args['user_password'] !== $args['confirm_user_password'] ) {
				UM()->form()->add_error( 'confirm_user_password', __( 'Your passwords do not match', 'ultimate-member' ) );
				return;
			}

			wp_fix_server_vars();

			$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
			if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
				list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

				$user = check_password_reset_key( $rp_key, $rp_login );
				um_fetch_user( $user->ID );
			}

			if ( UM()->options()->get( 'require_strongpass' ) ) {
				$min_length = UM()->options()->get( 'password_min_chars' );
				$min_length = ! empty( $min_length ) ? $min_length : 8;
				$max_length = UM()->options()->get( 'password_max_chars' );
				$max_length = ! empty( $max_length ) ? $max_length : 30;
				$user_login = um_user( 'user_login' );
				$user_email = um_user( 'user_email' );

				if ( mb_strlen( wp_unslash( $args['user_password'] ) ) < $min_length ) {
					// translators: %s: min length.
					UM()->form()->add_error( 'user_password', sprintf( __( 'Your password must contain at least %d characters', 'ultimate-member' ), $min_length ) );
					return;
				}

				if ( mb_strlen( wp_unslash( $args['user_password'] ) ) > $max_length ) {
					// translators: %s: max length.
					UM()->form()->add_error( 'user_password', sprintf( __( 'Your password must contain less than %d characters', 'ultimate-member' ), $max_length ) );
					return;
				}

				if ( strpos( strtolower( $user_login ), strtolower( $args['user_password'] ) ) > -1 ) {
					UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your username', 'ultimate-member' ) );
					return;
				}

				if ( strpos( strtolower( $user_email ), strtolower( $args['user_password'] ) ) > -1 ) {
					UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your email address', 'ultimate-member' ) );
					return;
				}

				$strong_pass_result = UM()->validation()->strong_pass( $args['user_password'] );
				if ( true !== $strong_pass_result ) {
					UM()->form()->add_error( 'user_password', $strong_pass_result );
				}
			}
		}

		/**
		 * Process a password change request.
		 * It only works on the Password Reset Shortcode form.
		 *
		 * @param array $args
		 */
		public function um_change_password_process_hook( $args ) {
			$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

			if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
				list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

				$user = check_password_reset_key( $rp_key, $rp_login );

				if ( isset( $args['user_password'] ) && ! hash_equals( $rp_key, $args['rp_key'] ) ) {
					$user = false;
				}
			} else {
				$user = false;
			}

			if ( ! $user || is_wp_error( $user ) ) {
				$this->setcookie( $rp_cookie, false );
				if ( $user && 'expired_key' === $user->get_error_code() ) {
					// Not `um_safe_redirect()` because it is predefined page and is situated on the same host.
					wp_safe_redirect( add_query_arg( array( 'updated' => 'expiredkey' ), um_get_core_page( 'password-reset' ) ) );
				} else {
					// Not `um_safe_redirect()` because it is predefined page and is situated on the same host.
					wp_safe_redirect( add_query_arg( array( 'updated' => 'invalidkey' ), um_get_core_page( 'password-reset' ) ) );
				}
				exit;
			}

			$errors = new WP_Error();

			/** This action is documented in wp-login.php */
			do_action( 'validate_password_reset', $errors, $user );

			if ( ( ! $errors->get_error_code() ) ) {
				reset_password( $user, trim( $args['user_password'] ) );

				// send the Password Changed Email
				UM()->user()->password_changed( $user->ID );

				// clear temporary data
				$attempts = (int) get_user_meta( $user->ID, 'password_rst_attempts', true );
				if ( $attempts ) {
					update_user_meta( $user->ID, 'password_rst_attempts', 0 );
				}
				$this->setcookie( $rp_cookie, false );

				$set_password_required = get_user_meta( $user->ID, 'um_set_password_required', true );
				if ( ! empty( $set_password_required ) ) {
					delete_user_meta( $user->ID, 'um_set_password_required' );
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
				do_action( 'um_after_changing_user_password', $user->ID );

				if ( ! is_user_logged_in() ) {
					$url = um_get_core_page( 'login', 'password_changed' );
				} else {
					$url = um_get_core_page( 'password-reset', 'password_changed' );
				}
				// Not `um_safe_redirect()` because it is predefined page and is situated on the same host.
				wp_safe_redirect( $url );
				exit;
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
		 * @depreated 2.10.5
		 * @param $placeholders
		 *
		 * @return array
		 */
		public function add_placeholder( $placeholders ) {
			_deprecated_function( __METHOD__, '2.10.5' );
			return $placeholders;
		}

		/**
		 * UM Replace Placeholders for reset password
		 * @depreated 2.10.5
		 * @param $replace_placeholders
		 *
		 * @return array
		 */
		public function add_replace_placeholder( $replace_placeholders ) {
			_deprecated_function( __METHOD__, '2.10.5' );
			return $replace_placeholders;
		}

		/**
		 * Check if a legitimate password reset request is in action
		 *
		 * @deprecated 2.14.3
		 *
		 * @return bool
		 */
		public function is_reset_request() {
			_deprecated_function( __METHOD__, '2.14.3' );
			// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
			return ! empty( $_POST['_um_password_reset'] );
		}

		/**
		 * Check if a legitimate password change request is in action
		 *
		 * works both for the Account > Password form and the Reset Password shortcode form
		 *
		 * @deprecated 2.14.3
		 *
		 * @return bool
		 */
		public function is_change_request() {
			_deprecated_function( __METHOD__, '2.14.3' );

			// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
			if ( ! empty( $_POST['_um_account'] ) && isset( $_POST['_um_account_tab'] ) && 'password' === sanitize_key( $_POST['_um_account_tab'] ) ) {
				return true;
			}

			if ( ! empty( $_POST['_um_password_change'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- already verified here
				return true;
			}

			return false;
		}
	}
}
