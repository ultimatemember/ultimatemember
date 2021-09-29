<?php if ( ! defined( 'ABSPATH' ) ) exit;


function um_pre_template_locations_polylang( $template_locations, $template_name, $module, $template_path ) {
	$language_codes = um_polylang_get_languages_codes();

	if ( $language_codes['default'] != $language_codes['current'] ) {
		$lang = $language_codes['current'];

		$ml_template_locations = array_map( function( $item ) use ( $template_path, $lang ) {
			return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $lang . '/', $item );
		}, $template_locations );

		$template_locations = array_merge( $ml_template_locations, $template_locations );
	}

	return $template_locations;
}
add_filter( 'um_pre_template_locations_common_locale_integration', 'um_pre_template_locations_polylang', 10, 4 );


/**
 * Get default and current locales.
 *
 * @since  3.0
 *
 * @return array
 */
function um_polylang_get_languages_codes() {
	return array(
		'default' => pll_default_language( 'locale' ),
		'current' => pll_current_language( 'locale' ),
	);
}


/**
 * @param int $page_id
 * @param string $slug
 *
 * @return mixed
 */
function um_get_predefined_page_id_polylang( $page_id, $slug ) {
	if ( $post = pll_get_post( $page_id ) ) {
		$page_id = $post;
	}

	return $page_id;
}
add_filter( 'um_get_predefined_page_id', 'um_get_predefined_page_id_polylang', 10, 2 );



/**
 * @param $columns
 *
 * @return array
 */
function um_add_email_templates_column_polylang( $columns ) {
	global $polylang;

	if ( count( pll_languages_list() ) > 0 ) {
		$flags_column = '';
		foreach ( pll_languages_list() as $language_code ) {
			if ( $language_code === pll_current_language() ) {
				continue;
			}
			$language = $polylang->model->get_language( $language_code );
			$flags_column .= '<span class="um-flag" style="margin:2px">' . $language->flag . '</span>';
		}

		$columns = UM()->array_insert_after( $columns, 'email', array( 'translations' => $flags_column ) );
	}

	return $columns;
}
add_filter( 'um_email_templates_columns', 'um_add_email_templates_column_polylang', 10, 1 );




function um_emails_list_table_custom_column_content_polylang( $content, $item, $column_name ) {
	if ( 'translations' === $column_name ) {
		$html = '';

		foreach ( pll_languages_list() as $language_code ) {
			if ( $language_code === pll_current_language() ) {
				continue;
			}
			$html .= um_polylang_get_status_html( $item['key'], $language_code );
		}

		$content = $html;
	}

	return $content;
}
add_filter( 'um_emails_list_table_custom_column_content', 'um_emails_list_table_custom_column_content_polylang', 10, 3 );


/**
 * @param $template
 * @param $code
 *
 * @return string
 */
