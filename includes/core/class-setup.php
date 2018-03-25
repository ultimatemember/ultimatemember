<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Setup' ) ) {


	/**
	 * Class Setup
	 * @package um\core
	 */
	class Setup {


		/**
		 * @var array
		 */
		var $setup_shortcode = array();


		/**
		 * Setup constructor.
		 */
		function __construct() {
			//add_action('init',  array(&$this, 'install_basics'), 9);
		}


		/**
		 * Run setup
		 */
		function run_setup() {
			$this->install_basics();
			$this->install_default_forms();
			$this->set_default_settings();
			$this->set_default_role_meta();
		}


		/**
		 * Basics
		 */
		function install_basics() {
			if ( ! get_option( '__ultimatemember_sitekey' ) )
				update_option( '__ultimatemember_sitekey', str_replace( array( 'http://', 'https://' ), '', sanitize_user( get_bloginfo('url') ) ) . '-' . wp_generate_password( 20, false ) );
		}


		/**
		 * Default Forms
		 */
		function install_default_forms() {

			$options = get_option( 'um_options' );
			$options = empty( $options ) ? array() : $options;

			if ( current_user_can( 'manage_options' ) && ! get_option( 'um_is_installed' ) ) {

				update_option( 'um_is_installed', 1 );

				//Install default options
				foreach ( UM()->config()->settings_defaults as $key => $value ) {
					$options[$key] = $value;
				}

				// Install Core Forms
				foreach ( UM()->config()->core_forms as $id ) {

					/**
					If page does not exist
					Create it
					 **/
					$page_exists = UM()->query()->find_post_id( 'um_form', '_um_core', $id );
					if ( ! $page_exists ) {

						if ( $id == 'register' ) {
							$title = 'Default Registration';
						} else if ( $id == 'login' ) {
							$title = 'Default Login';
						} else {
							$title = 'Default Profile';
						}

						$form = array(
							'post_type' 	  	=> 'um_form',
							'post_title'		=> $title,
							'post_status'		=> 'publish',
							'post_author'   	=> get_current_user_id(),
						);

						$form_id = wp_insert_post( $form );

						foreach( UM()->config()->core_form_meta[$id] as $key => $value ) {
							if ( $key == '_um_custom_fields' ) {
								$array = unserialize( $value );
								update_post_meta( $form_id, $key, $array );
							} else {
								update_post_meta( $form_id, $key, $value );
							}
						}

						$this->setup_shortcode[$id] = '[ultimatemember form_id='.$form_id.']';

						$core_forms[ $form_id ] = $form_id;

					}
					/** DONE **/

				}

				if ( isset( $core_forms ) )
					update_option( 'um_core_forms', $core_forms );

				// Install Core Directories
				foreach ( UM()->config()->core_directories as $id ) {

					/**
					If page does not exist
					Create it
					 **/
					$page_exists = UM()->query()->find_post_id( 'um_directory', '_um_core', $id );
					if ( ! $page_exists ) {

						$title = 'Members';

						$form = array(
							'post_type' 	  	=> 'um_directory',
							'post_title'		=> $title,
							'post_status'		=> 'publish',
							'post_author'   	=> get_current_user_id(),
						);

						$form_id = wp_insert_post( $form );

						foreach ( UM()->config()->core_directory_meta[$id] as $key => $value ) {
							if ( $key == '_um_custom_fields' ) {
								$array = unserialize( $value );
								update_post_meta( $form_id, $key, $array );
							} else {
								update_post_meta($form_id, $key, $value);
							}
						}

						$this->setup_shortcode[$id] = '[ultimatemember form_id='.$form_id.']';

						$core_directories[ $form_id ] = $form_id;

					}
					/** DONE **/

				}

				if ( isset( $core_directories ) ) update_option( 'um_core_directories', $core_directories );


				// Install Core Pages
				$core_pages = array();
				foreach ( UM()->config()->core_pages as $slug => $array ) {

					/**
					If page does not exist
					Create it
					 **/
					$page_exists = UM()->query()->find_post_id( 'page', '_um_core', $slug );
					if ( ! $page_exists ) {

						if ( $slug == 'logout' ) {
							$content = '';
						} else if ( $slug == 'account' ) {
							$content = '[ultimatemember_account]';
						} else if ( $slug == 'password-reset' ) {
							$content = '[ultimatemember_password]';
						} else if ( $slug == 'user' ){
							$content = $this->setup_shortcode['profile'];
						} else {
							$content = $this->setup_shortcode[$slug];
						}

						$user_page = array(
							'post_title'		=> $array['title'],
							'post_content'		=> $content,
							'post_name'			=> $slug,
							'post_type' 	  	=> 'post',
							'post_status'		=> 'publish',
							'post_author'   	=> get_current_user_id(),
							'comment_status'    => 'closed'
						);

						$post_id = wp_insert_post( $user_page );
						wp_update_post( array( 'ID' => $post_id, 'post_type' => 'page' ) );

						update_post_meta( $post_id, '_um_core', $slug );

						$core_pages[ $slug ] = $post_id;

					} else {
						$core_pages[ $slug ] = $page_exists;
					}
					/** DONE **/
				}

				foreach ( $core_pages as $slug => $page_id ) {
					$key = UM()->options()->get_core_page_id( $slug );
					$options[ $key ] = $page_id;
				}
			}

			update_option( 'um_options', $options );
		}


		/**
		 * Set default UM settings
		 */
		function set_default_settings() {
			$options = get_option( 'um_options' );
			$options = empty( $options ) ? array() : $options;

			foreach ( UM()->config()->settings_defaults as $key => $value ) {
				//set new options to default
				if ( ! isset( $options[$key] ) )
					$options[$key] = $value;
			}

			update_option( 'um_options', $options );
		}


		/**
		 * Set UM roles meta to Default WP roles
		 */
		function set_default_role_meta() {
			//for set accounts without account status approved status
			UM()->query()->count_users_by_status( 'unassigned' );

			foreach ( UM()->config()->default_roles_metadata as $role => $meta ) {
				update_option( "um_role_{$role}_meta", $meta );
			}
		}
	}
}