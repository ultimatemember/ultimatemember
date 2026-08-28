<?php
namespace um\common;

use Random\RandomException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Guest' ) ) {

	/**
	 * Class Guest
	 *
	 * @package um\common
	 */
	class Guest {

		/**
		 * @var string
		 */
		private static $key = 'um_guest_token';

		/**
		 * @var float|int
		 */
		private static $expiration = DAY_IN_SECONDS;

		/**
		 * Guest constructor.
		 */
		public function __construct() {
			add_action( 'um_core_loaded', array( &$this, 'set_guest_token' ) );
			add_action( 'wp_logout', array( &$this, 'flush_cookies' ) );
			add_action( 'wp_login', array( &$this, 'flush_cookies' ) );
		}

		/**
		 * @return void
		 * @throws RandomException
		 */
		public function set_guest_token() {
			if ( wp_doing_cron() || UM()->is_ajax() ) {
				return;
			}

			if ( is_user_logged_in() ) {
				if ( isset( $_COOKIE[ self::$key ] ) ) {
					// flush cookies after login
					UM()::setcookie( self::$key, false );
				}
			} elseif ( ! isset( $_COOKIE[ self::$key ] ) ) {
				self::generate_token();
			}
		}

		/**
		 * @return string
		 * @throws RandomException
		 */
		private static function generate_token() {
			$guest_token = bin2hex( random_bytes( 32 ) ); // More secure than uniqid()

			self::insert_token( $guest_token );

			// Set HTTP-only cookie
			UM()::setcookie( self::$key, $guest_token, time() + self::$expiration, '/', false ); // 1-day expiry

			return $guest_token;
		}

		/**
		 * @return string|null
		 * @throws RandomException
		 */
		public function get_guest_token() {
			global $wpdb;

			if ( is_user_logged_in() ) {
				return null;
			}

			if ( ! isset( $_COOKIE[ self::$key ] ) ) {
				return self::generate_token();
			}

			$guest_token = sanitize_text_field( $_COOKIE[ self::$key ] );
			$guest_data  = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT *
					FROM {$wpdb->prefix}um_guest_tokens
					WHERE token = %s",
					$guest_token
				)
			);

			if ( ! $guest_data ) {
				// Possible hijacking attempt.
				return null;
			}

			// Extra Security: Verify IP and User-Agent
			wp_fix_server_vars();

			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; // Capture IP
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'undefined'; // Capture browser details

			if ( $guest_data->ip_address !== $ip_address || $guest_data->user_agent !== $user_agent ) {
				// Possible hijacking attempt.
				return null;
			}

			return $guest_token;
		}

		/**
		 * @return void
		 */
		public static function set_download_attempts() {
			global $wpdb;

			$guest_token = sanitize_text_field( $_COOKIE[ self::$key ] );
			if ( empty( $guest_token ) ) {
				return;
			}

			wp_fix_server_vars();
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; // Capture IP

			// Log this download attempt
			$wpdb->insert(
				"{$wpdb->prefix}um_guest_download_attempts",
				array(
					'token'      => $guest_token,
					'ip_address' => $ip_address,
				),
				array(
					'%s',
					'%s',
				)
			);
		}

		/**
		 * @return bool|null
		 */
		public static function check_excessive_downloads() {
			global $wpdb;

			$guest_token = sanitize_text_field( $_COOKIE[ self::$key ] );
			if ( empty( $guest_token ) ) {
				return null;
			}

			wp_fix_server_vars();
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; // Capture IP
			$interval   = apply_filters( 'um_guest_download_attempts_limit_interval', 5 );
			$limit      = apply_filters( 'um_guest_download_attempts_limit', 5 );

			// Check for excessive downloads (e.g., max 5 downloads per 5 minutes)
			$download_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$wpdb->prefix}um_guest_download_attempts
					WHERE token = %s AND
						  ip_address = %s AND
						  request_time > NOW() - INTERVAL %d MINUTE",
					$guest_token,
					$ip_address,
					$interval
				)
			);

			return $download_count >= $limit;
		}

		/**
		 * Flush cookies for secure access to temp uploaded files.
		 * @return void
		 */
		public function flush_cookies() {
			UM()::setcookie( self::$key, false );
		}

		private static function insert_token( $guest_token ) {
			global $wpdb;

			wp_fix_server_vars();

			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'; // Capture IP
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'undefined'; // Capture browser details

			// Store token in the database
			$wpdb->insert(
				"{$wpdb->prefix}um_guest_tokens",
				array(
					'token'      => $guest_token,
					'ip_address' => $ip_address,
					'user_agent' => $user_agent,
				),
				array(
					'%s',
					'%s',
					'%s',
				)
			);
		}
	}
}
