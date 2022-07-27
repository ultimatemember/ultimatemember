<?php namespace um\common;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\common\Install' ) ) {


	/**
	 * Class Install
	 *
	 * @since 3.0
	 *
	 * @package um\common
	 */
	class Install {


		/**
		 * @var bool
		 */
		var $install_process = false;


		/**
		 * Install constructor.
		 */
		function __construct() {
		}


		/**
		 * Plugin Activation
		 *
		 * @since 3.0
		 */
		function activation() {
			$this->install_process = true;

			$this->single_site_activation();
			if ( is_multisite() ) {
				update_network_option( get_current_network_id(), 'um_maybe_network_wide_activation', 1 );
			}

			$this->install_process = false;
		}


		/**
		 * Check if plugin is network activated make the first installation on all blogs
		 *
		 * @since 3.0
		 */
		function maybe_network_activation() {
			$maybe_activation = get_network_option( get_current_network_id(), 'um_maybe_network_wide_activation' );

			if ( $maybe_activation ) {

				delete_network_option( get_current_network_id(), 'um_maybe_network_wide_activation' );

				if ( is_plugin_active_for_network( um_plugin ) ) {
					// get all blogs
					$blogs = get_sites();
					if ( ! empty( $blogs ) ) {
						foreach( $blogs as $blog ) {
							switch_to_blog( $blog->blog_id );
							//make activation script for each sites blog
							$this->single_site_activation();
							restore_current_blog();
						}
					}
				}
			}
		}


		/**
		 * Single site plugin activation handler
		 *
		 * @since 3.0
		 */
		function single_site_activation() {
			//first install
			$version = get_option( 'um_version' );
			if ( ! $version ) {
				update_option( 'um_last_version_upgrade', ultimatemember_version );
				add_option( 'um_first_activation_date', time() );
			}

			if ( $version !== ultimatemember_version ) {
				update_option( 'um_version', ultimatemember_version );
			}

			$first_activation = get_option( 'um_first_activation_date', false );

			// if {first activation date} is lower than first v3 release then set legacy option
			if ( ! $first_activation || $first_activation <= 1644940610 ) {
				add_option( 'um_is_legacy', true );
			}

			//run setup
			UM()->common()->cpt()->create_post_types();

			$this->create_db();
			$this->set_default_settings();
			$this->set_default_roles_meta();
			$this->set_default_user_status();

			$this->set_icons_options();

			if ( ! get_option( 'um_is_installed' ) ) {
				$this->create_forms();
				update_option( 'um_is_installed', 1 );
			}
		}


		/**
		 * Create custom DB tables
		 *
		 * @since 3.0
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

			/** @noinspection PhpIncludeInspection */
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}


		/**
		 * Set default UM settings
		 *
		 * @since 3.0
		 */
		function set_default_settings() {
			$options = get_option( 'um_options', array() );

			foreach ( UM()->config()->get( 'default_settings' ) as $key => $value ) {
				//set new options to default
				if ( ! isset( $options[ $key ] ) ) {
					$options[ $key ] = $value;
				}
			}

			update_option( 'um_options', $options );
		}


		/**
		 * Set default UM role settings
		 * for existed WP native roles
		 *
		 * @since 3.0
		 */
		function set_default_roles_meta() {
			foreach ( UM()->config()->get( 'roles_meta' ) as $role => $meta ) {
				add_option( "um_role_{$role}_meta", $meta );
			}
		}


		/**
		 * Set accounts without account_status meta to 'approved' status
		 *
		 * @since 3.0
		 */
		function set_default_user_status() {
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
				return;
			}

			$result = $users->get_results();
			if ( empty( $result ) ) {
				return;
			}

			foreach ( $result as $user_id ) {
				update_user_meta( $user_id, 'account_status', 'approved' );
			}
		}


		/**
		 *
		 */
		function set_icons_options() {
			$fa_version    = get_option( 'um_fa_version' );
			$ion_version   = get_option( 'um_ion_version' );
			$um_icons_list = get_option( 'um_icons_list' );

			if ( empty( $um_icons_list ) || $fa_version !== UM()->admin()->enqueue()->fa_version || $ion_version !== UM()->admin()->enqueue()->ion_version ) {
				update_option( 'um_fa_version', UM()->admin()->enqueue()->fa_version, false );
				update_option( 'um_ion_version', UM()->admin()->enqueue()->ion_version, false );

				$common_icons = array();

				$icons = file_get_contents( UM_PATH . 'assets/libs/fontawesome/metadata/icons.json' );
				$icons = json_decode( $icons );

				foreach ( $icons as $key => $data ) {
					if ( ! isset( $data->styles ) ) {
						continue;
					}

					foreach ( $data->styles as $style ) {
						$style_class = '';
						if ( 'solid' === $style ) {
							$style_class = 'fas fa-';
						} elseif ( 'regular' === $style ) {
							$style_class = 'far fa-';
						} elseif ( 'brands' === $style ) {
							$style_class = 'fab fa-';
						}

						$label  = count( $data->styles ) > 1 ? $data->label . ' (' . $style . ')' : $data->label;
						$search = array_unique( array_merge( $data->search->terms, array( $key, strtolower( $data->label ) ) ) );

						$common_icons[ $style_class . $key ] = array(
							'label'  => $label,
							'search' => $search,
						);
					}
				}

				$ionicons = file_get_contents( UM_PATH . 'assets/libs/ionicons/data.json' );
				$ionicons = json_decode( $ionicons );
				foreach ( $ionicons->icons as $item ) {
					foreach ( $item->icons as $class ) {
						$search = array_unique( array_merge( $item->tags, array( $class, 'ion-' . $class ) ) );

						$common_icons[ 'ion-' . $class ] = array(
							'label'  => $class,
							'search' => $search,
						);
					}
				}

				update_option( 'um_icons_list', $common_icons, false );
			}
		}


		/**
		 * Install Default Core Forms
		 *
		 * @since 3.0
		 */
		function create_forms() {
			foreach ( UM()->config()->get( 'form_meta' ) as $id => $meta ) {
				/**
				If page does not exist
				Create it
				 **/
				$page_exists = UM()->query()->find_post_id( 'um_form', '_um_core', $id );
				if ( $page_exists ) {
					continue;
				}

				$title = array_key_exists( 'title', $meta ) ? $meta['title'] : '';
				unset( $meta['title'] );

				$form = array(
					'post_type'   => 'um_form',
					'post_title'  => $title,
					'post_status' => 'publish',
					'post_author' => get_current_user_id(),
					'meta_input'  => $meta,
				);

				$form_id = wp_insert_post( $form );
				if ( is_wp_error( $form_id ) ) {
					continue;
				}

				$core_forms[ $id ] = $form_id;
			}

			if ( ! isset( $core_forms ) ) {
				return;
			}

			update_option( 'um_core_forms', $core_forms );
		}


		/**
		 * Install selected predefined pages by $slug
		 *
		 * @param string $slug
		 * @param bool $with_rewrite
		 *
		 * @since 3.0
		 */
		function predefined_page( $slug, $with_rewrite = true ) {
			$page_exists = UM()->query()->find_post_id( 'page', '_um_core', $slug );
			if ( $page_exists ) {
				return;
			}

			$predefined_pages = UM()->config()->get( 'predefined_pages' );
			if ( empty( $predefined_pages ) || ! array_key_exists( $slug, $predefined_pages ) ) {
				return;
			}

			$data = $predefined_pages[ $slug ];

			if ( empty( $data['title'] ) ) {
				return;
			}

			$content = ! empty( $data['content'] ) ? $data['content'] : '';
			$content = apply_filters( 'um_setup_predefined_page_content', $content, $slug );

			$user_page = array(
				'post_title'     => $data['title'],
				'post_content'   => $content,
				'post_name'      => $slug,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'comment_status' => 'closed',
			);

			$post_id = wp_insert_post( $user_page );
			if ( empty( $post_id ) || is_wp_error( $post_id ) ) {
				return;
			}

			update_post_meta( $post_id, '_um_core', $slug );

			UM()->options()->update( UM()->options()->get_predefined_page_option_key( $slug ), $post_id );

			if ( $with_rewrite ) {
				// reset rewrite rules after page creation and option upgrade
				UM()->rewrite()->reset_rules();
			}
		}


		/**
		 * Install all predefined pages
		 *
		 * @since 3.0
		 */
		function predefined_pages() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			//Install Core Pages
			foreach ( UM()->config()->get( 'predefined_pages' ) as $slug => $data ) {
				// fires without rewrites for making that after the loop
				$this->predefined_page( $slug, false );
			}

			// reset rewrite rules after first install of core pages
			UM()->rewrite()->reset_rules();
		}

	}
}
