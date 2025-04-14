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

		const SCHEDULE_ACTION = 'um_schedule_account_status_check';

		const BATCH_SIZE = 50;

		const BATCH_ACTION = 'um_check_account_status_batch';

		public function __construct() {
			add_action( 'init', array( &$this, 'add_recurring_action' ) );
			add_action( self::SCHEDULE_ACTION, array( &$this, 'status_check' ) );
			add_action( self::BATCH_ACTION, array( &$this, 'batch_check' ) );
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
			global $wpdb;
			$total_users = $wpdb->get_var(
				"SELECT COUNT(u.ID)
				FROM {$wpdb->users} u
				LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'account_status'
				LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'um_registration_in_progress'
				WHERE ( um.meta_value IS NULL OR um.meta_value = '' ) AND
					  um2.meta_value IS NULL OR um2.meta_value != '1'"
			);
			$total_users = absint( $total_users );

			if ( empty( $total_users ) ) {
				return;
			}

			for ( $offset = 0; $offset < $total_users; $offset += self::BATCH_SIZE ) {
				UM()->maybe_action_scheduler()->enqueue_async_action( self::BATCH_ACTION, array( $offset ) );
			}
		}

		public function batch_check( $offset ) {
			$users = new WP_User_Query(
				array(
					'number'     => self::BATCH_SIZE,
					'offset'     => $offset,
					'fields'     => 'ids',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'relation' => 'OR',
							array(
								'key'     => 'um_registration_in_progress',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'um_registration_in_progress',
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
				foreach ( $results as $user_id ) {
					UM()->common()->users()->approve( $user_id, true, true );
				}
			}
		}
	}
}
