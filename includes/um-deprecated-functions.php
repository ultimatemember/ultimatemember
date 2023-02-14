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
 * Check if we are on UM page
 *
 * @deprecated 3.0
 *
 * @return bool
 */
function is_ultimatemember() {
	_deprecated_function( __FUNCTION__, '3.0' );

	global $post;

	$is_ultimatemember = false;
	$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );

	foreach ( $predefined_pages as $slug ) {
		if ( um_is_predefined_page( $slug, $post->ID ) ) {
			$is_ultimatemember = true;
			break;
		}
	}

	return $is_ultimatemember;
}



/**
 * Get core page url
 *
 * @deprecated 3.0
 *
 * @param string $slug
 * @param bool $updated
 *
 * @return string
 */
function um_get_core_page( $slug, $updated = false ) {
	_deprecated_function( __FUNCTION__, '3.0', 'um_get_predefined_page_url' );

	$url = um_get_predefined_page_url( $slug );
	if ( $updated ) {
		$url = add_query_arg( 'updated', esc_attr( $updated ), $url );
	}

	return apply_filters( 'um_get_core_page_filter', $url, $slug, $updated );
}


/**
 * Get server protocol
 *
 * @deprecated 3.0
 *
 * @return  string
 */
function um_get_domain_protocol() {
	_deprecated_function( __FUNCTION__, '3.0' );
	if ( is_ssl() ) {
		$protocol = 'https://';
	} else {
		$protocol = 'http://';
	}

	return $protocol;
}


/**
 * @deprecated 3.0
 *
 * @param $post
 * @param $slug
 *
 * @return bool
 */
function um_is_core_post( $post, $slug ) {
	_deprecated_function( __FUNCTION__, '3.0', 'um_is_predefined_page' );
	return um_is_predefined_page( $slug, $post );
}


/**
 * Check if we are on a UM Core Page or not
 *
 * Default um core pages slugs
 * 'user', 'login', 'register', 'members', 'logout', 'account', 'password-reset'
 *
 * @deprecated 3.0
 *
 * @param string $slug UM core page slug
 * @param null|\WP_Post $post UM core page slug
 *
 * @return bool
 */
function um_is_core_page( $slug, $post = null ) {
	_deprecated_function( __FUNCTION__, '3.0', 'um_is_predefined_page' );
	return um_is_predefined_page( $slug, $post );
}


/**
 * @param $url
 *
 * @deprecated 3.0
 */
function um_js_redirect( $url ) {
	_deprecated_function( __FUNCTION__, '3.0', 'wp_redirect or wp_safe_redirect' );
	if ( headers_sent() || empty( $url ) ) {
		//for blank redirects
		if ( '' == $url ) {
			$url = set_url_scheme( '//' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] );
		}

		register_shutdown_function( function( $url ) {
			echo '<script data-cfasync="false" type="text/javascript">window.location = "' . esc_js( $url ) . '"</script>';
		}, $url );

		if ( 1 < ob_get_level() ) {
			while ( ob_get_level() > 1 ) {
				ob_end_clean();
			}
		} ?>
		<script data-cfasync='false' type="text/javascript">
			window.location = '<?php echo esc_js( $url ); ?>';
		</script>
		<?php exit;
	} else {
		wp_redirect( $url );
	}
	exit;
}


/**
 * Prepare template
 *
 * @param  string  $k
 * @param  string  $title
 * @param  array   $data
 * @param  boolean $style
 * @return string
 *
 * @deprecated 3.0
 *
 * @since  2.1.4
 */
function um_user_submited_display( $k, $title, $data = array(), $style = true ) {
	_deprecated_function( __FUNCTION__, '3.0', 'um_user_submitted_display()' );
	return um_user_submitted_display( $k, $title, $data, $style );
}


/**
 * Default avatar URL
 *
 * @deprecated 3.0.0
 *
 * @return string
 */
function um_get_default_avatar_uri() {
	_deprecated_function( __FUNCTION__, '3.0', 'um_get_default_avatar_url()' );
	return um_get_default_avatar_url();
}
