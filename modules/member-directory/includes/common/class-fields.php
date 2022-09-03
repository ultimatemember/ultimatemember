<?php
namespace umm\member_directory\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Fields
 *
 * @package umm\member_directory\includes\common
 */
class Fields {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'um_delete_custom_field', array( &$this, 'delete_custom_field' ), 10, 1 );
		add_filter( 'um_predefined_fields_hook', array( &$this, 'add_fields' ), 10, 1 );
		add_action( 'um_add_new_field', array( &$this, 'on_new_field_added' ), 10, 2 );
		add_action( 'um_delete_custom_field', array( &$this, 'on_delete_custom_field' ), 10, 2 );
	}


	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	function add_fields( $fields ) {
		$fields['hide_in_members'] = array(
			'title'        => __( 'Hide my profile from directory', 'ultimate-member' ),
			'metakey'      => 'hide_in_members',
			'type'         => 'radio',
			'label'        => __( 'Hide my profile from directory', 'ultimate-member' ),
			'help'         => __( 'Here you can hide yourself from appearing in public directory', 'ultimate-member' ),
			'required'     => 0,
			'public'       => 1,
			'editable'     => 1,
			'default'      => UM()->module( 'member-directory' )->get_hide_in_members_default() ? 'Yes' : 'No',
			'options'      => array(
				'No'  => __( 'No', 'ultimate-member' ),
				'Yes' => __( 'Yes', 'ultimate-member' ),
			),
			'account_only' => true,
		);

		$account_hide_in_directory = UM()->options()->get( 'account_hide_in_directory' );
		$account_hide_in_directory = apply_filters( 'um_account_hide_in_members_visibility', $account_hide_in_directory );

		if ( ! $account_hide_in_directory ) {
			unset( $fields['hide_in_members'] );
		}

		return $fields;
	}


	/**
	 * @param string $key
	 */
	function delete_custom_field( $key ) {
		global $wpdb;

		// delete field from Member Directories meta
		$directories = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'um_directory'" );
		foreach ( $directories as $directory_id ) {
			// Frontend filters
			$directory_search_fields = get_post_meta( $directory_id, '_um_search_fields', true );
			if ( empty( $directory_search_fields ) ) {
				$directory_search_fields = array();
			}
			$directory_search_fields = array_values( array_diff( $directory_search_fields, array( $key ) ) );
			update_post_meta( $directory_id, '_um_search_fields', $directory_search_fields );

			// Admin filtering
			$directory_search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
			if ( isset( $directory_search_filters[ $key ] ) ) {
				unset( $directory_search_filters[ $key ] );
			}
			update_post_meta( $directory_id, '_um_search_filters', $directory_search_filters );

			// display in tagline
			$directory_reveal_fields = get_post_meta( $directory_id, '_um_reveal_fields', true );
			if ( empty( $directory_reveal_fields ) ) {
				$directory_reveal_fields = array();
			}
			$directory_reveal_fields = array_values( array_diff( $directory_reveal_fields, array( $key ) ) );
			update_post_meta( $directory_id, '_um_reveal_fields', $directory_reveal_fields );

			// extra user information section
			$directory_tagline_fields = get_post_meta( $directory_id, '_um_tagline_fields', true );
			if ( empty( $directory_tagline_fields ) ) {
				$directory_tagline_fields = array();
			}
			$directory_tagline_fields = array_values( array_diff( $directory_tagline_fields, array( $key ) ) );
			update_post_meta( $directory_id, '_um_tagline_fields', $directory_tagline_fields );

			// Custom fields selected in "Choose field(s) to enable in sorting"
			$directory_sorting_fields = get_post_meta( $directory_id, '_um_sorting_fields', true );
			if ( empty( $directory_sorting_fields ) ) {
				$directory_sorting_fields = array();
			}
			foreach ( $directory_sorting_fields as $k => $sorting_data ) {
				if ( is_array( $sorting_data ) && array_key_exists( $key, $sorting_data ) ) {
					unset( $directory_sorting_fields[ $k ] );
				}
			}
			$directory_sorting_fields = array_values( $directory_sorting_fields );
			update_post_meta( $directory_id, '_um_sorting_fields', $directory_sorting_fields );

			// If "Default sort users by" = "Other (Custom Field)" is selected when delete this custom field and set default sorting
			$directory_sortby_custom = get_post_meta( $directory_id, '_um_sortby_custom', true );
			if ( $directory_sortby_custom === $key ) {
				$directory_sortby = get_post_meta( $directory_id, '_um_sortby', true );
				if ( 'other' === $directory_sortby ) {
					update_post_meta( $directory_id, '_um_sortby', 'user_registered_desc' );
				}
				update_post_meta( $directory_id, '_um_sortby_custom', '' );
				update_post_meta( $directory_id, '_um_sortby_custom_label', '' );
			}
		}
	}


	/**
	 * Delete custom field and metakey from UM usermeta table
	 *
	 * @param string $metakey
	 * @param array $args
	 */
	function on_delete_custom_field( $metakey, $args ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

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
	 * @param string $metakey
	 * @param array $args
	 */
	function on_new_field_added( $metakey, $args ) {
		$search_in_table = UM()->options()->get( 'member_directory_own_table' );
		if ( empty( $search_in_table ) ) {
			return;
		}

		$metakeys = get_option( 'um_usermeta_fields', array() );

		if ( ! in_array( $metakey, $metakeys ) ) {
			$metakeys[] = $metakey;
			update_option( 'um_usermeta_fields', array_values( $metakeys ) );
		}

		do_action( 'um_metadata_on_new_field_added', $metakeys, $metakey, $args );
	}
}
