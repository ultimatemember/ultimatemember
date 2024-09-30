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
