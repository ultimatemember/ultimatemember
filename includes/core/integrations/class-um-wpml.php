<?php
/**
 * Integration between Ultimate Member and WPML.
 *
 * @package um\core\integrations
 */

namespace um\core\integrations;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'um\core\integrations\UM_WPML' ) ) {
	return;
}


// Interface UM_Multilingual.
require_once __DIR__ . '/interface-um-multilingual.php';


/**
 * Class UM_WPML
 *
 * @example UM()->external_integrations()->wpml()
 * @package um\core\integrations
 */
class UM_WPML implements UM_Multilingual {

	/**
	 * Class UM_WPML constructor.
	 */
	public function __construct() {
		if ( $this->is_active() ) {

			/* Email */
			add_filter( 'um_admin_settings_email_section_fields', array( &$this, 'admin_settings_email_section_fields' ), 10, 2 );
			add_filter( 'um_change_email_template_file', array( &$this, 'change_email_template_file' ), 10, 1 );
			add_filter( 'um_change_settings_before_save', array( &$this, 'create_email_template_file' ), 8, 1 );
			add_filter( 'um_email_send_subject', array( &$this, 'localize_email_subject' ), 10, 2 );
			add_filter( 'um_email_templates_columns', array( &$this, 'emails_column_header' ), 10, 1 );
			add_filter( 'um_locate_email_template', array( &$this, 'locate_email_template' ), 10, 2 );

			/* Form */
			add_filter( 'um_pre_args_setup', array( &$this, 'shortcode_pre_args_setup' ), 20, 1 );

			/* Permalink */
			add_filter( 'icl_ls_languages', array( &$this, 'core_page_permalink' ), 10, 1 );
			add_filter( 'rewrite_rules_array', array( &$this, 'add_rewrite_rules' ), 10, 1 );
			add_filter( 'um_is_core_page', array( &$this, 'is_core_page' ), 10, 2 );
			add_filter( 'um_get_core_page_filter', array( &$this, 'localize_core_page_url' ), 10, 3 );
			add_filter( 'um_localize_permalink_filter', array( &$this, 'localize_profile_permalink' ), 10, 2 );

			add_filter( 'um_login_form_button_two_url', array( &$this, 'localize_page_url' ), 10, 2 );
			add_filter( 'um_register_form_button_two_url', array( &$this, 'localize_page_url' ), 10, 2 );
		}
	}

