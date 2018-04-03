<?php
/**
 * Deprecated functions
 *
 * Where public functions come to die.
 *
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Wrapper for deprecated functions so we can apply some extra logic.
 *
 * @since  2.0
 * @param  string $function
 * @param  string $version
 * @param  string $replacement
 */
function um_deprecated_function( $function, $version, $replacement = null ) {
	if ( is_ajax() ) {
		do_action( 'deprecated_function_run', $function, $replacement, $version );
		$log_string  = "The {$function} function is deprecated since version {$version}.";
		$log_string .= $replacement ? " Replace with {$replacement}." : '';
		error_log( $log_string );
	} else {
		_deprecated_function( $function, $version, $replacement );
	}
}


/**
 * Get option value
 *
 * Please use UM()->options()->get() instead
 *
 * @deprecated 2.0.1
 * @param $option_id
 * @return mixed|string|void
 */
function um_get_option( $option_id ) {
	//um_deprecated_function( 'um_get_option', '2.0', 'UM()->options()->get' );
	return UM()->options()->get( $option_id );
}


/**
 * Update option value
 *
 * Please use UM()->options()->update() instead
 *
 * @deprecated 2.0.1
 * @param $option_id
 * @param $value
 */
function um_update_option( $option_id, $value ) {
	//um_deprecated_function( 'um_update_option', '2.0', 'UM()->options()->update' );
	UM()->options()->update( $option_id, $value );
}


/**
 * Update option value
 *
 * Please use UM()->options()->remove() instead
 *
 * @deprecated 2.0.1
 * @param $option_id
 */
function um_remove_option( $option_id ) {
	//um_deprecated_function( 'um_remove_option', '2.0', 'UM()->options()->remove' );
	UM()->options()->remove( $option_id );
}


/**
 * @deprecated 2.0
 *
 * @param $content_type
 * @return string
 */
function um_mail_content_type( $content_type ) {
	return 'text/html';
}


/**
 * Convert urls to clickable links
 *
 * @deprecated 2.0
 *
 * @param $s
 * @return mixed
 */
function um_clickable_links( $s ) {
	return preg_replace( '@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" class="um-link" target="_blank">$1</a>', $s );
}


/**
 * Set redirect key
 *
 * @deprecated 2.0
 *
 * @param  string $url
 * @return string $redirect_key
 */
function um_set_redirect_url( $url ) {

	if (um_is_session_started() === false) {
		session_start();
	}

	$redirect_key = wp_generate_password( 12, false );

	$_SESSION['um_redirect_key'] = array( $redirect_key => $url );

	return $redirect_key;
}


/**
 * Set redirect key
 *
 * @deprecated 2.0
 *
 * @param  string $key
 * @return string $redirect_key
 */
function um_get_redirect_url( $key ) {

	if (um_is_session_started() === false) {
		session_start();
	}

	if (isset( $_SESSION['um_redirect_key'][$key] )) {

		$url = $_SESSION['um_redirect_key'][$key];

		return $url;

	} else {

		if (isset( $_SESSION['um_redirect_key'] )) {
			foreach ($_SESSION['um_redirect_key'] as $key => $url) {

				return $url;

				break;
			}
		}
	}

	return;
}


/**
 * Get user's last login time
 *
 * @deprecated 2.0
 *
 * @param $user_id
 * @return string
 */
function um_user_last_login_date( $user_id ) {
	$value = get_user_meta( $user_id, '_um_last_login', true );
	if ($value)
		return date_i18n( 'F d, Y', $value );

	return '';
}


/**
 * Is core URL
 *
 * @deprecated 2.0
 *
 * @return bool
 */
function um_is_core_uri() {
	$array = UM()->config()->permalinks;
	$current_url = UM()->permalinks()->get_current_url( get_option( 'permalink_structure' ) );

	if (!isset( $array ) || !is_array( $array )) return false;

	foreach ($array as $k => $id) {
		$page_url = get_permalink( $id );
		if (strstr( $current_url, $page_url ))
			return true;
	}

	return false;
}


/**
 * Check if meta_value exists
 *
 * @deprecated 2.0
 *
 * @param  string $key
 * @param  mixed  $value
 * @param  mixed  $return_user_id
 *
 * @return integer
 */
function um_is_meta_value_exists( $key, $value, $return_user_id = false ) {
	global $wpdb;

	if (isset( UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key] )) {
		return UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key];
	}

	if (!$return_user_id) {
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) as count FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s ",
			$key,
			$value
		) );

		UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key] = $count;

		return $count;
	}

	$user_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s ",
		$key,
		$value
	) );

	UM()->profile()->arr_user_slugs['is_' . $return_user_id][$key] = $user_id;

	return $user_id;

}


/**
 * Get localization
 *
 * @deprecated 2.0
 *
 * @return string
 */
function um_get_locale() {

	$lang_code = get_locale();

	if (strpos( $lang_code, 'en_' ) > -1 || empty( $lang_code ) || $lang_code == 0) {
		return 'en';
	}

	return $lang_code;
}


/**
 * Get current page type
 *
 * @deprecated 2.0
 *
 * @return string
 */
function um_get_current_page_type() {
	global $wp_query;
	$loop = 'notfound';

	if ($wp_query->is_page) {
		//$loop = is_front_page() ? 'front' : 'page';
		$loop = 'page';
	} else if ($wp_query->is_home) {
		$loop = 'home';
	} else if ($wp_query->is_single) {
		$loop = ( $wp_query->is_attachment ) ? 'attachment' : 'single';
	} else if ($wp_query->is_category) {
		$loop = 'category';
	} else if ($wp_query->is_tag) {
		$loop = 'tag';
	} else if ($wp_query->is_tax) {
		$loop = 'tax';
	} else if ($wp_query->is_archive) {
		if ($wp_query->is_day) {
			$loop = 'day';
		} else if ($wp_query->is_month) {
			$loop = 'month';
		} else if ($wp_query->is_year) {
			$loop = 'year';
		} else if ($wp_query->is_author) {
			$loop = 'author';
		} else {
			$loop = 'archive';
		}
	} else if ($wp_query->is_search) {
		$loop = 'search';
	} else if ($wp_query->is_404) {
		$loop = 'notfound';
	}

	return $loop;
}


/**
 * Check if running local
 *
 * @deprecated 2.0
 *
 * @return boolean
 */
function um_core_is_local() {
	if ($_SERVER['HTTP_HOST'] == 'localhost'
	    || substr( $_SERVER['HTTP_HOST'], 0, 3 ) == '10.'
	    || substr( $_SERVER['HTTP_HOST'], 0, 7 ) == '192.168'
	) return true;

	return false;
}


/**
 * Get a translated core page URL
 *
 * @deprecated 2.0.1
 *
 * @param $post_id
 * @param $language
 * @return bool|false|string
 */
function um_get_url_for_language( $post_id, $language ) {
	//um_deprecated_function( 'um_get_url_for_language', '2.0', 'UM()->external_integrations()->get_url_for_language' );
	return UM()->external_integrations()->get_url_for_language( $post_id, $language );
}