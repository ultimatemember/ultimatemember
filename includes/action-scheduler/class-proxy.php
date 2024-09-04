<?php

namespace um\action_scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\Action_Scheduler\Proxy' ) ) {

	/**
	 * Class Action_Scheduler
	 *
	 * Wrapper for action-scheduler
	 *
	 * @package um\action_scheduler
	 */
	class Proxy {

		protected $default_group = 'ultimate-member';
		// TODO: Do we need prefix for hook?.

		/**
		 * Action_Scheduler constructor.
		 */
		public function __construct() {
			require_once UM_PATH . 'includes/lib/action-scheduler/action-scheduler.php';
		}

		/**
		 * Enqueue an action to run one time, as soon as possible.
		 *
		 * @param string $hook Required. Name of the action hook.
		 * @param array $args Arguments to pass to callbacks when the hook triggers. Default: array().
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param boolean $unique Whether the action should be unique. Default: false.
		 * @param integer $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
		 *
		 * @return int Еhe action’s ID. Zero if there was an error scheduling the action. The error will be sent to error_log
		 */
		public function enqueue_async_action( $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
			$group = $this->set_group( $group );
			return as_enqueue_async_action( $hook, $args, $group, $unique, $priority );
		}

		/**
		 * Schedule an action to run one time at some defined point in the future.
		 *
		 * @param integer $timestamp Required. The Unix timestamp representing the date you want the action to run.
		 * @param string $hook Required. Name of the action hook.
		 * @param array $args Arguments to pass to callbacks when the hook triggers. Default: array()
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param boolean $unique Whether the action should be unique. Default: false.
		 * @param integer $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.)
		 *
		 * @return int The action’s ID. Zero if there was an error scheduling the action. The error will be sent to error_log.
		 */
		public function schedule_single_action( $timestamp, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
			$group = $this->set_group( $group );

			return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
		}

		/**
		 * Schedule an action to run repeatedly with a specified interval in seconds.
		 *
		 * @param integer $timestamp Required. The Unix timestamp representing the date you want the action to run.
		 * @param integer $interval_in_seconds Required. How long to wait between runs.
		 * @param string $hook Required. Name of the action hook.
		 * @param array $args Arguments to pass to callbacks when the hook triggers. Default: array().
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param boolean $unique Whether the action should be unique. Default: false.
		 * @param integer $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
		 *
		 * @return int The action’s ID. Zero if there was an error scheduling the action. The error will be sent to error_log.
		 */
		public function schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
			$group = $this->set_group( $group );

			return as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $group, $unique, $priority );
		}

		/**
		 * Schedule an action that recurs on a cron-like schedule.
		 *
		 * If execution of a cron-like action is delayed, the next attempt will still be scheduled according to the provided cron expression.
		 *
		 * @param integer $timestamp Required. The Unix timestamp representing the date you want the action to run.
		 * @param string $schedule Required. A cron-like schedule string, see http://en.wikipedia.org/wiki/Cron.
		 * @param string $hook Required Name of the action hook.
		 * @param array $args Arguments to pass to callbacks when the hook triggers. Default: array().
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param boolean $unique Whether the action should be unique. Default: false.
		 * @param integer $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
		 *
		 * @return int The action’s ID. Zero if there was an error scheduling the action. The error will be sent to error_log.
		 */
		public function schedule_cron_action( $timestamp, $schedule, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
			$group = $this->set_group( $group );

			return as_schedule_cron_action( $timestamp, $schedule, $hook, $args, $group, $unique, $priority );
		}

		/**
		 * Cancel the next occurrence of a scheduled action.
		 *
		 * @param string $hook Required. Name of the action hook.
		 * @param array $args Arguments passed to callbacks when the hook triggers. Default: array().
		 * @param string $group The group the job is assigned to. Default: ''.
		 *
		 * @return int|null
		 */
		public function unschedule_action( $hook, $args = array(), $group = '' ) {
			$group = $this->set_group( $group );

			return as_unschedule_action( $hook, $args, $group );
		}

		/**
		 * Cancel all occurrences of a scheduled action.
		 *
		 * @param string $hook Required. Name of the action hook.
		 * @param array $args Arguments passed to callbacks when the hook triggers. Default: array().
		 * @param string $group The group the job is assigned to. Default: ''.
		 *
		 * @return string|null The scheduled action ID if a scheduled action was found, or null if no matching action found.
		 */
		public function unschedule_all_actions( $hook, $args = array(), $group = '' ) {
			$group = $this->set_group( $group );

			return as_unschedule_all_actions( $hook, $args, $group );
		}

		/**
		 * Returns the next timestamp for a scheduled action.
		 *
		 * @param string $hook Required. Name of the action hook. Default: none.
		 * @param array $args Arguments passed to callbacks when the hook triggers. Default: array().
		 * @param string $group The group the job is assigned to. Default: ''.
		 *
		 * @return int|bool The timestamp for the next occurrence of a pending scheduled action, true for an async or in-progress action or false if there is no matching action.
		 */
		public function next_scheduled_action( $hook, $args = array(), $group = '' ) {
			$group = $this->set_group( $group );

			return as_next_scheduled_action( $hook, $args, $group );
		}

		/**
		 * Check if there is a scheduled action in the queue, but more efficiently than as_next_scheduled_action().
		 * It’s recommended to use this function when you need to know whether a specific action is currently scheduled.
		 *
		 * @param string $hook Required. Name of the action hook. Default: none.
		 * @param array $args Arguments passed to callbacks when the hook triggers. Default: array().
		 * @param string $group The group the job is assigned to. Default: ''.
		 *
		 * @return bool True if a matching action is pending or in-progress, false otherwise.
		 */
		public function has_scheduled_action( $hook, $args = array(), $group = '' ) {
			$group = $this->set_group( $group );

			return as_has_scheduled_action( $hook, $args, $group );
		}

		/**
		 * @param array $args $args (array) Arguments to search and filter results by. Possible arguments, with their default values:
		 * 'hook' => '' - the name of the action that will be triggered
		 * 'args' => NULL - the args array that will be passed with the action
		 * 'date' => NULL - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime().
		 * 'date_compare' => '<=’ - operator for testing “date”. accepted values are ‘!=’, ‘>’, ‘>=’, ‘<’, ‘<=’, ‘=’
		 * 'modified' => NULL - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime().
		 * 'modified_compare' => '<=' - operator for testing “modified”. accepted values are ‘!=’, ‘>’, ‘>=’, ‘<’, ‘<=’, ‘=’
		 * 'group' => '' - the group the action belongs to
		 * 'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
		 * 'claimed' => NULL - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
		 * 'per_page' => 5 - Number of results to return
		 * 'offset' => 0
		 * 'orderby' => 'date' - accepted values are ‘hook’, ‘group’, ‘modified’, or ‘date’
		 * 'order' => 'ASC'
		 * @param string $return_format The format in which to return the scheduled actions: 'OBJECT', 'ARRAY_A', or 'ids'. Default: 'OBJECT'.
		 *
		 * @return array Array of action rows matching the criteria specified with $args.
		 */
		public function get_scheduled_actions( $args, $return_format = 'OBJECT' ) {
			if ( ! empty( $args['group'] ) ) {
				$args['group'] = $this->set_group( $args['group'] );
			}
			return as_get_scheduled_actions( $args, $return_format );
		}

		public function set_group( $group ) {
			if ( empty( $group ) ) {
				return $this->default_group;
			} else {
				return $this->default_group . '_' . $group;
			}
		}
	}
}
