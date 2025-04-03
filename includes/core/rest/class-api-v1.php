<?php
namespace um\core\rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
				$user = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT user_id
						FROM $wpdb->usermeta
						WHERE meta_key = 'um_user_public_key' AND
							  meta_value = %s
						LIMIT 1",
						$key
					)
				);
				set_transient( md5( 'um_api_user_' . $key ) , $user, DAY_IN_SECONDS );
			}

			if ( $user != null ) {
				$this->user_id = $user;
				return $user;
			}

			return false;
		}

		/**
		 * Process Get users API Request.
		 *
		 * @param array $args
		 *
		 * @return array
		 */
		public function get_users( $args ) {
			$response = array();

			$number  = array_key_exists( 'number', $args ) && is_numeric( $args['number'] ) ? absint( $args['number'] ) : 10;
			$orderby = array_key_exists( 'orderby', $args ) ? sanitize_key( $args['orderby'] ) : 'user_registered';
			$order   = array_key_exists( 'order', $args ) ? sanitize_key( $args['order'] ) : 'desc';

			$loop_a = array(
				'number'  => $number,
				'orderby' => $orderby,
				'order'   => $order,
			);

			if ( array_key_exists( 'include', $args ) ) {
				$include           = explode( ',', sanitize_text_field( $args['include'] ) );
				$loop_a['include'] = $include;
			}

			if ( array_key_exists( 'exclude', $args ) ) {
				$exclude           = explode( ',', sanitize_text_field( $args['exclude'] ) );
				$loop_a['exclude'] = $exclude;
			}

			$loop = get_users( $loop_a );

			foreach ( $loop as $user ) {
				unset( $user->data->user_status, $user->data->user_activation_key, $user->data->user_pass );

				um_fetch_user( $user->ID );

				foreach ( $user as $key => $val ) {
					if ( 'data' !== $key ) {
						continue;
					}

					$val->roles                = $user->roles;
					$val->first_name           = um_user( 'first_name' );
					$val->last_name            = um_user( 'last_name' );
					$val->account_status       = UM()->common()->users()->get_status( $user->ID );
					$val->profile_pic_original = um_get_user_avatar_url( '', 'original' );
					$val->profile_pic_normal   = um_get_user_avatar_url( '', 200 );
					$val->profile_pic_small    = um_get_user_avatar_url( '', 40 );
					$val->cover_photo          = $this->getsrc( um_user( 'cover_photo', 1000 ) );

					/**
					 * Filters the output data for Rest API userdata call.
					 *
					 * @param {mixed} $val      User data value.
					 * @param {int}   $user_id  User ID.
					 *
					 * @return {mixed} User data value.
					 *
					 * @since 2.0
					 * @hook um_rest_userdata
					 *
					 * @example <caption>Force change the output data for Rest API userdata call.</caption>
					 * function my_custom_um_rest_userdata( $value, $user_id  ) {
					 *     // your code here
					 *     return $response;
					 * }
					 * add_filter( 'um_rest_userdata', 'my_custom_um_rest_userdata', 10, 2 );
					 */
					$response[ $user->ID ] = apply_filters( 'um_rest_userdata', $val, $user->ID );
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
			$response = array();
			$error    = array();

			if ( empty( $args['id'] ) ) {
				$error['error'] = __( 'You must provide a user ID', 'ultimate-member' );
				return $error;
			}

			if ( empty( $args['data'] ) ) {
				$error['error'] = __( 'You need to provide data to update', 'ultimate-member' );
				return $error;
			}

			if ( ! array_key_exists( 'value', $args ) ) {
				$error['error'] = __( 'You need to provide value to update', 'ultimate-member' );
				return $error;
			}

			$id    = absint( $args['id'] );
			$data  = sanitize_text_field( $args['data'] );
			$value = sanitize_text_field( $args['value'] );

			um_fetch_user( $id );

			switch ( $data ) {
				case 'status':
					// Force update of the user status without email notifications.
					UM()->common()->users()->set_status( $id, $value );
					$response['success'] = __( 'User status has been changed.', 'ultimate-member' );
					break;
				case 'role':
					$wp_user_object = new \WP_User( $id );
					$old_roles      = $wp_user_object->roles;
					$wp_user_object->set_role( $value );

					/** This action is documented in includes/core/class-user.php */
					do_action( 'um_after_member_role_upgrade', array( $value ), $old_roles, $id );

					$response['success'] = __( 'User role has been changed.', 'ultimate-member' );
					break;
				default:
					update_user_meta( $id, $data, $value );
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
			$response = array();
			$error    = array();

			if ( empty( $args['id'] ) ) {
				$error['error'] = __( 'You must provide a user ID', 'ultimate-member' );
				return $error;
			}

			$id = absint( $args['id'] );

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
			$response = array();
			$error    = array();

			if ( empty( $args['id'] ) ) {
				$error['error'] = __( 'You must provide a user ID', 'ultimate-member' );
				return $error;
			}

			$id   = absint( $args['id'] );
			$user = get_userdata( $id );
			if ( ! $user ) {
				$error['error'] = __( 'Invalid user specified', 'ultimate-member' );
				return $error;
			}

			unset( $user->data->user_status, $user->data->user_activation_key, $user->data->user_pass );

			um_fetch_user( $user->ID );

			if ( array_key_exists( 'fields', $args ) ) {
				$fields               = explode( ',', sanitize_text_field( $args['fields'] ) );
				$response['ID']       = $user->ID;
				$response['username'] = $user->user_login;
				foreach ( $fields as $field ) {

					switch ( $field ) {
						default:
							$profile_data       = um_profile( $field );
							$response[ $field ] = $profile_data ? $profile_data : '';

							/**
							 * Filters the output data for Rest API user authentication call.
							 *
							 * @param {array}  $response REST API response.
							 * @param {string} $field    Field Options.
							 * @param {int}    $user_id  User ID.
							 *
							 * @return {array} REST API response.
							 *
							 * @since 2.0
							 * @hook um_rest_get_auser
							 *
							 * @example <caption>Force change the output data for Rest API user authentication call.</caption>
							 * function my_custom_um_rest_get_auser( $response, $field, $user_id  ) {
							 *     // your code here
							 *     return $response;
							 * }
							 * add_filter( 'um_rest_get_auser', 'my_custom_um_rest_get_auser', 10, 3 );
							 */
							$response = apply_filters( 'um_rest_get_auser', $response, $field, $user->ID );
							break;
						case 'cover_photo':
							$response['cover_photo'] = $this->getsrc( um_user( 'cover_photo', 1000 ) );
							break;
						case 'profile_pic':
							$response['profile_pic_original'] = um_get_user_avatar_url( '', 'original' );
							$response['profile_pic_normal']   = um_get_user_avatar_url( '', 200 );
							$response['profile_pic_small']    = um_get_user_avatar_url( '', 40 );
							break;
						case 'status':
							$response['status'] = UM()->common()->users()->get_status( $user->ID );
							break;
						case 'role':
							//get priority role here
							$response['role'] = um_user( 'role' );
							break;
						case 'email':
						case 'user_email':
							$response['email'] = um_user( 'user_email' );
							break;
					}
				}
			} else {
				foreach ( $user as $key => $val ) {
					if ( 'data' !== $key ) {
						continue;
					}

					$val->roles                = $user->roles;
					$val->first_name           = um_user( 'first_name' );
					$val->last_name            = um_user( 'last_name' );
					$val->account_status       = UM()->common()->users()->get_status( $user->ID );
					$val->profile_pic_original = um_get_user_avatar_url( '', 'original' );
					$val->profile_pic_normal   = um_get_user_avatar_url( '', 200 );
					$val->profile_pic_small    = um_get_user_avatar_url( '', 40 );
					$val->cover_photo          = $this->getsrc( um_user( 'cover_photo', 1000 ) );

					/** This filter is documented in includes/core/rest/class-api-v1.php */
					$response = apply_filters( 'um_rest_userdata', $val, $user->ID );
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
			if ( preg_match( '/<img.+?src(?: )*=(?: )*[\'"](.*?)[\'"]/si', $image, $arr_result ) ) {
				return $arr_result[1];
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
			 * Filters the REST API output format. JSON by default.
			 *
			 * @param {string} $format REST API output format.
			 *
			 * @return {string} REST API output format.
			 *
			 * @since 1.3.x
			 * @hook um_api_output_format
			 *
			 * @example <caption>Changing the REST API output format.</caption>
			 * function my_custom_um_api_output_format( $format ) {
			 *     // your code here
			 *     $format = 'xml';
			 *     return $format;
			 * }
			 * add_filter( 'um_api_output_format', 'my_custom_um_api_output_format' );
			 */
			return apply_filters( 'um_api_output_format', $format );
		}
	}
}
