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