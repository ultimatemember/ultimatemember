<?php

namespace um\core\integrations;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'um\core\integrations\UM_WPML' ) ) {


	/**
	 * Class UM_WPML
	 * @package um\core\integrations
	 */
	class UM_WPML {

		/**
		 * Access constructor.
		 */
		public function __construct() {
			//WPML translations
			add_filter( 'um_admin_settings_email_section_fields', array( &$this, 'admin_settings_email_section_fields' ), 10, 2 );
			add_filter( 'um_change_email_template_file', array( &$this, 'change_email_template_file' ), 10, 1 );
			add_filter( 'um_email_send_subject', array( &$this, 'localize_email_subject' ), 10, 2 );
			add_filter( 'um_email_templates_columns', array( &$this, 'add_email_templates_column' ), 10, 1 );
			add_filter( 'um_get_core_page_filter', array( &$this, 'get_core_page_url' ), 10, 3 );
			add_filter( 'um_locate_email_template', array( &$this, 'locate_email_template' ), 10, 2 );
			add_filter( 'um_localize_permalink_filter', array( &$this, 'localize_permalink' ), 10, 2 );
			add_filter( 'icl_ls_languages', array( &$this, 'core_page_permalink' ), 10, 1 );

			/**
			 * @todo Customize this form metadata
			 */
			//add_filter( 'um_pre_args_setup',  array( &$this, 'shortcode_pre_args_setup' ), 20, 1 );
		}


		/**
		 * @param $columns
		 *
		 * @return array
		 */
		public function add_email_templates_column( $columns ) {
			if ( !$this->is_active() ) {
				return $columns;
			}

			global $sitepress;
			$new_columns = $columns;
			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			unset( $active_languages[$current_language] );

			if ( count( $active_languages ) > 0 ) {
				$flags_column = '';
				foreach ( $active_languages as $language_data ) {
					$flags_column .= '<img src="' . $sitepress->get_flag_url( $language_data['code'] ) . '" width="18" height="12" alt="' . $language_data['display_name'] . '" title="' . $language_data['display_name'] . '" style="margin:2px" />';
				}

				$new_columns = array();
				foreach ( $columns as $column_key => $column_content ) {
					$new_columns[$column_key] = $column_content;
					if ( 'email' === $column_key && !isset( $new_columns['icl_translations'] ) ) {
						$new_columns['icl_translations'] = $flags_column;
					}
				}
			}

			return $new_columns;
		}


		/**
		 * Adding endings to the "Subject Line" field, depending on the language.
		 * @exaple welcome_email_sub_de_DE
		 *
		 * @param $section_fields
		 * @param $email_key
		 *
		 * @return array
		 */
		public function admin_settings_email_section_fields( $section_fields, $email_key ) {
			if ( !$this->is_active() ) {
				return $section_fields;
			}

			$language_codes = $this->get_languages_codes();

			$lang = '';
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$lang = '_' . $language_codes['current'];
			}

			$value_default = UM()->options()->get( $email_key . '_sub' );
			$value = UM()->options()->get( $email_key . '_sub' . $lang );

			$section_fields[2]['id'] = $email_key . '_sub' . $lang;
			$section_fields[2]['value'] = !empty( $value ) ? $value : $value_default;

			return $section_fields;
		}


		/**
		 * @param $template
		 *
		 * @return string
		 */
		public function change_email_template_file( $template ) {
			if ( !$this->is_active() ) {
				return $template;
			}

			$language_codes = $this->get_languages_codes();

			$lang = '';
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$lang = $language_codes['current'] . '/';
			}

