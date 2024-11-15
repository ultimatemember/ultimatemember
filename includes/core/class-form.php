<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Form' ) ) {

	/**
	 * Class Form
	 * @package um\core
	 */
	class Form {

		/**
		 * @var
		 */
		public $form_data;

		public $form_suffix = null;

		/**
		 * @var
		 */
		public $form_id;

		/**
		 * @var
		 */
		public $form_status;

		/**
		 * @var null
		 */
		public $post_form = null;

		/**
		 * @var null
		 */
		public $nonce = null;

		/**
		 * @var null|array
		 */
		public $errors = null;

		/**
		 * @var null
		 */
		public $processing = null;

		/**
		 * @var array
		 */
		public $all_fields = array();

		/**
		 * Whitelisted usermeta that can be stored when UM Form is submitted.
		 *
		 * @since 2.6.7
		 *
		 * @var array
		 */
		public $usermeta_whitelist = array();

		/**
		 * Hook for singleton
		 * @since 2.8.0
		 */
		public function hooks() {
			add_action( 'template_redirect', array( &$this, 'form_init' ), 2 );
			add_action( 'init', array( &$this, 'field_declare' ) );
		}

		/**
		 *
		 */
		public function ajax_muted_action() {
			UM()->check_ajax_nonce();

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['hook'] ) ) {
				die( esc_html__( 'Invalid hook', 'ultimate-member' ) );
			}

			if ( isset( $_REQUEST['user_id'] ) ) {
				$user_id = absint( $_REQUEST['user_id'] );
			}
			if ( ! isset( $user_id ) || ! UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				die( esc_html__( 'You can not edit this user.', 'ultimate-member' ) );
			}

			$hook = sanitize_key( $_REQUEST['hook'] );
			/**
			 * Fires on AJAX muted action.
			 *
			 * @since 1.3.x
			 * @hook  um_run_ajax_function__{$hook}
			 *
			 * @param {array} $request Request.
			 *
			 * @example <caption>Make any custom action on AJAX muted action.</caption>
			 * function my_run_ajax_function( $request ) {
			 *     // your code here
			 * }
			 * add_action( 'um_run_ajax_function__{$hook}', 'my_run_ajax_function', 10, 1 );
			 */
			do_action( "um_run_ajax_function__{$hook}", $_REQUEST );
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 *
		 */
		public function ajax_select_options() {
			UM()->check_ajax_nonce();

			// phpcs:disable WordPress.Security.NonceVerification

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

			if ( UM()->fields()->is_source_blacklisted( $ajax_source_func ) ) {
				$arr_options['status']  = 'error';
				$arr_options['message'] = __( 'This is not possible for security reasons.', 'ultimate-member' );

				wp_send_json( $arr_options );
			}

			if ( isset( $_POST['form_id'] ) ) {
				UM()->fields()->set_id = absint( $_POST['form_id'] );
			}
			UM()->fields()->set_mode = 'profile';
			$form_fields             = UM()->fields()->get_fields();
			$arr_options['fields']   = $form_fields;

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
					$parent_dropdown      = isset( $arr_options['post']['parent_option_name'] ) ? $arr_options['post']['parent_option_name'] : '';
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

				// phpcs:enable WordPress.Security.NonceVerification
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
		 * Remove banned wp_usermeta keys from submitted data.
		 *
		 * @since 2.6.5
		 * @param array $submitted
		 * @return array
		 */
		public function clean_submitted_data( $submitted ) {
			foreach ( $submitted as $metakey => $value ) {
				if ( UM()->user()->is_metakey_banned( $metakey/*, 'submission'*/ ) ) {
					unset( $submitted[ $metakey ] );
				}
			}

			return $submitted;
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
			// Handles Register, Profile and Login forms.
			if ( $http_post && ! is_admin() && isset( $_POST['form_id'] ) && is_numeric( $_POST['form_id'] ) ) {

				$this->form_id = absint( $_POST['form_id'] );
				if ( 'um_form' !== get_post_type( $this->form_id ) ) {
					return;
				}

				$this->form_status = get_post_status( $this->form_id );
				if ( 'publish' !== $this->form_status ) {
					return;
				}

				// Verified that form_id is right and UM form is published. Then get form data.

				$this->form_data = UM()->query()->post_data( $this->form_id );

				// Checking the form custom fields. Form without custom fields is invalid.
				if ( ! array_key_exists( 'mode', $this->form_data ) ) {
					return;
				}

				// Checking the form custom fields. Form without custom fields is invalid.
				if ( ! array_key_exists( 'custom_fields', $this->form_data ) ) {
					return;
				}

				$custom_fields = maybe_unserialize( $this->form_data['custom_fields'] );
				if ( ! is_array( $custom_fields ) || empty( $custom_fields ) ) {
					return;
				}

				$ignore_keys = array();

				$arr_restricted_fields = array();
				if ( 'profile' === $this->form_data['mode'] ) {
					$arr_restricted_fields = UM()->fields()->get_restricted_fields_for_edit();
				}

				$field_types_without_metakey = UM()->builtin()->get_fields_without_metakey();
				foreach ( $custom_fields as $cf_k => $cf_data ) {
					if ( ! array_key_exists( 'type', $cf_data ) || in_array( $cf_data['type'], $field_types_without_metakey, true ) ) {
						unset( $custom_fields[ $cf_k ] );
					}

					if ( array_key_exists( 'type', $cf_data ) && 'password' === $cf_data['type'] ) {
						$ignore_keys[] = $cf_k;
						$ignore_keys[] = 'confirm_' . $cf_k;
					}

					if ( 'profile' === $this->form_data['mode'] ) {
						if ( ! empty( $cf_data['edit_forbidden'] ) ) {
							$ignore_keys[] = $cf_k;
						}

						if ( ! um_can_edit_field( $cf_data ) || ! um_can_view_field( $cf_data ) ) {
							$ignore_keys[] = $cf_k;
						}
					}

					if ( ! array_key_exists( 'metakey', $cf_data ) || empty( $cf_data['metakey'] ) ) {
						unset( $custom_fields[ $cf_k ] );
					}

					if ( isset( $cf_data['required_opt'] ) ) {
						$opt = $cf_data['required_opt'];
						if ( UM()->options()->get( $opt[0] ) !== $opt[1] ) {
							$ignore_keys[] = $cf_k;
						}
					}
				}
				$cf_metakeys     = array_column( $custom_fields, 'metakey' );
				$all_cf_metakeys = $cf_metakeys;

				// The '_um_last_login' cannot be updated through UM form.
				$cf_metakeys = array_values( array_diff( $cf_metakeys, array( 'role_select', 'role_radio', 'role', '_um_last_login', 'user_pass', 'user_password', 'confirm_user_password' ) ) );
				if ( ! empty( $ignore_keys ) ) {
					$cf_metakeys = array_values( array_diff( $cf_metakeys, $ignore_keys ) );
				}
				// Remove restricted fields when edit profile.
				if ( 'profile' === $this->form_data['mode'] ) {
					// Column names from wp_users table.
					$cf_metakeys = array_values( array_diff( $cf_metakeys, array( 'user_login' ) ) );
					// Hidden for edit fields
					$cf_metakeys = array_values( array_diff( $cf_metakeys, $arr_restricted_fields ) );

					$cf_metakeys[] = 'profile_photo';
					$cf_metakeys[] = 'cover_photo';

					if ( ! empty( $this->form_data['use_custom_settings'] ) && ! empty( $this->form_data['show_bio'] ) ) {
						$cf_metakeys[] = UM()->profile()->get_show_bio_key( $this->form_data );
					} else {
						if ( UM()->options()->get( 'profile_show_bio' ) ) {
							$cf_metakeys[] = UM()->profile()->get_show_bio_key( $this->form_data );
						}
					}
				}
				// Add required usermeta for register.
				if ( 'register' === $this->form_data['mode'] ) {
					$cf_metakeys[] = 'form_id';
				}

				/**
				 * Filters whitelisted usermeta keys that can be stored inside DB after UM Form submission.
				 *
				 * @param {array} $whitelisted_metakeys Whitelisted usermeta keys.
				 * @param {array} $form_data            UM form data.
				 *
				 * @return {array} Whitelisted usermeta keys.
				 *
				 * @since 2.6.7
				 * @hook um_whitelisted_metakeys
				 *
				 * @example <caption>Extends whitelisted usermeta keys.</caption>
				 * function my_um_whitelisted_metakeys( $metakeys, $form_data ) {
				 *     $metakeys[] = 'some_key';
				 *     return $metakeys;
				 * }
				 * add_filter( 'um_whitelisted_metakeys', 'my_um_whitelisted_metakeys', 10, 2 );
				 */
				$cf_metakeys = apply_filters( 'um_whitelisted_metakeys', $cf_metakeys, $this->form_data );

				// Important variable to prevent save unnecessary data to wp_usermeta.
				$this->usermeta_whitelist = $cf_metakeys;

				/**
				 * Fires before UM login, registration or profile form submission.
				 *
				 * @since 1.3.x
				 * @hook um_before_submit_form_post
				 *
				 * @param {array}  $post $_POST submission array. Deprecated since 2.0.
				 * @param {object} $this UM()->form() class instance. Since 2.6.7
				 *
				 * @example <caption>Make any custom action before UM login, registration or profile form submission.</caption>
				 * function my_custom_before_submit_form_post( $um_form_obj ) {
				 *     // your code here
				 * }
				 * add_action( 'um_before_submit_form_post', 'my_custom_before_submit_form_post' );
				 */
				do_action( 'um_before_submit_form_post', $this );

				/* save entire form as global */
				/**
				 * Filters $_POST submitted data by the UM login, registration or profile form.
				 *
				 * @param {array} $_post Submitted data. Already un-slashed by `wp_unslash()`.
				 *
				 * @return {array} Submitted data.
				 *
				 * @since 1.3.x
				 * @hook um_submit_post_form
				 *
				 * @example <caption>Extends $_POST data.</caption>
				 * function my_submit_post_form( $_post ) {
				 *     $_post['some_key'] = 'some value';
				 *     return $_post;
				 * }
				 * add_filter( 'um_submit_post_form', 'my_submit_post_form' );
				 */
				$this->post_form = apply_filters( 'um_submit_post_form', wp_unslash( $_POST ) );

				// Validate form submission by honeypot.
				if ( isset( $this->post_form[ UM()->honeypot ] ) && '' !== $this->post_form[ UM()->honeypot ] ) {
					// High level escape if hacking.
					wp_die( esc_html__( 'Hello, spam bot!', 'ultimate-member' ) );
				}

				$this->post_form = $this->beautify( $this->post_form );

				// Validate and filter 'role' submitted data to avoid handling roles with admin privileges.
				// Remove role from post_form at first if role ! empty and there aren't custom fields with role name
				$maybe_set_default_role = true;
				if ( array_key_exists( 'role', $this->post_form ) ) {
					if ( 'login' === $this->form_data['mode'] ) {
						unset( $this->post_form['role'] );
					} else {
						$form_has_role_field = count( array_intersect( $all_cf_metakeys, array( 'role_select', 'role_radio' ) ) ) > 0;
						if ( ! $form_has_role_field ) {
							unset( $this->post_form['role'] );
						} else {
							$custom_field_roles = $this->custom_field_roles( $this->form_data['custom_fields'] );
							if ( ! empty( $custom_field_roles ) && ! empty( $this->post_form['role'] ) ) {
								if ( is_array( $this->post_form['role'] ) ) {
									$role = current( $this->post_form['role'] );
									$role = sanitize_key( $role );
								} else {
									$role = sanitize_key( $this->post_form['role'] );
								}

								global $wp_roles;
								$exclude_roles = array_diff( array_keys( $wp_roles->roles ), UM()->roles()->get_editable_user_roles() );

								if ( ! empty( $role ) &&
									( ! in_array( $role, $custom_field_roles, true ) || in_array( $role, $exclude_roles, true ) ) ) {
									// High level escape if hacking.
									wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
								}

								$this->post_form['role'] = $role;
								$maybe_set_default_role  = false;

								// Force adding `role` metakey if there is a role-type field on the form. It's required to User Profile.
								$this->usermeta_whitelist[] = 'role';
							}
						}
					}
				}

				$this->post_form              = $this->sanitize( $this->post_form );
				$this->post_form['submitted'] = $this->post_form;

				// Set default role from settings on registration form. It has been made after defined 'submitted' because predefined role isn't a submitted field.
				if ( $maybe_set_default_role && 'register' === $this->form_data['mode'] ) {
					$role                    = $this->assigned_role( $this->form_id );
					$this->post_form['role'] = $role;
				}

				/**
				 * Filters $_POST submitted data by the UM login, registration or profile form.
				 * It's un-slashed by `wp_unslash()`, beautified and sanitized. `role` attribute is filtered by possible role.
				 * `submitted` key is added by code and contains summary of submission.
				 *
				 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
				 * 9  - `um_submit_form_data_trim_fields()` maybe over-functionality and can be removed.
				 * 10 - `um_submit_form_data_role_fields()` important for conditional logic based on role fields in form.
				 *
				 * @param {array}  $_post           Submitted data.
				 * @param {string} $mode            Form mode. login||register||profile
				 * @param {array}  $all_cf_metakeys Form's metakeys. Since 2.6.7.
				 *
				 * @return {array} Submitted data.
				 *
				 * @since 1.3.x
				 * @hook um_submit_form_data
				 *
				 * @example <caption>Extends UM form submitted data.</caption>
				 * function my_submit_form_data( $_post, $mode, $all_cf_metakeys ) {
				 *     $_post['some_key'] = 'some value';
				 *     return $_post;
				 * }
				 * add_filter( 'um_submit_form_data', 'my_submit_form_data', 10, 3 );
				 */
				$this->post_form = apply_filters( 'um_submit_form_data', $this->post_form, $this->form_data['mode'], $all_cf_metakeys );
				/* Continue based on form mode - pre-validation */
				/**
				 * Fires for validation UM login, registration or profile form submission.
				 *
				 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
				 * 10 - `um_submit_form_errors_hook()` All form validation handlers.
				 * 20 - `um_recaptcha_validate()`      reCAPTCHA form validation handlers. um-recaptcha extension.
				 *
				 * @since 1.3.x
				 * @hook um_submit_form_errors_hook
				 *
				 * @param {array} $post      $_POST Submission array.
				 * @param {array} $form_data UM form data. Since 2.6.7
				 *
				 * @example <caption>Make any common validation action here.</caption>
				 * function my_custom_before_submit_form_post( $post, $form_data ) {
				 *     // your code here
				 * }
				 * add_action( 'um_submit_form_errors_hook', 'my_custom_submit_form_errors_hook', 10, 2 );
				 */
				do_action( 'um_submit_form_errors_hook', $this->post_form, $this->form_data );
				/* Continue based on form mode - store data. */
				/**
				 * Fires for make main actions on UM login, registration or profile form submission.
				 * Where $mode equals login, registration or profile
				 *
				 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
				 * ### um_submit_form_login:
				 * * 1 - `UM()->login()->verify_nonce()` Verify nonce.
				 * * 10 - `um_submit_form_login()`       Login form main handler.
				 * ### um_submit_form_register:
				 * * 1  - `UM()->register()->verify_nonce()`                 Verify nonce.
				 * * 9  - `UM()->agreement_validation()`                     GDPR Agreement.
				 * * 9  - `UM()->terms_conditions()->agreement_validation()` Terms & Conditions Agreement.
				 * * 10 - `um_submit_form_register()`                        Register form main handler.
				 * ### um_submit_form_profile:
				 * * 10 - `um_submit_form_profile()` Profile form main handler.
				 *
				 * @since 1.3.x
				 * @hook um_submit_form_{$mode}
				 *
				 * @param {array} $post      $_POST Submission array.
				 * @param {array} $form_data UM form data. Since 2.6.7
				 *
				 * @example <caption>Make any custom action on profile submission.</caption>
				 * function my_custom_submit_form_profile( $post, $form_data ) {
				 *     // your code here
				 * }
				 * add_action( 'um_submit_form_profile', 'my_custom_submit_form_profile', 10, 2 );
				 */
				do_action( "um_submit_form_{$this->form_data['mode']}", $this->post_form, $this->form_data );
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
				$this->processing  = absint( $form['form_id'] );

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
		 * Use PHP tidy extension if it's active for getting clean HTML without unclosed tags.
		 *
		 * @param string $html_fragment Textarea with active HTML option field value.
		 * @param array  $field_data    Ultimate Member form field data.
		 *
		 * @return string|\tidy
		 */
		private static function maybe_apply_tidy( $html_fragment, $field_data ) {
			// Break if extension isn't active in php.ini
			if ( ! function_exists( 'tidy_parse_string' ) ) {
				return $html_fragment;
			}

			$tidy_config = array(
				'clean'          => true,
				'output-xhtml'   => true,
				'show-body-only' => true,
				'wrap'           => 0,
			);
			/**
			 * Filters PHP tidy extension config.
			 * Get more info here https://www.php.net/manual/en/tidy.parsestring.php
			 *
			 * @param {array} $tidy_config Config.
			 * @param {array} $field_data  UM Form Field Data.
			 *
			 * @return {array} Config.
			 *
			 * @since 2.8.9
			 * @hook um_tidy_config
			 *
			 * @example <caption>Customize tidy config based on field data.</caption>
			 * function my_um_tidy_config( $tidy_config, $field_data ) {
			 *     // your code here
			 *     if ( 'custom_metakey' === $field_data['metakey'] ) {
			 *         $tidy_config['clean'] = false;
			 *     }
			 *     return $tidy_config;
			 * }
			 * add_filter( 'um_tidy_config', 'my_um_tidy_config', 10, 2 );
			 */
			$tidy_config = apply_filters( 'um_tidy_config', $tidy_config, $field_data );

			// since PHP8.0 $tidy_config, 'UTF8' variables are nullable https://www.php.net/manual/en/tidy.parsestring.php
			$tidy   = tidy_parse_string( $html_fragment, $tidy_config, 'UTF8' );
			$result = $tidy->cleanRepair();
			if ( $result ) {
				return $tidy;
			}

			return $html_fragment;
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
											$form[ $k ] = '' !== $form[ $k ] ? (int) $form[ $k ] : '';
											break;
										case 'textarea':
											if ( ! empty( $field['html'] ) || ( UM()->profile()->get_show_bio_key( $form ) === $k && UM()->options()->get( 'profile_show_html_bio' ) ) ) {
												$form[ $k ] = html_entity_decode( $form[ $k ] ); // required because WP_Editor send sometimes encoded content.
												$form[ $k ] = self::maybe_apply_tidy( $form[ $k ], $field );

												$allowed_html = UM()->get_allowed_html( 'templates' );
												if ( empty( $allowed_html['iframe'] ) ) {
													$allowed_html['iframe'] = array(
														'allow'           => true,
														'frameborder'     => true,
														'loading'         => true,
														'name'            => true,
														'referrerpolicy'  => true,
														'sandbox'         => true,
														'src'             => true,
														'srcdoc'          => true,
														'title'           => true,
														'width'           => true,
														'height'          => true,
														'allowfullscreen' => true,
													);
												}
												$form[ $k ] = wp_kses( $form[ $k ], $allowed_html );
												add_filter( 'wp_kses_allowed_html', array( &$this, 'wp_kses_user_desc' ), 10, 2 );
											} else {
												$form[ $k ] = sanitize_textarea_field( $form[ $k ] );
											}
											break;
										case 'oembed':
										case 'url':
											$f = UM()->builtin()->get_a_field( $k );

											if ( is_array( $f ) && array_key_exists( 'match', $f ) && array_key_exists( 'advanced', $f ) && 'social' === $f['advanced'] ) {
												$v = $form[ $k ];

												// Make a proper social link
												if ( ! empty( $v ) ) {
													$replace_match = is_array( $f['match'] ) ? $f['match'][0] : $f['match'];

													$need_replace = false;
													if ( is_array( $f['match'] ) ) {
														$need_replace = true;
														foreach ( $f['match'] as $arr_match ) {
															if ( strstr( $v, $arr_match ) ) {
																$need_replace = false;
															}
														}
													}

													if ( ! is_array( $f['match'] ) || $need_replace ) {
														if ( ! strstr( $v, $replace_match ) ) {
															$domain = trim(
																strtr(
																	$replace_match,
																	array(
																		'https://' => '',
																		'http://'  => '',
																	)
																),
																' /'
															);

															if ( ! strstr( $v, $domain ) ) {
																$v = $replace_match . $v;
															} else {
																$v = 'https://' . trim(
																	strtr(
																		$v,
																		array(
																			'https://' => '',
																			'http://'  => '',
																		)
																	),
																	' /'
																);
															}
														}
													}
												}

												$form[ $k ] = esc_url_raw( $v );
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
										case 'spotify':
										case 'tel':
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

				$show_bio       = false;
				$bio_html       = false;
				$global_setting = UM()->options()->get( 'profile_show_html_bio' );
				if ( ! empty( $form_data['use_custom_settings'] ) ) {
					if ( ! empty( $form_data['show_bio'] ) ) {
						$show_bio = true;
						$bio_html = ! empty( $global_setting );
					}
				} else {
					$global_show_bio = UM()->options()->get( 'profile_show_bio' );
					if ( ! empty( $global_show_bio ) ) {
						$show_bio = true;
						$bio_html = ! empty( $global_setting );
					}
				}

				$description_key = UM()->profile()->get_show_bio_key( $this->form_data );
				if ( $show_bio && ! empty( $form[ $description_key ] ) ) {
					$field_exists = false;
					if ( ! empty( $this->form_data['custom_fields'] ) ) {
						$custom_fields = maybe_unserialize( $this->form_data['custom_fields'] );
						if ( array_key_exists( $description_key, $custom_fields ) ) {
							$field_exists = true;
							if ( ! empty( $custom_fields[ $description_key ]['html'] ) && $bio_html ) {
								$form[ $description_key ] = html_entity_decode( $form[ $description_key ] ); // required because WP_Editor send sometimes encoded content.
								$form[ $description_key ] = self::maybe_apply_tidy( $form[ $description_key ], $custom_fields[ $description_key ] );

								$allowed_html = UM()->get_allowed_html( 'templates' );
								if ( empty( $allowed_html['iframe'] ) ) {
									$allowed_html['iframe'] = array(
										'allow'           => true,
										'frameborder'     => true,
										'loading'         => true,
										'name'            => true,
										'referrerpolicy'  => true,
										'sandbox'         => true,
										'src'             => true,
										'srcdoc'          => true,
										'title'           => true,
										'width'           => true,
										'height'          => true,
										'allowfullscreen' => true,
									);
								}
								$form[ $description_key ] = wp_kses( $form[ $description_key ], $allowed_html );

								add_filter( 'wp_kses_allowed_html', array( &$this, 'wp_kses_user_desc' ), 10, 2 );
							} else {
								$form[ $description_key ] = sanitize_textarea_field( $form[ $description_key ] );
							}
						}
					}

					if ( ! $field_exists ) {
						if ( $bio_html ) {
							$allowed_html = UM()->get_allowed_html( 'templates' );
							if ( empty( $allowed_html['iframe'] ) ) {
								$allowed_html['iframe'] = array(
									'allow'           => true,
									'frameborder'     => true,
									'loading'         => true,
									'name'            => true,
									'referrerpolicy'  => true,
									'sandbox'         => true,
									'src'             => true,
									'srcdoc'          => true,
									'title'           => true,
									'width'           => true,
									'height'          => true,
									'allowfullscreen' => true,
								);
							}
							$form[ $description_key ] = wp_kses( $form[ $description_key ], $allowed_html );

							add_filter( 'wp_kses_allowed_html', array( &$this, 'wp_kses_user_desc' ), 10, 2 );
						} else {
							$form[ $description_key ] = sanitize_textarea_field( $form[ $description_key ] );
						}
					}
				}
			}

			return $form;
		}

		public function wp_kses_user_desc( $tags, $context ) {
			if ( 'user_description' === $context || 'pre_user_description' === $context ) {
				$allowed_html = UM()->get_allowed_html( 'templates' );
				if ( empty( $allowed_html['iframe'] ) ) {
					$allowed_html['iframe'] = array(
						'allow'           => true,
						'frameborder'     => true,
						'loading'         => true,
						'name'            => true,
						'referrerpolicy'  => true,
						'sandbox'         => true,
						'src'             => true,
						'srcdoc'          => true,
						'title'           => true,
						'width'           => true,
						'height'          => true,
						'allowfullscreen' => true,
					);
				}
				$tags = $allowed_html;
			}
			return $tags;
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

			$existing_roles = array_keys( wp_roles()->roles );
			if ( ! empty( $um_global_role ) && in_array( $um_global_role, $existing_roles, true ) ) {
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

			if ( empty( $role ) || ! in_array( $role, $existing_roles, true ) ) { // custom role is default, return default role's slug
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
			$exclude_roles = array_diff( array_keys( $wp_roles->roles ), UM()->roles()->get_editable_user_roles() );

			$roles = UM()->roles()->get_roles( false, $exclude_roles );
			$roles = array_map(
				function( $item ) {
					return html_entity_decode( $item, ENT_QUOTES );
				},
				$roles
			);

			foreach ( $fields as $field_key => $field_settings ) {

				if ( strstr( $field_key, 'role_' ) && array_key_exists( 'options', $field_settings ) && is_array( $field_settings['options'] ) ) {

					if ( isset( $this->post_form['mode'] ) && 'profile' === $this->post_form['mode'] ) {
						// It's for a legacy case `array_key_exists( 'editable', $field_settings )`.
						if ( ( array_key_exists( 'editable', $field_settings ) && empty( $field_settings['editable'] ) ) || ! um_can_edit_field( $field_settings ) ) {
							continue;
						}
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
