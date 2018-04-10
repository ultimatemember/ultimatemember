<?php
namespace um\admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\admin\Admin' ) ) {


	/**
	 * Class Admin
	 * @package um\admin
	 */
	class Admin extends Admin_Functions {


		/**
		 * @var string
		 */
		var $templates_path;


		/**
		 * Admin constructor.
		 */
		function __construct() {
			parent::__construct();

			$this->templates_path = um_path . 'includes/admin/templates/';

			add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );

			$prefix = is_network_admin() ? 'network_admin_' : '';
			add_filter( "{$prefix}plugin_action_links_" . um_plugin, array( &$this, 'plugin_links' ) );

			add_action( 'um_admin_do_action__user_cache', array( &$this, 'user_cache' ) );
			add_action( 'um_admin_do_action__purge_temp', array( &$this, 'purge_temp' ) );
			add_action( 'um_admin_do_action__duplicate_form', array( &$this, 'duplicate_form' ) );
			add_action( 'um_admin_do_action__um_language_downloader', array( &$this, 'um_language_downloader' ) );
			add_action( 'um_admin_do_action__um_hide_locale_notice', array( &$this, 'um_hide_notice' ) );
			add_action( 'um_admin_do_action__um_can_register_notice', array( &$this, 'um_hide_notice' ) );
			add_action( 'um_admin_do_action__um_hide_exif_notice', array( &$this, 'um_hide_notice' ) );
			add_action( 'um_admin_do_action__user_action', array( &$this, 'user_action' ) );

			add_action( 'parent_file', array( &$this, 'parent_file' ), 9 );
			add_filter( 'gettext', array( &$this, 'gettext' ), 10, 4 );
			add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		}



		/**
		 * Clear all users cache
		 *
		 * @param $action
		 */
		function user_cache( $action ) {
			global $wpdb;
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'um_cache_userdata_%'" );

			$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'cleared_cache' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * Purge temp uploads dir
		 * @param $action
		 */
		function purge_temp( $action ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			UM()->files()->remove_dir( UM()->files()->upload_temp );

			$url = add_query_arg( array( 'page' => 'ultimatemember', 'update' => 'purged_temp' ), admin_url( 'admin.php' ) );
			exit( wp_redirect( $url ) );
		}


		/**
		 * Duplicate form
		 *
		 * @param $action
		 */
		function duplicate_form( $action ) {
			if ( ! is_admin() || ! current_user_can('manage_options') ) die();
			if ( ! isset( $_REQUEST['post_id'] ) || ! is_numeric( $_REQUEST['post_id'] ) ) die();

			$post_id = $_REQUEST['post_id'];

			$n = array(
				'post_type' 	  	=> 'um_form',
				'post_title'		=> sprintf( __( 'Duplicate of %s', 'ultimate-member' ), get_the_title( $post_id ) ),
				'post_status'		=> 'publish',
				'post_author'   	=> get_current_user_id(),
			);

			$n_id = wp_insert_post( $n );

			$n_fields = get_post_custom( $post_id );
			foreach ( $n_fields as $key => $value ) {

				if ( $key == '_um_custom_fields' ) {
					$the_value = unserialize( $value[0] );
				} else {
					$the_value = $value[0];
				}

				update_post_meta( $n_id, $key, $the_value );

			}

			delete_post_meta($n_id, '_um_core');

			$url = admin_url('edit.php?post_type=um_form');
			$url = add_query_arg('update','form_duplicated',$url);

			exit( wp_redirect( $url ) );

		}


		/**
		 * Download a language remotely
		 *
		 * @param $action
		 */
		function um_language_downloader( $action ) {
			if ( !is_admin() || !current_user_can('manage_options') ) die();

			$locale = get_option('WPLANG');
			if ( !$locale ) return;
			if ( !isset( UM()->available_languages[$locale] ) ) return;

			$path = UM()->files()->upload_basedir;
			$path = str_replace('/uploads/ultimatemember','',$path);
			$path = $path . '/languages/plugins/';
			$path = str_replace('//','/',$path);

			$remote = 'https://ultimatemember.com/wp-content/languages/plugins/ultimatemember-' . $locale . '.po';
			$remote2 = 'https://ultimatemember.com/wp-content/languages/plugins/ultimatemember-' . $locale . '.mo';

			$remote_tmp = download_url( $remote, $timeout = 300 );
			copy( $remote_tmp, $path . 'ultimatemember-' . $locale . '.po' );
			unlink( $remote_tmp );

			$remote2_tmp = download_url( $remote2, $timeout = 300 );
			copy( $remote2_tmp, $path . 'ultimatemember-' . $locale . '.mo' );
			unlink( $remote2_tmp );

			$url = remove_query_arg('um_adm_action', UM()->permalinks()->get_current_url() );
			$url = add_query_arg('update','language_updated',$url);
			exit( wp_redirect($url) );

		}


		/**
		 * Action to hide notices in admin
		 *
		 * @param $action
		 */
		function um_hide_notice( $action ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				die();
			}

			update_option( $action, 1 );
			exit( wp_redirect( remove_query_arg( 'um_adm_action' ) ) );
		}


		/**
		 * Various user actions
		 *
		 * @param $action
		 */
		function user_action( $action ) {
			if ( !is_admin() || !current_user_can( 'edit_users' ) ) die();
			if ( !isset( $_REQUEST['sub'] ) ) die();
			if ( !isset($_REQUEST['user_id']) ) die();

			um_fetch_user( $_REQUEST['user_id'] );

			$subaction = $_REQUEST['sub'];

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_user_action_hook
			 * @description Action on bulk user subaction
			 * @input_vars
			 * [{"var":"$subaction","type":"string","desc":"Bulk Subaction"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_user_action_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_user_action_hook', 'my_admin_user_action', 10, 1 );
			 * function my_admin_user_action( $subaction ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_admin_user_action_hook", $subaction );
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_user_action_{$subaction}_hook
			 * @description Action on bulk user subaction
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_user_action_{$subaction}_hook', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_user_action_{$subaction}_hook', 'my_admin_user_action', 10 );
			 * function my_admin_user_action() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( "um_admin_user_action_{$subaction}_hook" );

			um_reset_user();

			wp_redirect( add_query_arg( 'update', 'user_updated', admin_url('?page=ultimatemember') ) );
			exit;

		}


		/**
		 * Add any custom links to plugin page
		 *
		 * @param array $links
		 *
		 * @return array
		 */
		function plugin_links( $links ) {
			$more_links[] = '<a href="http://docs.ultimatemember.com/">' . __( 'Docs', 'ultimate-member' ) . '</a>';
			$more_links[] = '<a href="'.admin_url().'admin.php?page=um_options">' . __( 'Settings', 'ultimate-member' ) . '</a>';

			$links = $more_links + $links;
			return $links;
		}


		/**
		 * Init admin action/filters + request handlers
		 */
		function admin_init() {
			if ( is_admin() && current_user_can( 'manage_options' ) && ! empty( $_REQUEST['um_adm_action'] ) ) {
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_admin_do_action__
				 * @description Make some action on custom admin action
				 * @input_vars
				 * [{"var":"$action","type":"string","desc":"Admin Action"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_admin_do_action__', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_admin_do_action__', 'my_admin_do_action', 10, 1 );
				 * function my_admin_do_action( $action ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_admin_do_action__", $_REQUEST['um_adm_action'] );
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_admin_do_action__{$action}
				 * @description Make some action on custom admin $action
				 * @input_vars
				 * [{"var":"$action","type":"string","desc":"Admin Action"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_admin_do_action__{$action}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_admin_do_action__{$action}', 'my_admin_do_action', 10, 1 );
				 * function my_admin_do_action( $action ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( "um_admin_do_action__{$_REQUEST['um_adm_action']}", $_REQUEST['um_adm_action'] );
			}
		}


		/**
		 * Updated post messages
		 *
		 * @param array $messages
		 *
		 * @return array
		 */
		function post_updated_messages( $messages ) {
			global $post_ID;

			$post_type = get_post_type( $post_ID );

			if ( $post_type == 'um_form' ) {
				$messages['um_form'] = array(
					0   => '',
					1   => __( 'Form updated.', 'ultimate-member' ),
					2   => __( 'Custom field updated.', 'ultimate-member' ),
					3   => __( 'Custom field deleted.', 'ultimate-member' ),
					4   => __( 'Form updated.', 'ultimate-member' ),
					5   => isset( $_GET['revision'] ) ? __( 'Form restored to revision.', 'ultimate-member' ) : false,
					6   => __( 'Form created.', 'ultimate-member' ),
					7   => __( 'Form saved.', 'ultimate-member' ),
					8   => __( 'Form submitted.', 'ultimate-member' ),
					9   => __( 'Form scheduled.', 'ultimate-member' ),
					10  => __( 'Form draft updated.', 'ultimate-member' ),
				);
			}

			return $messages;
		}


		/**
		 * Gettext filters
		 *
		 * @param $translation
		 * @param $text
		 * @param $domain
		 *
		 * @return string
		 */
		function gettext( $translation, $text, $domain ) {
			global $post;
			if ( isset( $post->post_type ) && $this->is_plugin_post_type() ) {
				$translations = get_translations_for_domain( $domain );
				if ( $text == 'Publish' ) {
					return $translations->translate( 'Create' );
				} elseif ( $text == 'Move to Trash' ) {
					return $translations->translate( 'Delete' );
				}
			}

			return $translation;
		}


		/**
		 * Fix parent file for correct highlighting
		 *
		 * @param $parent_file
		 *
		 * @return string
		 */
		function parent_file( $parent_file ) {
			global $current_screen;
			$screen_id = $current_screen->id;
			if ( strstr( $screen_id, 'um_' ) ) {
				$parent_file = 'ultimatemember';
			}
			return $parent_file;
		}
	}
}