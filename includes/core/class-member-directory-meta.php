<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Member_Directory_Meta' ) ) {

	/**
	 * Class Member_Directory_Meta
	 * @package um\core
	 */
	class Member_Directory_Meta extends Member_Directory {

		/**
		 * @var array
		 */
		public $joins = array();

		/**
		 * @var array
		 */
		public $where_clauses = array();

		/**
		 * @var array
		 */
		public $roles = array();

		/**
		 * @var bool
		 */
		var $roles_in_query = false;

		var $general_meta_joined = false;

		var $having = '';
		var $select = '';
		var $sql_limit = '';
		var $sql_order = '';


		/**
		 * Member_Directory_Meta constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'updated_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
			add_action( 'added_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
			add_action( 'deleted_user_meta', array( &$this, 'on_delete_usermeta' ), 10, 4 );

			add_action( 'um_add_new_field', array( &$this, 'on_new_field_added' ), 10, 2 );
			add_action( 'um_delete_custom_field', array( &$this, 'on_delete_custom_field' ), 10, 2 );
		}


		/**
		 * Delete custom field and metakey from UM usermeta table
		 *
		 * @param $metakey
		 * @param $args
		 */
		function on_delete_custom_field( $metakey, $args ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );

			if ( in_array( $metakey, $metakeys ) ) {
				unset( $metakeys[ array_search( $metakey, $metakeys ) ] );

				global $wpdb;

				$wpdb->delete(
					"{$wpdb->prefix}um_metadata",
					array(
						'um_key'    => $metakey
					),
					array(
						'%s'
					)
				);

				update_option( 'um_usermeta_fields', array_values( $metakeys ) );
			}

			do_action( 'um_metadata_on_delete_custom_field', $metakeys, $metakey, $args );
		}


		/**
		 * Add metakey to usermeta fields
		 *
		 * @param $metakey
		 * @param $args
		 */
		function on_new_field_added( $metakey, $args ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );

			if ( ! in_array( $metakey, $metakeys ) ) {
				$metakeys[] = $metakey;
				update_option( 'um_usermeta_fields', array_values( $metakeys ) );
			}

			do_action( 'um_metadata_on_new_field_added', $metakeys, $metakey, $args );
		}


		/**
		 * When you delete usermeta - remove row from um_metadata
		 *
		 * @param int|array $meta_ids
		 * @param int $object_id
		 * @param string $meta_key
		 * @param mixed $_meta_value
		 */
		function on_delete_usermeta( $meta_ids, $object_id, $meta_key, $_meta_value ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );
			if ( ! in_array( $meta_key, $metakeys ) ) {
				return;
			}

			global $wpdb;

			$wpdb->delete(
				"{$wpdb->prefix}um_metadata",
				array(
					'user_id'   => $object_id,
					'um_key'    => $meta_key
				),
				array(
					'%d',
					'%s'
				)
			);
		}


		/**
		 * When you add/update usermeta - add/update row from um_metadata
		 *
		 * @param int $meta_id
		 * @param int $object_id
		 * @param string $meta_key
		 * @param mixed $_meta_value
		 */
		function on_update_usermeta( $meta_id, $object_id, $meta_key, $_meta_value ) {

			$metakeys = get_option( 'um_usermeta_fields', array() );
			if ( ! in_array( $meta_key, $metakeys ) ) {
				return;
			}

			global $wpdb;

			$result = $wpdb->get_var( $wpdb->prepare(
				"SELECT umeta_id
				FROM {$wpdb->prefix}um_metadata
				WHERE user_id = %d AND
				      um_key = %s
				LIMIT 1",
				$object_id,
				$meta_key
			) );

			if ( empty( $result ) ) {
				$wpdb->insert(
					"{$wpdb->prefix}um_metadata",
					array(
						'user_id'   => $object_id,
						'um_key'    => $meta_key,
						'um_value'  => maybe_serialize( $_meta_value ),
					),
					array(
						'%d',
						'%s',
						'%s',
					)
				);
			} else {
				$wpdb->update(
					"{$wpdb->prefix}um_metadata",
					array(
						'um_value'  => maybe_serialize( $_meta_value ),
					),
					array(
						'umeta_id'  => $result,
					),
					array(
						'%s',
					),
					array(
						'%d',
					)
				);
			}
		}


		/**
		 * @param $directory_data
		 * @param $field
		 * @param $value
		 * @param $i
		 * @param bool $is_default
		 */
		function handle_filter_query( $directory_data, $field, $value, $i, $is_default = false ) {
			global $wpdb;

			$join_slug = $is_default ? 'ummd' : 'umm' ;

			$blog_id = get_current_blog_id();

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
					 * <?php add_filter( 'um_query_args_{$field}__filter_meta', 'function_name', 10, 4 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_query_args_{$field}__filter_meta', 'my_query_args_filter', 10, 4 );
					 * function my_query_args_filter( $field_query ) {
					 *     // your code here
					 *     return $field_query;
					 * }
					 * ?>
					 */
					$skip_default = apply_filters( "um_query_args_{$field}__filter_meta", false, $this, $field, $value, $filter_type, $is_default );

					$skip_default = apply_filters( 'um_query_args_filter_global_meta', $skip_default, $this, $field, $value, $filter_type, $is_default );

					if ( ! $skip_default ) {

						switch ( $filter_type ) {
							default:

								do_action( "um_query_args_{$field}_{$filter_type}__filter_meta", $field, $value, $filter_type, $i, $is_default );
								break;

							case 'text':

								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_slug}{$i} ON {$join_slug}{$i}.user_id = u.ID";

								$value = trim( stripslashes( $value ) );

								$compare = apply_filters( 'um_members_directory_filter_text', '=', $field );
								$value = apply_filters( 'um_members_directory_filter_text_meta_value', $value, $field );

								$this->where_clauses[] = $wpdb->prepare( "{$join_slug}{$i}.um_key = %s AND {$join_slug}{$i}.um_value {$compare} %s", $field, $value );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = $value;
								}

								break;

							case 'select':
								if ( ! is_array( $value ) ) {
									$value = array( $value );
								}

								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_slug}{$i} ON {$join_slug}{$i}.user_id = u.ID";

								$values_array = array();
								foreach ( $value as $single_val ) {
									$single_val = trim( stripslashes( $single_val ) );

									$values_array[] = $wpdb->prepare( "{$join_slug}{$i}.um_value LIKE %s", '%"' . $single_val . '"%' );
									$values_array[] = $wpdb->prepare( "{$join_slug}{$i}.um_value LIKE %s", '%' . serialize( (string) $single_val ) . '%' );
									$values_array[] = $wpdb->prepare( "{$join_slug}{$i}.um_value = %s", $single_val );

									if ( is_numeric( $single_val ) ) {
										$values_array[] = $wpdb->prepare( "{$join_slug}{$i}.um_value LIKE %s", '%' . serialize( (int) $single_val ) . '%' );
									}
								}

								$values = implode( ' OR ', $values_array );

								$this->where_clauses[] = $wpdb->prepare( "( {$join_slug}{$i}.um_key = %s AND ( {$values} ) )", $field );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = $value;
								}

								break;
							case 'slider':

								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_slug}{$i} ON {$join_slug}{$i}.user_id = u.ID";

								$min = min( $value );
								$max = max( $value );

								$this->where_clauses[] = $wpdb->prepare( "( {$join_slug}{$i}.um_key = %s AND {$join_slug}{$i}.um_value BETWEEN %d AND %d )", $field, $min, $max );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = $value;
								}

								break;
							case 'datepicker':

								$offset = 0;
								if ( ! $is_default ) {
									if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
										$offset = (int) $_POST['gmt_offset'];
									}
								} else {
									$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );
									if ( is_numeric( $gmt_offset ) ) {
										$offset = $gmt_offset;
									}
								}

								$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
								$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
								$from_date = date( 'Y/m/d', $from_date );
								$to_date = date( 'Y/m/d', $to_date );

								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_slug}{$i} ON {$join_slug}{$i}.user_id = u.ID";

								$this->where_clauses[] = $wpdb->prepare( "( {$join_slug}{$i}.um_key = %s AND {$join_slug}{$i}.um_value BETWEEN %s AND %s )", $field, $from_date, $to_date );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = array( $from_date, $to_date );
								}

								break;
							case 'timepicker':

								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_slug}{$i} ON {$join_slug}{$i}.user_id = u.ID";
								if ( $value[0] == $value[1] ) {
									$this->where_clauses[] = $wpdb->prepare( "( {$join_slug}{$i}.um_key = %s AND {$join_slug}{$i}.um_value = %s )", $field, $value[0] );
								} else {
									$this->where_clauses[] = $wpdb->prepare( "( {$join_slug}{$i}.um_key = %s AND CAST( {$join_slug}{$i}.um_value AS TIME ) BETWEEN %s AND %s )", $field, $value[0], $value[1] );
								}

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = $value;
								}

								break;
						}

					}

					break;
				case 'role':
					$value = array_map( 'strtolower', $value );

					if ( empty( $this->roles ) && ! is_multisite() ) {
						$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = '" . $wpdb->get_blog_prefix( $blog_id ) . "capabilities' )";
						$this->roles = $value;

						$this->roles_in_query = true;
					}

					$roles_clauses = array();
					foreach ( $value as $role ) {
						$roles_clauses[] = $wpdb->prepare( "umm_roles.um_value LIKE %s", '%"' . $role . '"%' );
					}

					$this->where_clauses[] = '( ' . implode( ' OR ', $roles_clauses ) . ' )';

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = $value;
					}

					break;
				case 'birth_date':

					$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
					$to_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_slug}{$i} ON {$join_slug}{$i}.user_id = u.ID";

					$this->where_clauses[] = $wpdb->prepare( "( {$join_slug}{$i}.um_key = 'birth_date' AND {$join_slug}{$i}.um_value BETWEEN %s AND %s )", $to_date, $from_date );

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = array( $to_date, $from_date );
					}

					break;
				case 'user_registered':

					$offset = 0;
					if ( ! $is_default ) {
						if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
							$offset = (int) $_POST['gmt_offset'];
						}
					} else {
						$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );
						if ( is_numeric( $gmt_offset ) ) {
							$offset = (int) $gmt_offset;
						}
					}

					$from_date = date( 'Y-m-d H:s:i', strtotime( min( $value ) ) + $offset * HOUR_IN_SECONDS ); // client time zone offset
					$to_date = date( 'Y-m-d H:s:i', strtotime( max( $value ) ) + $offset * HOUR_IN_SECONDS + DAY_IN_SECONDS - 1 ); // time 23:59

					$this->where_clauses[] = $wpdb->prepare( "u.user_registered BETWEEN %s AND %s", $from_date, $to_date );

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = $value;
					}

					break;
				case 'last_login':

					$offset = 0;
					if ( ! $is_default ) {
						if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
							$offset = (int) $_POST['gmt_offset'];
						}
					} else {
						$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );
						if ( is_numeric( $gmt_offset ) ) {
							$offset = $gmt_offset;
						}
					}

					$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
					$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59

					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_slug}{$i} ON {$join_slug}{$i}.user_id = u.ID";

					$this->where_clauses[] = $wpdb->prepare( "( {$join_slug}{$i}.um_key = '_um_last_login' AND {$join_slug}{$i}.um_value BETWEEN %s AND %s )", $from_date, $to_date );

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = $value;
					}

					break;
			}
		}


		/**
		 * Main Query function for getting members via AJAX
		 */
		function ajax_get_members() {
			UM()->check_ajax_nonce();

			global $wpdb;

			$blog_id = get_current_blog_id();

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
						$this->where_clauses[] = "u.ID IN ( '" . implode( "','", $users_array ) . "' )";
					}
				}
			}

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
						$this->where_clauses[] = "u.ID NOT IN ( '" . implode( "','", $users_array ) . "' )";
					}
				}
			}

			$profile_photo_where = '';
			if ( $directory_data['has_profile_photo'] == 1 ) {
				$profile_photo_where = " AND umm_general.um_value LIKE '%s:13:\"profile_photo\";b:1;%'";
			}

			$cover_photo_where = '';
			if ( $directory_data['has_cover_photo'] == 1 ) {
				$cover_photo_where = " AND umm_general.um_value LIKE '%s:11:\"cover_photo\";b:1;%'";
			}

			if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				if ( ! $this->general_meta_joined ) {
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_general ON umm_general.user_id = u.ID";
					$this->general_meta_joined = true;
				}
				$this->where_clauses[] = "( umm_general.um_key = 'um_member_directory_data' AND
				umm_general.um_value LIKE '%s:14:\"account_status\";s:8:\"approved\";%' AND umm_general.um_value LIKE '%s:15:\"hide_in_members\";b:0;%'{$profile_photo_where}{$cover_photo_where} )";
			} else {
				if ( ! empty( $cover_photo_where ) || ! empty( $profile_photo_where ) ) {
					if ( ! $this->general_meta_joined ) {
						$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_general ON umm_general.user_id = u.ID";
						$this->general_meta_joined = true;
					}
					$this->where_clauses[] = "( umm_general.um_key = 'um_member_directory_data'{$profile_photo_where}{$cover_photo_where} )";
				}
			}

			//$this->roles = array();
			if ( UM()->roles()->um_user_can( 'can_view_all' ) ) {
				$view_roles = um_user( 'can_view_roles' );

				if ( ! $view_roles ) {
					$view_roles = array();
				} else {
					$this->roles_in_query = true;
				}

				$this->roles = array_merge( $this->roles, maybe_unserialize( $view_roles ) );
			}

			if ( ! empty( $directory_data['roles'] ) ) {
				if ( ! empty( $this->roles ) ) {
					$this->roles = array_intersect( $this->roles, maybe_unserialize( $directory_data['roles'] ) );
				} else {
					$this->roles = array_merge( $this->roles, maybe_unserialize( $directory_data['roles'] ) );
				}

				$this->roles_in_query = true;
			}

			if ( ! empty( $this->roles ) ) {
				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = '" . $wpdb->get_blog_prefix( $blog_id ) . "capabilities' )";

				$roles_clauses = array();
				foreach ( $this->roles as $role ) {
					$roles_clauses[] = $wpdb->prepare( 'umm_roles.um_value LIKE %s', '%"' . $role . '"%' );
				}

				$this->where_clauses[] = '( ' . implode( ' OR ', $roles_clauses ) . ' )';
			} else {

				if ( ! $this->roles_in_query && is_multisite() ) {
					// select users who have capabilities for current blog
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = '" . $wpdb->get_blog_prefix( $blog_id ) . "capabilities' )";
					$this->where_clauses[] = "umm_roles.um_value IS NOT NULL";
				} elseif ( $this->roles_in_query ) {
					$member_directory_response = apply_filters( 'um_ajax_get_members_response', array(
						'pagination'    => $this->calculate_pagination( $directory_data, 0 ),
						'users'         => array(),
						'is_search'     => $this->is_search,
					), $directory_data );

					wp_send_json_success( $member_directory_response );
				}
			}

			if ( ! empty( $_POST['search'] ) ) {
				$search_line = $this->prepare_search( $_POST['search'] );
				if ( ! empty( $search_line ) ) {
					$searches = array();
					foreach ( $this->core_search_fields as $field ) {
						$searches[] = $wpdb->prepare( "u.{$field} LIKE %s", '%' . $search_line . '%' );
					}

					$core_search = implode( ' OR ', $searches );

					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_search ON umm_search.user_id = u.ID";

					$additional_search = apply_filters( 'um_member_directory_meta_general_search_meta_query', '',$search_line );

					$search_like_string = apply_filters( 'um_member_directory_meta_search_like_type', '%' . $search_line . '%', $search_line );

					$this->where_clauses[] = $wpdb->prepare( "( umm_search.um_value = %s OR umm_search.um_value LIKE %s OR umm_search.um_value LIKE %s OR {$core_search}{$additional_search})", $search_line, $search_like_string, '%' . serialize( (string) $search_line ) . '%' );

					$this->is_search = true;
				}
			}

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

			if ( ! empty( $filter_query ) || $ignore_empty_filters ) {
				$this->is_search = true;

				$i = 1;
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

					$this->handle_filter_query( $directory_data, $field, $value, $i );

					$i++;
				}
			}

			//unable default filter in case if we select other filters in frontend filters
			//if ( empty( $this->custom_filters_in_query ) ) {
			$default_filters = array();
			if ( ! empty( $directory_data['search_filters'] ) ) {
				$default_filters = maybe_unserialize( $directory_data['search_filters'] );
			}

			if ( ! empty( $default_filters ) ) {
				$i = 1;
				foreach ( $default_filters as $field => $value ) {

					$this->handle_filter_query( $directory_data, $field, $value, $i, true );

					$i++;
				}
			}
			//}

			$order = 'ASC';
			$sortby = ! empty( $_POST['sorting'] ) ? sanitize_text_field( $_POST['sorting'] ) : $directory_data['sortby'];
			$sortby = ( $sortby == 'other' ) ? $directory_data['sortby_custom'] : $sortby;

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

			// handle sorting options
			// sort members by
			if ( $sortby == $directory_data['sortby_custom'] || in_array( $sortby, $custom_sort ) ) {
				$custom_sort_order = ! empty( $directory_data['sortby_custom_order'] ) ? $directory_data['sortby_custom_order'] : 'ASC';

				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = '{$sortby}' )";

				$meta_query       = new \WP_Meta_Query();
				$custom_sort_type = ! empty( $directory_data['sortby_custom_type'] ) ? $meta_query->get_cast_for_type( $directory_data['sortby_custom_type'] ) : 'CHAR';
				if ( ! empty( $directory_data['sorting_fields'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification -- already verified here
					$sorting        = sanitize_text_field( $_POST['sorting'] );
					$sorting_fields = maybe_unserialize( $directory_data['sorting_fields'] );

					foreach ( $sorting_fields as $field ) {
						if ( isset( $field[ $sorting ] ) ) {
							$custom_sort_type  = ! empty( $field['type'] ) ? $meta_query->get_cast_for_type( $field['type'] ) : 'CHAR';
							$custom_sort_order = $field['order'];
						}
					}
				}

				/** This filter is documented in includes/core/class-member-directory.php */
				$custom_sort_type = apply_filters( 'um_member_directory_custom_sorting_type', $custom_sort_type, $sortby, $directory_data );

				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS {$custom_sort_type} ) {$custom_sort_order} ";

			} elseif ( count( $numeric_sorting_keys ) && in_array( $sortby, $numeric_sorting_keys ) ) {

				if ( strstr( $sortby, '_desc' ) ) {
					$sortby = str_replace( '_desc', '', $sortby );
					$order = 'DESC';
				}

				if ( strstr( $sortby, '_asc' ) ) {
					$sortby = str_replace( '_asc', '', $sortby );
					$order = 'ASC';
				}

				$this->joins[]   = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = '{$sortby}' )";
				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS SIGNED ) {$order}, u.user_registered DESC ";

			} elseif ( 'username' == $sortby ) {

				$this->sql_order = " ORDER BY u.user_login {$order} ";

			} elseif ( 'display_name' == $sortby ) {

				$display_name = UM()->options()->get( 'display_name' );
				if ( $display_name == 'username' ) {

					$this->sql_order = " ORDER BY u.user_login {$order} ";

				} else {

					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = 'full_name' )";

					$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) {$order}, u.display_name {$order} ";

				}

			} elseif ( in_array( $sortby, array( 'last_name', 'first_name', 'nickname' ) ) ) {

				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = '{$sortby}' )";

				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) {$order} ";

			} elseif ( $sortby == 'last_login' ) {

				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = '_um_last_login' )";

				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS SIGNED ) DESC ";

			} elseif ( $sortby == 'last_first_name' ) {

				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = 'last_name' )";
				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort2 ON ( umm_sort2.user_id = u.ID AND umm_sort2.um_key = 'first_name' )";

				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) ASC, CAST( umm_sort2.um_value AS CHAR ) ASC ";

			} elseif ( $sortby == 'random' ) {

				if ( um_is_session_started() === false ) {
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

				$this->sql_order = 'ORDER BY RAND(' . $seed . ')';

			} else {

				if ( strstr( $sortby, '_desc' ) ) {
					$sortby = str_replace( '_desc', '', $sortby );
					$order = 'DESC';
				}

				if ( strstr( $sortby, '_asc' ) ) {
					$sortby = str_replace( '_asc', '', $sortby );
					$order = 'ASC';
				}

				$metakeys = get_option( 'um_usermeta_fields', array() );
				if ( false !== array_search( $sortby, $metakeys ) ) {
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = '{$sortby}' )";
					$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) {$order} ";
				} else {
					$this->sql_order = " ORDER BY u.{$sortby} {$order} ";
				}
			}

			$this->sql_order = apply_filters( 'um_modify_sortby_parameter_meta', $this->sql_order, $sortby );

			$profiles_per_page = $directory_data['profiles_per_page'];
			if ( UM()->mobile()->isMobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $directory_data['profiles_per_page_mobile'];
			}

			$query_number = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;
			$query_paged = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

			$number = $query_number;
			if ( ! empty( $directory_data['max_users'] ) && $query_paged*$query_number > $directory_data['max_users'] ) {
				$number = ( $query_paged*$query_number - ( $query_paged*$query_number - $directory_data['max_users'] ) ) % $query_number;
			}

			// limit
			if ( isset( $query_number ) && $query_number > 0 ) {
				$this->sql_limit .= $wpdb->prepare( 'LIMIT %d, %d', $query_number * ( $query_paged - 1 ), $number );
			}

			do_action( 'um_pre_users_query', $this, $directory_data, $sortby );

			$sql_join = implode( ' ', $this->joins );
			$sql_where = implode( ' AND ', $this->where_clauses );
			$sql_where = ! empty( $sql_where ) ? 'AND ' . $sql_where : '';

			global $wpdb;

			/*
			 *
			 * SQL_CALC_FOUND_ROWS is deprecated as of MySQL 8.0.17
			 * https://core.trac.wordpress.org/ticket/47280
			 *
			 * */
			$user_ids = $wpdb->get_col(
				"SELECT SQL_CALC_FOUND_ROWS DISTINCT u.ID
				{$this->select}
				FROM {$wpdb->users} AS u
				{$sql_join}
				WHERE 1=1 {$sql_where}
				{$this->having}
				{$this->sql_order}
				{$this->sql_limit}"
			);

			$query = array(
				'select'    => $this->select,
				'sql_where' => $sql_where,
				'having'    => $this->having,
				'sql_limit' => $this->sql_limit,
			);

			$total_users = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

			/**
			 * Filters the member directory query result when um_usermeta table is used.
			 *
			 * @since 2.1.3
			 * @hook um_prepare_user_results_array_meta
			 *
			 * @param {array} $user_ids   Members Query Result.
			 * @param {array} $query_args Query arguments.
			 *
			 * @return {array} Query result.
			 *
			 * @example <caption>Remove some users where ID equals 10 and 12 from query.</caption>
			 * function my_custom_um_prepare_user_results_array_meta( $user_ids, $query_args ) {
			 *     $user_ids = array_diff( $user_ids, array( 10, 12 ) );
			 *     return $user_ids;
			 * }
			 * add_filter( 'um_prepare_user_results_array_meta', 'my_custom_um_prepare_user_results_array', 10, 2 );
			 */
			$user_ids = apply_filters( 'um_prepare_user_results_array_meta', $user_ids, $query );

			$pagination_data = $this->calculate_pagination( $directory_data, $total_users );

			$sizes = UM()->options()->get( 'cover_thumb_sizes' );

			$this->cover_size = UM()->mobile()->isTablet() ? $sizes[1] : end( $sizes );

			$avatar_size = UM()->options()->get( 'profile_photosize' );
			$this->avatar_size = str_replace( 'px', '', $avatar_size );

			$users = array();
			foreach ( $user_ids as $user_id ) {
				$users[] = $this->build_user_card_data( $user_id, $directory_data );
			}

			um_reset_user();
			// end of user card

			$member_directory_response = apply_filters( 'um_ajax_get_members_response', array(
				'pagination'    => $pagination_data,
				'users'         => $users,
				'is_search'     => $this->is_search,
			), $directory_data );

			wp_send_json_success( $member_directory_response );
		}
	}
}
