<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_pre_template_locations_wpml( $template_locations, $template_name, $module, $template_path ) {
	$language_codes = um_wpml_get_languages_codes();

	if ( $language_codes['default'] != $language_codes['current'] ) {
		$lang = $language_codes['current'];

		$ml_template_locations = array_map( function( $item ) use ( $template_path, $lang ) {
			return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $lang . '/', $item );
		}, $template_locations );

		$template_locations = array_merge( $ml_template_locations, $template_locations );
	}

	return $template_locations;
}
add_filter( 'um_pre_template_locations_common_locale_integration', 'um_pre_template_locations_wpml', 10, 4 );



/**
 * @return array
 */
function um_wpml_get_languages_codes() {
	global $sitepress;

	return array(
		'default' => $sitepress->get_locale_from_language_code( $sitepress->get_default_language() ),
		'current' => $sitepress->get_locale_from_language_code( $sitepress->get_current_language() ),
	);
}


/**
 * Get predefined page translation for current language
 *
 * @param int $page_id
 * @param string $slug
 *
 * @return mixed
 */
function um_get_predefined_page_id_wpml( $page_id, $slug ) {
	global $sitepress;

	$page_id = wpml_object_id_filter( $page_id, 'page', true, $sitepress->get_current_language() );

	return $page_id;
}
add_filter( 'um_get_predefined_page_id', 'um_get_predefined_page_id_wpml', 10, 2 );


/**
 * @param $columns
 *
 * @return array
 */
function um_add_email_templates_column_wpml( $columns ) {
	global $sitepress;

	$active_languages = $sitepress->get_active_languages();
	$current_language = $sitepress->get_current_language();
	unset( $active_languages[ $current_language ] );

	if ( count( $active_languages ) > 0 ) {
		$flags_column = '';
		foreach ( $active_languages as $language_data ) {
			$flags_column .= '<img src="' . $sitepress->get_flag_url( $language_data['code'] ). '" width="18" height="12" alt="' . $language_data['display_name'] . '" title="' . $language_data['display_name'] . '" style="margin:2px" />';
		}

		$columns = UM()->array_insert_after( $columns, 'email', array( 'translations' => $flags_column ) );
	}

	return $columns;
}
add_filter( 'um_email_templates_columns', 'um_add_email_templates_column_wpml', 10, 1 );




function um_emails_list_table_custom_column_content_wpml( $content, $item, $column_name ) {
	if ( 'translations' === $column_name ) {
		global $sitepress;

		$active_languages = $sitepress->get_active_languages();
		$current_language = $sitepress->get_current_language();
		unset( $active_languages[ $current_language ] );

		$html = '';
		foreach ( $active_languages as $language_data ) {
			$html .= um_wpml_get_status_html( $item['key'], $language_data['code'] );
		}

		$content = $html;
	}

	return $content;
}
add_filter( 'um_emails_list_table_custom_column_content', 'um_emails_list_table_custom_column_content_wpml', 10, 3 );


/**
 * @param $template
 * @param $code
 *
 * @return string
 */
function um_wpml_get_status_html( $template, $code ) {
	global $sitepress;

	$link = add_query_arg( array( 'email' => $template, 'lang' => $code ) );

	$active_languages = $sitepress->get_active_languages();
	$translation_map  = array(
		'edit' => array(
			'icon' => 'edit_translation.png',
			'text' => sprintf( __( 'Edit the %s translation', 'sitepress' ), $active_languages[ $code ]['display_name'] ),
		),
		'add'  => array(
			'icon' => 'add_translation.png',
			'text' => sprintf( __( 'Add translation to %s', 'sitepress' ), $active_languages[ $code ]['display_name'] ),
		),
	);

	$default_lang = $sitepress->get_default_language();

	if ( $default_lang === $code ) {
		return um_wpml_render_status_icon( $link, $translation_map['edit']['text'], $translation_map['edit']['icon'] );
	}

	$template_name = um_get_email_template( $template );
	$module = um_get_email_template_module( $template );

	$current_language = $sitepress->get_current_language();
	$sitepress->switch_lang( $code );

	$template_path = UM()->template_path( $module );

	$template_locations = array(
		trailingslashit( $template_path ) . $template_name,
	);

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

	foreach ( $template_locations as $k => $location ) {
		if ( false === strstr( $location, wp_normalize_path( DIRECTORY_SEPARATOR . $code . DIRECTORY_SEPARATOR ) ) ) {
			unset( $template_locations[ $k ] );
		}
	}

	$sitepress->switch_lang( $current_language );

	$custom_path = apply_filters( 'um_template_structure_custom_path', false, $template_name, $module );
	if ( false === $custom_path || ! is_dir( $custom_path ) ) {
		$template_exists = locate_template( $template_locations );
	} else {
		$template_exists = um_locate_template_custom_path( $template_locations, $custom_path );
	}

	// Get default template in cases:
	// 1. Conflict test constant is defined and TRUE
	// 2. There aren't any proper template in custom or theme directories
	$status = 'add';
	if ( ! empty( $template_exists ) ) {
		$status = 'edit';
	}

	return um_wpml_render_status_icon( $link, $translation_map[ $status ]['text'], $translation_map[ $status ]['icon'] );
}


