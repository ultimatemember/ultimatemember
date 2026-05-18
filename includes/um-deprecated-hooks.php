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

/**
 * Fires after user status changed.
 *
 * @param {int} $user_id User ID.
 *
 * @since 1.3.x
 * @depecated 2.8.7 use action hook `um_after_user_status_is_changed` instead.
 * @hook um_after_user_status_is_changed_hook
 */

/**
 * Fires just before User status is changed.
 *
 * @since 1.3.x
 * @depecated 2.8.7 use action hook `um_before_user_status_is_set` instead.
 * @hook um_when_status_is_set
 *
 * @param {int} $user_id User ID. Since 2.0
 */

/**
 * Fires for handle admin user_action scripts.
 *
 * @since 1.3.x
 * @depecated 2.8.7 WordPress native `handle_bulk_actions-users` hook is used. Use action hook `um_handle_bulk_actions-users-{$current_action}` for custom user bulk actions instead.
 * @hook um_admin_user_action_hook
 *
 * @param {string} $bulk_action Bulk action key
 */

/**
 * Fires for handle admin user_action scripts.
 * Where $bulk_action is a bulk action key
 *
 * @since 1.3.x
 * @depecated 2.8.7 WordPress native `handle_bulk_actions-users` hook is used. Use action hook `um_handle_bulk_actions-users-{$current_action}` for custom user bulk actions instead.
 * @hook um_admin_user_action_{$bulk_action}_hook
 */

/**
 * Fires for handle custom admin user_action scripts.
 * Where $action is a bulk action key
 *
 * @since 1.3.x
 * @depecated 2.8.7 WordPress native `handle_bulk_actions-users` hook is used. Use action hook `um_handle_bulk_actions-users-{$current_action}` for custom user bulk actions instead.
 * @hook um_admin_custom_hook_{$action}
 *
 * @param {int} $user_id User ID.
 */

/**
 * Filters the WP Users list table views.
 *
 * Fully deprecated. Please use filter 'um_user_statuses_admin_filter_options' hook instead since 2.8.7.
 *
 * @param {array} $views List table filter views.
 *
 * @return {array} List table filter views.
 *
 * @since 1.3.x
 * @depecated 2.8.7 Fully deprecated because there is used dropdown with statuses instead of list table views.
 * @hook um_admin_views_users
 */

/**
 * Filters the plugin's textdomain.
 *
 * @param {string} $domain Plugin's textdomain.
 *
 * @return {string} Maybe changed plugin's textdomain.
 *
 * @since 1.3.x
 * @depecated 2.9.2 Fully deprecated because minimum required WP version is 5.5, but we cannot use `load_plugin_textdomain()` function since 4.6.0 if the plugin is situated in wp.org plugins directory.
 * @hook um_language_textdomain
 */

/**
 * Fires before UM REST API output.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_output_before
 *
 * @param {array}  $data     API data.
 * @param {object} $rest_api UM REST API class.
 * @param {string} $format   Data format.
 */

/**
 * Fires for displaying UM REST API output with other $format.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_output_{$format}
 *
 * @param {array}  $data     API data.
 * @param {object} $rest_api UM REST API class.
 */

/**
 * Fires after UM REST API output.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_output_after
 *
 * @param {array}  $data     API data.
 * @param {object} $rest_api UM REST API class.
 * @param {string} $format   Data format.
 */

/**
 * Filters the output data for Rest API userdata call.
 *
 * @param {mixed} $val      User data value.
 * @param {int}   $user_id  User ID.
 *
 * @return {mixed} User data value.
 *
 * @since 2.0
 * @depecated 2.11.5
 * @hook um_rest_userdata
 *
 * @example <caption>Force change the output data for Rest API userdata call.</caption>
 * function my_custom_um_rest_userdata( $value, $user_id  ) {
 *     // your code here
 *     return $response;
 * }
 * add_filter( 'um_rest_userdata', 'my_custom_um_rest_userdata', 10, 2 );
 */

/**
 * Filters the output data for Rest API user authentication call.
 *
 * @param {array}  $response REST API response.
 * @param {string} $field    Field Options.
 * @param {int}    $user_id  User ID.
 *
 * @return {array} REST API response.
 *
 * @since 2.0
 * @depecated 2.11.5
 * @hook um_rest_get_auser
 *
 * @example <caption>Force change the output data for Rest API user authentication call.</caption>
 * function my_custom_um_rest_get_auser( $response, $field, $user_id  ) {
 *     // your code here
 *     return $response;
 * }
 * add_filter( 'um_rest_get_auser', 'my_custom_um_rest_get_auser', 10, 3 );
 */

/**
 * Filters the REST API output format. JSON by default.
 *
 * @param {string} $format REST API output format.
 *
 * @return {string} REST API output format.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_output_format
 *
 * @example <caption>Changing the REST API output format.</caption>
 * function my_custom_um_api_output_format( $format ) {
 *     // your code here
 *     $format = 'xml';
 *     return $format;
 * }
 * add_filter( 'um_api_output_format', 'my_custom_um_api_output_format' );
 */

/**
 * Filters the API request logging to be turned off.
 * Default is true.
 *
 * @param {bool} $allow_log REST API log is allowed.
 *
 * @return {bool} REST API log is allowed.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_log_requests
 */

/**
 * Filters the UM REST API query attributes.
 *
 * @param {array} $data REST API query data.
 * @param {string} $query_mode REST API query mode.
 * @param {array} $args REST API query Arguments.
 *
 * @return {array} REST API query data.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_rest_query_mode
 */

/**
 * Filters the UM REST API output data.
 *
 * @param {array} $data Output data.
 * @param {string} $query_mode REST API query mode.
 * @param {object} $api_class REST API class instance.
 *
 * @return {array} Output data.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_output_data
 */

/**
 * Filters the output data for Rest API get stats call.
 *
 * @param {array} $response Output data.
 *
 * @return {array} Output data.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_rest_api_get_stats
 */

/**
 * Filters the UM REST API whitelist query modes.
 *
 * @param {array} $query_mode Allowed query modes.
 *
 * @return {array} Allowed query modes.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_valid_query_modes
 */

/**
 * Filters the UM REST API output format. JSON is default.
 *
 * @param {string} $format UM REST API output format.
 *
 * @return {array} UM REST API output format.
 *
 * @since 1.3.x
 * @depecated 2.11.5
 * @hook um_api_output_format
 */