			return $lang . $template;
		}


		/**
		 * @param $array
		 *
		 * @return mixed
		 */
		public function core_page_permalink( $array ) {

			if ( !$this->is_active() ) {
				return $array;
			}

			global $sitepress;
			if ( !um_is_core_page( "user" ) ) return $array;
			if ( !defined( "ICL_LANGUAGE_CODE" ) ) return $array;
			if ( !function_exists( 'icl_object_id' ) ) return $array;

			// Permalink base
			$permalink_base = UM()->options()->get( 'permalink_base' );

			// Get user slug
			$profile_slug = strtolower( get_user_meta( um_profile_id(), "um_user_profile_url_slug_{$permalink_base}", true ) );
			$current_language = ICL_LANGUAGE_CODE;
			foreach ( $array as $lang_code => $arr ) {
				$sitepress->switch_lang( $lang_code );
				$user_page = um_get_core_page( "user" );

				$array[$lang_code]['url'] = "{$user_page}{$profile_slug}/";
			}

			$sitepress->switch_lang( $current_language );

			return $array;
		}


		/**
		 * @param $item
		 *
		 * @return string
		 */
		public function emails_column_content( $item ) {
			if ( !$this->is_active() ) {
				return '';
			}

			global $sitepress;
			$html = '';

			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			unset( $active_languages[$current_language] );
			foreach ( $active_languages as $language_data ) {
				$html .= $this->get_status_html( $item['key'], $language_data['code'] );
			}
			return $html;
		}


		/**
		 * @param $url
		 * @param $slug
		 * @param $updated
		 *
		 * @return bool|false|string
		 */
		public function get_core_page_url( $url, $slug, $updated ) {

			if ( !$this->is_active() ) return $url;

			if ( function_exists( 'icl_get_current_language' ) && icl_get_current_language() != icl_get_default_language() ) {
				$url = $this->get_url_for_language( UM()->config()->permalinks[$slug], icl_get_current_language() );

				if ( $updated ) {
					$url = add_query_arg( 'updated', esc_attr( $updated ), $url );
				}
			}

			return $url;
		}


		/**
		 * @param bool|string $current_code
		 *
		 * @return array
		 */
		public function get_languages_codes( $current_code = false ) {
			global $sitepress;

			if ( !$this->is_active() ) return $current_code;

			$current_code = !empty( $current_code ) ? $current_code : $sitepress->get_current_language();

			$default = $sitepress->get_locale_from_language_code( $sitepress->get_default_language() );
			$current = $sitepress->get_locale_from_language_code( $current_code );


			return array(
				'default'	 => $default,
				'current'	 => $current
			);
		}


		/**
		 * @param $template
		 * @param $code
		 *
		 * @return string
		 */
		public function get_status_html( $template, $code ) {
			global $sitepress;
			$status = 'add';

			$active_languages = $sitepress->get_active_languages();
			$translation = array(
				'edit' => array(
					'icon' => 'edit_translation.png',
					'text' => sprintf(
						__( 'Edit the %s translation', 'sitepress' ), $active_languages[$code]['display_name']
					)
				),
				'add'	 => array(
					'icon' => 'add_translation.png',
					'text' => sprintf(
						__( 'Add translation to %s', 'sitepress' ), $active_languages[$code]['display_name']
					)
				)
			);

			$language_codes = $this->get_languages_codes( $code );

			$lang = '';
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$lang = $language_codes['current'] . '/';
			}

			//theme location
			$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' ) . $lang . $template . '.php';

			//plugin location for default language
			if ( empty( $lang ) && !file_exists( $template_path ) ) {
				$template_path = UM()->mail()->get_template_file( 'plugin', $template );
			}

			if ( file_exists( $template_path ) ) {
				$status = 'edit';
			}

			$link = add_query_arg( array( 'email' => $template, 'lang' => $code ) );

			return $this->render_status_icon( $link, $translation[$status]['text'], $translation[$status]['icon'] );
		}


		/**
		 * Get a translated core page URL
		 *
		 * @param $post_id
		 * @param $language
		 * @return bool|false|string
		 */
		public function get_url_for_language( $post_id, $language ) {
			if ( !$this->is_active() ) return '';

			$lang_post_id = icl_object_id( $post_id, 'page', true, $language );

			if ( $lang_post_id != 0 ) {
				$url = get_permalink( $lang_post_id );
			} else {
				// No page found, it's most likely the homepage
				global $sitepress;
				$url = $sitepress->language_url( $language );
			}

			return $url;
		}


		/**
		 * Check if WPML is active
		 *
		 * @return bool|mixed
		 */
		public function is_active() {
			if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
				global $sitepress;

				return $sitepress->get_setting( 'setup_complete' );
			}

			return false;
		}


		/**
		 * Adding endings to the "Subject Line" field, depending on the language.
		 *
		 * @param $subject
		 * @param $template
		 *
		 * @return string
		 */
		public function localize_email_subject( $subject, $template ) {
			if ( !$this->is_active() ) {
				return $subject;
			}

			$language_codes = $this->get_languages_codes();

			$lang = '';
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$lang = '_' . $language_codes['current'];
			}

			$value_default = UM()->options()->get( $template . '_sub' );
			$value = UM()->options()->get( $template . '_sub' . $lang );

			$subject = !empty( $value ) ? $value : $value_default;

			return $subject;
		}


		/**
		 * @param $profile_url
		 * @param $page_id
		 *
		 * @return bool|false|string
		 */
		public function localize_permalink( $profile_url, $page_id ) {

			if ( !$this->is_active() ) {
				return $profile_url;
			}

			// WPML compatibility
			if ( function_exists( 'icl_object_id' ) ) {
				$language_code = ICL_LANGUAGE_CODE;
				$lang_post_id = icl_object_id( $page_id, 'page', true, $language_code );

				if ( $lang_post_id != 0 ) {
					$profile_url = get_permalink( $lang_post_id );
				} else {
					// No page found, it's most likely the homepage
					global $sitepress;
					$profile_url = $sitepress->language_url( $language_code );
				}
			}

			return $profile_url;
		}


		/**
		 * @param $template
		 * @param $template_name
		 *
		 * @return string
		 */
		public function locate_email_template( $template, $template_name ) {
			if ( !$this->is_active() ) {
				return $template;
			}

			//WPML compatibility and multilingual email templates
			$language_codes = $this->get_languages_codes();

			$lang = '';
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$lang = $language_codes['current'] . '/';
			}

			// check if there is template at theme folder
			$template = locate_template( array(
				trailingslashit( 'ultimate-member/email' ) . $lang . $template_name . '.php',
				trailingslashit( 'ultimate-member/email' ) . $template_name . '.php'
				) );

			//if there isn't template at theme folder get template file from plugin dir
			if ( !$template ) {
				$path = !empty( UM()->mail()->path_by_slug[$template_name] ) ? UM()->mail()->path_by_slug[$template_name] : um_path . 'templates/email';
				$template = trailingslashit( $path ) . $template_name . '.php';
			}

			return $template;
		}


		/**
		 * @param $link
		 * @param $text
		 * @param $img
		 *
		 * @return string
		 */
		public function render_status_icon( $link, $text, $img ) {

			$icon_html = '<a href="' . $link . '" title="' . $text . '">';
			$icon_html .= '<img style="padding:1px;margin:2px;" border="0" src="'
				. ICL_PLUGIN_URL . '/res/img/'
				. $img . '" alt="'
				. $text . '" width="16" height="16" />';
			$icon_html .= '</a>';

			return $icon_html;
		}
		

		/**
		 * UM filter - Restore original arguments on translated page
		 *
		 * @description Restore original arguments on load shortcode if they are missed in the WPML translation
		 * @hook um_pre_args_setup
		 *
		 * @global \SitePress $sitepress
		 * @param array $args
		 * @return array
		 */
		public function shortcode_pre_args_setup( $args ) {
			if ( UM()->external_integrations()->is_active() ) {
				global $sitepress;

				$original_form_id = $sitepress->get_object_id( $args['form_id'], 'post', true, $sitepress->get_default_language() );

				if ( $original_form_id != $args['form_id'] ) {
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

	}

}