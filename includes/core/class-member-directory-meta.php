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
		private $roles_in_query = false;

		/**
		 * @var bool
		 */
		public $general_meta_joined = false;

		/**
		 * @var string
		 */
		private $having = '';

		/**
		 * @var string
		 */
		private $select = '';

		/**
		 * @var string
		 */
		private $sql_limit = '';

		/**
		 * @var string
		 */
		public $sql_order = '';

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
		public function on_delete_custom_field( $metakey, $args ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );

			if ( in_array( $metakey, $metakeys, true ) ) {
				unset( $metakeys[ array_search( $metakey, $metakeys, true ) ] );

				global $wpdb;

				$wpdb->delete(
					"{$wpdb->prefix}um_metadata",
					array(
						'um_key' => $metakey,
					),
					array(
						'%s',
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
		public function on_new_field_added( $metakey, $args ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );

			if ( ! in_array( $metakey, $metakeys, true ) ) {
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
		public function on_delete_usermeta( $meta_ids, $object_id, $meta_key, $_meta_value ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );
			if ( ! in_array( $meta_key, $metakeys, true ) ) {
				return;
			}

			global $wpdb;

			$wpdb->delete(
				"{$wpdb->prefix}um_metadata",
				array(
					'user_id' => $object_id,
					'um_key'  => $meta_key,
				),
				array(
					'%d',
					'%s',
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
		public function on_update_usermeta( $meta_id, $object_id, $meta_key, $_meta_value ) {
			$metakeys = get_option( 'um_usermeta_fields', array() );
			if ( ! in_array( $meta_key, $metakeys, true ) ) {
				return;
			}

			global $wpdb;

			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT umeta_id
					FROM {$wpdb->prefix}um_metadata
					WHERE user_id = %d AND
						  um_key = %s
					LIMIT 1",
					$object_id,
					$meta_key
				)
			);

			if ( empty( $result ) ) {
				$wpdb->insert(
					"{$wpdb->prefix}um_metadata",
					array(
						'user_id'  => $object_id,
						'um_key'   => $meta_key,
						'um_value' => maybe_serialize( $_meta_value ),
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
						'um_value' => maybe_serialize( $_meta_value ),
					),
					array(
						'umeta_id' => $result,
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
		 * @param int  $i
		 * @param bool $is_default
		 */
		protected function handle_filter_query( $directory_data, $field, $value, $i, $is_default = false ) {
			global $wpdb;

			$join_slug  = $is_default ? 'ummd' : 'umm';
			$join_alias = esc_sql( $join_slug . $i );

			$blog_id = get_current_blog_id();

			/**
			 * Filters member directory select-type filter relation in query.
			 *
			 * @param {string} $relation Relation `OR` or `AND`. `OR` by default.
			 * @param {string} $field    Field key.
			 *
			 * @return {string} Relation.
			 *
			 * @since 2.8.5
			 * @hook um_members_directory_select_filter_relation
			 *
			 * @example <caption>Change relation to 'AND'.</caption>
			 * function my_um_members_directory_select_filter_relation( $relation, $field ) {
			 *     // your code here
			 *     $relation = 'AND';
			 *     return $relation;
			 * }
			 * add_filter( 'um_members_directory_select_filter_relation', 'my_um_members_directory_select_filter_relation', 10, 2 );
			 */
			$relation = apply_filters( 'um_members_directory_select_filter_relation', 'OR', $field );

			switch ( $field ) {
				default:
					$filter_type = $this->filter_types[ $field ];

					/**
					 * Filters marker for skipping default filter handle in member directory queries.
					 * Hook handle filter queries for the custom usermeta table only.
					 * Note: $field is the field meta key.
					 *
					 * @since 2.1
					 * @hook um_query_args_{$field}__filter_meta
					 *
					 * @param {bool}   $skip                  Skip default filter handler marker.
					 * @param {object} $member_directory_meta Member_Directory_Meta class instance.
					 * @param {string} $field                 Filter's field key.
					 * @param {mixed}  $value                 Filter value.
					 * @param {string} $filter_type           Filter type.
					 * @param {bool}   $is_default            If it's admin filtering option then `true`.
					 *
					 * @return {bool} Skip default filter handler marker.
					 *
					 * @example <caption>Skip filter by rating default handler and add 3rd-party handlers in callback.</caption>
					 * function um_custom_query_args_filter_rating__filter_meta( $skip, $member_directory_meta, $field, $value, $filter_type, $is_default ) {
					 *     $skip = true;
					 *     $member_directory_meta->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummreviews ON ( ummreviews.user_id = u.ID AND ummreviews.um_key = '_reviews_avg' )";
					 *     return $skip;
					 * }
					 * add_filter( 'um_query_args_filter_rating__filter_meta', 'um_custom_query_args_filter_rating__filter_meta', 10, 6 );
					 */
					$skip_default = apply_filters( "um_query_args_{$field}__filter_meta", false, $this, $field, $value, $filter_type, $is_default );
					/**
					 * Filters marker for skipping default filter handle in member directory queries.
					 * Hook handle filter queries for the custom usermeta table only.
					 *
					 * @since 2.1
					 * @hook um_query_args_filter_global_meta
					 *
					 * @param {bool}   $skip                  Skip default filter handler marker.
					 * @param {object} $member_directory_meta Member_Directory_Meta class instance.
					 * @param {string} $field                 Filter's field key.
					 * @param {mixed}  $value                 Filter value.
					 * @param {string} $filter_type           Filter type.
					 * @param {bool}   $is_default            If it's admin filtering option then `true`.
					 *
					 * @return {bool} Skip default filter handler marker.
					 *
					 * @example <caption>Skip filter by rating default handler and add 3rd-party handlers in callback.</caption>
					 * function um_custom_query_args_filter_global_meta( $skip, $member_directory_meta, $field, $value, $filter_type, $is_default ) {
					 *     if ( 'filter_rating' === $field ) {
					 *         $skip = true;
					 *         $member_directory_meta->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata ummreviews ON ( ummreviews.user_id = u.ID AND ummreviews.um_key = '_reviews_avg' )";
					 *     }
					 *     return $skip;
					 * }
					 * add_filter( 'um_query_args_filter_global_meta', 'um_custom_query_args_filter_global_meta', 10, 6 );
					 */
					$skip_default = apply_filters( 'um_query_args_filter_global_meta', $skip_default, $this, $field, $value, $filter_type, $is_default );

					if ( ! $skip_default ) {
						switch ( $filter_type ) {
							default:
								do_action( "um_query_args_{$field}_{$filter_type}__filter_meta", $field, $value, $filter_type, $i, $is_default );
								break;

							case 'text':
								// $join_alias is pre-escaped.
								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";

								$value   = trim( stripslashes( $value ) );
								$compare = apply_filters( 'um_members_directory_filter_text', '=', $field );
								$compare = esc_sql( $compare );
								$value   = apply_filters( 'um_members_directory_filter_text_meta_value', $value, $field );

								// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias and $compare variables are pre-escaped.
								$this->where_clauses[] = $wpdb->prepare( "{$join_alias}.um_key = %s AND {$join_alias}.um_value {$compare} %s", $field, $value );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = $value;
								}
								break;

							case 'select':
								if ( ! is_array( $value ) ) {
									$value = array( $value );
								}

								// $join_alias is pre-escaped.
								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";

								$values_array = array();
								foreach ( $value as $single_val ) {
									$single_val = trim( stripslashes( $single_val ) );

									// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias and $compare variables are pre-escaped.
									$values_array[] = $wpdb->prepare( "{$join_alias}.um_value LIKE %s", '%"' . $wpdb->esc_like( $single_val ) . '"%' );
									$values_array[] = $wpdb->prepare( "{$join_alias}.um_value LIKE %s", '%' . $wpdb->esc_like( maybe_serialize( (string) $single_val ) ) . '%' );
									$values_array[] = $wpdb->prepare( "{$join_alias}.um_value = %s", $single_val );

									if ( is_numeric( $single_val ) ) {
										$values_array[] = $wpdb->prepare( "{$join_alias}.um_value LIKE %s", '%' . $wpdb->esc_like( maybe_serialize( (int) $single_val ) ) . '%' );
									}
									// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
								}

								$values = implode( ' ' . esc_sql( $relation ) . ' ', $values_array );

								// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias and $values variables are pre-escaped or $wpdb->prepare.
								$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = %s AND ( {$values} ) )", $field );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = $value;
								}
								break;

							case 'slider':
								// $join_alias is pre-escaped.
								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";

								$min = min( $value );
								$max = max( $value );

								// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
								$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = %s AND {$join_alias}.um_value BETWEEN %d AND %d )", $field, $min, $max );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = $value;
								}
								break;

							case 'datepicker':
								$offset = 0;
								if ( ! $is_default ) {
									// phpcs:disable WordPress.Security.NonceVerification -- early verified in `ajax_get_members()`.
									if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
										$offset = (int) $_POST['gmt_offset'];
									}
									// phpcs:enable WordPress.Security.NonceVerification -- early verified in `ajax_get_members()`.
								} else {
									$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );
									if ( is_numeric( $gmt_offset ) ) {
										$offset = $gmt_offset;
									}
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

								// $join_alias is pre-escaped.
								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";

								// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
								$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = %s AND {$join_alias}.um_value BETWEEN %s AND %s )", $field, $from_date, $to_date );

								if ( ! $is_default ) {
									$this->custom_filters_in_query[ $field ] = array( $from_date, $to_date );
								}
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
								// $join_alias is pre-escaped.
								$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";
								if ( $value[0] === $value[1] ) {
									// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
									$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = %s AND {$join_alias}.um_value = %s )", $field, $value[0] );
								} else {
									// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
									$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = %s AND CAST( {$join_alias}.um_value AS TIME ) BETWEEN %s AND %s )", $field, $value[0], $value[1] );
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
						$this->joins[] = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = %s )", $wpdb->get_blog_prefix( $blog_id ) . 'capabilities' );
						$this->roles   = $value;

						$this->roles_in_query = true;
					}

					$roles_clauses = array();
					foreach ( $value as $role ) {
						$roles_clauses[] = $wpdb->prepare( 'umm_roles.um_value LIKE %s', '%"' . $wpdb->esc_like( $role ) . '"%' );
					}

					// $roles_clauses is pre-prepared.
					$this->where_clauses[] = '( ' . implode( ' ' . esc_sql( $relation ) . ' ', $roles_clauses ) . ' )';

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = $value;
					}
					break;

				case 'birth_date':
					// @todo: rewrite date() in WP5.3 standards.
					$from_date = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ), date( 'Y', time() - min( $value ) * YEAR_IN_SECONDS ) ) );
					$to_date   = date( 'Y/m/d', mktime( 0,0,0, date( 'm', time() ), date( 'd', time() ) + 1, date( 'Y', time() - ( max( $value ) + 1 ) * YEAR_IN_SECONDS ) ) );

					// $join_alias is pre-escaped.
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
					$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = 'birth_date' AND {$join_alias}.um_value BETWEEN %s AND %s )", $to_date, $from_date );

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = array( $to_date, $from_date );
					}
					break;

				case 'user_registered':
					$offset = 0;
					if ( ! $is_default ) {
						// phpcs:disable WordPress.Security.NonceVerification -- early verified in `ajax_get_members()`.
						if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
							$offset = (int) $_POST['gmt_offset'];
						}
						// phpcs:enable WordPress.Security.NonceVerification -- early verified in `ajax_get_members()`.
					} else {
						$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );
						if ( is_numeric( $gmt_offset ) ) {
							$offset = (int) $gmt_offset;
						}
					}
					// @todo: rewrite date() in WP5.3 standards.
					$from_date = date( 'Y-m-d H:i:s', strtotime( min( $value ) ) + $offset * HOUR_IN_SECONDS ); // client time zone offset
					$to_date   = date( 'Y-m-d H:i:s', strtotime( max( $value ) ) + $offset * HOUR_IN_SECONDS + DAY_IN_SECONDS - 1 ); // time 23:59

					$this->where_clauses[] = $wpdb->prepare( 'u.user_registered BETWEEN %s AND %s', $from_date, $to_date );

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = $value;
					}
					break;

				case 'last_login':
					$offset = 0;
					if ( ! $is_default ) {
						// phpcs:disable WordPress.Security.NonceVerification -- early verified in `ajax_get_members()`.
						if ( isset( $_POST['gmt_offset'] ) && is_numeric( $_POST['gmt_offset'] ) ) {
							$offset = (int) $_POST['gmt_offset'];
						}
						// phpcs:enable WordPress.Security.NonceVerification -- early verified in `ajax_get_members()`.
					} else {
						$gmt_offset = get_post_meta( $directory_data['form_id'], '_um_search_filters_gmt', true );
						if ( is_numeric( $gmt_offset ) ) {
							$offset = $gmt_offset;
						}
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

					// $join_alias is pre-escaped.
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";
					$join_alias_ll = $join_alias . '_show_las_login';
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias_ll} ON {$join_alias_ll}.user_id = u.ID AND {$join_alias_ll}.um_key = 'um_show_last_login'";
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
					$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = '_um_last_login' AND {$join_alias}.um_value BETWEEN %s AND %s AND ( {$join_alias_ll}.um_value IS NULL OR {$join_alias_ll}.um_value != %s ) )", $from_date, $to_date, 'a:1:{i:0;s:2:"no";}' );

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = $value;
					}
					break;

				case 'gender':
					if ( ! is_array( $value ) ) {
						$value = array( $value );
					}

					// $join_alias is pre-escaped.
					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata {$join_alias} ON {$join_alias}.user_id = u.ID";

					$values_array = array();
					foreach ( $value as $single_val ) {
						$single_val = trim( stripslashes( $single_val ) );

						// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias and $compare variables are pre-escaped.
						$values_array[] = $wpdb->prepare( "{$join_alias}.um_value LIKE %s", '%"' . $wpdb->esc_like( $single_val ) . '"%' );
						$values_array[] = $wpdb->prepare( "{$join_alias}.um_value = %s", $single_val );
						// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias is pre-escaped.
					}

					$values = implode( ' ' . esc_sql( $relation ) . ' ', $values_array );

					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $join_alias and $values variables are pre-escaped or $wpdb->prepare.
					$this->where_clauses[] = $wpdb->prepare( "( {$join_alias}.um_key = %s AND ( {$values} ) )", $field );

					if ( ! $is_default ) {
						$this->custom_filters_in_query[ $field ] = $value;
					}
					break;
			}
		}

		/**
		 * Main Query function for getting members via AJAX
		 */
		public function ajax_get_members() {
			UM()->check_ajax_nonce();

			global $wpdb;

			$blog_id = get_current_blog_id();
			// phpcs:disable WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.
			if ( empty( $_POST['directory_id'] ) ) {
				wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
			}

			$directory_id = $this->get_directory_by_hash( sanitize_key( $_POST['directory_id'] ) );
			if ( empty( $directory_id ) ) {
				wp_send_json_error( __( 'Wrong member directory data', 'ultimate-member' ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.

			$directory_data = UM()->query()->post_data( $directory_id );

			// Predefined result for user without capabilities to see other members.
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
							$users_array[] = absint( $exists_id );
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
							$users_array[] = absint( $exists_id );
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
				// $profile_photo_where and $cover_photo_where are static in code.
				$this->where_clauses[] = "( umm_general.um_key = 'um_member_directory_data' AND
				umm_general.um_value LIKE '%s:14:\"account_status\";s:8:\"approved\";%' AND umm_general.um_value LIKE '%s:15:\"hide_in_members\";b:0;%'{$profile_photo_where}{$cover_photo_where} )";
			} else {
				if ( ! empty( $cover_photo_where ) || ! empty( $profile_photo_where ) ) {
					if ( ! $this->general_meta_joined ) {
						$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_general ON umm_general.user_id = u.ID";

						$this->general_meta_joined = true;
					}
					// $profile_photo_where and $cover_photo_where are static in code.
					$this->where_clauses[] = "( umm_general.um_key = 'um_member_directory_data'{$profile_photo_where}{$cover_photo_where} )";
				}
			}

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
				$this->joins[] = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = %s )", $wpdb->get_blog_prefix( $blog_id ) . 'capabilities' );

				$roles_clauses = array();
				foreach ( $this->roles as $role ) {
					$roles_clauses[] = $wpdb->prepare( 'umm_roles.um_value LIKE %s', '%"' . $wpdb->esc_like( $role ) . '"%' );
				}

				// $roles_clauses is pre-prepared.
				$this->where_clauses[] = '( ' . implode( ' OR ', $roles_clauses ) . ' )';
			} else {
				if ( ! $this->roles_in_query && is_multisite() ) {
					// select users who have capabilities for current blog
					$this->joins[]         = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata umm_roles ON ( umm_roles.user_id = u.ID AND umm_roles.um_key = %s )", $wpdb->get_blog_prefix( $blog_id ) . 'capabilities' );
					$this->where_clauses[] = 'umm_roles.um_value IS NOT NULL';
				} elseif ( $this->roles_in_query ) {
					$member_directory_response = array(
						'pagination' => $this->calculate_pagination( $directory_data, 0 ),
						'users'      => array(),
						'is_search'  => $this->is_search,
					);
					$member_directory_response = apply_filters( 'um_ajax_get_members_response', $member_directory_response, $directory_data );

					wp_send_json_success( $member_directory_response );
				}
			}

			// phpcs:disable WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.
			if ( ! empty( $_POST['search'] ) ) {
				$search_line = $this->prepare_search( $_POST['search'] );
				// phpcs:enable WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.
				if ( ! empty( $search_line ) ) {
					$searches = array();

					$exclude_fields = get_post_meta( $directory_id, '_um_search_exclude_fields', true );
					$include_fields = get_post_meta( $directory_id, '_um_search_include_fields', true );

					$core_search = $this->get_core_search_fields();
					if ( ! empty( $include_fields ) ) {
						$core_search = array_intersect( $core_search, $include_fields );
					}
					if ( ! empty( $exclude_fields ) ) {
						$core_search = array_diff( $core_search, $exclude_fields );
					}
					if ( ! empty( $core_search ) ) {
						foreach ( $core_search as $field ) {
							$field = esc_sql( $field );
							// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $field is pre-escaped.
							$searches[] = $wpdb->prepare( "u.{$field} LIKE %s", '%' . $wpdb->esc_like( $search_line ) . '%' );
						}
					}

					$core_search = implode( ' OR ', $searches );
					if ( ! empty( $core_search ) ) {
						$core_search = ' OR ' . $core_search;
					}

					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_search ON umm_search.user_id = u.ID";

					$additional_search = apply_filters( 'um_member_directory_meta_general_search_meta_query', '', $search_line );

					$search_like_string = apply_filters( 'um_member_directory_meta_search_like_type', '%' . $wpdb->esc_like( $search_line ) . '%', $search_line );

					$custom_fields_sql = '';

					if ( ! empty( $exclude_fields ) ) {
						$custom_fields_sql = " AND umm_search.um_key NOT IN ('" . implode( "','", $exclude_fields ) . "') ";
					}
					if ( ! empty( $include_fields ) ) {
						$custom_fields_sql = " AND umm_search.um_key IN ('" . implode( "','", $include_fields ) . "') ";
					}

					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $core_search and $additional_search are pre-prepared.
					$this->where_clauses[] = $wpdb->prepare( "( umm_search.um_value = %s OR umm_search.um_value LIKE %s OR umm_search.um_value LIKE %s{$core_search}{$additional_search}){$custom_fields_sql}", $search_line, $search_like_string, '%' . $wpdb->esc_like( maybe_serialize( (string) $search_line ) ) . '%' );

					$this->is_search = true;
				}
			}

			// Filters
			$filter_query = array();
			if ( ! empty( $directory_data['search_fields'] ) ) {
				$search_filters = maybe_unserialize( $directory_data['search_fields'] );
				if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.
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
			// phpcs:ignore WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.
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

			// handle sorting options
			// sort members by
			if ( $sortby === $directory_data['sortby_custom'] || in_array( $sortby, $custom_sort, true ) ) {
				$custom_sort_order = ! empty( $directory_data['sortby_custom_order'] ) ? $directory_data['sortby_custom_order'] : 'ASC';

				$this->joins[] = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = %s )", $sortby );

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
				$custom_sort_type = esc_sql( $custom_sort_type );
				$custom_sort_type = in_array( strtoupper( $custom_sort_type ), $this->sort_data_types, true ) ? $custom_sort_type : 'CHAR';

				$custom_sort_order = esc_sql( $custom_sort_order );
				$custom_sort_order = in_array( strtoupper( $custom_sort_order ), array( 'ASC', 'DESC' ), true ) ? $custom_sort_order : 'ASC';
				$this->sql_order   = " ORDER BY CAST( umm_sort.um_value AS {$custom_sort_type} ) {$custom_sort_order} ";

			} elseif ( count( $numeric_sorting_keys ) && in_array( $sortby, $numeric_sorting_keys, true ) ) {

				if ( false !== strpos( $sortby, '_desc' ) ) {
					$sortby = str_replace( '_desc', '', $sortby );
					$order  = 'DESC';
				}

				if ( false !== strpos( $sortby, '_asc' ) ) {
					$sortby = str_replace( '_asc', '', $sortby );
					$order  = 'ASC';
				}

				$order           = esc_sql( $order );
				$order           = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
				$this->joins[]   = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = %s )", $sortby );
				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS SIGNED ) {$order}, u.user_registered DESC ";

			} elseif ( 'username' === $sortby ) {

				$order           = esc_sql( $order );
				$order           = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
				$this->sql_order = " ORDER BY u.user_login {$order} ";

			} elseif ( 'display_name' === $sortby ) {

				$display_name = UM()->options()->get( 'display_name' );
				if ( 'username' === $display_name ) {

					$order           = esc_sql( $order );
					$order           = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
					$this->sql_order = " ORDER BY u.user_login {$order} ";

				} else {

					$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = 'full_name' )";

					$order           = esc_sql( $order );
					$order           = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
					$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) {$order}, u.display_name {$order} ";

				}
			} elseif ( in_array( $sortby, array( 'last_name', 'first_name', 'nickname' ), true ) ) {

				$this->joins[] = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = %s )", $sortby );

				$order           = esc_sql( $order );
				$order           = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
				$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) {$order} ";

			} elseif ( 'last_login' === $sortby ) {

				$this->joins[]   = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = '_um_last_login' )";
				$this->joins[]   = "LEFT JOIN {$wpdb->prefix}um_metadata umm_show_login ON ( umm_show_login.user_id = u.ID AND umm_show_login.um_key = 'um_show_last_login' )";
				$this->sql_order = $wpdb->prepare( ' ORDER BY CASE ISNULL(NULLIF(umm_show_login.um_value,%s)) WHEN 0 THEN %s ELSE CAST( umm_sort.um_value AS DATETIME ) END DESC ', 'a:1:{i:0;s:3:"yes";}', '1970-01-01 00:00:00' );

			} elseif ( 'last_first_name' === $sortby ) {

				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = 'last_name' )";
				$this->joins[] = "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort2 ON ( umm_sort2.user_id = u.ID AND umm_sort2.um_key = 'first_name' )";

				$this->sql_order = ' ORDER BY CAST( umm_sort.um_value AS CHAR ) ASC, CAST( umm_sort2.um_value AS CHAR ) ASC ';

			} elseif ( 'random' === $sortby ) {

				if ( um_is_session_started() === false ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@session_start();
				}

				// Reset seed on load of initial
				// phpcs:ignore WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.
				if ( empty( $_REQUEST['directory_id'] ) && isset( $_SESSION['um_member_directory_seed'] ) ) {
					unset( $_SESSION['um_member_directory_seed'] );
				}

				// Get seed from session variable if it exists
				$seed = false;
				if ( isset( $_SESSION['um_member_directory_seed'] ) ) {
					$seed = (int) $_SESSION['um_member_directory_seed'];
				}

				// Set new seed if none exists
				if ( ! $seed ) {
					$seed = wp_rand();

					$_SESSION['um_member_directory_seed'] = $seed;
				}

				$seed            = esc_sql( $seed );
				$this->sql_order = 'ORDER BY RAND(' . $seed . ')';

			} else {

				if ( false !== strpos( $sortby, '_desc' ) ) {
					$sortby = str_replace( '_desc', '', $sortby );
					$order  = 'DESC';
				}

				if ( false !== strpos( $sortby, '_asc' ) ) {
					$sortby = str_replace( '_asc', '', $sortby );
					$order  = 'ASC';
				}

				$metakeys = get_option( 'um_usermeta_fields', array() );
				if ( in_array( $sortby, $this->core_users_fields, true ) ) {
					$sortby          = esc_sql( $sortby );
					$order           = esc_sql( $order );
					$order           = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
					$this->sql_order = " ORDER BY u.{$sortby} {$order} ";
				} elseif ( in_array( $sortby, $metakeys, true ) ) {
					$this->joins[]   = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata umm_sort ON ( umm_sort.user_id = u.ID AND umm_sort.um_key = %s )", $sortby );
					$order           = esc_sql( $order );
					$order           = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
					$this->sql_order = " ORDER BY CAST( umm_sort.um_value AS CHAR ) {$order} ";
				}
			}

			$this->sql_order = apply_filters( 'um_modify_sortby_parameter_meta', $this->sql_order, $sortby );

			$profiles_per_page = $directory_data['profiles_per_page'];
			if ( wp_is_mobile() && isset( $directory_data['profiles_per_page_mobile'] ) ) {
				$profiles_per_page = $directory_data['profiles_per_page_mobile'];
			}

			$query_number = ( ! empty( $directory_data['max_users'] ) && $directory_data['max_users'] <= $profiles_per_page ) ? $directory_data['max_users'] : $profiles_per_page;
			$query_paged  = ! empty( $_POST['page'] ) ? absint( $_POST['page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification -- verified via `UM()->check_ajax_nonce();`.

			$number = $query_number;
			if ( ! empty( $directory_data['max_users'] ) && $query_paged * $query_number > $directory_data['max_users'] ) {
				$number = ( $query_paged * $query_number - ( $query_paged * $query_number - $directory_data['max_users'] ) ) % $query_number;
			}

			// limit
			if ( isset( $query_number ) && $query_number > 0 ) {
				$this->sql_limit .= $wpdb->prepare( 'LIMIT %d, %d', $query_number * ( $query_paged - 1 ), $number );
			}

			do_action( 'um_pre_users_query', $this, $directory_data, $sortby );

			$sql_select = esc_sql( $this->select );
			$sql_having = esc_sql( $this->having );
			$sql_join   = implode( ' ', $this->joins );
			$sql_where  = implode( ' AND ', $this->where_clauses );
			$sql_where  = ! empty( $sql_where ) ? 'AND ' . $sql_where : '';

			$query = array(
				'select'    => $this->select,
				'sql_where' => $sql_where,
				'having'    => $this->having,
				'sql_limit' => $this->sql_limit,
			);

			/** This filter is documented in includes/core/class-member-directory.php */
			do_action( 'um_user_before_query', $query, $this );

			/*
			 *
			 * SQL_CALC_FOUND_ROWS is deprecated as of MySQL 8.0.17
			 * https://core.trac.wordpress.org/ticket/47280
			 *
			 * */
			$user_ids = $wpdb->get_col(
				"SELECT SQL_CALC_FOUND_ROWS DISTINCT u.ID
				{$sql_select}
				FROM {$wpdb->users} AS u
				{$sql_join}
				WHERE 1=1 {$sql_where}
				{$sql_having}
				{$this->sql_order}
				{$this->sql_limit}"
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

			$this->cover_size = wp_is_mobile() ? $sizes[1] : end( $sizes );

			$avatar_size       = UM()->options()->get( 'profile_photosize' );
			$this->avatar_size = str_replace( 'px', '', $avatar_size );

			$users = array();
			foreach ( $user_ids as $user_id ) {
				$users[] = $this->build_user_card_data( $user_id, $directory_data );
			}

			um_reset_user();
			// end of user card

			$member_directory_response = array(
				'pagination' => $pagination_data,
				'users'      => $users,
				'is_search'  => $this->is_search,
			);
			$member_directory_response = apply_filters( 'um_ajax_get_members_response', $member_directory_response, $directory_data );

			wp_send_json_success( $member_directory_response );
		}
	}
}
