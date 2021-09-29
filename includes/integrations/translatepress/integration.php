<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_pre_template_locations_translatepress( $template_locations, $template_name, $module, $template_path ) {
	$language_codes = um_translatepress_get_languages_codes();

	if ( $language_codes['default'] != $language_codes['current'] ) {
		$lang = $language_codes['current'];

		$ml_template_locations = array_map( function( $item ) use ( $template_path, $lang ) {
			return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $lang . '/', $item );
		}, $template_locations );

		$template_locations = array_merge( $ml_template_locations, $template_locations );
	}

	return $template_locations;
}
add_filter( 'um_pre_template_locations_common_locale_integration', 'um_pre_template_locations_translatepress', 10, 4 );


/**
 * Get default and current locales.
 *
 * @since  3.0
 *
 * @return array
 */
function um_translatepress_get_languages_codes() {
	$trp = TRP_Translate_Press::get_trp_instance();
	$trp_settings = $trp->get_component( 'settings' );
	$settings = $trp_settings->get_settings();

	$default_language = $settings['default-language'];

	return array(
		'default' => $default_language,
		'current' => get_locale(),
	);
}


/**
 * @param int $page_id
 * @param string $slug
 *
 * @return mixed
 */
function um_get_predefined_page_id_translatepress( $page_id, $slug ) {
	// just empty method, but works properly
	return $page_id;
}
add_filter( 'um_get_predefined_page_id', 'um_get_predefined_page_id_translatepress', 10, 2 );


/**
 * @param array $variables
 *
 * @return array
 */
function um_common_js_variables_translatepress( $variables ) {
	$variables['locale'] = get_locale();
	return $variables;
}
add_filter( 'um_common_js_variables', 'um_common_js_variables_translatepress', 10, 1 );
