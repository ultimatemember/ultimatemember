<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Mail' ) ) {


	/**
	 * Class Mail
	 * @package um\core
	 */
	class Mail {


		/**
		 * @var array
		 */
		var $email_templates = array();


		/**
		 * @var array
		 */
		var $path_by_slug = array();


		/**
		 * Mail constructor.
		 */
		function __construct() {
			//mandrill compatibility
			add_filter( 'mandrill_nl2br', array( &$this, 'mandrill_nl2br' ) );
			add_action( 'plugins_loaded', array( &$this, 'init_paths' ), 99 );
		}


		/**
		 * Init paths for email notifications
		 */
		function init_paths() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_email_templates_path_by_slug
			 * @description Extend email templates path
			 * @input_vars
			 * [{"var":"$paths","type":"array","desc":"Email slug -> Template Path"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_email_templates_path_by_slug', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_email_templates_path_by_slug', 'my_email_templates_path_by_slug', 10, 1 );
			 * function my_email_templates_path_by_slug( $paths ) {
			 *     // your code here
			 *     return $paths;
			 * }
			 * ?>
			 */
			$this->path_by_slug = apply_filters( 'um_email_templates_path_by_slug', $this->path_by_slug );
		}


		/**
		 * Mandrill compatibility
		 *
		 * @param $nl2br
		 * @param string $message
		 * @return bool
		 */
		function mandrill_nl2br( $nl2br, $message = '' ) {
			// text emails
			if ( ! UM()->options()->get( 'email_html' ) ) {
				$nl2br = true;
			}

			return $nl2br;
		}


		/**
		 * Send Email function
		 *
		 * @param string $email
		 * @param null $template
		 * @param array $args
		 */
		function send( $email, $template, $args = array() ) {

			if ( ! is_email( $email ) ) return;
			if ( UM()->options()->get( $template . '_on' ) != 1 ) return;

			$this->attachments = null;
			$this->headers = 'From: '. UM()->options()->get('mail_from') .' <'. UM()->options()->get('mail_from_addr') .'>' . "\r\n";

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_email_send_subject
			 * @description Change email notification subject
			 * @input_vars
			 * [{"var":"$subject","type":"string","desc":"Subject"},
			 * {"var":"$key","type":"string","desc":"Template Key"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_email_send_subject', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_email_send_subject', 'my_email_send_subject', 10, 2 );
			 * function my_email_send_subject( $subject, $key ) {
			 *     // your code here
			 *     return $paths;
			 * }
			 * ?>
			 */
			$subject = apply_filters( 'um_email_send_subject', UM()->options()->get( $template . '_sub' ), $template );
			$this->subject = um_convert_tags( $subject , $args );

			$this->message = $this->prepare_template( $template, $args );

			add_filter( 'wp_mail_content_type', array( &$this, 'set_content_type' ) );
			// Send mail
			wp_mail( $email, $this->subject, $this->message, $this->headers, $this->attachments );
			remove_filter( 'wp_mail_content_type', array( &$this, 'set_content_type' )  );
		}


		/**
		 * @param $slug
		 * @param $args
		 * @return bool|string
		 */
		function get_email_template( $slug, $args = array() ) {
			$located = $this->locate_template( $slug );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_email_template_path
			 * @description Change email template location
			 * @input_vars
			 * [{"var":"$located","type":"string","desc":"Template Location"},
			 * {"var":"$slug","type":"string","desc":"Template Key"},
			 * {"var":"$args","type":"array","desc":"Template settings"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_email_template_path', 'function_name', 10, 3 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_email_template_path', 'my_email_send_subject', 10, 3 );
			 * function my_email_send_subject( $located, $slug, $args ) {
			 *     // your code here
			 *     return $located;
			 * }
			 * ?>
			 */
			$located = apply_filters( 'um_email_template_path', $located, $slug, $args );

			if ( ! file_exists( $located ) ) {
				_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
				return false;
			}

			ob_start();
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_email_template_part
			 * @description Action before email template loading
			 * @input_vars
			 * [{"var":"$slug","type":"string","desc":"Email template slug"},
			 * {"var":"$located","type":"string","desc":"Email template location"},
			 * {"var":"$args","type":"array","desc":"Email template arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_before_email_template_part', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_action( 'um_before_email_template_part', 'my_before_email_template_part', 10, 3 );
			 * function my_before_email_template_part( $slug, $located, $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_before_email_template_part', $slug, $located, $args );

			include( $located );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_email_template_part
			 * @description Action after email template loading
			 * @input_vars
			 * [{"var":"$slug","type":"string","desc":"Email template slug"},
			 * {"var":"$located","type":"string","desc":"Email template location"},
			 * {"var":"$args","type":"array","desc":"Email template arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_email_template_part', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_action( 'um_after_email_template_part', 'my_after_email_template_part', 10, 3 );
			 * function my_after_email_template_part( $slug, $located, $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_after_email_template_part', $slug, $located, $args );

			return ob_get_clean();
		}


		/**
		 * Prepare email template to send
		 *
		 * @param $slug
		 * @param $args
		 * @return mixed|string
		 */
		function prepare_template( $slug, $args = array() ) {
			ob_start();

			if ( UM()->options()->get( 'email_html' ) ) {

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_email_template_html_formatting
				 * @description Change email notification template header
				 * @input_vars
				 * [{"var":"$header","type":"string","desc":"Email notification header. '<html>' by default"},
				 * {"var":"$slug","type":"string","desc":"Template Key"},
				 * {"var":"$args","type":"array","desc":"Template settings"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_email_template_html_formatting', 'function_name', 10, 3 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_email_template_html_formatting', 'my_email_template_html_formatting', 10, 3 );
				 * function my_email_template_html_formatting( $header, $slug, $args ) {
				 *     // your code here
				 *     return $header;
				 * }
				 * ?>
				 */
				echo apply_filters( 'um_email_template_html_formatting', '<html>', $slug, $args );

				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_before_email_template_body
				 * @description Action before email template body display
				 * @input_vars
				 * [{"var":"$slug","type":"string","desc":"Email template slug"},
				 * {"var":"$args","type":"array","desc":"Email template arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_before_email_template_body', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_action( 'um_before_email_template_body', 'my_before_email_template_body', 10, 2 );
				 * function my_before_email_template_body( $slug, $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_before_email_template_body', $slug, $args );

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_email_template_body_attrs
				 * @description Change email notification template body additional attributes
				 * @input_vars
				 * [{"var":"$body_atts","type":"string","desc":"Email notification body attributes"},
				 * {"var":"$slug","type":"string","desc":"Template Key"},
				 * {"var":"$args","type":"array","desc":"Template settings"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_email_template_body_attrs', 'function_name', 10, 3 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_email_template_body_attrs', 'my_email_template_body_attrs', 10, 3 );
				 * function my_email_template_body_attrs( $body_atts, $slug, $args ) {
				 *     // your code here
				 *     return $body_atts;
				 * }
				 * ?>
				 */
				$body_attrs = apply_filters( 'um_email_template_body_attrs', 'style="background: #f2f2f2;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;"', $slug, $args );
				?>


				<body <?php echo $body_attrs ?>>

					<?php echo $this->get_email_template( $slug, $args ); ?>

				</body>
				</html>

			<?php } else {

				echo $this->get_email_template( $slug, $args );

			}

			$message = ob_get_clean();


			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_email_send_message_content
			 * @description Change email notification message content
			 * @input_vars
			 * [{"var":"$message","type":"string","desc":"Message Content"},
			 * {"var":"$template","type":"string","desc":"Template Key"},
			 * {"var":"$args","type":"string","desc":"Notification Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_email_send_message_content', 'function_name', 10, 3 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_email_send_message_content', 'my_email_send_message_content', 10, 3 );
			 * function my_email_send_message_content( $message, $template, $args ) {
			 *     // your code here
			 *     return $message;
			 * }
			 * ?>
			 */
			$message = apply_filters( 'um_email_send_message_content', $message, $slug, $args );

			// Convert tags in email template
			return um_convert_tags( $message, $args );
		}


		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * @access public
		 * @param string $template_name
		 * @return string
		 */
		function locate_template( $template_name ) {
			// check if there is template at theme folder
			$template = locate_template( array(
				trailingslashit( 'ultimate-member/email' ) . $template_name . '.php'
			) );

			//if there isn't template at theme folder get template file from plugin dir
			if ( ! $template ) {
				$path = ! empty( $this->path_by_slug[ $template_name ] ) ? $this->path_by_slug[ $template_name ] : um_path . 'templates/email';
				$template = trailingslashit( $path ) . $template_name . '.php';
			}

			// Return what we found.
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_locate_email_template
			 * @description Change email notification template path
			 * @input_vars
			 * [{"var":"$template","type":"string","desc":"Template Path"},
			 * {"var":"$template_name","type":"string","desc":"Template Name"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_locate_email_template', 'function_name', 10, 2 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_locate_email_template', 'my_locate_email_template', 10, 2 );
			 * function my_email_template_body_attrs( $template, $template_name ) {
			 *     // your code here
			 *     return $template;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_locate_email_template', $template, $template_name );
		}


		/**
		 * @param $template_name
		 *
		 * @return mixed|void
		 */
		function get_template_filename( $template_name ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_change_email_template_file
			 * @description Change email notification template path
			 * @input_vars
			 * [{"var":"$template_name","type":"string","desc":"Template Name"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage
			 * <?php add_filter( 'um_change_email_template_file', 'function_name', 10, 1 ); ?>
			 * @example
			 * <?php
			 * add_filter( 'um_change_email_template_file', 'my_change_email_template_file', 10, 1 );
			 * function my_change_email_template_file( $template, $template_name ) {
			 *     // your code here
			 *     return $template;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_change_email_template_file', $template_name );
		}


		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * @access public
		 * @param string $template_name
		 * @param bool $html
		 * @return string
		 */
		function template_in_theme( $template_name, $html = false ) {
			$template_name_file = $this->get_template_filename( $template_name );
			$ext = ! $html ? '.php' : '.html';

			// check if there is template at theme folder
			$template = locate_template( array(
				trailingslashit( 'ultimate-member/email' ) . $template_name_file . $ext
			) );

			// Return what we found.
			return ! $template ? false : true;
		}


		/**
		 * Method returns expected path for template
		 *
		 * @access public
		 * @param string $location
		 * @param string $template_name
		 * @param bool $html
		 * @return string
		 */
		function get_template_file( $location, $template_name, $html = false ) {
			$template_path = '';
			$template_name_file = $this->get_template_filename( $template_name );

			$ext = ! $html ? '.php' : '.html';

			switch( $location ) {
				case 'theme':
					$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' ). $template_name_file . $ext;
					break;
				case 'plugin':
					$path = ! empty( $this->path_by_slug[ $template_name ] ) ? $this->path_by_slug[ $template_name ] : um_path . 'templates/email';
					$template_path = trailingslashit( $path ) . $template_name . $ext;
					break;
			}

			return $template_path;
		}


		/**
		 * Set email content type
		 *
		 *
		 * @param $content_type
		 * @return string
		 */
		function set_content_type( $content_type ) {
			if ( UM()->options()->get( 'email_html' ) ) {
				return 'text/html';
			} else {
				return 'text/plain';
			}
		}


		/**
		 * Ajax copy template to the theme
		 *
		 * @param bool $template
		 * @return bool
		 */
		function copy_email_template( $template = false ) {

			$in_theme = $this->template_in_theme( $template );
			if ( $in_theme ) {
				return false;
			}

			$plugin_template_path = $this->get_template_file( 'plugin', $template );
			$theme_template_path = $this->get_template_file( 'theme', $template );

			$temp_path = str_replace( trailingslashit( get_stylesheet_directory() ), '', $theme_template_path );
			$temp_path = str_replace( '/', DIRECTORY_SEPARATOR, $temp_path );
			$folders = explode( DIRECTORY_SEPARATOR, $temp_path );
			$folders = array_splice( $folders, 0, count( $folders ) - 1 );
			$cur_folder = '';
			$theme_dir = trailingslashit( get_stylesheet_directory() );

			foreach ( $folders as $folder ) {
				$prev_dir = $cur_folder;
				$cur_folder .= $folder . DIRECTORY_SEPARATOR;
				if ( ! is_dir( $theme_dir . $cur_folder ) && wp_is_writable( $theme_dir . $prev_dir ) ) {
					mkdir( $theme_dir . $cur_folder, 0777 );
				}
			}

			if ( file_exists( $plugin_template_path ) && copy( $plugin_template_path, $theme_template_path ) ) {
				return true;
			} else {
				return false;
			}
		}


		/**
		 * Delete Email Notification Template
		 */
		function delete_email_template() {
			$template = $_POST['email_key'];

			$in_theme = $this->template_in_theme( $template );
			if ( ! $in_theme ) {
				wp_send_json_error( new \WP_Error( 'template_in_theme', __( 'Template does not exists in theme', 'ultimate-member' ) ) );
			}

			$theme_template_path = $this->get_template_file( 'theme', $template );

			if ( unlink( $theme_template_path ) ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( new \WP_Error( 'template_not_exists', __( 'Can not remove template from theme', 'ultimate-member' ) ) );
			}
		}
	}
}