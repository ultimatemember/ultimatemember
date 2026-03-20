<?php
namespace um\common;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um\common
 */
class Directory extends Directory_Config {

	/**
	 * Directory constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'updated_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
		add_action( 'added_user_meta', array( &$this, 'on_update_usermeta' ), 10, 4 );
		add_action( 'deleted_user_meta', array( &$this, 'on_delete_usermeta' ), 10, 4 );

		add_action( 'um_add_new_field', array( &$this, 'on_new_field_added' ), 10, 2 );
		add_action( 'um_delete_custom_field', array( &$this, 'on_delete_custom_field' ), 10, 2 );

		add_action( 'wp_insert_post', array( &$this, 'set_token' ), 10, 3 );
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
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

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
	 * When you delete usermeta - remove row from um_metadata
	 *
	 * @param int|array $meta_ids
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	public function on_delete_usermeta( $meta_ids, $object_id, $meta_key, $_meta_value ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

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
	 * Add metakey to usermeta fields
	 *
	 * @param $metakey
	 * @param $args
	 */
	public function on_new_field_added( $metakey, $args ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

		$metakeys = get_option( 'um_usermeta_fields', array() );

		if ( ! in_array( $metakey, $metakeys, true ) ) {
			$metakeys[] = $metakey;
			update_option( 'um_usermeta_fields', array_values( $metakeys ) );
		}

		do_action( 'um_metadata_on_new_field_added', $metakeys, $metakey, $args );
	}

