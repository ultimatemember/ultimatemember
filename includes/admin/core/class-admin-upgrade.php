<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Admin_Upgrade' ) ) {
    /**
     * This class handles all functions that changes data structures and moving files
     */
    class Admin_Upgrade {
        var $update_versions;
        var $packages_dir;


        function __construct() {
            $this->packages_dir = plugin_dir_path( __FILE__ ).'packages/';

            $um_last_version_upgrade = get_option( 'um_last_version_upgrade' );

            if ( ! $um_last_version_upgrade || version_compare( $um_last_version_upgrade, ultimatemember_version, '<' ) )
                add_action( 'admin_init', array( $this, 'packages' ), 10 );
        }


		/**
		 * Load packages
		 */
		public function packages() {
			if ( ! ini_get( 'safe_mode' ) ) {
				@set_time_limit(0);
			}

			$this->set_update_versions();

			$um_last_version_upgrade = get_option( 'um_last_version_upgrade' );
			$um_last_version_upgrade = ! $um_last_version_upgrade ? '0.0.0' : $um_last_version_upgrade;

			foreach ( $this->update_versions as $update_version ) {

				if ( version_compare( $update_version, $um_last_version_upgrade, '<=' ) )
					continue;

				if ( version_compare( $update_version, ultimatemember_version, '>' ) )
					continue;

				$file_path = $this->packages_dir . $update_version . '.php';

				if ( file_exists( $file_path ) ) {
					include_once( $file_path );
					update_option( 'um_last_version_upgrade', $update_version );
				}
			}
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