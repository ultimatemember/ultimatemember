<?php
namespace umm\member_directory\includes\ajax;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Settings
 * @package umm\member_directory\includes\ajax
 */
class Settings {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_member_directory_default_filter_settings', array( &$this, 'default_filter_settings' ) );
		add_action( 'um_same_page_update_ajax_um_usermeta_fields', array( &$this, 'same_page_update_ajax_um_usermeta_fields' ) );
		add_action( 'um_same_page_update_ajax_um_get_metadata', array( &$this, 'same_page_update_ajax_um_get_metadata' ) );
		add_action( 'um_same_page_update_ajax_um_update_metadata_per_page', array( &$this, 'same_page_update_ajax_um_update_metadata_per_page' ) );
	}


	function same_page_update_ajax_um_usermeta_fields() {
		//first install metatable
		global $wpdb;

		$metakeys = array();
		foreach ( UM()->builtin()->all_user_fields as $all_user_field ) {
			$metakeys[] = $all_user_field['metakey'];
		}

		$metakeys = apply_filters( 'um_metadata_same_page_update_ajax', $metakeys, UM()->builtin()->all_user_fields );

		if ( is_multisite() ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $blog_id ) {
				$metakeys[] = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
			}
		} else {
			$blog_id    = get_current_blog_id();
			$metakeys[] = $wpdb->get_blog_prefix( $blog_id ) . 'capabilities';
		}

		//member directory data
		$metakeys[] = 'um_member_directory_data';
		$metakeys[] = '_um_verified';
		$metakeys[] = '_money_spent';
		$metakeys[] = '_completed';
		$metakeys[] = '_reviews_avg';

		//myCred meta
		if ( function_exists( 'mycred_get_types' ) ) {
			$mycred_types = mycred_get_types();
			if ( ! empty( $mycred_types ) ) {
				foreach ( array_keys( $mycred_types ) as $point_type ) {
					$metakeys[] = $point_type;
				}
			}
		}

		$sortby_custom_keys = $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_um_sortby_custom'" );
		if ( empty( $sortby_custom_keys ) ) {
			$sortby_custom_keys = array();
		}

		$sortby_custom_keys2 = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_um_sorting_fields'" );
		if ( ! empty( $sortby_custom_keys2 ) ) {
			foreach ( $sortby_custom_keys2 as $custom_val ) {
				$custom_val = maybe_unserialize( $custom_val );

				foreach ( $custom_val as $sort_value ) {
					if ( is_array( $sort_value ) ) {
						$field_keys           = array_keys( $sort_value );
						$sortby_custom_keys[] = $field_keys[0];
					}
				}
			}
		}

		if ( ! empty( $sortby_custom_keys ) ) {
			$sortby_custom_keys = array_unique( $sortby_custom_keys );
			$metakeys           = array_merge( $metakeys, $sortby_custom_keys );
		}

		$skip_fields = UM()->builtin()->get_fields_without_metakey();
		$skip_fields = array_merge( $skip_fields, UM()->module( 'member-directory' )->config()->get( 'core_search_fields' ) );

		$real_usermeta = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta}" );
		$real_usermeta = ! empty( $real_usermeta ) ? $real_usermeta : array();
		$real_usermeta = array_merge( $real_usermeta, array( 'um_member_directory_data' ) );

		if ( ! empty( $sortby_custom_keys ) ) {
			$real_usermeta = array_merge( $real_usermeta, $sortby_custom_keys );
		}

		$wp_usermeta_option = array_intersect( array_diff( $metakeys, $skip_fields ), $real_usermeta );

		update_option( 'um_usermeta_fields', array_values( $wp_usermeta_option ) );

		update_option( 'um_member_directory_update_meta', time() );

		UM()->options()->update( 'member_directory_own_table', true );

		wp_send_json_success();
	}


	function same_page_update_ajax_um_get_metadata() {
		global $wpdb;

		$wp_usermeta_option = get_option( 'um_usermeta_fields', array() );

		$count = $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->usermeta}
			WHERE meta_key IN ('" . implode( "','", $wp_usermeta_option ) . "')"
		);

		wp_send_json_success( array( 'count' => $count ) );
	}


	function same_page_update_ajax_um_update_metadata_per_page() {
		if ( empty( $_POST['page'] ) ) {
			wp_send_json_error( __( 'Wrong data', 'ultimate-member' ) );
		}

		$per_page           = 500;
		$wp_usermeta_option = get_option( 'um_usermeta_fields', array() );

		global $wpdb;
		$metadata = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT *
				FROM {$wpdb->usermeta}
				WHERE meta_key IN ('" . implode( "','", $wp_usermeta_option ) . "')
				LIMIT %d, %d",
				( absint( $_POST['page'] ) - 1 ) * $per_page,
				$per_page
			),
			ARRAY_A
		);

		$values = array();
		foreach ( $metadata as $metarow ) {
			$values[] = $wpdb->prepare( '(%d, %s, %s)', $metarow['user_id'], $metarow['meta_key'], $metarow['meta_value'] );
		}

		if ( ! empty( $values ) ) {
			$wpdb->query(
				"INSERT INTO
				{$wpdb->prefix}um_metadata(user_id, um_key, um_value)
				VALUES " . implode( ',', $values )
			);
		}

		$from = ( absint( $_POST['page'] ) * $per_page ) - $per_page + 1;
		$to   = absint( $_POST['page'] ) * $per_page;

		wp_send_json_success( array( 'message' => sprintf( __( 'Metadata from %1$s to %2$s was upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
	}


	/**
	 * AJAX handler - Get options for the member directory "Admin filtering"
	 * @version 2.1.12
	 */
	function default_filter_settings() {
		UM()->ajax()->check_nonce( 'um-admin-nonce' );

		// we can't use function "sanitize_key" because it changes uppercase to lowercase
		$filter_key = sanitize_text_field( $_REQUEST['key'] );
		$directory_id = absint( $_REQUEST['directory_id'] );

		$html = UM()->module( 'member-directory' )->frontend()->show_filter( $filter_key, array( 'form_id' => $directory_id ), false, true );

		wp_send_json_success( array( 'field_html' => $html ) );
	}
}
