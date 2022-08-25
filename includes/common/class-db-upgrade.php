<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class DB_Upgrade
 *
 * This class handles all functions that changes data structures and moving files
 *
 * @package um\common
 */
class DB_Upgrade {

	/**
	 * Path to the folder with updates
	 *
	 * @var string
	 */
	protected $packages_dir;

	/**
	 * Path to the folder with updates
	 *
	 * @var array
	 */
	protected $necessary_packages;

	/**
	 * DB_Upgrade constructor.
	 */
	public function __construct() {
		// early triggered common hook
		add_action( 'um_core_loaded', array( $this, 'init_variables' ), 10 );
	}

	/**
	 *
	 */
	public function init_variables() {
		$this->packages_dir       = UM_PATH . 'updates' . DIRECTORY_SEPARATOR;
		$this->necessary_packages = $this->need_run_upgrades();
	}

	/**
	 * Get array of necessary upgrade packages
	 *
	 * @return array
	 */
	private function need_run_upgrades() {
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
	private function get_packages() {
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
	 * Sort versions by version compare function
	 * Uses as callback function for `usort()` inside class
	 *
	 * @param string $a
	 * @param string $b
	 * @return int  -1 if the first version is lower than the second,
	 *               0 if they are equal, and
	 *               1 if the second is lower.
	 */
	private function version_compare_sort( $a, $b ) {
		return version_compare( $a, $b );
	}

	/**
	 * Check if there are available packages for upgrades
	 * @return bool
	 */
	public function need_upgrade() {
		return ! empty( $this->necessary_packages );
	}
}
