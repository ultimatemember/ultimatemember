<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		public $store_url = 'https://ultimatemember.com/';


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
			$path = untrailingslashit( UM_PATH ) . '/templates/';
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
			$index = array_search( $key, array_keys( $array ), true );
			if ( false === $index ) {
				return $array;
			}

			$array = array_slice( $array, 0, $index, true ) +
					$insert_array +
					array_slice( $array, $index, count( $array ) - 1, true );

			return $array;
		}


		/**
		 * Easy merge arrays based on a parent array key. Insert after a selected key.
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
			$index = array_search( $key, array_keys( $array ), true );
			if ( false === $index ) {
				return $array;
			}

			$array_before = array_slice( $array, 0, $index + 1, true );
			$array_after  = array_slice( $array, $index + 1, count( $array ) - 1, true );
			if ( is_numeric( $key ) ) {
				$array = array_merge( $array_before, $insert_array, $array_after );
			} else {
				$array = $array_before + $insert_array + $array_after;
			}

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
		 * @param string $context
		 *
		 * @return array
		 */
		public function get_allowed_html( $context = '' ) {
			switch ( $context ) {
				case 'wp-admin':
					$allowed_html = array(
						'img'      => array(
							'alt'      => true,
							'align'    => true,
							'border'   => true,
							'height'   => true,
							'hspace'   => true,
							'loading'  => true,
							'longdesc' => true,
							'vspace'   => true,
							'src'      => true,
							'srcset'   => true,
							'usemap'   => true,
							'width'    => true,
						),
						'ul'       => array(),
						'li'       => array(),
						'h1'       => array(
							'align' => true,
						),
						'h2'       => array(
							'align' => true,
						),
						'h3'       => array(
							'align' => true,
						),
						'p'        => array(
							'align' => true,
							'dir'   => true,
							'lang'  => true,
						),
						'form'     => array(
							'action'         => true,
							'accept'         => true,
							'accept-charset' => true,
							'enctype'        => true,
							'method'         => true,
							'name'           => true,
							'target'         => true,
						),
						'label'    => array(
							'for' => true,
						),
						'select'   => array(
							'name'         => true,
							'multiple'     => true,
							'disabled'     => true,
							'readonly'     => true,
							'required'     => true,
							'autocomplete' => true,
						),
						'option'   => array(
							'value'    => true,
							'selected' => true,
							'disabled' => true,
						),
						'input'    => array(
							'type'         => true,
							'name'         => true,
							'value'        => true,
							'placeholder'  => true,
							'readonly'     => true,
							'disabled'     => true,
							'checked'      => true,
							'selected'     => true,
							'required'     => true,
							'autocomplete' => true,
						),
						'textarea' => array(
							'cols'         => true,
							'rows'         => true,
							'disabled'     => true,
							'name'         => true,
							'readonly'     => true,
							'required'     => true,
							'autocomplete' => true,
						),
						'table'    => array(
							'align'       => true,
							'bgcolor'     => true,
							'border'      => true,
							'cellpadding' => true,
							'cellspacing' => true,
							'dir'         => true,
							'rules'       => true,
							'summary'     => true,
							'width'       => true,
						),
						'tbody'    => array(
							'align'   => true,
							'char'    => true,
							'charoff' => true,
							'valign'  => true,
						),
						'td'       => array(
							'abbr'    => true,
							'align'   => true,
							'axis'    => true,
							'bgcolor' => true,
							'char'    => true,
							'charoff' => true,
							'colspan' => true,
							'dir'     => true,
							'headers' => true,
							'height'  => true,
							'nowrap'  => true,
							'rowspan' => true,
							'scope'   => true,
							'valign'  => true,
							'width'   => true,
						),
						'tfoot'    => array(
							'align'   => true,
							'char'    => true,
							'charoff' => true,
							'valign'  => true,
						),
						'th'       => array(
							'abbr'    => true,
							'align'   => true,
							'axis'    => true,
							'bgcolor' => true,
							'char'    => true,
							'charoff' => true,
							'colspan' => true,
							'headers' => true,
							'height'  => true,
							'nowrap'  => true,
							'rowspan' => true,
							'scope'   => true,
							'valign'  => true,
							'width'   => true,
						),
						'thead'    => array(
							'align'   => true,
							'char'    => true,
							'charoff' => true,
							'valign'  => true,
						),
						'tr'       => array(
							'align'   => true,
							'bgcolor' => true,
							'char'    => true,
							'charoff' => true,
							'valign'  => true,
						),
					);
					break;
				case 'templates':
					$allowed_html = array(
						'style'    => array(),
						'link'     => array(
							'rel'   => true,
							'href'  => true,
							'media' => true,
						),
						'form'     => array(
							'action'         => true,
							'accept'         => true,
							'accept-charset' => true,
							'enctype'        => true,
							'method'         => true,
							'name'           => true,
							'target'         => true,
						),
						'label'    => array(
							'for' => true,
						),
						'select'   => array(
							'name'         => true,
							'multiple'     => true,
							'disabled'     => true,
							'readonly'     => true,
							'required'     => true,
							'autocomplete' => true,
						),
						'option'   => array(
							'value'    => true,
							'selected' => true,
							'disabled' => true,
						),
						'input'    => array(
							'type'         => true,
							'name'         => true,
							'value'        => true,
							'placeholder'  => true,
							'readonly'     => true,
							'disabled'     => true,
							'checked'      => true,
							'selected'     => true,
							'required'     => true,
							'autocomplete' => true,
							'size'         => true,
							'step'         => true,
							'min'          => true,
							'max'          => true,
							'minlength'    => true,
							'maxlength'    => true,
							'pattern'      => true,
						),
						'textarea' => array(
							'cols'         => true,
							'rows'         => true,
							'disabled'     => true,
							'name'         => true,
							'readonly'     => true,
							'required'     => true,
							'autocomplete' => true,
						),
						'img'      => array(
							'alt'      => true,
							'align'    => true,
							'border'   => true,
							'height'   => true,
							'hspace'   => true,
							'loading'  => true,
							'longdesc' => true,
							'vspace'   => true,
							'src'      => true,
							'srcset'   => true,
							'usemap'   => true,
							'width'    => true,
						),
						'h1'       => array(
							'align' => true,
						),
						'h2'       => array(
							'align' => true,
						),
						'h3'       => array(
							'align' => true,
						),
						'p'        => array(
							'align' => true,
							'dir'   => true,
							'lang'  => true,
						),
						'ul'       => array(),
						'li'       => array(),
						'time'     => array(
							'datetime' => true,
						),
					);
					break;
				case 'admin_notice':
					$allowed_html = array(
						'p'      => array(
							'align' => true,
							'dir'   => true,
							'lang'  => true,
						),
						'label'  => array(
							'for' => true,
						),
						'strong' => array(
							'style' => true,
						),
					);
					break;
				default:
					$allowed_html = array();
					break;
			}

			$global_allowed = array(
				'a'      => array(
					'href'     => array(),
					'rel'      => true,
					'rev'      => true,
					'name'     => true,
					'target'   => true,
					'download' => array(
						'valueless' => 'y',
					),
				),
				'em'     => array(),
				'i'      => array(),
				'q'      => array(
					'cite' => true,
				),
				's'      => array(),
				'strike' => array(),
				'strong' => array(),
				'br'     => array(),
				'div'    => array(
					'align' => true,
					'dir'   => true,
					'lang'  => true,
				),
				'span'   => array(
					'dir'   => true,
					'align' => true,
					'lang'  => true,
				),
				'code'   => array(),
				'hr'     => array(
					'style' => true,
				),
			);

			$allowed_html = array_merge( $global_allowed, $allowed_html );
			$allowed_html = array_map( '_wp_add_global_attributes', $allowed_html );

			/**
			 * Filters the allowed HTML tags and their attributes in the late escaping before echo.
			 *
			 * Note: Please use the `wp_kses()` allowed tags structure.
			 *
			 * @since 3.0
			 * @hook um_late_escaping_allowed_tags
			 *
			 * @param {array}  $allowed_html Allowed HTML tags with attributes.
			 * @param {string} $context      Function context 'wp-admin' for Admin Dashboard echo, 'templates' for the frontend.
			 *
			 * @return {array} Allowed HTML tags with attributes.
			 */
			$allowed_html = apply_filters( 'um_late_escaping_allowed_tags', $allowed_html, $context );

			return $allowed_html;
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
		 * @deprecated 3.0
		 *
		 * @param bool $action
		 */
		function check_ajax_nonce( $action = false ) {
			_deprecated_function( __METHOD__, '3.0', 'UM()->ajax()->check_nonce()' );
			$action = empty( $action ) ? 'um-frontend-nonce' : $action;
			UM()->ajax()->check_nonce( $action );
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
		 * Is Ultimate Member Pro active?
		 *
		 * @return bool
		 */
		public function is_pro_plugin_active() {
			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}

			return in_array( 'ultimate-member-pro/ultimate-member-pro.php', $active_plugins, true );
		}

		/**
		 * Disable page caching and set or clear cookie
		 *
		 * @param string $name
		 * @param string $value
		 * @param int $expire
		 * @param string $path
		 *
		 * @since 3.0.0
		 */
		public function setcookie( $name, $value = '', $expire = 0, $path = '' ) {
			if ( empty( $value ) ) {
				$expire = time() - YEAR_IN_SECONDS;
			}
			if ( empty( $path ) ) {
				list( $path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
			}

			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				@ob_end_clean();
			}

			nocache_headers();
			setcookie( $name, $value, $expire, $path, COOKIE_DOMAIN, is_ssl(), true );
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

			<span class="um-helptip dashicons dashicons-editor-help" title="<?php echo $tip ?>"></span>

			<?php if ( $echo ) {
				ob_get_flush();
				return '';
			} else {
				return ob_get_clean();
			}

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
				return ! is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
			}
		}


		/**
		 * Replace the first match in the string, alternative for the `str_replace()` function
		 *
		 * @param string $search
		 * @param string $replace
		 * @param string $subject
		 *
		 * @return string
		 */
		function str_replace_first( $search, $replace, $subject ) {
			$search = '/' . preg_quote( $search, '/' ) . '/';
			return preg_replace( $search, $replace, $subject, 1 );
		}


		/**
		 * @deprecated 3.0
		 *
		 * @return array
		 */
		function cpt_list() {
			_deprecated_function( __METHOD__, '3.0', 'UM()->common()->cpt()->get_list()' );
			return UM()->common()->cpt()->get_list();
		}


		/**
		 * @deprecated 3.0
		 *
		 * @param null|string $post_type
		 * @return array
		 */
		function cpt_taxonomies_list( $post_type = null ) {
			_deprecated_function( __METHOD__, '3.0', 'UM()->common()->cpt()->get_taxonomies_list( $post_type )' );
			return UM()->common()->cpt()->get_taxonomies_list( $post_type );
		}


		/**
		 * @deprecated 3.0
		 * @return array
		 */
		function excluded_taxonomies() {
			_deprecated_function( __METHOD__, '3.0', 'UM()->common()->access()->excluded_taxonomies()' );
			return UM()->common()->access()->excluded_taxonomies();
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
			_deprecated_function( __METHOD__, '3.0', 'um_get_template_html() or um_get_template()' );

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
			_deprecated_function( __METHOD__, '3.0', 'um_locate_template' );

			return um_locate_template( $template_name, '', '', $path );
		}
	}
}
