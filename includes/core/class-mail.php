<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Mail' ) ) {

	/**
	 * Class Mail
	 * @package um\core
	 */
	class Mail {

		/**
		 * @var array
		 */
		public $email_templates = array();

		/**
		 * @var array
		 */
		public $path_by_slug = array();

		/**
		 * @var array
		 */
		public $attachments = array();

		/**
		 * @var string
		 */
		public $headers = '';

		/**
		 * @var string
		 */
		public $subject = '';

		/**
		 * @var string
		 */
		public $message = '';

		/**
		 * Mail constructor.
		 */
		public function __construct() {
			add_action( 'init', array( &$this, 'init_paths' ), 0 ); // init class variables on zero-priority.
			add_filter( 'mandrill_nl2br', array( &$this, 'mandrill_nl2br' ) );
		}

		/**
		 * Init paths for email notifications.
		 */
		public function init_paths() {
			/**
			 * Filters extend email templates path.
			 *
			 * @param {array} $paths Email templates paths.
			 *
			 * @return {array} Email templates paths.
			 *
			 * @since 2.0
			 * @hook um_email_templates_path_by_slug
			 *
			 * @example <caption>Extends email templates path.</caption>
			 * function my_email_templates_path_by_slug( $paths ) {
			 *     // your code here
			 *     $paths['template_name'] = 'template_path';
			 *     return $paths;
			 * }
			 * add_filter( 'um_email_templates_path_by_slug', 'my_email_templates_path_by_slug' );
			 */
			$this->path_by_slug = apply_filters( 'um_email_templates_path_by_slug', $this->path_by_slug );
		}

		/**
		 * Mandrill compatibility
		 *
		 * @param $nl2br
		 * @return bool
		 */
		public function mandrill_nl2br( $nl2br ) {
			if ( ! UM()->options()->get( 'email_html' ) ) {
				$nl2br = true; // nl2br for text emails
			}

			return $nl2br;
		}

		/**
		 * Check blog ID on multisite, return '' if single site.
		 *
		 * @return string
		 */
		public function get_blog_id() {
			$blog_id = '';
			if ( is_multisite() ) {
				$blog_id = '/' . get_current_blog_id();
			}

			return $blog_id;
		}

		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * @access public
		 * @param string $template_name
		 * @return string
		 */
		public function locate_template( $template_name ) {
			// check if there is template at theme folder
			$blog_id = $this->get_blog_id();

			//get template file from current blog ID folder
			$template = locate_template(
				array(
					trailingslashit( 'ultimate-member/email' . $blog_id ) . $template_name . '.php',
				)
			);

			// If there isn't template at theme folder for current blog ID get template file from theme folder
			if ( is_multisite() && ! $template ) {
				$template = locate_template(
					array(
						trailingslashit( 'ultimate-member/email' ) . $template_name . '.php',
					)
				);
			}

			// If there isn't template at theme folder, get template file from plugin dir.
			if ( ! $template ) {
				$path     = ! empty( $this->path_by_slug[ $template_name ] ) ? $this->path_by_slug[ $template_name ] : UM_PATH . 'templates/email';
				$template = trailingslashit( $path ) . $template_name . '.php';
			}
			/**
			 * Filters email notification template path.
			 *
			 * @param {string} $template      Email notification template path.
			 * @param {string} $template_name Email notification template name.
			 *
			 * @return {string} Email notification template path.
			 *
			 * @since 2.0
			 * @hook um_locate_email_template
			 *
			 * @example <caption>Change email notification template path.</caption>
			 * function my_locate_email_template( $template, $template_name ) {
			 *     // your code here
			 *     return $template;
			 * }
			 * add_filter( 'um_locate_email_template', 'my_locate_email_template', 10, 2 );
			 */
			return apply_filters( 'um_locate_email_template', $template, $template_name );
		}

		/**
		 * @param $slug
		 * @param $args
		 * @return bool|string
		 */
		public function get_email_template( $slug, $args = array() ) {
			$located = $this->locate_template( $slug );

			/**
			 * Filters email template location.
			 *
			 * @param {string} $located Email template location.
			 * @param {string} $slug    Email template slug.
			 * @param {array}  $args    Email template settings.
			 *
			 * @return {string} Email template location.
			 *
			 * @since 1.3.x
			 * @hook um_email_template_path
			 *
			 * @example <caption>Change email template location.</caption>
			 * function my_email_template_path( $located, $slug, $args ) {
			 *     // your code here
			 *     return $located;
			 * }
			 * add_filter( 'um_email_template_path', 'my_email_template_path', 10, 3 );
			 */
			$located = apply_filters( 'um_email_template_path', $located, $slug, $args );

			if ( ! file_exists( $located ) ) {
				_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
				return false;
			}

			ob_start();
			/**
			 * Fires before email template loading.
			 *
			 * @param {string} $slug    Email template slug.
			 * @param {string} $located Email template location.
			 * @param {array}  $args    Email template arguments.
			 *
			 * @since 2.0
			 * @hook um_before_email_template_part
			 *
			 * @example <caption>Action before email template loading.</caption>
			 * function my_before_email_template_part( $slug, $located, $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_before_email_template_part', 'my_before_email_template_part', 10, 3 );
			 */
			do_action( 'um_before_email_template_part', $slug, $located, $args );

			include $located;

			/**
			 * Fires after email template loading.
			 *
			 * @param {string} $slug    Email template slug.
			 * @param {string} $located Email template location.
			 * @param {array}  $args    Email template arguments.
			 *
			 * @since 2.0
			 * @hook um_after_email_template_part
			 *
			 * @example <caption>Action after email template loading.</caption>
			 * function my_after_email_template_part( $slug, $located, $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_after_email_template_part', 'my_after_email_template_part', 10, 3 );
			 */
			do_action( 'um_after_email_template_part', $slug, $located, $args );

			return ob_get_clean();
		}

		/**
		 * Prepare email template to send.
		 *
		 * @param string $slug
		 * @param array  $args
		 * @return mixed|string
		 */
		public function prepare_template( $slug, $args = array() ) {
			ob_start();

			if ( UM()->options()->get( 'email_html' ) ) {

				/**
				 * Filters email notification template header.
				 *
				 * @param {string} $header Email notification header. It equals `<html>` by default.
				 * @param {string} $slug   Email template slug.
				 * @param {array}  $args   Email template settings.
				 *
				 * @return {string} Email notification header.
				 *
				 * @since 2.0
				 * @hook um_email_template_html_formatting
				 *
				 * @example <caption>Change email notification template header.</caption>
				 * function my_email_template_html_formatting( $header, $slug, $args ) {
				 *     // your code here
				 *     return $header;
				 * }
				 * add_filter( 'um_email_template_html_formatting', 'my_email_template_html_formatting', 10, 3 );
				 */
				echo apply_filters( 'um_email_template_html_formatting', '<html>', $slug, $args );

				/**
				 * Fires before email template body display.
				 *
				 * @param {string} $slug Email template slug.
				 * @param {array}  $args Email template settings.
				 *
				 * @since 2.0
				 * @hook um_before_email_template_body
				 *
				 * @example <caption>Action before email template body display.</caption>
				 * function my_before_email_template_body( $slug, $args ) {
				 *     // your code here
				 * }
				 * add_action( 'um_before_email_template_body', 'my_before_email_template_body', 10, 2 );
				 */
				do_action( 'um_before_email_template_body', $slug, $args );

				/**
				 * Filters email notification template body additional attributes.
				 *
				 * @param {string} $body_attrs Email notification body attributes.
				 * @param {string} $slug       Email template slug.
				 * @param {array}  $args       Email template settings.
				 *
				 * @return {string} Email notification body attributes.
				 *
				 * @since 2.0
				 * @hook um_email_template_body_attrs
				 *
				 * @example <caption>Change email notification template body additional attributes.</caption>
				 * function my_email_template_body_attrs( $body_attrs, $slug, $args ) {
				 *     // your code here
				 *     return $body_attrs;
				 * }
				 * add_filter( 'um_email_template_body_attrs', 'my_email_template_body_attrs', 10, 3 );
				 */
				$body_attrs = apply_filters( 'um_email_template_body_attrs', 'style="background: #fff;-webkit-font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;"', $slug, $args );
				?>

				<body <?php echo $body_attrs; ?>>

				<?php echo $this->get_email_template( $slug, $args ); ?>

				</body>
				</html>

				<?php
			} else {

				// Strip tags in plain text email
				// Important don't use HTML in plain text emails!
				$raw_email_template   = $this->get_email_template( $slug, $args );
				$plain_email_template = wp_strip_all_tags( $raw_email_template );
				if ( $plain_email_template !== $raw_email_template ) {
					$plain_email_template = preg_replace( array( '/&nbsp;/mi', '/^\s+/mi' ), array( ' ', '' ), $plain_email_template );
				}

				echo $plain_email_template;
			}

			$message = ob_get_clean();
			/**
			 * Filters email notification message content.
			 *
			 * @param {string} $message Message content.
			 * @param {string} $slug    Template slug.
			 * @param {array}  $args    Notification arguments.
			 *
			 * @return {string} Message Content.
			 *
			 * @since 2.0
			 * @hook um_email_send_message_content
			 *
			 * @example <caption>Change email notification message content.</caption>
			 * function my_email_send_message_content( $message, $slug, $args ) {
			 *     // your code here
			 *     return $message;
			 * }
			 * add_filter( 'um_email_send_message_content', 'my_email_send_message_content', 10, 3 );
			 */
			$message = apply_filters( 'um_email_send_message_content', $message, $slug, $args );

//			add_filter( 'um_template_tags_patterns_hook', array( &$this, 'add_placeholder' ) );
//			add_filter( 'um_template_tags_replaces_hook', array( &$this, 'add_replace_placeholder' ) );

			// Convert tags in email template.
			return um_convert_tags( $message, $args );
		}

		/**
		 * Send Email function
		 *
		 * @param string $email
		 * @param null $template
		 * @param array $args
		 */
		public function send( $email, $template, $args = array() ) {
			if ( ! is_email( $email ) ) {
				return;
			}

			if ( empty( UM()->options()->get( $template . '_on' ) ) ) {
				return;
			}
			/**
			 * Filters disabling email notifications programmatically.
			 *
			 * @param {bool}   $disabled Does an email is disabled programmatically. By default it is false.
			 * @param {string} $email    Email address for sending.
			 * @param {string} $template Email template key.
			 * @param {array}  $args     Arguments for sending email.
			 *
			 * @return {bool} `true` if email is disabled programmatically.
			 *
			 * @since 2.6.1
			 * @hook um_disable_email_notification_sending
			 *
			 * @example <caption>Disabling email notifications programmatically.</caption>
			 * function my_disable_email_notification_sending( $disabled, $email, $template, $args ) {
			 *     // your code here
			 *     return $disabled;
			 * }
			 * add_filter( 'um_disable_email_notification_sending', 'my_disable_email_notification_sending', 10, 4 );
			 */
			$hook_disabled = apply_filters( 'um_disable_email_notification_sending', false, $email, $template, $args );
			if ( false !== $hook_disabled ) {
				return;
			}

			/**
			 * Fires before email notification sending.
			 *
			 * @param {string} $email    Email template slug.
			 * @param {string} $template Email template settings.
			 * @param {array}  $args     Arguments for sending email.
			 *
			 * @since 2.0
			 * @hook um_before_email_notification_sending
			 *
			 * @example <caption>Action before email notification sending.</caption>
			 * function my_before_email_notification_sending( $email, $template, $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_before_email_notification_sending', 'my_before_email_notification_sending', 10, 3 );
			 */
			do_action( 'um_before_email_notification_sending', $email, $template, $args );

			$this->attachments = array();
			$mail_from         = UM()->options()->get( 'mail_from' ) ? UM()->options()->get( 'mail_from' ) : get_bloginfo( 'name' );
			$mail_from_addr    = UM()->options()->get( 'mail_from_addr' ) ? UM()->options()->get( 'mail_from_addr' ) : get_bloginfo( 'admin_email' );
			$this->headers     = 'From: ' . stripslashes( $mail_from ) . ' <' . $mail_from_addr . '>' . "\r\n";

			add_filter( 'um_template_tags_patterns_hook', array( $this, 'add_placeholder' ) );
			add_filter( 'um_template_tags_replaces_hook', array( $this, 'add_replace_placeholder' ) );

			/**
			 * Filters email notification subject.
			 *
			 * @param {string} $subject Email subject.
			 * @param {string} $key     Email template key.
			 *
			 * @return {string} Email subject.
			 *
			 * @since 2.6.1
			 * @hook um_email_send_subject
			 *
			 * @example <caption>Change email notification subject.</caption>
			 * function my_email_send_subject( $subject, $key ) {
			 *     // your code here
			 *     return $subject;
			 * }
			 * add_filter( 'um_email_send_subject', 'my_email_send_subject', 10, 2 );
			 */
			$subject = apply_filters( 'um_email_send_subject', UM()->options()->get( $template . '_sub' ), $template );
			$subject = wp_unslash( um_convert_tags( $subject, $args ) );

			$this->subject = html_entity_decode( $subject, ENT_QUOTES, 'UTF-8' );

			$this->message = $this->prepare_template( $template, $args );

			if ( UM()->options()->get( 'email_html' ) ) {
				$this->headers .= "Content-Type: text/html\r\n";
			} else {
				$this->headers .= "Content-Type: text/plain\r\n";
			}

			// Send mail.
			wp_mail( $email, $this->subject, $this->message, $this->headers, $this->attachments );

			/**
			 * Fires after email notification sending.
			 *
			 * @param {string} $email    Email template slug.
			 * @param {string} $template Email template settings.
			 * @param {array}  $args     Arguments for sending email.
			 *
			 * @since 2.0
			 * @hook um_after_email_notification_sending
			 *
			 * @example <caption>Action after email notification sending.</caption>
			 * function my_after_email_notification_sending( $email, $template, $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_after_email_notification_sending', 'my_after_email_notification_sending', 10, 3 );
			 */
			do_action( 'um_after_email_notification_sending', $email, $template, $args );
		}

		/**
		 * @param $template_name
		 *
		 * @return mixed|void
		 */
		public function get_template_filename( $template_name ) {
			/**
			 * Filters email notification template name.
			 *
			 * @param {string} $template_name Email template Name.
			 *
			 * @return {string} Email template Name.
			 *
			 * @since 2.0
			 * @hook um_change_email_template_file
			 *
			 * @example <caption>Change email notification template path.</caption>
			 * function my_change_email_template_file( $template_name ) {
			 *     // your code here
			 *     return $template_name;
			 * }
			 * add_filter( 'um_change_email_template_file', 'my_change_email_template_file' );
			 */
			return apply_filters( 'um_change_email_template_file', $template_name );
		}

		/**
		 * Locate a template and return the path for inclusion.
		 *
		 * @access public
		 * @param string $template_name
		 * @return string
		 */
		public function template_in_theme( $template_name ) {
			$template_name_file = $this->get_template_filename( $template_name );

			$blog_id = $this->get_blog_id();

			// check if there is a template at theme blog ID folder
			$template = locate_template(
				array(
					trailingslashit( 'ultimate-member/email' . $blog_id ) . $template_name_file . '.php',
				)
			);

			// Return what we found.
			if ( get_template_directory() === get_stylesheet_directory() ) {
				return ! $template ? false : true;
			}

			return strstr( $template, get_stylesheet_directory() );
		}

		/**
		 * Method returns an expected path for template
		 *
		 * @access public
		 *
		 * @param string $location
		 * @param string $template_name
		 *
		 * @return string
		 */
		public function get_template_file( $location, $template_name ) {
			$template_path      = '';
			$template_name_file = $this->get_template_filename( $template_name );

			switch ( $location ) {
				case 'theme':
					//save email template in blog ID folder if we use multisite
					$blog_id = $this->get_blog_id();

					$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' . $blog_id ) . $template_name_file . '.php';
					break;
				case 'plugin':
					$path          = ! empty( $this->path_by_slug[ $template_name ] ) ? $this->path_by_slug[ $template_name ] : UM_PATH . 'templates/email';
					$template_path = trailingslashit( $path ) . $template_name . '.php';
					break;
			}

			/**
			 * Filters expected path for email template in theme or plugin.
			 *
			 * @param {string} $template_path      Expected template path.
			 * @param {string} $location           Where to search 'theme||plugin'.
			 * @param {string} $template_name_file Expected filename.
			 *
			 * @return {string} Email template path.
			 *
			 * @since 2.6.1
			 * @hook um_email_get_template_file_path
			 *
			 * @example <caption>Change expected path for email template in theme or plugin.</caption>
			 * function my_email_get_template_file_path( $template_path, $location, $template_name_file ) {
			 *     // your code here
			 *     return $template_path;
			 * }
			 * add_filter( 'um_email_get_template_file_path', 'my_email_get_template_file_path' );
			 */
			return apply_filters( 'um_email_get_template_file_path', $template_path, $location, $template_name_file );
		}

		/**
		 * Copy template to the theme.
		 *
		 * @param string $template
		 * @return bool
		 */
		public function copy_email_template( $template ) {
			$in_theme = $this->template_in_theme( $template );
			if ( $in_theme ) {
				return false;
			}

			$plugin_template_path = wp_normalize_path( $this->get_template_file( 'plugin', $template ) );
			$theme_template_path  = wp_normalize_path( $this->get_template_file( 'theme', $template ) );
			$template_filename    = $this->get_template_filename( $template ) . '.php';

			$template_dir = wp_normalize_path( str_replace( $template_filename, '', $theme_template_path ) );
			$result       = wp_mkdir_p( $template_dir );
			if ( ! $result ) {
				return false;
			}

			if ( file_exists( $plugin_template_path ) && copy( $plugin_template_path, $theme_template_path ) ) {
				return true;
			}

			return false;
		}

		/**
		 * UM Placeholders for site url, admin email, submit registration.
		 *
		 * @param array $placeholders
		 *
		 * @return array
		 */
		public function add_placeholder( $placeholders ) {
			$placeholders[] = '{user_profile_link}';
			$placeholders[] = '{site_url}';
			$placeholders[] = '{admin_email}';
			$placeholders[] = '{submitted_registration}';
			$placeholders[] = '{login_url}';
			$placeholders[] = '{password}';
			$placeholders[] = '{account_activation_link}';
			$placeholders[] = '{action_url}';
			$placeholders[] = '{action_title}';
			return $placeholders;
		}

		/**
		 * UM Replace Placeholders for site url, admin email, submit registration.
		 *
		 * @param array $replace_placeholders
		 *
		 * @return array
		 */
		public function add_replace_placeholder( $replace_placeholders ) {
			$replace_placeholders[] = um_user_profile_url();
			$replace_placeholders[] = get_bloginfo( 'url' );
			$replace_placeholders[] = um_admin_email();
			$replace_placeholders[] = um_user_submitted_registration_formatted();
			$replace_placeholders[] = um_get_core_page( 'login' );
			$replace_placeholders[] = esc_html__( 'Your set password', 'ultimate-member' );
			$replace_placeholders[] = um_user( 'account_activation_link' );

			$set_password_required = get_user_meta( um_user( 'ID' ), 'um_set_password_required', true );
			if ( empty( $set_password_required ) || 'pending' === um_user( 'status' ) ) {
				$replace_placeholders[] = um_get_core_page( 'login' );
				$replace_placeholders[] = esc_html__( 'Login to our site', 'ultimate-member' );
			} else {
				$replace_placeholders[] = um_user( 'password_reset_link' );
				$replace_placeholders[] = esc_html__( 'Set your password', 'ultimate-member' );
			}

			return $replace_placeholders;
		}
	}
}
