<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\common\Mail' ) ) {


	/**
	 * Class Mail
	 *
	 * @package um\common
	 */
	class Mail {

		/**
		 * @var array
		 */
		var $email_templates = array();

		/**
		 * Mail constructor.
		 */
		function __construct() {
		}

		/**
		 *
		 */
		public function hooks() {
			//mandrill compatibility
			add_filter( 'mandrill_nl2br', array( &$this, 'mandrill_nl2br' ), 10, 1 );
		}

		/**
		 * Mandrill compatibility
		 *
		 * @param $nl2br
		 * @return bool
		 */
		function mandrill_nl2br( $nl2br ) {
			// text emails
			if ( ! UM()->options()->get( 'email_html' ) ) {
				$nl2br = true;
			}

			return $nl2br;
		}

		/**
		 * Prepare email template to send
		 *
		 * @param $slug
		 * @param $args
		 * @return mixed|string
		 */
		function prepare_template( $slug, $args = array() ) {
			$emails = UM()->config()->get( 'email_notifications' );

			$module = ! empty( $emails[ $slug ]['module'] ) ? $emails[ $slug ]['module'] : '';

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

				<?php um_get_template( "emails/{$slug}.php", $args, $module ); ?>

				</body>
				</html>

			<?php } else {
				// uses plain text email here
				// important don't use HTML in plain text emails!
				um_get_template( "emails/plain/{$slug}.php", $args, $module );
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

			add_filter( 'um_template_tags_patterns_hook', array( &$this, 'add_placeholder' ), 10, 1 );
			add_filter( 'um_template_tags_replaces_hook', array( &$this, 'add_replace_placeholder' ), 10, 1 );

			// Convert tags in email template
			return um_convert_tags( $message, $args );
		}

		public function init_replace_placeholders( $template, $args ) {
			if ( 'approved_email' === $template || 'resetpw_email' === $template ) {
				add_filter( 'um_template_tags_patterns_hook', array( $this, 'password_add_placeholder' ), 10, 1 );
				add_filter( 'um_template_tags_replaces_hook', array( $this, 'password_add_replace_placeholder' ), 10, 1 );
			} elseif ( 'checkmail_email' === $template ) {
				add_filter( 'um_template_tags_patterns_hook', array( $this, 'add_activation_placeholder' ), 10, 1 );
				add_filter( 'um_template_tags_replaces_hook', array( $this, 'add_activation_replace_placeholder' ), 10, 1 );
			}
		}

		/**
		 * UM Placeholders for reset password
		 *
		 * @param $placeholders
		 *
		 * @return array
		 */
		public function password_add_placeholder( $placeholders ) {
			$placeholders[] = '{password_reset_link}';
			$placeholders[] = '{password}';
			return $placeholders;
		}

		/**
		 * UM Replace Placeholders for reset password
		 *
		 * @param $replace_placeholders
		 *
		 * @return array
		 */
		public function password_add_replace_placeholder( $replace_placeholders ) {
			$replace_placeholders[] = um_user( 'password_reset_link' );
			$replace_placeholders[] = esc_html__( 'Your set password', 'ultimate-member' );
			return $replace_placeholders;
		}

		/**
		 * UM Placeholders for activation link in email
		 *
		 * @param $placeholders
		 *
		 * @return array
		 */
		public function add_activation_placeholder( $placeholders ) {
			$placeholders[] = '{account_activation_link}';
			return $placeholders;
		}

		/**
		 * UM Replace Placeholders for activation link in email
		 *
		 * @param $replace_placeholders
		 *
		 * @return array
		 */
		public function add_activation_replace_placeholder( $replace_placeholders ) {
			$replace_placeholders[] = UM()->common()->user()->get_account_activation_link( um_user( 'ID' ) );
			return $replace_placeholders;
		}

		/**
		 * Send Email function
		 *
		 * @param string $email
		 * @param null $template
		 * @param array $args
		 */
		function send( $email, $template, $args = array() ) {
			if ( ! is_email( $email ) ) {
				return;
			}

			if ( UM()->options()->get( $template . '_on' ) != 1 ) {
				return;
			}

			do_action( 'um_before_email_notification_sending', $email, $template, $args );

			$this->attachments = array();
			$this->headers     = 'From: '. stripslashes( UM()->options()->get( 'mail_from' ) ) .' <'. UM()->options()->get( 'mail_from_addr' ) .'>' . "\r\n";

			$this->init_replace_placeholders( $template, $args );

			add_filter( 'um_template_tags_patterns_hook', array( $this, 'add_placeholder' ), 10, 1 );
			add_filter( 'um_template_tags_replaces_hook', array( $this, 'add_replace_placeholder' ), 10, 1 );

			$subject = apply_filters( 'um_email_send_subject', UM()->options()->get( $template . '_sub' ), $template );

			$subject = wp_unslash( um_convert_tags( $subject, $args ) );

			$this->subject = html_entity_decode( $subject, ENT_QUOTES, 'UTF-8' );

			$this->message = $this->prepare_template( $template, $args );

			if ( UM()->options()->get( 'email_html' ) ) {
				$this->headers .= "Content-Type: text/html\r\n";
			} else {
				$this->headers .= "Content-Type: text/plain\r\n";
			}

			// Send mail
			wp_mail( $email, $this->subject, $this->message, $this->headers, $this->attachments );

			do_action( 'um_after_email_notification_sending', $email, $template, $args );
		}

		/**
		 * UM Placeholders for site url, admin email, submit registration
		 *
		 * @param $placeholders
		 *
		 * @return array
		 */
		function add_placeholder( $placeholders ) {
			$placeholders[] = '{user_profile_link}';
			$placeholders[] = '{site_url}';
			$placeholders[] = '{admin_email}';
			$placeholders[] = '{submitted_registration}';
			$placeholders[] = '{login_url}';
			$placeholders[] = '{password}';
			$placeholders[] = '{account_activation_link}';
			return $placeholders;
		}

		/**
		 * UM Replace Placeholders for site url, admin email, submit registration
		 *
		 * @param $replace_placeholders
		 *
		 * @return array
		 */
		function add_replace_placeholder( $replace_placeholders ) {
			$replace_placeholders[] = um_user_profile_url();
			$replace_placeholders[] = get_bloginfo( 'url' );
			$replace_placeholders[] = um_admin_email();
			$replace_placeholders[] = um_user_submitted_registration_formatted();
			$replace_placeholders[] = um_get_predefined_page_url( 'login' );
			$replace_placeholders[] = esc_html__( 'Your set password', 'ultimate-member' );
			$replace_placeholders[] = um_user( 'account_activation_link' );
			return $replace_placeholders;
		}
	}
}
