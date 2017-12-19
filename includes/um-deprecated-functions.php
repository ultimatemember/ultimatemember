<?php
/**
 * Deprecated functions
 *
 * Where functions come to die.
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
	if ( ! UM()->external_integrations()->is_wpml_active() )
		return '';

	$lang_post_id = icl_object_id( $post_id, 'page', true, $language );

	if ( $lang_post_id != 0 ) {
		$url = get_permalink( $lang_post_id );
	} else {
		// No page found, it's most likely the homepage
		global $sitepress;
		$url = $sitepress->language_url( $language );
	}

	return $url;
}