<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Date_Time' ) ) {


	/**
	 * Class Date_Time
	 * @package um\core
	 */
	class Date_Time {

		/**
		 * Date_Time constructor.
		 */
		function __construct() {

		}


		/**
		 * Display time in specific format
		 *
		 * @param $format
		 *
		 * @return int|string
		 */
		function get_time( $format ) {
			return current_time( $format );
		}


		/**
		 * Show a cool time difference between 2 timestamps
		 *
		 * @param int $from
		 * @param int $to
		 *
		 * @return string
		 */
		function time_diff( $from, $to = '' ) {
			$since = '';

			if ( empty( $to ) ) {
				$to = time();
			}

			$diff = (int) abs( $to - $from );
			if ( $diff < 60 ) {

				$since = __( 'just now', 'ultimate-member' );

			} elseif ( $diff < HOUR_IN_SECONDS ) {

				$mins = round( $diff / MINUTE_IN_SECONDS );
				if ( $mins <= 1 ) {
					$mins = 1;
				}

				$since = sprintf( _n( '%s min', '%s mins', $mins, 'ultimate-member' ), $mins );

			} elseif ( $diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS ) {

				$hours = round( $diff / HOUR_IN_SECONDS );
				if ( $hours <= 1 ) {
					$hours = 1;
				}

				$since = sprintf( _n( '%s hr', '%s hrs', $hours, 'ultimate-member' ), $hours );

			} elseif ( $diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS ) {

				$days = round( $diff / DAY_IN_SECONDS );
				if ( $days <= 1 ) {
					$days = 1;
				}

				if ( $days == 1 ) {
					$since = sprintf( __( 'Yesterday at %s', 'ultimate-member' ), date_i18n( 'g:ia', $from ) );
				} else {
					$since = sprintf( __( '%s at %s', 'ultimate-member' ), date_i18n( 'F d', $from ), date_i18n( 'g:ia', $from ) );
				}

			} elseif ( $diff < 30 * DAY_IN_SECONDS && $diff >= WEEK_IN_SECONDS ) {

				$since = sprintf( __( '%s at %s', 'ultimate-member' ), date_i18n( 'F d', $from ), date_i18n( 'g:ia', $from ) );

			} elseif ( $diff < YEAR_IN_SECONDS && $diff >= 30 * DAY_IN_SECONDS ) {

				$since = sprintf( __( '%s at %s','ultimate-member'), date_i18n( 'F d', $from ), date_i18n( 'g:ia', $from ) );

			} elseif ( $diff >= YEAR_IN_SECONDS ) {

				$since = sprintf( __( '%s at %s', 'ultimate-member' ), date_i18n( 'F d, Y', $from ), date_i18n( 'g:ia', $from ) );

			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_human_time_diff
			 * @description Change human time string
			 * @input_vars
			 * [{"var":"$since","type":"string","desc":"Disable UM Cron?"},
			 * {"var":"$diff","type":"int","desc":"Difference in seconds"},
			 * {"var":"$from","type":"int","desc":"From Timestamp"},
			 * {"var":"$to","type":"int","desc":"To Timestamp"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_human_time_diff', 'function_name', 10, 4 );
			 * @example
			 * <?php
			 * add_filter( 'um_human_time_diff', 'my_human_time_diff', 10, 4 );
			 * function my_human_time_diff( $since, $diff, $from, $to ) {
			 *     // your code here
			 *     return $since;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_human_time_diff', $since, $diff, $from, $to );
		}


		/**
		 * Get age
		 *
		 * @param $then
		 *
		 * @return string
		 */
		function get_age( $then ) {
			if ( ! $then ) {
				return '';
			}
			$then_ts = strtotime( $then );
			$then_year = date( 'Y', $then_ts );
			$age = date( 'Y' ) - $then_year;
			if ( strtotime( '+' . $age . ' years', $then_ts ) > current_time( 'timestamp' ) ) {
				$age--;
			}
			if ( $age == 1 ) {
				return sprintf( __( '%s year old', 'ultimate-member' ), $age );
			}
			if ( $age > 1 ) {
				return sprintf( __( '%s years old', 'ultimate-member' ), $age );
			}
			if ( $age == 0 ) {
				return __( 'Less than 1 year old', 'ultimate-member' );
			}

			return '';
		}


		/**
		 * Reformat dates
		 *
		 * @param $old
		 * @param $new
		 *
		 * @return string
		 */
		function format( $old, $new ) {
			$datetime = new \DateTime( $old );
			$output = $datetime->format( $new );
			return $output;
		}


		/**
		 * Get last 30 days as array
		 *
		 * @param int $num
		 * @param bool $reverse
		 *
		 * @return array
		 */
		function get_last_days( $num = 30, $reverse = true ) {
			$d = array();
			for ( $i = 0; $i < $num; $i++ ) {
				$d[ date('Y-m-d', strtotime( '-' . $i . ' days' ) ) ] = date( 'm/d', strtotime( '-' . $i . ' days' ) );
			}

			return ( $reverse ) ? array_reverse( $d ) : $d;
		}

	}
}