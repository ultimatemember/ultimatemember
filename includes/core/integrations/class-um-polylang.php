<?php
namespace um\core\integrations;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'um\core\integrations\UM_Polylang' ) ) {
	return;
}


// Interface UM_Multilingual
require_once __DIR__ . '/interface-um-multilingual.php';


/**
 * Class UM_Polylang
 *
 * @example UM()->external_integrations()->polylang()
 * @link    https://polylang.wordpress.com/documentation/ Polylang Documentation
 * @package um\core\integrations
 */
class UM_Polylang implements UM_Multilingual {

	/**
	 * Class UM_Polylang constructor.
	 */
	public function __construct() {
		if ( $this->is_active() ) {

			/* Email */
			add_filter( 'um_admin_settings_email_section_fields', array( &$this, 'admin_settings_email_section_fields' ), 10, 2 );
			add_filter( 'um_change_email_template_file', array( &$this, 'change_email_template_file' ), 10, 1 );
			add_filter( 'um_email_send_subject', array( &$this, 'localize_email_subject' ), 10, 2 );
			add_filter( 'um_email_templates_columns', array( &$this, 'emails_column_header' ), 10, 1 );
			add_filter( 'um_locate_email_template', array( &$this, 'locate_email_template' ), 10, 2 );

			/* Form */
			add_action( 'um_after_user_updated', array( &$this, 'profile_bio_update' ), 20, 2 );
			add_filter( 'um_field_value', array( &$this, 'profile_bio_value' ), 20, 5 );
			add_filter( 'um_profile_bio_key', array( &$this, 'profile_bio_key' ), 20, 2 );
			add_filter( 'um_pre_args_setup', array( &$this, 'shortcode_pre_args_setup' ), 20, 1 );

			/* Permalink */
			add_filter( 'rewrite_rules_array', array( &$this, 'add_rewrite_rules' ), 10, 1 );
			add_filter( 'um_is_core_page', array( &$this, 'is_core_page' ), 10, 2 );
			add_filter( 'um_get_core_page_filter', array( &$this, 'localize_core_page_url' ), 10, 3 );
			add_filter( 'um_localize_permalink_filter', array( &$this, 'localize_profile_permalink' ), 10, 2 );
		}
	}

	/**
	 * Add UM rewrite rules for the Account page and Profile page
	 *
	 * @since  2.1.7
	 *
	 * @global object $polylang  The Polylang instance
	 * @param  array  $rules
	 * @return array
	 */
	public function add_rewrite_rules( $rules ) {
		global $polylang;

		if ( $this->is_active() ) {
			$active_languages = pll_languages_list();

			$newrules = array();

			// Account
			if ( isset( UM()->config()->permalinks['account'] ) ) {
				$account_page_id = UM()->config()->permalinks['account'];
				$account = get_post( $account_page_id );

				foreach ( $active_languages as $language_code ) {
					if( $language_code === pll_default_language() && $polylang->options['hide_default'] ){
						continue;
					}
					$lang_post_id = pll_get_post( $account_page_id, $language_code );
					$lang_post_obj = get_post( $lang_post_id );

					if ( isset( $account->post_name ) && isset( $lang_post_obj->post_name ) ) {
						$lang_page_slug = $lang_post_obj->post_name;

						if( $polylang->options['force_lang'] === 1 ){
							$newrules[$language_code . '/' . $lang_page_slug . '/([^/]+)/?$'] = 'index.php?page_id=' . $lang_post_id . '&um_tab=$matches[1]&lang=' . $language_code;
						}

						$newrules[$lang_page_slug . '/([^/]+)/?$'] = 'index.php?page_id=' . $lang_post_id . '&um_tab=$matches[1]&lang=' . $language_code;
					}
				}
			}

			// Profile
			if ( isset( UM()->config()->permalinks['user'] ) ) {
				$user_page_id = UM()->config()->permalinks['user'];
				$user = get_post( $user_page_id );

				foreach ( $active_languages as $language_code ) {
					if( $language_code === pll_default_language() && $polylang->options['hide_default'] ){
						continue;
					}
					$lang_post_id = pll_get_post( $user_page_id, $language_code );
					$lang_post_obj = get_post( $lang_post_id );

					if ( isset( $user->post_name ) && isset( $lang_post_obj->post_name ) ) {
						$lang_page_slug = $lang_post_obj->post_name;

						if( $polylang->options['force_lang'] === 1 ){
							$newrules[$language_code . '/' . $lang_page_slug . '/([^/]+)/?$'] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
						}

						$newrules[$lang_page_slug . '/([^/]+)/?$'] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
					}
				}
			}

			$rules = $newrules + $rules;
		}

		return $rules;
	}

