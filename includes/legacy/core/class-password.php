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
			add_shortcode( 'ultimatemember_password', array( &$this, 'ultimatemember_password' ) );

			add_action( 'template_redirect', array( &$this, 'form_init' ), 10001 );

			add_action( 'um_reset_password_errors_hook', array( &$this, 'um_reset_password_errors_hook' ) );
			add_action( 'um_reset_password_process_hook', array( &$this,'um_reset_password_process_hook' ) );

			add_action( 'um_change_password_errors_hook', array( &$this, 'um_change_password_errors_hook' ) );
			add_action( 'um_change_password_process_hook', array( &$this,'um_change_password_process_hook' ) );
		}


		/**
		 * Get Reset URL
		 *
		 * @return bool|string
		 */
		function reset_url() {
			$user_id = um_user( 'ID' );

			delete_option( "um_cache_userdata_{$user_id}" );

			//new reset password key via WordPress native field. It maybe already exists here but generated twice to make sure that emailed with a proper and fresh hash
			$user_data = get_userdata( $user_id );
			$key       = UM()->user()->maybe_generate_password_reset_key( $user_data );

			// this link looks like WordPress native link e.g. wp-login.php?action=rp&key={hash}&login={user_login}
			$url = add_query_arg(
				array(
					'act'   => 'reset_password',
					'hash'  => $key,
					'login' => $user_data->user_login,
				),
				um_get_core_page( 'password-reset' )
			);
			return $url;
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

			if ( UM()->fields()->editing == true ) {
				$classes .= ' um-editing';
			}

			if ( UM()->fields()->viewing == true ) {
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
		function ultimatemember_password( $args = array() ) {
			ob_start();

			$defaults = array(
				'template'  => 'password-reset',
				'mode'      => 'password',
				'form_id'   => 'um_password_id',
				'max_width' => '450px',
				'align'     => 'center',
			);
			$args = wp_parse_args( $args, $defaults );

			if ( empty( $args['use_custom_settings'] ) ) {
				$args = array_merge( $args, UM()->shortcodes()->get_css_args( $args ) );
			} else {
				$args = array_merge( UM()->shortcodes()->get_css_args( $args ), $args );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_reset_password_shortcode_args_filter
			 * @description Extend Reset Password Arguments
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_reset_password_shortcode_args_filter', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_reset_password_shortcode_args_filter', 'my_reset_password_shortcode_args', 10, 1 );
			 * function my_reset_password_shortcode_args( $args ) {
			 *     // your code here
			 *     return $args;
			 * }
			 * ?>
			 */
			$args = apply_filters( 'um_reset_password_shortcode_args_filter', $args );

			if ( isset( $this->change_password ) ) {
				// then COOKIE are valid then get data from them and populate hidden fields for the password reset form
				$args['template'] = 'password-change';
				$args['rp_key']   = '';
				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

					$args['login']  = $rp_login;
					$args['rp_key'] = $rp_key;
				}
			}

			UM()->fields()->set_id = 'um_password_id';

			/**
			 * @var $mode
			 * @var $template
			 */
			extract( $args, EXTR_SKIP );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_pre_{$mode}_shortcode
			 * @description Action pre-load password form shortcode
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form shortcode pre-loading"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_pre_{$mode}_shortcode', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_pre_{$mode}_shortcode', 'my_pre_password_shortcode', 10, 1 );
			 * function my_pre_password_shortcode( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_pre_{$mode}_shortcode", $args );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_form_is_loaded
			 * @description Action pre-load password form shortcode
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form shortcode pre-loading"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_form_is_loaded', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_form_is_loaded', 'my_before_form_is_loaded', 10, 1 );
			 * function my_before_form_is_loaded( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_before_form_is_loaded", $args );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_{$mode}_form_is_loaded
			 * @description Action pre-load password form shortcode
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form shortcode pre-loading"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_{$mode}_form_is_loaded', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_{$mode}_form_is_loaded', 'my_before_form_is_loaded', 10, 1 );
			 * function my_before_form_is_loaded( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_before_{$mode}_form_is_loaded", $args );

			UM()->shortcodes()->template_load( $template, $args );

			if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
				UM()->shortcodes()->dynamic_css( $args );
			}

			$output = ob_get_clean();
			return $output;
		}


		/**
		 * Check if a legitimate password reset request is in action
		 *
		 * @return bool
		 */
		function is_reset_request() {
			if ( um_is_core_page( 'password-reset' ) && isset( $_POST['_um_password_reset'] ) ) {
				return true;
			}

			return false;
		}


		/**
		 * Check if a legitimate password change request is in action
		 *
		 * works both for the Account > Password form and the Reset Password shortcode form
		 *
		 * @return bool
		 */
		function is_change_request() {
			if ( isset( $_POST['_um_account'] ) == 1 && isset( $_POST['_um_account_tab'] ) && sanitize_key( $_POST['_um_account_tab'] ) === 'password' ) {
				return true;
			} elseif ( isset( $_POST['_um_password_change'] ) && $_POST['_um_password_change'] == 1 ) {
				return true;
			}

			return false;
		}


		/**
		 * Password page form
		 */
		public function form_init() {
			if ( um_is_core_page( 'password-reset' ) ) {
				UM()->fields()->set_mode = 'password';
			}

			// validate $rp_cookie and hash via check_password_reset_key
			if ( um_is_core_page( 'password-reset' ) && isset( $_REQUEST['act'] ) && 'reset_password' === sanitize_key( $_REQUEST['act'] ) ) {
				wp_fix_server_vars();

				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

				if ( isset( $_GET['hash'] ) && isset( $_GET['login'] ) ) {
					$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['hash'] ) );
					$this->setcookie( $rp_cookie, $value );

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
						wp_redirect( add_query_arg( array( 'updated' => 'expiredkey' ), um_get_core_page( 'password-reset' ) ) );
					} else {
						wp_redirect( add_query_arg( array( 'updated' => 'invalidkey' ), um_get_core_page( 'password-reset' ) ) );
					}
					exit;
				}

				// this variable is used for populating the reset password form via the hash and login
				$this->change_password = true;
			}

			if ( $this->is_reset_request() ) {
				UM()->form()->post_form = $_POST;

				if ( empty( UM()->form()->post_form['mode'] ) ) {
					UM()->form()->post_form['mode'] = 'password';
				}

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_reset_password_errors_hook
				 * @description Action on reset password submit form
				 * @input_vars
				 * [{"var":"$post","type":"array","desc":"Form submitted"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_reset_password_errors_hook', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_reset_password_errors_hook', 'my_reset_password_errors', 10, 1 );
				 * function my_reset_password_errors( $post ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_reset_password_errors_hook', UM()->form()->post_form );

				if ( ! isset( UM()->form()->errors ) ) {
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_reset_password_process_hook
					 * @description Action on reset password success submit form
					 * @input_vars
					 * [{"var":"$post","type":"array","desc":"Form submitted"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_reset_password_process_hook', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_reset_password_process_hook', 'my_reset_password_process', 10, 1 );
					 * function my_reset_password_process( $post ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_reset_password_process_hook', UM()->form()->post_form );
				}
			}

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
		 * Error handler: reset password
		 *
		 * @param $args
		 */
		public function um_reset_password_errors_hook( $args ) {
			if ( '' !== $args[ UM()->honeypot ] ) {
				wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
			}

			$user = '';
			foreach ( $args as $key => $val ) {
				if ( strstr( $key, 'username_b' ) ) {
					$user = trim( sanitize_text_field( $val ) );
				}
			}

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
		 * @param $args
		 */
		public function um_reset_password_process_hook( $args ) {
			$user = null;

			foreach ( $args as $key => $val ) {
				if ( strstr( $key, 'username_b' ) ) {
					$user = trim( sanitize_text_field( $val ) );
				}
			}

			if ( username_exists( $user ) ) {
				$data = get_user_by( 'login', $user );
			} elseif ( email_exists( $user ) ) {
				$data = get_user_by( 'email', $user );
			}

			if ( isset( $data ) && is_a( $data, '\WP_User' ) ) {
				um_fetch_user( $data->ID );
				UM()->user()->password_reset();
			}

			wp_redirect( um_get_core_page('password-reset', 'checkemail' ) );
			exit;
		}


		/**
		 * Error handler: changing password
		 *
		 * It works both for the Reset Password Shortcode and Account > Change password form
		 *
		 * @param $args
		 */
		public function um_change_password_errors_hook( $args ) {
			if ( isset( $args[ UM()->honeypot ] ) && '' !== $args[ UM()->honeypot ] ) {
				wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
			}

			if ( isset( $args['_um_account'] ) == 1 && isset( $args['_um_account_tab'] ) && 'password' === sanitize_key( $args['_um_account_tab'] ) ) {
				// validate for security on the account change password page
				if ( ! is_user_logged_in() ) {
					wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
				}
			}

			if ( isset( $args['user_password'] ) && empty( $args['user_password'] ) ) {
				UM()->form()->add_error( 'user_password', __( 'You must enter a new password', 'ultimate-member' ) );
			}

			if ( isset( $args['user_password'] ) ) {
				$args['user_password'] = trim( $args['user_password'] );
			}

			if ( isset( $args['confirm_user_password'] ) ) {
				$args['confirm_user_password'] = trim( $args['confirm_user_password'] );
			}

			// Check for "\" in password.
			if ( false !== strpos( wp_unslash( $args['user_password'] ), '\\' ) ) {
				UM()->form()->add_error( 'user_password', __( 'Passwords may not contain the character "\\".', 'ultimate-member' ) );
			}

			if ( UM()->options()->get( 'require_strongpass' ) ) {
				$min_length = UM()->options()->get( 'password_min_chars' );
				$min_length = ! empty( $min_length ) ? $min_length : 8;
				$max_length = UM()->options()->get( 'password_max_chars' );
				$max_length = ! empty( $max_length ) ? $max_length : 30;

				if ( mb_strlen( wp_unslash( $args['user_password'] ) ) < $min_length ) {
					UM()->form()->add_error( 'user_password', sprintf( __( 'Your password must contain at least %d characters', 'ultimate-member' ), $min_length ) );
				}

				if ( mb_strlen( wp_unslash( $args['user_password'] ) ) > $max_length ) {
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
				// it only works on the Password Reset Shortcode form
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
						wp_redirect( add_query_arg( array( 'updated' => 'expiredkey' ), um_get_core_page( 'password-reset' ) ) );
					} else {
						wp_redirect( add_query_arg( array( 'updated' => 'invalidkey' ), um_get_core_page( 'password-reset' ) ) );
					}
					exit;
				}

				$errors = new \WP_Error();

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
					wp_redirect( $url );
					exit;
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
