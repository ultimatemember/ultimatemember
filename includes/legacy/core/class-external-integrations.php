<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\External_Integrations' ) ) {


	/**
	 * Class External_Integrations
	 * @package um\core
	 */
	class External_Integrations {


		/**
		 * Access constructor.
		 */
		function __construct() {
			//WPML translations
			add_filter( 'um_get_core_page_filter', array( &$this, 'get_core_page_url' ), 10, 3 );
			add_filter( 'um_admin_settings_email_section_fields', array( &$this, 'um_admin_settings_email_section_fields' ), 10, 2 );
			add_filter( 'um_email_send_subject', array( &$this, 'um_email_send_subject' ), 10, 2 );
			add_filter( 'um_locate_email_template', array( &$this, 'locate_email_template' ), 10, 2 );
			add_filter( 'um_change_email_template_file', array( &$this, 'change_email_template_file' ), 10, 1 );
			add_filter( 'um_email_templates_columns', array( &$this, 'add_email_templates_wpml_column' ), 10, 1 );


			add_action( 'um_access_fix_external_post_content', array( &$this, 'bbpress_no_access_message_fix' ), 10 );
			add_action( 'um_access_fix_external_post_content', array( &$this, 'forumwp_fix' ), 11 );
			add_action( 'um_access_fix_external_post_content', array( &$this, 'woocommerce_fix' ), 12 );

			add_filter( 'um_localize_permalink_filter', array( &$this, 'um_localize_permalink_filter' ), 10, 2 );
			add_filter( 'icl_ls_languages', array( &$this, 'um_core_page_wpml_permalink' ), 10, 1 );

			// Integration for the "Transposh Translation Filter" plugin
			add_action( 'template_redirect', array( &$this, 'transposh_user_profile' ), 9990 );

			/**
			 * @todo Customize this form metadata
			 */
			//add_filter( 'um_pre_args_setup',  array( &$this, 'shortcode_pre_args_setup' ), 20, 1 );

			$this->plugins_loaded();
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
		function shortcode_pre_args_setup( $args ) {
			if ( UM()->external_integrations()->is_wpml_active() ) {
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
			}

			return $args;
		}


		/**
		 * Integration for the "Transposh Translation Filter" plugin
		 *
		 * @description Fix issue "404 Not Found" on profile page
		 * @hook template_redirect
		 * @see http://transposh.org/
		 *
		 * @global transposh_plugin $my_transposh_plugin
		 * @global \WP_Query $wp_query Global WP_Query instance.
		 */
		public function transposh_user_profile() {
			global $my_transposh_plugin, $wp_query;

			if ( empty( $my_transposh_plugin ) ) {
				return;
			}

			if ( ! $wp_query->is_404() ) {
				return;
			}

			$profile_id = UM()->options()->get( 'core_user' );
			$post = get_post( $profile_id );

			if ( empty( $post ) || is_wp_error( $post ) ) {
				return;
			}

			if ( ! empty( $_SERVER['REQUEST_URI'] ) && stripos( $_SERVER['REQUEST_URI'], "$my_transposh_plugin->target_language/$post->post_name" ) !== false ) {
				preg_match( "#/$post->post_name/([^\/\?$]+)#", $_SERVER['REQUEST_URI'], $matches );

				if ( isset( $matches[1] ) ) {
					query_posts( array(
						'page_id' => $post->ID
					) );
					set_query_var( 'um_user', $matches[1] );
					wp_reset_postdata();
				}
			}
		}


		/**
		 * Gravity forms role capabilities compatibility
		 */
		public function plugins_loaded() {
			//gravity forms
			if ( ! function_exists('members_get_capabilities' ) ) {

				function members_get_capabilities() {

				}

			}
		}


		/**
		 * Fixed bbPress access to Forums message
		 */
		function bbpress_no_access_message_fix() {
			remove_filter( 'template_include', 'bbp_template_include' );
		}


		/**
		 * Fixed ForumWP access to Forums message
		 */
		function forumwp_fix() {
			if ( function_exists( 'FMWP' ) ) {
				remove_filter( 'single_template', array( FMWP()->frontend()->shortcodes(), 'cpt_template' ) );
			}
		}


		/**
		 * Fixed Woocommerce access to Products message
		 */
		function woocommerce_fix() {
			if ( UM()->dependencies()->woocommerce_active_check() ) {
				add_filter( 'single_template', array( &$this, 'woocommerce_template' ), 9999999, 1 );
			}
		}


		/**
		 * @param string $single_template
		 *
		 * @return string
		 */
		function woocommerce_template( $single_template ) {
			if ( is_product() ) {
				remove_filter( 'template_include', array( 'WC_Template_Loader', 'template_loader' ) );
			}

			return $single_template;
		}


		/**
		 * @param $profile_url
		 * @param $page_id
		 *
		 * @return bool|false|string
		 */
		function um_localize_permalink_filter( $profile_url, $page_id ) {

			if ( ! $this->is_wpml_active() )
				return $profile_url;

			/*if ( function_exists( 'icl_get_current_language' ) && icl_get_current_language() != icl_get_default_language() ) {
				if ( get_the_ID() > 0 && get_post_meta( get_the_ID(), '_um_wpml_user', true ) == 1 ) {
					$profile_url = get_permalink( get_the_ID() );
				}
			}*/

			// WPML compatibility
			if ( function_exists( 'icl_object_id' ) ) {
				$language_code = ICL_LANGUAGE_CODE;
				$lang_post_id = icl_object_id( $page_id , 'page', true, $language_code );

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
		 * @param $array
		 *
		 * @return mixed
		 */
		function um_core_page_wpml_permalink( $array ) {

			if ( ! $this->is_wpml_active() )
				return $array;

			global $sitepress;
			if( ! um_is_core_page("user") ) return $array;
			if( ! defined("ICL_LANGUAGE_CODE") ) return $array;
			if( ! function_exists('icl_object_id') ) return $array;

			// Permalink base
			$permalink_base = UM()->options()->get( 'permalink_base' );

			// Get user slug
			$profile_slug = strtolower( get_user_meta( um_profile_id(), "um_user_profile_url_slug_{$permalink_base}", true ) );
			$current_language = ICL_LANGUAGE_CODE;
			foreach ( $array as $lang_code => $arr ) {
				$sitepress->switch_lang( $lang_code );
				$user_page = um_get_core_page( "user" );

				$array[ $lang_code ]['url'] = "{$user_page}{$profile_slug}/";
			}

			$sitepress->switch_lang( $current_language );

			return $array;
		}


		/**
		 * Check if WPML is active
		 *
		 * @return bool|mixed
		 */
		function is_wpml_active() {
			if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
				global $sitepress;

				return $sitepress->get_setting( 'setup_complete' );
			}

			return false;
		}


		/**
		 * Get a translated core page URL
		 *
		 * @param $post_id
		 * @param $language
		 * @return bool|false|string
		 */
		function get_url_for_language( $post_id, $language ) {
			if ( ! $this->is_wpml_active() )
				return '';

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
		 * @param bool|string $current_code
		 *
		 * @return array
		 */
		function get_languages_codes( $current_code = false ) {
			global $sitepress;

			if ( ! $this->is_wpml_active() ) {
				return $current_code;
			}

			$current_code = ! empty( $current_code ) ? $current_code : $sitepress->get_current_language();

			$default = $sitepress->get_locale_from_language_code( $sitepress->get_default_language() );
			$current = $sitepress->get_locale_from_language_code( $current_code );


			return array(
				'default' => $default,
				'current' => $current
			);
		}


		/**
		 * @param $url
		 * @param $slug
		 * @param $updated
		 *
		 * @return bool|false|string
		 */
		function get_core_page_url( $url, $slug, $updated ) {

			if ( ! $this->is_wpml_active() )
				return $url;

			if ( function_exists( 'icl_get_current_language' ) && icl_get_current_language() != icl_get_default_language() ) {
				$url = $this->get_url_for_language( UM()->config()->permalinks[ $slug ], icl_get_current_language() );

				if ( $updated ) {
					$url = add_query_arg( 'updated', esc_attr( $updated ), $url );
				}
			}

			return $url;
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
		function um_admin_settings_email_section_fields( $section_fields, $email_key ) {
			if ( ! $this->is_wpml_active() ) {
				return $section_fields;
			}

			$language_codes = $this->get_languages_codes();

			$lang = '';
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$lang = '_' . $language_codes['current'];
			}

			$value_default = UM()->options()->get( $email_key . '_sub'  );
			$value = UM()->options()->get( $email_key . '_sub' . $lang );

			$section_fields[2]['id'] = $email_key . '_sub' . $lang;
			$section_fields[2]['value'] = ! empty( $value ) ? $value : $value_default;

			return $section_fields;
		}


		/**
		 * Adding endings to the "Subject Line" field, depending on the language.
		 *
		 * @param $subject
		 * @param $template
		 *
		 * @return string
		 */
		function um_email_send_subject( $subject, $template ) {
			if ( ! $this->is_wpml_active() ) {
				return $subject;
			}

			$language_codes = $this->get_languages_codes();

			$lang = '';
			if ( $language_codes['default'] != $language_codes['current'] ) {
				$lang = '_' . $language_codes['current'];
			}

			$value_default = UM()->options()->get( $template . '_sub'  );
			$value = UM()->options()->get( $template . '_sub' . $lang );

			$subject = ! empty( $value ) ? $value : $value_default;

			return $subject;
		}


		/**
		 * @param $template
		 * @param $template_name
		 *
		 * @return string
		 */
		function locate_email_template( $template, $template_name ) {
			if ( ! $this->is_wpml_active() ) {
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
			if ( ! $template ) {
				$path = ! empty( UM()->mail()->path_by_slug[ $template_name ] ) ? UM()->mail()->path_by_slug[ $template_name ] : um_path . 'templates/email';
				$template = trailingslashit( $path ) . $template_name . '.php';
			}

			return $template;
		}


		/**
		 * @param $template
		 *
		 * @return string
		 */
		function change_email_template_file( $template ) {
			if ( ! $this->is_wpml_active() ) {
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
		 * @param $columns
		 *
		 * @return array
		 */
		function add_email_templates_wpml_column( $columns ) {
			if ( ! $this->is_wpml_active() ) {
				return $columns;
			}

			global $sitepress;
			$new_columns = $columns;
			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			unset( $active_languages[ $current_language ] );

			if ( count( $active_languages ) > 0 ) {
				$flags_column = '';
				foreach ( $active_languages as $language_data ) {
					$flags_column .= '<img src="' . $sitepress->get_flag_url( $language_data['code'] ). '" width="18" height="12" alt="' . $language_data['display_name'] . '" title="' . $language_data['display_name'] . '" style="margin:2px" />';
				}

				$new_columns = array();
				foreach ( $columns as $column_key => $column_content ) {
					$new_columns[ $column_key ] = $column_content;
					if ( 'email' === $column_key && ! isset( $new_columns['icl_translations'] ) )  {
						$new_columns['icl_translations'] = $flags_column;
					}
				}
			}

			return $new_columns;
		}


		/**
		 * @param $item
		 *
		 * @return string
		 */
		function wpml_column_content( $item ) {
			if ( ! $this->is_wpml_active() ) {
				return '';
			}

			global $sitepress;
			$html = '';

			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			unset( $active_languages[ $current_language ] );
			foreach ( $active_languages as $language_data ) {
				$html .= $this->get_status_html( $item['key'], $language_data['code'] );
			}
			return $html;
		}


		/**
		 * @param $template
		 * @param $code
		 *
		 * @return string
		 */
		function get_status_html( $template, $code ) {
			global $sitepress;
			$status = 'add';

			$active_languages = $sitepress->get_active_languages();
			$translation = array(
				'edit' => array(
					'icon' => 'edit_translation.png',
					'text' => sprintf(
						__( 'Edit the %s translation', 'sitepress' ),
						$active_languages[$code]['display_name']
					)
				),
				'add'  => array(
					'icon' => 'add_translation.png',
					'text' => sprintf(
						__( 'Add translation to %s', 'sitepress' ),
						$active_languages[$code]['display_name']
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
			if ( empty( $lang ) && ! file_exists( $template_path ) ) {
				$template_path = UM()->mail()->get_template_file( 'plugin', $template );
			}

			if ( file_exists( $template_path ) ) {
				$status = 'edit';
			}

			$link = add_query_arg( array( 'email' => $template, 'lang' => $code ) );

			return $this->render_status_icon( $link, $translation[ $status ]['text'], $translation[ $status ]['icon'] );
		}


		/**
		 * @param $link
		 * @param $text
		 * @param $img
		 *
		 * @return string
		 */
		function render_status_icon( $link, $text, $img ) {

			$icon_html = '<a href="' . $link . '" title="' . $text . '">';
			$icon_html .= '<img style="padding:1px;margin:2px;" border="0" src="'
			              . ICL_PLUGIN_URL . '/res/img/'
			              . $img . '" alt="'
			              . $text . '" width="16" height="16" />';
			$icon_html .= '</a>';

			return $icon_html;
		}


	}
}
