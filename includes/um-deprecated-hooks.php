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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
 * @hook um_api_output_{$format}
 *
 * @param {array}  $data     API data.
 * @param {object} $rest_api UM REST API class.
 */

/**
 * Fires after UM REST API output.
 *
 * @since 1.3.x
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
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
 * @depecated 2.12.0
 * @hook um_api_output_format
 */

/**
 * Fires for displaying content in supporting header row on User Profile.
 *
 * @param {array} $args User Profile data.
 *
 * @since 1.3.x
 * @depecated 3.0.0 Please use 'um_after_profile_header_name' hook instead.
 * @hook  um_after_profile_header_name_args
 */

/**
 * Filters the allowed image types.
 *
 * @param {array} $types Allowed image types.
 *
 * @return {array} Allowed image types.
 *
 * @since 1.3.x
 * @depecated 3.0.0 Please use 'um_allowed_default_image_types' hook instead.
 * @hook  um_allowed_image_types
 */

/**
 * Filters the allowed file types.
 *
 * @param {array} $types Allowed file types.
 *
 * @return {array} Allowed file types.
 *
 * @since 1.3.x
 * @depecated 3.0.0 Please use 'um_allowed_default_file_types' hook instead.
 * @hook  um_allowed_file_types
 */

/**
 * Filters the default cover URL.
 *
 * @param {string} $url Default cover URL.
 *
 * @return {string} Cover URL.
 *
 * @since 1.3.67
 * @depecated 3.0.0 Please use 'um_default_cover_url' hook instead.
 * @hook  um_get_default_cover_uri_filter
 */

/**
 * Filters the cover photo size for mobile device.
 *
 * @param {string} $size Default cover photo size for mobile device.
 *
 * @return {string} Cover photo size.
 *
 * @since 2.0.0
 * @depecated 3.0.0 Please use 'um_cover_photo_size' hook instead with checking `wp_is_mobile()` inside the callback.
 * @hook  um_mobile_cover_photo
 */

/**
 * Filters the users privacy.
 *
 * @param {bool}   $is_private_case Does the current case is privacy.
 * @param {string} $privacy         Privacy value.
 * @param {int}    $user_id         User ID to check for the current user.
 *
 * @return {bool} Current privacy case.
 *
 * @since 1.3.x
 * @depecated 3.0.0 Please use 'um_can_view_private_user_profile' hook instead.
 *
 * @hook um_is_private_filter_hook
 */

/**
 * Filters Ultimate Member predefined pages.
 *
 * @param {array} $pages Predefined pages.
 *
 * @return {array} Predefined pages.
 *
 * @since 1.3.x
 * @depecated 3.0.0 Please use 'um_predefined_pages' hook instead.
 * @hook um_core_pages
 */

/**
 * Filters the base URL of the UM profile page.
 *
 * @since 1.3.x
 * @deprecated 2.6.3 Use <a href="https://developer.wordpress.org/reference/hooks/post_link/" target="_blank" title="'post_link' hook article on developer.wordpress.org">'post_link'</a> instead.
 * @hook um_localize_permalink_filter
 *
 * @param {string} $profile_url Profile URL.
 * @param {int}    $page_id     Profile Page ID.
 *
 * @return {string} Profile URL.
 */
