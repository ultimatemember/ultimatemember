<?php
/**
 * Admin theme updater
 *
 * @package um\admin\core
 */

namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\core\Admin_Theme_Updater' ) ) {


	/**
	 * Class Admin_Theme_Updater
	 */
	class Admin_Theme_Updater {


		/**
		 * Restored themes
		 *
		 * @var array
		 */
		private $restored = array();


		/**
		 * Saved themes
		 *
		 * @var array
		 */
		private $saved = array();


		/**
		 * Class constructor
		 */
		public function __construct() {
			add_filter( 'upgrader_package_options', array( $this, 'upgrader_package_options' ), 40, 1 );
			add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 40, 2 );
		}


		/**
		 * Copy directory
		 *
		 * @param string $src   Source directory.
		 * @param string $dest  Destination directory.
		 */
		public static function recurse_copy( $src, $dest ) {

			if ( ! is_dir( $dest ) ) {
				wp_mkdir_p( $dest );
			}

			$dir = opendir( $src );
			do {
				$file = readdir( $dir );
				if ( '.' !== $file && '..' !== $file ) {
					if ( is_dir( $src . DIRECTORY_SEPARATOR . $file ) ) {
						self::recurse_copy( $src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file );
					} elseif ( is_file( $src . DIRECTORY_SEPARATOR . $file ) ) {
						copy( $src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file );
					}
				}
			} while ( false !== $file );
			closedir( $dir );
		}


		/**
		 * Restore UM templates to theme directory
		 *
		 * @param  string $name  Directory name for the theme.
		 *
		 * @return void
		 */
		public function restore_templates( $name = '' ) {
			$theme = wp_get_theme( $name );

			if ( empty( $theme ) || ! $theme->exists() ) {
				return;
			}
			if ( isset( $this->restored[ $theme->get( 'Name' ) ] ) ) {
				return;
			}
			if ( empty( $this->saved[ $theme->get( 'Name' ) ] ) ) {
				return;
			}

			$old_version = get_option( 'theme_version ' . $theme->get( 'Name' ) );
			$version     = $theme->get( 'Version' );
			if ( $old_version === $version ) {
				return;
			}

			$temp_dir = UM()->uploader()->get_core_temp_dir() . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme->get( 'template' );
			if ( ! is_dir( $temp_dir ) ) {
				return;
			}

			$um_dir = $theme->get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'ultimate-member';
			wp_mkdir_p( $um_dir );

			$src  = realpath( $temp_dir );
			$dest = realpath( $um_dir );
			if ( $src && $dest ) {
				self::recurse_copy( $src, $dest );
				UM()->files()->remove_dir( $src );
			}

			delete_option( 'theme_version ' . $theme->get( 'Name' ) );
			$this->restored[ $theme->get( 'Name' ) ] = $theme->get( 'Version' );
		}


		/**
		 * Save UM templates to temp directory
		 *
		 * @param  string $name  Directory name for the theme.
		 *
		 * @return void
		 */
		public function save_templates( $name = '' ) {
			$theme = wp_get_theme( $name );

			if ( empty( $theme ) || ! $theme->exists() ) {
				return;
			}
			if ( isset( $this->restored[ $theme->get( 'Name' ) ] ) ) {
				return;
			}
			if ( isset( $this->saved[ $theme->get( 'Name' ) ] ) ) {
				return;
			}

			$um_dir = $theme->get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'ultimate-member';
			if ( ! is_dir( $um_dir ) ) {
				return;
			}

			$temp_dir = UM()->uploader()->get_core_temp_dir() . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme->get( 'template' );
			wp_mkdir_p( $temp_dir );

			$src  = realpath( $um_dir );
			$dest = realpath( $temp_dir );
			if ( $src && $dest ) {
				self::recurse_copy( $src, $dest );
			}

			update_option( 'theme_version ' . $theme->get( 'Name' ), $theme->get( 'Version' ) );
			$this->saved[ $theme->get( 'Name' ) ] = $theme->get( 'Version' );
		}


		/**
		 * Filters the package options before running an update.
		 *
		 * @hook   upgrader_package_options
		 *
		 * @param  array $options  Options used by the upgrader.
		 *
		 * @return array
		 */
		public function upgrader_package_options( $options ) {
			if ( isset( $options['hook_extra'] ) && isset( $options['hook_extra']['theme'] ) ) {
				$this->save_templates( $options['hook_extra']['theme'] );
			}
			return $options;
		}


		/**
		 * Fires when the upgrader process is complete.
		 *
		 * @hook  upgrader_process_complete
		 *
		 * @param \WP_Upgrader $upgrader  WP_Upgrader instance.
		 * @param array        $options   Array of bulk item update data.
		 */
		public function upgrader_process_complete( $upgrader, $options ) {
			if ( isset( $options['themes'] ) && is_array( $options['themes'] ) ) {
				foreach ( $options['themes'] as $theme ) {
					$this->restore_templates( $theme );
				}
			}
		}

	}
}
