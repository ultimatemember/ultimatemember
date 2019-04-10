<?php
namespace um\core\rest;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\rest\API_v1' ) ) {


	/**
	 * Class REST_API
	 * @package um\core
	 */
	class API_v1 extends API {

		/**
		 *
		 */
		const VERSION = '1.0';


		/**
		 * REST_API constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_filter( 'query_vars', array( $this, 'query_vars' ) );
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
		protected function validate_request() {
			global $wp_query;

			$this->override = false;

			// Make sure we have both user and api key
			if ( ! empty( $wp_query->query_vars['um-api'] ) ) {

				if ( empty( $wp_query->query_vars['token'] ) || empty( $wp_query->query_vars['key'] ) ) {
					$this->missing_auth();
				}

				// Retrieve the user by public API key and ensure they exist
				if ( ! ( $user = $this->get_user( $wp_query->query_vars['key'] ) ) ) {
					$this->invalid_key();
				} else {
					$token  = urldecode( $wp_query->query_vars['token'] );
					$secret = get_user_meta( $user, 'um_user_secret_key', true );
					$public = urldecode( $wp_query->query_vars['key'] );

					if ( hash_equals( md5( $secret . $public ), $token ) ) {
						$this->is_valid_request = true;
					} else {
						$this->invalid_auth();
					}
				}

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

			if ( empty( $key ) ) {
				$key = urldecode( $wp_query->query_vars['key'] );
			}

			if ( empty( $key ) ) {
				return false;
			}

			$user = get_transient( md5( 'um_api_user_' . $key ) );

			if ( false === $user ) {
				$user = $wpdb->get_var( $wpdb->prepare(
					"SELECT user_id 
					FROM $wpdb->usermeta 
					WHERE meta_key = 'um_user_public_key' AND 
					      meta_value = %s 
					LIMIT 1",
					$key
				) );
				set_transient( md5( 'um_api_user_' . $key ) , $user, DAY_IN_SECONDS );
			}

			if ( $user != null ) {
				$this->user_id = $user;
				return $user;
			}

			return false;
		}


		/**
		 * Process Get users API Request
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function get_users( $args ) {
			/**
			 * @var int $number
			 * @var string $orderby
			 * @var string $order
			 * @var string $include
			 * @var string $exclude
			 */
			extract( $args );

			$response = array();

			if ( ! $number ) {
				$number = 10;
			}

			if ( ! $orderby ) {
				$orderby = 'user_registered';
			}

			if ( ! $order ) {
				$order = 'desc';
			}

			$loop_a = array( 'number' => $number, 'orderby' => $orderby, 'order' => $order );

			if ( $include ) {
				$include = explode(',', $include );
				$loop_a['include'] = $include;
			}

			if ( $exclude ) {
				$exclude = explode(',', $exclude );
				$loop_a['exclude'] = $exclude;
			}

			$loop = get_users( $loop_a );

			foreach ( $loop as $user ) {

				unset( $user->data->user_status );
				unset( $user->data->user_activation_key );
				unset( $user->data->user_pass );

				um_fetch_user( $user->ID );

				foreach ( $user as $key => $val ) {
					if ( $key != 'data' ) {
						continue;
					}

					$key = 'profile';
					$val->roles = $user->roles;
					$val->first_name = um_user('first_name');
					$val->last_name = um_user('last_name');
					$val->account_status = um_user('account_status');
					$val->profile_pic_original = um_get_user_avatar_url('', 'original');
					$val->profile_pic_normal = um_get_user_avatar_url('', 200);
					$val->profile_pic_small = um_get_user_avatar_url('', 40);
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
					$response[ $user->ID ] = $val;
				}
			}

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
			/**
			 * @var int $id
			 * @var string $data
			 * @var string $value
			 */
			extract( $args );

			$response = array();
			$error = array();

			if ( ! $id ) {
				$error['error'] = __( 'You must provide a user ID', 'ultimate-member' );
				return $error;
			}

			if ( ! $data ) {
				$error['error'] = __( 'You need to provide data to update', 'ultimate-member' );
				return $error;
			}

			um_fetch_user( $id );

			switch ( $data ) {
				case 'status':
					UM()->user()->set_status( $value );
					$response['success'] = __( 'User status has been changed.', 'ultimate-member' );
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
					do_action( 'um_after_member_role_upgrade', array( $value ), $old_roles, $id );

					$response['success'] = __( 'User role has been changed.', 'ultimate-member' );
					break;
				default:
					update_user_meta( $id, $data, esc_attr( $value ) );
					$response['success'] = __( 'User meta has been changed.', 'ultimate-member' );
					break;
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
			/**
			 * @var int $id
			 */
			extract( $args );

			$response = array();
			$error = array();

			if ( ! isset( $id ) ) {
				$error['error'] = __( 'You must provide a user ID', 'ultimate-member' );
				return $error;
			}

			$user = get_userdata( $id );
			if ( ! $user ) {
				$error['error'] = __( 'Invalid user specified', 'ultimate-member' );
				return $error;
			}

			um_fetch_user( $id );
			UM()->user()->delete();

			$response['success'] = __( 'User has been successfully deleted.', 'ultimate-member' );

			return $response;
		}


		/**
		 * Process Get user API Request
		 *
		 * @param $args
		 *
		 * @return array|mixed
		 */
		public function get_auser( $args ) {
			/**
			 * @var int $um_id
			 * @var string $um_fields
			 */
			extract( $args );

			$response = array();
			$error = array();

			if ( ! isset( $id ) ) {
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

					switch ( $field ) {

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
							$response['profile_pic_original'] = um_get_user_avatar_url('', 'original');
							$response['profile_pic_normal'] = um_get_user_avatar_url('', 200);
							$response['profile_pic_small'] = um_get_user_avatar_url('', 40);
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

				foreach ( $user as $key => $val ) {
					if ( $key != 'data' ) {
						continue;
					}

					$key = 'profile';
					$val->roles = $user->roles;
					$val->first_name = um_user( 'first_name' );
					$val->last_name = um_user('last_name' );
					$val->account_status = um_user( 'account_status' );
					$val->profile_pic_original = um_get_user_avatar_url( '', 'original' );
					$val->profile_pic_normal = um_get_user_avatar_url( '', 200 );
					$val->profile_pic_small = um_get_user_avatar_url( '', 40 );
					$val->cover_photo = $this->getsrc( um_user( 'cover_photo', 1000 ) );

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
	}
}