<?php
namespace um\core;

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\External_Integrations' ) ) {


	/**
	 * Class External_Integrations
	 * @package um\core
	 */
	class External_Integrations {

		/**
		 * The class fot multilingual integration
		 * @var object|null
		 */
		private $translations = null;

		/**
		 * External_Integrations constructor.
		 */
		public function __construct() {

			add_action( 'um_access_fix_external_post_content', array( &$this, 'bbpress_no_access_message_fix' ), 10 );

			add_action( 'um_access_fix_external_post_content', array( &$this, 'forumwp_fix' ), 11 );

			// Integration for the "Transposh Translation Filter" plugin
			add_action( 'template_redirect', array( &$this, 'transposh_user_profile' ), 9990 );

			$this->plugins_loaded();

			add_action( 'plugins_loaded', array( &$this, 'load_integrations' ), 20 );
		}


		/**
		 * Fixed bbPress access to Forums message
		 */
		public function bbpress_no_access_message_fix() {
			remove_filter( 'template_include', 'bbp_template_include' );
		}


		/**
		 * Fixed ForumWP access to Forums message
		 */
		public function forumwp_fix() {
			if ( function_exists( 'FMWP' ) ) {
				remove_filter( 'single_template', array( FMWP()->shortcodes(), 'cpt_template' ) );
			}
		}


		/**
		 * @param bool|string $current_code
		 *
		 * @return array
		 */
		public function get_languages_codes( $current_code = false ) {
			global $sitepress;

			if ( !$this->is_wpml_active() ) return $current_code;

			$current_code = !empty( $current_code ) ? $current_code : $sitepress->get_current_language();

			$default = $sitepress->get_locale_from_language_code( $sitepress->get_default_language() );
			$current = $sitepress->get_locale_from_language_code( $current_code );


			return array(
				'default'	 => $default,
				'current'	 => $current
			);
		}


		/**
		 * Is this site multilingual
		 *
		 * @since 2.1.6
		 *
		 * @return boolean
		 */
		public function is_multilingual() {
			return isset( $this->translations ) && is_object( $this->translations ) && $this->translations->is_active();
		}

		
		/**
		 * Check if WPML is active
		 *
		 * @return bool|mixed
		 */
		public function is_wpml_active() {
			if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
				global $sitepress;

				return $sitepress->get_setting( 'setup_complete' );
			}

			return false;
		}


		/**
		 * Load integration classes
		 *
		 * @since 2.1.6
		 * @hook  plugins_loaded
		 */
		public function load_integrations() {

			$active_plugins = UM()->dependencies()->get_active_plugins();

			/* Multilingual */
			if ( in_array( 'sitepress-multilingual-cms/sitepress.php', $active_plugins ) ) {
				$this->translations = $this->wpml();
			} elseif ( in_array( 'polylang/polylang.php', $active_plugins ) ) {
				$this->translations = $this->polylang();
			} elseif ( in_array( 'translatepress-multilingual/index.php', $active_plugins ) ) {
				$this->translations = $this->translatepress();
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
		 * Integration between Ultimate member and Polylang
		 *
		 * @since 2.1.6
		 *
		 * @return um\core\integrations\UM_Polylang()
		 */
		public function polylang() {
			if ( empty( $this->classes['UM_Polylang'] ) ) {
				$this->classes['UM_Polylang'] = new \um\core\integrations\UM_Polylang();
			}
			return $this->classes['UM_Polylang'];
		}


		/**
		 * Integration between Ultimate member and TranslatePress
		 *
		 * @since 2.1.6
		 *
		 * @return um\core\integrations\UM_TranslatePress()
		 */
		public function translatepress() {
			if ( empty( $this->classes['UM_TranslatePress'] ) ) {
				$this->classes['UM_TranslatePress'] = new \um\core\integrations\UM_TranslatePress();
			}
			return $this->classes['UM_TranslatePress'];
		}


		/**
		 * The class fot multilingual integration
		 *
		 * @since  2.1.6
		 *
		 * @return object|null
		 */
		public function translations(){
			return $this->translations;
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
		 * Integration between Ultimate member and WPML
		 *
		 * @since 2.1.6
		 *
		 * @return um\core\integrations\UM_WPML()
		 */
		public function wpml() {
			if ( empty( $this->classes['UM_WPML'] ) ) {
				$this->classes['UM_WPML'] = new \um\core\integrations\UM_WPML();
			}
			return $this->classes['UM_WPML'];
		}

	}
}