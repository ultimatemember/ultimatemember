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
	if ( UM()->is_ajax() ) {
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

	if ( um_is_session_started() === false ) {
		session_start();
	}

	if ( isset( $_SESSION['um_redirect_key'][ $key ] ) ) {

		$url = $_SESSION['um_redirect_key'][ $key ];

		return $url;

	} else {

		if ( isset( $_SESSION['um_redirect_key'] ) ) {
			foreach ( $_SESSION['um_redirect_key'] as $key => $url ) {

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
	_deprecated_function( __FUNCTION__, '2.0.0' );
	$value = get_user_meta( $user_id, '_um_last_login', true );
	if ( $value ) {
		return date_i18n( 'F d, Y', $value );
	}

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


/**
 * user uploads directory
 *
 * @deprecated 2.0.26
 *
 * @return string
 */
function um_user_uploads_dir() {
	//um_deprecated_function( 'um_user_uploads_dir', '2.0.26', 'UM()->external_integrations()->get_url_for_language' );
	$uri = UM()->files()->upload_basedir . um_user( 'ID' ) . '/';
	return $uri;
}

/**
 * user uploads uri
 *
 * @deprecated 2.0.26
 *
 * @return string
 */
function um_user_uploads_uri() {
	//um_deprecated_function( 'um_user_uploads_uri', '2.0.26', 'UM()->external_integrations()->get_url_for_language' );
	UM()->files()->upload_baseurl = set_url_scheme( UM()->files()->upload_baseurl );
	$uri = UM()->files()->upload_baseurl . um_user( 'ID' ) . '/';
	return $uri;
}

/**
 * Check if a legitimate password reset request is in action
 *
 * @deprecated 2.0.26
 *
 * @return bool
 */
function um_requesting_password_reset() {
	//um_deprecated_function( 'um_requesting_password_reset', '2.0.26', 'UM()->password()->is_reset_request' );

	if ( um_is_core_page( 'password-reset' ) && isset( $_POST['_um_password_reset'] ) == 1 )
		return true;

	return false;
}


/**
 * Check if a legitimate password change request is in action
 *
 * @deprecated 2.0.26
 *
 * @return bool
 */
function um_requesting_password_change() {
	//um_deprecated_function( 'um_requesting_password_change', '2.0.26', 'UM()->password()->is_change_request' );

	if ( um_is_core_page( 'account' ) && isset( $_POST['_um_account'] ) == 1 & isset( $_POST['_um_account_tab'] ) == 'password' ) {
		return true;
	} elseif ( isset( $_POST['_um_password_change'] ) && $_POST['_um_password_change'] == 1 ) {
		return true;
	}

	return false;
}



/**
 * Get core page url
 *
 * @deprecated 2.0.30
 *
 * @param $time1
 * @param $time2
 *
 * @return string
 */
function um_time_diff( $time1, $time2 ) {
	//um_deprecated_function( 'um_time_diff', '2.0.30', 'UM()->datetime()->time_diff' );

	return UM()->datetime()->time_diff( $time1, $time2 );
}


/**
 * Get members to show in directory
 *
 * @deprecated 2.1.0
 *
 *
 * @param $argument
 *
 * @return mixed
 */
function um_members( $argument ) {
	//um_deprecated_function( 'um_members', '2.1.0', 'UM()->member_directory()' );

	$result = null;
	if ( isset( UM()->members()->results[ $argument ] ) ) {
		$result = UM()->members()->results[ $argument ];
	}
	return $result;
}


/**
 * Returns the ultimate member search form
 *
 * @deprecated 2.1.0
 *
 * @return string
 */
function um_get_search_form() {
	//um_deprecated_function( 'um_get_search_form', '2.1.0', 'do_shortcode( \'[ultimatemember_searchform]\' )' );

	if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
		return do_shortcode( '[ultimatemember_searchform]' );
	} else {
		return apply_shortcodes( '[ultimatemember_searchform]' );
	}
}


/**
 * Display the search form.
 *
 * @deprecated 2.1.0
 */
function um_search_form() {
	//um_deprecated_function( 'um_search_form', '2.1.0', 'echo do_shortcode( \'[ultimatemember_searchform]\' )' );

	echo um_get_search_form();
}


/**
 * Filters the search query.
 *
 * @deprecated 2.1.0
 *
 * @param  string $search
 *
 * @return string
 */
function um_filter_search( $search ) {
	$search = trim( strip_tags( $search ) );
	$search = preg_replace( '/[^a-z \.\@\_\-]+/i', '', $search );

	return $search;
}


/**
 * Returns the user search query
 *
 * @deprecated 2.1.0
 *
 * @return string
 */
function um_get_search_query() {
	$query = UM()->permalinks()->get_query_array();
	$search = isset( $query['search'] ) ? $query['search'] : '';

	return um_filter_search( $search );
}


/**
 * Check value of queried search in text input
 *
 * @deprecated 2.1.0
 *
 * @param $filter
 * @param bool $echo
 *
 * @return mixed|string
 */
function um_queried_search_value( $filter, $echo = true ) {
	$value = '';
	if (isset( $_REQUEST['um_search'] )) {
		$query = UM()->permalinks()->get_query_array();
		if (isset( $query[$filter] ) && $query[$filter] != '') {
			$value = stripslashes_deep( $query[$filter] );
		}
	}

	if ($echo) {
		echo $value;

		return '';
	} else {
		return $value;
	}

}


/**
 * Check whether item in dropdown is selected in query-url
 *
 * @deprecated 2.1.0
 *
 * @param $filter
 * @param $val
 */
function um_select_if_in_query_params( $filter, $val ) {
	$selected = false;

	if (isset( $_REQUEST['um_search'] )) {
		$query = UM()->permalinks()->get_query_array();

		if (isset( $query[$filter] ) && $val == $query[$filter])
			$selected = true;

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_selected_if_in_query_params
		 * @description Make selected or unselected from query attribute
		 * @input_vars
		 * [{"var":"$selected","type":"bool","desc":"Selected or not"},
		 * {"var":"$filter","type":"string","desc":"Check by this filter in query"},
		 * {"var":"$val","type":"string","desc":"Field Value"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_filter( 'um_selected_if_in_query_params', 'function_name', 10, 3 );
		 * @example
		 * <?php
		 * add_filter( 'um_selected_if_in_query_params', 'my_selected_if_in_query_params', 10, 3 );
		 * function my_selected_if_in_query_params( $selected, $filter, $val ) {
		 *     // your code here
		 *     return $selected;
		 * }
		 * ?>
		 */
		$selected = apply_filters( 'um_selected_if_in_query_params', $selected, $filter, $val );
	}

	echo $selected ? 'selected="selected"' : '';
}


/**
 * Get submitted user information
 *
 * @param bool $style
 *
 * @return null|string
 *
 * @deprecated 2.1.3
 */
function um_user_submitted_registration( $style = false ) {
	$output = null;

	$data = um_user( 'submitted' );

	if ( $style ) {
		$output .= '<div class="um-admin-infobox">';
	}

	if ( isset( $data ) && is_array( $data ) ) {

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_email_registration_data
		 * @description Prepare Registration data to email
		 * @input_vars
		 * [{"var":"$data","type":"array","desc":"Registration Data"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_filter( 'um_email_registration_data', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_filter( 'um_email_registration_data', 'my_email_registration_data', 10, 1 );
		 * function my_email_registration_data( $data ) {
		 *     // your code here
		 *     return $data;
		 * }
		 * ?>
		 */
		$data = apply_filters( 'um_email_registration_data', $data );

		$pw_fields = array();
		foreach ( $data as $k => $v ) {

			if ( strstr( $k, 'user_pass' ) || in_array( $k, array( 'g-recaptcha-response', 'request', '_wpnonce', '_wp_http_referer' ) ) ) {
				continue;
			}

			if ( UM()->fields()->get_field_type( $k ) == 'password' ) {
				$pw_fields[] = $k;
				$pw_fields[] = 'confirm_' . $k;
				continue;
			}

			if ( ! empty( $pw_fields ) && in_array( $k, $pw_fields ) ) {
				continue;
			}

			if ( UM()->fields()->get_field_type( $k ) == 'image' || UM()->fields()->get_field_type( $k ) == 'file' ) {
				$file = basename( $v );
				$filedata = get_user_meta( um_user( 'ID' ), $k . "_metadata", true );

				$baseurl = UM()->uploader()->get_upload_base_url();
				if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $file ) ) {
					if ( is_multisite() ) {
						//multisite fix for old customers
						$baseurl = str_replace( '/sites/' . get_current_blog_id() . '/', '/', $baseurl );
					}
				}

				if ( ! empty( $filedata['original_name'] ) ) {
					$v = '<a href="' . esc_attr( $baseurl . um_user( 'ID' ) . '/' . $file ) . '">' . esc_html( $filedata['original_name'] ) . '</a>';
				} else {
					$v = $baseurl . um_user( 'ID' ) . '/' . $file;
				}
			}

			if ( is_array( $v ) ) {
				$v = implode( ',', $v );
			}

			if ( $k == 'timestamp' ) {
				$k = __( 'date submitted', 'ultimate-member' );
				$v = date( "d M Y H:i", $v );
			}

			if ( $style ) {
				if ( ! $v ) {
					$v = __( '(empty)', 'ultimate-member' );
				}
				$output .= "<p><label>$k</label><span>$v</span></p>";
			} else {
				$output .= "$k: $v" . "<br />";
			}
		}
	}

	if ( $style ) {
		$output .= '</div>';
	}

	return $output;
}
