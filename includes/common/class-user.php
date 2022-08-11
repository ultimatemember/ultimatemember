<?php namespace um\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\common\User' ) ) {


	/**
	 * Class User
	 *
	 * @package um\common
	 */
	class User {


		/**
		 * User constructor.
		 *
		 * @since 3.0
		 */
		function __construct() {
		}


		public function hooks() {
			add_filter( 'user_has_cap', array( &$this, 'map_caps_by_role' ), 10, 3 );
		}


		/**
		 * Restrict the edit/delete users via wp-admin screen by the UM role capabilities
		 *
		 * @param $allcaps
		 * @param $cap
		 * @param $args
		 * @param $user
		 *
		 * @return mixed
		 */
		function map_caps_by_role( $allcaps, $cap, $args ) {
			if ( isset( $cap[0] ) && 'edit_users' === $cap[0] ) {
				if ( ! user_can( $args[1], 'administrator' ) && $args[0] == 'edit_user' ) {
					if ( ! UM()->roles()->um_current_user_can( 'edit', $args[2] ) ) {
						$allcaps[ $cap[0] ] = false;
					}
				}
			} elseif ( isset( $cap[0] ) && 'delete_users' === $cap[0] ) {
				if ( ! user_can( $args[1], 'administrator' ) && 'delete_user' === $args[0] ) {
					if ( ! UM()->roles()->um_current_user_can( 'delete', $args[2] ) ) {
						$allcaps[ $cap[0] ] = false;
					}
				}
			} elseif ( isset( $cap[0] ) && 'list_users' === $cap[0] ) {
				if ( ! user_can( $args[1], 'administrator' ) && 'list_users' === $args[0] ) {
					if ( ! um_user( 'can_view_all' ) ) {
						$allcaps[ $cap[0] ] = false;
					}
				}
			}

			return $allcaps;
		}


		/**
		 * @param int $user_id
		 *
		 * @return bool
		 */
		function exists_by_id( $user_id ) {
			$aux = get_userdata( absint( $user_id ) );
			if ( $aux == false ) {
				return false;
			} else {
				return $user_id;
			}
		}


		/**
		 * User exists by name
		 *
		 * @param string $value
		 *
		 * @return bool
		 */
		function exists_by_name( $value ) {
			// Permalink base
			$permalink_base = UM()->options()->get( 'permalink_base' );

			$raw_value = $value;
			$value     = UM()->validation()->safe_name_in_url( $value );
			$value     = um_clean_user_basename( $value );

			// Search by Profile Slug
			$args = array(
				'fields'     => 'ids',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     =>  'um_user_profile_url_slug_' . $permalink_base,
						'value'   => strtolower( $raw_value ),
						'compare' => '=',
					),
				),
				'number'     => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
				return $user_id;
			}

			// Search by Display Name or ID
			$args = array(
				'fields'         => 'ids',
				'search'         => $value,
				'search_columns' => array( 'display_name', 'ID' ),
				'number'         => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
				return $user_id;
			}

			// Search By User Login
			$value = str_replace( ".", "_", $value );
			$value = str_replace( " ", "", $value );

			$args = array(
				'fields'         => 'ids',
				'search'         => $value,
				'search_columns' => array( 'user_login' ),
				'number'         => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
				return $user_id;
			}

			return false;
		}


		/**
		 * This method checks if a user exists or not in your site based on the user email as username
		 *
		 * @param string $slug A user slug must be passed to check if the user exists
		 *
		 * @usage <?php UM()->user()->user_exists_by_email_as_username( $slug ); ?>
		 *
		 * @return bool
		 *
		 * @example Basic Usage

		<?php

		$boolean = UM()->user()->user_exists_by_email_as_username( 'calumgmail-com' );
		if ( $boolean ) {
		// That user exists
		}

		?>
		 */
		function exists_by_email_as_username( $slug ) {
			$user_id = false;

			$args = array(
				'fields'   => 'ids',
				'meta_key' => 'um_email_as_username_' . $slug,
				'number'   => 1,
			);

			$query = new \WP_User_Query( $args );
			$total = $query->get_total();

			if ( $total > 0 ) {
				$user_id = current( $query->get_results() );
			}

			return $user_id;
		}


		/**
		 * Deletes all user roles
		 *
		 * @param int $user_id User ID.
		 */
		public function flush_roles( $user_id ) {
			$user = get_userdata( $user_id );

			if ( empty( $user->roles ) ) {
				return;
			}

			foreach ( $user->roles as $role ) {
				$user->remove_role( $role );
			}
		}
	}
}
