<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\REST_API' ) ) {


	/**
	 * Class REST_API
	 * @package um\core
	 */
	class REST_API {

		/**
		 *
		 */
		const VERSION = '1.0';


		/**
		 * @var bool|int|null
		 */
		private $pretty_print = false;


		/**
		 * @var bool|mixed|void
		 */
		public 	$log_requests = true;


		/**
		 * @var bool
		 */
		private $is_valid_request = false;


		/**
		 * @var int
		 */
		private $user_id = 0;


		/**
		 * @var
		 */
		private $stats;


		/**
		 * @var array
		 */
		private $data = array();


		/**
		 * @var bool
		 */
		private $override = true;


		/**
		 * REST_API constructor.
		 */
		public function __construct() {

			add_action( 'init',                     array( $this, 'add_endpoint'     ) );
			add_action( 'template_redirect',        array( $this, 'process_query'    ), -1 );
			add_filter( 'query_vars',               array( $this, 'query_vars'       ) );

			add_filter( 'um_user_profile_additional_fields', array( $this, 'user_key_field' ), 3, 2 );

			add_action( 'personal_options_update',  array( $this, 'update_key'       ) );
			add_action( 'edit_user_profile_update', array( $this, 'update_key'       ) );

			// Determine if JSON_PRETTY_PRINT is available
			$this->pretty_print = defined( 'JSON_PRETTY_PRINT' ) ? JSON_PRETTY_PRINT : null;

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_api_log_requests
			 * @description Allow API request logging to be turned off
			 * @input_vars
			 * [{"var":"$allow_log","type":"bool","desc":"Enable api logs"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_api_log_requests', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_api_log_requests', 'my_api_log_requests', 10, 1 );
			 * function my_api_log_requests( $allow_log ) {
			 *     // your code here
			 *     return $allow_log;
			 * }
			 * ?>
			 */
			$this->log_requests = apply_filters( 'um_api_log_requests', $this->log_requests );
		}


		/**
		 * Registers a new rewrite endpoint for accessing the API
		 *
		 * @param $rewrite_rules
		 */
		public function add_endpoint( $rewrite_rules ) {
			add_rewrite_endpoint( 'um-api', EP_ALL );
		}


		/**
		 * Registers query vars for API access
		 *
		 * @param $vars
		 *
		 * @return array
		 */
		public function query_vars( $vars ) {

			$vars[] = 'key';
			$vars[] = 'token';
			$vars[] = 'format';
			$vars[] = 'query';
			$vars[] = 'type';
			$vars[] = 'data';
			$vars[] = 'fields';
			$vars[] = 'value';
			$vars[] = 'number';
			$vars[] = 'id';
			$vars[] = 'email';
			$vars[] = 'orderby';
			$vars[] = 'order';
			$vars[] = 'include';
			$vars[] = 'exclude';

			$this->vars = $vars;

			return $vars;
		}


		/**
		 * Validate the API request
		 */
		private function validate_request() {
			global $wp_query;

			$this->override = false;

			// Make sure we have both user and api key
			if ( ! empty( $wp_query->query_vars['um-api'] ) ) {

				if ( empty( $wp_query->query_vars['token'] ) || empty( $wp_query->query_vars['key'] ) )
					$this->missing_auth();

				// Retrieve the user by public API key and ensure they exist
				if ( ! ( $user = $this->get_user( $wp_query->query_vars['key'] ) ) ) :
					$this->invalid_key();
				else :
					$token  = urldecode( $wp_query->query_vars['token'] );
					$secret = get_user_meta( $user, 'um_user_secret_key', true );
					$public = urldecode( $wp_query->query_vars['key'] );

					if ( hash_equals( md5( $secret . $public ), $token ) )
						$this->is_valid_request = true;
					else
						$this->invalid_auth();
				endif;
			}
		}


		/**
		 * Retrieve the user ID based on the public key provided
		 *
		 * @param string $key
		 *
		 * @return bool|mixed|null|string
		 */
		public function get_user( $key = '' ) {
			global $wpdb, $wp_query;

			if( empty( $key ) )
				$key = urldecode( $wp_query->query_vars['key'] );

			if ( empty( $key ) ) {
				return false;
			}

			$user = get_transient( md5( 'um_api_user_' . $key ) );

			if ( false === $user ) {
				$user = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'um_user_public_key' AND meta_value = %s LIMIT 1", $key ) );
				set_transient( md5( 'um_api_user_' . $key ) , $user, DAY_IN_SECONDS );
			}

			if ( $user != NULL ) {
				$this->user_id = $user;
				return $user;
			}

			return false;
		}


		/**
		 * Displays a missing authentication error if all the parameters aren't
		 * provided
		 */
		private function missing_auth() {
			$error = array();
			$error['error'] = __( 'You must specify both a token and API key!', 'ultimate-member' );

			$this->data = $error;
			$this->output( 401 );
		}


		/**
		 * Displays an authentication failed error if the user failed to provide valid credentials
		 */
		private function invalid_auth() {
			$error = array();
			$error['error'] = __( 'Your request could not be authenticated', 'ultimate-member' );

			$this->data = $error;
			$this->output( 401 );
		}


		/**
		 * Displays an invalid API key error if the API key provided couldn't be validated
		 */
		private function invalid_key() {
			$error = array();
			$error['error'] = __( 'Invalid API key', 'ultimate-member' );

			$this->data = $error;
			$this->output( 401 );
		}


		/**
		 * Listens for the API and then processes the API requests
		 */
		public function process_query() {
			global $wp_query;

			// Check for um-api var. Get out if not present
			if ( ! isset( $wp_query->query_vars['um-api'] ) )
				return;

			// Check for a valid user and set errors if necessary
			$this->validate_request();

			// Only proceed if no errors have been noted
			if( ! $this->is_valid_request )
				return;

			if( ! defined( 'UM_DOING_API' ) ) {
				define( 'UM_DOING_API', true );
			}

			// Determine the kind of query
			$args = array();
			$query_mode = $this->get_query_mode();
			foreach( $this->vars as $k ) {
				$args[ $k ] = isset( $wp_query->query_vars[ $k ] ) ? $wp_query->query_vars[ $k ] : null;
			}

			$data = array();

			switch( $query_mode ) {

				case 'get.stats':
					$data = $this->get_stats( $args );
					break;

				case 'get.users':
					$data = $this->get_users( $args );
					break;

				case 'get.user':
					$data = $this->get_auser( $args );
					break;

				case 'update.user':
					$data = $this->update_user( $args );
					break;

				case 'delete.user':
					$data = $this->delete_user( $args );
					break;

				default:
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_rest_query_mode
					 * @description Change query attributes
					 * @input_vars
					 * [{"var":"$data","type":"array","desc":"Query Data"},
					 * {"var":"$query_mode","type":"string","desc":"Query Mode"},
					 * {"var":"$args","type":"array","desc":"Query Arguments"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_rest_query_mode', 'function_name', 10, 3 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_rest_query_mode', 'my_rest_query_mode', 10, 3 );
					 * function um_rest_query_mode( $data, $query_mode, $args ) {
					 *     // your code here
					 *     return $data;
					 * }
					 * ?>
					 */
					$data = apply_filters( 'um_rest_query_mode', $data, $query_mode, $args );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_api_output_data
			 * @description Change output data for Rest API call
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"Output Data"},
			 * {"var":"$query_mode","type":"string","desc":"Query Mode"},
			 * {"var":"$api_class","type":"REST_API","desc":"REST_API instance"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_api_output_data', 'function_name', 10, 3 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_api_output_data', 'my_api_output_data', 10, 3 );
			 * function my_api_output_data( $data, $query_mode, $api_class ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			$this->data = apply_filters( 'um_api_output_data', $data, $query_mode, $this );

			// Log this API request, if enabled. We log it here because we have access to errors.
			$this->log_request( $this->data );

			// Send out data to the output function
			$this->output();
		}


		/**
		 * Get some stats
		 *
		 * @param $args
		 *
		 * @return array|mixed|void
		 */
		public function get_stats( $args ) {
			global $wpdb;
			extract( $args );

			$response = array();

			$query = "SELECT COUNT(*) FROM {$wpdb->prefix}users";
			$count = absint( $wpdb->get_var($query) );
			$response['stats']['total_users'] = $count;

			$pending = UM()->user()->get_pending_users_count();
			$response['stats']['pending_users'] = absint( $pending );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_rest_api_get_stats
			 * @description Change output data for Rest API get stats call
			 * @input_vars
			 * [{"var":"$response","type":"array","desc":"Output Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_rest_api_get_stats', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_rest_api_get_stats', 'my_rest_api_get_stats', 10, 1 );
			 * function my_rest_api_get_stats( $response ) {
			 *     // your code here
			 *     return $response;
			 * }
			 * ?>
			 */
			$response = apply_filters( 'um_rest_api_get_stats', $response );
			return $response;
		}


		/**
		 * Update user API query
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function update_user( $args ) {
			extract( $args );

			$response = array();
			$error = array();

			if ( !$id ) {
				$error['error'] = __('You must provide a user ID','ultimate-member');
				return $error;
			}

			if ( !$data ) {
				$error['error'] = __('You need to provide data to update','ultimate-member');
				return $error;
			}

			um_fetch_user( $id );

			switch ( $data ) {
				case 'status':
					UM()->user()->set_status( $value );
					$response['success'] = __('User status has been changed.','ultimate-member');
					break;
				case 'role':
					$wp_user_object = new \WP_User( $id );
					$old_roles = $wp_user_object->roles;
					$wp_user_object->set_role( $value );

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_after_member_role_upgrade
					 * @description Action after user role was changed
					 * @input_vars
					 * [{"var":"$new_roles","type":"array","desc":"New User Roles"},
					 * {"var":"$old_roles","type":"array","desc":"Old roles"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_after_member_role_upgrade', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_action( 'um_after_member_role_upgrade', 'my_after_member_role_upgrade', 10, 2 );
					 * function my_after_member_role_upgrade( $new_roles, $old_roles ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_after_member_role_upgrade', array( $value ), $old_roles );

					$response['success'] = __( 'User role has been changed.', 'ultimate-member' );
					break;
				default:
					update_user_meta( $id, $data, esc_attr( $value ) );
					$response['success'] = __('User meta has been changed.','ultimate-member');
					break;
			}

			return $response;
		}


		/**
		 * Process Get users API Request
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function get_users( $args ) {
			extract( $args );

			$response = array();
			$error = array();

			if ( !$number )
				$number = 10;

			if ( !$orderby )
				$orderby = 'user_registered';

			if ( !$order )
				$order = 'desc';

			$loop_a = array('number' => $number, 'orderby' => $orderby, 'order' => $order );

			if ( $include ) {
				$include = explode(',', $include );
				$loop_a['include'] = $include;
			}

			if ( $exclude ) {
				$exclude = explode(',', $exclude );
				$loop_a['exclude'] = $exclude;
			}

			$loop = get_users( $loop_a );

			foreach( $loop as $user ) {

				unset( $user->data->user_status );
				unset( $user->data->user_activation_key );
				unset( $user->data->user_pass );

				um_fetch_user( $user->ID );

				foreach( $user as $key => $val ) {
					if ( $key != 'data' ) continue;
					if ( $key == 'data' ) {
						$key = 'profile';
						$val->roles = $user->roles;
						$val->first_name = um_user('first_name');
						$val->last_name = um_user('last_name');
						$val->account_status = um_user('account_status');
						$val->profile_pic_original = $this->getsrc( um_user('profile_photo', 'original') );
						$val->profile_pic_normal = $this->getsrc( um_user('profile_photo', 200) );
						$val->profile_pic_small = $this->getsrc( um_user('profile_photo', 40) );
						$val->cover_photo = $this->getsrc( um_user('cover_photo', 1000) );

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_rest_userdata
						 * @description Change output data for Rest API userdata call
						 * @input_vars
						 * [{"var":"$value","type":"array","desc":"Output Data"},
						 * {"var":"$user_id","type":"string","desc":"User ID"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage
						 * <?php add_filter( 'um_rest_userdata', 'function_name', 10, 2 ); ?>
						 * @example
						 * <?php
						 * add_filter( 'um_rest_userdata', 'my_rest_userdata', 10, 2 );
						 * function my_rest_userdata( $value, $user_id ) {
						 *     // your code here
						 *     return $value;
						 * }
						 * ?>
						 */
						$val = apply_filters( 'um_rest_userdata', $val, $user->ID );
					}
					$response[ $user->ID ] = $val;
				}

			}

			return $response;
		}


		/**
		 * Process delete user via API
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function delete_user( $args ) {
			extract( $args );

			$response = array();
			$error = array();

			if ( !isset( $id ) ) {
				$error['error'] = __('You must provide a user ID','ultimate-member');
				return $error;
			}

			$user = get_userdata( $id );
			if ( !$user ) {
				$error['error'] = __('Invalid user specified','ultimate-member');
				return $error;
			}

			um_fetch_user( $id );
			UM()->user()->delete();

			$response['success'] = __('User has been successfully deleted.','ultimate-member');

			return $response;
		}


		/**
		 * Process Get user API Request
		 *
		 * @param $args
		 *
		 * @return array|mixed|void
		 */
		public function get_auser( $args ) {
			extract( $args );

			$response = array();
			$error = array();

			if ( !isset( $id ) ) {
				$error['error'] = __('You must provide a user ID','ultimate-member');
				return $error;
			}

			$user = get_userdata( $id );
			if ( ! $user ) {
				$error['error'] = __('Invalid user specified','ultimate-member');
				return $error;
			}

			unset( $user->data->user_status );
			unset( $user->data->user_activation_key );
			unset( $user->data->user_pass );

			um_fetch_user( $user->ID );

			if ( isset( $fields ) && $fields ) {
				$fields = explode(',', $fields );
				$response['ID'] = $user->ID;
				$response['username'] = $user->user_login;
				foreach ( $fields as $field ) {

					switch( $field ) {

						default:
							$response[ $field ] = ( um_profile( $field ) ) ? um_profile( $field ) : '';

							/**
							 * UM hook
							 *
							 * @type filter
							 * @title um_rest_get_auser
							 * @description Change output data for Rest API user authentification call
							 * @input_vars
							 * [{"var":"$response","type":"array","desc":"Output Data"},
							 * {"var":"$field","type":"string","desc":"Field Key"},
							 * {"var":"$user_id","type":"int","desc":"User ID"}]
							 * @change_log
							 * ["Since: 2.0"]
							 * @usage
							 * <?php add_filter( 'um_rest_get_auser', 'function_name', 10, 3 ); ?>
							 * @example
							 * <?php
							 * add_filter( 'um_rest_get_auser', 'my_rest_get_auser', 10, 3 );
							 * function my_rest_get_auser( $response, $field, $user_id ) {
							 *     // your code here
							 *     return $response;
							 * }
							 * ?>
							 */
							$response = apply_filters( 'um_rest_get_auser', $response, $field, $user->ID );
							break;

						case 'cover_photo':
							$response['cover_photo'] = $this->getsrc( um_user('cover_photo', 1000) );
							break;

						case 'profile_pic':
							$response['profile_pic_original'] = $this->getsrc( um_user('profile_photo', 'original') );
							$response['profile_pic_normal'] = $this->getsrc( um_user('profile_photo', 200) );
							$response['profile_pic_small'] = $this->getsrc( um_user('profile_photo', 40) );
							break;

						case 'status':
							$response['status'] = um_user('account_status');
							break;

						case 'role':
							//get priority role here
							$response['role'] = um_user( 'role' );
							break;

						case 'email':
						case 'user_email':
						$response['email'] = um_user('user_email');
						break;

					}

				}
			} else {

				foreach( $user as $key => $val ) {
					if ( $key != 'data' ) continue;
					if ( $key == 'data' ) {
						$key = 'profile';
						$val->roles = $user->roles;
						$val->first_name = um_user('first_name');
						$val->last_name = um_user('last_name');
						$val->account_status = um_user('account_status');
						$val->profile_pic_original = $this->getsrc( um_user('profile_photo', 'original') );
						$val->profile_pic_normal = $this->getsrc( um_user('profile_photo', 200) );
						$val->profile_pic_small = $this->getsrc( um_user('profile_photo', 40) );
						$val->cover_photo = $this->getsrc( um_user('cover_photo', 1000) );

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_rest_userdata
						 * @description Change output data for Rest API userdata call
						 * @input_vars
						 * [{"var":"$value","type":"array","desc":"Output Data"},
						 * {"var":"$user_id","type":"string","desc":"User ID"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage
						 * <?php add_filter( 'um_rest_userdata', 'function_name', 10, 2 ); ?>
						 * @example
						 * <?php
						 * add_filter( 'um_rest_userdata', 'my_rest_userdata', 10, 2 );
						 * function my_rest_userdata( $value, $user_id ) {
						 *     // your code here
						 *     return $value;
						 * }
						 * ?>
						 */
						$val = apply_filters( 'um_rest_userdata', $val, $user->ID );
					}
					$response = $val;
				}

			}

			return $response;
		}


		/**
		 * Get source
		 *
		 * @param $image
		 *
		 * @return string
		 */
		public function getsrc( $image ) {
			if (preg_match('/<img.+?src(?: )*=(?: )*[\'"](.*?)[\'"]/si', $image, $arrResult)) {
				return $arrResult[1];
			}
			return '';
		}


		/**
		 * Determines the kind of query requested and also ensure it is a valid query
		 *
		 * @return null
		 */
		public function get_query_mode() {
			global $wp_query;

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_api_valid_query_modes
			 * @description Whitelist UM query options
			 * @input_vars
			 * [{"var":"$list","type":"array","desc":"Whitelist"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_api_valid_query_modes', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_api_valid_query_modes', 'my_api_valid_query_modes', 10, 1 );
			 * function my_api_valid_query_modes( $list ) {
			 *     // your code here
			 *     return $list;
			 * }
			 * ?>
			 */
			$accepted = apply_filters( 'um_api_valid_query_modes', array(
				'get.users',
				'get.user',
				'update.user',
				'delete.user',
				'get.following',
				'get.followers',
				'get.stats',
			) );

			$query = isset( $wp_query->query_vars['um-api'] ) ? $wp_query->query_vars['um-api'] : null;
			$error = array();
			// Make sure our query is valid
			if ( ! in_array( $query, $accepted ) ) {
				$error['error'] = __( 'Invalid query!', 'ultimate-member' );

				$this->data = $error;
				$this->output();
			}

			return $query;
		}


		/**
		 * Get page number
		 */
		public function get_paged() {
			global $wp_query;

			return isset( $wp_query->query_vars['page'] ) ? $wp_query->query_vars['page'] : 1;
		}


		/**
		 * Retrieve the output format
		 */
		public function get_output_format() {
			global $wp_query;

			$format = isset( $wp_query->query_vars['format'] ) ? $wp_query->query_vars['format'] : 'json';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_api_output_format
			 * @description UM Rest API output format
			 * @input_vars
			 * [{"var":"$format","type":"string","desc":"Format"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_api_output_format', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_api_output_format', 'my_api_output_format', 10, 1 );
			 * function my_api_output_format( $format ) {
			 *     // your code here
			 *     return $format;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_api_output_format', $format );
		}


		/**
		 * Log each API request, if enabled
		 *
		 * @param array $data
		 */
		private function log_request( $data = array() ) {
			if ( ! $this->log_requests )
				return;
		}


		/**
		 * Retrieve the output data
		 */
		public function get_output() {
			return $this->data;
		}


		/**
		 * Output Query in either JSON/XML. The query data is outputted as JSON
		 * by default
		 */
		public function output( $status_code = 200 ) {
			global $wp_query;

			$format = $this->get_output_format();

			status_header( $status_code );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_api_output_before
			 * @description Action before API output
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"API data"},
			 * {"var":"$rest_api","type":"object","desc":"REST API class"},
			 * {"var":"$format","type":"string","desc":"Format"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_api_output_before', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_action( 'um_api_output_before', 'my_api_output_before', 10, 3 );
			 * function my_api_output_before( $data, $rest_api, $format ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_api_output_before', $this->data, $this, $format );

			switch ( $format ) :

				case 'xml' :

					require_once um_path . 'includes/lib/array2xml.php';
					$xml = Array2XML::createXML( 'um', $this->data );
					echo $xml->saveXML();

					break;

				case 'json' :
				case '' :

				header( 'Content-Type: application/json' );
				if ( ! empty( $this->pretty_print ) )
					echo json_encode( $this->data, $this->pretty_print );
				else
					echo json_encode( $this->data );

				break;


				default :

					// Allow other formats to be added via extensions
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_api_output_{$format}
					 * @description Action before API output
					 * @input_vars
					 * [{"var":"$data","type":"array","desc":"API data"},
					 * {"var":"$rest_api","type":"object","desc":"REST API class"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_api_output_{$format}', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_action( 'um_api_output_{$format}', 'my_api_output', 10, 2 );
					 * function my_api_output( $data, $rest_api ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_api_output_' . $format, $this->data, $this );

					break;

			endswitch;

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_api_output_after
			 * @description Action after API output
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"API data"},
			 * {"var":"$rest_api","type":"object","desc":"REST API class"},
			 * {"var":"$format","type":"string","desc":"Format"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_api_output_after', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_action( 'um_api_output_after', 'my_api_output_after', 10, 3 );
			 * function my_api_output_after( $data, $rest_api, $format ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_api_output_after', $this->data, $this, $format );

			die();
		}


		/**
		 * Modify User Profile Page fields
		 *
		 * @param $content
		 * @param $user
		 * @return string
		 */
		function user_key_field( $content, $user ) {
			if ( empty( $user ) )
				return $content;

			if( ! isset( $user->ID ) )
				return $content;

			if ( current_user_can( 'edit_users' ) && current_user_can( 'edit_user', $user->ID ) ) {
				$user = get_userdata( $user->ID );

				ob_start(); ?>

				<table class="form-table">
					<tbody>
					<tr>
						<th>
							<label for="um_set_api_key"><?php _e( 'Ultimate Member REST API', 'ultimate-member' ); ?></label>
						</th>
						<td>
							<?php if ( empty( $user->um_user_public_key ) ) { ?>
								<p><input name="um_set_api_key" type="checkbox" id="um_set_api_key" value="0" />
									<span class="description"><?php _e( 'Generate API Key', 'ultimate-member' ); ?></span></p>
							<?php } else { ?>
								<p>
									<strong><?php _e( 'Public key:', 'ultimate-member' ); ?>&nbsp;</strong><span id="publickey"><?php echo $user->um_user_public_key; ?></span><br/>
									<strong><?php _e( 'Secret key:', 'ultimate-member' ); ?>&nbsp;</strong><span id="privatekey"><?php echo $user->um_user_secret_key; ?></span><br/>
									<strong><?php _e( 'Token:', 'ultimate-member' ); ?>&nbsp;</strong><span id="token"><?php echo $this->get_token( $user->ID ); ?></span>
								</p>
								<p><input name="um_set_api_key" type="checkbox" id="um_set_api_key" value="0" />
									<span class="description"><?php _e( 'Revoke API Keys', 'ultimate-member' ); ?></span></p>
							<?php } ?>
						</td>
					</tr>
					</tbody>
				</table>

				<?php $content .= ob_get_clean();
			}


			return $content;
		}


		/**
		 * Generate new API keys for a user
		 *
		 * @param int $user_id
		 * @param bool $regenerate
		 *
		 * @return bool
		 */
		public function generate_api_key( $user_id = 0, $regenerate = false ) {

			if( empty( $user_id ) ) {
				return false;
			}

			$user = get_userdata( $user_id );

			if( ! $user ) {
				return false;
			}

			if ( empty( $user->um_user_public_key ) ) {
				update_user_meta( $user_id, 'um_user_public_key', $this->generate_public_key( $user->user_email ) );
				update_user_meta( $user_id, 'um_user_secret_key', $this->generate_private_key( $user->ID ) );
			} elseif( $regenerate == true ) {
				$this->revoke_api_key( $user->ID );
				update_user_meta( $user_id, 'um_user_public_key', $this->generate_public_key( $user->user_email ) );
				update_user_meta( $user_id, 'um_user_secret_key', $this->generate_private_key( $user->ID ) );
			} else {
				return false;
			}

			return true;
		}


		/**
		 * Revoke a users API keys
		 *
		 * @param int $user_id
		 *
		 * @return bool
		 */
		public function revoke_api_key( $user_id = 0 ) {

			if( empty( $user_id ) ) {
				return false;
			}

			$user = get_userdata( $user_id );

			if( ! $user ) {
				return false;
			}

			if ( ! empty( $user->um_user_public_key ) ) {
				delete_transient( md5( 'um_api_user_' . $user->um_user_public_key ) );
				delete_user_meta( $user_id, 'um_user_public_key' );
				delete_user_meta( $user_id, 'um_user_secret_key' );
			} else {
				return false;
			}

			return true;
		}


		/**
		 * Generate and Save API key
		 *
		 * @param $user_id
		 */
		public function update_key( $user_id ) {
			if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST['um_set_api_key'] ) ) {

				$user = get_userdata( $user_id );

				if ( empty( $user->um_user_public_key ) ) {
					update_user_meta( $user_id, 'um_user_public_key', $this->generate_public_key( $user->user_email ) );
					update_user_meta( $user_id, 'um_user_secret_key', $this->generate_private_key( $user->ID ) );
				} else {
					$this->revoke_api_key( $user_id );
				}
			}
		}


		/**
		 * Generate the public key for a user
		 *
		 * @param string $user_email
		 *
		 * @return string
		 */
		private function generate_public_key( $user_email = '' ) {
			$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
			$public   = hash( 'md5', $user_email . $auth_key . date( 'U' ) );
			return $public;
		}


		/**
		 * Generate the secret key for a user
		 *
		 * @param int $user_id
		 *
		 * @return string
		 */
		private function generate_private_key( $user_id = 0 ) {
			$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
			$secret   = hash( 'md5', $user_id . $auth_key . date( 'U' ) );
			return $secret;
		}


		/**
		 * Retrieve the user's token
		 *
		 * @param int $user_id
		 *
		 * @return string
		 */
		private function get_token( $user_id = 0 ) {
			$user = get_userdata( $user_id );
			return hash( 'md5', $user->um_user_secret_key . $user->um_user_public_key );
		}

	}
}