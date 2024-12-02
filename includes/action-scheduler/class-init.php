<?php
namespace um\action_scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\action_scheduler\Init' ) ) {

	/**
	 * Class Init
	 *
	 * @since 2.9.0
	 *
	 * @package um\action_scheduler
	 */
	class Init {

		/**
		 * Global variable if Action Scheduler is active.
		 *
		 * @var bool
		 */
		protected $enabled = false;

		/**
		 * Action Scheduler group
		 *
		 * @var string
		 */
		protected $default_group = 'ultimate-member';

		/**
		 * Path to library
		 *
		 * @var string
		 */
		protected $lib_path = UM_PATH . 'includes/lib/action-scheduler/action-scheduler.php';

		/**
		 *
		 */
		public function __construct() {
			if ( ! $this->can_be_active() ) {
				add_action( 'init', array( $this, 'add_notice' ) );
			} else {
				add_filter( 'um_settings_structure', array( $this, 'add_setting' ) );

				if ( UM()->options()->get( 'enable_action_scheduler' ) ) {
					$this->enabled = true;
					$this->load_library( true );
				}
			}
		}

		public function is_enabled() {
			return $this->enabled;
		}

		public function add_notice() {
			UM()->admin()->notices()->add_notice(
				'um-action-scheduler',
				array(
					'class'   => 'notice-warning is-dismissible',
					// translators: %1$s - Plugin name, %1$s - Plugin Version
					'message' => '<p>' . sprintf( __( '<strong>%1$s %2$s</strong> The file needed to enable the Action Scheduler is missing. The plugin will continue to function as it did before, but without the new benefits offered by the Action Scheduler.', 'ultimate-member' ), UM_PLUGIN_NAME, UM_VERSION ) . '</p>',
				)
			);
		}

		/**
		 * Adds the Action Scheduler setting to Ultimate Member feature settings
		 *
		 * @param array $settings
		 *
		 * @return array
		 */
		public function add_setting( $settings ) {
			$settings['advanced']['sections']['features']['form_sections']['features']['fields'][] = array(
				'id'             => 'enable_action_scheduler',
				'type'           => 'checkbox',
				'label'          => __( 'Action Scheduler', 'ultimate-member' ),
				'checkbox_label' => __( 'Enable Action Scheduler', 'ultimate-member' ),
				'description'    => __( 'Check this box if you want to use the Ultimate Member action scheduler. By enabling it, certain tasks like sending system emails will be scheduled to run at optimal times, which can help reduce the load on your server', 'ultimate-member' ),
			);

			return $settings;
		}

		/**
		 * Verifies whether WooCommerce is installed and activated,
		 * and checks for the existence of the WooCommerce Action Scheduler file.
		 *
		 * @return bool
		 */
		public function verify_wc_action_scheduler() {
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				return false;
			}

			$action_scheduler_file = WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php';

			return file_exists( $action_scheduler_file );
		}

		/**
		 * Tries to load Action Scheduler from Ultimate Member if file exists
		 *
		 * @param bool $force
		 *
		 * @return bool
		 */
		public function load_library( $force = false ) {
			if ( file_exists( $this->lib_path ) ) {
				if ( $force ) {
					require_once $this->lib_path;
				}

				return true;
			}

			return false;
		}

		/**
		 * Checks whenever Action Scheduler can be active
		 *
		 * @return bool
		 */
		public function can_be_active() {
			return $this->verify_wc_action_scheduler() || $this->load_library();
		}

		/**
		 * Enqueue an action to run one time, as soon as possible.
		 * If Action Scheduler is disabled then do_action_ref_array is called to run action right away.
		 *
		 * @param string $hook Required. Name of the action hook.
		 * @param array  $args Arguments to pass to callbacks when the hook triggers. Default: array().
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param bool   $unique Whether the action should be unique. Default: false.
		 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
		 *
		 * @return int Еhe action’s ID. Zero if there was an error scheduling the action. The error will be sent to error_log
		 */
		public function enqueue_async_action( $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
			if ( $this->enabled ) {
				$group = $this->set_group( $group );
				return as_enqueue_async_action( $hook, $args, $group, $unique, $priority );
			}

			do_action_ref_array( $hook, $args );
			return 0;
		}

		/**
		 * Schedule an action to run one time at some defined point in the future.
		 *
		 * @param int    $timestamp Required. The Unix timestamp representing the date you want the action to run.
		 * @param string $hook Required. Name of the action hook.
		 * @param array  $args Arguments to pass to callbacks when the hook triggers. Default: array()
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param bool   $unique Whether the action should be unique. Default: false.
		 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
		 *
		 * @return int The action’s ID. Zero if there was an error scheduling the action. The error will be sent to error_log.
		 */
		public function schedule_single_action( $timestamp, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
			if ( $this->enabled ) {
				$group = $this->set_group( $group );
				return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
			}

			do_action_ref_array( $hook, $args );
			return 0;
		}

		/**
		 * Schedule an action to run repeatedly with a specified interval in seconds.
		 *
		 * @param int    $timestamp Required. The Unix timestamp representing the date you want the action to run.
		 * @param int    $interval_in_seconds Required. How long to wait between runs.
		 * @param string $hook Required. Name of the action hook.
		 * @param array  $args Arguments to pass to callbacks when the hook triggers. Default: array().
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param bool   $unique Whether the action should be unique. Default: false.
		 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
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
		 * @param int    $timestamp Required. The Unix timestamp representing the date you want the action to run.
		 * @param string $schedule Required. A cron-like schedule string, see http://en.wikipedia.org/wiki/Cron.
		 * @param string $hook Required Name of the action hook.
		 * @param array  $args Arguments to pass to callbacks when the hook triggers. Default: array().
		 * @param string $group The group to assign this job to. Default: ''.
		 * @param bool   $unique Whether the action should be unique. Default: false.
		 * @param int    $priority Lower values take precedence over higher values. Defaults to 10, with acceptable values falling in the range 0-255.
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
		 * @param array  $args Arguments passed to callbacks when the hook triggers. Default: array().
		 * @param string $group The group the job is assigned to. Default: ''.
		 *
		 * @return int|null The scheduled action ID if a scheduled action was found, or null if no matching action found.
		 */
		public function unschedule_action( $hook, $args = array(), $group = '' ) {
			$group = $this->set_group( $group );

			return as_unschedule_action( $hook, $args, $group );
		}

		/**
		 * Cancel all occurrences of a scheduled action.
		 *
		 * @param string $hook The hook that the job will trigger.
		 * @param array  $args Args that would have been passed to the job.
		 * @param string $group The group the job is assigned to.
		 */
		public function unschedule_all_actions( $hook, $args = array(), $group = '' ) {
			$group = $this->set_group( $group );

			as_unschedule_all_actions( $hook, $args, $group );
		}

		/**
		 * Returns the next timestamp for a scheduled action.
		 *
		 * @param string $hook Required. Name of the action hook. Default: none.
		 * @param array  $args Arguments passed to callbacks when the hook triggers. Default: array().
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
		 * @param array  $args Arguments passed to callbacks when the hook triggers. Default: array().
		 * @param string $group The group the job is assigned to. Default: ''.
		 *
		 * @return bool True if a matching action is pending or in-progress, false otherwise.
		 */
		public function has_scheduled_action( $hook, $args = array(), $group = '' ) {
			$group = $this->set_group( $group );

			return as_has_scheduled_action( $hook, $args, $group );
		}

		/**
		 * @param array $args Arguments to search and filter results by. Possible arguments, with their default values:
		 *          'hook' => '' - the name of the action that will be triggered
		 *          'args' => NULL - the args array that will be passed with the action
		 *          'date' => NULL - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime().
		 *          'date_compare' => '<=' - operator for testing “date”. accepted values are ‘!=’, ‘>’, ‘>=’, ‘<’, ‘<=’, ‘=’
		 *          'modified' => NULL - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime().
		 *          'modified_compare' => '<=' - operator for testing “modified”. accepted values are ‘!=’, ‘>’, ‘>=’, ‘<’, ‘<=’, ‘=’
		 *          'group' => '' - the group the action belongs to
		 *          'status' => '' - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
		 *          'claimed' => NULL - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
		 *          'per_page' => 5 - Number of results to return
		 *          'offset' => 0
		 *          'orderby' => 'date' - accepted values are ‘hook’, ‘group’, ‘modified’, or ‘date’
		 *          'order' => 'ASC'
		 *
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
			}

			return $this->default_group . '_' . $group;
		}
	}
}
