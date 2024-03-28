<?php
/**
 * Deprecated Ultimate Member hooks.
 * The place for hookdocs of the Ultimate Member hooks that have been deprecated.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters the language locale before loading textdomain.
 *
 * @param {string} $language_locale Current language locale.
 *
 * @return {string} Maybe changed language locale.
 *
 * @since 1.3.x
 * @depecated 2.8.5 Used WordPress native `load_plugin_textdomain()`. And can be replaced via WordPress native hook 'plugin_locale'.
 * @hook um_language_locale
 *
 * @example <caption>Change UM language locale.</caption>
 * function my_um_language_locale( $language_locale ) {
 *     $language_locale = 'es_ES';
 *     return $language_locale;
 * }
 * add_filter( 'um_language_locale', 'my_um_language_locale' );
 */

/**
 * Filters the path to the language file (*.mo).
 *
 * @param {string} $language_file Default path to the language file.
 *
 * @return {string} Language file path.
 *
 * @since 1.3.x
 * @depecated 2.8.5 Used WordPress native `load_plugin_textdomain()`. And can be replaced via WordPress native hook 'load_textdomain_mofile'.
 * @hook um_language_file
 *
 * @example <caption>Change UM language file path.</caption>
 * function my_um_language_file( $language_file ) {
 *     $language_file = '{path-to-language-file}';
 *     return $language_file;
 * }
 * add_filter( 'um_language_file', 'my_um_language_file' );
 */
