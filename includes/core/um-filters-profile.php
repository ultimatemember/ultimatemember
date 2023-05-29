<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fix for plugin "The SEO Framework", dynamic profile page title
 * @link https://wordpress.org/plugins/autodescription/
 *
 * @param $title
 * @param string $sep
 *
 * @return mixed|string
 */
function um_dynamic_user_profile_pagetitle( $title, $sep = '' ) {

	if ( um_is_predefined_page( 'user' ) && um_get_requested_user() ) {

		$user_id = um_get_requested_user();

		if ( UM()->user()->is_profile_noindex( $user_id ) ) {
			return $title;
		}

		$profile_title = UM()->options()->get( 'profile_title' );

		um_fetch_user( um_get_requested_user() );

		$profile_title = um_convert_tags( $profile_title );

		$title = stripslashes( $profile_title );

		um_reset_user();

	}

	return $title;
}
add_filter( 'the_seo_framework_pro_add_title', 'um_dynamic_user_profile_pagetitle', 100000, 2 );
add_filter( 'wp_title', 'um_dynamic_user_profile_pagetitle', 100000, 2 );
add_filter( 'pre_get_document_title', 'um_dynamic_user_profile_pagetitle', 100000, 2 );


/**
 * Try and modify the page title in page
 *
 * @param $title
 * @param string $id
 *
 * @return string
 */
function um_dynamic_user_profile_title( $title, $id = '' ) {
	if ( is_admin() ) {
		return $title;
	}

	if ( um_is_predefined_page( 'user' ) ) {
		if ( um_is_predefined_page( 'user', $id ) && in_the_loop() ) {
			if ( um_get_requested_user() ) {
				$title = um_get_display_name( um_get_requested_user() );
			} elseif ( is_user_logged_in() ) {
				$title = um_get_display_name( get_current_user_id() );
			}
		}
	}

	if ( ! function_exists( 'utf8_decode' ) ) {
		return $title;
	}

	return ( strlen( $title ) !== mb_strlen( $title ) ) ? $title : mb_convert_encoding( $title, 'UTF-8' );
}
add_filter( 'the_title', 'um_dynamic_user_profile_title', 100000, 2 );


/**
 * Fix SEO canonical for the profile page
 *
 * @param  string       $canonical_url The canonical URL.
 * @param  WP_Post      $post          Optional. Post ID or object. Default is global `$post`.
 * @return string|false                The canonical URL, or false if current URL is canonical.
 */
function um_get_canonical_url( $canonical_url, $post ) {
	if ( ! um_is_predefined_page( 'user', $post->ID ) ) {
		return $canonical_url;
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_allow_canonical__filter
	 * @description Allow canonical
	 * @input_vars
	 * [{"var":"$allow_canonical","type":"bool","desc":"Allow?"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_allow_canonical__filter', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_allow_canonical__filter', 'my_allow_canonical', 10, 1 );
	 * function my_allow_canonical( $allow_canonical ) {
	 *     // your code here
	 *     return $allow_canonical;
	 * }
	 * ?>
	 */
	$enable_canonical = apply_filters( 'um_allow_canonical__filter', true );

	if ( $enable_canonical ) {
		$url = um_user_profile_url( um_get_requested_user() );
		$canonical_url = ( $url === home_url( $_SERVER['REQUEST_URI'] ) ) ? false : $url;

		if ( $page = get_query_var( 'cpage' ) ) {
			$canonical_url = get_comments_pagenum_link( $page );
		}
	}

	return $canonical_url;
}
add_filter( 'get_canonical_url', 'um_get_canonical_url', 20, 2 );


/**
 * Add cover photo label of file size limit
 *
 * @param array $fields Predefined fields
 *
 * @return array
 */
function um_change_profile_cover_photo_label( $fields ) {
	if ( ! array_key_exists( 'cover_photo', $fields ) || ! array_key_exists( 'max_size', $fields['cover_photo'] ) ) {
		return $fields;
	}

	$max_size = UM()->common()->filesystem()->format_bytes( $fields['cover_photo']['max_size'] );
	if ( ! empty( $max_size ) ) {
		list( $file_size, $unit ) = explode( ' ', $max_size );

		if ( $file_size < 999999999 ) {
			$fields['cover_photo']['upload_text'] .= '<small class="um-max-filesize">( ' . __( 'max', 'ultimate-member' ) . ': <span>' . $file_size . $unit . '</span> )</small>';
		}
	}
	return $fields;
}
add_filter( 'um_predefined_fields_hook', 'um_change_profile_cover_photo_label', 10, 1 );


/**
 * Add profile photo label of file size limit
 *
 * @param array $fields Predefined fields
 *
 * @return array
 */
function um_change_profile_photo_label( $fields ) {
	if ( ! array_key_exists( 'profile_photo', $fields ) || ! array_key_exists( 'max_size', $fields['profile_photo'] ) ) {
		return $fields;
	}

	$max_size = UM()->common()->filesystem()->format_bytes( $fields['profile_photo']['max_size'] );
	if ( ! empty( $max_size ) ) {
		list( $file_size, $unit ) = explode( ' ', $max_size );

		if ( $file_size < 999999999 ) {
			$fields['profile_photo']['upload_text'] .= '<small class="um-max-filesize">( ' . __( 'max', 'ultimate-member' ) . ': <span>' . $file_size . $unit . '</span> )</small>';
		}
	}
	return $fields;
}
add_filter( 'um_predefined_fields_hook', 'um_change_profile_photo_label', 10, 1 );
