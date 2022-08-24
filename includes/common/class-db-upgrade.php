<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\common\DB_Upgrade' ) ) {


	/**
	 * Class DB_Upgrade
	 *
	 * This class handles all functions that changes data structures and moving files
	 *
	 * @package um\common
	 */
	class DB_Upgrade {


		/**
		 * @var null
		 */
		protected static $instance = null;


		/**
		 * @var
		 */
		var $update_versions;
		var $update_packages;
		var $necessary_packages;


		/**
		 * @var string
		 */
		var $packages_dir;


		/**
		 * Main DB_Upgrade Instance
		 *
		 * Ensures only one instance of UM is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @see UM()
		 * @return DB_Upgrade - Main instance
		 */
		static public function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * DB_Upgrade constructor.
		 */
		function __construct() {
		}


		public function get_packages_dir() {
			$this->packages_dir = plugin_dir_path( __FILE__ ) . 'packages' . DIRECTORY_SEPARATOR;
			return $this->packages_dir;
		}


		public function get_necessary_packages() {
			$this->necessary_packages = $this->need_run_upgrades();
			return $this->necessary_packages;
		}


		/**
		 * Get array of necessary upgrade packages
		 *
		 * @return array
		 */
		function need_run_upgrades() {
			$um_last_version_upgrade = get_option( 'um_last_version_upgrade', '1.3.88' );

			$diff_packages = array();

			$all_packages = $this->get_packages();
			foreach ( $all_packages as $package ) {
				if ( version_compare( $um_last_version_upgrade, $package, '<' ) && version_compare( $package, UM_VERSION, '<=' ) ) {
					$diff_packages[] = $package;
				}
			}

			return $diff_packages;
		}


		/**
		 * Get all upgrade packages
		 *
		 * @return array
		 */
		function get_packages() {
			$update_versions = array();
			$handle = opendir( $this->packages_dir );
			if ( $handle ) {
				while ( false !== ( $filename = readdir( $handle ) ) ) {
					if ( $filename != '.' && $filename != '..' ) {
						if ( is_dir( $this->packages_dir . $filename ) ) {
							$update_versions[] = $filename;
						}
					}
				}
				closedir( $handle );

				usort( $update_versions, array( &$this, 'version_compare_sort' ) );
			}

			return $update_versions;
		}


		/**
		 * Parse packages dir for packages files
		 */
		function set_update_versions() {
			$update_versions = array();
			$handle = opendir( $this->packages_dir );
			if ( $handle ) {
				while ( false !== ( $filename = readdir( $handle ) ) ) {
					if ( $filename != '.' && $filename != '..' )
						$update_versions[] = preg_replace( '/(.*?)\.php/i', '$1', $filename );
				}
				closedir( $handle );

				usort( $update_versions, array( &$this, 'version_compare_sort' ) );

				$this->update_versions = $update_versions;
			}
		}


		/**
		 * Sort versions by version compare function
		 * @param $a
		 * @param $b
		 * @return mixed
		 */
		function version_compare_sort( $a, $b ) {
			return version_compare( $a, $b );
		}

	}
}
