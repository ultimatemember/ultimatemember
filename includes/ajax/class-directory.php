<?php
namespace um\ajax;

use Exception;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um\ajax
 */
class Directory extends \um\common\Directory {

	/**
	 * AJAX get members query variables.
	 *
	 * @var array
	 */
	public $query_args = array();

	/**
	 * @var array
	 */
	public $custom_filters_in_query = array();

	/**
	 * Directory constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_nopriv_um_get_members', array( $this, 'ajax_get_members' ) );
		add_action( 'wp_ajax_um_get_members', array( $this, 'ajax_get_members' ) );
		add_action( 'wp_ajax_um_member_directory_default_filter_settings', array( $this, 'default_filter_settings' ) );
	}

	public function default_filter_settings() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'um_search_filters' ) ) {
			wp_send_json_error( __( 'Wrong nonce.', 'ultimate-member' ) );
		}

		// we can't use function "sanitize_key" because it changes uppercase to lowercase
		$filter_key   = sanitize_text_field( $_REQUEST['key'] );
		$directory_id = absint( $_REQUEST['directory_id'] );
		$html         = UM()->member_directory()->show_filter( $filter_key, array( 'form_id' => $directory_id ), false, true );

		wp_send_json_success( array( 'field_html' => $html ) );
	}

	protected function empty_response( $directory_data ) {
		$response = array(
			'users'       => array(),
			'total_pages' => 0,
			'pagination'  => '',
		);

		return apply_filters( 'um_ajax_get_members_response', $response, $directory_data );
	}

	protected function no_access_response( $directory_data ) {
		// Predefined result for user without capabilities to see other members
		if ( is_user_logged_in() && ! UM()->roles()->um_user_can( 'can_view_all' ) ) {
			wp_send_json_success( $this->empty_response( $directory_data ) );
		}
	}

	/**
	 * Handle members can view restrictions.
	 */
	protected function restriction_options() {
		$this->hide_not_approved();
		$this->hide_by_role();
		$this->hide_by_account_settings();

		do_action( 'um_member_directory_restrictions_handle_extend' );
	}