/**
 * @param $link
 * @param $text
 * @param $img
 *
 * @return string
 */
function um_wpml_render_status_icon( $link, $text, $img ) {
	$icon_html = '<a href="' . esc_url( $link ) . '" title="' . esc_attr( $text ) . '">';
	$icon_html .= '<img style="padding:1px;margin:2px;" border="0" src="'
	              . ICL_PLUGIN_URL . '/res/img/'
	              . esc_attr( $img ) . '" alt="'
	              . esc_attr( $text ) . '" width="16" height="16" />';
	$icon_html .= '</a>';

	return $icon_html;
}


/**
 * Adding endings to the "Subject Line" field, depending on the language.
 * @exaple welcome_email_sub_de_DE
 *
 * @param array $section_fields
 * @param string $email_key
 *
 * @return array
 */
function um_admin_settings_change_subject_field_wpml( $section_fields, $email_key ) {
	$language_codes = um_wpml_get_languages_codes();

	if ( $language_codes['default'] === $language_codes['current'] ) {
		return $section_fields;
	}

	$lang       = '_' . $language_codes['current'];
	$option_key = $email_key . '_sub' . $lang;
	$value      = UM()->options()->get( $option_key );

	$section_fields[2]['id']    = $option_key;
	$section_fields[2]['value'] = ! empty( $value ) ? $value : UM()->options()->get( $email_key . '_sub' );

	return $section_fields;
}
add_filter( 'um_admin_settings_email_section_fields', 'um_admin_settings_change_subject_field_wpml', 10, 2 );


/**
 * @param string $subject
 * @param $template
 *
 * @return string
 */
function um_change_email_subject_wpml( $subject, $template ) {
	$language_codes = um_wpml_get_languages_codes();

	if ( $language_codes['default'] === $language_codes['current'] ) {
		return $subject;
	}

	$lang  = '_' . $language_codes['current'];
	$value = UM()->options()->get( $template . '_sub' . $lang );

	$subject = ! empty( $value ) ? $value : $subject;

	return $subject;
}
add_filter( 'um_email_send_subject', 'um_change_email_subject_wpml', 10, 2 );


/**
 * @param array $template_locations
 * @param $template_name
 * @param $module
 * @param $template_path
 *
 * @return array
 */
function um_change_email_templates_locations_wpml( $template_locations, $template_name, $module, $template_path ) {
	global $sitepress;

	$code = $sitepress->get_current_language();
	$code_default = $sitepress->get_default_language();

	if ( $code === $code_default ) {
		return $template_locations;
	}

	foreach ( $template_locations as $k => $location ) {
		if ( false === strstr( $location, wp_normalize_path( DIRECTORY_SEPARATOR . $code . DIRECTORY_SEPARATOR ) ) ) {
			unset( $template_locations[ $k ] );
		}
	}

	return $template_locations;
}
add_filter( 'um_save_email_templates_locations', 'um_change_email_templates_locations_wpml', 10, 4 );


/**
 * Extends rewrite rules
 *
 * @param array $rules
 *
 * @return array
 */
