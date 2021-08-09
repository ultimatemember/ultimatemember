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
		 * Define constant if not already set.
		 *
		 * @since 3.0
		 * @access protected
		 *
		 * @param string      $name  Constant name.
		 * @param string|bool $value Constant value.
		 */
		protected function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}


		/**
		 * Get the template path inside theme or custom path
		 *
		 * @since 3.0
		 * @access public
		 *
		 * @param string $module Module slug.
		 *
		 * @return string
		 */
		public function template_path( $module = '' ) {
			$path = 'ultimate-member/';

			if ( ! empty( $module ) ) {
				$path .= "$module/";
			}

			return apply_filters( 'um_template_path', $path, $module );
		}


		/**
		 * Get the default template path inside wp-content/plugins/
		 *
		 * @since 3.0
		 * @access public
		 *
		 * @param string $module Module slug.
		 *
		 * @return string
		 */
		public function default_templates_path( $module = '' ) {
			$path = untrailingslashit( um_path ) . '/templates/';
			if ( ! empty( $module ) ) {
				$module_data = UM()->modules()->get_data( $module );
				$path = untrailingslashit( $module_data['path'] ) . '/templates/';
			}

			return apply_filters( 'um_default_template_path', $path, $module );
		}


		/**
		 * Easy merge arrays based on parent array key. Insert before selected key
		 *
		 * @since 2.0.44
		 *
		 * @param array $array
		 * @param string $key
		 * @param array $insert_array
		 *
		 * @return array
		 */
		public function array_insert_before( $array, $key, $insert_array ) {
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
		 * Easy merge arrays based on parent array key. Insert after selected key
		 *
		 * @since 3.0
		 *
		 * @param array $array
		 * @param string $key
		 * @param array $insert_array
		 *
		 * @return array
		 */
		public function array_insert_after( $array, $key, $insert_array ) {
			$index = array_search( $key, array_keys( $array ) );
			if ( $index === false ) {
				return $array;
			}

			$array = array_slice( $array, 0, $index + 1, true ) +
			         $insert_array +
			         array_slice( $array, $index + 1, count( $array ) - 1, true );

			return $array;
		}


		/**
		 * Undash string. Easy operate
		 *
		 * @since 3.0
		 * @param string $slug
		 *
		 * @return string
		 */
		function undash( $slug ) {
			$slug = str_replace( '-', '_', $slug );
			return $slug;
		}















		/**
		 * Check if AJAX now
		 * @since 2.0
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
			$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : '';
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


		/**
		 * Output templates
		 *
		 * @deprecated 3.0
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
			_deprecated_function( 'UM()->get_template()', '3.0', 'um_get_template_html() or um_get_template()' );

			if ( $echo ) {
				um_get_template_html( $template_name, $t_args, '', '', $basename );
			} else {
				um_get_template( $template_name, $t_args, '', '', $basename );
			}
		}


		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * @deprecated 3.0
		 *
		 * @access public
		 * @param string $template_name
		 * @param string $path (default: '')
		 * @return string
		 */
		function locate_template( $template_name, $path = '' ) {
			_deprecated_function( 'UM()->locate_template()', '3.0', 'um_locate_template' );

			return um_locate_template( $template_name, '', '', $path );
		}

	}
}
