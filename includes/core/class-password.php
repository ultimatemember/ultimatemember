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

			//new reset password key via WP native field
			$user_data = get_userdata( $user_id );
			$key = get_password_reset_key( $user_data );

			$url =  add_query_arg( array( 'act' => 'reset_password', 'hash' => $key, 'user_id' => $user_id ), um_get_core_page( 'password-reset' ) );
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
				$args['template'] = 'password-change';
				$args['rp_key'] = '';
				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

					$user = get_user_by( 'login', $rp_login );
					$args['user_id'] = $user->ID;
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
			if ( um_is_core_page( 'password-reset' ) && isset( $_POST['_um_password_reset'] ) == 1 ) {
				return true;
			}

			return false;
		}


		/**
		 * Check if a legitimate password change request is in action
		 *
		 *
		 * @return bool
		 */
		function is_change_request() {
			if ( isset( $_POST['_um_account'] ) == 1 && isset( $_POST['_um_account_tab'] ) && $_POST['_um_account_tab'] == 'password' ) {
				return true;
			} elseif ( isset( $_POST['_um_password_change'] ) && $_POST['_um_password_change'] == 1 ) {
				return true;
			}

			return false;
		}


		/**
		 * Password page form
		 */
		function form_init() {
			if ( um_is_core_page( 'password-reset' ) ) {
				UM()->fields()->set_mode = 'password';
			}

			if ( um_is_core_page( 'password-reset' ) && isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'reset_password' ) {
				wp_fix_server_vars();

				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

				if ( isset( $_GET['hash'] ) ) {
					$userdata = get_userdata( wp_unslash( absint( $_GET['user_id'] ) ) );
					if ( ! $userdata || is_wp_error( $userdata ) ) {
						wp_redirect( add_query_arg( array( 'act' => 'reset_password', 'error' => 'invalidkey' ), get_permalink() ) );
						exit;
					}
					$rp_login = $userdata->user_login;
					$rp_key = wp_unslash( $_GET['hash'] );

					$user = check_password_reset_key( $rp_key, $rp_login );

					if ( is_wp_error( $user ) ) {
						$this->setcookie( $rp_cookie, false );
						wp_redirect( add_query_arg( array( 'updated' => 'invalidkey' ), get_permalink() ) );
					} else {
						$value = sprintf( '%s:%s', $rp_login, wp_unslash( $_GET['hash'] ) );
						$this->setcookie( $rp_cookie, $value );
						wp_safe_redirect( remove_query_arg( array( 'hash', 'user_id' ) ) );
					}
					
					exit;
				}

				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
					$user = check_password_reset_key( $rp_key, $rp_login );
				} else {
					$user = false;
				}

				if ( ( ! $user || is_wp_error( $user ) ) && ! isset( $_GET['updated'] ) ) {
					$this->setcookie( $rp_cookie, false );
					if ( $user && $user->get_error_code() === 'expired_key' ) {
						wp_redirect( add_query_arg( array( 'updated' => 'expiredkey' ), get_permalink() ) );
					} else {
						wp_redirect( add_query_arg( array( 'updated' => 'invalidkey' ), get_permalink() ) );
					}
					exit;
				}

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
		function um_reset_password_errors_hook( $args ) {

			if ( $_POST[ UM()->honeypot ] != '' ) {
				wp_die( __( 'Hello, spam bot!', 'ultimate-member' ) );
			}

			$user = "";

			foreach ( $_POST as $key => $val ) {
				if ( strstr( $key, "username_b") ) {
					$user = trim( $val );
				}
			}

			if ( empty( $user ) ) {
				UM()->form()->add_error('username_b', __( 'Please provide your username or email', 'ultimate-member' ) );
			}

			if ( ( ! is_email( $user ) && ! username_exists( $user ) ) || ( is_email( $user ) && ! email_exists( $user ) ) ) {
				UM()->form()->add_error('username_b', __( 'We can\'t find an account registered with that address or username','ultimate-member') );
			} else {

				if ( is_email( $user ) ) {
					$user_id = email_exists( $user );
				} else {
					$user_id = username_exists( $user );
				}

				$attempts = (int) get_user_meta( $user_id, 'password_rst_attempts', true );
				$is_admin = user_can( absint( $user_id ),'manage_options' );

				if ( UM()->options()->get( 'enable_reset_password_limit' ) ) { // if reset password limit is set

					if ( UM()->options()->get( 'disable_admin_reset_password_limit' ) &&  $is_admin ) {
						// Triggers this when a user has admin capabilities and when reset password limit is disabled for admins
					} else {
						$limit = UM()->options()->get( 'reset_password_limit_number' );
						if ( $attempts >= $limit ) {
							UM()->form()->add_error( 'username_b', __( 'You have reached the limit for requesting password change for this user already. Contact support if you cannot open the email','ultimate-member') );
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
		function um_reset_password_process_hook( $args ) {
			$user = null;

			foreach ( $_POST as $key => $val ) {
				if ( strstr( $key, "username_b" ) ) {
					$user = trim( $val );
				}
			}

			if ( username_exists( $user ) ) {
				$data = get_user_by( 'login', $user );
			} elseif ( email_exists( $user ) ) {
				$data = get_user_by( 'email', $user );
			}

			um_fetch_user( $data->ID );

			UM()->user()->password_reset();

			exit( wp_redirect( um_get_core_page('password-reset', 'checkemail' ) ) );
		}


		/**
		 * Error handler: changing password
		 *
		 * @param $args
		 */
		function um_change_password_errors_hook( $args ) {
			if ( isset( $_POST[ UM()->honeypot ] ) && $_POST[ UM()->honeypot ] != '' ) {
				wp_die( __( 'Hello, spam bot!', 'ultimate-member' ) );
			}

			if ( ! is_user_logged_in() && isset( $args ) && ! um_is_core_page( 'password-reset' ) ||
			     is_user_logged_in() && isset( $args['user_id'] ) && $args['user_id'] != get_current_user_id() ) {
				wp_die( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
			}

			if ( isset( $args['user_password'] ) && empty( $args['user_password'] ) ) {
				UM()->form()->add_error( 'user_password', __( 'You must enter a new password', 'ultimate-member' ) );
			}

			if ( UM()->options()->get( 'reset_require_strongpass' ) ) {

				if ( strlen( utf8_decode( $args['user_password'] ) ) < 8 ) {
					UM()->form()->add_error( 'user_password', __( 'Your password must contain at least 8 characters', 'ultimate-member' ) );
				}

				if ( strlen( utf8_decode( $args['user_password'] ) ) > 30 ) {
					UM()->form()->add_error( 'user_password', __( 'Your password must contain less than 30 characters', 'ultimate-member' ) );
				}

				if ( ! UM()->validation()->strong_pass( $args['user_password'] ) ) {
					UM()->form()->add_error( 'user_password', __( 'Your password must contain at least one lowercase letter, one capital letter and one number', 'ultimate-member' ) );
				}

			}

			if ( isset( $args['confirm_user_password'] ) && empty( $args['confirm_user_password'] ) ) {
				UM()->form()->add_error( 'confirm_user_password', __( 'You must confirm your new password', 'ultimate-member' ) );
			}

			if ( isset( $args['user_password'] ) && isset( $args['confirm_user_password'] ) && $args['user_password'] != $args['confirm_user_password'] ) {
				UM()->form()->add_error( 'confirm_user_password', __( 'Your passwords do not match', 'ultimate-member' ) );
			}

		}


		/**
		 * Process a change request
		 *
		 * @param $args
		 */
		function um_change_password_process_hook( $args ) {
			extract( $args );

			if ( isset( $_POST['_um_password_change'] ) && $_POST['_um_password_change'] == 1 ) {

				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
				$user = get_userdata( $args['user_id'] );

				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );

					if ( $user->user_login != $rp_login ) {
						$user = false;
					} else {
						$user = check_password_reset_key( $rp_key, $rp_login );
						if ( isset( $_POST['user_password'] ) && ! hash_equals( $rp_key, $_POST['rp_key'] ) ) {
							$user = false;
						}
					}
				} else {
					$user = false;
				}

				if ( ! $user || is_wp_error( $user ) ) {
					$this->setcookie( $rp_cookie, false );
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
					reset_password( $user, $args['user_password'] );
					delete_user_meta( $args['user_id'], 'password_rst_attempts' );
					$this->setcookie( $rp_cookie, false );
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
					do_action( 'um_after_changing_user_password', $args['user_id'] );

					exit( wp_redirect( um_get_core_page('login', 'password_changed' ) ) );
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