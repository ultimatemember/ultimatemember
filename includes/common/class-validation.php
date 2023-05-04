<?php
namespace um\common;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Validation' ) ) {

	/**
	 * Class Validation.
	 *
	 * @package um\common
	 */
	class Validation {

		/**
		 * It means "Any word character (letter, number, underscore) with dash and point."
		 *
		 * @var string
		 */
		protected $regex_safe = '/\A[\w\-\.]+\z/';

		/**
		 * A list of keys that should never be in wp_usermeta.
		 *
		 * @var array
		 */
		protected $banned_keys = array();

		/**
		 * A list of columns inside wp_users table.
		 *
		 * @var array
		 */
		protected $wp_users_columns = array(
			'ID',
			'user_login',
			'user_pass',
			'user_nicename',
			'user_email',
			'user_url',
			'user_registered',
			'user_activation_key',
			'user_status',
			'display_name',
		);

		/**
		 * Validation constructor.
		 */
		public function __construct() {
		}

		/**
		 * Getting filtered list of keys that should never be in wp_usermeta.
		 * Public helper for getting protected $this->banned_keys.
		 *
		 * @return array
		 */
		public function get_banned_keys() {
			global $wpdb;

			$this->banned_keys = array_merge(
				$this->wp_users_columns,
				array(
					'metabox',
					'postbox',
					'meta-box',
					'dismissed_wp_pointers',
					'session_tokens',
					'screen_layout',
					'wp_user-',
					'dismissed',
					'cap_key',
					$wpdb->get_blog_prefix() . 'capabilities',
					'managenav',
					'nav_menu',
					'user_activation_key',
					'level_',
					$wpdb->get_blog_prefix() . 'user_level',
				)
			);

			$this->banned_keys = apply_filters( 'um_banned_user_metakeys', $this->banned_keys );
			return $this->banned_keys;
		}

		/**
		 * @param string $email Email to check.
		 *
		 * @return array
		 */
		public function email_is_blocked( $email ) {
			$emails = UM()->options()->get( 'blocked_emails' );
			if ( empty( $emails ) ) {
				return array( false, 'empty' );
			}

			if ( ! is_email( $email ) ) {
				return array( false, 'invalid' );
			}

			$emails = strtolower( $emails );
			$emails = array_map( 'rtrim', explode( "\n", $emails ) );

			if ( in_array( strtolower( $email ), $emails, true ) ) {
				return array( true, 'email' );
			}

			$domain       = explode( '@', $email );
			$check_domain = str_replace( $domain[0], '*', $email );

			if ( in_array( strtolower( $check_domain ), $emails, true ) ) {
				return array( true, 'domain' );
			}

			return array( false, 'valid' );
		}

		/**
		 * Dash and underscore (metakey)
		 *
		 * @param $string
		 *
		 * @return bool
		 */
		public function metakey_is_valid( $string ) {
			/**
			 * Change validation regex for each string.
			 *
			 * @since 2.0
			 * @hook um_validation_safe_string_regex
			 *
			 * @param {string} $regex RegEx string.
			 *
			 * @return {string} Filtered RegEx string.
			 */
			$regex_safe_string = apply_filters( 'um_validation_safe_string_regex', $this->regex_safe );

			if ( ! preg_match( $regex_safe_string, $string ) ) {
				return false;
			}
			return true;
		}
	}
}
