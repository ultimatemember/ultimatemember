<?php
function um_upgrade_get_users2022() {
	um_maybe_unset_time_limit();

	$result = count_users();

	wp_send_json_success( array( 'count' => $result['total_users'], 'message' => __( 'Users are ready for upgrade', 'ultimate-member' ) ) );
}


function um_upgrade_usermeta2022() {
	um_maybe_unset_time_limit();

	if ( ! empty( $_POST['page'] ) && ! empty( $_POST['pages'] ) ) {
		$users_per_page = 50;

		$from = ( $_POST['page'] * $users_per_page ) - $users_per_page + 1;
		$to = $_POST['page'] * $users_per_page;

		global $wpdb;

		$all_metafields = UM()->builtin()->all_user_fields;

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_admin_custom_search_filters
		 * @description Custom Search Filters
		 * @input_vars
		 * [{"var":"$custom_search","type":"array","desc":"Filters"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_admin_custom_search_filters', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_admin_custom_search_filters', 'my_admin_custom_search_filters', 10, 1 );
		 * function my_upload_file_name( $custom_search ) {
		 *     // your code here
		 *     return $custom_search;
		 * }
		 * ?>
		 */
		$custom_search = apply_filters( 'um_admin_custom_search_filters', array() );
		$searchable_fields = UM()->builtin()->all_user_fields( 'date,time,url' );
		$searchable_fields = $searchable_fields + $custom_search;

		unset( $searchable_fields['user_registered'] );
		unset( $searchable_fields['role_select'] );
		unset( $searchable_fields['role_radio'] );
		unset( $searchable_fields[0] );

		$searchable_fields = array_keys( $searchable_fields );

		foreach ( $all_metafields as $key => $field_data ) {
			if ( ! in_array( $key, $searchable_fields ) ) {
				continue;
			}

			$users = get_users( array(
				'offset' => $from,
				'number' => $users_per_page,
				'fields' => 'ids',
			) );

			$values = $wpdb->get_results( $wpdb->prepare(
				"SELECT user_id, meta_value as val 
				FROM {$wpdb->usermeta} 
				WHERE meta_key = %s AND
					  meta_value IS NOT NULL AND 
					  user_id IN('" . implode( "','", $users ) . "')",
				$key
			), ARRAY_A );

			if ( empty( $values ) ) {
				continue;
			}

			foreach ( $values as $meta ) {
				if ( in_array( $field_data['type'], array( 'radio', 'multiselect', 'select', 'checkbox' ) ) ) {
					if ( ! is_serialized( $meta['val'] ) ) {
						$backup = get_user_meta( $meta['user_id'], $field_data['metakey'], true );
						update_user_meta( $meta['user_id'], $field_data['metakey'] . '_backup', $backup );

						$array = array( $meta['val'] );
						$metavalue = serialize( $array );
						update_user_meta( $meta['user_id'], $field_data['metakey'], $metavalue );
					}
				} else {
					if ( is_serialized( $meta['val'] ) ) {
						$backup = get_user_meta( $meta['user_id'], $field_data['metakey'], true );
						update_user_meta( $meta['user_id'], $field_data['metakey'] . '_backup', $backup );

						$maybe_array = maybe_unserialize( $meta['val'] );
						$metavalue = $maybe_array[0];
						update_user_meta( $meta['user_id'], $field_data['metakey'], $metavalue );
					}
				}
			}
		}

		if ( $_POST['page'] == $_POST['pages'] ) {
			update_option( 'um_last_version_upgrade', '2.0.22-alpha1' );
		}

		wp_send_json_success( array( 'message' => sprintf( __( 'Users from %s to %s was upgraded successfully...', 'ultimate-member' ), $from, $to ) ) );
	} else {
		wp_send_json_error();
	}
}