<?php
namespace um\integrations;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\integrations\Common' ) ) {


	/**
	 * Class Common
	 *
	 * @package um\integrations
	 */
	class Common {


		/**
		 * Common constructor.
		 */
		function __construct() {
			add_action( 'plugins_loaded', [ &$this, 'plugins_loaded' ] );

			add_filter( 'um_pre_template_locations', [ &$this, 'pre_template_locations_common_locale' ], 10, 4 );
		}


		/**
		 *
		 */
		function plugins_loaded() {
			if ( $this->is_wpml_active() ) {
				include_once wp_normalize_path( 'wpml/integration.php' );
			}

			if ( $this->is_polylang_active() ) {
				include_once wp_normalize_path( 'polylang/integration.php' );
			}

			if ( $this->is_translatepress_active() ) {
				include_once wp_normalize_path( 'translatepress/integration.php' );
			}

			if ( $this->is_weglot_active() ) {
				include_once wp_normalize_path( 'weglot/integration.php' );
			}
		}


		/**
		 * Email notifications integration with `get_user_locale()`
		 *
		 * @param $template_locations
		 * @param $template_name
		 * @param $module
		 * @param $template_path
		 *
		 * @return array
		 */
		function pre_template_locations_common_locale( $template_locations, $template_name, $module, $template_path ) {
			// make pre templates locations array to avoid the conflicts between different locales when multilingual plugins are integrated
			// e.g. "ultimate-member/ru_RU(user locale)/uk(WPML)/email/approved_email.php"
			// must be the next priority:
			//
			// ultimate-member/{user locale}/email/approved_email.php
			// ultimate-member/{site locale}/email/approved_email.php
			$template_locations_pre = $template_locations;

			$template_locations = apply_filters( 'um_pre_template_locations_common_locale_integration', $template_locations, $template_name, $module, $template_path );

			// use the user_locale only for email notifications templates
			if ( 0 === strpos( $template_name, 'email/' ) ) {
				$current_locale = determine_locale();
				$current_user_locale = get_user_locale();

				if ( $current_locale != $current_user_locale ) {
					// todo skip duplications e.g. "ultimate-member/ru_RU/uk/email/approved_email.php" when current language = uk, but user locale is ru_RU. Must be only "ultimate-member/ru_RU/email/approved_email.php" in this case
					$locale_template_locations = array_map( function( $item ) use ( $template_path, $current_user_locale ) {
						return str_replace( trailingslashit( $template_path ), trailingslashit( $template_path ) . $current_user_locale . '/', $item );
					}, $template_locations_pre );

					$template_locations = array_merge( $locale_template_locations, $template_locations );
				}
			}

			return $template_locations;
		}


		/**
		 * Check if WPML is active
		 *
		 * @return bool
		 */
		function is_wpml_active() {
			if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
				global $sitepress;
				return $sitepress->is_setup_complete();
			}

			return false;
		}


		/**
		 * Check if Polylang is active
		 *
		 * @return bool
		 */
		function is_polylang_active() {
			if ( defined( 'POLYLANG_VERSION' ) ) {
				global $polylang;
				return is_object( $polylang );
			}

			return false;
		}


		/**
		 * Check if TranslatePress is active
		 *
		 * @return bool
		 */
		function is_translatepress_active() {
			return defined( 'TRP_PLUGIN_VERSION' ) && class_exists( '\TRP_Translate_Press' );
		}


		/**
		 * Check if Weglot is active
		 *
		 * @return bool
		 */
		function is_weglot_active() {
			return defined( 'WEGLOT_VERSION' );
		}
	}
}