	/**
	 * Delete custom field and metakey from UM usermeta table
	 *
	 * @param $metakey
	 * @param $args
	 */
	public function on_delete_custom_field( $metakey, $args ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

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
	 * Set member directory token as soon as it's created.
	 *
	 * @param int     $post_ID
	 * @param WP_Post $post
	 * @param bool    $update
	 *
	 * @return void
	 */
	public function set_token( $post_ID, $post, $update ) {
		if ( 'um_directory' === $post->post_type && ! $update ) {
			$this->set_directory_hash( $post_ID );
		}
	}

	/**
	 * Check if the user can view the member directory.
	 *
	 * @param int      $directory_id
	 * @param int|null $user_id
	 *
	 * @return bool
	 */
	public function can_view_directory( $directory_id, $user_id = null ) {
		if ( is_null( $user_id ) && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		$can_view = false;
		$privacy  = get_post_meta( $directory_id, '_um_privacy', true );
		if ( '' === $privacy ) {
			$can_view = true;
		} else {
			$privacy = absint( $privacy );

			switch ( $privacy ) {
				case 0:
					$can_view = true;
					break;
				case 1:
					if ( ! is_user_logged_in() ) {
						$can_view = true;
					}
					break;
				case 2:
					if ( is_user_logged_in() ) {
						$can_view = true;
					}
					break;
				case 3:
					if ( is_user_logged_in() ) {
						$privacy_roles = get_post_meta( $directory_id, '_um_privacy_roles', true );
						$privacy_roles = ! empty( $privacy_roles ) && is_array( $privacy_roles ) ? $privacy_roles : array();

						$current_user_roles = um_user( 'roles' );
						if ( ! empty( $current_user_roles ) && count( array_intersect( $current_user_roles, $privacy_roles ) ) > 0 ) {
							$can_view = true;
						}
					}
					break;
			}
		}

		return apply_filters( 'um_directory_user_can_view', $can_view, $directory_id, $user_id );
	}

	/**
	 * Getting member directory post ID via the hash.
	 * Hash is unique attr, which we use visible at frontend
	 *
	 * @param string $hash
	 *
	 * @return bool|int
	 */
	public function get_directory_by_hash( $hash ) {
		global $wpdb;

		$directory_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id
					FROM {$wpdb->postmeta}
					WHERE meta_key = '_um_directory_token' AND
						  meta_value = %s
					LIMIT 1",
				$hash
			)
		);

		if ( empty( $directory_id ) ) {
			// Fallback, use old value.
			$directory_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID
						FROM {$wpdb->posts}
						WHERE SUBSTRING( MD5( ID ), 11, 5 ) = %s",
					$hash
				)
			);
			if ( empty( $directory_id ) ) {
				return false;
			}
		}

		return (int) $directory_id;
	}

	/**
	 * Generate a secure random token for each directory
	 *
	 * @param int $id
	 *
	 * @return false|string
	 */
	public function set_directory_hash( $id ) {
		$unique_hash = wp_generate_password( 5, false );
		$result      = update_post_meta( $id, '_um_directory_token', $unique_hash );
		if ( false === $result ) {
			return false;
		}
		return $unique_hash;
	}

	/**
	 * @param $id
	 *
	 * @return bool|string
	 */
	public function get_directory_hash( $id ) {
		$hash = get_post_meta( $id, '_um_directory_token', true );
		if ( '' === $hash ) {
			// Set the hash if empty.
			$hash = $this->set_directory_hash( $id );
		}
		if ( empty( $hash ) ) {
			// Fallback, use old value.
			$hash = substr( md5( $id ), 10, 5 );
		}
		return $hash;
	}

	/**
	 * Generate a secure random token for each user card
	 *
	 * @param int $id
	 *
	 * @return false|string
	 */
	public function set_user_hash( $id ) {
		$unique_hash = wp_generate_password( 5, false );
		$result      = update_user_meta( $id, '_um_card_anchor_token', $unique_hash );
		if ( false === $result ) {
			return false;
		}
		return $unique_hash;
	}

	/**
	 * @param $id
	 *
	 * @return bool|string
	 */
	public function get_user_hash( $id ) {
		$hash = get_user_meta( $id, '_um_card_anchor_token', true );
		if ( '' === $hash ) {
			// Set the hash if empty.
			$hash = $this->set_user_hash( $id );
		}
		if ( empty( $hash ) ) {
			// Fallback, use old value.
			$hash = substr( md5( $id ), 10, 5 );
		}
		return $hash;
	}

	/**
	 * @return bool
	 */
	public function get_hide_in_members_default() {
		$default = false;
		$option  = UM()->options()->get( 'account_hide_in_directory_default' );
		if ( 'Yes' === $option ) {
			$default = true;
		}

		return apply_filters( 'um_member_directory_hide_in_members_default', $default );
	}

	/**
	 * Get view Type template
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_type_basename( $type ) {
		return apply_filters( "um_member_directory_{$type}_type_template_basename", '' );
	}

	/**
	 * @param int $user_id
	 * @param array $directory_data
	 *
	 * @return array
	 */
	public function build_user_card_data( $user_id, $directory_data ) {
		um_fetch_user( $user_id );

		$dropdown_actions = UM()->frontend()->users()->get_dropdown_items( $user_id, 'directory' );

		$can_edit = UM()->roles()->um_current_user_can( 'edit', $user_id );

		$this->init_image_sizing( $directory_data );

		// Replace hook 'um_members_just_after_name'
		ob_start();
		do_action( 'um_members_just_after_name', $user_id, $directory_data );
		$hook_just_after_name = ob_get_clean();

		// Replace hook 'um_members_after_user_name'
		ob_start();
		do_action( 'um_members_after_user_name', $user_id, $directory_data );
		$hook_after_user_name = ob_get_clean();

		$data_array = array(
			'id'                   => $user_id,
			'card_anchor'          => esc_html( $this->get_user_hash( $user_id ) ),
			'role'                 => is_user_logged_in() ? esc_html( um_user( 'role' ) ) : 'undefined', // make the role hidden for the nopriv requests.
			'account_status'       => is_user_logged_in() ? esc_html( UM()->common()->users()->get_status( $user_id ) ) : 'undefined', // make the status hidden for the nopriv requests.
			'account_status_name'  => is_user_logged_in() ? esc_html( UM()->common()->users()->get_status( $user_id, 'formatted' ) ) : __( 'Undefined', 'ultimate-member' ), // make the status hidden for the nopriv requests.
			'cover_photo'          => UM()->frontend()::layouts()::cover_photo( $user_id, array( 'size' => $this->cover_size ) ),
			'display_name'         => esc_html( um_user( 'display_name' ) ),
			'profile_url'          => esc_url( um_user_profile_url( $user_id ) ),
			'can_edit'             => (bool) $can_edit,
			'edit_profile_url'     => $can_edit ? esc_url( um_edit_profile_url( $user_id ) ) : '',
			'display_name_html'    => wp_kses( um_user( 'display_name', 'html' ), UM()->get_allowed_html( 'templates' ) ),
			'dropdown_actions'     => $dropdown_actions,
			'hook_just_after_name' => wp_kses( preg_replace( '/^\s+/im', '', $hook_just_after_name ), UM()->get_allowed_html( 'templates' ) ),
			'hook_after_user_name' => wp_kses( preg_replace( '/^\s+/im', '', $hook_after_user_name ), UM()->get_allowed_html( 'templates' ) ),
		);

		if ( ! empty( $directory_data['show_tagline'] ) ) {
			if ( ! empty( $directory_data['tagline_fields'] ) ) {
				$directory_data['tagline_fields'] = maybe_unserialize( $directory_data['tagline_fields'] );

				if ( is_array( $directory_data['tagline_fields'] ) ) {
					foreach ( $directory_data['tagline_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						if ( '_um_last_login' === $key ) {
							$show_last_login = get_user_meta( $user_id, 'um_show_last_login', true );
							if ( ! empty( $show_last_login ) && 'no' === $show_last_login[0] ) {
								continue;
							}
						}

						$value = um_filtered_value( $key );

						if ( ! $value ) {
							continue;
						}

						$data_array[ $key ] = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );
					}
				}
			}
		}

		if ( ! empty( $directory_data['show_userinfo'] ) ) {

			if ( ! empty( $directory_data['reveal_fields'] ) ) {

				$directory_data['reveal_fields'] = maybe_unserialize( $directory_data['reveal_fields'] );

				if ( is_array( $directory_data['reveal_fields'] ) ) {
					foreach ( $directory_data['reveal_fields'] as $key ) {
						if ( ! $key ) {
							continue;
						}

						if ( '_um_last_login' === $key ) {
							$show_last_login = get_user_meta( $user_id, 'um_show_last_login', true );
							if ( ! empty( $show_last_login ) && 'no' === $show_last_login[0] ) {
								continue;
							}
						}

						$value = um_filtered_value( $key );
						if ( ! $value ) {
							continue;
						}

						$label = UM()->fields()->get_label( $key );
						if ( in_array( $key, array( 'role_select', 'role_radio' ), true ) ) {
							$label = strtr(
								$label,
								array(
									' (Dropdown)' => '',
									' (Radio)'    => '',
								)
							);
						}

						$data_array[ "label_{$key}" ] = esc_html( $label );
						$data_array[ $key ]           = wp_kses( $value, UM()->get_allowed_html( 'templates' ) );
					}
				}
			}

			if ( ! empty( $directory_data['show_social'] ) ) {
				ob_start();
				UM()->fields()->show_social_urls( $user_id );
				$data_array['social_urls'] = ob_get_clean();
			}
		}

		$data_array = apply_filters( 'um_ajax_get_members_data', $data_array, $user_id, $directory_data );

		um_reset_user_clean();

		return $data_array;
	}

	private function pre_filter_query( $filter, $directory_data, $admin = false ) {
		global $wpdb;

		if ( 'role' === $filter ) {
			if ( $admin ) {
				$pre_query_results = array_reverse( get_editable_roles() );
			} else {
				$pre_query_results = UM()->roles()->get_roles();
			}
		} elseif ( 'last_login' === $filter ) {
			$join_clause  = '';
			$where_clause = '';
			if ( UM()->options()->get( 'account_hide_in_directory' ) && ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				$join_clause  .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:15:"hide_in_members";b:0;%';
			}

			if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				if ( empty( $join_clause ) ) {
					$join_clause .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				}
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:14:"account_status";s:8:"approved";%';
			}

			$pre_query_results = $wpdb->get_row(
				"SELECT DISTINCT COUNT(*) AS total,
						MIN(um.meta_value) AS min,
						MAX(um.meta_value) AS max
				FROM {$wpdb->usermeta} um
				{$join_clause}
				WHERE um.meta_key = '_um_last_login' AND
					  um.meta_value != ''
					  {$where_clause}",
				ARRAY_A
			);
		} elseif ( 'user_registered' === $filter ) {
			$join_clause  = '';
			$where_clause = '';
			if ( UM()->options()->get( 'account_hide_in_directory' ) && ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				$join_clause  .= "LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'um_member_directory_data'";
				$where_clause .= " AND um.meta_value LIKE '%" . 's:15:"hide_in_members";b:0;%';
			}

			if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				if ( empty( $join_clause ) ) {
					$join_clause .= "LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'um_member_directory_data'";
				}
				$where_clause .= " AND um.meta_value LIKE '%" . 's:14:"account_status";s:8:"approved";%';
			}

			$pre_query_results = $wpdb->get_col(
				"SELECT DISTINCT user_registered
				FROM {$wpdb->users} u
				{$join_clause}
				WHERE 1=1 {$where_clause}
				ORDER BY user_registered DESC"
			);
		} elseif ( 'birth_date' === $filter ) {
			$join_clause  = '';
			$where_clause = '';
			if ( UM()->options()->get( 'account_hide_in_directory' ) && ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				$join_clause  .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:15:"hide_in_members";b:0;%';
			}

			if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				if ( empty( $join_clause ) ) {
					$join_clause .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				}
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:14:"account_status";s:8:"approved";%';
			}

			$pre_query_results = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT MIN( um.meta_value ) as min_meta,
					MAX( um.meta_value ) as max_meta,
					COUNT( DISTINCT um.meta_value ) as amount
					FROM {$wpdb->usermeta} um
					{$join_clause}
					WHERE um.meta_key = %s AND
						  um.meta_value != ''
						  {$where_clause}",
					$filter
				),
				ARRAY_A
			);
		} elseif ( array_key_exists( $filter, $this->filter_types ) && 'slider' === $this->filter_types[ $filter ] ) {
			$join_clause  = '';
			$where_clause = '';
			if ( UM()->options()->get( 'account_hide_in_directory' ) && ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				$join_clause  .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:15:"hide_in_members";b:0;%';
			}

			if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				if ( empty( $join_clause ) ) {
					$join_clause .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				}
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:14:"account_status";s:8:"approved";%';
			}

			$pre_query_results = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT MIN( CONVERT( um.meta_value, DECIMAL ) ) as min_meta,
					MAX( CONVERT( um.meta_value, DECIMAL ) ) as max_meta,
					COUNT( DISTINCT um.meta_value ) as amount
					FROM {$wpdb->usermeta} um
					{$join_clause}
					WHERE um.meta_key = %s AND
						  um.meta_value != ''
						  {$where_clause}",
					$filter
				),
				ARRAY_A
			);
		} else {
			$join_clause  = '';
			$where_clause = '';
			if ( UM()->options()->get( 'account_hide_in_directory' ) && ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				$join_clause  .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:15:"hide_in_members";b:0;%';
			}

			if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
				if ( empty( $join_clause ) ) {
					$join_clause .= "LEFT JOIN {$wpdb->usermeta} um2 ON um2.user_id = um.user_id AND um2.meta_key = 'um_member_directory_data'";
				}
				$where_clause .= " AND um2.meta_value LIKE '%" . 's:14:"account_status";s:8:"approved";%';
			}

			$pre_query_results = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT um.meta_value
					FROM {$wpdb->usermeta} um
					{$join_clause}
					WHERE um.meta_key = %s AND
						  um.meta_value != ''
						  {$where_clause}
					ORDER BY um.meta_value DESC",
					$filter
				)
			);
		}

		return $pre_query_results;
	}

	/**
	 * @param string $filter
	 * @param array $directory_data
	 *
	 * @return mixed
	 */
	protected function slider_filters_range( $filter, $directory_data ) {
		$directory_id = $directory_data['form_id'];

		// Cannot disable pre query for filters where we need to get the min and max amount based on DB values.
		// So slider filter can have disabled pre query for filters only for the "Age" filter.
		$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );
		if ( true === $disable_filters_pre_query && 'birth_date' === $filter ) {
			// Set default age range in the case when pre query for filters is disabled.
			return array( 0, 150 );
		}

		$range = false;
		$meta  = $this->pre_filter_query( $filter, $directory_data );
		if ( isset( $meta['min_meta'], $meta['max_meta'], $meta['amount'] ) && $meta['amount'] > 1 ) {
			if ( 'birth_date' === $filter ) {
				$range = array( $this->borndate( strtotime( $meta['max_meta'] ) ), $this->borndate( strtotime( $meta['min_meta'] ) ) );
			} else {
				$range = array( (float) $meta['min_meta'], (float) $meta['max_meta'] );
			}
		}

		$range = apply_filters( 'um_member_directory_filter_slider_common', $range, $directory_data, $filter );
		return apply_filters( "um_member_directory_filter_{$filter}_slider", $range, $directory_data );
	}

	/**
	 * @param string $filter
	 * @param array  $attrs
	 *
	 * @return string[]
	 */
	protected function slider_range_placeholder( $filter, $attrs ) {
		if ( 'birth_date' === $filter ) {
			return array(
				__( '<strong>Age:</strong>&nbsp;{{{value}}} years old', 'ultimate-member' ),
				__( '<strong>Age:</strong>&nbsp;{{{value_from}}} - {{{value_to}}} years old', 'ultimate-member' ),
			);
		}

		$label        = ! empty( $attrs['label'] ) ? $attrs['label'] : $filter;
		$label        = ucwords( str_replace( array( 'um_', '_' ), array( '', ' ' ), $label ) );
		$placeholders = apply_filters( 'um_member_directory_filter_slider_range_placeholder', false, $filter );

		if ( false === $placeholders ) {
			if ( 'rating' === $attrs['type'] ) {
				return array(
					"<strong>$label:</strong>&nbsp;{{{value}}}" . __( ' stars', 'ultimate-member' ),
					"<strong>$label:</strong>&nbsp;{{{value_from}}} - {{{value_to}}}" . __( ' stars', 'ultimate-member' ),
				);
			}

			$placeholders = array(
				"<strong>$label:</strong>&nbsp;{{{value}}}",
				"<strong>$label:</strong>&nbsp;{{{value_from}}} - {{{value_to}}}",
			);
		}

		return $placeholders;
	}

	/**
	 * Handle members can view restrictions.
	 */
	protected function restriction_options() {
//		$this->hide_not_approved();
		$this->hide_by_role();

		do_action( 'um_member_directory_restrictions_handle_extend' );
	}

	/**
	 *
	 */
