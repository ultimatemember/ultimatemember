<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_cron_disable
			 * @description Make UM Cron Actions Enabled or Disabled
			 * @input_vars
			 * [{"var":"$cron_disable","type":"bool","desc":"Disable UM Cron?"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_cron_disable', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_cron_disable', 'my_cron_disable', 10, 1 );
			 * function my_predefined_field( $cron_disable ) {
			 *     // your code here
			 *     return $cron_disable;
			 * }
			 * ?>
			 */
			$um_cron = apply_filters( 'um_cron_disable', false );
			if ( $um_cron ) {
				return;
			}
            
			add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
			add_action( 'wp', array( $this, 'schedule_Events' ) );
		}


		/**
		 * @param array $schedules
		 *
		 * @return array
		 */
		public function add_schedules( $schedules = array() ) {

			// Adds once weekly to the existing schedules.
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once Weekly', 'ultimate-member' )
			);

			return $schedules;
		}


		/**
		 *
		 */
		public function schedule_Events() {
			$this->weekly_events();
			$this->daily_events();
			$this->twicedaily_events();
			$this->hourly_events();
		}


		/**
		 *
		 */
		private function weekly_events() {
			if ( ! wp_next_scheduled( 'um_weekly_scheduled_events' ) ) {
				wp_schedule_event( current_time( 'timestamp' ), 'weekly', 'um_weekly_scheduled_events' );
			}
		}


		/**
		 *
		 */
		private function daily_events() {
			if ( ! wp_next_scheduled( 'um_daily_scheduled_events' ) ) {
				wp_schedule_event( current_time( 'timestamp' ), 'daily', 'um_daily_scheduled_events' );
			}
		}


		/**
		 *
		 */
		private function twicedaily_events() {
			if ( ! wp_next_scheduled( 'um_twicedaily_scheduled_events' ) ) {
				wp_schedule_event( current_time( 'timestamp' ), 'twicedaily', 'um_twicedaily_scheduled_events' );
			}
		}


		/**
		 *
		 */
		private function hourly_events() {
			if ( ! wp_next_scheduled( 'um_hourly_scheduled_events' ) ) {
				wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'um_hourly_scheduled_events' );
			}
		}


		public function unschedule_events() {
			wp_clear_scheduled_hook( 'um_weekly_scheduled_events' );
			wp_clear_scheduled_hook( 'um_daily_scheduled_events' );
			wp_clear_scheduled_hook( 'um_twicedaily_scheduled_events' );
			wp_clear_scheduled_hook( 'um_hourly_scheduled_events' );
		}
	}
}
