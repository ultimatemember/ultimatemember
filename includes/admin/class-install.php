<?php namespace um\admin;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\Install' ) ) {


	/**
	 * Class Install
	 *
	 * @since 3.0
	 *
	 * @package um\admin
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
			} else {
				UM()->options()->update( 'rest_api_version', '1.0' );
			}

			if ( $version != ultimatemember_version ) {
				update_option( 'um_version', ultimatemember_version );
			}

			$this->set_defaults( UM()->config()->get( 'defaults' ) );

			//run setup
			UM()->common()->create_post_types();
			UM()->setup()->run_setup();
		}


		/**
		 * Set default JB settings
		 *
		 * @param array $defaults
		 *
		 * @since 3.0
		 */
		function set_defaults( $defaults ) {
			if ( ! empty( $defaults ) ) {
				$options = get_option( 'um_options', [] );

				foreach ( UM()->config()->settings_defaults as $key => $value ) {
					//set new options to default
					if ( ! isset( $options[ $key ] ) ) {
						$options[ $key ] = $value;
					}
				}

				update_option( 'um_options', $options );
			}
		}


		/**
		 * Parse user capabilities and set the proper capabilities for roles
		 *
		 * @since 1.0
		 */
		function create_roles() {
			global $wp_roles;

			if ( ! class_exists( '\WP_Roles' ) ) {
				return;
			}

			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new \WP_Roles();
			}

			$all_caps = JB()->config()->get( 'all_caps' );
			$custom_roles = JB()->config()->get( 'custom_roles' );
			$capabilities_map = JB()->config()->get( 'capabilities_map' );

			foreach ( $custom_roles as $role_id => $role_title ) {
				$wp_roles->remove_role( $role_id );

				if ( empty( $capabilities_map[ $role_id ] ) ) {
					$capabilities_map[ $role_id ] = [];
				}

				add_role( $role_id, $role_title, $capabilities_map[ $role_id ] );
			}

			foreach ( $capabilities_map as $role_id => $caps ) {
				foreach ( array_diff( $caps, $all_caps ) as $cap ) {
					$wp_roles->remove_cap( $role_id, $cap );
				}

				foreach ( $caps as $cap ) {
					$wp_roles->add_cap( $role_id, $cap );
				}
			}
		}


		/**
		 * Create pre-defined Job Types
		 *
		 * @since 1.0
		 */
		function create_job_types() {
			// create post types here because on install there aren't registered CPT and terms
			JB()->common()->cpt()->create_post_types();

			$types = [
				'full-time' => [
					'title'     => __( 'Full-time', 'jobboardwp' ),
					'color'     => '#0e6245',
					'bgcolor'   => '#cbf4c9',
				],
				'part-time' => [
					'title'     => __( 'Part-time', 'jobboardwp' ),
					'color'     => '#3d4eac',
					'bgcolor'   => '#d6ecff',
				],
				'internship' => [
					'title'     => __( 'Internship', 'jobboardwp' ),
					'color'     => '#a3052a',
					'bgcolor'   => '#fedce4',
				],
				'freelance' => [
					'title'     => __( 'Freelance', 'jobboardwp' ),
					'color'     => '#983705',
					'bgcolor'   => '#f8e5b9',
				],
				'temporary' => [
					'title'     => __( 'Temporary', 'jobboardwp' ),
					'color'     => '#5c3eb7',
					'bgcolor'   => '#e0d4ff',
				],
				'graduate' => [
					'title'     => __( 'Graduate', 'jobboardwp' ),
					'color'     => '#8c2a84',
					'bgcolor'   => '#ffd7fc',
				],
				'volunteer' => [
					'title'     => __( 'Volunteer', 'jobboardwp' ),
					'color'     => '#4f566b',
					'bgcolor'   => '#e3e8ee',
				],
			];

			foreach ( $types as $key => $type ) {
				$term = wp_insert_term( $type['title'], 'jb-job-type', [
					'description' => '',
					'parent'      => 0,
					'slug'        => $key,
				] );

				if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
					update_term_meta( $term['term_id'], 'jb-color', $type['color'] );
					update_term_meta( $term['term_id'], 'jb-background', $type['bgcolor'] );
				}
			}
		}


		/**
		 * Install Core Pages
		 *
		 * @since 1.0
		 */
		function core_pages() {
			foreach ( JB()->config()->get( 'core_pages' ) as $slug => $array ) {

				$page_id = JB()->options()->get( $slug . '_page' );
				if ( ! empty( $page_id ) ) {
					$page = get_post( $page_id );

					if ( isset( $page->ID ) ) {
						continue;
					}
				}

				//If page does not exist - create it
				$user_page = [
					'post_title'        => $array['title'],
					'post_content'      => ! empty( $array['content'] ) ? $array['content'] : '',
					'post_name'         => $slug,
					'post_type'         => 'page',
					'post_status'       => 'publish',
					'post_author'       => get_current_user_id(),
					'comment_status'    => 'closed'
				];

				$post_id = wp_insert_post( $user_page );
				if ( empty( $post_id ) || is_wp_error( $post_id ) ) {
					continue;
				}

				JB()->options()->update( $slug . '_page', $post_id );
			}
		}

	}
}
