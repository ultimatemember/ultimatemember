<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Members' ) ) {


	/**
	 * Class Members
	 * @package um\core
	 */
	class Members {


		/**
		 * @var
		 */
		var $results;


		/**
		 * Members constructor.
		 */
		function __construct() {


		}


		/**
		 * Optimizes Member directory with multiple LEFT JOINs
		 * @param  object $vars
		 * @return object $var
		 */
		public function um_optimize_member_query( $vars ) {

			global $wpdb;

			$arr_where = explode("\n", $vars->query_where );
			$arr_left_join = explode("LEFT JOIN", $vars->query_from );
			$arr_user_photo_key = array( 'synced_profile_photo', 'profile_photo', 'synced_gravatar_hashed_id' );

			foreach ( $arr_where as $where ) {

				foreach ( $arr_user_photo_key as $key ) {

					if ( strpos( $where, "'" . $key . "'" ) > -1 ) {

						// find usermeta key
						preg_match("#mt[0-9]+.#",  $where, $meta_key );

						// remove period from found meta_key
						$meta_key = str_replace(".","", current( $meta_key ) );

						// remove matched LEFT JOIN clause
						$vars->query_from = str_replace('LEFT JOIN wp_usermeta AS '.$meta_key.' ON ( wp_users.ID = '.$meta_key.'.user_id )', '',  $vars->query_from );

						// prepare EXISTS replacement for LEFT JOIN clauses
						$where_exists = 'um_exist EXISTS( SELECT '.$wpdb->usermeta.'.umeta_id FROM '.$wpdb->usermeta.' WHERE '.$wpdb->usermeta.'.user_id = '.$wpdb->users.'.ID AND '.$wpdb->usermeta.'.meta_key IN("'.implode('","',  $arr_user_photo_key ).'") AND '.$wpdb->usermeta.'.meta_value != "" )';

						// Replace LEFT JOIN clauses with EXISTS and remove duplicates
						if ( strpos( $vars->query_where, 'um_exist' ) === FALSE ) {
							$vars->query_where = str_replace( $where , $where_exists,  $vars->query_where );
						} else {
							$vars->query_where = str_replace( $where , '1=0',  $vars->query_where );
						}
					}

				}

			}

			$vars->query_where = str_replace( "\n", "", $vars->query_where );
			$vars->query_where = str_replace( "um_exist", "", $vars->query_where );

			return $vars;

		}
	}
}