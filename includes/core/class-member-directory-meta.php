<?php
namespace um\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Member_Directory_Meta' ) ) {


	/**
	 * Class Member_Directory_Meta
	 * @package um\core
	 */
	class Member_Directory_Meta extends Member_Directory {


		/**
		 * @var string
		 */
		var $sql_where = '';
		var $meta_iteration = 1;
		var $joins = array();
		var $sql_limit = '';
		var $sql_order = '';


		/**
		 * Member_Directory_Meta constructor.
		 */
		function __construct() {
			parent::__construct();

			add_action( 'updated_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
			add_action( 'added_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
			add_action( 'deleted_user_meta', array( &$this, 'on_delete_usermeta' ), 10, 4 );

			add_action( 'um_add_new_field', array( &$this, 'on_new_field_added' ), 10, 1 );
			add_action( 'um_delete_custom_field', array( &$this, 'on_delete_custom_field' ), 10, 1 );
		}


		/**
		 * Delete custom field and metakey from UM usermeta table
		 *
		 * @param $metakey
		 */
		function on_delete_custom_field( $metakey ) {
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

				update_option( 'um_usermeta_fields', $metakeys );
			}
		}


		/**
		 * Add metakey to usermeta fields
		 *
		 * @param $metakey
		 */
		function on_new_field_added( $metakey ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );
			if ( ! in_array( $metakey, $metakeys ) ) {
				$metakeys[] = $metakey;
				update_option( 'um_usermeta_fields', $metakeys );
			}
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
		 * Main Query function for getting members via AJAX
		 */
		function ajax_get_members() {
			UM()->check_ajax_nonce();

			global $wpdb;

			$blog_id = get_current_blog_id();

			$directory_id = $this->get_directory_by_hash( $_POST['directory_id'] );
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
						$this->sql_where .= " AND u.ID IN ( '" . implode( "','", $users_array ) . "' )";
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
				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_general ON umm_general.user_id = u.ID";

				$this->sql_where .= " AND ( umm_general.um_key = 'um_member_directory_data' AND 
				umm_general.um_value LIKE '%s:14:\"account_status\";s:8:\"approved\";%' AND umm_general.um_value LIKE '%s:15:\"hide_in_members\";b:0;%'{$profile_photo_where}{$cover_photo_where} )";
			} else {
				if ( ! empty( $cover_photo_where ) || ! empty( $profile_photo_where ) ) {
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_general ON umm_general.user_id = u.ID";

					$this->sql_where .= " AND ( umm_general.um_key = 'um_member_directory_data'{$profile_photo_where}{$cover_photo_where} )";
				}
			}

			$roles = array();
			if ( UM()->roles()->um_user_can( 'can_view_all' ) ) {
				$view_roles = um_user( 'can_view_roles' );

				if ( ! $view_roles ) {
					$view_roles = array();
				}

				$roles = array_merge( $roles, maybe_unserialize( $view_roles ) );
			}

			if ( ! empty( $directory_data['roles'] ) ) {
				if ( ! empty( $roles ) ) {
					$roles = array_intersect( $roles, maybe_unserialize( $directory_data['roles'] ) );
				} else {
					$roles = array_merge( $roles, maybe_unserialize( $directory_data['roles'] ) );
				}
			}

			if ( ! empty( $roles ) ) {
				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = '" . $wpdb->get_blog_prefix( $blog_id ) . "capabilities' )";

				$roles_clauses = array();
				foreach ( $roles as $role ) {
					$roles_clauses[] = "umm_roles.um_value LIKE '%\"" . $role . "\"%'";
				}

				$roles_search = implode( ' OR ', $roles_clauses );

				$this->sql_where .= " AND ( {$roles_search} )";
			} else {
				if ( is_multisite() ) {
					// select users who have capabilities for current blog
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = '" . $wpdb->get_blog_prefix( $blog_id ) . "capabilities' )";
					$this->sql_where .= " AND umm_roles.um_value IS NOT NULL ";
				}
			}


			if ( ! empty( $_POST['search'] ) ) {
				$searches = array();
				foreach ( $this->core_search_fields as $field ) {
					$searches[] = $wpdb->prepare( "u.{$field} LIKE %s", '%' . trim( $_POST['search'] ) . '%' );
				}

				$core_search = implode( ' OR ', $searches );

				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_search ON umm_search.user_id = u.ID";
				$this->sql_where .= " AND ( umm_search.um_value = '" . trim( $_POST['search'] ) . "' OR umm_search.um_value LIKE '%" . trim( $_POST['search'] ) . "%' OR umm_search.um_value LIKE '%" . trim( serialize( strval( $_POST['search'] ) ) ) . "%' OR {$core_search})";

				$this->is_search = true;
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

					$attrs = UM()->fields()->get_field( $field );
					// skip private invisible fields
					if ( ! um_can_view_field( $attrs ) ) {
						continue;
					}

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

										$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";

										$value = trim( stripslashes( $value ) );

										$this->sql_where .= " AND ( umm" . $i . ".um_key = '{$field}' AND umm" . $i . ".um_value = '{$value}' )";

										$this->custom_filters_in_query[ $field ] = $value;

										break;

									case 'select':
										if ( is_array( $value ) ) {

											$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";

											$values_array = array();
											foreach ( $value as $single_val ) {
												$single_val = stripslashes( $single_val );

												$values_array[] = "umm" . $i . ".um_value LIKE '%\"" . trim( $single_val ) . "\"%'";
												$values_array[] = "umm" . $i . ".um_value LIKE '%" . serialize( strval( trim( $single_val ) ) ) . "%'";
												$values_array[] = "umm" . $i . ".um_value = '" . trim( $single_val ) . "'";

												if ( is_numeric( $single_val ) ) {
													$values_array[] = "umm" . $i . ".um_value LIKE '%" . serialize( intval( trim( $single_val ) ) ) . "%'";
												}
											}

											$values = implode( ' OR ', $values_array );

											$this->sql_where .= " AND ( umm" . $i . ".um_key = '{$field}' AND ( {$values} ) )";
										}

										$this->custom_filters_in_query[ $field ] = $value;

										break;
									case 'slider':

										$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";

										$min = min( $value );
										$max = max( $value );

										$this->sql_where .= " AND ( umm" . $i . ".um_key = '{$field}' AND umm" . $i . ".um_value BETWEEN {$min} AND {$max} )";

										$this->custom_filters_in_query[ $field ] = $value;

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


										$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";

										$this->sql_where .= " AND ( umm" . $i . ".um_key = '{$field}' AND umm" . $i . ".um_value BETWEEN {$from_date} AND {$to_date} )";

										$this->custom_filters_in_query[ $field ] = array( $from_date, $to_date );

										break;
									case 'timepicker':

										if ( $value[0] == $value[1] ) {
											$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";
											$this->sql_where .= " AND ( umm" . $i . ".um_key = '{$field}' AND umm" . $i . ".um_value = '{$value[0]}' )";
										} else {
											$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";
											$this->sql_where .= " AND ( umm" . $i . ".um_key = '{$field}' AND CAST( umm" . $i . ".um_value AS TIME ) BETWEEN {$value[0]} AND {$value[1]} )";
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

							if ( ! empty( $roles ) || is_multisite() ) {
								$roles_clauses = array();
								foreach ( $value as $role ) {
									$roles_clauses[] = "umm_roles.um_value LIKE '%\"" . $role . "\"%'";
								}

								$roles_search = implode( ' OR ', $roles_clauses );

								$this->sql_where .= " AND ( {$roles_search} )";
							} else {
								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = '" . $wpdb->get_blog_prefix( $blog_id ) . "capabilities' )";

								$roles = $value;

								$roles_clauses = array();
								foreach ( $value as $role ) {
									$roles_clauses[] = "umm_roles.um_value LIKE '%\"" . $role . "\"%'";
								}

								$roles_search = implode( ' OR ', $roles_clauses );

								$this->sql_where .= " AND ( {$roles_search} )";
							}

							$this->custom_filters_in_query[ $field ] = $value;

							break;
						case 'birth_date':

							$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
							$to_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

							$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";

							$this->sql_where .= " AND ( umm" . $i . ".um_key = 'birth_date' AND umm" . $i . ".um_value BETWEEN {$from_date} AND {$to_date} )";

							$this->custom_filters_in_query[ $field ] = array( $to_date, $from_date );

							break;
						case 'user_registered':

							$offset = 0;
							if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
								$offset = (int) $_POST['gmt_offset'];
							}

							$from_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', min( $value ) ) . "+$offset hours" ) );
							$to_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', max( $value ) ) . "+$offset hours" ) );

							$this->sql_where .= " AND ( u.user_registered BETWEEN {$from_date} AND {$to_date} )";

							$this->custom_filters_in_query[ $field ] = $value;

							break;
						case 'last_login':

							$offset = 0;
							if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
								$offset = (int) $_POST['gmt_offset'];
							}

							$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
							$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59

							$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm" . $i . " ON umm" . $i . ".user_id = u.ID";

							$this->sql_where .= " AND ( umm" . $i . ".um_key = '_um_last_login' AND umm" . $i . ".um_value BETWEEN {$from_date} AND {$to_date} )";

							$this->custom_filters_in_query[ $field ] = $value;

							break;
					}

					$i++;
				}
			}


			//unable default filter in case if we select other filters in frontend filters
			if ( empty( $this->custom_filters_in_query ) ) {
				$default_filters = array();
				if ( ! empty( $directory_data['search_filters'] ) ) {
					$default_filters = maybe_unserialize( $directory_data['search_filters'] );
				}

				$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );

				if ( ! empty( $default_filters ) ) {
					$i = 1;
					foreach ( $default_filters as $field => $value ) {
						//unable default filter in case if we select other value in frontend filters
		//				if ( in_array( $field, array_keys( $this->custom_filters_in_query ) ) ) {
		//					continue;
		//				}

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

											$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";

											$value = trim( stripslashes( $value ) );

											$this->sql_where .= " AND ( ummd" . $i . ".um_key = '{$field}' AND ummd" . $i . ".um_value = '{$value}' )";

											$this->custom_filters_in_query[ $field ] = $value;

											break;
										case 'select':
											if ( ! is_array( $value ) ) {
												$value = array( $value );
											}

											$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";

											$values_array = array();
											foreach ( $value as $single_val ) {
												$single_val = stripslashes( $single_val );

												$values_array[] = "ummd" . $i . ".um_value LIKE '%\"" . trim( $single_val ) . "\"%'";
												$values_array[] = "ummd" . $i . ".um_value LIKE '%" . serialize( strval( trim( $single_val ) ) ) . "%'";
												$values_array[] = "ummd" . $i . ".um_value = '" . trim( $single_val ) . "'";

												if ( is_numeric( $single_val ) ) {
													$values_array[] = "ummd" . $i . ".um_value LIKE '%" . serialize( intval( trim( $single_val ) ) ) . "%'";
												}
											}

											$values = implode( ' OR ', $values_array );

											$this->sql_where .= " AND ( ummd" . $i . ".um_key = '{$field}' AND ( {$values} ) )";

											break;
										case 'slider':

											$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";

											$min = min( $value );
											$max = max( $value );

											$this->sql_where .= " AND ( ummd" . $i . ".um_key = '{$field}' AND ummd" . $i . ".um_value BETWEEN {$min} AND {$max} )";

											break;
										case 'datepicker':

											$offset = 0;
											if ( is_numeric( $gmt_offset ) ) {
												$offset = $gmt_offset;
											}

											$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
											$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59
											$from_date = date( 'Y/m/d', $from_date );
											$to_date = date( 'Y/m/d', $to_date );


											$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";

											$this->sql_where .= " AND ( ummd" . $i . ".um_key = '{$field}' AND ummd" . $i . ".um_value BETWEEN {$from_date} AND {$to_date} )";

											break;
										case 'timepicker':

											if ( $value[0] == $value[1] ) {
												$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";
												$this->sql_where .= " AND ( ummd" . $i . ".um_key = '{$field}' AND ummd" . $i . ".um_value = '{$value[0]}' )";
											} else {
												$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";
												$this->sql_where .= " AND ( ummd" . $i . ".um_key = '{$field}' AND CAST( ummd" . $i . ".um_value AS TIME ) BETWEEN {$value[0]} AND {$value[1]} )";
											}

											break;
									}

								}

								break;
							case 'role':
