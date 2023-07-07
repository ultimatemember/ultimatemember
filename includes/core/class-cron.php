<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Cron' ) ) {

	/**
	 * Class Cron
	 * @package um\core
	 */
	class Cron {

		/**
		 * Cron constructor.
		 */
		public function __construct() {
			add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
			add_action( 'wp', array( $this, 'schedule_events' ) );
		}

		/**
		 * @return bool
		 */
		private function cron_disabled() {
			/**
			 * Filters variable for disable Ultimate Member WP Cron actions.
			 *
			 * @since 2.0
			 * @hook  um_cron_disable
			 *
			 * @param {bool} $is_disabled Shortcode arguments.
			 *
			 * @return {bool} Do Cron actions are disabled? True for disable.
			 *
			 * @example <caption>Disable all Ultimate Member WP Cron actions.</caption>
			 * add_filter( 'um_cron_disable', '__return_true' );
			 */
			return apply_filters( 'um_cron_disable', false );
		}

		/**
		 * Adds once weekly to the existing schedules.
		 *
		 * @param array $schedules
		 *
		 * @return array
		 */
		public function add_schedules( $schedules = array() ) {
			if ( $this->cron_disabled() ) {
				return $schedules;
			}

			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once Weekly', 'ultimate-member' ),
			);

			return $schedules;
		}

		/**
		 *
		 */
		public function schedule_events() {
			if ( $this->cron_disabled() ) {
				return;
			}

			$this->weekly_events();
			$this->daily_events();
			$this->twicedaily_events();
			$this->hourly_events();
		}

		/**
		 *
		 */
		private function weekly_events() {
			$sunday_start   = wp_date( 'w' );
			$week_start     = $sunday_start - absint( get_option( 'start_of_week' ) );
			$week_start_day = strtotime( '-' . $week_start . ' days' );
			$time           = mktime( 0, 0, 0, wp_date( 'm', $week_start_day ), wp_date( 'd', $week_start_day ), wp_date( 'Y', $week_start_day ) );
			if ( ! wp_next_scheduled( 'um_weekly_scheduled_events' ) ) {
				wp_schedule_event( $time, 'weekly', 'um_weekly_scheduled_events' );
			}
		}

		/**
		 *
		 */
		private function daily_events() {
			if ( ! wp_next_scheduled( 'um_daily_scheduled_events' ) ) {
				$time = mktime( 0, 0, 0, wp_date( 'm' ), wp_date( 'd' ), wp_date( 'Y' ) );
				wp_schedule_event( $time, 'daily', 'um_daily_scheduled_events' );
			}
		}

		/**
		 *
		 */
		private function twicedaily_events() {
			if ( ! wp_next_scheduled( 'um_twicedaily_scheduled_events' ) ) {
				$time = mktime( 0, 0, 0, wp_date( 'm' ), wp_date( 'd' ), wp_date( 'Y' ) );
				wp_schedule_event( $time, 'twicedaily', 'um_twicedaily_scheduled_events' );
			}
		}

		/**
		 *
		 */
		private function hourly_events() {
			if ( ! wp_next_scheduled( 'um_hourly_scheduled_events' ) ) {
				$time = mktime( wp_date( 'H' ), 0, 0, wp_date( 'm' ), wp_date( 'd' ), wp_date( 'Y' ) );
				wp_schedule_event( $time, 'hourly', 'um_hourly_scheduled_events' );
			}
		}

		/**
		 * Breaks all Ultimate Member registered schedule events.
		 */
		public function unschedule_events() {
			wp_clear_scheduled_hook( 'um_weekly_scheduled_events' );
			wp_clear_scheduled_hook( 'um_daily_scheduled_events' );
			wp_clear_scheduled_hook( 'um_twicedaily_scheduled_events' );
			wp_clear_scheduled_hook( 'um_hourly_scheduled_events' );
		}
	}
}
