<?php
namespace um;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\Extensions' ) ) {


	/**
	 * Class Extensions
	 *
	 * @package um
	 *
	 * @method void bbpress_activation()
	 */
	class Extensions {


		/**
		 * Extensions list
		 *
		 * @var array
		 */
		var $list = array();


		/**
		 * Extensions data
		 *
		 * @var array
		 */
		var $plugin_data = array();


		/**
		 * Extensions constructor.
		 */
		function __construct() {

		}


		/**
		 *
		 */
		function check_dependencies() {
			$extensions = $this->get_list();

			foreach ( $extensions as $slug ) {
				$extension = $this->get_info( $slug );

				list( $run, $slug, $message ) = apply_filters_ref_array( 'um_extension_custom_dependencies', array( true, $slug, '' ) );

				if ( $run ) {
					$compare_version_result = UM()->dependencies()->compare_versions( $extension['min_core_version'], $extension['version'], $slug, $extension['title'] );

					if ( true !== $compare_version_result ) {
						UM()->admin()->notices()->add_notice( "{$slug}_dependencies", array(
							'class'     => 'error',
							'message'   => '<p>' . $compare_version_result . '</p>',
						), 1 );
					}
				} elseif ( ! $run && ! empty( $message ) ) {
					UM()->admin()->notices()->add_notice( "{$slug}_dependencies", array(
						'class'     => 'error',
						'message'   => $message,
					), 1 );
				}

				if ( $run ) {
					UM()->call_class( "um_ext\um_{$slug}\Init" );
				}
			}
		}


		/**
		 * @param $settings
		 *
		 * @return mixed
		 */
		function license_options( $settings ) {

			$extensions = $this->get_list();

			if ( empty( $extensions ) ) {
				return $settings;
			}

			foreach ( $extensions as $slug ) {
				$extension = $this->get_info( $slug );

				if ( isset( $extension['plan'] ) && $extension['plan'] == 'free' ) {
					continue;
				}

				$settings['licenses']['fields'][] = array(
					'id'        => "um_{$slug}_license_key",
					'label'     => sprintf( __( '%s License Key', 'ultimate-member' ), $extension['title'] ),
					'item_name' => $extension['item_name'],
					'author'    => 'Ultimate Member',
					'version'   => $extension['version'],
				);
			}

			return $settings;
		}


		/**
		 * Loading Extensions localizations
		 */
		function localization() {
			$extensions = $this->get_list();

			foreach ( $extensions as $slug ) {
				$extension = $this->get_info( $slug );

				$locale = ( get_locale() != '' ) ? get_locale() : 'en_US';
				load_textdomain( $extension['textdomain'], WP_LANG_DIR . '/plugins/' . $extension['textdomain'] . '-' . $locale . '.mo');
				load_plugin_textdomain( $extension['textdomain'], false, dirname( $extension['plugin'] ) . '/languages/' );
			}
		}


		/**
		 * @param $slug
		 *
		 * @return string
		 */
		function get_version( $slug ) {

			$version = '';

			return $version;
		}


		/**
		 * @param $slug
		 * @param bool $field
		 *
		 * @return array|bool
		 */
		function get_info( $slug, $field = false ) {
			if ( ! $field ) {
				return ! empty( $this->plugin_data[ $slug ] ) ? $this->plugin_data[ $slug ] : array();
			} else {
				return ! empty( $this->plugin_data[ $slug ][ $field ] ) ? $this->plugin_data[ $slug ][ $field ] : false;
			}
		}


		/**
		 * @param string $slug
		 * @param array $plugin_data
		 */
		function add( $slug, $plugin_data ) {
			$this->list[] = $slug;
			$this->plugin_data[ $slug ] = $plugin_data;
		}


		/**
		 * Activate Extension Process
		 * Common functions in activation
		 *
		 * @param $slug
		 */
		function activate( $slug ) {
			$plugin_data = $this->get_info( $slug );

			//if extension wasn't inited, init it firstly via "um_{$slug}_add" function
			//"um_{$slug}_add" must be in the preset structure of UM extension
			if ( empty( $plugin_data ) && function_exists( "um_{$slug}_add" ) ) {
				call_user_func( "um_{$slug}_add" );
				$plugin_data = $this->get_info( $slug );
			}

			//first install
			$version = get_option( "um_{$slug}_version" );
			if ( ! $version ) {
				update_option( "um_{$slug}_last_version_upgrade", $plugin_data['version'] );
			}

			if ( $version != $plugin_data['version'] ) {
				update_option( "um_{$slug}_version", $plugin_data['version'] );
			}


			//start setup
			UM()->extension( $slug )->setup()->start();
		}


		/**
		 * @return array
		 */
		function get_list() {
			return $this->list;
		}


		function get_packages( $slug ) {
			$plugin_info = $this->get_info( $slug );
			$packages_dir = $plugin_info['path'] . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'packages';

			$update_versions = array();
			$handle = opendir( $packages_dir );
			if ( $handle ) {
				while ( false !== ( $filename = readdir( $handle ) ) ) {
					if ( $filename != '.' && $filename != '..' ) {
						if ( is_dir( $packages_dir . DIRECTORY_SEPARATOR . $filename ) ) {
							$update_versions[] = $filename;
						}
					}
				}
				closedir( $handle );

				usort( $update_versions, array( UM()->admin_upgrade(), 'version_compare_sort' ) );
			}

			return $update_versions;
		}


	}
}