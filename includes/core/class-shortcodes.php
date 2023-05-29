<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Shortcodes' ) ) {


	/**
	 * Class Shortcodes
	 * @package um\core
	 */
	class Shortcodes {

		var $profile_role = '';

		/**
		 * Shortcodes constructor.
		 */
		function __construct() {

			$this->custom_message = '';



			add_shortcode( 'ultimatemember', array( &$this, 'ultimatemember' ) );

			add_shortcode( 'ultimatemember_login', array( &$this, 'ultimatemember_login' ) );
			add_shortcode( 'ultimatemember_register', array( &$this, 'ultimatemember_register' ) );
			add_shortcode( 'ultimatemember_profile', array( &$this, 'ultimatemember_profile' ) );


			add_shortcode( 'ultimatemember_searchform', array( &$this, 'ultimatemember_searchform' ) );

			add_filter( 'body_class', array( &$this, 'body_class' ), 0 );

			add_filter( 'um_shortcode_args_filter', array( &$this, 'display_logout_form' ), 99 );
			add_filter( 'um_shortcode_args_filter', array( &$this, 'parse_shortcode_args' ), 99 );



		}


		/**
		 * Conditional logout form
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		function display_logout_form( $args ) {
			if ( is_user_logged_in() && isset( $args['mode'] ) && $args['mode'] == 'login' ) {

				if ( isset( UM()->user()->preview ) && UM()->user()->preview ) {
					return $args;
				}

				if ( get_current_user_id() != um_user( 'ID' ) ) {
					um_fetch_user( get_current_user_id() );
				}

				$args['template'] = 'logout';
			}

			return $args;
		}


		/**
		 * Filter shortcode args
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		function parse_shortcode_args( $args ) {
			if ( $this->message_mode == true ) {
				if ( ! empty( $_REQUEST['um_role'] ) ) {
					$args['template'] = 'message';
					$roleID = sanitize_key( $_REQUEST['um_role'] );
					$role = UM()->roles()->role_data( $roleID );

					if ( ! empty( $role ) && ! empty( $role['status'] ) ) {
						$message_key = $role['status'] . '_message';
						$this->custom_message = ! empty( $role[ $message_key ] ) ? stripslashes( $role[ $message_key ] ) : '';
					}
				}
			}

			foreach ( $args as $k => $v ) {
				$args[ $k ] = maybe_unserialize( $args[ $k ] );
			}

			return $args;
		}


		/**
		 * Remove wpautop filter for post content if it's UM core page
		 *
		 * @deprecated 3.0
		 */
		function is_um_page() {
			_deprecated_function( __METHOD__, '3.0' );

			if ( is_ultimatemember() ) {
				remove_filter( 'the_content', 'wpautop' );
			}
		}


		/**
		 * Extend body classes
		 *
		 * @param $classes
		 *
		 * @return array
		 */
		function body_class( $classes ) {
			$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );

			foreach ( $predefined_pages as $slug ) {
				if ( um_is_predefined_page( $slug ) ) {

					$classes[] = 'um-page-' . $slug;

					if ( is_user_logged_in() ) {
						$classes[] = 'um-page-loggedin';
					} else {
						$classes[] = 'um-page-loggedout';
					}

				}
			}

			if ( um_is_predefined_page( 'user' ) && um_is_user_himself() ) {
				$classes[] = 'um-own-profile';
			}

			return $classes;
		}














		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember_login( $args = array() ) {
			global $wpdb;

			$args = ! empty( $args ) ? $args : array();

			$default_login = $wpdb->get_var(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'login' AND
					  pm2.meta_value = '1'"
			);

			$args['form_id'] = $default_login;
			$shortcode_attrs = '';
			foreach ( $args as $key => $value ) {
				$shortcode_attrs .= " {$key}=\"{$value}\"";
			}

			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
			} else {
				return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
			}
		}


		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember_register( $args = array() ) {
			global $wpdb;

			$args = ! empty( $args ) ? $args : array();

			$default_register = $wpdb->get_var(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'register' AND
					  pm2.meta_value = '1'"
			);

			$args['form_id'] = $default_register;
			$shortcode_attrs = '';
			foreach ( $args as $key => $value ) {
				$shortcode_attrs .= " {$key}=\"{$value}\"";
			}

			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
			} else {
				return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
			}
		}


		/**
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember_profile( $args = array() ) {
			global $wpdb;

			$args = ! empty( $args ) ? $args : array();

			$default_profile = $wpdb->get_var(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'profile' AND
					  pm2.meta_value = '1'"
			);

			$args['form_id'] = $default_profile;

			$shortcode_attrs = '';
			foreach ( $args as $key => $value ) {
				$shortcode_attrs .= " {$key}=\"{$value}\"";
			}

			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
			} else {
				return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
			}
		}


		/**
		 * Shortcode
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		function ultimatemember( $args = array() ) {
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
			$defaults = array();
			$args = wp_parse_args( $args, $defaults );

			// when to not continue
			$this->form_id = isset( $args['form_id'] ) ? $args['form_id'] : null;
			if ( ! $this->form_id ) {
				return;
			}

			$this->form_status = get_post_status( $this->form_id );
			if ( $this->form_status != 'publish' ) {
				return;
			}

			// get data into one global array
			$post_data = UM()->query()->post_data( $this->form_id );
			$args = array_merge( $args, $post_data );

			ob_start();

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_pre_args_setup
			 * @description Change arguments on load shortcode
			 * @input_vars
			 * [{"var":"$post_data","type":"string","desc":"$_POST data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_pre_args_setup', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_pre_args_setup', 'my_pre_args_setup', 10, 1 );
			 * function my_pre_args_setup( $post_data ) {
			 *     // your code here
			 *     return $post_data;
			 * }
			 * ?>
			 */
			$args = apply_filters( 'um_pre_args_setup', $args );

			if ( ! isset( $args['template'] ) ) {
				$args['template'] = '';
			}

			if ( isset( $post_data['template'] ) && $post_data['template'] != $args['template'] ) {
				$args['template'] = $post_data['template'];
			}

			if ( ! $this->template_exists( $args['template'] ) ) {
				$args['template'] = $post_data['mode'];
			}

			if ( ! isset( $post_data['template'] ) ) {
				$post_data['template'] = $post_data['mode'];
			}

			$maybe_skip_meta = apply_filters( 'um_load_shortcode_maybe_skip_meta', false, $args );
			if ( ! $maybe_skip_meta ) {
				$args = array_merge( $post_data, $args );
				$args = array_merge( $this->get_css_args( $args ), $args );
			}
			// filter for arguments

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_shortcode_args_filter
			 * @description Change arguments on load shortcode
			 * @input_vars
			 * [{"var":"$args","type":"string","desc":"Shortcode arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_shortcode_args_filter', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_shortcode_args_filter', 'my_shortcode_args', 10, 1 );
			 * function my_shortcode_args( $args ) {
			 *     // your code here
			 *     return $args;
			 * }
			 * ?>
			 */
			$args = apply_filters( 'um_shortcode_args_filter', $args );

			/**
			 * @var string $mode
			 */
			extract( $args, EXTR_SKIP );

			//not display on admin preview
			if ( empty( $_POST['action'] ) || sanitize_key( $_POST['action'] ) !== 'um_admin_preview_form' ) {

				$enable_loggedin_registration = apply_filters( 'um_registration_for_loggedin_users', false, $args );

				if ( 'register' == $mode && is_user_logged_in() && ! $enable_loggedin_registration ) {
					ob_get_clean();
					return __( 'You are already registered', 'ultimate-member' );
				}
			}

			// for profiles only
			if ( $mode == 'profile' && um_profile_id() ) {

				//set requested user if it's not setup from permalinks (for not profile page in edit mode)
				if ( ! um_get_requested_user() ) {
					um_set_requested_user( um_profile_id() );
				}

				if ( ! empty( $args['role'] ) ) { // Option "Make this profile form role-specific"

					// show the first Profile Form with role selected, don't show profile forms below the page with other role-specific setting
					if ( empty( $this->profile_role ) ) {
						$current_user_roles = UM()->roles()->get_all_user_roles( um_profile_id() );

						if ( empty( $current_user_roles ) ) {
							ob_get_clean();
							return '';
						} elseif ( is_array( $args['role'] ) ) {
							if ( ! count( array_intersect( $args['role'], $current_user_roles ) ) ) {
								ob_get_clean();
								return '';
							}
						} else {
							if ( ! in_array( $args['role'], $current_user_roles ) ) {
								ob_get_clean();
								return '';
							}
						}

						$this->profile_role = $args['role'];
					} elseif ( $this->profile_role != $args['role'] ) {
						ob_get_clean();
						return '';
					}
				}
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_pre_{$mode}_shortcode
			 * @description Action pre-load form shortcode
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form shortcode pre-loading"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_pre_{$mode}_shortcode', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_pre_{$mode}_shortcode', 'my_pre_shortcode', 10, 1 );
			 * function my_pre_shortcode( $args ) {
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
			 * @description Action pre-load form shortcode
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form shortcode pre-loading"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_form_is_loaded', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_form_is_loaded', 'my_pre_shortcode', 10, 1 );
			 * function my_pre_shortcode( $args ) {
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
			 * @description Action pre-load form shortcode
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form shortcode pre-loading"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_{$mode}_form_is_loaded', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_{$mode}_form_is_loaded', 'my_pre_shortcode', 10, 1 );
			 * function my_pre_shortcode( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_before_{$mode}_form_is_loaded", $args );

			$output = ob_get_clean();

			ob_start();

			$this->template_load( $template, $args );

			$main_content = ob_get_clean();

			$output .= apply_filters( 'um_main_ultimatemember_shortcode_content', $main_content, $mode, $args );

			ob_start();

			$this->dynamic_css( $args );

			if ( um_get_requested_user() || $mode == 'logout' ) {
				um_reset_user();
			}

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_everything_output
			 * @description Action after load shortcode content
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_everything_output', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_after_everything_output', 'my_after_everything_output', 10 );
			 * function my_after_everything_output() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_after_everything_output' );

			$output .= ob_get_clean();
			return $output;
		}


		/**
		 * Get dynamic CSS args
		 *
		 * @param $args
		 * @return array
		 */
		function get_css_args( $args ) {
			$arr = um_styling_defaults( $args['mode'] );
			$arr = array_merge( $arr, array( 'form_id' => $args['form_id'], 'mode' => $args['mode'] ) );
			return $arr;
		}








		/**
		 * Checks if a template file exists
		 *
		 * @param $template
		 *
		 * @return bool
		 */
		function template_exists($template) {

			$file = UM_PATH . 'templates/' . $template . '.php';
			$theme_file = get_stylesheet_directory() . '/ultimate-member/templates/' . $template . '.php';

			if (file_exists($theme_file) || file_exists($file)) {
				return true;
			}

			return false;
		}















	}
}
