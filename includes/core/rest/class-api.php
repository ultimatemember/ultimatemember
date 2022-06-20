<?php
namespace um\core\rest;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\rest\API' ) ) {


	/**
	 * Class API
	 *
	 * @package um\core\rest
	 */
	class API {


		/**
		 * @var bool|int|null
		 */
		protected $pretty_print = false;


		/**
		 * @var bool|mixed|void
		 */
		public 	$log_requests = true;


		/**
		 * @var bool
		 */
		protected $is_valid_request = false;


		/**
		 * @var int
		 */
		protected $user_id = 0;


		/**
		 * @var
		 */
		protected $stats;


		/**
		 * @var array
		 */
		protected $data = array();


		/**
		 * @var bool
		 */
		protected $override = true;


		/**
		 * @var array
		 */
		protected $vars = array();


		/**
		 * REST_API constructor.
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'add_endpoint' ) );
			add_action( 'template_redirect', array( $this, 'process_query' ), -1 );

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
		 * Listens for the API and then processes the API requests
		 */
		public function process_query() {
			global $wp_query;

			// Check for um-api var. Get out if not present
			if ( ! isset( $wp_query->query_vars['um-api'] ) ) {
				return;
			}

			// Check for a valid user and set errors if necessary
			$this->validate_request();

			// Only proceed if no errors have been noted
			if ( ! $this->is_valid_request ) {
				return;
			}

			if ( ! defined( 'UM_DOING_API' ) ) {
				define( 'UM_DOING_API', true );
			}

			// Determine the kind of query
			$args = array();
			$query_mode = $this->get_query_mode();
			foreach ( $this->vars as $k ) {
				$args[ $k ] = isset( $wp_query->query_vars[ $k ] ) ? $wp_query->query_vars[ $k ] : null;
			}

			$data = array();

			switch ( $query_mode ) {
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
					break;
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
		 * Validate the API request
		 */
		protected function validate_request() {

		}


		/**
		 * Retrieve the user ID based on the public key provided
		 *
		 * @param string $key
		 *
		 * @return bool
		 */
		public function get_user( $key = '' ) {
			return false;
		}


		/**
		 * Displays a missing authentication error if all the parameters aren't
		 * provided
		 */
		protected function missing_auth() {
			$error = array();
			$error['error'] = __( 'You must specify both a token and API key!', 'ultimate-member' );

			$this->data = $error;
			$this->output( 401 );
		}


		/**
		 * Displays an authentication failed error if the user failed to provide valid credentials
		 */
		protected function invalid_auth() {
			$error = array();
			$error['error'] = __( 'Your request could not be authenticated', 'ultimate-member' );

			$this->data = $error;
			$this->output( 401 );
		}


		/**
		 * Displays an invalid API key error if the API key provided couldn't be validated
		 */
		protected function invalid_key() {
			$error = array();
			$error['error'] = __( 'Invalid API key', 'ultimate-member' );

			$this->data = $error;
			$this->output( 401 );
		}


		/**
		 * Get some stats
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function get_stats( $args ) {
			global $wpdb;

			$response = array();

			$count = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}users" ) );
			$response['stats']['total_users'] = $count;

			$pending = UM()->query()->get_pending_users_count();
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
		 * Process Get users API Request
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function get_users( $args ) {
			return array();
		}


		/**
		 * Update user API query
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function update_user( $args ) {
			return array();
		}


		/**
		 * Process delete user via API
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function delete_user( $args ) {
			return array();
		}


		/**
		 * Process Get user API Request
		 *
		 * @param $args
		 *
		 * @return array
		 */
		public function get_auser( $args ) {
			return array();
		}


		/**
		 * Get source
		 *
		 * @param $image
		 *
		 * @return string
		 */
		protected function getsrc( $image ) {
			if ( preg_match( '/<img.+?src(?: )*=(?: )*[\'"](.*?)[\'"]/si', $image, $arrResult ) ) {
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
			return apply_filters( 'um_api_output_format', 'json' );
		}


		/**
		 * Log each API request, if enabled
		 *
		 * @param array $data
		 */
		protected function log_request( $data = array() ) {
			if ( ! $this->log_requests ) {
				return;
			}
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
		 *
		 * @param int $status_code
		 */
		public function output( $status_code = 200 ) {
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

			switch ( $format ) {

				case 'xml' :

					require_once um_path . 'includes/lib/array2xml.php';
					$xml = \Array2XML::createXML( 'um', $this->data );
					echo $xml->saveXML();

					break;

				case 'json' :
				case '' :

					header( 'Content-Type: application/json' );
					if ( ! empty( $this->pretty_print ) ) {
						echo json_encode( $this->data, $this->pretty_print );
					} else {
						echo json_encode( $this->data );
					}

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
			}

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

	}
}