	/**
	 * Add UM rewrite rules for the Account page and Profile page
	 *
	 * @since  2.1.7
	 *
	 * @global \SitePress $sitepress
	 *
	 * @param  array $rules Rewrite rules.
	 * @return array
	 */
	public function add_rewrite_rules( $rules ) {
		global $sitepress;

		if ( $this->is_active() ) {
			$active_languages = $sitepress->get_active_languages();

			$newrules = array();

			// Account.
			if ( $active_languages && isset( UM()->config()->permalinks['account'] ) ) {
				$account_page_id = UM()->config()->permalinks['account'];
				$account         = get_post( $account_page_id );

				foreach ( $active_languages as $language_code => $language ) {
					$lang_post_id  = wpml_object_id_filter( $account_page_id, 'post', false, $language_code );
					$lang_post_obj = get_post( $lang_post_id );

					if ( isset( $account->post_name ) && isset( $lang_post_obj->post_name ) && $lang_post_obj->post_name !== $account->post_name ) {
						$lang_page_slug = $lang_post_obj->post_name;

						$newrules[ $lang_page_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_tab=$matches[1]&lang=' . $language_code;
					}
				}
			}

			// Profile.
			if ( $active_languages && isset( UM()->config()->permalinks['user'] ) ) {
				$user_page_id = UM()->config()->permalinks['user'];
				$user         = get_post( $user_page_id );

				foreach ( $active_languages as $language_code => $language ) {
					$lang_post_id  = wpml_object_id_filter( $user_page_id, 'post', false, $language_code );
					$lang_post_obj = get_post( $lang_post_id );

					if ( isset( $user->post_name ) && isset( $lang_post_obj->post_name ) && $lang_post_obj->post_name !== $user->post_name ) {
						$lang_page_slug = $lang_post_obj->post_name;

						$newrules[ $lang_page_slug . '/([^/]+)/?$' ] = 'index.php?page_id=' . $lang_post_id . '&um_user=$matches[1]&lang=' . $language_code;
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
	 * @param  array  $section_fields The email template fields.
	 * @param  string $email_key      The email template slug.
	 * @return array
	 */
	public function admin_settings_email_section_fields( $section_fields, $email_key ) {
		if ( $this->is_active() ) {
			$language_codes = $this->get_languages_codes();
			$lang           = $language_codes['default'] === $language_codes['current'] ? '' : '_' . $language_codes['current'];
			$value_default  = UM()->options()->get( $email_key . '_sub' );
			$value          = UM()->options()->get( $email_key . '_sub' . $lang );

			$section_fields[2]['id']    = $email_key . '_sub' . $lang;
			$section_fields[2]['value'] = empty( $value ) ? $value_default : $value;
		}

		return $section_fields;
	}

	/**
	 * Change email template for searching in the theme folder.
	 *
	 * @since  2.1.6
	 *
	 * @param  string $template The email template slug.
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
	 * Create email template file in the theme folder.
	 *
	 * @since  2.5.4
	 *
	 * @param  array $settings Input data.
	 * @return array
	 */
	public function create_email_template_file( $settings ) {
		if ( isset( $settings['um_email_template'] ) ) {
			$template      = $settings['um_email_template'];
			$template_path = UM()->mail()->get_template_file( 'theme', $template );

			if ( ! file_exists( $template_path ) ) {
				$template_dir = dirname( $template_path );

				if ( wp_mkdir_p( $template_dir ) ) {
					file_put_contents( $template_path, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				}
			}
		}
		return $settings;
	}

	/**
	 * Filter profile page permalink.
	 *
	 * @hook   icl_ls_languages
	 * @global \SitePress $sitepress
	 *
	 * @param  array $array Data.
	 * @return mixed
	 */
	public function core_page_permalink( $array ) {
		global $sitepress;

		if ( $this->is_active() ) {
			if ( ! um_is_core_page( 'user' ) ) {
				return $array;
			}
			if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
				return $array;
			}
			if ( ! function_exists( 'icl_object_id' ) ) {
				return $array;
			}

			// Permalink base.
			$permalink_base = UM()->options()->get( 'permalink_base' );

			// Get user slug.
			$profile_slug = strtolower( get_user_meta( um_profile_id(), "um_user_profile_url_slug_{$permalink_base}", true ) );

			$current_language = ICL_LANGUAGE_CODE;
			foreach ( $array as $lang_code => $arr ) {
				$sitepress->switch_lang( $lang_code );
				$user_page = um_get_core_page( 'user' );

				$array[ $lang_code ]['url'] = "{$user_page}{$profile_slug}/";
			}
			$sitepress->switch_lang( $current_language );
		}

		return $array;
	}

	/**
	 *
	 * Add cell for the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @global \SitePress $sitepress
	 *
	 * @param  array $item The email template data.
	 * @return string
	 */
	public function emails_column_content( $item ) {
		global $sitepress;

		$html = '';
		if ( $this->is_active() ) {
			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			unset( $active_languages[ $current_language ] );
			foreach ( $active_languages as $language_data ) {
				$html .= $this->get_status_html( $item['key'], $language_data['code'] );
			}
		}

		return $html;
	}

	/**
	 * Add header for the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @global \SitePress $sitepress
	 *
	 * @param  array $columns The Email table headers.
	 * @return array
	 */
	public function emails_column_header( $columns ) {
		global $sitepress;

		if ( $this->is_active() ) {
			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			unset( $active_languages[ $current_language ] );

			if ( count( $active_languages ) > 0 ) {
				$flags_column = '';
				foreach ( $active_languages as $language_data ) {
					$flags_column .= '<img src="' . $sitepress->get_flag_url( $language_data['code'] ) . '" width="18" height="12" alt="' . $language_data['display_name'] . '" title="' . $language_data['display_name'] . '" style="margin:2px" />';
				}

				$new_columns = array();
				foreach ( $columns as $column_key => $column_content ) {
					$new_columns[ $column_key ] = $column_content;
					if ( 'email' === $column_key && ! isset( $new_columns['icl_translations'] ) ) {
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
	 * @global \SitePress $sitepress
	 *
	 * @param  string|false $current_code Slug of the queried language.
	 * @return arra
	 */
	public function get_languages_codes( $current_code = false ) {
		global $sitepress;

		if ( ! $this->is_active() ) {
			return $current_code;
		}

		if ( empty( $current_code ) ) {
			$current_code = $sitepress->get_current_language();
		}
		if ( empty( $current_code ) ) {
			$current_code = substr( get_locale(), 0, 2 );
		}

		$default = $sitepress->get_locale_from_language_code( $sitepress->get_default_language() );
		$current = $sitepress->get_locale_from_language_code( $current_code );

		return compact( 'default', 'current' );
	}

	/**
	 * Get translated page URL.
	 *
	 * @since  2.1.6
	 *
	 * @global \SitePress $sitepress
	 *
	 * @param  integer $post_id  The post/page ID.
	 * @param  string  $language Slug or locale of the queried language.
	 * @return string|false
	 */
	public function get_page_url_for_language( $post_id, $language ) {
		if ( $this->is_active() ) {
			$lang_post_id = icl_object_id( $post_id, 'page', true, $language );

			if ( ! empty( $lang_post_id ) ) {
				$url = get_permalink( $lang_post_id );
			}
		}

		return empty( $url ) ? get_permalink( $post_id ) : $url;
	}

	/**
	 * Get content for the cell of the column 'translations' in the Email table.
	 *
	 * @since  2.1.6
	 *
	 * @global \SitePress $sitepress
	 *
	 * @param  string $template The email template slug.
	 * @param  string $code     Slug or locale of the queried language.
	 * @return string
	 */
	public function get_status_html( $template, $code ) {
		global $sitepress;
		$status = 'add';

		$active_languages = $sitepress->get_active_languages();
		$translation      = array(
			'edit' => array(
				'icon' => 'edit_translation.png',
				'text' => sprintf(
					// translators: %s - language name.
					__( 'Edit the %s translation', 'sitepress' ),
					$active_languages[ $code ]['display_name']
				),
			),
			'add'  => array(
				'icon' => 'add_translation.png',
				'text' => sprintf(
					// translators: %s - language name.
					__( 'Add translation to %s', 'sitepress' ),
					$active_languages[ $code ]['display_name']
				),
			),
		);

		$language_codes = $this->get_languages_codes( $code );
		$lang           = $language_codes['default'] === $language_codes['current'] ? '' : $language_codes['current'] . '/';

		// theme location.
		$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' ) . $lang . $template . '.php';

		// plugin location for default language.
		if ( empty( $lang ) && ! file_exists( $template_path ) ) {
			$template_path = UM()->mail()->get_template_file( 'plugin', $template );
		}

		if ( file_exists( $template_path ) ) {
			$status = 'edit';
		}

		$link = add_query_arg(
			array(
				'email' => $template,
				'lang'  => $code,
			)
		);

		$icon_html = sprintf(
			'<a href="%1$s" title="%2$s" class="wpml_icon"><img src="%3$s" style="padding:1px;margin:2px;" border="0" width="16" height="16" /></a>',
			esc_url( $link ),
			esc_attr( $translation[ $status ]['text'] ),
			esc_url( ICL_PLUGIN_URL . '/res/img/' . $translation[ $status ]['icon'] )
		);

		return $icon_html;
	}

	/**
	 * Check if WPML is active.
	 *
	 * @since  2.1.6
	 *
	 * @global \SitePress $sitepress
	 *
	 * @return boolean
	 */
	public function is_active() {
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			global $sitepress;
			return $sitepress->get_setting( 'setup_complete' );
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
	 * @global \SitePress $sitepress
	 *
	 * @param  boolean $is_core_page Is core page.
	 * @param  string  $page         Page key.
	 * @return boolean
	 */
	public function is_core_page( $is_core_page, $page ) {
		global $post;
		global $sitepress;

		if ( $this->is_active() ) {
			if ( isset( UM()->config()->permalinks[ $page ] ) && wpml_object_id_filter( $post->ID, 'page', true, $sitepress->get_default_language() ) === UM()->config()->permalinks[ $page ] ) {
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
	 * @param  string $url     Default page URL.
	 * @param  string $slug    Core page slug.
	 * @param  string $updated Additional parameter 'updated' value.
	 * @return string
	 */
	public function localize_core_page_url( $url, $slug, $updated ) {
		if ( $this->is_active() ) {
			$page_id = UM()->config()->permalinks[ $slug ];
			$url     = $this->get_page_url_for_language( $page_id, icl_get_current_language() );

			if ( $updated ) {
				$url = add_query_arg( 'updated', esc_attr( $updated ), $url );
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
	 * @param  string $subject  Default subject.
	 * @param  string $template The email template slug.
	 * @return string
	 */
	public function localize_email_subject( $subject, $template ) {
		if ( $this->is_active() ) {
			$language_codes = $this->get_languages_codes();
			$lang           = $language_codes['default'] === $language_codes['current'] ? '' : '_' . $language_codes['current'];
			$value_default  = UM()->options()->get( $template . '_sub' );
			$value          = UM()->options()->get( $template . '_sub' . $lang );
			$subject        = empty( $value ) ? $value_default : $value;
		}

		return $subject;
	}

	/**
	 * Get translated page URL.
	 *
	 * @param  string $url  Page URL or slug.
	 * @param  array  $args Additional data.
	 * @return string
	 */
	public function localize_page_url( $url, $args = array() ) {

		$page = get_page_by_path( trim( $url, "/ \n\r\t\v\0" ) );
		if ( $page && is_a( $page, '\WP_Post' ) ) {
			$language_codes = $this->get_languages_codes();
			$url            = $this->get_page_url_for_language( $page->ID, $language_codes['current'] );
		}

		return $url;
	}

	/**
	 * Get translated profile page URL.
	 *
	 * @since  2.1.6
	 *
	 * @global \SitePress $sitepress
	 *
	 * @param  string  $profile_url Default profile URL.
	 * @param  integer $page_id     The page ID.
	 * @return string
	 */
	public function localize_profile_permalink( $profile_url, $page_id ) {
		global $sitepress;

		if ( $this->is_active() && function_exists( 'icl_object_id' ) ) {

			static $language_code = null;
			if ( empty( $language_code ) && isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referer = esc_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
				if ( strstr( $referer, home_url() ) ) {
					$language_code = $sitepress->get_language_from_url( $referer );
					$sitepress->switch_lang( $language_code );
				}
			}
			if ( empty( $language_code ) ) {
				$language_code = ICL_LANGUAGE_CODE;
			}
			$lang_post_id = icl_object_id( $page_id, 'page', true, $language_code );

			if ( ! empty( $lang_post_id ) ) {
				$profile_url = get_permalink( $lang_post_id );
			} else {
				// No page found, it's most likely the homepage.
				$profile_url = $sitepress->language_url( $language_code );
			}
		}

		return $profile_url;
	}

	/**
	 * Change email template path.
	 *
	 * @since  2.1.6
	 *
	 * @param  string $template      The email template path.
	 * @param  string $template_name The email template slug.
	 * @return string
	 */
	public function locate_email_template( $template, $template_name ) {
		if ( $this->is_active() ) {
			// WPML compatibility and multilingual email templates.
			$language_codes = $this->get_languages_codes();
			$lang           = $language_codes['default'] === $language_codes['current'] ? '' : $language_codes['current'] . '/';

			// check if there is template at theme folder.
			$template = locate_template(
				array(
					trailingslashit( 'ultimate-member/email' ) . $lang . $template_name . '.php',
					trailingslashit( 'ultimate-member/email' ) . $template_name . '.php',
				)
			);

			// if there isn't template at theme folder get template file from plugin dir.
			if ( ! $template ) {
				$path     = empty( UM()->mail()->path_by_slug[ $template_name ] ) ? um_path . 'templates/email' : UM()->mail()->path_by_slug[ $template_name ];
				$template = trailingslashit( $path ) . $template_name . '.php';
			}
		}

		return $template;
	}

	/**
	 * Get arguments from original form if translated form doesn't have this data.
	 *
	 * @since  2.1.6
	 * @hook um_pre_args_setup
	 *
	 * @global \SitePress $sitepress
	 *
	 * @param  array $args Arguments.
	 * @return array
	 */
	public function shortcode_pre_args_setup( $args ) {
		global $sitepress;

		if ( $this->is_active() && isset( $args['form_id'] ) ) {
			$form_id          = absint( $args['form_id'] );
			$original_form_id = $sitepress->get_object_id( $form_id, 'post', true, $sitepress->get_default_language() );

			if ( $original_form_id && $original_form_id !== $form_id ) {
				$original_post_data = UM()->query()->post_data( $original_form_id );

				foreach ( $original_post_data as $key => $value ) {
					if ( ! isset( $args[ $key ] ) ) {
						$args[ $key ] = $value;
					}
				}
			}
		}

		return $args;
	}

}
