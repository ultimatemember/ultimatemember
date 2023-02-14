<?php
namespace umm\member_directory\includes\ajax;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Directory
 *
 * @package umm\member_directory\includes\ajax
 */
class Directory {


	/**
	 * @var
	 */
	var $query_args;


	/**
	 * @var bool Searching marker
	 */
	var $is_search = false;


	/**
	 * @var array Searching frontend filters in query
	 */
	var $custom_filters_in_query = array();


	/**
	 * @var string User Card cover size
	 */
	var $cover_size;


	/**
	 * @var string User Avatar size
	 */
	var $avatar_size;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_um_get_members', array( $this, 'get_members' ) );
		add_action( 'wp_ajax_um_get_members', array( $this, 'get_members' ) );

		add_action( 'wp_ajax_nopriv_um_member_directory_get_more_details', array( $this, 'get_more_details' ) );
		add_action( 'wp_ajax_um_member_directory_get_more_details', array( $this, 'get_more_details' ) );
	}

	public function get_more_details() {
		if ( empty( $_POST['user_id'] ) ) {
			wp_send_json_error( __( 'Wrong user data', 'ultimate-member' ) );
		}

		if ( empty( $_POST['directory_id'] ) ) {
			wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
		}

		$directory_id = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );

		if ( empty( $directory_id ) ) {
			wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
		}

		$directory_data = UM()->query()->post_data( $directory_id );

		$data_array = array();

		if ( ! empty( $directory_data['show_userinfo'] ) ) {

			if ( ! empty( $directory_data['reveal_fields'] ) ) {

				$directory_data['reveal_fields'] = maybe_unserialize( $directory_data['reveal_fields'] );

				if ( is_array( $directory_data['reveal_fields'] ) ) {
					foreach ( $directory_data['reveal_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						$value = um_filtered_value( $key );
						if ( ! $value ) {
							continue;
						}

						$label = UM()->fields()->get_label( $key );
						if ( $key == 'role_select' || $key == 'role_radio' ) {
							$label = strtr( $label, array(
								' (Dropdown)'   => '',
								' (Radio)'      => ''
							) );
						}

						$data_array[ "label_{$key}" ] = __( $label, 'ultimate-member' );
						$data_array[ $key ] = $value;
					}
				}
			}

			if ( ! empty( $directory_data['show_social'] ) ) {
				ob_start();
				UM()->fields()->show_social_urls();
				$social_urls = ob_get_clean();

				$data_array['social_urls'] = $social_urls;
			}
		}

		$content = um_get_template_html( 'member-details-modal.php', array( 'data' => $data_array ), 'member-directory' );

		wp_send_json_success( $content );
	}

	/**
	 * Getting member directory post ID via hash
	 * Hash is unique attr, which we use visible at frontend
	 *
	 * @param string $hash
	 *
	 * @return bool|int
	 */
	function get_directory_by_hash( $hash ) {
		global $wpdb;

		$directory_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE SUBSTRING( MD5( ID ), 11, 5 ) = %s", $hash ) );

		if ( empty( $directory_id ) ) {
			return false;
		}

		return (int) $directory_id;
	}


	/**
	 * Tag conversion for member directory
	 *
	 * @param string $string
	 * @param array $array
	 *
	 * @return string
	 */
	function convert_tags( $string, $array ) {

		$search = array(
			'{total_users}',
		);

		$replace = array(
			$array['total_users'],
		);

		$string = str_replace( $search, $replace, $string );
		return $string;
	}


	/**
	 * @param array $directory_data
	 */
	function predefined_no_caps( $directory_data ) {
		//predefined result for user without capabilities to see other members
		if ( is_user_logged_in() && ! UM()->roles()->um_user_can( 'can_view_all' ) ) {
			$pagination_data = array(
				'pages_to_show' => array(),
				'current_page'  => 1,
				'total_pages'   => 0,
				'total_users'   => 0,
			);

			$pagination_data['header']        = $this->convert_tags( $directory_data['header'], $pagination_data );
			$pagination_data['header_single'] = $this->convert_tags( $directory_data['header_single'], $pagination_data );

			wp_send_json_success( array( 'users' => array(), 'pagination' => $pagination_data ) );
		}
	}


	/**
	 * Handle members can view restrictions
	 */
	function restriction_options() {
		$this->hide_not_approved();
		$this->hide_by_role();
		$this->hide_by_account_settings();

		do_action( 'um_member_directory_restrictions_handle_extend' );
	}


	/**
	 *
	 */
	function hide_not_approved() {
		if ( UM()->roles()->um_user_can( 'can_edit_everyone' )  ) {
			return;
		}

		$this->query_args['meta_query'] = array_merge(
			$this->query_args['meta_query'],
			array(
				array(
					'key'     => 'um_member_directory_data',
					'value'   => 's:14:"account_status";s:8:"approved";',
					'compare' => 'LIKE',
				),
			)
		);
	}


	/**
	 *
	 */
	function hide_by_role() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$roles = um_user( 'can_view_roles' );
		$roles = maybe_unserialize( $roles );

		if ( UM()->roles()->um_user_can( 'can_view_all' ) && empty( $roles ) ) {
			return;
		}

		if ( ! empty( $this->query_args['role__in'] ) ) {
			$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
			$this->query_args['role__in'] = array_intersect( $this->query_args['role__in'], $roles );
		} else {
			$this->query_args['role__in'] = $roles;
		}
	}


	/**
	 *
	 */
	function hide_by_account_settings() {
		if ( ! UM()->options()->get( 'account_hide_in_directory' ) ) {
			return;
		}

		if ( UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
			return;
		}

		$this->query_args['meta_query'] = array_merge(
			$this->query_args['meta_query'],
			array(
				array(
					'key'     => 'um_member_directory_data',
					'value'   => 's:15:"hide_in_members";b:0;',
					'compare' => 'LIKE',
				),
			)
		);
	}


	/**
	 * Handle "General Options" metabox settings
	 *
	 * @param array $directory_data
	 */
	function general_options( $directory_data ) {
		$this->show_selected_roles( $directory_data );
		$this->show_only_with_avatar( $directory_data );
		$this->show_only_with_cover( $directory_data );
		$this->show_only_these_users( $directory_data );
		$this->exclude_these_users( $directory_data );

		do_action( 'um_member_directory_general_options_handle_extend', $directory_data );
	}


	/**
	 * Handle "User Roles to Display" option
	 *
	 * @param array $directory_data
	 */
	function show_selected_roles( $directory_data ) {
		// add roles to appear in directory
		if ( ! empty( $directory_data['roles'] ) ) {
			//since WP4.4 use 'role__in' argument
			if ( ! empty( $this->query_args['role__in'] ) ) {
				$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
				$this->query_args['role__in'] = array_intersect( $this->query_args['role__in'], maybe_unserialize( $directory_data['roles'] ) );
			} else {
				$this->query_args['role__in'] = maybe_unserialize( $directory_data['roles'] );
			}
		}
	}


	/**
	 * Handle "Only show members who have uploaded a profile photo" option
	 *
	 * @param array $directory_data
	 */
	function show_only_with_avatar( $directory_data ) {
		if ( $directory_data['has_profile_photo'] == 1 ) {
			$this->query_args['meta_query'] = array_merge(
				$this->query_args['meta_query'],
				array(
					array(
						'key'     => 'um_member_directory_data',
						'value'   => 's:13:"profile_photo";b:1;',
						'compare' => 'LIKE',
					),
				)
			);
		}
	}


	/**
	 * Handle "Only show members who have uploaded a cover photo" option
	 *
	 * @param array $directory_data
	 */
	function show_only_with_cover( $directory_data ) {
		if ( $directory_data['has_cover_photo'] == 1 ) {
			$this->query_args['meta_query'] = array_merge(
				$this->query_args['meta_query'],
				array(
					array(
						'key'     => 'um_member_directory_data',
						'value'   => 's:11:"cover_photo";b:1;',
						'compare' => 'LIKE',
					),
				)
			);
		}
	}


	/**
	 * Handle "Only show specific users (Enter one username per line)" option
	 *
	 * @param array $directory_data
	 */
	function show_only_these_users( $directory_data ) {
		if ( ! empty( $directory_data['show_these_users'] ) ) {
			$show_these_users = maybe_unserialize( $directory_data['show_these_users'] );

			if ( is_array( $show_these_users ) && ! empty( $show_these_users ) ) {
				$users_array = array();

				foreach ( $show_these_users as $username ) {
					if ( false !== ( $exists_id = username_exists( $username ) ) ) {
						$users_array[] = $exists_id;
					}
				}

				if ( ! empty( $users_array ) ) {
					$this->query_args['include'] = $users_array;
				}
			}
		}
	}


	/**
	 * Handle "Exclude specific users (Enter one username per line)" option
	 *
	 * @param array $directory_data
	 */
	function exclude_these_users( $directory_data ) {
		if ( ! empty( $directory_data['exclude_these_users'] ) ) {
			$exclude_these_users = maybe_unserialize( $directory_data['exclude_these_users'] );

			if ( is_array( $exclude_these_users ) && ! empty( $exclude_these_users ) ) {
				$users_array = array();

				foreach ( $exclude_these_users as $username ) {
					if ( false !== ( $exists_id = username_exists( $username ) ) ) {
						$users_array[] = $exists_id;
					}
				}

				if ( ! empty( $users_array ) ) {
					$this->query_args['exclude'] = $users_array;
				}
			}
		}
	}


	/**
	 * Handle "Pagination Options" metabox settings
	 *
	 * @param array $directory_data
	 */
	function pagination_options( $directory_data ) {
		// number of profiles for mobile
		$profiles_per_page = $directory_data['profiles_per_page'];
		if ( UM()->mobile()->isMobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
			$profiles_per_page = $directory_data['profiles_per_page_mobile'];
		}

		$this->query_args['number'] = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;
		$this->query_args['paged'] = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
	}


	/**
	 * Add sorting attributes for \WP_Users_Query
	 *
	 * @param array $directory_data Member Directory options
	 */
	function sorting_query( $directory_data ) {
		// sort members by
		$this->query_args['order'] = 'ASC';
		$sortby = ! empty( $_POST['sorting'] ) ? sanitize_text_field( $_POST['sorting'] ) : $directory_data['sortby'];
		$sortby = ( 'other' === $sortby ) ? $directory_data['sortby_custom'] : $sortby;

		$custom_sort = array();
		if ( ! empty( $directory_data['sorting_fields'] ) ) {
			$sorting_fields = maybe_unserialize( $directory_data['sorting_fields'] );
			foreach ( $sorting_fields as $field ) {
				if ( is_array( $field ) ) {
					$field_keys = array_keys( $field );
					$custom_sort[] = $field_keys[0];
				}
			}
		}

		$numeric_sorting_keys = array();

		if ( ! empty( UM()->builtin()->saved_fields ) ) {
			foreach ( UM()->builtin()->saved_fields as $key => $data ) {
				if ( $key == '_um_last_login' ) {
					continue;
				}

				if ( isset( $data['type'] ) && 'number' === $data['type'] ) {
					if ( array_key_exists( $key . '_desc', UM()->module( 'member-directory' )->config()->get( 'sort_fields' ) ) ) {
						$numeric_sorting_keys[] = $key . '_desc';
					}
					if ( array_key_exists( $key . '_asc', UM()->module( 'member-directory' )->config()->get( 'sort_fields' ) ) ) {
						$numeric_sorting_keys[] = $key . '_asc';
					}
				}
			}
		}

		if ( $sortby === 'username' ) {

			$this->query_args['orderby'] = 'user_login';
			$this->query_args['order']   = 'ASC';

		} elseif ( $sortby === 'display_name' ) {

			$display_name = UM()->options()->get( 'display_name' );
			if ( 'username' === $display_name ) {
				$this->query_args['orderby'] = 'user_login';
				$this->query_args['order']   = 'ASC';
			} else {
				$this->query_args['meta_query'][] = array(
					'relation'  => 'OR',
					'full_name' => array(
						'key'     => 'full_name',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'full_name',
						'compare' => 'NOT EXISTS',
					),
				);

				$this->query_args['orderby'] = 'full_name, display_name';
				$this->query_args['order']   = 'ASC';
			}

		} elseif ( in_array( $sortby, array( 'last_name', 'first_name', 'nickname' ), true ) ) {

			$this->query_args['meta_query'] = array_merge(
				$this->query_args['meta_query'],
				array(
					$sortby . '_c' => array(
						'key'     => $sortby,
						'compare' => 'EXISTS',
					),
				)
			);

			$this->query_args['orderby'] = array( $sortby . '_c' => 'ASC' );
			unset( $this->query_args['order'] );

		} elseif ( 'last_login' === $sortby ) {

			$this->query_args['orderby']      = array( 'um_last_login' => 'DESC' );
			$this->query_args['meta_query'][] = array(
				'relation'      => 'OR',
				array(
					'key'     => '_um_last_login',
					'compare' => 'EXISTS',
				),
				'um_last_login' => array(
					'key'     => '_um_last_login',
					'compare' => 'NOT EXISTS',
				),
			);
			unset( $this->query_args['order'] );

		} elseif ( 'last_first_name' === $sortby ) {

			$this->query_args['meta_query'][] = array(
				'last_name_c'  => array(
					'key'     => 'last_name',
					'compare' => 'EXISTS',
				),
				'first_name_c' => array(
					'key'     => 'first_name',
					'compare' => 'EXISTS',
				),
			);

			$this->query_args['orderby'] = array( 'last_name_c' => 'ASC', 'first_name_c' => 'ASC' );
			unset( $this->query_args['order'] );

		} elseif ( count( $numeric_sorting_keys ) && in_array( $sortby, $numeric_sorting_keys ) ) {

			$order = 'DESC';
			if ( strstr( $sortby, '_desc' ) ) {
				$sortby = str_replace( '_desc', '', $sortby );
				$order = 'DESC';
			}

			if ( strstr( $sortby, '_asc' ) ) {
				$sortby = str_replace( '_asc', '', $sortby );
				$order = 'ASC';
			}

			$this->query_args['meta_query'] = array_merge(
				$this->query_args['meta_query'],
				array(
					array(
						'relation'      => 'OR',
						array(
							'key'     => $sortby,
							'compare' => 'EXISTS',
							'type'    => 'NUMERIC',
						),
						$sortby . '_ns' => array(
							'key'     => $sortby,
							'compare' => 'NOT EXISTS',
							'type'    => 'NUMERIC',
						),
					),
				)
			);

			$this->query_args['orderby'] = array( $sortby . '_ns' => $order, 'user_registered' => 'DESC' );
			unset( $this->query_args['order'] );

		} elseif ( ( ! empty( $directory_data['sortby_custom'] ) && $sortby == $directory_data['sortby_custom'] ) || in_array( $sortby, $custom_sort ) ) {

			$custom_sort_type = apply_filters( 'um_member_directory_custom_sorting_type', 'CHAR', $sortby, $directory_data );

			$this->query_args['meta_query'][] = array(
				'relation'      => 'OR',
				$sortby . '_cs' => array(
					'key'     => $sortby,
					'compare' => 'EXISTS',
					'type'    => $custom_sort_type,
				),
				array(
					'key'     => $sortby,
					'compare' => 'NOT EXISTS',
				)
			);

			$this->query_args['orderby'] = array( $sortby . '_cs' => 'ASC', 'user_login' => 'ASC' );

		} else {

			if ( strstr( $sortby, '_desc' ) ) {
				$sortby = str_replace( '_desc', '', $sortby );
				$order  = 'DESC';
			}

			if ( strstr( $sortby, '_asc' ) ) {
				$sortby = str_replace( '_asc', '', $sortby );
				$order  = 'ASC';
			}

			$this->query_args['orderby'] = $sortby;
			if ( isset( $order ) ) {
				$this->query_args['order'] = $order;
			}

			add_action( 'pre_user_query', array( &$this, 'sortby_randomly' ), 10, 1 );
		}

		$this->query_args = apply_filters( 'um_modify_sortby_parameter', $this->query_args, $sortby );
	}


	/**
	 * Sorting random
	 *
	 * @param \WP_User_Query $query
	 */
	function sortby_randomly( $query ) {
		if ( 'random' === $query->query_vars['orderby'] ) {

			if ( false === um_is_session_started() ) {
				@session_start();
			}

			// Reset seed on load of initial
			if ( empty( $_REQUEST['directory_id'] ) && isset( $_SESSION['um_member_directory_seed'] ) ) {
				unset( $_SESSION['um_member_directory_seed'] );
			}

			// Get seed from session variable if it exists
			$seed = false;
			if ( isset( $_SESSION['um_member_directory_seed'] ) ) {
				$seed = $_SESSION['um_member_directory_seed'];
			}

			// Set new seed if none exists
			if ( ! $seed ) {
				$seed = rand();
				$_SESSION['um_member_directory_seed'] = $seed;
			}

			$query->query_orderby = 'ORDER by RAND(' . $seed . ')';
		}
	}


	/**
	 * Handle general search line request
	 */
	function general_search() {
		//general search
		if ( ! empty( $_POST['search'] ) ) {
			// complex using with `change_meta_sql function

			$search = trim( stripslashes( sanitize_text_field( $_POST['search'] ) ) );

			$meta_query = array(
				'relation' => 'OR',
				array(
					'value'   => $search,
					'compare' => '=',
				),
				array(
					'value'   => $search,
					'compare' => 'LIKE',
				),
				array(
					'value'   => serialize( (string) $search ),
					'compare' => 'LIKE',
				),
			);

			$meta_query = apply_filters( 'um_member_directory_general_search_meta_query', $meta_query, $search );

			$this->query_args['meta_query'][] = $meta_query;

			$this->is_search = true;
		}
	}


	/**
	 * Change mySQL meta query join attribute
	 * for search only by UM user meta fields and WP core fields in WP Users table
	 *
	 * @param array $sql Array containing the query's JOIN and WHERE clauses.
	 * @param $queries
	 * @param $type
	 * @param $primary_table
	 * @param $primary_id_column
	 * @param \WP_User_Query $context
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	function change_meta_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context ) {
		if ( ! empty( $_POST['search'] ) ) {
			global $wpdb;
			$search = trim( stripslashes( sanitize_text_field( $_POST['search'] ) ) );

			if ( ! empty( $search ) ) {
				$meta_value  = '%' . $wpdb->esc_like( $search ) . '%';
				$search_meta = $wpdb->prepare( '%s', $meta_value );

				preg_match(
					'/^(.*).meta_value LIKE ' . addslashes( $search_meta ) . '[^\)]/im',
					$sql['where'],
					$join_matches
				);

				if ( isset( $join_matches[1] ) ) {
					$meta_join_for_search = trim( $join_matches[1] );

					// skip private invisible fields
					$custom_fields = array();
					foreach ( array_keys( UM()->builtin()->all_user_fields ) as $field_key ) {
						$data = UM()->fields()->get_field( $field_key );
						if ( ! um_can_view_field( $data ) ) {
							continue;
						}

						$custom_fields[] = $field_key;
					}

					$custom_fields = apply_filters( 'um_general_search_custom_fields', $custom_fields );

					if ( ! empty( $custom_fields ) ) {
						$sql['join'] = preg_replace(
							'/(' . $meta_join_for_search . ' ON \( ' . $wpdb->users . '\.ID = ' . $meta_join_for_search . '\.user_id )(\))/im',
							"$1 AND " . $meta_join_for_search . ".meta_key IN( '" . implode( "','", $custom_fields ) . "' ) $2",
							$sql['join']
						);
					}
				}

				// Add OR instead AND to search in WP core fields user_email, user_login, user_display_name
				$search_where = $context->get_search_sql( $search, UM()->module( 'member-directory' )->config()->get( 'core_search_fields' ), 'both' );

				$search_where = preg_replace( '/ AND \((.*?)\)/im', "$1 OR", $search_where );

				$sql['where'] = preg_replace(
					'/(' . $meta_join_for_search . '.meta_value = \'' . esc_attr( $search ) . '\')/im',
					trim( $search_where ) . " $1",
					$sql['where'],
					1
				);
			}
		}

		return $sql;
	}


	/**
	 * Update limit query
	 *
	 * @param \WP_User_Query $user_query
	 */
	function pagination_changes( $user_query ) {
		global $wpdb;

		$directory_id   = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );
		$directory_data = UM()->query()->post_data( $directory_id );

		$qv = $user_query->query_vars;

		$number = $qv['number'];
		if ( ! empty( $directory_data['max_users'] ) && $qv['paged']*$qv['number'] > $directory_data['max_users'] ) {
			$number = ( $qv['paged']*$qv['number'] - ( $qv['paged']*$qv['number'] - $directory_data['max_users'] ) ) % $qv['number'];
		}

		// limit
		if ( isset( $qv['number'] ) && $qv['number'] > 0 ) {
			if ( $qv['offset'] ) {
				$user_query->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['offset'], $number );
			} else {
				$user_query->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['number'] * ( $qv['paged'] - 1 ), $number );
			}
		}
	}


	/**
	 * Handle filters request
	 *
	 * @param array $directory_data
	 *
	 * @throws \Exception
	 */
	function filters( $directory_data ) {
		//filters
		$filter_query = array();
		if ( ! empty( $directory_data['search_fields'] ) ) {
			$search_filters = maybe_unserialize( $directory_data['search_fields'] );

			if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
				$filter_query = array_intersect_key( $_POST, array_flip( $search_filters ) );
			}
		}

		// added for user tags extension integration on individual tag page
		$ignore_empty_filters = apply_filters( 'um_member_directory_ignore_empty_filters', false );

		if ( empty( $filter_query ) && ! $ignore_empty_filters ) {
			return;
		}

		$this->is_search = true;
		$filter_types = UM()->module( 'member-directory' )->config()->get( 'filter_types' );
		foreach ( $filter_query as $field => $value ) {
			$field = sanitize_text_field( $field );
			if ( is_array( $value ) ) {
				$value = array_map( 'sanitize_text_field', $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			$attrs = UM()->fields()->get_field( $field );
			// skip private invisible fields
			if ( ! um_can_view_field( $attrs ) ) {
				continue;
			}

			switch ( $field ) {
				default:

					$filter_type = $filter_types[ $field ];

					$field_query = apply_filters( "um_query_args_{$field}__filter", false, $field, $value, $filter_type, $this );
					$field_query = apply_filters( 'um_query_args_filter_global', $field_query, $field, $value, $filter_type, $this );

					if ( ! $field_query ) {

						switch ( $filter_type ) {
							default:

								$field_query = apply_filters( "um_query_args_{$field}_{$filter_type}__filter", false, $field, $value, $filter_type, $this );

								break;
							case 'text':

								$value = stripslashes( $value );
								$field_query = array(
									'relation' => 'OR',
									array(
										'key'     => $field,
										'value'   => trim( $value ),
										'compare' => apply_filters( 'um_members_directory_filter_text', 'LIKE', $field ),
									),
								);

								$this->custom_filters_in_query[ $field ] = $value;

								break;

							case 'select':
								if ( is_array( $value ) ) {
									$field_query = array( 'relation' => 'OR' );

									foreach ( $value as $single_val ) {
										$single_val = trim( stripslashes( $single_val ) );

										$arr_meta_query = array(
											array(
												'key'     => $field,
												'value'   => $single_val,
												'compare' => '=',
											),
											array(
												'key'     => $field,
												'value'   => serialize( (string) $single_val ),
												'compare' => 'LIKE',
											),
											array(
												'key'     => $field,
												'value'   => '"' . $single_val . '"',
												'compare' => 'LIKE',
											)
										);

										if ( is_numeric( $single_val ) ) {

											$arr_meta_query[] = array(
												'key'     => $field,
												'value'   => serialize( (int) $single_val ),
												'compare' => 'LIKE',
											);

										}

										$field_query = array_merge( $field_query, $arr_meta_query );
									}
								}

								$this->custom_filters_in_query[ $field ] = $value;

								break;
							case 'slider':

								$this->custom_filters_in_query[ $field ] = $value;

								$field_query = array(
									'key'       => $field,
									'value'     => $value,
									'compare'   => 'BETWEEN',
									'inclusive' => true,
									'type'      => 'NUMERIC',
								);

								break;
							case 'datepicker':

								$offset = 0;
								if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
									$offset = (int) $_POST['gmt_offset'];
								}

								$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
								$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
								$from_date = date( 'Y/m/d', $from_date );
								$to_date = date( 'Y/m/d', $to_date );

								$field_query = array(
									'key'       => $field,
									'value'     =>  array( $from_date, $to_date ),
									'compare'   => 'BETWEEN',
									'inclusive' => true,
								);

								$this->custom_filters_in_query[ $field ] = array( $from_date, $to_date );

								break;
							case 'timepicker':

								if ( $value[0] == $value[1] ) {
									$field_query = array(
										'key'   => $field,
										'value' => $value[0],
									);
								} else {
									$field_query = array(
										'key'       => $field,
										'value'     => $value,
										'compare'   => 'BETWEEN',
										'type'      => 'TIME',
										'inclusive' => true,
									);
								}

								$this->custom_filters_in_query[ $field ] = $value;

								break;
						}

					}

					if ( ! empty( $field_query ) && $field_query !== true ) {
						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $field_query ) );
					}

					break;
				case 'role':
					$value = array_map( 'strtolower', $value );

					if ( ! empty( $this->query_args['role__in'] ) ) {
						$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
						$default_role = array_intersect( $this->query_args['role__in'], $value );
						$um_role = array_diff( $value, $default_role );

						foreach ( $um_role as $key => &$val ) {
							$val = 'um_' . str_replace( ' ', '-', $val );
						}
						$this->query_args['role__in'] = array_merge( $default_role, $um_role );
					} else {
						$this->query_args['role__in'] = $value;
					};

					$this->custom_filters_in_query[ $field ] = $this->query_args['role__in'];

					break;
				case 'birth_date':

					$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
					$to_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

					$meta_query = array(
						array(
							'key'       => 'birth_date',
							'value'     => array( $to_date, $from_date ),
							'compare'   => 'BETWEEN',
							'type'      => 'DATE',
							'inclusive' => true,
						)
					);

					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

					$this->custom_filters_in_query[ $field ] = array( $to_date, $from_date );

					break;
				case 'user_registered':

					$offset = 0;
					if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
						$offset = (int) $_POST['gmt_offset'];
					}

					$from_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', min( $value ) ) . "+$offset hours" ) );
					$to_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', max( $value ) ) . "+$offset hours" ) );

					$date_query = array(
						array(
							'column'    => 'user_registered',
							'before'    => $to_date,
							'after'     => $from_date,
							'inclusive' => true,
						),
					);

					if ( empty( $this->query_args['date_query'] ) ) {
						$this->query_args['date_query'] = $date_query;
					} else {
						$this->query_args['date_query'] = array_merge( $this->query_args['date_query'], array( $date_query ) );
					}

					$this->custom_filters_in_query[ $field ] = $value;

					break;
				case 'last_login':

					$offset = 0;
					if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
						$offset = (int) $_POST['gmt_offset'];
					}

					$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
					$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
					$meta_query = array(
						array(
							'key'       => '_um_last_login',
							'value'     =>  array( $from_date, $to_date ),
							'compare'   => 'BETWEEN',
							'inclusive' => true,
						)
					);

					$this->custom_filters_in_query[ $field ] = $value;

					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
					break;
			}

		}
	}


	/**
	 * Set default filters
	 *
	 * @param $directory_data
	 */
	function default_filters( $directory_data ) {
		$default_filters = array();
		if ( ! empty( $directory_data['search_filters'] ) ) {
			$default_filters = maybe_unserialize( $directory_data['search_filters'] );
		}

		$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );

		if ( empty( $default_filters ) ) {
			return;
		}

		$filter_types = UM()->module( 'member-directory' )->config()->get( 'filter_types' );
		foreach ( $default_filters as $field => $value ) {

			switch ( $field ) {
				default:

					$filter_type = $filter_types[ $field ];

					$field_query = apply_filters( "um_query_args_{$field}__filter", false, $field, $value, $filter_type );

					if ( ! $field_query ) {

						switch ( $filter_type ) {
							default:

								$field_query = apply_filters( "um_query_args_{$field}_{$filter_type}__filter", false, $field, $value, $filter_type );
								break;

							case 'text':

								$field_query = array(
									'key'     => $field,
									'value'   => $value,
									'compare' => apply_filters( 'um_members_directory_filter_text', '=', $field ),
								);

								break;

							case 'select':
								if ( ! is_array( $value ) ) {
									$value = array( $value );
								}

								$field_query = array( 'relation' => 'OR' );

								foreach ( $value as $single_val ) {
									$single_val = trim( $single_val );

									$arr_meta_query = array(
										array(
											'key'     => $field,
											'value'   => $single_val,
											'compare' => '=',
										),
										array(
											'key'     => $field,
											'value'   => serialize( (string) $single_val ),
											'compare' => 'LIKE',
										),
										array(
											'key'     => $field,
											'value'   => '"' . $single_val . '"',
											'compare' => 'LIKE',
										),
									);

									if ( is_numeric( $single_val ) ) {

										$arr_meta_query[] = array(
											'key'     => $field,
											'value'   => serialize( (int) $single_val ),
											'compare' => 'LIKE',
										);

									}

									$field_query = array_merge( $field_query, $arr_meta_query );
								}

								break;
							case 'slider':

								$field_query = array(
									'key'       => $field,
									'value'     => $value,
									'compare'   => 'BETWEEN',
									'inclusive' => true,
								);

								break;
							case 'datepicker':

								$offset = 0;
								if ( is_numeric( $gmt_offset ) ) {
									$offset = $gmt_offset;
								}

								$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
								$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
								$field_query = array(
									'key'       => $field,
									'value'     =>  array( $from_date, $to_date ),
									'compare'   => 'BETWEEN',
									'inclusive' => true,
								);

								break;
							case 'timepicker':

								if ( $value[0] == $value[1] ) {
									$field_query = array(
										'key'   => $field,
										'value' => $value[0],
									);
								} else {
									$field_query = array(
										'key'       => $field,
										'value'     => $value,
										'compare'   => 'BETWEEN',
										'type'      => 'TIME',
										'inclusive' => true,
									);
								}

								break;
						}

					}

					if ( ! empty( $field_query ) && $field_query !== true ) {
						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $field_query ) );
					}

					break;
				case 'role':
					$value = is_array( $value ) ? $value : explode( '||', $value );
					$value = array_map( 'strtolower', $value );

					if ( ! empty( $this->query_args['role__in'] ) ) {
						$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
						$default_role = array_intersect( $this->query_args['role__in'], $value );
						$um_role = array_diff( $value, $default_role );

						foreach ( $um_role as $key => &$val ) {
							$val = 'um_' . str_replace( ' ', '-', $val );
						}
						$this->query_args['role__in'] = array_merge( $default_role, $um_role );
					} else {
						$this->query_args['role__in'] = $value;
					};

					break;
				case 'birth_date':
					$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
					$to_date   = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

					$meta_query = array(
						array(
							'key'       => 'birth_date',
							'value'     => array( $to_date, $from_date ),
							'compare'   => 'BETWEEN',
							'type'      => 'DATE',
							'inclusive' => true,
						)
					);

					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

					break;
				case 'user_registered':
					$offset = 0;
					if ( is_numeric( $gmt_offset ) ) {
						$offset = $gmt_offset;
					}

					$from_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', min( $value ) ) . "+$offset hours" ) );
					$to_date   = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', max( $value ) ) . "+$offset hours" ) );

					$date_query = array(
						array(
							'column'    => 'user_registered',
							'before'    => $to_date,
							'after'     => $from_date,
							'inclusive' => true,
						),
					);

					if ( empty( $this->query_args['date_query'] ) ) {
						$this->query_args['date_query'] = $date_query;
					} else {
						$this->query_args['date_query'] = array_merge( $this->query_args['date_query'], array( $date_query ) );
					}

					break;
				case 'last_login':
					$offset = 0;
					if ( is_numeric( $gmt_offset ) ) {
						$offset = $gmt_offset;
					}

					$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
					$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59

					$meta_query = array(
						array(
							'key'       => '_um_last_login',
							'value'     =>  array( $from_date, $to_date ),
							'compare'   => 'BETWEEN',
							'inclusive' => true,
						)
					);

					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
					break;
			}
		}
	}


	/**
	 * Get data array for pagination
	 *
	 *
	 * @param array $directory_data
	 * @param int $total_users
	 *
	 * @return array
	 */
	function calculate_pagination( $directory_data, $total_users ) {
		$current_page = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$total_users  = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $total_users ) ? $directory_data['max_users'] : $total_users;

		// number of profiles for mobile
		$profiles_per_page = $directory_data['profiles_per_page'];
		if ( UM()->mobile()->isMobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
			$profiles_per_page = $directory_data['profiles_per_page_mobile'];
		}

		$pages_to_show = array();
		$total_pages   = 0;
		if ( ! empty( $total_users ) ) {
			$total_pages = 1;
			if ( ! empty( $profiles_per_page ) ) {
				$total_pages = absint( ceil( $total_users / $profiles_per_page ) );
			}

			if ( $total_pages <= 7 ) {
				$pages_to_show = array(
					1 => array( 'label' => '1', 'current' => false, ),
					2 => array( 'label' => '2', 'current' => false, ),
					3 => array( 'label' => '3', 'current' => false, ),
					4 => array( 'label' => '4', 'current' => false, ),
					5 => array( 'label' => '5', 'current' => false, ),
					6 => array( 'label' => '6', 'current' => false, ),
					7 => array( 'label' => '7', 'current' => false, ),
				);
				$pages_to_show = array_filter( $pages_to_show, function( $key ) use ( $total_pages ) {
					return $key <= $total_pages;
				}, ARRAY_FILTER_USE_KEY );
			} else {
				$pages_to_show = array();
				$next_dot = true;
				for ( $i = 1; $i <= $total_pages; $i++ ) {
					if ( $i > 3 && $i <= $total_pages - 3 ) {
						if ( $i === $current_page ) {
							$pages_to_show[ $i ] = array( 'label' => (string) $i, 'current' => $i === $current_page, );
							$next_dot = true;
						} elseif ( $next_dot ) {
							$next_dot = false;
							$pages_to_show[ $i ] = array( 'label' => '...', 'current' => $i === $current_page, );
						}
					} else {
						$pages_to_show[ $i ] = array( 'label' => (string) $i, 'current' => $i === $current_page, );
					}
				}
			}
			$pages_to_show[ $current_page ]['current'] = true;

			$pages_to_show = count( $pages_to_show ) > 1 ? $pages_to_show : array();
		}

		$pagination_data = array(
			'pages_to_show' => $pages_to_show,
			'current_page'  => $current_page,
			'total_pages'   => $total_pages,
			'total_users'   => $total_users,
		);

		$pagination_data['header']        = $this->convert_tags( $directory_data['header'], $pagination_data );
		$pagination_data['header_single'] = $this->convert_tags( $directory_data['header_single'], $pagination_data );

		return $pagination_data;
	}


	/**
	 * Main Query function for getting members via AJAX
	 *
	 * @throws \Exception
	 */
	function get_members() {
		UM()->ajax()->check_nonce( 'um-frontend-nonce' );

		global $wpdb;

		if ( empty( $_POST['directory_id'] ) ) {
			wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
		}

		$directory_id = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );

		if ( empty( $directory_id ) ) {
			wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
		}

		$directory_data = UM()->query()->post_data( $directory_id );

		//predefined result for user without capabilities to see other members
		$this->predefined_no_caps( $directory_data );

		do_action( 'um_member_directory_before_query' );

		// Prepare for BIG SELECT query
		$wpdb->query( 'SET SQL_BIG_SELECTS=1' );

		// Prepare default user query values
		$this->query_args = array(
			'fields'     => 'ids',
			'number'     => 0,
			'meta_query' => array(
				'relation' => 'AND',
			),
		);


		// handle different restrictions
		$this->restriction_options();

		// handle general options
		$this->general_options( $directory_data );

		// handle pagination options
		$this->pagination_options( $directory_data );

		// handle sorting options
		$this->sorting_query( $directory_data );

		// handle general search line
		$this->general_search();

		// handle filters
		$this->filters( $directory_data );

		$this->default_filters( $directory_data );

		$this->query_args = apply_filters( 'um_prepare_user_query_args', $this->query_args, $directory_data );

		//unset empty meta_query attribute
		if ( isset( $this->query_args['meta_query']['relation'] ) && count( $this->query_args['meta_query'] ) == 1 ) {
			unset( $this->query_args['meta_query'] );
		}

		if ( is_user_logged_in() && ! UM()->roles()->um_user_can( 'can_view_all' ) && isset( $this->query_args['role__in'] ) && empty( $this->query_args['role__in'] ) ) {
			$member_directory_response = apply_filters(
				'um_ajax_get_members_response',
				array(
					'pagination'    => $this->calculate_pagination( $directory_data, 0 ),
					'users'         => array(),
					'is_search'     => $this->is_search,
				),
				$directory_data
			);

			wp_send_json_success( $member_directory_response );
		}

		do_action( 'um_user_before_query', $this->query_args, $this );

		add_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10, 6 );
		add_action( 'pre_user_query', array( &$this, 'pagination_changes' ), 10, 1 );

		$user_query = new \WP_User_Query( $this->query_args );

		remove_action( 'pre_user_query', array( &$this, 'pagination_changes' ), 10 );
		remove_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10 );

		do_action( 'um_user_after_query', $this->query_args, $user_query );

		$pagination_data = $this->calculate_pagination( $directory_data, $user_query->total_users );

		$user_ids = ! empty( $user_query->results ) ? array_unique( $user_query->results ) : array();
		$user_ids = apply_filters( 'um_prepare_user_results_array', $user_ids, $this->query_args );

		$sizes            = UM()->options()->get( 'cover_thumb_sizes' );
		$this->cover_size = UM()->mobile()->isTablet() ? $sizes[1] : end( $sizes );
		$this->cover_size = apply_filters( 'um_member_directory_cover_image_size', $this->cover_size, $directory_data );

		$avatar_size       = UM()->options()->get( 'profile_photosize' );
		$this->avatar_size = str_replace( 'px', '', $avatar_size );
		$this->avatar_size = apply_filters( 'um_member_directory_avatar_image_size', $this->avatar_size, $directory_data );

		$users = array();
		foreach ( $user_ids as $user_id ) {
			$users[] = $this->build_user_card_data( $user_id, $directory_data );
		}

		um_reset_user();
		// end of user card

		$member_directory_response = apply_filters(
			'um_ajax_get_members_response',
			array(
				'pagination' => $pagination_data,
				'users'      => $users,
				'is_search'  => $this->is_search,
			),
			$directory_data
		);

		wp_send_json_success( $member_directory_response );
	}


	/**
	 * @param int $user_id
	 * @param array $directory_data
	 *
	 * @return array
	 */
	function build_user_card_data( $user_id, $directory_data ) {
		um_fetch_user( $user_id );

		$dropdown_actions = $this->build_user_actions_list( $user_id );

//		// Replace hook 'um_members_just_after_name'
//		ob_start();
//		do_action( 'um_members_just_after_name', $user_id, $directory_data );
//		$hook_just_after_name = ob_get_clean();
//
//		// Replace hook 'um_members_after_user_name'
//		ob_start();
//		do_action( 'um_members_after_user_name', $user_id, $directory_data );
//		$hook_after_user_name = ob_get_clean();

		$data_array = array(
			'card_anchor'          => substr( md5( $user_id ), 10, 5 ),
			'id'                   => $user_id,
			'role'                 => um_user( 'role' ),
			'account_status'       => um_user( 'account_status' ),
			'account_status_name'  => um_user( 'account_status_name' ),
			'display_name'         => um_user( 'display_name' ),
			'profile_url'          => um_user_profile_url(),
			'display_name_html'    => um_user( 'display_name', 'html' ),
			'dropdown_actions'     => $dropdown_actions,
//			'hook_just_after_name' => preg_replace( '/^\s+/im', '', $hook_just_after_name ),
//			'hook_after_user_name' => preg_replace( '/^\s+/im', '', $hook_after_user_name ),
		);

		if ( get_option( 'show_avatars' ) ) {
			$data_array['avatar'] = get_avatar( $user_id, $this->avatar_size );
		}

		if ( UM()->options()->get( 'use_cover_photos' ) ) {
			$data_array['cover_photo'] = um_user( 'cover_photo', $this->cover_size );
		}

		if ( ! empty( $directory_data['show_tagline'] ) ) {
			if ( ! empty( $directory_data['tagline_fields'] ) ) {
				$directory_data['tagline_fields'] = maybe_unserialize( $directory_data['tagline_fields'] );

				if ( is_array( $directory_data['tagline_fields'] ) ) {
					foreach ( $directory_data['tagline_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						$value = um_filtered_value( $key );

						if ( ! $value ) {
							continue;
						}

						$data_array[ $key ] = $value;
					}
				}
			}
		}

/*		if ( ! empty( $directory_data['show_userinfo'] ) ) {

			if ( ! empty( $directory_data['reveal_fields'] ) ) {

				$directory_data['reveal_fields'] = maybe_unserialize( $directory_data['reveal_fields'] );

				if ( is_array( $directory_data['reveal_fields'] ) ) {
					foreach ( $directory_data['reveal_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						$value = um_filtered_value( $key );
						if ( ! $value ) {
							continue;
						}

						$label = UM()->fields()->get_label( $key );
						if ( $key == 'role_select' || $key == 'role_radio' ) {
							$label = strtr( $label, array(
								' (Dropdown)'   => '',
								' (Radio)'      => ''
							) );
						}

						$data_array[ "label_{$key}" ] = __( $label, 'ultimate-member' );
						$data_array[ $key ] = $value;
					}
				}
			}

			if ( ! empty( $directory_data['show_social'] ) ) {
				ob_start();
				UM()->fields()->show_social_urls();
				$social_urls = ob_get_clean();

				$data_array['social_urls'] = $social_urls;
			}
		}*/

		$data_array = apply_filters( 'um_ajax_get_members_data', $data_array, $user_id, $directory_data );

		um_reset_user_clean();

		return $data_array;
	}


	/**
	 * @param int $user_id
	 *
	 * @return array
	 */
	function build_user_actions_list( $user_id ) {
		$actions = array();
		if ( ! is_user_logged_in() ) {
			return $actions;
		}

		if ( get_current_user_id() != $user_id ) {

			if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
				$actions['um-editprofile'] = array(
					'title' => __( 'Edit Profile', 'ultimate-member' ),
					'url' => um_edit_profile_url(),
				);
			}

			$admin_actions = apply_filters( 'um_admin_user_actions_hook', array(), $user_id );
			if ( ! empty( $admin_actions ) ) {
				foreach ( $admin_actions as $id => $arr ) {
					$url = add_query_arg( array( 'um_action' => $id, 'uid' => $user_id ), um_get_predefined_page_url( 'user' ) );

					$actions[ $id ] = array(
						'title' => $arr['label'],
						'url'   => $url,
					);
				}
			}

			$actions = apply_filters( 'um_member_directory_users_card_actions', $actions, $user_id );

		} else {

			if ( empty( UM()->user()->cannot_edit ) ) {
				$actions['um-editprofile'] = array(
					'title' => __( 'Edit Profile', 'ultimate-member' ),
					'url'   => um_edit_profile_url(),
				);
			}

			$actions['um-myaccount'] = array(
				'title' => __( 'My Account', 'ultimate-member' ),
				'url'   => um_get_predefined_page_url( 'account' ),
			);

			$actions['um-logout'] = array(
				'title' => __( 'Logout', 'ultimate-member' ),
				'url'   => um_get_predefined_page_url( 'logout' ),
			);

			$actions = apply_filters( 'um_member_directory_my_user_card_actions', $actions, $user_id );
		}

		return $actions;
	}
}