	/**
	 * Adding endings to the "Subject Line" field, depending on the language.
	 *
	 * @since  2.1.6
	 * @exaple change 'welcome_email_sub' to 'welcome_email_sub_de_DE'
	 *
	 * @param  array  $section_fields  The email template fields
	 * @param  string $email_key       The email template slug
	 * @return array
	 */
	public function admin_settings_email_section_fields( $section_fields, $email_key ) {
		if ( $this->is_active() ) {
			$locale = '';
			$language_codes = $this->get_languages_codes();
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$locale = '_' . $language_codes['current'];
			}

			$value_default = UM()->options()->get( $email_key . '_sub' );
			$value = UM()->options()->get( $email_key . '_sub' . $locale );

			$section_fields[2]['id'] = $email_key . '_sub' . $locale;
			$section_fields[2]['value'] = !empty( $value ) ? $value : $value_default;
		}

		return $section_fields;
	}

	/**
	 * Change email template for searching in the theme folder.
	 *
	 * @since  2.1.6
	 *
	 * @param  string $template  The email template slug
	 * @return string
	 */
	public function change_email_template_file( $template ) {
		if ( $this->is_active() ) {
			$language_codes = $this->get_languages_codes();
			if ( $language_codes['default'] !== $language_codes['current'] ) {
				$template = $language_codes['current'] . '/' . $template;
			}
		}

		return $template;
	}

	/**
	 *
	 * Add cell for the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @param  array  $item  The email template data
	 * @return string
	 */
	public function emails_column_content( $item ) {
		$html = '';

		if ( $this->is_active() ) {
			foreach ( pll_languages_list() as $language_code ) {
				if ( $language_code === pll_default_language() ) {
					continue;
				}
				$html .= $this->get_status_html( $item['key'], $language_code );
			}
		}

		return $html;
	}

	/**
	 * Add header for the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @global object  $polylang  The Polylang instance
	 * @param  array   $columns   The Email table headers
	 * @return array
	 */
	public function emails_column_header( $columns ) {
		global $polylang;

		if ( $this->is_active() ) {
			if ( count( pll_languages_list() ) > 0 ) {

				$flags_column = '';
				foreach ( pll_languages_list() as $language_code ) {
					if ( $language_code === pll_default_language() ) {
						continue;
					}
					$language = $polylang->model->get_language( $language_code );
					$flags_column .= '<span class="um-flag" style="margin:2px">' . $language->flag . '</span>';
				}

				$new_columns = array();
				foreach ( $columns as $column_key => $column_content ) {
					$new_columns[$column_key] = $column_content;
					if ( 'email' === $column_key && !isset( $new_columns['icl_translations'] ) ) {
						$new_columns['icl_translations'] = $flags_column;
					}
				}

				$columns = $new_columns;
			}
		}

		return $columns;
	}

	/**
	 * Get default and current locales.
	 *
	 * @since  2.1.6
	 *
	 * @global object        $polylang      The Polylang instance
	 * @param  string|false  $current_code  Slug of the queried language
	 * @return array
	 */
	public function get_languages_codes( $current_code = false ) {
		global $polylang;

		if ( !$this->is_active() ) {
			return $current_code;
		}

		if ( empty( $current_code ) ) {
			$current_code = filter_input( INPUT_GET, 'lang', FILTER_SANITIZE_STRING );
		}
		if ( empty( $current_code ) ) {
			$current_code = pll_current_language();
		}
		if ( empty( $current_code ) ) {
			$current_code = substr( get_locale(), 0, 2 );
		}

		$default = $current = pll_default_language( 'locale' );
		$language = $polylang->model->get_language( $current_code );
		$current = $language->locale;

		return compact( 'default', 'current' );
	}

	/**
	 * Get translated page URL.
	 *
	 * @since  2.1.6
	 *
	 * @param  integer      $post_id   The post/page ID
	 * @param  string       $language  Slug or locale of the queried language
	 * @return string|false
	 */
	public function get_page_url_for_language( $post_id, $language = '' ) {

		$url = get_permalink( $post_id );

		if ( $this->is_active() ) {

			$lang = '';
			if ( is_string( $language ) && strlen( $language ) > 2 ) {
				$lang = current( explode( '_', $language ) );
			} elseif ( $language && is_string( $language ) ) {
				$lang = trim( $language );
			}

			$lang_post_id = pll_get_post( $post_id, $lang );

			if ( $lang_post_id && is_numeric( $lang_post_id ) ) {
				$url = get_permalink( $lang_post_id );
			}
		}

		return $url;
	}

	/**
	 * Get content for the cell of the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @global object  $polylang  The Polylang instance
	 * @param  string  $template  The email template slug
	 * @param  string  $code      Slug or locale of the queried language
	 * @return string
	 */
	public function get_status_html( $template, $code ) {
		global $polylang;

		$language = $polylang->model->get_language( $code );
		$default = pll_default_language();

		$lang = '';
		if ( $code !== $default ) {
			$lang = $language->locale . '/';
		}

		//theme location
		$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' ) . $lang . $template . '.php';

		//plugin location for default language
		if ( empty( $lang ) && !file_exists( $template_path ) ) {
			$template_path = UM()->mail()->get_template_file( 'plugin', $template );
		}

		$link = add_query_arg( array( 'email' => $template, 'lang' => $code ) );

		if ( file_exists( $template_path ) ) {

			$hint = sprintf( __( 'Edit the translation in %s', 'polylang' ), $language->name );
			$icon_html = sprintf( '<a href="%1$s" title="%2$s" class="pll_icon_edit"><span class="screen-reader-text">%3$s</span></a>',
					esc_url( $link ),
					esc_html( $hint ),
					esc_html( $hint )
			);
		} else {

			$hint = sprintf( __( 'Add a translation in %s', 'polylang' ), $language->name );
			$icon_html = sprintf( '<a href="%1$s" title="%2$s" class="pll_icon_add"><span class="screen-reader-text">%3$s</span></a>',
					esc_url( $link ),
					esc_attr( $hint ),
					esc_html( $hint )
			);
		}

		return $icon_html;
	}

	/**
	 * Check if Polylang is active.
	 *
	 * @since  2.1.6
	 *
	 * @return boolean
	 */
	public function is_active() {
		if ( defined( 'POLYLANG_VERSION' ) ) {
			global $polylang;
			return isset( $polylang ) && is_object( $polylang );
		}
		return false;
	}

	/**
	 * Check if we are on a UM Core Page or not
	 *
	 * @since  2.1.7
	 * @hook   um_is_core_page
	 *
	 * @global \WP_Post   $post
	 * @param  boolean    $is_core_page
	 * @param  string     $page
	 * @return boolean
	 */
	public function is_core_page( $is_core_page, $page ) {
		global $post;

		if ( $this->is_active() ) {
			$lang_post_id = pll_get_post( $post->ID, pll_default_language() );
			if ( isset( UM()->config()->permalinks[$page] ) && UM()->config()->permalinks[$page] == $lang_post_id ) {
				$is_core_page = true;
			}
		}

		return $is_core_page;
	}

	/**
	 * Get translated core page URL.
	 *
	 * @since  2.1.6
	 *
	 * @param  string  $url      Default page URL
	 * @param  string  $slug     Core page slug
	 * @param  string  $updated  Additional parameter 'updated' value
	 * @return string
	 */
	public function localize_core_page_url( $url, $slug, $updated = '' ) {

		if ( $this->is_active() ) {

			$language_codes = $this->get_languages_codes();
			if ( $language_codes['default'] != $language_codes['current'] ) {

				$page_id = UM()->config()->permalinks[$slug];
				$url = $this->get_page_url_for_language( $page_id, $language_codes['current'] );

				if ( $updated ) {
					$url = add_query_arg( 'updated', esc_attr( $updated ), $url );
				}
			}
		}

		return $url;
	}

	/**
	 * Replace email Subject with translated value on email send.
	 *
	 * @since  2.1.6
	 * @exaple change 'welcome_email_sub' to 'welcome_email_sub_de_DE'
	 *
	 * @param  string  $subject   Default subject
	 * @param  string  $template  The email template slug
	 * @return string
	 */
	public function localize_email_subject( $subject, $template ) {
		if ( $this->is_active() ) {
			$locale = '';
			$language_codes = $this->get_languages_codes();
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$locale = '_' . $language_codes['current'];
			}

			$value_default = UM()->options()->get( $template . '_sub' );
			$value = UM()->options()->get( $template . '_sub' . $locale );

			$subject = !empty( $value ) ? $value : $value_default;
		}

		return $subject;
	}

	/**
	 * Get translated profile page URL.
	 *
	 * @since  2.1.6
	 *
	 * @param  string   $profile_url  Default profile URL
	 * @param  integer  $page_id      The page ID
	 * @return string
	 */
	public function localize_profile_permalink( $profile_url, $page_id ) {

		if ( $this->is_active() ) {
			$profile_url = $this->get_page_url_for_language( $page_id );
		}

		return $profile_url;
	}

	/**
	 * Change email template path.
	 *
	 * @since  2.1.6
	 *
	 * @param  string  $template		   The email template path
	 * @param  string  $template_name  The email template slug
	 * @return string
	 */
	public function locate_email_template( $template, $template_name ) {
		if ( $this->is_active() ) {
			$locale = '';
			$language_codes = $this->get_languages_codes();
			if ( $language_codes['default'] !== $language_codes['current'] ) {
				$locale = $language_codes['current'] . '/';
			}

			// check if there is template at theme folder
			$template = locate_template( array(
					trailingslashit( 'ultimate-member/email' ) . $locale . $template_name . '.php',
					trailingslashit( 'ultimate-member/email' ) . $template_name . '.php'
					) );

			//if there isn't template at theme folder get template file from plugin dir
			if ( !$template ) {
				$path = !empty( UM()->mail()->path_by_slug[$template_name] ) ? UM()->mail()->path_by_slug[$template_name] : um_path . 'templates/email';
				$template = trailingslashit( $path ) . $template_name . '.php';
			}
		}

		return wp_normalize_path( $template );
	}

	/**
	 * Get arguments from original form if translated form doesn't have this data.
	 *
	 * @since  2.1.6
	 * @hook um_pre_args_setup
	 *
	 * @param  array $args
	 * @return array
	 */
	public function shortcode_pre_args_setup( $args ) {

		if ( $this->is_active() ) {
			$original_form_id = pll_get_post( $args['form_id'] , pll_default_language() );

			if ( $original_form_id && $original_form_id != $args['form_id'] ) {
				$original_post_data = UM()->query()->post_data( $original_form_id );

				foreach ( $original_post_data as $key => $value ) {
					if ( !isset( $args[$key] ) ) {
						$args[$key] = $value;
					}
				}
			}
		}

		return $args;
	}

	/**
	 * Get translated biography key
	 *
	 * @since  2.1.7
	 * @hook   um_profile_bio_key
	 *
	 * @param  string $key  Field Key
	 * @param  array  $args Form Data
	 * @return string
	 */
	public function profile_bio_key( $key, $args ) {
		if ( $key === 'description' ) {
			$curlang_slug = pll_current_language();
			$key = 'description_' . $curlang_slug;
		}
		return $key;
	}

	/**
	 * Get translated biography value
	 *
	 * @since  2.1.7
	 * @hook   um_field_value
	 * 
	 * @param  string $value   Field Value
	 * @param  string $default Default Value
	 * @param  string $key     Field Key
	 * @param  string $type    Field Type
	 * @param  array  $data    Field Data
	 * @return string
	 */
	public function profile_bio_value( $value, $default, $key, $type = '', $data = array() ) {
		if ( $key === 'description' ) {
			$curlang_slug = pll_current_language();
			$description = get_user_meta( um_profile_id(), 'description_' . $curlang_slug, true );
			if ( $description ) {
				$value = $description;
			}
		}
		return $value;
	}

	/**
	 * Save translated biography
	 *
	 * @since  2.1.7
	 * @hook   um_after_user_updated
	 *
	 * @param integer $user_id User ID
	 * @param array   $args    Form Data
	 */
	public function profile_bio_update( $user_id, $args ) {
		if ( !empty( $args['description'] ) ) {
			$curlang_slug = pll_current_language();
			update_user_meta( $user_id, 'description_' . $curlang_slug, $args['description'] );
		}
	}

}