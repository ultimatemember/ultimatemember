<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter user basename.
 *
 * @param  string $value
 *
 * @return string
 */
function um_clean_user_basename_filter( $value, $raw ) {
	$permalink_base = UM()->options()->get( 'permalink_base' );

	$user_query = new WP_User_Query(
		array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'um_user_profile_url_slug_' . $permalink_base,
					'value'   => $raw,
					'compare' => '=',
				),
			),
			'fields'     => array( 'ID' ),
		)
	);

	if ( $user_query->total_users > 0 ) {
		$result = current( $user_query->get_results() );
		if ( isset( $result->ID ) ) {
			$value = get_user_meta( $result->ID, 'um_user_profile_url_slug_' . $permalink_base, true );
		}
	}

	/**
	 * Filters the base user permalink value before cleaning.
	 *
	 * @param {string} $permalink User Profile permalink.
	 *
	 * @return {string} User Profile permalink.
	 *
	 * @since 1.3.x
	 * @hook um_permalink_base_before_filter
	 *
	 * @example <caption>Change base permalink before cleaning.</caption>
	 * function my_permalink_base_before( $permalink ) {
	 *     // your code here
	 *     return $permalink;
	 * }
	 * add_filter( 'um_permalink_base_before_filter', 'my_permalink_base_before' );
	 */
	$value       = apply_filters( 'um_permalink_base_before_filter', $value );
	$raw_value   = $value;
	$filter_slug = '';

	switch ( $permalink_base ) {
		case 'name':
			if ( ! empty( $value ) && strrpos( $value, '_' ) > -1 ) {
				$value = str_replace( '_', '. ', $value );
			}

			if ( ! empty( $value ) && strrpos( $value, '_' ) > -1 ) {
				$value = str_replace( '_', '-', $value );
			}

			if ( ! empty( $value ) && strrpos( $value, '.' ) > -1 && strrpos( $raw_value, '_' ) <= -1 ) {
				$value = str_replace( '.', ' ', $value );
			}

			$filter_slug = '_' . $permalink_base;
			break;
		case 'name_dash':
			if ( ! empty( $value ) && strrpos( $value, '-' ) > -1 ) {
				$value = str_replace( '-', ' ', $value );
			}

			if ( ! empty( $value ) && strrpos( $value, '_' ) > -1 ) {
				$value = str_replace( '_', '-', $value );
			}

			// Checks if last name has a dash
			if ( ! empty( $value ) && strrpos( $value, '_' ) > -1 ) {
				$value = str_replace( '_', '-', $value );
			}

			$filter_slug = '_' . $permalink_base;
			break;
		case 'name_plus':
			if ( ! empty( $value ) && strrpos( $value, '+' ) > -1 ) {
				$value = str_replace( '+', ' ', $value );
			}

			if ( ! empty( $value ) && strrpos( $value, '_' ) > -1 ) {
				$value = str_replace( '_', '+', $value );
			}

			$filter_slug = '_' . $permalink_base;
			break;
		default:
			// Checks if last name has a dash
			if ( ! empty( $value ) && strrpos( $value, '_' ) > -1 ) {
				$value = str_replace( '_', '-', $value );
			}
			break;
	}

	/**
	 * Filters the base user permalink value after cleaning.
	 * $filter_slug - can be empty '' or equals 'name', 'name_dash', 'name_plus'
	 *
	 * @param {string} $permalink User Profile permalink.
	 * @param {string} $raw_value The base user permalink value before cleaning.
	 *
	 * @return {string} User Profile permalink.
	 *
	 * @since 1.3.x
	 * @hook um_permalink_base_after_filter{$filter_slug}
	 *
	 * @example <caption>Change base permalink after cleaning if permalink settings isn't connected with user first or last name.</caption>
	 * function my_permalink_base_after_filter( $permalink, $raw_permalink ) {
	 *     // your code here
	 *     return $permalink;
	 * }
	 * add_filter( 'um_permalink_base_after_filter', 'my_permalink_base_after_filter', 10, 2 );
	 * @example <caption>Change base permalink after cleaning if permalink settings is a full name.</caption>
	 * function my_permalink_base_after_filter_name( $permalink, $raw_permalink ) {
	 *     // your code here
	 *     return $permalink;
	 * }
	 * add_filter( 'um_permalink_base_after_filter_name', 'my_permalink_base_after_filter_name', 10, 2 );
	 * @example <caption>Change base permalink after cleaning if permalink settings is a full name connected by dash.</caption>
	 * function my_permalink_base_after_filter_name_dash( $permalink, $raw_permalink ) {
	 *     // your code here
	 *     return $permalink;
	 * }
	 * add_filter( 'um_permalink_base_after_filter_name_dash', 'my_permalink_base_after_filter_name_dash', 10, 2 );
	 * @example <caption>Change base permalink after cleaning if permalink settings is a full name connected by plus.</caption>
	 * function my_permalink_base_after_filter_name_plus( $permalink, $raw_permalink ) {
	 *     // your code here
	 *     return $permalink;
	 * }
	 * add_filter( 'um_permalink_base_after_filter_name_plus', 'my_permalink_base_after_filter_name_plus', 10, 2 );
	 */
	return apply_filters( "um_permalink_base_after_filter{$filter_slug}", $value, $raw_value );
}
add_filter( 'um_clean_user_basename_filter', 'um_clean_user_basename_filter', 2, 10 );

/**
 * Filter before update profile to force utf8 strings
 *
 * @param  array $changes
 * @param int $user_id
 *
 * @return array
 */
function um_before_update_profile( $changes, $user_id ) {
	// todo check if this option required and maybe there are some WordPress native ways how to make that without custom unused functions. Maybe fully deprecate 'um_force_utf8_strings' option which doesn't exist in UI.
	if ( ! UM()->options()->get( 'um_force_utf8_strings' ) ) {
		return $changes;
	}

	foreach ( $changes as $key => $value ) {
		$changes[ $key ] = um_force_utf8_string( $value );
	}

	return $changes;
}
add_filter( 'um_before_update_profile', 'um_before_update_profile', 10, 2 );
