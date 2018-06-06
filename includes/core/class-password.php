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
		 * @var
		 */
		var $reset_request;


		/**
		 * Password constructor.
		 */
		function __construct() {

			add_shortcode('ultimatemember_password', array(&$this, 'ultimatemember_password'));

			add_action('template_redirect', array(&$this, 'password_reset'), 10001 );

			add_action('template_redirect', array(&$this, 'form_init'), 10002);

			add_action('init',  array(&$this, 'listen_to_password_reset_uri'), 1);

		}


		/**
		 * A listener to password reset uri
		 */
		function listen_to_password_reset_uri() {

			if ( isset($_REQUEST['act']) && $_REQUEST['act'] == 'reset_password' && isset($_REQUEST['hash']) && strlen($_REQUEST['hash']) == 40 &&
			     isset($_REQUEST['user_id']) && is_numeric($_REQUEST['user_id']) ) {

				$user_id = absint( $_REQUEST['user_id'] );
				delete_option( "um_cache_userdata_{$user_id}" );

				um_fetch_user( $user_id );

				if ( $_REQUEST['hash'] != um_user('reset_pass_hash') ){
					wp_die( __('This is not a valid hash, or it has expired.','ultimate-member') );
				}

				UM()->user()->profile['reset_pass_hash_token'] = current_time( 'timestamp' );
				UM()->user()->update_usermeta_info('reset_pass_hash_token');

				$this->change_password = true;

				um_reset_user();

			}

		}


		/**
		 * reset url
		 *
		 * @return bool|string
		 */
		function reset_url(){
			if ( !um_user('reset_pass_hash') ) return false;

			$user_id = um_user('ID');

			delete_option( "um_cache_userdata_{$user_id}" );

			$url =  add_query_arg( 'act', 'reset_password', um_get_core_page('password-reset') );
			$url =  add_query_arg( 'hash', esc_attr( um_user('reset_pass_hash') ), $url );
			$url =  add_query_arg( 'user_id', esc_attr( um_user('ID') ), $url );

			return $url;

		}


		/**
		 * we are on password reset page
		 */
		function password_reset(){
			if ( um_is_core_page('password-reset') ) {

				UM()->fields()->set_mode = 'password';

			}

		}


		/**
		 * Password page form
		 */
		function form_init() {
			if ( um_requesting_password_reset() ) {

				UM()->form()->post_form = $_POST;

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

			if ( um_requesting_password_change() ) {

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
		 * Add class based on shortcode
		 *
		 * @param $mode
		 *
		 * @return mixed|string|void
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
			return $this->load( $args );
		}

		/**
		 * Load a module with global function
		 *
		 * @param $args
		 *
		 * @return string
		 */
		function load( $args ) {

			ob_start();

			$defaults = array(
				'template' => 'password-reset',
				'mode' => 'password',
				'form_id' => 'um_password_id',
				'max_width' => '450px',
				'align' => 'center',
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

				$args['user_id'] =  $_REQUEST['user_id'];
				$args['template'] = 'password-change';

			}

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

			$output = ob_get_contents();
			ob_end_clean();
			return $output;

		}

	}
}