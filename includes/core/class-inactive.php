<?php
namespace um\core;

use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Inactive' ) ) {

	/**
	 * Class Inactive
	 *
	 * Handles automatic detection and deactivation of dormant user accounts.
	 *
	 * @since 2.12.1
	 * @package um\core
	 */
	class Inactive {

		/**
		 * Inactive constructor.
		 */
		public function __construct() {
			add_action( 'um_daily_scheduled_events', array( $this, 'process_inactive_users' ) );
		}

		/**
		 * Process inactive users based on admin settings.
		 *
		 * Runs daily via cron. Sends warning emails at configurable intervals
		 * and deactivates accounts that have exceeded the inactivity threshold.
		 */
		public function process_inactive_users() {
			if ( ! UM()->options()->get( 'inactive_cleanup_enabled' ) ) {
				return;
			}

			$threshold       = absint( UM()->options()->get( 'inactive_cleanup_days', 365 ) );
			$num_warnings    = absint( UM()->options()->get( 'inactive_cleanup_warnings', 3 ) );
			$min_age_days    = absint( UM()->options()->get( 'inactive_cleanup_min_age_days', 0 ) );
			$target_roles    = UM()->options()->get( 'inactive_cleanup_roles' );
			$exempt_posts    = (bool) UM()->options()->get( 'inactive_cleanup_exempt_with_posts' );
			$exempt_comments = (bool) UM()->options()->get( 'inactive_cleanup_exempt_with_comments' );

			if ( $threshold <= 0 ) {
				return;
			}

			if ( ! is_array( $target_roles ) ) {
				$target_roles = array();
			}

			$user_ids = $this->find_inactive_users( $threshold, $target_roles, $min_age_days, $exempt_posts, $exempt_comments );

			foreach ( $user_ids as $user_id ) {
				$this->handle_user( $user_id, $threshold, $num_warnings );
			}
		}

		/**
		 * Find user IDs that meet the inactivity criteria.
		 *
		 * @since 2.12.1
		 *
		 * @param  int   $threshold_days   Days without login before an account is considered inactive.
		 * @param  array $target_roles     Roles to include. Empty means all roles.
		 * @param  int   $min_age_days     Minimum account age in days. Newer accounts are skipped.
		 * @param  bool  $exempt_posts     Skip users who have published posts.
		 * @param  bool  $exempt_comments  Skip users who have approved comments.
		 * @return array  User IDs.
		 */
		private function find_inactive_users( $threshold_days, $target_roles, $min_age_days, $exempt_posts, $exempt_comments ) {
			$threshold_gmt = gmdate( 'Y-m-d H:i:s', time() - $threshold_days * DAY_IN_SECONDS );

			// Users who have logged in but not recently.
			$query1 = new WP_User_Query(
				array(
					'fields'     => 'ids',
					'meta_query' => array(
						array(
							'key'     => '_um_last_login',
							'value'   => $threshold_gmt,
							'compare' => '<=',
							'type'    => 'DATETIME',
						),
					),
				)
			);

			// Users who never logged in and registered before the threshold.
			$query2 = new WP_User_Query(
				array(
					'fields'     => 'ids',
					'meta_query' => array(
						array(
							'key'     => '_um_last_login',
							'compare' => 'NOT EXISTS',
						),
					),
					'date_query' => array(
						array(
							'column' => 'user_registered',
							'before' => $threshold_gmt,
						),
					),
				)
			);

			$user_ids = array_unique( array_merge( $query1->get_results(), $query2->get_results() ) );

			// Apply post-query filters.
			foreach ( $user_ids as $key => $user_id ) {
				$user = get_userdata( $user_id );
				if ( ! $user ) {
					unset( $user_ids[ $key ] );
					continue;
				}

				// Role filter.
				if ( ! empty( $target_roles ) ) {
					$match = false;
					foreach ( $target_roles as $role ) {
						if ( in_array( $role, (array) $user->roles, true ) ) {
							$match = true;
							break;
						}
					}
					if ( ! $match ) {
						unset( $user_ids[ $key ] );
						continue;
					}
				}

				// Minimum account age filter.
				if ( $min_age_days > 0 ) {
					$registration = strtotime( $user->user_registered );
					if ( $registration && ( time() - $registration ) < $min_age_days * DAY_IN_SECONDS ) {
						unset( $user_ids[ $key ] );
						continue;
					}
				}

				// Exempt users with published posts.
				if ( $exempt_posts && count_user_posts( $user_id ) > 0 ) {
					unset( $user_ids[ $key ] );
					continue;
				}

				// Exempt users with approved comments.
				if ( $exempt_comments ) {
					$has_comments = get_comments(
						array(
							'user_id' => $user_id,
							'status'  => 'approve',
							'count'   => true,
						)
					);
					if ( $has_comments > 0 ) {
						unset( $user_ids[ $key ] );
						continue;
					}
				}
			}

			return array_values( $user_ids );
		}

		/**
		 * Handle a single inactive user.
		 *
		 * Deactivates the user if past threshold, or sends warning email at the right stage.
		 *
		 * @since 2.12.1
		 *
		 * @param int $user_id         User ID.
		 * @param int $threshold_days  Inactivity threshold in days.
		 * @param int $num_warnings    Number of warning stages.
		 */
		private function handle_user( $user_id, $threshold_days, $num_warnings ) {
			$last_login = get_user_meta( $user_id, '_um_last_login', true );
			$min_idle   = 0;

			if ( ! empty( $last_login ) ) {
				$min_idle = ( time() - strtotime( $last_login ) ) / DAY_IN_SECONDS;
			} else {
				// Never logged in. Use user_registered as fallback.
				$user     = get_userdata( $user_id );
				$min_idle = $user ? ( time() - strtotime( $user->user_registered ) ) / DAY_IN_SECONDS : 0;
			}

			if ( $min_idle >= $threshold_days ) {
				$this->deactivate_user( $user_id );
				return;
			}

			if ( $num_warnings > 0 ) {
				$stage          = $this->warning_stage_for( $min_idle, $threshold_days, $num_warnings );
				$previous_stage = (int) get_user_meta( $user_id, '_um_inactive_warning_stage', true );

				// User was active again since the last check; forget prior warning progress.
				if ( $stage < $previous_stage ) {
					delete_user_meta( $user_id, '_um_inactive_warning_stage' );
					$previous_stage = 0;
				}

				if ( $stage > 0 && $stage > $previous_stage ) {
					$days_remaining = (int) ceil( $threshold_days - $min_idle );
					$this->send_warning( $user_id, $days_remaining );
					update_user_meta( $user_id, '_um_inactive_warning_stage', $stage );
				}
			}
		}

		/**
		 * Determine the current warning stage for an idle user.
		 *
		 * Warnings are evenly split across the inactivity window.
		 * Stage 1 is the earliest warning (most days remaining).
		 *
		 * @since 2.12.1
		 *
		 * @param float $days_idle     Days since last login.
		 * @param int   $threshold_days  Inactivity threshold.
		 * @param int   $num_warnings    Number of warning stages.
		 * @return int  Warning stage (0 = no warning needed).
		 */
		private function warning_stage_for( $days_idle, $threshold_days, $num_warnings ) {
			if ( $num_warnings <= 0 || $days_idle <= 0 ) {
				return 0;
			}
			$step = $threshold_days / ( $num_warnings + 1 );
			for ( $i = 1; $i <= $num_warnings; $i++ ) {
				if ( $days_idle >= $i * $step ) {
					return $i;
				}
			}
			return 0;
		}

		/**
		 * Send a warning email to an inactive user.
		 *
		 * @since 2.12.1
		 *
		 * @param int $user_id        User ID.
		 * @param int $days_remaining Days until deactivation.
		 */
		private function send_warning( $user_id, $days_remaining ) {
			$userdata = get_userdata( $user_id );
			if ( ! $userdata ) {
				return;
			}

			UM()->maybe_action_scheduler()->enqueue_async_action(
				'um_dispatch_email',
				array(
					$userdata->user_email,
					'inactive_warning_email',
					array(
						'fetch_user_id' => $user_id,
						'tags'          => array( '{days_until_deactivation}' ),
						'tags_replace'  => array( $days_remaining ),
					),
				)
			);
		}

		/**
		 * Deactivate a user account.
		 *
		 * Uses the existing deactivate() method which sets status to inactive,
		 * clears sessions, and sends the inactive notification.
		 *
		 * @since 2.12.1
		 *
		 * @param int $user_id User ID.
		 */
		private function deactivate_user( $user_id ) {
			if ( ! UM()->common()->users()->deactivate( $user_id ) ) {
				return;
			}
			delete_user_meta( $user_id, '_um_inactive_warning_stage' );
		}
	}
}