function um_polylang_get_status_html( $template, $code ) {
	global $polylang;

	$link = add_query_arg( array( 'email' => $template, 'lang' => $code ) );

	$language = $polylang->model->get_language( $code );
	$default_lang = pll_default_language();

	if ( $default_lang === $code ) {
		$hint = sprintf( __( 'Edit the translation in %s', 'polylang' ), $language->name );
		$icon_html = sprintf( '<a href="%1$s" title="%2$s" class="pll_icon_edit"><span class="screen-reader-text">%3$s</span></a>', esc_url( $link ), esc_html( $hint ), esc_html( $hint )
		);
		return $icon_html;
	}

	$template_name = um_get_email_template( $template );
	$module = um_get_email_template_module( $template );

	$current_language = pll_current_language();
	$current_language = $polylang->model->get_language( $current_language );

	PLL()->curlang = $language;

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
		if ( false === strstr( $location, $code ) ) {
			unset( $template_locations[ $k ] );
		}
	}

	PLL()->curlang = $current_language;

	$custom_path = apply_filters( 'um_template_structure_custom_path', false, $template_name, $module );
	if ( false === $custom_path || ! is_dir( $custom_path ) ) {
		$template_exists = locate_template( $template_locations );
	} else {
		$template_exists = um_locate_template_custom_path( $template_locations, $custom_path );
	}

	// Get default template in cases:
	// 1. Conflict test constant is defined and TRUE
	// 2. There aren't any proper template in custom or theme directories
	if ( ! empty( $template_exists ) ) {
		$hint = sprintf( __( 'Edit the translation in %s', 'polylang' ), $language->name );
		$icon_html = sprintf( '<a href="%1$s" title="%2$s" class="pll_icon_edit"><span class="screen-reader-text">%3$s</span></a>', esc_url( $link ), esc_html( $hint ), esc_html( $hint )
		);
	} else {
		$hint = sprintf( __( 'Add a translation in %s', 'polylang' ), $language->name );
		$icon_html = sprintf( '<a href="%1$s" title="%2$s" class="pll_icon_add"><span class="screen-reader-text">%3$s</span></a>', esc_url( $link ), esc_attr( $hint ), esc_html( $hint )
		);
	}

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
function um_admin_settings_change_subject_field( $section_fields, $email_key ) {
	$language_codes = um_polylang_get_languages_codes();

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
add_filter( 'um_admin_settings_email_section_fields', 'um_admin_settings_change_subject_field', 10, 2 );


/**
 * @param string $subject
 * @param $template
 *
 * @return string
 */
function um_change_email_subject_polylang( $subject, $template ) {
	$language_codes = um_polylang_get_languages_codes();

	if ( $language_codes['default'] === $language_codes['current'] ) {
		return $subject;
	}

	$lang  = '_' . $language_codes['current'];
	$value = UM()->options()->get( $template . '_sub' . $lang );

	$subject = ! empty( $value ) ? $value : $subject;

	return $subject;
}
add_filter( 'um_email_send_subject', 'um_change_email_subject_polylang', 10, 2 );


/**
 * @param array $template_locations
 * @param $template_name
 * @param $module
 * @param $template_path
 *
 * @return array
 */
function um_change_email_templates_locations_polylang( $template_locations, $template_name, $module, $template_path ) {
	$code = pll_current_language();
	$code_default = pll_default_language();

	if ( $code === $code_default ) {
		return $template_locations;
	}

	foreach ( $template_locations as $k => $location ) {
		if ( false === strstr( $location, $code ) ) {
			unset( $template_locations[ $k ] );
		}
	}

	return $template_locations;
}
add_filter( 'um_save_email_templates_locations', 'um_change_email_templates_locations_polylang', 10, 4 );


/**
 * Extends rewrite rules
 *
 * @param array $rules
 *
 * @return array
 */
function um_add_rewrite_rules_polylang( $rules ) {
	global $polylang;

	$active_languages = pll_languages_list();

	$newrules = array();

	// Account
	$account_page_id = um_get_predefined_page_id( 'account' );
	if ( $account_page_id ) {
		$account = get_post( $account_page_id );

		foreach ( $active_languages as $language_code ) {
			if ( $language_code === pll_default_language() && $polylang->options['hide_default'] ) {
				continue;
			}

			$lang_post_id = pll_get_post( $account_page_id, $language_code );
			$lang_post_obj = get_post( $lang_post_id );

			if ( isset( $account->post_name ) && isset( $lang_post_obj->post_name ) ) {
				$lang_page_slug = $lang_post_obj->post_name;

				if ( $polylang->options['force_lang'] === 1 ) {
					$newrules[ $language_code . '/' . $lang_page_slug . '/([^/]+)?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_tab=$matches[1]&lang=' . $language_code;
				}

				$newrules[ $lang_page_slug . '/([^/]+)?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_tab=$matches[1]&lang=' . $language_code;
			}
		}
	}

	// Profile
	$user_page_id = um_get_predefined_page_id( 'user' );
	if ( $user_page_id ) {
		$user = get_post( $user_page_id );

		foreach ( $active_languages as $language_code ) {
			if ( $language_code === pll_default_language() && $polylang->options['hide_default'] ) {
				continue;
			}
			$lang_post_id = pll_get_post( $user_page_id, $language_code );
			$lang_post_obj = get_post( $lang_post_id );

			if ( isset( $user->post_name ) && isset( $lang_post_obj->post_name ) ) {
				$lang_page_slug = $lang_post_obj->post_name;

				if ( $polylang->options['force_lang'] === 1 ) {
					$newrules[ $language_code . '/' . $lang_page_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
				}

				$newrules[ $lang_page_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
			}
		}
	}

	return $newrules + $rules;
}
add_filter( 'rewrite_rules_array', 'um_add_rewrite_rules_polylang', 10, 1 );


/**
 * @return array
 */
function um_admin_settings_get_pages_list_polylang() {
	$return = array();

	$current_lang_query = new \WP_Query( array(
		'post_type'           => 'page',
		's'                   => sanitize_text_field( $_GET['search'] ), // the search query
		'post_status'         => 'publish', // if you don't want drafts to be returned
		'ignore_sticky_posts' => 1,
		'fields'              => 'ids',
		'posts_per_page'      => -1,
	) );

	$posts = array();
	if ( ! empty( $current_lang_posts = $current_lang_query->get_posts() ) ) {
		$posts = array_merge( $posts, $current_lang_posts );
	}

	if ( pll_current_language() !== pll_default_language() ) {
		$default_lang_query = new \WP_Query( array(
			'post_type'           => 'page',
			's'                   => sanitize_text_field( $_GET['search'] ), // the search query
			'post_status'         => 'publish', // if you don't want drafts to be returned
			'ignore_sticky_posts' => 1,
			'fields'              => 'ids',
			'lang'                => pll_default_language(),
			'posts_per_page'      => -1,
		) );

		if ( ! empty( $default_lang_posts = $default_lang_query->get_posts() ) ) {
			foreach ( $default_lang_posts as $k => $post_id ) {
				$lang_post_id = pll_get_post( $post_id, pll_current_language() );
				if ( in_array( $lang_post_id, $posts, true ) ) {
					unset( $default_lang_posts[ $k ] );
				}
			}
			$posts = array_merge( $posts, array_values( $default_lang_posts ) );
		}
	}

	$active_languages = pll_languages_list();

	foreach ( $active_languages as $language_code ) {
		if ( $language_code === pll_current_language() || $language_code === pll_default_language() ) {
			continue;
		}

		$active_lang_query = new \WP_Query( array(
			'post_type'           => 'page',
			's'                   => sanitize_text_field( $_GET['search'] ), // the search query
			'post_status'         => 'publish', // if you don't want drafts to be returned
			'ignore_sticky_posts' => 1,
			'fields'              => 'ids',
			'lang'                => $language_code,
			'posts_per_page'      => -1,
		) );

		if ( ! empty( $active_lang_posts = $active_lang_query->get_posts() ) ) {
			foreach ( $active_lang_posts as $k => $post_id ) {
				$current_lang_post_id = pll_get_post( $post_id, pll_current_language() );
				$default_lang_post_id = pll_get_post( $post_id, pll_default_language() );
				if ( in_array( $current_lang_post_id, $posts, true ) || in_array( $default_lang_post_id, $posts, true ) ) {
					unset( $active_lang_posts[ $k ] );
				}
			}
			$posts = array_merge( $posts, array_values( $active_lang_posts ) );
		}
	}

	// you can use WP_Query, query_posts() or get_posts() here - it doesn't matter
	$search_results = new \WP_Query( array(
		'post_type'           => 'page',
		's'                   => sanitize_text_field( $_GET['search'] ), // the search query
		'post_status'         => 'publish', // if you don't want drafts to be returned
		'ignore_sticky_posts' => 1,
		'posts_per_page'      => 10, // how much to show at once
		'paged'               => absint( $_GET['page'] ),
		'orderby'             => 'title',
		'order'               => 'asc',
		'lang'                => '', // set empty language for getting posts of all languages
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
add_filter( 'um_admin_settings_get_pages_list', 'um_admin_settings_get_pages_list_polylang', 10 );


/**
 * @param false $pre_result
 * @param int $page_id
 *
 * @return array
 */
function um_admin_settings_pages_list_value_polylang( $pre_result, $page_id ) {
	if ( ! empty( $opt_value = UM()->options()->get( $page_id ) ) ) {

		if ( $post = pll_get_post( $opt_value ) ) {
			$opt_value = $post;
		}

		$title = get_the_title( $opt_value );
		$title = ( mb_strlen( $title ) > 50 ) ? mb_substr( $title, 0, 49 ) . '...' : $title;
		$title = sprintf( __( '%s (ID: %s)', 'ultimate-member' ), $title, $opt_value );

		$pre_result = array( $opt_value => $title );
		$pre_result['page_value'] = $opt_value;
	}

	return $pre_result;
}
add_filter( 'um_admin_settings_pages_list_value', 'um_admin_settings_pages_list_value_polylang', 10, 2 );


/**
 * @param array $variables
 *
 * @return array
 */
function um_common_js_variables_polylang( $variables ) {
	$variables['locale'] = pll_current_language();
	return $variables;
}
add_filter( 'um_common_js_variables', 'um_common_js_variables_polylang', 10, 1 );


/**
 * @param string $locale
 */
function um_admin_init_locale_polylang( $locale ) {
	global $polylang;
	PLL()->curlang = $polylang->model->get_language( $locale );
}
add_action( 'um_admin_init_locale', 'um_admin_init_locale_polylang', 10, 1 );
