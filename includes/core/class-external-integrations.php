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
			add_action( 'um_access_fix_external_post_content', array( &$this, 'bbpress_no_access_message_fix' ), 10 );
			add_action( 'um_access_fix_external_post_content', array( &$this, 'forumwp_fix' ), 11 );

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
			if ( UM()->integrations()->is_wpml_active() ) {
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
		 * @param $template
		 * @param $code
		 *
		 * @deprecated 3.0
		 *
		 * @return string
		 */
		function get_status_html( $template, $code ) {
			return um_wpml_get_status_html( $template, $code );
		}


		/**
		 * @deprecated 3.0
		 *
		 * @param $link
		 * @param $text
		 * @param $img
		 *
		 * @return string
		 */
		function render_status_icon( $link, $text, $img ) {
			return um_wpml_render_status_icon( $link, $text, $img );
		}


		/**
		 * Check if WPML is active
		 *
		 * @deprecated 3.0
		 *
		 * @return bool|mixed
		 */
		function is_wpml_active() {
			return UM()->integrations()->is_wpml_active();
		}


		/**
		 * @deprecated 3.0
		 *
		 * @param bool|string $current_code
		 *
		 * @return array
		 */
		function get_languages_codes( $current_code = false ) {
			return um_wpml_get_languages_codes();
		}
	}
}
