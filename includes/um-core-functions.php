<?php
/**
 * Ultimate Member Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @version 3.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Get Ultimate Member custom templates (e.g. member directory) passing attributes and including the file.
 *
 * @since 3.0
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $module        Module slug (default: '').
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function um_get_template( $template_name, $args = [], $module = '', $template_path = '', $default_path = '' ) {
	$template = um_locate_template( $template_name, $module, $template_path, $default_path );

	// Allow 3rd party plugin filter template file from their plugin.
	$filter_template = apply_filters( 'um_get_template', $template, $template_name, $args, $module, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			/* translators: %s template */
			_doing_it_wrong( __FUNCTION__, sprintf( __( '<code>%s</code> does not exist.', 'ultimate-member' ), $filter_template ), '2.1' );
			return;
		}
		$template = $filter_template;
	}

	$action_args = [
		'template_name' => $template_name,
		'template_path' => $template_path,
		'module'        => $module,
		'located'       => $template,
		'args'          => $args,
	];

	if ( ! empty( $args ) && is_array( $args ) ) {
		if ( isset( $args['action_args'] ) ) {
			_doing_it_wrong( __FUNCTION__, __( '`action_args` should not be overwritten when calling `um_get_template()`.', 'ultimate-member' ), '3.6.0' );
			unset( $args['action_args'] );
		}
		extract( $args ); // @codingStandardsIgnoreLine
	}

	do_action( 'um_before_template_part', $action_args['template_name'], $action_args['located'], $action_args['module'], $action_args['args'], $action_args['template_path'] );

	include $action_args['located'];

	do_action( 'um_after_template_part', $action_args['template_name'], $action_args['located'], $action_args['module'], $action_args['args'], $action_args['template_path'] );
}


/**
 * Like um_get_template, but returns the HTML instead of outputting.
 *
 * @see um_get_template
 *
 * @since 3.0
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $module        Module slug (default: '').
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string
 */
function um_get_template_html( $template_name, $args = [], $module = '', $template_path = '', $default_path = '' ) {
	ob_start();
	um_get_template( $template_name, $args, $module, $template_path, $default_path );
	return ob_get_clean();
}


/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$blog_id/$locale/$template_path/$template_name
 * yourtheme/$blog_id/$template_path/$template_name
 * yourtheme/$locale/$template_path/$template_name
 * yourtheme/$template_path/$template_name
 * $default_path/$template_name
 *
 * where $locale is site_locale for regular templates, but $user_locale for email templates
 *
 * @since 3.0
 *
 * @param string $template_name Template name.
 * @param string $module Module slug. (default: '').
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return string
 */
function um_locate_template( $template_name, $module = '', $template_path = '', $default_path = '' ) {
	// path in theme
	if ( ! $template_path ) {
		$template_path = UM()->template_path( $module );
	}

	$template_locations = [
		trailingslashit( $template_path ) . $template_name,
	];

	$template_locations = apply_filters( 'um_pre_template_locations', $template_locations, $template_name, $module, $template_path );

	// build multisite blog_ids priority paths
	if ( is_multisite() ) {
		$blog_id = get_current_blog_id();

		$ms_template_locations = array_map( function( $item ) use ( $template_path, $blog_id ) {
			return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $blog_id . '/', $item );
		}, $template_locations );

		$template_locations = array_merge( $ms_template_locations, $template_locations );
	}

	$template_locations = apply_filters( 'um_template_locations', $template_locations, $template_name, $module, $template_path );

	$template_locations = array_map( 'wp_normalize_path', $template_locations );

	$custom_path = apply_filters( 'um_template_structure_custom_path', false, $template_name, $module );
	if ( false === $custom_path || ! is_dir( $custom_path ) ) {
		$template = locate_template( $template_locations );
	} else {
		$template = um_locate_template_custom_path( $template_locations, $custom_path );
	}

	// Get default template in cases:
	// 1. Conflict test constant is defined and TRUE
	// 2. There aren't any proper template in custom or theme directories
	if ( ! $template || UM_TEMPLATE_CONFLICT_TEST ) {
		// default path in plugin
		if ( ! $default_path ) {
			$default_path = UM()->default_templates_path( $module );
		}

		$template = wp_normalize_path( trailingslashit( $default_path ) . $template_name );
	}

	// Return what we found.
	return apply_filters( 'um_locate_template', $template, $template_name, $module, $template_path );
}


/**
 * Retrieve the name of the highest priority template file that exists in custom path.
 *
 * @since 3.0
 *
 * @param string|array $template_locations Template file(s) to search for, in order.
 * @param string       $custom_path        Custom path to the UM templates.
 *
 * @return string The template filename if one is located.
 */
function um_locate_template_custom_path( $template_locations, $custom_path ) {
	$located = '';

	foreach ( (array) $template_locations as $template_location ) {
		if ( ! $template_location ) {
			continue;
		}

		$path = wp_normalize_path( trailingslashit( $custom_path ) . $template_location );
		if ( file_exists( $path ) ) {
			$located = $path;
			break;
		}
	}

	return $located;
}
