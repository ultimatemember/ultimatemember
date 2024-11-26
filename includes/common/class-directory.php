<?php
namespace um\common;

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
	 * Getting member directory post ID via the hash.
	 * Hash is unique attr, which we use visible at frontend
	 *
	 * @param string $hash
	 *
	 * @return bool|int
	 */
	public function get_directory_by_hash( $hash ) {
		global $wpdb;

		$directory_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE SUBSTRING( MD5( ID ), 11, 5 ) = %s", $hash ) );

		if ( empty( $directory_id ) ) {
			return false;
		}

		return (int) $directory_id;
	}

	/**
	 * @param $form_id
	 *
	 * @return bool|string
	 */
	public function get_directory_hash( $form_id ) {
		return substr( md5( $form_id ), 10, 5 );
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
			'card_anchor'          => esc_html( substr( md5( $user_id ), 10, 5 ) ),
			'id'                   => absint( $user_id ),
			'role'                 => esc_html( um_user( 'role' ) ),
			'account_status'       => esc_html( um_user( 'account_status' ) ),
			'account_status_name'  => esc_html( um_user( 'account_status_name' ) ),
			'cover_photo'          => wp_kses( um_user( 'cover_photo', $this->cover_size ), UM()->get_allowed_html( 'templates' ) ),
			'display_name'         => esc_html( um_user( 'display_name' ) ),
			'profile_url'          => esc_url( um_user_profile_url() ),
			'can_edit'             => (bool) $can_edit,
			'edit_profile_url'     => esc_url( um_edit_profile_url() ),
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

	/**
	 * @param string $filter
	 * @param array $directory_data
	 *
	 * @return mixed
	 */
	protected function slider_filters_range( $filter, $directory_data ) {
		global $wpdb;

		$range = false;

		switch ( $filter ) {

			default: {

				$meta = $wpdb->get_row( $wpdb->prepare(
					"SELECT MIN( CONVERT( meta_value, DECIMAL ) ) as min_meta,
						MAX( CONVERT( meta_value, DECIMAL ) ) as max_meta,
						COUNT( DISTINCT meta_value ) as amount
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s",
					$filter
				), ARRAY_A );

				if ( isset( $meta['min_meta'] ) && isset( $meta['max_meta'] ) && isset( $meta['amount'] ) && $meta['amount'] > 1 ) {
					$range = array( (float) $meta['min_meta'], (float) $meta['max_meta'] );
				}

				$range = apply_filters( 'um_member_directory_filter_slider_common', $range, $directory_data, $filter );
				$range = apply_filters( "um_member_directory_filter_{$filter}_slider", $range, $directory_data );

				break;
			}
			case 'birth_date': {

//					$meta = $wpdb->get_col(
//						"SELECT meta_value
//						FROM {$wpdb->usermeta}
//						WHERE meta_key = 'birth_date' AND
//						      meta_value != ''"
//					);
//
//					if ( empty( $meta ) || count( $meta ) < 2 ) {
//						$range = false;
//					} elseif ( is_array( $meta ) ) {
//						$birth_dates = array_filter( array_map( 'strtotime', $meta ), 'is_numeric' );
//						sort( $birth_dates );
//						$min_meta = array_shift( $birth_dates );
//						$max_meta = array_pop( $birth_dates );
//						$range = array( $this->borndate( $max_meta ), $this->borndate( $min_meta ) );
//					}

				$meta = $wpdb->get_row(
					"SELECT MIN( meta_value ) as min_meta,
						MAX( meta_value ) as max_meta,
						COUNT( DISTINCT meta_value ) as amount
						FROM {$wpdb->usermeta}
						WHERE meta_key = 'birth_date' AND
							  meta_value != ''",
					ARRAY_A );

				if ( isset( $meta['min_meta'] ) && isset( $meta['max_meta'] ) && isset( $meta['amount'] ) && $meta['amount'] > 1 ) {
					$range = array( $this->borndate( strtotime( $meta['max_meta'] ) ), $this->borndate( strtotime( $meta['min_meta'] ) ) );
				}

				break;
			}

		}

		return $range;
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
	 * @param $filter
	 *
	 * @return mixed
	 */
	public function datepicker_filters_range( $filter ) {
		global $wpdb;

		switch ( $filter ) {
			default:
				global $wpdb;
				$meta = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value
						FROM {$wpdb->usermeta}
						WHERE meta_key = %s
						ORDER BY meta_value DESC", $filter ) );

				if ( empty( $meta ) || count( $meta ) === 1 ) {
					$range = false;
				} elseif ( ! empty( $meta ) ) {
					$range = array( strtotime( min( $meta ) ), strtotime( max( $meta ) ) );
				}

				$range = apply_filters( "um_member_directory_filter_{$filter}_datepicker", $range );

				break;
			case 'last_login':
				$meta = $wpdb->get_row(
					"SELECT DISTINCT COUNT(*) AS total,
							MIN(meta_value) AS min,
							MAX(meta_value) AS max
						FROM {$wpdb->usermeta}
						WHERE meta_key = '_um_last_login'",
					ARRAY_A
				);
				if ( empty( $meta['total'] ) || 1 === absint( $meta['total'] ) ) {
					$range = false;
				} elseif ( array_key_exists( 'min', $meta ) && array_key_exists( 'max', $meta ) ) {
					$range = array( strtotime( $meta['min'] ), strtotime( $meta['max'] ) );
				}
				break;
			case 'user_registered':
				$meta = $wpdb->get_col(
					"SELECT DISTINCT user_registered
						FROM {$wpdb->users}
						ORDER BY user_registered DESC"
				);

				if ( empty( $meta ) || count( $meta ) === 1 ) {
					$range = false;
				} elseif ( ! empty( $meta ) ) {
					$range = array( strtotime( min( $meta ) ), strtotime( max( $meta ) ) );
				}

				break;
		}

		return $range;
	}

	/**
	 * @param $filter
	 *
	 * @return mixed
	 */
	protected function timepicker_filters_range( $filter ) {
		global $wpdb;
		$meta = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_value
					FROM {$wpdb->usermeta}
					WHERE meta_key = %s
					ORDER BY meta_value DESC",
				$filter
			)
		);

		$meta = array_filter( $meta );

		if ( empty( $meta ) || count( $meta ) === 1 ) {
			$range = false;
		} elseif ( ! empty( $meta ) ) {
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
}
