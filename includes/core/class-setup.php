<?php
namespace um\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Setup' ) ) {


	/**
	 * Class Setup
	 *
	 * @deprecated 3.0
	 *
	 * @package um\core
	 */
	class Setup {


		/**
		 * Setup constructor.
		 */
		function __construct() {
		}


		/**
		 * Run setup
		 *
		 * @deprecated 3.0
		 */
		function run_setup() {
			_deprecated_function( 'UM()->setup()->run_setup()', '3.0' );

			$this->create_db();
			$this->install_basics();
			$this->set_default_settings();
			$this->install_default_forms();
			$this->set_default_role_meta();
		}


		/**
		 * Create custom DB tables
		 *
		 * @deprecated 3.0
		 */
		function create_db() {
			_deprecated_function( 'UM()->setup()->create_db()', '3.0', 'UM()->install()->create_db()' );

			UM()->install()->create_db();
		}


		/**
		 * Basics
		 *
		 * @deprecated 3.0
		 */
		function install_basics() {
			_deprecated_function( 'UM()->setup()->install_basics()', '3.0' );

			if ( ! get_option( '__ultimatemember_sitekey' ) ) {
				update_option( '__ultimatemember_sitekey', str_replace( array( 'http://', 'https://' ), '', sanitize_user( get_bloginfo('url') ) ) . '-' . wp_generate_password( 20, false ) );
			}
		}


		/**
		 * Set default UM settings
		 *
		 * @deprecated 3.0
		 */
		function set_default_settings() {
			_deprecated_function( 'UM()->setup()->set_default_settings()', '3.0', 'UM()->install()->set_default_settings()' );

			UM()->install()->set_default_settings();
		}


		/**
		 * Install Pre-defined pages with shortcodes
		 *
		 *
		 * @deprecated 3.0
		 */
		function install_default_pages() {
			_deprecated_function( 'UM()->setup()->install_default_pages()', '3.0', 'UM()->install()->core_pages()' );

			UM()->install()->predefined_pages();
		}


		/**
		 * Set UM roles meta to Default WP roles
		 *
		 * @deprecated 3.0
		 */
		function set_default_role_meta() {
			_deprecated_function( 'UM()->setup()->set_default_role_meta()', '3.0', 'UM()->install()->set_default_user_status() and UM()->install()->set_default_roles_meta()' );

			UM()->install()->set_default_user_status();
			UM()->install()->set_default_roles_meta();
		}


		/**
		 * Default Forms
		 *
		 * @deprecated 3.0
		 */
		function install_default_forms() {
			_deprecated_function( 'UM()->setup()->install_default_forms()', '3.0', 'UM()->install()->create_forms() and UM()->install()->create_member_directory()' );

			UM()->install()->create_forms();
			UM()->install()->create_member_directory();
		}
	}
}
