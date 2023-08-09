<?php
namespace um\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Setup' ) ) {


	/**
	 * Class Setup
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
		 */
		function run_setup() {
			$this->create_db();
			$this->install_basics();
			$this->install_default_forms();
			$this->set_default_settings();
			$this->set_default_role_meta();
			$this->set_default_user_status();
		}


		/**
		 * Create custom DB tables
		 */
		function create_db() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$wpdb->prefix}um_metadata (
umeta_id bigint(20) unsigned NOT NULL auto_increment,
user_id bigint(20) unsigned NOT NULL default '0',
um_key varchar(255) default NULL,
um_value longtext default NULL,
PRIMARY KEY  (umeta_id),
KEY user_id_indx (user_id),
KEY meta_key_indx (um_key),
KEY meta_value_indx (um_value(191))
) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}


		/**
		 * Basics
		 */
		function install_basics() {
			if ( ! get_option( '__ultimatemember_sitekey' ) ) {
				update_option( '__ultimatemember_sitekey', str_replace( array( 'http://', 'https://' ), '', sanitize_user( get_bloginfo('url') ) ) . '-' . wp_generate_password( 20, false ) );
			}
		}


		/**
		 * Default Forms
		 */
		function install_default_forms() {
			if ( current_user_can( 'manage_options' ) && ! get_option( 'um_is_installed' ) ) {
				$options = get_option( 'um_options', array() );

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

						$core_forms[ $id ] = $form_id;
					}
					/** DONE **/
				}

				if ( isset( $core_forms ) ) {
					update_option( 'um_core_forms', $core_forms );
				}

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

						foreach ( UM()->config()->core_directory_meta[ $id ] as $key => $value ) {
							if ( $key == '_um_custom_fields' ) {
								$array = unserialize( $value );
								update_post_meta( $form_id, $key, $array );
							} else {
								update_post_meta($form_id, $key, $value);
							}
						}

						$core_directories[ $id ] = $form_id;
					}
					/** DONE **/

				}

				if ( isset( $core_directories ) ) {
					update_option( 'um_core_directories', $core_directories );
				}

				update_option( 'um_options', $options );
			}
		}


		/**
		 * Install Pre-defined pages with shortcodes
		 */
		public function install_default_pages() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$core_forms       = get_option( 'um_core_forms', array() );
			$core_directories = get_option( 'um_core_directories', array() );

			$setup_shortcodes = array_merge( $core_forms, $core_directories );

			//Install Core Pages
			$core_pages = array();
			foreach ( UM()->config()->core_pages as $slug => $array ) {

				$page_exists = UM()->query()->find_post_id( 'page', '_um_core', $slug );
				if ( $page_exists ) {
					$core_pages[ $slug ] = $page_exists;
					continue;
				}

				//If page does not exist - create it
				if ( 'logout' === $slug ) {
					$content = '';
				} elseif ( 'account' === $slug ) {
					$content = '[ultimatemember_account]';
				} elseif ( 'password-reset' === $slug ) {
					$content = '[ultimatemember_password]';
				} elseif ( 'user' === $slug ) {
					$content = '[ultimatemember form_id="' . $setup_shortcodes['profile'] . '"]';
				} else {
					$content = '[ultimatemember form_id="' . $setup_shortcodes[ $slug ] . '"]';
				}

				/**
				 * Filters Ultimate Member predefined pages content when set up the predefined page.
				 *
				 * @param {string} $content Predefined page content.
				 * @param {string} $slug    Predefined page slug (key).
				 *
				 * @return {string} Predefined page content.
				 *
				 * @since 2.1.0
				 * @hook um_setup_predefined_page_content
				 *
				 * @example <caption>Set Ultimate Member predefined pages content with key = 'my_page_key'.</caption>
				 * function my_um_setup_predefined_page_content( $content, $slug ) {
				 *     // your code here
				 *     if ( 'my_page_key' === $slug ) {
				 *         $content = __( 'My Page content', 'my-translate-key' );
				 *     }
				 *     return $pages;
				 * }
				 * add_filter( 'um_setup_predefined_page_content', 'my_um_setup_predefined_page_content' );
				 */
				$content = apply_filters( 'um_setup_predefined_page_content', $content, $slug );

				$user_page = array(
					'post_title'     => $array['title'],
					'post_content'   => $content,
					'post_name'      => $slug,
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'post_author'    => get_current_user_id(),
					'comment_status' => 'closed',
				);

				$post_id = wp_insert_post( $user_page );
				update_post_meta( $post_id, '_um_core', $slug );

				$core_pages[ $slug ] = $post_id;
			}

			$options = get_option( 'um_options', array() );

			foreach ( $core_pages as $slug => $page_id ) {
				$key             = UM()->options()->get_core_page_id( $slug );
				$options[ $key ] = $page_id;
			}

			update_option( 'um_options', $options );

			// reset rewrite rules after first install of core pages
			UM()->rewrite()->reset_rules();
		}


		/**
		 * Set default UM settings
		 */
		function set_default_settings() {
			$options = get_option( 'um_options', array() );

			foreach ( UM()->config()->settings_defaults as $key => $value ) {
				//set new options to default
				if ( ! isset( $options[ $key ] ) ) {
					$options[ $key ] = $value;
				}
			}

			update_option( 'um_options', $options );
		}


		/**
		 * Set UM roles meta to Default WP roles
		 */
		function set_default_role_meta() {
			foreach ( UM()->config()->default_roles_metadata as $role => $meta ) {
				add_option( "um_role_{$role}_meta", $meta );
			}
		}


		/**
		 * Set accounts without account_status meta to 'approved' status
		 *
		 * @since 2.4.2
		 */
		function set_default_user_status() {
			$result = get_transient( 'um_count_users_unassigned' );
			if ( false === $result ) {
				$args = array(
					'fields'               => 'ids',
					'number'               => 0,
					'meta_query'           => array(
						array(
							'key'     => 'account_status',
							'compare' => 'NOT EXISTS',
						),
					),
					'um_custom_user_query' => true,
				);

				$users = new \WP_User_Query( $args );
				if ( empty( $users ) || is_wp_error( $users ) ) {
					$result = array();
				} else {
					$result = $users->get_results();
				}

				set_transient( 'um_count_users_unassigned', $result, DAY_IN_SECONDS );
			}

			if ( empty( $result ) ) {
				return;
			}

			foreach ( $result as $user_id ) {
				update_user_meta( $user_id, 'account_status', 'approved' );
			}
		}
	}
}