function um_add_rewrite_rules_wpml( $rules ) {
	global $sitepress;

	$newrules = array();

	$active_languages = $sitepress->get_active_languages();

	$user_page_id = um_get_predefined_page_id( 'user' );
	if ( $user_page_id ) {

		$user = get_post( $user_page_id );
		foreach ( $active_languages as $language_code => $language ) {

			$lang_post_id = wpml_object_id_filter( $user_page_id, 'post', false, $language_code );
			$lang_post_obj = get_post( $lang_post_id );

			if ( isset( $lang_post_obj->post_name ) && $lang_post_obj->post_name != $user->post_name ) {
				$user_slug = $lang_post_obj->post_name;
				$newrules[ $user_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
			}
		}
	}

	$account_page_id = um_get_predefined_page_id( 'account' );
	if ( $account_page_id ) {
		$account = get_post( $account_page_id );

		foreach ( $active_languages as $language_code => $language ) {

			$lang_post_id = wpml_object_id_filter( $account_page_id, 'post', false, $language_code );
			$lang_post_obj = get_post( $lang_post_id );

			if ( isset( $lang_post_obj->post_name ) && $lang_post_obj->post_name != $account->post_name ) {
				$account_slug = $lang_post_obj->post_name;
				$newrules[ $account_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
			}
		}
	}

	return $newrules + $rules;
}
add_filter( 'rewrite_rules_array', 'um_add_rewrite_rules_wpml', 10, 1 );


function um_admin_settings_get_pages_list_wpml() {
	$return = array();

	$current_lang_query = new \WP_Query( array(
		'post_type'           => 'page',
		's'                   => sanitize_text_field( $_GET['search'] ), // the search query
		'post_status'         => 'publish', // if you don't want drafts to be returned
		'ignore_sticky_posts' => 1,
		'fields'              => 'ids',
		'posts_per_page'      => -1,
	) );

	global $sitepress;

	$code         = $sitepress->get_current_language();
	$code_default = $sitepress->get_default_language();

	$posts = array();
	if ( ! empty( $current_lang_posts = $current_lang_query->get_posts() ) ) {
		$posts = array_merge( $posts, $current_lang_posts );
	}

	if ( $code !== $code_default ) {
		$sitepress->switch_lang( $code_default );

		$default_lang_query = new \WP_Query( array(
			'post_type'           => 'page',
			's'                   => sanitize_text_field( $_GET['search'] ), // the search query
			'post_status'         => 'publish', // if you don't want drafts to be returned
			'ignore_sticky_posts' => 1,
			'fields'              => 'ids',
			'posts_per_page'      => -1,
		) );

		if ( ! empty( $default_lang_posts = $default_lang_query->get_posts() ) ) {
			foreach ( $default_lang_posts as $k => $post_id ) {
				$lang_post_id = wpml_object_id_filter( $post_id, 'page', true, $code );
				if ( $lang_post_id && in_array( $lang_post_id, $posts, true ) ) {
					unset( $default_lang_posts[ $k ] );
				}
			}
			$posts = array_merge( $posts, array_values( $default_lang_posts ) );
		}
	}

	$active_languages = $sitepress->get_active_languages();

	foreach ( $active_languages as $language_code ) {
		if ( $language_code['code'] === $code || $language_code['code'] === $code_default ) {
			continue;
		}

		$sitepress->switch_lang( $language_code['code'] );

		$active_lang_query = new \WP_Query( array(
			'post_type'           => 'page',
			's'                   => sanitize_text_field( $_GET['search'] ), // the search query
			'post_status'         => 'publish', // if you don't want drafts to be returned
			'ignore_sticky_posts' => 1,
			'fields'              => 'ids',
			'posts_per_page'      => -1,
		) );

		if ( ! empty( $active_lang_posts = $active_lang_query->get_posts() ) ) {
			foreach ( $active_lang_posts as $k => $post_id ) {
				$current_lang_post_id = wpml_object_id_filter( $post_id, 'page', true, $code );
				$default_lang_post_id = wpml_object_id_filter( $post_id, 'page', true, $code_default );
				if ( ( $current_lang_post_id && in_array( $current_lang_post_id, $posts, true ) ) ||
				     ( $default_lang_post_id && in_array( $default_lang_post_id, $posts, true ) ) ) {
					unset( $active_lang_posts[ $k ] );
				}
			}
			$posts = array_merge( $posts, array_values( $active_lang_posts ) );
		}
	}

	$sitepress->switch_lang( $code );

	// you can use WP_Query, query_posts() or get_posts() here - it doesn't matter
	$search_results = new \WP_Query( array(
		'post_type'           => 'page',
		's'                   => sanitize_text_field( $_GET['search'] ), // the search query
		'post_status'         => 'publish', // if you don't want drafts to be returned
		'ignore_sticky_posts' => 1,
		'posts_per_page'      => 10, // how much to show at once
		'paged'               => absint( $_GET['page'] ),
		'suppress_filters'    => true, // ignore WPML default filters for languages
		'orderby'             => 'title',
		'order'               => 'asc',
		'post__in'            => $posts,
	) );

	$posts = $search_results->get_posts();

	if ( ! empty( $posts ) ) {
		foreach ( $posts as $post ) {
			// shorten the title a little
			$title = ( mb_strlen( $post->post_title ) > 50 ) ? mb_substr( $post->post_title, 0, 49 ) . '...' : $post->post_title;
			$title = sprintf( __( '%s (ID: %s)', 'ultimate-member' ), $title, $post->ID );
			$return[] = array( $post->ID, $title ); // array( Post ID, Post Title )
		}
	}

	$return['total_count'] = $search_results->found_posts;
	return $return;
}
add_filter( 'um_admin_settings_get_pages_list', 'um_admin_settings_get_pages_list_wpml', 10 );


/**
 * @param false $pre_result
 * @param int $page_id
 *
 * @return array
 */
function um_admin_settings_pages_list_value_wpml( $pre_result, $page_id ) {
	if ( ! empty( $opt_value = UM()->options()->get( $page_id ) ) ) {
		global $sitepress;

		$page_id = wpml_object_id_filter( $opt_value, 'page', true, $sitepress->get_current_language() );
		if ( $page_id ) {
			$opt_value = $page_id;
		}

		if ( 'publish' === get_post_status( $opt_value ) ) {
			$title = get_the_title( $opt_value );
			$title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;
			$title = sprintf( __( '%s (ID: %s)', 'ultimate-member' ), $title, $opt_value );

			$pre_result = array( $opt_value => $title );
			$pre_result['page_value'] = $opt_value;
		}
	}

	return $pre_result;
}
add_filter( 'um_admin_settings_pages_list_value', 'um_admin_settings_pages_list_value_wpml', 10, 2 );


/**
 * @param array $variables
 *
 * @return array
 */
function um_common_js_variables_wpml( $variables ) {
	global $sitepress;

	$variables['locale'] = $sitepress->get_current_language();
	return $variables;
}
add_filter( 'um_common_js_variables', 'um_common_js_variables_wpml', 10, 1 );


/**
 * @param string $locale
 */
function um_admin_init_locale_wpml( $locale ) {
	global $sitepress;
	$sitepress->switch_lang( $locale );
}
add_action( 'um_admin_init_locale', 'um_admin_init_locale_wpml', 10, 1 );


/**
 * UM filter - Restore original arguments on translated page
 *
 * @todo Customize this form metadata
 *
 * @description Restore original arguments on load shortcode if they are missed in the WPML translation
 * @hook um_pre_args_setup
 *
 * @global \SitePress $sitepress
 * @param array $args
 * @return array
 */
function um_pre_args_setup_wpml( $args ) {
	global $sitepress;

	$original_form_id = $sitepress->get_object_id( $args['form_id'], 'post', true, $sitepress->get_default_language() );

	if ( $original_form_id != $args['form_id'] ) {
		$original_post_data = UM()->query()->post_data( $original_form_id );

		foreach ( $original_post_data as $key => $value ) {
			if ( ! isset( $args[ $key ] ) ) {
				$args[ $key ] = $value;
			}
		}
	}

	return $args;
}
//add_filter( 'um_pre_args_setup', 'um_pre_args_setup_wpml', 20, 1 );
