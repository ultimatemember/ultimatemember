<?php
namespace um\frontend;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\frontend\User' ) ) {


	/**
	 * Class User
	 *
	 * @package um\frontend
	 */
	class User {


		/**
		 * @var null|int
		 */
		var $query_id = null;


		/**
		 * User constructor.
		 */
		function __construct() {
			add_action( 'parse_query',  array( &$this, 'set_user_id' ) );
		}


		/**
		 * todo prepare the docs about the logic for parsing slug and what slugs the users can have
		 */
		function set_user_id() {
			$user_id   = null;
			$query_var = get_query_var( 'um_user' );

			$permalink_base = UM()->options()->get( 'permalink_base' );

			switch ( $permalink_base ) {
				case 'user_login':
					$user_id = username_exists( $query_var );

					//Try by Profile Slug
					if ( ! $user_id ) {
						$args = array(
							'fields'     => 'ids',
							'meta_query' => array(
								array(
									'key'     =>  'um_user_profile_url_slug_' . $permalink_base,
									'value'   => strtolower( $query_var ),
									'compare' => '=',
								)
							),
							'number'     => 1,
						);

						$query = new \WP_User_Query( $args );
						$total = $query->get_total();

						if ( $total > 0 ) {
							$user_id = current( $query->get_results() );
						}
					}

					// Try nicename
					if ( ! $user_id ) {
						$slug     = str_replace( '.', '-', $query_var );
						$the_user = get_user_by( 'slug', $slug );

						if ( is_a( $the_user, '\WP_User' ) ) {
							$user_id = $the_user->ID;
						}

						if ( ! $user_id ) {
							$user_id = UM()->common()->user()->exists_by_email_as_username( $query_var );
						}

						if ( ! $user_id ) {
							$user_id = UM()->common()->user()->exists_by_email_as_username( $slug );
						}
					}
					break;
				case 'user_id':
					$user_id = UM()->common()->user()->exists_by_id( $query_var );
					break;
				case 'name':
				case 'name_dash':
				case 'name_dot':
				case 'name_plus':
					$user_id = UM()->common()->user()->exists_by_name( $query_var );
					break;
			}

			$this->query_id = $user_id;
		}


		function get_id() {
			return $this->query_id;
		}
	}
}
