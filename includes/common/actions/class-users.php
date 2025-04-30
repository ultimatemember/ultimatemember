<?php
namespace um\common\actions;

use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\actions\Users' ) ) {

	/**
	 * Class Users
	 *
	 * @since 2.10.3
	 *
	 * @package um\common\actions
	 */
	class Users {

		const INTERVAL = 3600;

		const SCHEDULE_ACTION = 'um_schedule_empty_account_status_check';

		const BATCH_SIZE = 50;

		const BATCH_ACTION = 'um_set_default_account_status';

		public function __construct() {
			add_action( 'init', array( &$this, 'add_recurring_action' ) );
			add_action( self::SCHEDULE_ACTION, array( &$this, 'status_check' ) );
			add_action( self::BATCH_ACTION, array( &$this, 'batch_check' ), 10, 3 );
		}

		public function add_recurring_action() {
			if ( UM()->maybe_action_scheduler()->next_scheduled_action( self::SCHEDULE_ACTION ) ) {
				return;
			}

			UM()->maybe_action_scheduler()->schedule_recurring_action(
				time() + 60,
				self::INTERVAL,
				self::SCHEDULE_ACTION
			);
		}

		public function status_check() {
			// Make the control checking of the empty user statuses inside the function `get_empty_status_users`.
			// Maybe some users were approved manually or deleted and we need to reset the admin notice.
			$total_users = UM()->common()->users()::get_empty_status_users();
			if ( empty( $total_users ) ) {
				return;
			}

			UM()->maybe_action_scheduler()->enqueue_async_action(
				self::BATCH_ACTION,
				array(
					'page'  => 1,
					'total' => $total_users,
					'pages' => ceil( $total_users / self::BATCH_SIZE ),
				)
			);
		}

		/**
		 * Perform batch checking for users based on specific conditions.
		 * Ignore users with `_um_registration_in_progress` that can be in the process of the registration.
		 * Get users with empty `account_status` meta.
		 *
		 * @param int $page The current page number.
		 * @param int $total The total number of users to process.
		 * @param int $pages The total number of pages to process.
		 */
		public function batch_check( $page, $total, $pages ) {
			$users = new WP_User_Query(
				array(
					'number'     => self::BATCH_SIZE,
					'fields'     => 'ids',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => '_um_registration_in_progress',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => '_um_registration_in_progress',
								'value'   => '1',
								'compare' => '!=',
							),
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => 'account_status',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'account_status',
								'value'   => '',
								'compare' => '=',
							),
						),
					),
				)
			);

			$results = $users->get_results();

			if ( ! empty( $results ) ) {
				$um_empty_status_users = get_option( '_um_log_empty_status_users', array( 0, 0 ) );
				if ( ! is_array( $um_empty_status_users ) ) {
					$um_empty_status_users = array( 0, count( $results ) );
				}

				foreach ( $results as $user_id ) {
					$res = UM()->common()->users()->approve( $user_id, true, true );
					if ( $res || UM()->common()->users()->has_status( $user_id, 'approved' ) ) {
						++$um_empty_status_users[0];
					} else {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( 'Cannot set `approved` status for the user with ID:' . $user_id );
					}
				}

				if ( $um_empty_status_users[0] < $um_empty_status_users[1] ) {
					update_option( '_um_log_empty_status_users', $um_empty_status_users );
				} else {
					delete_option( '_um_log_empty_status_users' );
				}

				$next_page = $page + 1;
				if ( $next_page <= $pages ) {
					UM()->maybe_action_scheduler()->enqueue_async_action(
						self::BATCH_ACTION,
						array(
							'page'  => $next_page,
							'total' => $total,
							'pages' => $pages,
						)
					);
				} else {
					// Make the control checking of the empty user statuses.
					// Maybe some users were approved manually or deleted and we need to reset the admin notice.
					$total_users = UM()->common()->users()::get_empty_status_users();
					if ( empty( $total_users ) ) {
						// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
						error_log( 'There aren\'t users with empty `account_status`. Maybe some users were approved manually or deleted.' );
					}
				}
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'There aren\'t users with empty `account_status`. Maybe some users were approved manually or deleted.' );
				delete_option( '_um_log_empty_status_users' );
			}
		}
	}
}
