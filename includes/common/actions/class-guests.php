<?php
namespace um\common\actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\actions\Guests' ) ) {
	/**
	 * Class Guests
	 *
	 * @package um\common
	 */
	class Guests {

		/**
		 * Scheduled action hook name.
		 *
		 * @var string
		 */
		const FLUSH_ACTION = 'um_flush_guest_tokens';

		/**
		 * Guests constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'maybe_add_scheduled_action' ) );
			add_action( self::FLUSH_ACTION, array( $this, 'flush_guest_data' ) );
		}

		/**
		 * Maybe add recurring cleanup action.
		 *
		 * @return void
		 */
		public function maybe_add_scheduled_action() {
			if ( UM()->maybe_action_scheduler()->next_scheduled_action( self::FLUSH_ACTION ) ) {
				return;
			}

			$flush_interval = apply_filters( 'um_flush_guest_tokens_scheduled_action_interval', DAY_IN_SECONDS );

			UM()->maybe_action_scheduler()->schedule_recurring_action(
				strtotime( 'midnight tonight' ),
				$flush_interval,
				self::FLUSH_ACTION
			);
		}

		/**
		 * Flush expired guest tokens and old guest download attempts.
		 *
		 * @return void
		 */
		public function flush_guest_data() {
			global $wpdb;

			$tokens_table   = "{$wpdb->prefix}um_guest_tokens";
			$attempts_table = "{$wpdb->prefix}um_guest_download_attempts";

			/**
			 * How many days guest tokens should live.
			 *
			 * @param int $days
			 */
			$tokens_ttl_days = absint( apply_filters( 'um_guest_tokens_ttl_days', 0 ) );

			/**
			 * How many days download attempts should live.
			 *
			 * @param int $days
			 */
			$attempts_ttl_days = absint( apply_filters( 'um_guest_download_attempts_ttl_days', 0 ) );

			if ( UM()->is_new_ui() ) {
				$temp_folder = UM()->common()->filesystem()->get_tempdir();
			} else {
				$temp_folder = UM()->files()->upload_temp;
			}

			// Get expired guest tokens
			$expired_guest_tokens = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT token
					FROM {$tokens_table}
					WHERE created_at < NOW() - INTERVAL %d DAY",
					$tokens_ttl_days
				)
			);

			// Remove temp folders for collected tokens.
			foreach ( $expired_guest_tokens as $token ) {
				$token = sanitize_file_name( $token );

				if ( empty( $token ) ) {
					continue;
				}

				$folder_path = trailingslashit( $temp_folder ) . $token;

				if ( is_dir( $folder_path ) ) {
					UM()->common()->filesystem()::remove_dir( $folder_path );
				}
			}

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$tokens_table}
					WHERE created_at < NOW() - INTERVAL %d DAY",
					$tokens_ttl_days
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$attempts_table}
					WHERE request_time < NOW() - INTERVAL %d DAY",
					$attempts_ttl_days
				)
			);

			// Reset auto-increment value (if $tokens_ttl_days and $attempts_ttl_days = 0)
			if ( 0 === $attempts_ttl_days && 0 === $attempts_ttl_days ) {
				$wpdb->query("ALTER TABLE {$wpdb->prefix}um_guest_tokens AUTO_INCREMENT = 1");
			}
		}
	}
}
