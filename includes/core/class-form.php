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


		var $nonce = null;


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
		public function ajax_muted_action() {
			UM()->check_ajax_nonce();

			/**
			 * @var $user_id
			 * @var $hook
			 */
			extract( $_REQUEST );

			if ( isset( $user_id ) ) {
				$user_id = absint( $user_id );
			}

			if ( isset( $hook ) ) {
				$hook = sanitize_key( $hook );
			}

			if ( ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				die( esc_html__( 'You can not edit this user', 'ultimate-member' ) );
			}

			switch ( $hook ) {
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
		public function ajax_select_options() {
			UM()->check_ajax_nonce();

			$arr_options           = array();
			$arr_options['status'] = 'success';
			$arr_options['post']   = $_POST;

			// Callback validation
			if ( empty( $_POST['child_callback'] ) ) {
				$arr_options['status']  = 'error';
				$arr_options['message'] = __( 'Wrong callback.', 'ultimate-member' );

				wp_send_json( $arr_options );
			}

			$ajax_source_func = sanitize_text_field( $_POST['child_callback'] );

			if ( ! function_exists( $ajax_source_func ) ) {
				$arr_options['status']  = 'error';
				$arr_options['message'] = __( 'Wrong callback.', 'ultimate-member' );

				wp_send_json( $arr_options );
			}

			$allowed_callbacks = UM()->options()->get( 'allowed_choice_callbacks' );

			if ( empty( $allowed_callbacks ) ) {
				$arr_options['status']  = 'error';
				$arr_options['message'] = __( 'This is not possible for security reasons.', 'ultimate-member' );
				wp_send_json( $arr_options );
			}

			$allowed_callbacks = array_map( 'rtrim', explode( "\n", wp_unslash( $allowed_callbacks ) ) );

			if ( ! in_array( $ajax_source_func, $allowed_callbacks, true ) ) {
				$arr_options['status']  = 'error';
				$arr_options['message'] = __( 'This is not possible for security reasons.', 'ultimate-member' );

				wp_send_json( $arr_options );
			}

			if ( isset( $_POST['form_id'] ) ) {
				UM()->fields()->set_id = absint( $_POST['form_id'] );
			}
			UM()->fields()->set_mode  = 'profile';
			$form_fields              = UM()->fields()->get_fields();
			$arr_options['fields']    = $form_fields;

			if ( isset( $arr_options['post']['members_directory'] ) && 'yes' === $arr_options['post']['members_directory'] ) {
				global $wpdb;

				$values_array = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT meta_value
						FROM $wpdb->usermeta
						WHERE meta_key = %s AND
							  meta_value != ''",
						$arr_options['post']['child_name']
					)
				);

				if ( ! empty( $values_array ) ) {
					$parent_dropdown = isset( $arr_options['field']['parent_dropdown_relationship'] ) ? $arr_options['field']['parent_dropdown_relationship'] : '';
					$arr_options['items'] = call_user_func( $ajax_source_func, $parent_dropdown );

					if ( array_keys( $arr_options['items'] ) !== range( 0, count( $arr_options['items'] ) - 1 ) ) {
						// array with dropdown items is associative
						$arr_options['items'] = array_intersect_key( array_map( 'trim', $arr_options['items'] ), array_flip( $values_array ) );
					} else {
						// array with dropdown items has sequential numeric keys, starting from 0 and there are intersected values with $values_array
						$arr_options['items'] = array_intersect( $arr_options['items'], $values_array );
					}
				} else {
					$arr_options['items'] = array();
				}

				wp_send_json( $arr_options );
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
				$debug = apply_filters( 'um_ajax_select_options__debug_mode', false );
				if ( $debug ) {
					$arr_options['debug'] = array(
						$_POST,
						$form_fields,
					);
				}

				if ( ! empty( $_POST['child_callback'] ) && isset( $form_fields[ $_POST['child_name'] ] ) ) {
					// If the requested callback function is added in the form or added in the field option, execute it with call_user_func.
					if ( isset( $form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] ) &&
						! empty( $form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] ) &&
						$form_fields[ $_POST['child_name'] ]['custom_dropdown_options_source'] === $ajax_source_func ) {

						$arr_options['field'] = $form_fields[ $_POST['child_name'] ];

						$arr_options['items'] = call_user_func( $ajax_source_func, $arr_options['field']['parent_dropdown_relationship'] );
					} else {
						$arr_options['status']  = 'error';
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
		public function count_errors() {
			$errors = $this->errors;

			if ( $errors && is_array( $errors ) ) {
				return count( $errors );
			}

			return 0;
		}


		/**
		 * Appends field errors
		 *
		 * @param string $key
		 * @param string $error
		 */
		public function add_error( $key, $error ) {
			if ( ! isset( $this->errors[ $key ] ) ) {
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
				$this->errors[ $key ] = apply_filters( 'um_submit_form_error', $error, $key );
			}
		}

		/**
		 * Appends field notices
		 * @param string $key
		 * @param string $notice
		 */
		public function add_notice( $key, $notice ) {
			if ( ! isset( $this->notices[ $key ] ) ) {
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
				$this->notices[ $key ] = apply_filters( 'um_submit_form_notice', $notice, $key );
			}
		}


		/**
		 * If a form has errors
		 *
		 * @param  string  $key
		 * @return boolean
		 */
		public function has_error( $key ) {
			if ( isset( $this->errors[ $key ] ) ) {
				return true;
			}
			return false;
		}


		/**
		 * If a form has notices/info
		 *
		 * @param  string  $key
		 * @return boolean
		 */
		public function has_notice( $key ) {
			if ( isset( $this->notices[ $key ] ) ) {
				return true;
			}
			return false;
		}


		/**
		 * Return the errors as a WordPress Error object
		 *
		 * @return \WP_Error
		 */
		function get_wp_error() {
			$wp_error = new \WP_Error();
			if ( $this->count_errors() > 0 ) {
				foreach ( $this->errors as $key => $value ) {
					$wp_error->add( $key, $value );
				}
			}
			return $wp_error;
		}


		/**
		 * Declare all fields
		 */
		public function field_declare() {
			if ( isset( UM()->builtin()->custom_fields ) ) {
				$this->all_fields = UM()->builtin()->custom_fields;
			} else {
				$this->all_fields = null;
			}
		}


		/**
		 * Validate form on submit
		 */
		public function form_init() {
			if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
				$http_post = ( 'POST' === $_SERVER['REQUEST_METHOD'] );
			} else {
				$http_post = 'POST';
			}

			if ( $http_post && ! is_admin() && isset( $_POST['form_id'] ) && is_numeric( $_POST['form_id'] ) ) {

				$this->form_id = absint( $_POST['form_id'] );
				if ( 'um_form' !== get_post_type( $this->form_id ) ) {
					return;
				}

				$this->form_status = get_post_status( $this->form_id );
				if ( 'publish' !== $this->form_status ) {
					return;
				}

				$this->form_data   = UM()->query()->post_data( $this->form_id );

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_before_submit_form_post
				 * @description Before submit form
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
				do_action( 'um_before_submit_form_post' );

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

				if ( isset( $this->post_form[ UM()->honeypot ] ) && '' !== $this->post_form[ UM()->honeypot ] ) {
					wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
				}

				$this->post_form              = $this->beautify( $this->post_form );
				$this->post_form              = $this->sanitize( $this->post_form );
				$this->post_form['submitted'] = $this->post_form;

				$this->post_form = array_merge( $this->form_data, $this->post_form );

				// Remove role from post_form at first if role ! empty and there aren't custom fields with role name
				if ( ! empty( $_POST['role'] ) ) {
					if ( ! isset( $this->form_data['custom_fields'] ) || ! strstr( $this->form_data['custom_fields'], 'role_' ) ) {
						unset( $this->post_form['role'] );
						unset( $this->post_form['submitted']['role'] );
					}
				}

				// Secure sanitize of the submitted data
				if ( ! empty( $this->post_form ) ) {
					$this->post_form = array_diff_key( $this->post_form, array_flip( UM()->user()->banned_keys ) );
				}
				if ( ! empty( $this->post_form['submitted'] ) ) {
					$this->post_form['submitted'] = array_diff_key( $this->post_form['submitted'], array_flip( UM()->user()->banned_keys ) );
				}

				// set default role from settings on registration form
				if ( isset( $this->post_form['mode'] ) && 'register' === $this->post_form['mode'] ) {
					$role                    = $this->assigned_role( $this->form_id );
					$this->post_form['role'] = $role;
				}

				if ( isset( $this->form_data['custom_fields'] ) && strstr( $this->form_data['custom_fields'], 'role_' ) ) {  // Secure selected role

					if ( ! empty( $_POST['role'] ) ) {
						$custom_field_roles = $this->custom_field_roles( $this->form_data['custom_fields'] );

						if ( ! empty( $custom_field_roles ) ) {
							if ( is_array( $_POST['role'] ) ) {
								$role = current( $_POST['role'] );
								$role = sanitize_key( $role );
							} else {
								$role = sanitize_key( $_POST['role'] );
							}

							global $wp_roles;
							$role_keys     = array_map(
								function( $item ) {
									return 'um_' . $item;
								},
								get_option( 'um_roles', array() )
							);
							$exclude_roles = array_diff( array_keys( $wp_roles->roles ), array_merge( $role_keys, array( 'subscriber' ) ) );

							if ( ! empty( $role ) &&
								( ! in_array( $role, $custom_field_roles, true ) || in_array( $role, $exclude_roles, true ) ) ) {
								wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
							}

							$this->post_form['role']              = $role;
							$this->post_form['submitted']['role'] = $role;
						} else {
							unset( $this->post_form['role'] );
							unset( $this->post_form['submitted']['role'] );

							// set default role for registration form if custom field hasn't proper value
							if ( isset( $this->post_form['mode'] ) && 'register' === $this->post_form['mode'] ) {
								$role                    = $this->assigned_role( $this->form_id );
								$this->post_form['role'] = $role;
							}
						}
					}
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


		/**
		 * Beautify form data
		 *
		 * @param  array $form
		 *
		 * @return array $form
		 */
		public function beautify( $form ) {
			if ( isset( $form['form_id'] ) ) {
				$this->form_suffix = '-' . $form['form_id'];
				$this->processing  = $form['form_id'];

				foreach ( $form as $key => $value ) {
					if ( strstr( $key, $this->form_suffix ) ) {
						$a_key          = str_replace( $this->form_suffix, '', $key );
						$form[ $a_key ] = $value;
						unset( $form[ $key ] );
					}
				}
			}

			return $form;
		}


		/**
		 * Beautify form data
		 *
		 * @param  array $form
		 *
		 * @return array $form
		 */
		public function sanitize( $form ) {

			if ( isset( $form['form_id'] ) ) {
				if ( isset( $this->form_data['custom_fields'] ) ) {
					$custom_fields = maybe_unserialize( $this->form_data['custom_fields'] );

					if ( is_array( $custom_fields ) ) {
						foreach ( $custom_fields as $k => $field ) {

							if ( isset( $field['type'] ) ) {
								if ( isset( $form[ $k ] ) ) {

									switch ( $field['type'] ) {
										default:
											$form[ $k ] = apply_filters( 'um_sanitize_form_field', $form[ $k ], $field );
											break;
										case 'number':
											$form[ $k ] = (int) $form[ $k ];
											break;
										case 'textarea':
											if ( ! empty( $field['html'] ) || ( UM()->profile()->get_show_bio_key( $form ) === $k && UM()->options()->get( 'profile_show_html_bio' ) ) ) {
												$form[ $k ] = wp_kses_post( $form[ $k ] );
											} else {
												$form[ $k ] = sanitize_textarea_field( $form[ $k ] );
											}
											break;
										case 'url':
											$f = UM()->builtin()->get_a_field( $k );

											if ( array_key_exists( 'match', $f ) && array_key_exists( 'advanced', $f ) && 'social' === $f['advanced'] ) {
												$v = sanitize_text_field( $form[ $k ] );

												// Make a proper social link
												if ( ! empty( $v ) && ! strstr( $v, $f['match'] ) ) {
													$domain = trim( strtr( $f['match'], array(
														'https://' => '',
														'http://'  => '',
													) ), ' /' );

													if ( ! strstr( $v, $domain ) ) {
														$v = $f['match'] . $v;
													} else {
														$v = 'https://' . trim( strtr( $v, array(
															'https://' => '',
															'http://'  => '',
														) ), ' /' );
													}
												}

												$form[ $k ] = $v;
											} else {
												$form[ $k ] = esc_url_raw( $form[ $k ] );
											}
											break;
										case 'password':
											$form[ $k ] = trim( $form[ $k ] );
											if ( array_key_exists( 'confirm_' . $k, $form ) ) {
												$form[ 'confirm_' . $k ] = trim( $form[ 'confirm_' . $k ] );
											}
											break;
										case 'text':
										case 'select':
										case 'image':
										case 'file':
										case 'date':
										case 'time':
										case 'rating':
										case 'googlemap':
										case 'youtube_video':
										case 'vimeo_video':
										case 'soundcloud_track':
											$form[ $k ] = sanitize_text_field( $form[ $k ] );
											break;
										case 'multiselect':
										case 'radio':
										case 'checkbox':
											$form[ $k ] = is_array( $form[ $k ] ) ? array_map( 'sanitize_text_field', $form[ $k ] ) : sanitize_text_field( $form[ $k ] );
											break;
									}
								}
							}
						}
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
		public function display_form_type( $mode, $post_id ) {
			$output = null;
			switch ( $mode ) {
				case 'login':
					$output = __( 'Login', 'ultimate-member' );
					break;
				case 'profile':
					$output = __( 'Profile', 'ultimate-member' );
					break;
				case 'register':
					$output = __( 'Register', 'ultimate-member' );
					break;
			}
			return $output;
		}


		/**
		 * Assigned roles to a form
		 * @param  integer $post_id
		 * @return string $role
		 */
		public function assigned_role( $post_id ) {

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
		public function form_type( $post_id ) {
			$mode = get_post_meta( $post_id, '_um_mode', true );
			return $mode;
		}


		/**
		 * Get custom field roles
		 *
		 * @param  string $custom_fields serialized
		 * @return bool|array roles
		 */
		public function custom_field_roles( $custom_fields ) {

			$fields = maybe_unserialize( $custom_fields );
			if ( ! is_array( $fields ) ) {
				return false;
			}

			// role field
			global $wp_roles;
			$role_keys     = array_map(
				function( $item ) {
					return 'um_' . $item;
				},
				get_option( 'um_roles', array() )
			);
			$exclude_roles = array_diff( array_keys( $wp_roles->roles ), array_merge( $role_keys, array( 'subscriber' ) ) );

			$roles = UM()->roles()->get_roles( false, $exclude_roles );
			$roles = array_map(
				function( $item ) {
					return html_entity_decode( $item, ENT_QUOTES );
				},
				$roles
			);

			foreach ( $fields as $field_key => $field_settings ) {

				if ( strstr( $field_key, 'role_' ) && is_array( $field_settings['options'] ) ) {

					if ( isset( $this->post_form['mode'] ) && 'profile' === $this->post_form['mode'] &&
						 isset( $field_settings['editable'] ) && $field_settings['editable'] == 0 ) {
						continue;
					}

					if ( ! um_can_view_field( $field_settings ) ) {
						continue;
					}

					$intersected_options = array();
					foreach ( $field_settings['options'] as $key => $title ) {
						if ( false !== $search_key = array_search( $title, $roles ) ) {
							$intersected_options[ $search_key ] = $title;
						} elseif ( isset( $roles[ $key ] ) ) {
							$intersected_options[ $key ] = $title;
						}
					}

					// getting roles only from the first role fields
					return array_keys( $intersected_options );
				}
			}

			return false;
		}
	}
}