	/**
	 *
	 */
	private function hide_not_approved() {
		if ( UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
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
	private function hide_by_role() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$roles = um_user( 'can_view_roles' );
		$roles = maybe_unserialize( $roles );

		if ( empty( $roles ) && UM()->roles()->um_user_can( 'can_view_all' ) ) {
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
	private function hide_by_account_settings() {
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
	private function general_options( $directory_data ) {
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
	private function show_selected_roles( $directory_data ) {
		// add roles to appear in directory
		if ( empty( $directory_data['roles'] ) ) {
			return;
		}

		// Since WP4.4 use 'role__in' argument
		if ( ! empty( $this->query_args['role__in'] ) ) {
			$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );
			$this->query_args['role__in'] = array_intersect( $this->query_args['role__in'], maybe_unserialize( $directory_data['roles'] ) );
		} else {
			$this->query_args['role__in'] = maybe_unserialize( $directory_data['roles'] );
		}
	}

	/**
	 * Handle "Only show members who have uploaded a profile photo" option
	 *
	 * @param array $directory_data
	 */
	private function show_only_with_avatar( $directory_data ) {
		if ( empty( $directory_data['has_profile_photo'] ) ) {
			return;
		}

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

	/**
	 * Handle "Only show members who have uploaded a cover photo" option
	 *
	 * @param array $directory_data
	 */
	private function show_only_with_cover( $directory_data ) {
		if ( empty( $directory_data['has_cover_photo'] ) ) {
			return;
		}

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

	/**
	 * Handle "Only show specific users (Enter one username per line)" option
	 *
	 * @param array $directory_data
	 */
	private function show_only_these_users( $directory_data ) {
		if ( empty( $directory_data['show_these_users'] ) ) {
			return;
		}

		$show_these_users = maybe_unserialize( $directory_data['show_these_users'] );
		if ( is_array( $show_these_users ) && ! empty( $show_these_users ) ) {
			$users_array = array();
			foreach ( $show_these_users as $username ) {
				$exists_id = username_exists( $username );
				if ( false !== $exists_id ) {
					$users_array[] = $exists_id;
				}
			}

			if ( ! empty( $users_array ) ) {
				if ( ! empty( $this->query_args['include'] ) ) {
					$this->query_args['include'] = is_array( $this->query_args['include'] ) ? $this->query_args['include'] : array( $this->query_args['include'] );
					$this->query_args['include'] = array_intersect( $this->query_args['include'], $users_array );
				} else {
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
	private function exclude_these_users( $directory_data ) {
		if ( empty( $directory_data['exclude_these_users'] ) ) {
			return;
		}

		$exclude_these_users = maybe_unserialize( $directory_data['exclude_these_users'] );
		if ( is_array( $exclude_these_users ) && ! empty( $exclude_these_users ) ) {
			$users_array = array();
			foreach ( $exclude_these_users as $username ) {
				$exists_id = username_exists( $username );
				if ( false !== $exists_id ) {
					$users_array[] = $exists_id;
				}
			}

			if ( ! empty( $users_array ) ) {
				if ( ! empty( $this->query_args['exclude'] ) ) {
					$this->query_args['exclude'] = is_array( $this->query_args['exclude'] ) ? $this->query_args['exclude'] : array( $this->query_args['exclude'] );
					$this->query_args['exclude'] = array_intersect( $this->query_args['exclude'], $users_array );
				} else {
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
	private function pagination_options( $directory_data ) {
		// number of profiles for mobile
		$profiles_per_page = $directory_data['profiles_per_page'];
		if ( isset( $directory_data['profiles_per_page_mobile'] ) && UM()->mobile()->isMobile() ) {
			$profiles_per_page = $directory_data['profiles_per_page_mobile'];
		}

		$this->query_args['number'] = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;

		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		$this->query_args['paged'] = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
	}

	/**
	 * Add sorting attributes for \WP_Users_Query
	 *
	 * @param array $directory_data Member Directory options
	 */
	public function sorting_query( $directory_data ) {
		// sort members by
		$this->query_args['order'] = 'ASC';

		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		$sortby = ! empty( $_POST['sorting'] ) ? sanitize_text_field( $_POST['sorting'] ) : $directory_data['sortby'];
		$sortby = ( 'other' === $sortby ) ? $directory_data['sortby_custom'] : $sortby;

		$custom_sort = array();
		if ( ! empty( $directory_data['sorting_fields'] ) ) {
			$sorting_fields = maybe_unserialize( $directory_data['sorting_fields'] );
			foreach ( $sorting_fields as $field ) {
				if ( is_array( $field ) ) {
					$field_keys    = array_keys( $field );
					$custom_sort[] = $field_keys[0];
				}
			}
		}

		$numeric_sorting_keys = array();

		if ( ! empty( UM()->builtin()->saved_fields ) ) {
			foreach ( UM()->builtin()->saved_fields as $key => $data ) {
				if ( '_um_last_login' === $key ) {
					continue;
				}

				if ( isset( $data['type'] ) && 'number' === $data['type'] ) {
					if ( array_key_exists( $key . '_desc', $this->sort_fields ) ) {
						$numeric_sorting_keys[] = $key . '_desc';
					}
					if ( array_key_exists( $key . '_asc', $this->sort_fields ) ) {
						$numeric_sorting_keys[] = $key . '_asc';
					}
				}
			}
		}

		if ( 'username' === $sortby ) {
			$this->query_args['orderby'] = 'user_login';
			$this->query_args['order']   = 'ASC';
		} elseif ( 'display_name' === $sortby ) {
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
			$this->query_args['orderby'] = array( 'um_last_login' => 'DESC' );
			// Please use custom meta table for better results and sorting. Here we only hide the users without visible last login date.
			$this->query_args['meta_query'][] = array(
				'relation'      => 'OR',
				array(
					'key'     => '_um_last_login',
					'compare' => 'EXISTS',
					'type'    => 'DATETIME',
				),
				'um_last_login' => array(
					'key'     => '_um_last_login',
					'compare' => 'NOT EXISTS',
					'type'    => 'DATETIME',
				),
			);
			unset( $this->query_args['order'] );

			add_filter( 'pre_user_query', array( &$this, 'sortby_last_login' ) );
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

			$this->query_args['orderby'] = array(
				'last_name_c'  => 'ASC',
				'first_name_c' => 'ASC',
			);
			unset( $this->query_args['order'] );

		} elseif ( count( $numeric_sorting_keys ) && in_array( $sortby, $numeric_sorting_keys, true ) ) {

			$order = 'DESC';
			// Use `str_ends_with()` since min PHP8.0.
			if ( 0 === strpos( strrev( $sortby ), strrev( '_desc' ) ) ) {
				$sortby = str_replace( '_desc', '', $sortby );
			}
			// Use `str_ends_with()` since min PHP8.0.
			if ( 0 === strpos( strrev( $sortby ), strrev( '_asc' ) ) ) {
				$sortby = str_replace( '_asc', '', $sortby );
				$order  = 'ASC';
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

			$this->query_args['orderby'] = array(
				$sortby . '_ns'   => $order,
				'user_registered' => 'DESC',
			);
			unset( $this->query_args['order'] );

		} elseif ( ( ! empty( $directory_data['sortby_custom'] ) && $sortby === $directory_data['sortby_custom'] ) || in_array( $sortby, $custom_sort, true ) ) {
			$custom_sort_order = ! empty( $directory_data['sortby_custom_order'] ) ? $directory_data['sortby_custom_order'] : 'ASC';

			$meta_query       = new \WP_Meta_Query();
			$custom_sort_type = ! empty( $directory_data['sortby_custom_type'] ) ? $meta_query->get_cast_for_type( $directory_data['sortby_custom_type'] ) : 'CHAR';

			if ( ! empty( $directory_data['sorting_fields'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
				$sorting        = sanitize_text_field( $_POST['sorting'] );
				$sorting_fields = maybe_unserialize( $directory_data['sorting_fields'] );

				if ( ! empty( $sorting_fields ) && is_array( $sorting_fields ) ) {
					foreach ( $sorting_fields as $field ) {
						if ( isset( $field[ $sorting ] ) ) {
							$custom_sort_type  = ! empty( $field['type'] ) ? $meta_query->get_cast_for_type( $field['type'] ) : 'CHAR';
							$custom_sort_order = $field['order'];
						}
					}
				}
			}
			/**
			 * Filters the sorting MySQL type in member directory custom sorting query.
			 *
			 * Note: Possible MySQL types are BINARY|CHAR|DATE|DATETIME|SIGNED|UNSIGNED|TIME|DECIMAL
			 *
			 * @since 2.1.3
			 * @hook um_member_directory_custom_sorting_type
			 *
			 * @param {string} $custom_sort_type MySQL type to cast meta_value. 'CHAR' is default.
			 * @param {string} $sortby           meta_key used for sorting.
			 * @param {array}  $directory_data   Member directory data.
			 *
			 * @return {string} MySQL type to cast meta_value.
			 * @example <caption>Change type to DATE by the directory ID and mete_key.</caption>
			 * function my_um_member_directory_custom_sorting_type( $custom_sort_type, $sortby, $directory_data ) {
			 *     if ( '{selected member directory ID}' == $directory_data['form_id'] && '{custom_date_key}' === $sortby ) {
			 *         $custom_sort_type = 'DATE';
			 *     }
			 *
			 *     return $custom_sort_type;
			 * }
			 * add_filter( 'um_member_directory_custom_sorting_type', 'my_um_member_directory_custom_sorting_type', 10, 3 );
			 */
			$custom_sort_type = apply_filters( 'um_member_directory_custom_sorting_type', $custom_sort_type, $sortby, $directory_data );

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
				),
			);

			$this->query_args['orderby'] = array(
				$sortby . '_cs' => $custom_sort_order,
				'user_login'    => 'ASC',
			);

		} else {
			// Use `str_ends_with()` since min PHP8.0.
			if ( 0 === strpos( strrev( $sortby ), strrev( '_desc' ) ) ) {
				$sortby = str_replace( '_desc', '', $sortby );
				$order  = 'DESC';
			}
			// Use `str_ends_with()` since min PHP8.0.
			if ( 0 === strpos( strrev( $sortby ), strrev( '_asc' ) ) ) {
				$sortby = str_replace( '_asc', '', $sortby );
				$order  = 'ASC';
			}

			$this->query_args['orderby'] = $sortby;
			if ( isset( $order ) ) {
				$this->query_args['order'] = $order;
			}

			add_filter( 'pre_user_query', array( &$this, 'sortby_randomly' ) );
		}

		/**
		 * Filters query sort by attributes for search at Members Directory.
		 *
		 * @since 1.3.x
		 * @hook um_modify_sortby_parameter
		 *
		 * @param {array}  $query_args WP_Query Arguments.
		 * @param {string} $sortby     meta_key used for sorting.
		 *
		 * @return {string} WP_Query Arguments.
		 * @example <caption>Change sorting query attributes.</caption>
		 * function my_modify_sortby_parameter( $query_args, $sortby ) {
		 *     if ( '{my_custom_sorting_key}' === $sortby ) {
		 *         $query_args['orderby'] = '{my_custom_sorting_key}';
		 *         $query_args['order']   = 'DESC';
		 *     }
		 *     return $query_args;
		 * }
		 * add_filter( 'um_modify_sortby_parameter', 'my_modify_sortby_parameter', 10, 2 );
		 */
		$this->query_args = apply_filters( 'um_modify_sortby_parameter', $this->query_args, $sortby );
	}

	/**
	 * Sorting random.
	 *
	 * @param WP_User_Query $query
	 *
	 * @return WP_User_Query
	 */
	public function sortby_randomly( $query ) {
		if ( 'random' === $query->query_vars['orderby'] ) {

			if ( um_is_session_started() === false ) {
				@session_start();
			}

			// Reset seed on load of initial.

			// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
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
				$seed = wp_rand();

				$_SESSION['um_member_directory_seed'] = $seed;
			}

			$query->query_orderby = 'ORDER by RAND(' . $seed . ')';
		}

		return $query;
	}

	/**
	 * Sorting by last login.
	 *
	 * @param WP_User_Query $query
	 *
	 * @return WP_User_Query
	 */
	public function sortby_last_login( $query ) {
		if ( array_key_exists( 'um_last_login', $query->query_vars['orderby'] ) ) {
			global $wpdb;
			$query->query_from   .= " LEFT JOIN {$wpdb->prefix}usermeta AS umm_sort ON ( umm_sort.user_id = {$wpdb->prefix}users.ID AND umm_sort.meta_key = '_um_last_login' ) ";
			$query->query_from   .= " LEFT JOIN {$wpdb->prefix}usermeta AS umm_show_login ON ( umm_show_login.user_id = {$wpdb->prefix}users.ID AND umm_show_login.meta_key = 'um_show_last_login' ) ";
			$query->query_orderby = " ORDER BY CASE ISNULL(NULLIF(umm_show_login.meta_value,'a:1:{i:0;s:3:\"yes\";}')) WHEN 0 THEN '1970-01-01 00:00:00' ELSE CAST( umm_sort.meta_value AS DATETIME ) END DESC ";
		}
		return $query;
	}

	/**
	 * Prepare the search line. Avoid the using mySQL statement.
	 *
	 * @param string $search
	 *
	 * @return string
	 */
	protected function prepare_search( $search ) {
		// unslash, sanitize, trim - necessary prepare.
		$search = trim( sanitize_text_field( wp_unslash( $search ) ) );
		if ( empty( $search ) ) {
			return '';
		}

		// Make the search line empty if it contains the mySQL query statements.
		$regexp_map = array(
			'/select(.*?)from/im',
			'/update(.*?)set/im',
			'/delete(.*?)from/im',
		);

		foreach ( $regexp_map as $regexp ) {
			preg_match( $regexp, $search, $matches );
			if ( ! empty( $matches ) ) {
				$search = '';
				break;
			}
		}

		return $search;
	}

	/**
	 * Handle general search line request.
	 */
	public function general_search() {
		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		if ( empty( $_POST['search'] ) ) {
			return;
		}

		// Complex using with change_meta_sql function.
		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		$search = $this->prepare_search( $_POST['search'] );
		if ( empty( $search ) ) {
			return;
		}

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
			array( // @todo maybe unnecessary because LIKE above checks.
				'value'   => maybe_serialize( $search ), // already sanitized string here.
				'compare' => 'LIKE',
			),
		);

		/**
		 * Filters general search query in Members Directory.
		 *
		 * @param {array}  $meta_query WP_Query Meta query.
		 * @param {string} $search     Search line.
		 *
		 * @return {string} WP_Query Meta for general search in Member Directory.
		 * @since 2.1.0
		 * @hook um_member_directory_general_search_meta_query
		 *
		 * @example <caption>Change searching query attributes.</caption>
		 * function my_member_directory_general_search_meta_query( $query_args, $search ) {
		 *     $query_args[] = array(
		 *         'value'   => $search,
		 *         'compare' => 'NOT LIKE',
		 *     );
		 *     return $query_args;
		 * }
		 * add_filter( 'um_member_directory_general_search_meta_query', 'my_member_directory_general_search_meta_query', 10, 2 );
		 */
		$meta_query = apply_filters( 'um_member_directory_general_search_meta_query', $meta_query, $search );

		$this->query_args['meta_query'][] = $meta_query;
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
	 * @param WP_User_Query $context
	 *
	 * @return array
	 * @throws Exception
	 */
	public function change_meta_sql( $sql, $queries, $type, $primary_table, $primary_id_column, $context ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		if ( empty( $_POST['search'] ) ) {
			return $sql;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		$search = $this->prepare_search( $_POST['search'] );
		if ( empty( $search ) ) {
			return $sql;
		}

		global $wpdb;

		$meta_value  = '%' . $wpdb->esc_like( $search ) . '%';
		$search_meta = $wpdb->prepare( '%s', $meta_value );

		preg_match( '~(?<=\{)(.*?)(?=\})~', $search_meta, $matches, PREG_OFFSET_CAPTURE, 0 );

		// workaround for standard mySQL hashes which are used by $wpdb->prepare instead of the %symbol
		// sometimes it breaks error for strings like that wp_postmeta.meta_value LIKE '{12f209b48a89eeab33424902879d05d503f251ca8812dde03b59484a2991dc74}AMS{12f209b48a89eeab33424902879d05d503f251ca8812dde03b59484a2991dc74}'
		// {12f209b48a89eeab33424902879d05d503f251ca8812dde03b59484a2991dc74} isn't applied by the `preg_replace()` below
		if ( $matches[0][0] ) {
			$search_meta  = str_replace( '{' . $matches[0][0] . '}', '#%&', $search_meta );
			$sql['where'] = str_replace( '{' . $matches[0][0] . '}', '#%&', $sql['where'] );
		}

		// str_replace( '/', '\/', wp_slash( $search_meta ) ) means that we add backslashes to special symbols + add backslash to slash(/) symbol for proper regular pattern.
		preg_match(
			'/^(.*).meta_value LIKE ' . str_replace( '/', '\/', wp_slash( $search_meta ) ) . '[^\)]/im',
			$sql['where'],
			$join_matches
		);

		$sql['where'] = str_replace( '#%&', '{' . $matches[0][0] . '}', $sql['where'] );

		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		$directory_id   = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );
		$exclude_fields = get_post_meta( $directory_id, '_um_search_exclude_fields', true );
		$include_fields = get_post_meta( $directory_id, '_um_search_include_fields', true );

		$meta_join_for_search = '';
		if ( isset( $join_matches[1] ) ) {
			$meta_join_for_search = trim( $join_matches[1] );

			// skip private invisible fields
			$custom_fields = array();
			if ( empty( $include_fields ) ) {
				foreach ( array_keys( UM()->builtin()->all_user_fields ) as $field_key ) {
					if ( empty( $field_key ) ) {
						continue;
					}

					$data = UM()->fields()->get_field( $field_key );
					if ( ! um_can_view_field( $data ) ) {
						continue;
					}

					$custom_fields[] = $field_key;
				}
			} else {
				foreach ( $include_fields as $field_key ) {
					if ( empty( $field_key ) ) {
						continue;
					}

					$data = UM()->fields()->get_field( $field_key );
					if ( ! um_can_view_field( $data ) ) {
						continue;
					}

					$custom_fields[] = $field_key;
				}
			}

			$custom_fields = apply_filters( 'um_general_search_custom_fields', $custom_fields );

			if ( ! empty( $custom_fields ) ) {
				if ( ! empty( $exclude_fields ) ) {
					$custom_fields = array_diff( $custom_fields, $exclude_fields );
				}

				$sql['join'] = preg_replace(
					'/(' . $meta_join_for_search . ' ON \( ' . $wpdb->users . '\.ID = ' . $meta_join_for_search . '\.user_id )(\))/im',
					'$1 AND ' . $meta_join_for_search . ".meta_key IN( '" . implode( "','", $custom_fields ) . "' ) $2",
					$sql['join']
				);
			}
		}

		$core_search = $this->get_core_search_fields();
		if ( ! empty( $include_fields ) ) {
			$core_search = array_intersect( $core_search, $include_fields );
		}
		if ( ! empty( $exclude_fields ) ) {
			$core_search = array_diff( $core_search, $exclude_fields );
		}

		if ( ! empty( $core_search ) ) {
			// Add OR instead AND to search in WP core fields user_email, user_login, user_display_name
			$search_where = $context->get_search_sql( $search, $core_search, 'both' );

			$search_where = preg_replace( '/ AND \((.*?)\)/im', '$1 OR', $search_where );

			// str_replace( '/', '\/', wp_slash( $search ) ) means that we add backslashes to special symbols + add backslash to slash(/) symbol for proper regular pattern.
			$sql['where'] = preg_replace(
				'/(' . $meta_join_for_search . '.meta_value = \'' . str_replace( '/', '\/', wp_slash( $search ) ) . '\')/im',
				trim( $search_where ) . ' $1',
				$sql['where'],
				1
			);
		}

		return $sql;
	}

	/**
	 * Handle filters request.
	 *
	 * @param array $directory_data
	 *
	 * @throws Exception
	 */
	public function filters( $directory_data ) {
		global $wpdb;

		$filter_query = array();
		if ( ! empty( $directory_data['search_fields'] ) ) {
			$search_filters = maybe_unserialize( $directory_data['search_fields'] );

			if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
				$filter_query = array_intersect_key( $_POST, array_flip( $search_filters ) );
			}
		}

		// added for user tags extension integration on individual tag page
		$ignore_empty_filters = apply_filters( 'um_member_directory_ignore_empty_filters', false );

		if ( empty( $filter_query ) && ! $ignore_empty_filters ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
		$offset = ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) ? (int) $_POST['gmt_offset'] : 0;

		foreach ( $filter_query as $field => $value ) {
			$field = sanitize_text_field( $field );
			$attrs = UM()->fields()->get_field( $field );
			// Skip private invisible fields
			if ( ! um_can_view_field( $attrs ) ) {
				continue;
			}

			// includes trim, but not includes `wp_unslash()`
			if ( is_array( $value ) ) {
				$value = array_map( 'sanitize_text_field', $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			/** This filter is documented in includes/core/class-member-directory-meta.php */
			$relation = apply_filters( 'um_members_directory_select_filter_relation', 'OR', $field );

			switch ( $field ) {
				default:
					$filter_type = $this->filter_types[ $field ];

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_query_args_{$field}__filter
					 * @description Change field's query for search at Members Directory
					 * @input_vars
					 * [{"var":"$field_query","type":"array","desc":"Field query"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_query_args_{$field}__filter', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_query_args_{$field}__filter', 'my_query_args_filter', 10, 1 );
					 * function my_query_args_filter( $field_query ) {
					 *     // your code here
					 *     return $field_query;
					 * }
					 * ?>
					 */
					$field_query = apply_filters( "um_query_args_{$field}__filter", false, $field, $value, $filter_type );

					$field_query = apply_filters( 'um_query_args_filter_global', $field_query, $field, $value, $filter_type );

					if ( ! $field_query ) {
						switch ( $filter_type ) {
							default:
								$field_query = apply_filters( "um_query_args_{$field}_{$filter_type}__filter", false, $field, $value, $filter_type );
								break;

							case 'text':
								$value       = wp_unslash( $value );
								$compare     = apply_filters( 'um_members_directory_filter_text', 'LIKE', $field );
								$field_query = array(
									'relation' => 'OR',
									array(
										'key'     => $field,
										'value'   => $value,
										'compare' => $compare,
									),
								);

								$this->custom_filters_in_query[ $field ] = $value;
								break;

							case 'select':
								if ( is_array( $value ) ) {
									$field_query = array();
									foreach ( $value as $single_val ) {
										$single_val = wp_unslash( $single_val );

										$arr_meta_query = array(
											array(
												'key'     => $field,
												'value'   => $single_val,
												'compare' => '=',
											),
											array(
												'key'     => $field,
												'value'   => maybe_serialize( (string) $single_val ),
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
												'value'   => maybe_serialize( absint( $single_val ) ),
												'compare' => 'LIKE',
											);
										}

										$field_query[] = $arr_meta_query;
									}

									$field_query             = array_merge( ...$field_query );
									$field_query['relation'] = esc_sql( $relation );
								}

								$this->custom_filters_in_query[ $field ] = $value;
								break;

							case 'slider':
								$field_query = array(
									'key'       => $field,
									'value'     => $value,
									'compare'   => 'BETWEEN',
									'inclusive' => true,
									'type'      => 'NUMERIC',
								);

								$this->custom_filters_in_query[ $field ] = $value;
								break;

							case 'datepicker':
								$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
								$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
								$from_date = date( 'Y/m/d', $from_date );
								$to_date   = date( 'Y/m/d', $to_date );

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

					if ( 'OR' !== $relation ) {
						$role__in_clauses = array( 'relation' => $relation );
						foreach ( $value as $role ) {
							$role__in_clauses[] = array(
								'key'     => $wpdb->get_blog_prefix() . 'capabilities',
								'value'   => '"' . $role . '"',
								'compare' => 'LIKE',
							);
						}

						$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $role__in_clauses ) );

						$this->custom_filters_in_query[ $field ] = $value;
					} else {
						if ( ! empty( $this->query_args['role__in'] ) ) {
							$this->query_args['role__in'] = is_array( $this->query_args['role__in'] ) ? $this->query_args['role__in'] : array( $this->query_args['role__in'] );

							$default_role = array_intersect( $this->query_args['role__in'], $value );
							$um_role      = array_diff( $value, $default_role );

							foreach ( $um_role as $key => &$val ) {
								$val = 'um_' . str_replace( ' ', '-', $val );
							}
							unset( $val );

							$this->query_args['role__in'] = array_merge( $default_role, $um_role );
						} else {
							$this->query_args['role__in'] = $value;
						}

						$this->custom_filters_in_query[ $field ] = $this->query_args['role__in'];
					}
					break;

				case 'birth_date':
					$from_date = wp_date( 'Y/m/d', mktime( 0, 0, 0, wp_date( 'm' ), wp_date( 'd' ), wp_date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
					$to_date   = wp_date( 'Y/m/d', mktime( 0, 0, 0, wp_date( 'm' ), wp_date( 'j' ) + 1, wp_date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

					$meta_query = array(
						array(
							'key'       => 'birth_date',
							'value'     => array( $to_date, $from_date ),
							'compare'   => 'BETWEEN',
							'type'      => 'DATE',
							'inclusive' => true,
						),
					);

					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

					$this->custom_filters_in_query[ $field ] = array( $to_date, $from_date );
					break;

				case 'user_registered':
					$from_date = gmdate( 'Y-m-d H:i:s', strtotime( $value[0] ) + ( $offset * HOUR_IN_SECONDS ) );
					$to_date   = gmdate( 'Y-m-d H:i:s', strtotime( $value[1] ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1 ); // time 23:59

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
					$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
					$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59

					$meta_query = array(
						'relation' => 'AND',
						array(
							'key'       => '_um_last_login',
							'value'     => array( gmdate( 'Y-m-d H:i:s', $from_date ), gmdate( 'Y-m-d H:i:s', $to_date ) ),
							'compare'   => 'BETWEEN',
							'inclusive' => true,
							'type'      => 'DATETIME',
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => 'um_show_last_login',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'um_show_last_login',
								'value'   => 'a:1:{i:0;s:2:"no";}',
								'compare' => '!=',
							),
						),
					);

					$this->query_args['meta_query']          = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
					$this->custom_filters_in_query[ $field ] = $value;
					break;

				case 'gender':
					if ( is_array( $value ) ) {
						$field_query = array();

						foreach ( $value as $single_val ) {
							$single_val = wp_unslash( $single_val );

							$field_query[] = array(
								array(
									'key'     => $field,
									'value'   => $single_val,
									'compare' => '=',
								),
								array(
									'key'     => $field,
									'value'   => '"' . $single_val . '"',
									'compare' => 'LIKE',
								),
							);
						}

						$field_query = array_merge( ...$field_query );

						$field_query['relation'] = $relation;
					}

					if ( ! empty( $field_query ) ) {
						$this->query_args['meta_query']          = array_merge( $this->query_args['meta_query'], array( $field_query ) );
						$this->custom_filters_in_query[ $field ] = $value;
					}
					break;
			}
		}
	}

	/**
	 * Set default filters
	 *
	 * @param $directory_data
	 */
	public function default_filters( $directory_data ) {
		$default_filters = array();
		if ( ! empty( $directory_data['search_filters'] ) ) {
			$default_filters = maybe_unserialize( $directory_data['search_filters'] );
		}

		$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );

		if ( empty( $default_filters ) ) {
			return;
		}

		foreach ( $default_filters as $field => $value ) {

			switch ( $field ) {
				default:
					$filter_type = $this->filter_types[ $field ];

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_query_args_{$field}__filter
					 * @description Change field's query for search at Members Directory
					 * @input_vars
					 * [{"var":"$field_query","type":"array","desc":"Field query"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_query_args_{$field}__filter', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_query_args_{$field}__filter', 'my_query_args_filter', 10, 1 );
					 * function my_query_args_filter( $field_query ) {
					 *     // your code here
					 *     return $field_query;
					 * }
					 * ?>
					 */
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

								/** This filter is documented in includes/core/class-member-directory.php */
								$field_query = apply_filters( 'um_members_directory_filter_select', array( 'relation' => 'OR' ), $field );

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
											'value'   => serialize( absint( $single_val ) ),
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
									'type'      => 'NUMERIC',
									'inclusive' => true,
								);
								break;

							case 'datepicker':
								$offset = 0;
								if ( is_numeric( $gmt_offset ) ) {
									$offset = $gmt_offset;
								}

								if ( ! empty( $value[0] ) ) {
									$min = $value[0];
								} else {
									$range = $this->datepicker_filters_range( $field );
									$min   = strtotime( gmdate( 'Y/m/d', $range[0] ) );
								}
								if ( ! empty( $value[1] ) ) {
									$max = $value[1];
								} else {
									$max = strtotime( gmdate( 'Y/m/d' ) );
								}

								$from_date = (int) $min + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
								$to_date   = (int) $max + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59

								$field_query = array(
									'key'       => $field,
									'value'     => array( $from_date, $to_date ),
									'compare'   => 'BETWEEN',
									'inclusive' => true,
								);

								break;
							case 'timepicker':
								if ( ! empty( $value[0] ) ) {
									$value[0] = $value[0] . ':00';
								} else {
									$range    = $this->timepicker_filters_range( $field );
									$value[0] = $range[0] . ':00';
								}
								if ( ! empty( $value[1] ) ) {
									$value[1] = $value[1] . ':00';
								} else {
									$range    = $this->timepicker_filters_range( $field );
									$value[1] = $range[1] . ':00';
								}

								if ( $value[0] === $value[1] ) {
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
					}

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
						),
					);

					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );

					break;
				case 'user_registered':
					$offset = 0;
					if ( is_numeric( $gmt_offset ) ) {
						$offset = $gmt_offset;
					}

					$from_date = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s', min( $value ) ) . "+$offset hours" ) );
					$to_date   = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d H:i:s', max( $value ) ) . "+$offset hours" ) );

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

					$value = array_map(
						function( $date ) {
							return is_numeric( $date ) ? $date : strtotime( $date );
						},
						$value
					);

					if ( ! empty( $value[0] ) ) {
						$min = $value[0];
					} else {
						$range = $this->datepicker_filters_range( 'last_login' );
						$min   = strtotime( gmdate( 'Y/m/d', $range[0] ) );
					}
					if ( ! empty( $value[1] ) ) {
						$max = $value[1];
					} else {
						$max = strtotime( gmdate( 'Y/m/d' ) );
					}

					$from_date = gmdate( 'Y-m-d H:i:s', (int) $min + ( $offset * HOUR_IN_SECONDS ) ); // client time zone offset
					$to_date   = gmdate( 'Y-m-d H:i:s', (int) $max + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1 ); // time 23:59

					$meta_query = array(
						'relation' => 'AND',
						array(
							'key'       => '_um_last_login',
							'value'     => array( $from_date, $to_date ),
							'compare'   => 'BETWEEN',
							'inclusive' => true,
							'type'      => 'DATETIME',
						),
						array(
							'relation' => 'OR',
							array(
								'key'     => 'um_show_last_login',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'um_show_last_login',
								'value'   => 'a:1:{i:0;s:2:"no";}',
								'compare' => '!=',
							),
						),
					);

					$this->query_args['meta_query'] = array_merge( $this->query_args['meta_query'], array( $meta_query ) );
					break;
			}
		}
	}

	/**
	 * Update limit query
	 *
	 * @param $user_query
	 */
	public function pagination_changes( $user_query ) {
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
	 * Main Query function for getting members via AJAX
	 *
	 * @throws Exception
	 */
	public function ajax_get_members() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'um_member_directory' ) ) {
			wp_send_json_error( __( 'Wrong nonce.', 'ultimate-member' ) );
		}

		global $wpdb;

		if ( empty( $_POST['directory_id'] ) ) {
			wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
		}

		$directory_id = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );

		if ( empty( $directory_id ) ) {
			wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
		}

		$directory_data = UM()->query()->post_data( $directory_id );

		if ( array_key_exists( 'tagline_fields', $directory_data ) ) {
			$directory_data['tagline_fields'] = maybe_unserialize( $directory_data['tagline_fields'] );
		} else {
			$directory_data['tagline_fields'] = array();
		}
		if ( array_key_exists( 'reveal_fields', $directory_data ) ) {
			$directory_data['reveal_fields'] = maybe_unserialize( $directory_data['reveal_fields'] );
		} else {
			$directory_data['reveal_fields'] = array();
		}
		if ( array_key_exists( 'view_types', $directory_data ) ) {
			$directory_data['view_types'] = maybe_unserialize( $directory_data['view_types'] );
		} else {
			$directory_data['view_types'] = array();
		}

		// Predefined result for user without capabilities to see other members
		$this->no_access_response( $directory_data );

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

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_prepare_user_query_args
		 * @description Extend member directory query arguments
		 * @input_vars
		 * [{"var":"$query_args","type":"array","desc":"Members Query Arguments"},
		 * {"var":"$directory_settings","type":"array","desc":"Member Directory Settings"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_prepare_user_query_args', 'function_name', 10, 2 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_prepare_user_query_args', 'my_prepare_user_query_args', 10, 2 );
		 * function my_prepare_user_query_args( $query_args, $directory_settings ) {
		 *     // your code here
		 *     return $query_args;
		 * }
		 * ?>
		 */
		$this->query_args = apply_filters( 'um_prepare_user_query_args', $this->query_args, $directory_data );

		//unset empty meta_query attribute
		if ( isset( $this->query_args['meta_query']['relation'] ) && count( $this->query_args['meta_query'] ) === 1 ) {
			unset( $this->query_args['meta_query'] );
		}

		if ( isset( $this->query_args['role__in'] ) && empty( $this->query_args['role__in'] ) ) {
			wp_send_json_success( $this->empty_response( $directory_data ) );
		}

		/**
		 * Fires just before the users query for getting users in member directory.
		 *
		 * @since 1.3.x
		 * @since 2.1.0 Added `$member_directory_class` variable.
		 * @hook um_user_before_query
		 *
		 * @param {array}  $args                   Query arguments.
		 * @param {object} $member_directory_class Member Directory class. Since 2.1.0 version.
		 *
		 * @example <caption>Add custom arguments for query.</caption>
		 * function my_user_before_query( $query_args, $md_class ) {
		 *     $query_args['{custom_key}'] = 'custom_value';
		 * }
		 * add_action( 'um_user_before_query', 'my_user_before_query', 10, 2 );
		 */
		do_action( 'um_user_before_query', $this->query_args, $this );

		add_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10, 6 );

		add_filter( 'pre_user_query', array( &$this, 'pagination_changes' ), 10, 1 );

		$user_query = new WP_User_Query( $this->query_args );

		remove_filter( 'pre_user_query', array( &$this, 'pagination_changes' ), 10 );

		remove_filter( 'get_meta_sql', array( &$this, 'change_meta_sql' ), 10 );

		/**
		 * Fires just after the users query for getting users in member directory.
		 *
		 * @since 1.3.x
		 * @hook um_user_after_query
		 *
		 * @param {array}  $query_args Query arguments.
		 * @param {object} $user_query Query results.
		 *
		 * @example <caption>Make some custom action after getting the users in member directory.</caption>
		 * function my_user_after_query( $query_args, $user_query ) {
		 *     // your code here
		 * }
		 * add_action( 'um_user_after_query', 'my_user_after_query', 10, 2 );
		 */
		do_action( 'um_user_after_query', $this->query_args, $user_query );

		// Pagination attributes for template.
		$current_page = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		// number of profiles for mobile
		$profiles_per_page = $directory_data['profiles_per_page'];
		if ( isset( $directory_data['profiles_per_page_mobile'] ) && UM()->mobile()->isMobile() ) {
			$profiles_per_page = $directory_data['profiles_per_page_mobile'];
		}
		$profiles_per_page = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;
		$total_users       = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $user_query->total_users ) ? $directory_data['max_users'] : $user_query->total_users;

		$user_ids = ! empty( $user_query->results ) ? array_unique( $user_query->results ) : array();

		/**
		 * Filters the member directory query result.
		 *
		 * @since 2.0
		 * @hook um_prepare_user_results_array
		 *
		 * @param {array} $user_ids   Members Query Result.
		 * @param {array} $query_args Query arguments.
		 *
		 * @return {array} Query result.
		 *
		 * @example <caption>Remove some users where ID equals 10 and 12 from query.</caption>
		 * function my_custom_um_prepare_user_results_array( $user_ids, $query_args ) {
		 *     $user_ids = array_diff( $user_ids, array( 10, 12 ) );
		 *     return $user_ids;
		 * }
		 * add_filter( 'um_prepare_user_results_array', 'my_custom_um_prepare_user_results_array', 10, 2 );
		 */
		$user_ids = apply_filters( 'um_prepare_user_results_array', $user_ids, $this->query_args );

		$this->init_image_sizing( $directory_data );

		$users = array();
		foreach ( $user_ids as $user_id ) {
			$users[] = $this->build_user_card_data( $user_id, $directory_data );
		}

		um_reset_user();
		// end of user card

		$pagination_args = array(
			'page'     => $current_page,
			'total'    => $total_users,
			'per_page' => $profiles_per_page,
		);
		$pagination      = UM()->frontend()::layouts()::pagination( $pagination_args );

		$response = array(
			'pagination'  => UM()->ajax()->esc_html_spaces( $pagination ),
			'total_pages' => ! empty( $pagination_args['per_page'] ) ? absint( ceil( $pagination_args['total'] / $pagination_args['per_page'] ) ) : 1,
			// translators: %d is the count of users
			'counter'     => $pagination_args['total'] > 0 ? esc_html( sprintf( _n( '%d Member', '%d Members', $pagination_args['total'], 'ultimate-member' ), $pagination_args['total'] ) ) : '',
		);

		foreach ( $directory_data['view_types'] as $view_type ) {
			$response[ 'content_' . $view_type ] = UM()->ajax()->esc_html_spaces(
				UM()->get_template(
//					'v3/directory/' . $view_type . '.php',
					'v3/directory/loop.php',
					UM()->member_directory()->get_type_basename( $view_type ),
					array(
						'members'        => $users,
						'view_type'      => $view_type,
						'directory_obj'  => $this,
						'unique_hash'    => substr( md5( $directory_id ), 10, 5 ),
						'directory_data' => $directory_data,
					)
				)
			);
		}

		$response = apply_filters( 'um_ajax_get_members_response', $response, $directory_data );
		wp_send_json_success( $response );
	}
}
