<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'External_Integrations' ) ) {
	class External_Integrations {

		/**
		 * Access constructor.
		 */
		function __construct() {
			add_filter( 'um_get_core_page_filter', array( &$this, 'get_core_page_url' ), 10, 3 );


			//check the site's accessible more priority have Individual Post/Term Restriction settings
			add_action( 'template_redirect', array( &$this, 'template_redirect' ), 1000 );
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

				if ( get_post_meta( get_the_ID(), '_um_wpml_account', true ) == 1 ) {
					$url = get_permalink( get_the_ID() );
				}
				if ( get_post_meta( get_the_ID(), '_um_wpml_user', true ) == 1 ) {
					$url = $this->get_url_for_language( UM()->config()->permalinks[ $slug ], icl_get_current_language() );
				}
			}

			return $url;
		}


	}
}