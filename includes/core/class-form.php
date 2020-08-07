<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Form' ) ) {


	/**
	 * Class Form
	 * @package um\core
	 */
	class Form {


		/**
		 * @var null
		 */
		public $form_suffix;


		/**
		 * @var
		 */
		var $form_id;


		/**
		 * @var null
		 */
		var $post_form = null;


		/**
		 * Form constructor.
		 */
		function __construct() {

			$this->form_suffix = null;

			$this->errors = null;

			$this->processing = null;

			add_action( 'template_redirect', array( &$this, 'form_init' ), 2 );

			add_action( 'init', array( &$this, 'field_declare' ), 10 );

		}


		/**
		 *
		 */
		function ajax_muted_action() {
			UM()->check_ajax_nonce();

			extract( $_REQUEST );

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) )
				die( __( 'You can not edit this user' ) );

			switch( $hook ) {
				default:
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_run_ajax_function__{$hook}
					 * @description Action on AJAX muted action
					 * @input_vars
					 * [{"var":"$request","type":"int","desc":"Request"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_run_ajax_function__{$hook}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_run_ajax_function__{$hook}', 'my_run_ajax_function', 10, 1 );
					 * function my_run_ajax_function( $request ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_run_ajax_function__{$hook}", $_REQUEST );
					break;
			}
		}


		/**
		 *
		 */
		function ajax_select_options() {
			UM()->check_ajax_nonce();

			$arr_options = array();
			$arr_options['status'] = 'success';
			$arr_options['post'] = $_POST;

			UM()->fields()->set_id = intval( $_POST['form_id'] );
			UM()->fields()->set_mode  = 'profile';
			$form_fields = UM()->fields()->get_fields();
			$arr_options['fields'] = $form_fields;

			if ( isset( $arr_options['post']['members_directory'] ) && $arr_options['post']['members_directory'] == 'yes' ) {
				$ajax_source_func = $_POST['child_callback'];
				if ( function_exists( $ajax_source_func ) ) {
					$arr_options['items'] = call_user_func( $ajax_source_func, $arr_options['field']['parent_dropdown_relationship'] );

					global $wpdb;

					$values_array = $wpdb->get_col( $wpdb->prepare(
						"SELECT DISTINCT meta_value 
						FROM $wpdb->usermeta 
						WHERE meta_key = %s AND 
						      meta_value != ''",
						$arr_options['post']['child_name']
					) );

					if ( ! empty( $values_array ) ) {
						$arr_options['items'] = array_intersect( $arr_options['items'], $values_array );
					} else {
						$arr_options['items'] = array();
					}

					wp_send_json( $arr_options );
				}
			} else {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_ajax_select_options__debug_mode
				 * @description Activate debug mode for AJAX select options
				 * @input_vars
				 * [{"var":"$debug_mode","type":"bool","desc":"Enable Debug mode"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_ajax_select_options__debug_mode', 'function_name', 10, 1 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_ajax_select_options__debug_mode', 'my_ajax_select_options__debug_mode', 10, 1 );
				 * function my_ajax_select_options__debug_mode( $debug_mode ) {
				 *     // your code here
				 *     return $debug_mode;
				 * }
				 * ?>
				 */
				$debug = apply_filters('um_ajax_select_options__debug_mode', false );
				if( $debug ){
					$arr_options['debug'] = array(
						$_POST,
						$form_fields,
					);
				}

				if ( ! empty( $_POST['child_callback'] ) && isset( $form_fields[ $_POST['child_name'] ] ) ) {

					$ajax_source_func = $_POST['child_callback'];

					// If the requested callback function is added in the form or added in the field option, execute it with call_user_func.
					if ( isset( $form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] ) &&
						! empty( $form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] ) &&
						$form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] == $ajax_source_func ) {

						$arr_options['field'] = $form_fields[ $_POST['child_name'] ];

						if ( function_exists( $ajax_source_func ) ) {
							$arr_options['items'] = call_user_func( $ajax_source_func, $arr_options['field']['parent_dropdown_relationship'] );
						}

					} else {
						$arr_options['status'] = 'error';
						$arr_options['message'] = __( 'This is not possible for security reasons.', 'ultimate-member' );
					}

				}

				wp_send_json( $arr_options );
			}
		}


		/**
		 * Count the form errors.
		 * @return integer
		 */
		function count_errors() {
			$errors = $this->errors;

			if( $errors && is_array( $errors ) ) {
				return count( $errors );
			}

			return 0;
		}


		/**
		 * Appends field errors
		 * @param string $key
		 * @param string $error
		 */
		function add_error( $key, $error ) {
			if ( ! isset( $this->errors[ $key ] ) ){
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_submit_form_error
				 * @description Change error text on submit form
				 * @input_vars
				 * [{"var":"$error","type":"string","desc":"Error String"},
				 * {"var":"$key","type":"string","desc":"Error Key"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_submit_form_error', 'function_name', 10, 2 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_submit_form_error', 'my_submit_form_error', 10, 2 );
				 * function my_submit_form_error( $error, $key ) {
				 *     // your code here
				 *     return $error;
				 * }
				 * ?>
				 */
				$error = apply_filters( 'um_submit_form_error', $error, $key );
				$this->errors[ $key ] = $error;
			}
		}

		/**
		 * Appends field notices
		 * @param string $key
		 * @param string $notice
		 */
		function add_notice( $key, $notice ) {
			if ( ! isset( $this->notices[ $key ] ) ){
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_submit_form_notice
				 * @description Change notice text on submit form
				 * @input_vars
				 * [{"var":"$notice","type":"string","desc":"notice String"},
				 * {"var":"$key","type":"string","desc":"notice Key"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_submit_form_notice', 'function_name', 10, 2 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_submit_form_notice', 'my_submit_form_notice', 10, 2 );
				 * function my_submit_form_notice( $notice, $key ) {
				 *     // your code here
				 *     return $notice;
				 * }
				 * ?>
				 */
				$notice = apply_filters( 'um_submit_form_notice', $notice, $key );
				$this->notices[ $key ] = $notice;
			}
		}


		/**
		 * If a form has errors
		 * @param  string  $key
		 * @return boolean
		 */
		function has_error( $key ) {
			if ( isset( $this->errors[ $key ] ) ) {
				return true;
			}
			return false;
		}

		/**
		 * If a form has notices/info
		 * @param  string  $key
		 * @return boolean
		 */
		function has_notice( $key ) {
			if ( isset( $this->notices[ $key ] ) ) {
				return true;
			}
			return false;
		}


		/**
		 * Declare all fields
		 */
		function field_declare() {
			if ( isset( UM()->builtin()->custom_fields ) ) {
				$this->all_fields = UM()->builtin()->custom_fields;
			} else {
				$this->all_fields = null;
			}
		}


		/**
		 * Validate form on submit
		 */
		function form_init() {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
				$http_post = ( 'POST' == $_SERVER['REQUEST_METHOD'] );
			} else {
				$http_post = 'POST';
			}

			if ( $http_post && ! is_admin() && isset( $_POST['form_id'] ) && is_numeric( $_POST['form_id'] ) ) {

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_before_submit_form_post
				 * @description Before submit form
				 * @input_vars
				 * [{"var":"$post","type":"int","desc":"Post data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_before_submit_form_post', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_before_submit_form_post', 'my_before_submit_form_post', 10, 1 );
				 * function my_run_ajax_function( $post ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_before_submit_form_post', $_POST );

				$this->form_id = $_POST['form_id'];
				$this->form_status = get_post_status( $this->form_id );


				if ( $this->form_status == 'publish' ) {
					/* save entire form as global */
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_submit_post_form
					 * @description Change submitted data on form submit
					 * @input_vars
					 * [{"var":"$data","type":"array","desc":"Submitted data"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_submit_post_form', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_submit_post_form', 'my_submit_post_form', 10, 1 );
					 * function my_submit_post_form( $data ) {
					 *     // your code here
					 *     return $data;
					 * }
					 * ?>
					 */
					$this->post_form = apply_filters( 'um_submit_post_form', $_POST );

					$this->post_form = $this->beautify( $this->post_form );

					$this->form_data = UM()->query()->post_data( $this->form_id );

					$this->post_form['submitted'] = $this->post_form;

					$this->post_form = array_merge( $this->form_data, $this->post_form );

					if ( isset( $this->form_data['custom_fields'] )  && strstr( $this->form_data['custom_fields'], 'role_' )  ) {  // Secure selected role

						$custom_field_roles = $this->custom_field_roles( $this->form_data['custom_fields'] );

						if ( ! empty( $_POST['role'] ) ) {
							$role = $_POST['role'];

							if( is_array( $_POST['role'] ) ){
								$role = current( $_POST['role'] );
							}

							global $wp_roles;
							$role_keys = array_map( function( $item ) {
								return 'um_' . $item;
							}, get_option( 'um_roles', array() ) );
							$exclude_roles = array_diff( array_keys( $wp_roles->roles ), array_merge( $role_keys, array( 'subscriber' ) ) );

							if ( ! empty( $role ) &&
								( ! in_array( $role , $custom_field_roles ) || in_array( $role , $exclude_roles ) ) ) {
								wp_die( __( 'This is not possible for security reasons.','ultimate-member') );
							}

							$this->post_form['role'] = $role;
							$this->post_form['submitted']['role'] = $role;
						}

					} elseif ( isset( $this->post_form['mode'] ) && $this->post_form['mode'] == 'register' ) {
						$role = $this->assigned_role( $this->form_id );
						$this->post_form['role'] = $role;
						//fix for social login
						//$this->post_form['submitted']['role'] = $role;
					}

					if ( isset( $_POST[ UM()->honeypot ] ) && $_POST[ UM()->honeypot ] != '' ) {
						wp_die( 'Hello, spam bot!', 'ultimate-member' );
					}

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_submit_form_data
					 * @description Change submitted data on form submit
					 * @input_vars
					 * [{"var":"$data","type":"array","desc":"Submitted data"},
					 * {"var":"$mode","type":"string","desc":"Form mode"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_submit_form_data', 'function_name', 10, 2 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_submit_form_data', 'my_submit_form_data', 10, 2 );
					 * function my_submit_form_data( $data ) {
					 *     // your code here
					 *     return $data;
					 * }
					 * ?>
					 */
					$this->post_form = apply_filters( 'um_submit_form_data', $this->post_form, $this->post_form['mode'] );

					/* Continue based on form mode - pre-validation */

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_submit_form_errors_hook
					 * @description Action on submit form
					 * @input_vars
					 * [{"var":"$post","type":"int","desc":"Post data"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_submit_form_errors_hook', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_submit_form_errors_hook', 'my_submit_form_errors', 10, 1 );
					 * function my_submit_form_errors( $post ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_submit_form_errors_hook', $this->post_form );
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_submit_form_{$mode}
					 * @description Action on submit form
					 * @input_vars
					 * [{"var":"$post","type":"int","desc":"Post data"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_submit_form_{$mode}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_submit_form_{$mode}', 'my_submit_form', 10, 1 );
					 * function my_submit_form( $post ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_submit_form_{$this->post_form['mode']}", $this->post_form );

				}

			}

		}


		/**
		 * Beautify form data
		 * @param  array $form
		 * @return array $form
		 */
		function beautify( $form ){

			if (isset($form['form_id'])){

				$this->form_suffix = '-' . $form['form_id'];

				$this->processing = $form['form_id'];

				foreach( $form as $key => $value ){
					if ( strstr( $key, $this->form_suffix ) ) {
						$a_key = str_replace( $this->form_suffix, '', $key );
						$form[ $a_key ] = $value;
						unset( $form[ $key ] );
					}
				}

			}

			return $form;
		}


		/**
		 * Display form type as Title
		 * @param  string $mode
		 * @param  integer $post_id
		 * @return string $output
		 */
		function display_form_type( $mode, $post_id ){
			$output = null;
			switch( $mode ){
				case 'login':
					$output = 'Login';
					break;
				case 'profile':
					$output = 'Profile';
					break;
				case 'register':
					$output = 'Register';
					break;
			}
			return $output;
		}


		/**
		 * Assigned roles to a form
		 * @param  integer $post_id
		 * @return string $role
		 */
		function assigned_role( $post_id ) {

			$global_role = get_option( 'default_role' ); // WP Global settings

			$um_global_role = UM()->options()->get( 'register_role' ); // UM Settings Global settings
			if ( ! empty( $um_global_role ) ) {
				$global_role = $um_global_role; // Form Global settings
			}

			$mode = $this->form_type( $post_id );

			/**
			 * @todo WPML integration to get role from original if it's empty
			 */
			$use_custom = get_post_meta( $post_id, "_um_{$mode}_use_custom_settings", true );
			if ( $use_custom ) { // Custom Form settings
				$role = get_post_meta( $post_id, "_um_{$mode}_role", true );
			}

			if ( empty( $role ) ) { // custom role is default, return default role's slug
				$role = $global_role;
			}

			return $role;
		}


		/**
		 * Get form type
		 * @param  integer $post_id
		 * @return string
		 */
		function form_type( $post_id ) {
			$mode = get_post_meta( $post_id, '_um_mode', true );
			return $mode;
		}


		/**
		 * Get custom field roles
		 * @param  string $custom_fields serialized
		 * @return bool|array roles
		 */
		function custom_field_roles( $custom_fields ) {

			$fields = maybe_unserialize( $custom_fields );

			if ( ! is_array( $fields )  )
				return false;

			foreach ( $fields as $field_key => $field_settings ) {

				if ( strstr( $field_key , 'role_' ) ) {
					if ( is_array( $field_settings['options'] ) ) {
						return array_keys( $field_settings['options'] );
					}
				}

			}

			return false;
		}
	}
}