//								$value = explode( '||', $value );
//								$value = array_map( 'strtolower', $value );

								$value = array_map( 'strtolower', $value );

								if ( ! empty( $roles ) || is_multisite() ) {
									$roles_clauses = array();
									foreach ( $value as $role ) {
										$roles_clauses[] = "umm_roles.um_value LIKE '%\"" . $role . "\"%'";
									}

									$roles_search = implode( ' OR ', $roles_clauses );

									$this->sql_where .= " AND ( {$roles_search} )";
								} else {
									$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = '" . $wpdb->get_blog_prefix( $blog_id ) . "capabilities' )";

									$roles = $value;

									$roles_clauses = array();
									foreach ( $value as $role ) {
										$roles_clauses[] = "umm_roles.um_value LIKE '%\"" . $role . "\"%'";
									}

									$roles_search = implode( ' OR ', $roles_clauses );

									$this->sql_where .= " AND ( {$roles_search} )";
								}

								break;
							case 'birth_date':
								$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
								$to_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";

								$this->sql_where .= " AND ( ummd" . $i . ".um_key = 'birth_date' AND ummd" . $i . ".um_value BETWEEN {$from_date} AND {$to_date} )";


								break;
							case 'user_registered':
								$offset = 0;
								if ( is_numeric( $gmt_offset ) ) {
									$offset = $gmt_offset;
								}

								$from_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', min( $value ) ) . "+$offset hours" ) );
								$to_date = date( 'Y-m-d H:s:i', strtotime( date( 'Y-m-d H:s:i', max( $value ) ) . "+$offset hours" ) );

								$this->sql_where .= " AND ( u.user_registered BETWEEN {$from_date} AND {$to_date} )";

								break;
							case 'last_login':
								$offset = 0;
								if ( is_numeric( $gmt_offset ) ) {
									$offset = $gmt_offset;
								}

								$from_date = (int) min( $value ) + ( $offset * HOUR_IN_SECONDS ); // client time zone offset
								$to_date   = (int) max( $value ) + ( $offset * HOUR_IN_SECONDS ) + DAY_IN_SECONDS - 1; // time 23:59

								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummd" . $i . " ON ummd" . $i . ".user_id = u.ID";

								$this->sql_where .= " AND ( ummd" . $i . ".um_key = '_um_last_login' AND ummd" . $i . ".um_value BETWEEN {$from_date} AND {$to_date} )";
								break;
						}

						$i++;
					}
				}
			}


			$order = 'ASC';
			$sortby = ! empty( $_POST['sorting'] ) ? $_POST['sorting'] : $directory_data['sortby'];

			// handle sorting options
			// sort members by
			if ( $sortby == 'other' && $directory_data['sortby_custom'] ) {

				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = '{$directory_data['sortby_custom']}' )";

				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) {$order} ";

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

				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS NUMERIC ) {$order} ";

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

				$this->sql_order = 'ORDER by RAND(' . $seed . ')';

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

			$this->sql_order = apply_filters( 'um_modify_sortby_parameter', $this->sql_order, $sortby );


			$profiles_per_page = $directory_data['profiles_per_page'];
			if ( UM()->mobile()->isMobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $directory_data['profiles_per_page_mobile'];
			}

			$query_number = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;
			$query_paged = ! empty( $_POST['page'] ) ? $_POST['page'] : 1;

			$number = $query_number;
			if ( ! empty( $directory_data['max_users'] ) && $query_paged*$query_number > $directory_data['max_users'] ) {
				$number = ( $query_paged*$query_number - ( $query_paged*$query_number - $directory_data['max_users'] ) ) % $query_number;
			}

			// limit
			if ( isset( $query_number ) && $query_number > 0 ) {
				$this->sql_limit .= $wpdb->prepare( 'LIMIT %d, %d', $query_number * ( $query_paged - 1 ), $number );
			}

			$sql_join = implode( ' ', $this->joins );

			do_action( 'um_pre_users_query', $this, $directory_data, $sortby );

			global $wpdb;
			$user_ids = $wpdb->get_col(
				"SELECT SQL_CALC_FOUND_ROWS DISTINCT u.ID 
				FROM {$wpdb->users} AS u
				{$sql_join}
				WHERE 1=1 {$this->sql_where}
				{$this->sql_order}
				{$this->sql_limit}"
			);

			$total_users = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );

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

			wp_send_json_success( array( 'pagination' => $pagination_data, 'users' => $users, 'is_search' => $this->is_search ) );
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

			$current_page = ! empty( $_POST['page'] ) ? $_POST['page'] : 1;

			$total_users = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $total_users ) ? $directory_data['max_users'] : $total_users;

			// number of profiles for mobile
			$profiles_per_page = $directory_data['profiles_per_page'];
			if ( UM()->mobile()->isMobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $directory_data['profiles_per_page_mobile'];
			}

			$total_pages = 1;
			if ( ! empty( $profiles_per_page ) ) {
				$total_pages = ceil( $total_users / $profiles_per_page );
			}

			if ( ! empty( $total_pages ) ) {
				$index1 = 0 - ( $current_page - 2 ) + 1;
				$to = $current_page + 2;
				if ( $index1 > 0 ) {
					$to += $index1;
				}

				$index2 = $total_pages - ( $current_page + 2 );
				$from = $current_page - 2;
				if ( $index2 < 0 ) {
					$from += $index2;
				}

				$pages_to_show = range(
					( $from > 0 ) ? $from : 1,
					( $to <= $total_pages ) ? $to : $total_pages
				);
			}


			$pagination_data = array(
				'pages_to_show' => ( ! empty( $pages_to_show ) && count( $pages_to_show ) > 1 ) ? array_values( $pages_to_show ) : array(),
				'current_page'  => $current_page,
				'total_pages'   => $total_pages,
				'total_users'   => $total_users,
			);

			$pagination_data['header'] = $this->convert_tags( $directory_data['header'], $pagination_data );
			$pagination_data['header_single'] = $this->convert_tags( $directory_data['header_single'], $pagination_data );

			return $pagination_data;
		}
	}
}