<?php if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'UM_Functions' ) ) {


	/**
	 * Class UM_Functions
	 */
	class UM_Functions {

		/**
		 * Path to the templates root directory
		 * @var string
		 */
		private $basedir;


		/**
		 * Current locale
		 * @var string
		 */
		private $locale;


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

			// Set properties
			$this->basedir = get_stylesheet_directory() . '/ultimate-member';
			$this->locale = determine_locale();

			// Maybe change properties in theme
			add_action( 'after_setup_theme', [$this, 'filter_properties'] );
		}


		/**
		 * Maybe change properties in theme
		 */
		public function filter_properties() {

			/**
			 * UM hook
			 *
			 * @type        filter
			 * @title       um_template_basedir
			 * @description Change [basedir] for templates.
			 *   The path structure: [basedir]/[blog_id]/[locale]/[folder]/[template_name].php
			 * @input_vars
			 * [
			 *  {"var":"$basedir","type":"string","desc":"Default directory path"}
			 * ]
			 * @change_log
			 * ["Since: 2.1.21"]
			 * @example
			 * <?php
			 * add_filter( 'um_template_basedir', 'my_um_template_basedir', 10, 1 );
			 * function my_um_template_basedir( $basedir ) {
			 *     // your code here
			 *     return $basedir;
			 * }
			 * ?>
			 */
			$this->basedir = apply_filters( 'um_template_basedir', $this->basedir );


			/**
			 * UM hook
			 *
			 * @type        filter
			 * @title       um_template_locale
			 * @description Change [locale] for templates.
			 *   The path structure: [basedir]/[blog_id]/[locale]/[folder]/[template_name].php
			 * @input_vars
			 * [
			 *  {"var":"$locale","type":"string","desc":"Current locale"}
			 * ]
			 * @change_log
			 * ["Since: 2.1.21"]
			 * @example
			 * <?php
			 * add_filter( 'um_template_locale', 'my_um_template_locale', 10, 1 );
			 * function my_um_template_locale( $locale ) {
			 *     // your code here
			 *     return $locale;
			 * }
			 * ?>
			 */
			$this->locale = apply_filters( 'um_template_locale', $this->locale );
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
		 * Expected path for template
		 *
		 * @access public
		 * @since  2.1.21
		 *
		 * @param  string $template_name The slug of the template
		 * @param  string $path          Sub-folder to store the template or path to template
		 * @param  string $location			 Place to store template: 'theme', 'plugin', 'uploads', 'basedir'
		 * @param  string $locale			   Locale
		 *
		 * @return string
		 */
		public function get_template_filepath( $template_name, $path = '', $location = '', $locale = '' ) {

			$file = trim( str_replace( '.php', '', $template_name ) ) . '.php';
			$folder = $path ? trailingslashit( $path ) : '';
			$blog_id = is_multisite() ? trailingslashit( get_current_blog_id() ) : '';

			if ( $locale === true ) {
				$locale = $this->locale;
				if ( isset( UM()->user()->id ) && UM()->user()->id !== get_current_user_id() ) {
					$locale = get_user_locale( UM()->user()->id );
				}
			}
			if ( empty( $locale ) || !in_array( $locale, get_available_languages() ) ) {
				$locale = '';
			} else {
				$locale = trailingslashit( $locale );
			}

			switch ( $location ) {
				case 'theme':
					$dir = trailingslashit( get_stylesheet_directory() ) . 'ultimate-member/' . $blog_id . $locale . $folder;
					break;
				case 'plugin':
					if( 'email' === $path ){
						$dir = empty( UM()->mail()->path_by_slug[ $template_name ] ) ? um_path . 'templates/email/' : UM()->mail()->path_by_slug[ $template_name ];
					}else{
						if( empty( $folder ) ){
							$folder = 'ultimate-member/templates/';
						} elseif ( strpos( $folder, 'templates' ) === false ) {
							$folder .= 'templates/';
						}
						$dir = trailingslashit( WP_PLUGIN_DIR ) . $folder;
					}
					break;
				case 'uploads':
					$dir = trailingslashit( UM()->uploader()->get_upload_base_dir() ) . 'templates/' . $blog_id . $locale . $folder;
					break;
				case 'basedir':
				default :
					$dir = trailingslashit( $this->basedir ) . $blog_id . $locale . $folder;
					break;
			}

			$template_path = wp_normalize_path( trailingslashit( $dir ) . $file );

			/**
			 * UM hook
			 *
			 * @type        filter
			 * @title       um_template_filepath
			 * @description Change template path
			 * @input_vars
			 * [
			 *  {"var":"$template_path","type":"string","desc":"Template Path"},
			 *  {"var":"$template_name","type":"string","desc":"Template Name"},
			 *  {"var":"$path","type":"string","desc":"Folder or path"},
			 *  {"var":"$location","type":"string","desc":"Root directory: theme, plugin, uploads, basedir"}
			 * ]
			 * @change_log
			 * ["Since: 2.1.21"]
			 * @example
			 * <?php
			 * add_filter( 'um_template_filepath', 'my_um_template_filepath', 10, 4 );
			 * function my_um_template_filepath( $template_path, $template_name, $path, $location ) {
			 *     // your code here
			 *     return $template_path;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_template_filepath', $template_path, $template_name, $path, $location );
		}


		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * @access  public
		 * @version 2.1.21
		 *
		 * @param   string $template_name The slug of the template
		 * @param   string $path					Sub-folder to store the template or path to template
		 * @return  string
		 */
		public function locate_template( $template_name, $path = '' ) {

			// Search for the localized template in the base directory
			if ( file_exists( $basedir_path = $this->get_template_filepath( $template_name, $path, 'basedir', true ) ) ) {
				$template = $basedir_path;
			} else
			// Search for the template in the base directory
			if ( file_exists( $basedir_path = $this->get_template_filepath( $template_name, $path, 'basedir', false ) ) ) {
				$template = $basedir_path;
			} else
			// Search for the localized template in the theme directory
			if ( file_exists( $theme_path = $this->get_template_filepath( $template_name, $path, 'theme', true ) ) ) {
				$template = $theme_path;
			} else
			// Search for the template in the theme directory
			if ( file_exists( $theme_path = $this->get_template_filepath( $template_name, $path, 'theme', false ) ) ) {
				$template = $theme_path;
			} else
			// Search for the template in the plugin directory
			if ( file_exists( $plugin_path = $this->get_template_filepath( $template_name, $path, 'plugin', true ) ) ) {
				$template = $plugin_path;
			}
			// No template found
			else {
				$template = '';
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