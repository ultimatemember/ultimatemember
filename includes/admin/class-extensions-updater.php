<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This class handles all functions that changes data structures and moving files
 */
class Extensions_Updater {

	const EXTRA_TIME = 30;

	const PAGINATION = 50;

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
		add_action( 'um_' . $this->updater_data['slug'] . '_package_start', array( $this, 'package_start' ), 10, 4 );
		add_action( 'um_' . $this->updater_data['slug'] . '_package_complete', array( $this, 'package_complete' ) );
	}

	public function package_start( $version, $file_path, $delay, $per_page ) {
		$hooks = array(
			'start'    => 'um_' . $this->updater_data['slug'] . '_package_start',
			'complete' => 'um_' . $this->updater_data['slug'] . '_package_complete',
		);

		$debug = defined( 'UM_UPDATER_DEBUG' ) && UM_UPDATER_DEBUG;

		include_once $file_path;
		/**
		 * IMPORTANT!!!: Last action that we need to do after package is complete.
		 * `UM()->maybe_action_scheduler()->schedule_single_action(
		 *      time() + 1,
		 *      $hook,
		 *      array(
		 *          'version' => $version,
		 *      )
		 * );`
		 */
	}

	public function package_complete( $version ) {
		$this->set_last_version_upgrade( $version );
		$this->reset_in_process_package_version();
	}

	/**
	 * Maybe run upgrade if needed.
	 */
	public function maybe_run_updater() {
		// Cold start to avoid scheduled actions duplicates. Don't remove it.
		$init = get_transient( 'um_' . $this->updater_data['slug'] . '_updater_init' );
		if ( ! empty( $init ) ) {
			return;
		}
		set_transient( 'um_' . $this->updater_data['slug'] . '_updater_init', true, 10 );

		$next_package = $this->get_next_package();
		if ( empty( $next_package ) ) {
			$this->set_last_version_upgrade( $this->updater_data['version'] );
			$this->reset_in_process_package_version();
			return;
		}

		list( $package_version, $file_path ) = $next_package;

		$in_process_package = $this->get_in_process_package_version();
		if ( ! empty( $in_process_package ) && version_compare( $in_process_package, $package_version, '=' ) ) {
			return;
		}

		// Initialize start package action.
		$action_id = UM()->maybe_action_scheduler()->schedule_single_action(
			time() + self::EXTRA_TIME,
			'um_' . $this->updater_data['slug'] . '_package_start',
			array(
				'version'  => $package_version,
				'path'     => $file_path,
				'delay'    => self::EXTRA_TIME,
				'per_page' => self::PAGINATION,
			)
		);

		// As soon as scheduler single action is created - then set in progress package version to avoid duplicates of the package start action.
		if ( ! empty( $action_id ) ) {
			$this->set_in_process_package_version( $package_version );
		}
	}

	private function get_next_package() {
		$last_version_upgrade = $this->get_last_version_upgrade();
		if ( ! empty( $last_version_upgrade ) && version_compare( $last_version_upgrade, $this->updater_data['version'], '>=' ) ) {
			return null;
		}

		$packages = $this->get_packages();
		if ( empty( $packages ) ) {
			return null;
		}

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

			return array( $package_version, $file_path );
		}

		return null;
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

	/**
	 * Gets the last version upgrade from options.
	 *
	 * @return string The last version upgrade.
	 */
	private function get_in_process_package_version() {
		return get_option( 'um_' . $this->updater_data['slug'] . '_in_process_package_upgrade', '' );
	}

	/**
	 * Set the last version upgrade for the updater.
	 *
	 * @param string $version The version to set as the last upgrade version.
	 */
	private function set_in_process_package_version( $version ) {
		update_option( 'um_' . $this->updater_data['slug'] . '_in_process_package_upgrade', $version );
	}

	/**
	 * Set the last version upgrade for the updater.
	 */
	private function reset_in_process_package_version() {
		update_option( 'um_' . $this->updater_data['slug'] . '_in_process_package_upgrade', '' );
	}
}
