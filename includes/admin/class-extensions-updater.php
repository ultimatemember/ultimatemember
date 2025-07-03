<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles all functions that changes data structures and moving files
 */
class Extensions_Updater {

	/**
	 * @var array
	 */
	private $updater_data;

	/**
	 * @param false|array $args
	 */
	public function __construct( $args = false ) {
		if ( empty( $args ) ) {
			return;
		}

		$this->updater_data = wp_parse_args(
			$args,
			array(
				'slug'    => '',
				'version' => '0.0.0',
				'path'    => '',
			)
		);

		add_action( 'admin_init', array( $this, 'maybe_run_updater' ) );
	}

	/**
	 * Maybe run upgrade if needed.
	 */
	public function maybe_run_updater() {
		$last_version_upgrade = $this->get_last_version_upgrade();
		if ( ! empty( $last_version_upgrade ) && version_compare( $last_version_upgrade, $this->updater_data['version'], '>=' ) ) {
			// Don't need update.
			return;
		}

		$packages = $this->get_packages();
		if ( ! empty( $packages ) ) {
			$packages_dir = $this->get_packages_dir();
			foreach ( $packages as $package_version ) {
				if ( version_compare( $package_version, $last_version_upgrade, '<=' ) ) {
					continue;
				}

				if ( version_compare( $package_version, $this->updater_data['version'], '>' ) ) {
					continue;
				}

				$file_path = $packages_dir . $package_version . '.php';
				if ( ! file_exists( $file_path ) ) {
					continue;
				}

				include_once $file_path;
				$this->set_last_version_upgrade( $package_version );
			}
		}

		$this->set_last_version_upgrade( $this->updater_data['version'] );
	}

	/**
	 * Get packages list, based on the files in packages dir.
	 */
	private function get_packages() {
		$packages = array();

		$handle = opendir( $this->get_packages_dir() );
		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition, WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition -- reading folder's content here
		while ( false !== ( $filename = readdir( $handle ) ) ) {
			if ( '.' !== $filename && '..' !== $filename ) {
				$packages[] = preg_replace( '/(.*?)\.php/i', '$1', $filename );
			}
		}
		closedir( $handle );

		usort(
			$packages,
			static function ( $a, $b ) {
				return version_compare( $a, $b );
			}
		);

		return $packages;
	}

	/**
	 * Retrieve the directory path where packages are stored for updates.
	 *
	 * @return string The normalized path to the packages' directory.
	 */
	private function get_packages_dir() {
		return wp_normalize_path( $this->updater_data['path'] . 'includes/updates/' );
	}

	/**
	 * Gets the last version upgrade from options.
	 *
	 * @return string The last version upgrade.
	 */
	private function get_last_version_upgrade() {
		return get_option( 'um_' . $this->updater_data['slug'] . '_last_version_upgrade', '0.0.0' );
	}

	/**
	 * Set the last version upgrade for the updater.
	 *
	 * @param string $version The version to set as the last upgrade version.
	 */
	private function set_last_version_upgrade( $version ) {
		update_option( 'um_' . $this->updater_data['slug'] . '_last_version_upgrade', $version );
	}
}
