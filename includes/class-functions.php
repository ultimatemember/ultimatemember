<?php if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'UM_Functions' ) ) {


	/**
	 * Class UM_Functions
	 */
	class UM_Functions {


		/**
		 * Store URL
		 *
		 * @var string
		 */
		var $store_url = 'https://ultimatemember.com/';


		/**
		 * WP remote Post timeout
		 * @var int
		 */
		var $request_timeout = 60;


		/**
		 * UM_Functions constructor.
		 */
		function __construct() {
		}


		/**
		 * Check if AJAX now
		 *
		 * @return bool
		 */
		function is_ajax() {
			return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
		}


		/**
		 * Check frontend nonce
		 *
		 * @param bool $action
		 */
		function check_ajax_nonce( $action = false ) {
			$nonce = isset( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : '';
			$action = empty( $action ) ? 'um-frontend-nonce' : $action;

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error( esc_js( __( 'Wrong Nonce', 'ultimate-member' ) ) );
			}
		}


		/**
		 * What type of request is this?
		 *
		 * @param string $type String containing name of request type (ajax, frontend, cron or admin)
		 *
		 * @return bool
		 */
		public function is_request( $type ) {
			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}

			return false;
		}


		/**
		 * Help Tip displaying
		 *
		 * Function for render/displaying UltimateMember help tip
		 *
		 * @since  2.0.0
		 *
		 * @param string $tip Help tip text
		 * @param bool $allow_html Allow sanitized HTML if true or escape
		 * @param bool $echo Return HTML or echo
		 * @return string
		 */
		function tooltip( $tip, $allow_html = false, $echo = true ) {
			if ( $allow_html ) {

				$tip = htmlspecialchars( wp_kses( html_entity_decode( $tip ), array(
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
					'small'  => array(),
					'span'   => array(),
					'ul'     => array(),
					'li'     => array(),
					'ol'     => array(),
					'p'      => array(),
				) ) );

			} else {
				$tip = esc_attr( $tip );
			}

			ob_start(); ?>

			<span class="um_tooltip dashicons dashicons-editor-help" title="<?php echo $tip ?>"></span>

			<?php if ( $echo ) {
				ob_get_flush();
				return '';
			} else {
				return ob_get_clean();
			}

		}


		/**
		 * @return mixed|void
		 */
		function excluded_taxonomies() {
			$taxes = array(
				'nav_menu',
				'link_category',
				'post_format',
			);

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_excluded_taxonomies
			 * @description Exclude taxonomies for UM
			 * @input_vars
			 * [{"var":"$taxes","type":"array","desc":"Taxonomies keys"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_excluded_taxonomies', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_excluded_taxonomies', 'my_excluded_taxonomies', 10, 1 );
			 * function my_excluded_taxonomies( $taxes ) {
			 *     // your code here
			 *     return $taxes;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_excluded_taxonomies', $taxes );
		}


		/**
		 * Output templates
		 *
		 * @access public
		 * @param string $template_name
		 * @param string $basename (default: '')
		 * @param array $t_args (default: array())
		 * @param bool $echo
		 *
		 * @return string|void
		 */
		function get_template( $template_name, $basename = '', $t_args = array(), $echo = false ) {
			if ( ! empty( $t_args ) && is_array( $t_args ) ) {
				extract( $t_args );
			}

			$path = '';
			if ( $basename ) {
				// use '/' instead of "DIRECTORY_SEPARATOR", because wp_normalize_path makes the correct replace
				$array = explode( '/', wp_normalize_path( trim( $basename ) ) );
				$path  = $array[0];
			}

			$located = $this->locate_template( $template_name, $path );
			if ( ! file_exists( $located ) ) {
				_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
				return;
			}


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_get_template
			 * @description Change template location
			 * @input_vars
			 * [{"var":"$located","type":"string","desc":"template Located"},
			 * {"var":"$template_name","type":"string","desc":"Template Name"},
			 * {"var":"$path","type":"string","desc":"Template Path at server"},
			 * {"var":"$t_args","type":"array","desc":"Template Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_get_template', 'function_name', 10, 4 );
			 * @example
			 * <?php
			 * add_filter( 'um_get_template', 'my_get_template', 10, 4 );
			 * function my_get_template( $located, $template_name, $path, $t_args ) {
			 *     // your code here
			 *     return $located;
			 * }
			 * ?>
			 */
			$located = apply_filters( 'um_get_template', $located, $template_name, $path, $t_args );

			ob_start();

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_template_part
			 * @description Make some action before include template file
			 * @input_vars
			 * [{"var":"$template_name","type":"string","desc":"Template Name"},
			 * {"var":"$path","type":"string","desc":"Template Path at server"},
			 * {"var":"$located","type":"string","desc":"template Located"},
			 * {"var":"$t_args","type":"array","desc":"Template Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_template_part', 'function_name', 10, 4 );
			 * @example
			 * <?php
			 * add_action( 'um_before_template_part', 'my_before_template_part', 10, 4 );
			 * function my_before_template_part( $template_name, $path, $located, $t_args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_before_template_part', $template_name, $path, $located, $t_args );
			include( $located );

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_template_part
			 * @description Make some action after include template file
			 * @input_vars
			 * [{"var":"$template_name","type":"string","desc":"Template Name"},
			 * {"var":"$path","type":"string","desc":"Template Path at server"},
			 * {"var":"$located","type":"string","desc":"template Located"},
			 * {"var":"$t_args","type":"array","desc":"Template Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_template_part', 'function_name', 10, 4 );
			 * @example
			 * <?php
			 * add_action( 'um_after_template_part', 'my_after_template_part', 10, 4 );
			 * function my_after_template_part( $template_name, $path, $located, $t_args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_after_template_part', $template_name, $path, $located, $t_args );
			$html = ob_get_clean();

			if ( ! $echo ) {
				return $html;
			} else {
				echo $html;
				return;
			}
		}


		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * @access public
		 * @param string $template_name
		 * @param string $path (default: '')
		 * @return string
		 */
		function locate_template( $template_name, $path = '' ) {
			// check if there is template at theme folder
			$template = locate_template( array(
				trailingslashit( 'ultimate-member' . DIRECTORY_SEPARATOR . $path ) . $template_name
			) );

			if ( ! $template ) {
				if ( $path ) {
					$template = trailingslashit( trailingslashit( WP_PLUGIN_DIR ) . $path );
				} else {
					$template = trailingslashit( um_path );
				}
				$template .= 'templates' . DIRECTORY_SEPARATOR . $template_name;
			}


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_locate_template
			 * @description Change template locate
			 * @input_vars
			 * [{"var":"$template","type":"string","desc":"Template locate"},
			 * {"var":"$template_name","type":"string","desc":"Template Name"},
			 * {"var":"$path","type":"string","desc":"Template Path at server"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_locate_template', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_locate_template', 'my_locate_template', 10, 3 );
			 * function my_locate_template( $template, $template_name, $path ) {
			 *     // your code here
			 *     return $template;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_locate_template', $template, $template_name, $path );
		}


		/**
		 * @return mixed|void
		 */
		function cpt_list() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_cpt_list
			 * @description Extend UM Custom Post Types
			 * @input_vars
			 * [{"var":"$list","type":"array","desc":"Custom Post Types list"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_cpt_list', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_cpt_list', 'my_cpt_list', 10, 1 );
			 * function my_admin_pending_queue( $list ) {
			 *     // your code here
			 *     return $list;
			 * }
			 * ?>
			 */
			$cpt = apply_filters( 'um_cpt_list', array( 'um_form', 'um_directory' ) );
			return $cpt;
		}


		/**
		 * @param array $array
		 * @param string $key
		 * @param array $insert_array
		 *
		 * @return array
		 */
		function array_insert_before( $array, $key, $insert_array ) {
			$index = array_search( $key, array_keys( $array ) );
			if ( $index === false ) {
				return $array;
			}

			$array = array_slice( $array, 0, $index, true ) +
			         $insert_array +
			         array_slice( $array, $index, count( $array ) - 1, true );

			return $array;
		}


		/**
		 * @since 2.1.0
		 *
		 * @param $var
		 * @return array|string
		 */
		function clean_array( $var ) {
			if ( is_array( $var ) ) {
				return array_map( array( $this, 'clean_array' ), $var );
			} else {
				return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
			}
		}

	}
}