<?php
namespace umm\member_directory\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class User
 *
 * @package umm\member_directory\includes\common
 */
class User {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'updated_user_meta', array( &$this, 'update_md_data_when_usermeta_update' ), 10, 4 );
		add_action( 'added_user_meta', array( &$this, 'update_md_data_when_usermeta_update' ), 10, 4 );
		add_action( 'deleted_user_meta', array( &$this, 'update_md_data_when_usermeta_delete' ), 10, 4 );

		add_action( 'updated_user_meta', array( &$this, 'update_um_metadata' ), 10, 4 );
		add_action( 'added_user_meta', array( &$this, 'update_um_metadata' ), 10, 4 );
		add_action( 'deleted_user_meta', array( &$this, 'delete_um_metadata' ), 10, 4 );
	}


	/**
	 * When you delete usermeta connected with member directory - reset it to  default value
	 *
	 * @param int|array $meta_ids
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	function update_md_data_when_usermeta_delete( $meta_ids, $object_id, $meta_key, $_meta_value ) {
		$metakeys = array( 'account_status', 'hide_in_members', 'synced_gravatar_hashed_id', 'synced_profile_photo', 'profile_photo', 'cover_photo', '_um_verified' );
		if ( ! in_array( $meta_key, $metakeys ) ) {
			return;
		}

		$md_data = get_user_meta( $object_id, 'um_member_directory_data', true );
		if ( empty( $md_data ) ) {
			$md_data = array(
				'account_status'  => 'approved',
				'hide_in_members' => UM()->module( 'member-directory' )->get_hide_in_members_default(),
				'profile_photo'   => false,
				'cover_photo'     => false,
				'verified'        => false,
			);
		}

		switch ( $meta_key ) {
			case 'account_status':
				$md_data['account_status'] = 'approved';
				break;
			case 'hide_in_members':
				$md_data['hide_in_members'] = UM()->module( 'member-directory' )->get_hide_in_members_default();
				break;
			case 'synced_gravatar_hashed_id':
				if ( UM()->options()->get( 'use_gravatars' ) ) {
					$profile_photo = get_user_meta( $object_id, 'profile_photo', true );
					$synced_profile_photo = get_user_meta( $object_id, 'synced_profile_photo', true );

					$md_data['profile_photo'] = ! empty( $profile_photo ) || ! empty( $synced_profile_photo );
				}

				break;
			case 'synced_profile_photo':
				$profile_photo = get_user_meta( $object_id, 'profile_photo', true );

				$synced_gravatar_hashed_id = false;
				if ( UM()->options()->get( 'use_gravatars' ) ) {
					$synced_gravatar_hashed_id = get_user_meta( $object_id, 'synced_gravatar_hashed_id', true );
				}

				$md_data['profile_photo'] = ! empty( $profile_photo ) || ! empty( $synced_gravatar_hashed_id );
				break;
			case 'profile_photo':
				$synced_profile_photo = get_user_meta( $object_id, 'synced_profile_photo', true );

				$synced_gravatar_hashed_id = false;
				if ( UM()->options()->get( 'use_gravatars' ) ) {
					$synced_gravatar_hashed_id = get_user_meta( $object_id, 'synced_gravatar_hashed_id', true );
				}

				$md_data['profile_photo'] = ! empty( $synced_profile_photo ) || ! empty( $synced_gravatar_hashed_id );
				break;
			case 'cover_photo':
				$md_data['cover_photo'] = false;
				break;
			case '_um_verified':
				$md_data['verified'] = false;
				break;
		}

		update_user_meta( $object_id, 'um_member_directory_data', $md_data );
	}


	/**
	 * When you add/update usermeta connected with member directories - set this data to member directory metakey
	 *
	 * @param int $meta_id
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	function update_md_data_when_usermeta_update( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$metakeys = array( 'account_status', 'hide_in_members', 'synced_gravatar_hashed_id', 'synced_profile_photo', 'profile_photo', 'cover_photo', '_um_verified' );
		if ( ! in_array( $meta_key, $metakeys ) ) {
			return;
		}

		$md_data = get_user_meta( $object_id, 'um_member_directory_data', true );
		if ( empty( $md_data ) ) {
			$md_data = array(
				'account_status'    => 'approved',
				'hide_in_members'   => UM()->module( 'member-directory' )->get_hide_in_members_default(),
				'profile_photo'     => false,
				'cover_photo'       => false,
				'verified'          => false,
			);
		}

		switch ( $meta_key ) {
			case 'account_status':
				$md_data['account_status'] = $_meta_value;
				break;
			case 'hide_in_members':

				$hide_in_members = UM()->module( 'member-directory' )->get_hide_in_members_default();
				if ( ! empty( $_meta_value ) ) {
					if ( $_meta_value == 'Yes' || $_meta_value == __( 'Yes', 'ultimate-member' ) ||
					     array_intersect( array( 'Yes', __( 'Yes', 'ultimate-member' ) ), $_meta_value ) ) {
						$hide_in_members = true;
					} else {
						$hide_in_members = false;
					}
				}

				$md_data['hide_in_members'] = $hide_in_members;

				break;
			case 'synced_gravatar_hashed_id':
				if ( UM()->options()->get( 'use_gravatars' ) ) {
					if ( empty( $md_data['profile_photo'] ) ) {
						$md_data['profile_photo'] = ! empty( $_meta_value );
					}
				}

				break;
			case 'synced_profile_photo':
			case 'profile_photo':
				if ( empty( $md_data['profile_photo'] ) ) {
					$md_data['profile_photo'] = ! empty( $_meta_value );
				}
				break;
			case 'cover_photo':
				$md_data['cover_photo'] = ! empty( $_meta_value );
				break;
			case '_um_verified':
				$md_data['verified'] = $_meta_value == 'verified' ? true : false;
				break;
		}

		update_user_meta( $object_id, 'um_member_directory_data', $md_data );
	}


	/**
	 * When you delete usermeta - remove row from um_metadata
	 *
	 * @param int|array $meta_ids
	 * @param int $object_id
	 * @param string $meta_key
	 * @param mixed $_meta_value
	 */
	function delete_um_metadata( $meta_ids, $object_id, $meta_key, $_meta_value ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

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
	function update_um_metadata( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

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
}
