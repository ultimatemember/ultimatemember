<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * @param $template_locations
 * @param $template_name
 * @param $module
 * @param $template_path
 *
 * @return array
 * @throws Exception
 */
function um_pre_template_locations_weglot( $template_locations, $template_name, $module, $template_path ) {
	$language_codes = um_weglot_get_languages_codes();

	if ( $language_codes['default'] != $language_codes['current'] ) {
		$lang = $language_codes['current'];

		$ml_template_locations = array_map( function( $item ) use ( $template_path, $lang ) {
			return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $lang . '/', $item );
		}, $template_locations );

		$template_locations = array_merge( $ml_template_locations, $template_locations );
	}

	return $template_locations;
}
add_filter( 'um_pre_template_locations_common_locale_integration', 'um_pre_template_locations_weglot', 10, 4 );


/**
 * @return array
 * @throws Exception
 */
function um_weglot_get_languages_codes() {
	return [
		'default' => weglot_get_original_language(),
		'current' => weglot_get_current_language(),
	];
}