//	private function hide_not_approved() {
//		if ( UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
//			return;
//		}
//
//		$this->query_args['meta_query'] = array_merge(
//			$this->query_args['meta_query'],
//			array(
//				array(
//					'key'     => 'um_member_directory_data',
//					'value'   => 's:14:"account_status";s:8:"approved";',
//					'compare' => 'LIKE',
//				),
//			)
//		);
//	}

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
	 * @param string $filter
	 * @param array  $directory_data
	 *
	 * @return array
	 */
	public function datepicker_filters_range( $filter, $directory_data ) {
		$directory_id = $directory_data['form_id'];

		$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );
		if ( true === $disable_filters_pre_query ) {
			$min_date = apply_filters( 'um_member_directory_datepicker_filter_disabled_filters_pre_query_min_date', gmdate( 'Y-m-d', strtotime( '1900-01-01 00:00:00' ) ), $filter, $directory_data );
			return array( $min_date, gmdate( 'Y-m-d' ) ); // TODO check the format for min/max range in the new UI. gmdate for now.
		}

		$range = false;
		$meta  = $this->pre_filter_query( $filter, $directory_data );

		if ( 'last_login' === $filter ) {
			if ( array_key_exists( 'min', $meta ) && array_key_exists( 'max', $meta ) && ! empty( $meta['total'] ) && absint( $meta['total'] ) > 1 ) {
				// $range = array( strtotime( $meta['min'] ), strtotime( $meta['max'] ) );
				$range = array( $meta['min'], $meta['max'] ); // TODO check the format for min/max range in the new UI. gmdate for now.
			}
		} elseif ( ! empty( $meta ) && count( $meta ) > 1 ) {
			$range = array( min( $meta ), max( $meta ) ); // TODO check the format for min/max range in the new UI. gmdate for now.
		}

		return apply_filters( "um_member_directory_filter_{$filter}_datepicker", $range );
	}

	/**
	 * @param string $filter
	 * @param array  $directory_data
	 *
	 * @return array
	 */
	protected function timepicker_filters_range( $filter, $directory_data ) {
		$directory_id = $directory_data['form_id'];

		$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );
		if ( true === $disable_filters_pre_query ) {
			return array( '00:00', '23:59' );
		}

		$meta = $this->pre_filter_query( $filter, $directory_data );
		$meta = array_filter( $meta );

		$range = false;
		if ( ! empty( $meta ) && count( $meta ) > 1 ) {
			$range = array( min( $meta ), max( $meta ) );
		}

		return apply_filters( "um_member_directory_filter_{$filter}_timepicker", $range );
	}

	/**
	 * @param $borndate
	 *
	 * @return false|string
	 */
	private function borndate( $borndate ) {
		if ( date( 'm', $borndate ) > date( 'm' ) || ( date( 'm', $borndate ) === date( 'm' ) && date( 'd', $borndate ) > date( 'd' ) ) ) {
			return ( date( 'Y' ) - date( 'Y', $borndate ) - 1 );
		}
		return ( date( 'Y' ) - date( 'Y', $borndate ) );
	}

	/**
	 * Render member's directory filters.
	 *
	 * @param string $filter
	 * @param array  $directory_data
	 * @param mixed  $default_value
	 * @param bool   $admin
	 *
	 * @return string $filter
	 */
	public function show_filter( $filter, $directory_data, $default_value = false, $admin = false ) {
		if ( empty( $this->filter_types[ $filter ] ) ) {
			return '';
		}

		// Don't show filter (not select type) in the case when the same default filter is set from wp-admin
		if ( ! $admin && 'select' !== $this->filter_types[ $filter ] ) {
			$default_filters = array();
			if ( ! empty( $directory_data['search_filters'] ) ) {
				$default_filters = maybe_unserialize( $directory_data['search_filters'] );
			}

			if ( ! empty( $default_filters[ $filter ] ) ) {
				return '';
			}
		}

		$field_key = $filter;
		if ( 'last_login' === $filter ) {
			$field_key = '_um_last_login';
		} elseif ( 'role' === $filter ) {
			$field_key = 'role_select';
		}

		$fields = UM()->builtin()->all_user_fields;

		$attrs = isset( $fields[ $field_key ] ) ? $fields[ $field_key ] : array();
		/**
		 * Filters the field's data used in member directory filter.
		 * Where $filter is the filter key.
		 *
		 * @param {array}  $attrs     Filter's field data.
		 * @param {string} $field_key Field key.
		 *
		 * @return {array} Filter's field data.
		 *
		 * @since 2.0
		 * @hook um_custom_search_field_{$filter}
		 *
		 * @example <caption>Set data for the filter with `my_filter` filter.</caption>
		 * function my_filter_search_field( $attrs, $field_key ) {
		 *     // your code here
		 *     if ( 'exam_date' === $field_key ) {
		 *         $attrs = array(
		 *             'title'    => 'Exam Date'
		 *             'metakey'  => 'exam_date',
		 *             'type'     => 'date',
		 *             'label'    => 'Exam Date',
		 *             'editable' => true,
		 *         );
		 *     }
		 *     return $attrs;
		 * }
		 * add_filter( 'um_custom_search_field_my_filter', 'my_filter_search_field', 10, 2 );
		 */
		$attrs = apply_filters( "um_custom_search_field_$filter", $attrs, $field_key );

		// Skip private invisible fields
		if ( ! $admin && ! um_can_view_field( $attrs ) ) {
			return '';
		}

		/**
		 * Filters the field's data used in member directory filter.
		 *
		 * @param {array}  $attrs        Filter's field data.
		 * @param {string} $field_key    Field key.
		 * @param {int}    $directory_id Member Directory ID.
		 *
		 * @return {array} Filter's field data.
		 *
		 * @since 2.0
		 * @hook um_search_fields
		 *
		 * @example <caption>Change `Birth Date` filter's label.</caption>
		 * function my_filter_search_field( $attrs, $field_key, $directory_id ) {
		 *     // your code here
		 *     if ( 'birth_date' === $field_key ) {
		 *         $attrs['label'] = 'Birthday';
		 *     }
		 *     return $attrs;
		 * }
		 * add_filter( 'um_search_fields', 'my_filter_search_field', 10, 3 );
		 */
		$attrs = apply_filters( 'um_search_fields', $attrs, $field_key, $directory_data['form_id'] );

		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		switch ( $this->filter_types[ $filter ] ) {
			default:
				ob_start();
				do_action( "um_member_directory_filter_type_{$this->filter_types[ $filter ]}", $filter, $directory_data, $unique_hash, $attrs, $default_value );
				return ob_get_clean();

			case 'text':
				$label = '';
				if ( isset( $attrs['label'] ) ) {
					$label = $attrs['label'];
				} elseif ( isset( $attrs['title'] ) ) {
					$label = $attrs['title'];
				}

				$label = stripslashes( $label );

				$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : $default_value;

				ob_start();
				if ( $admin ) {
					?>
					<input type="text" autocomplete="off" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?>"
						placeholder="<?php echo esc_attr( $label ); ?>"
						value="<?php echo esc_attr( $filter_from_url ); ?>"
						aria-label="<?php echo esc_attr( $label ); ?>" />
					<?php
				} else {
					?>
					<div class="um-field-wrapper">
						<label for="<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( stripslashes( $label ) ); ?></label>
						<input type="text" autocomplete="off" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $filter ); ?>"
							placeholder="<?php echo esc_attr( $label ); ?>"
							value="<?php echo esc_attr( $filter_from_url ); ?>" class="um-search-filter-field"
							aria-label="<?php echo esc_attr( $label ); ?>" />
					</div>
					<?php
				}
				return ob_get_clean();

			case 'select':
				return $this->render_dropdown_filter( $attrs, $filter, $directory_data, $default_value, $admin );

			case 'slider':
				if ( $admin ) {
					return $this->render_slider_admin_filter( $filter, $default_value );
				}

				return $this->render_slider_filter( $attrs, $filter, $directory_data );

			case 'datepicker':
				if ( $admin ) {
					return $this->render_datepicker_admin_filter( $filter, $default_value );
				}
				return $this->render_datepicker_filter( $attrs, $filter, $directory_data );

			case 'timepicker':
				if ( $admin ) {
					return $this->render_timepicker_admin_filter( $filter, $default_value );
				}

				return $this->render_timepicker_filter( $attrs, $filter, $directory_data );

		}
	}

	/**
	 * @param array  $attrs
	 * @param string $filter
	 * @param array  $directory_data
	 * @param mixed  $default_value
	 * @param bool   $admin
	 *
	 * @return false|string
	 */
	private function render_dropdown_filter( $attrs, $filter, $directory_data, $default_value, $admin ) {
		$directory_id = $directory_data['form_id'];
		$unique_hash  = $this->get_directory_hash( $directory_id );
		$values_array = isset( $attrs['options'] ) ? $attrs['options'] : array(); // Fallback

		// getting value from GET line
		$filter_from_url = array();
		if ( ! $admin ) {
			$filter_from_url = isset( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ) : $filter_from_url;
			$filter_from_url = apply_filters( 'um_member_directory_filter_value_from_url', $filter_from_url, $attrs, $filter, $directory_data );
		}

		$disable_filters_pre_query = (bool) get_post_meta( $directory_id, '_um_disable_filters_pre_query', true );

		/** This filter is documented in includes/core/class-fields.php */
		$option_pairs    = apply_filters( 'um_select_options_pair', null, $attrs );
		$custom_dropdown = '';

		// Workaround for the first member directory page loading.
		// Can be required for some cases in the callback functions. E.g. Billing/Shipping Country -> State dependencies.
		$workaround_unset = false;
		if ( ! isset( $_POST['member_directory'] ) ) {
			$_POST['member_directory'] = true;
			$_POST['child_name']       = $filter;
			$workaround_unset          = true;
		}

		$choices_callback = UM()->fields()->get_custom_dropdown_options_source( $filter, $attrs );
		if ( ! empty( $choices_callback ) ) {
			$option_pairs     = true;
			$custom_dropdown .= ' data-um-ajax-source="' . esc_attr( $choices_callback ) . '" data-nonce="' . wp_create_nonce( 'um_dropdown_parent_nonce' . $attrs['metakey'] ) . '" ';
			if ( ! empty( $attrs['parent_dropdown_relationship'] ) ) {
				/** This filter is documented in includes/core/class-fields.php */
				$parent_dropdown_relationship = apply_filters( "um_custom_dropdown_options_parent__$filter", $attrs['parent_dropdown_relationship'], $attrs );

				$custom_dropdown .= ' data-um-parent="' . esc_attr( $parent_dropdown_relationship ) . '"';

				$parent_option     = array();
				$um_search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
				if ( $admin ) {
					if ( ! empty( $um_search_filters ) ) {
						$parent_option = isset( $um_search_filters[ $parent_dropdown_relationship ] ) ? $um_search_filters[ $parent_dropdown_relationship ] : $parent_option;
					}
				} else {
					$filter_value_key = 'filter_' . $parent_dropdown_relationship . '_' . $unique_hash;
					if ( isset( $_GET[ $filter_value_key ] ) ) {
						$parent_option_value = sanitize_text_field( $_GET[ $filter_value_key ] );
						$parent_option       = explode( '||', $parent_option_value );
					} elseif ( ! empty( $um_search_filters ) ) {
						// Case when display member directory filter in directory header, but the parent filter is set in Admin filtering.
						$parent_option = isset( $um_search_filters[ $parent_dropdown_relationship ] ) ? $um_search_filters[ $parent_dropdown_relationship ] : $parent_option;
					}
				}

				$attrs['options'] = $choices_callback( $parent_option, $parent_dropdown_relationship );
			} else {
				$attrs['options'] = $choices_callback();
			}

			if ( ! $admin && true !== $disable_filters_pre_query ) {
				if ( in_array( $attrs['type'], array( 'select', 'multiselect', 'checkbox', 'radio' ), true ) ) {
					$values_array = $this->pre_filter_query( $attrs['metakey'], $directory_data );
					if ( ! empty( $values_array ) ) {
						$values_array = array_map( 'maybe_unserialize', $values_array );
						$values_array = array_map(
							function ( $item ) {
								$item = maybe_unserialize( $item );
								return is_array( $item ) ? $item : (array) $item;
							},
							$values_array
						);
						$values_array = array_unique( array_merge( ...$values_array ) );
					}
				}

				$attrs['options'] = array_intersect_key( array_map( 'trim', $attrs['options'] ), array_flip( $values_array ) );
			}
		} else {
			if ( 'role' === $filter ) {
				$values_array    = array();
				$shortcode_roles = get_post_meta( $directory_id, '_um_roles', true );
				$shortcode_roles = maybe_unserialize( $shortcode_roles );
				if ( $admin ) {
					$editable_roles = array_reverse( get_editable_roles() );
					if ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {
						foreach ( $editable_roles as $role => $details ) {
							if ( in_array( $role, $shortcode_roles, true ) ) {
								$values_array[ $role ] = translate_user_role( $details['name'] );
							}
						}
					} else {
						foreach ( $editable_roles as $role => $details ) {
							$values_array[ $role ] = translate_user_role( $details['name'] );
						}
					}
				} else {
					$um_roles = UM()->roles()->get_roles();
					if ( true !== $disable_filters_pre_query ) {
						$users_roles = count_users();
						$roles_exist = ( ! empty( $users_roles['avail_roles'] ) && is_array( $users_roles['avail_roles'] ) ) ? array_keys( array_filter( $users_roles['avail_roles'] ) ) : array();

						foreach ( $um_roles as $key => $value ) {
							if ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {
								if ( in_array( $key, $roles_exist, true ) && in_array( $key, $shortcode_roles, true ) ) {
									$values_array[ $key ] = $value;
								}
							} elseif ( in_array( $key, $roles_exist, true ) ) {
								$values_array[ $key ] = $value;
							}
						}
					} elseif ( ! empty( $shortcode_roles ) && is_array( $shortcode_roles ) ) {
						foreach ( $um_roles as $key => $value ) {
							if ( in_array( $key, $shortcode_roles, true ) ) {
								$values_array[ $key ] = $value;
							}
						}
					} else {
						$values_array = $um_roles;
					}
				}

				$attrs['options'] = $values_array;
			}

			if ( ! $admin && true !== $disable_filters_pre_query && empty( $attrs['disable_filters_pre_query'] ) ) {
				$values_array = $this->pre_filter_query( $attrs['metakey'], $directory_data );

				if ( ! empty( $values_array ) && in_array( $attrs['type'], array( 'select', 'multiselect', 'checkbox', 'radio' ), true ) ) {
					$values_array = array_map(
						function ( $item ) {
							$item = maybe_unserialize( $item );
							return is_array( $item ) ? $item : (array) $item;
						},
						$values_array
					);
					$values_array = array_unique( array_merge( ...$values_array ) );
				}

				if ( empty( $option_pairs ) ) {
					$attrs['options'] = array_intersect( array_map( 'stripslashes', array_map( 'trim', $attrs['options'] ) ), $values_array );
				} else {
					$attrs['options'] = array_intersect_key( array_map( 'trim', $attrs['options'] ), array_flip( $values_array ) );
				}
			}
		}

		// Workaround for the first member directory page loading.
		// Can be required for some cases in the callback functions. E.g. Billing/Shipping Country -> State dependencies.
		if ( $workaround_unset ) {
			unset( $_POST['member_directory'], $_POST['child_name'] );
		}

		$options          = array_key_exists( 'options', $attrs ) ? $attrs['options'] : array();
		$attrs['options'] = apply_filters( 'um_member_directory_filter_select_options', $options, $values_array, $attrs, $directory_data );
		if ( empty( $attrs['options'] ) || ! is_array( $attrs['options'] ) ) {
			return '';
		}

		// Intersect options array with the default filter options
		if ( ! $admin && ! empty( $directory_data['search_filters'] ) ) {
			$default_filters = maybe_unserialize( $directory_data['search_filters'] );

			if ( ! empty( $default_filters[ $filter ] ) ) {
				$attrs['options'] = array_intersect( $attrs['options'], $default_filters[ $filter ] );
			}
		}

		ksort( $attrs['options'] );

		$attrs['options'] = apply_filters( 'um_member_directory_filter_select_options_sorted', $attrs['options'], $attrs, $directory_data );

		if ( empty( $attrs['options'] ) ) {
			return '';
		}

		if ( isset( $attrs['label'] ) ) {
			$attrs['label'] = wp_strip_all_tags( $attrs['label'] );
		}

		$label = '';
		if ( isset( $attrs['label'] ) ) {
			$label = $attrs['label'];
		} elseif ( isset( $attrs['title'] ) ) {
			$label = $attrs['title'];
		}
		$label = stripslashes( $label );

		$dropdown_class = '';
		if ( true !== $admin ) {
			$dropdown_class = 'um-field-wrapper';
		}

		$orig_name = $filter;
		$name      = $filter;
		if ( $admin && count( $attrs['options'] ) > 1 ) {
			$name .= '[]';
		}
		ob_start();

		if ( ! $admin ) {
		?>
		<div class="<?php echo esc_attr( $dropdown_class ); ?>">
			<label for="<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( $label ); ?></label>
		<?php } ?>
			<select multiple class="js-choice um-search-filter-field" id="<?php echo esc_attr( $filter ); ?>" name="<?php echo esc_attr( $name ); ?>" data-orig-name="<?php echo esc_attr( $orig_name ); ?>"
				aria-label="<?php echo esc_attr( $label ); ?>" <?php echo $custom_dropdown; ?>>
				<?php
				foreach ( $attrs['options'] as $k => $v ) {
					$opt = stripslashes( $v );
					if ( ! empty( $option_pairs ) || 'role' === $filter ) {
						$opt = $k;
					}

					if ( $admin ) {
						if ( ! is_array( $default_value ) ) {
							$default_value = array( $default_value );
						}

						// Convert any int option to the string to strict compare.
						$selected = in_array( (string) $opt, $default_value, true );
					} else {
						$selected = ( ! empty( $filter_from_url ) && in_array( (string) $opt, $filter_from_url, true ) );
					}
					?>
					<option value="<?php echo esc_attr( $opt ); ?>" data-value_label="<?php echo esc_attr( $v ); ?>" <?php selected( $selected ); ?>>
						<?php echo esc_html( $v ); ?>
					</option>
					<?php
				}
				?>
			</select>
		<?php if ( ! $admin ) { ?>
		</div>
		<?php
		}
		return ob_get_clean();
	}

	private function render_slider_admin_filter( $filter, $default_value ) {
		$value = array( 0, 0 );
		if ( $default_value ) {
			$value = $default_value;
		}
		ob_start();
		?>
		<div>
			<label for="<?php echo esc_attr( $filter . '_min' ); ?>"><?php esc_html_e( 'From', 'ultimate-member' ); ?></label>
			<input type="number" autocomplete="off" id="<?php echo esc_attr( $filter . '_min' ); ?>" name="<?php echo esc_attr( $filter . '_min' ); ?>"
				value="<?php echo esc_attr( min( $value ) ); ?>" class="um-search-filter-field" />
		</div>
		<div>
			<label for="<?php echo esc_attr( $filter . '_max' ); ?>"><?php esc_html_e( 'To', 'ultimate-member' ); ?></label>
			<input type="number" autocomplete="off" id="<?php echo esc_attr( $filter . '_max' ); ?>" name="<?php echo esc_attr( $filter . '_max' ); ?>"
				value="<?php echo esc_attr( max( $value ) ); ?>" class="um-search-filter-field" />
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_slider_filter( $attrs, $filter, $directory_data ) {
		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		// Ignore '_um_disable_filters_pre_query' meta here because range can be different for different fields and need to know the real range or hide this filter.
		// Only the "Age" filter can display different default range when filter pre-queries are disabled.
		$range = $this->slider_filters_range( $filter, $directory_data );
		if ( ! $range ) {
			return '';
		}

		$label = '';
		if ( isset( $attrs['label'] ) ) {
			$label = $attrs['label'];
		} elseif ( ! isset( $attrs['label'] ) && isset( $attrs['title'] ) ) {
			$label = $attrs['title'];
		}

		$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : $range[0];
		$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) : $range[1];

		$value = array( $filter_from_url_from, $filter_from_url_to );

		list( $single_placeholder, $plural_placeholder ) = $this->slider_range_placeholder( $filter, $attrs );

		return UM()->frontend()::layouts()::range(
			array(
				'label'       => stripslashes( $label ),
				'name'        => $filter,
				'classes'     => array(
					'from' => array( 'um-search-filter-field' ),
					'to'   => array( 'um-search-filter-field' ),
				),
				'value'       => $value,
				'min'         => $range[0],
				'max'         => $range[1],
				'placeholder' => array(
					'single' => $single_placeholder,
					'plural' => $plural_placeholder,
				),
			)
		);
	}

	private function render_datepicker_admin_filter( $filter, $default_value ) {
		$value = array( gmdate( 'Y-m-d' ), gmdate( 'Y-m-d' ) );
		if ( $default_value ) {
			// Backward compatibility for dates in format Y/m/d.
			foreach ( $default_value as &$v ) {
				if ( strpos( $v, '/' ) ) {
					$v = gmdate( 'Y-m-d', strtotime( $v ) );
				}
			}
			unset( $v );

			$value = $default_value;
		}
		ob_start();
		?>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_from' ); ?>"><?php esc_html_e( 'From', 'ultimate-member' ); ?></label>
			<input type="date" id="<?php echo esc_attr( $filter . '_from' ); ?>" name="<?php echo esc_attr( $filter . '_from' ); ?>" data-range="from" value="<?php echo esc_attr( min( $value ) ); ?>" />
		</div>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_to' ); ?>"><?php esc_html_e( 'To', 'ultimate-member' ); ?></label>
			<input type="date" id="<?php echo esc_attr( $filter . '_to' ); ?>" name="<?php echo esc_attr( $filter . '_to' ); ?>" data-range="to" value="<?php echo esc_attr( max( $value ) ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_datepicker_filter( $attrs, $filter, $directory_data ) {
		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		$range = $this->datepicker_filters_range( $filter, $directory_data );
		if ( ! $range ) {
			return '';
		}

		$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
		$label = stripslashes( $label );

		list( $min, $max ) = $range;

		$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : '';
		$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) : '';

		$value = array( $filter_from_url_from, $filter_from_url_to );

		return UM()->frontend()::layouts()::date_range(
			array(
				'id'      => $filter,
				'name'    => $filter,
				'label'   => $label,
				'value'   => $value,
				'min'     => $min,
				'max'     => $max,
				'classes' => array(
					'from' => array( 'um-search-filter-field' ),
					'to'   => array( 'um-search-filter-field' ),
				),
			)
		);
	}

	private function render_timepicker_admin_filter( $filter, $default_value ) {
		$value = array( '00:00', '23:59' );
		if ( $default_value ) {
			$value = $default_value;
		}
		ob_start();
		?>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_from' ); ?>"><?php esc_html_e( 'From', 'ultimate-member' ); ?></label>
			<input type="time" id="<?php echo esc_attr( $filter . '_from' ); ?>" name="<?php echo esc_attr( $filter . '_from' ); ?>" data-range="from" value="<?php echo esc_attr( min( $value ) ); ?>" />
		</div>
		<div class="um-date-range-row">
			<label for="<?php echo esc_attr( $filter . '_to' ); ?>"><?php esc_html_e( 'To', 'ultimate-member' ); ?></label>
			<input type="time" id="<?php echo esc_attr( $filter . '_to' ); ?>" name="<?php echo esc_attr( $filter . '_to' ); ?>" data-range="to" value="<?php echo esc_attr( max( $value ) ); ?>" />
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_timepicker_filter( $attrs, $filter, $directory_data ) {
		$unique_hash = $this->get_directory_hash( $directory_data['form_id'] );

		$range = $this->timepicker_filters_range( $filter, $directory_data );
		if ( ! $range ) {
			return '';
		}

		$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $attrs['title'];
		$label = stripslashes( $label );

		list( $min, $max ) = $range;

		$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : '';
		$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $unique_hash ] ) : '';

		$value = array( $filter_from_url_from, $filter_from_url_to );

		return UM()->frontend()::layouts()::time_range(
			array(
				'id'      => $filter,
				'name'    => $filter,
				'label'   => $label,
				'value'   => $value,
				'min'     => $min,
				'max'     => $max,
				'classes' => array(
					'from' => array( 'um-search-filter-field' ),
					'to'   => array( 'um-search-filter-field' ),
				),
			)
		);
	}
}
