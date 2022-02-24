<?php
namespace um;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Modules
 *
 * @package um
 *
 * @since 3.0
 */
class Modules {


	/**
	 * Modules list
	 *
	 * @var array
	 */
	private $list = array();


	/**
	 * Modules constructor.
	 */
	function __construct() {
		add_action( 'um_core_loaded', array( &$this, 'predefined_modules' ), 0 );
	}


	/**
	 * Set modules list
	 * @usedby on `um_core_loaded` hook for modules initialization
	 *
	 * @uses get_plugins() for getting installed plugins list
	 * @uses DIRECTORY_SEPARATOR for getting proper path to modules' directories
	 */
	function predefined_modules() {
		$modules = UM()->config()->get( 'modules' );
		$modules = apply_filters( 'um_predefined_modules', $modules );

		/** This filter is documented in wp-admin/includes/class-wp-plugins-list-table.php */
		$all_plugins = apply_filters( 'all_plugins', get_plugins() );

		foreach ( $modules as $slug => &$data ) {
			// @todo checking the proper module structure function if not proper make 'invalid' data with displaying red line in list table

			// check the module's dir
			if ( ! is_dir( $data['path'] ) ) {

				$data['disabled'] = true;
				$data['description'] = '<strong>' . __( 'Module has not been installed properly. Please check the module\'s directory and re-install it.', 'ultimate-member' ) . '</strong><br />' . $data['description'];

			} else {

				if ( array_key_exists( 'plugin_slug', $data ) ) {
					$data['disabled'] = array_key_exists( $data['plugin_slug'], $all_plugins );

					if ( $data['disabled'] ) {
						$data['description'] = '<strong>' . sprintf( __( 'Module will be disabled until "%s" plugin is installed.', 'ultimate-member' ), $all_plugins[ $data['plugin_slug'] ]['Name'] ) . '</strong><br />' . $data['description'];
					}
				}

			}

			// set `disabled = false` by default
			if ( ! array_key_exists( 'disabled', $data ) ) {
				$data['disabled'] = false;
			}
		}

		$this->list = apply_filters( 'um_predefined_validated_modules', $modules );
	}


	/**
	 * Get list of modules
	 *
	 * @uses list
	 *
	 * @return array
	 */
	function get_list() {
		$list = apply_filters( 'um_formatting_modules_list', $this->list );
		return $list;
	}


	/**
	 * Get module data
	 *
	 * @param string $slug Module slug
	 *
	 * @return bool|array Returns `false` if module doesn't exists
	 *
	 * @uses exists
	 */
	function get_data( $slug ) {
		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		return $this->list[ $slug ];
	}


	/**
	 * Checking if module exists
	 *
	 * @param string $slug Module slug
	 *
	 * @return bool Returns `false` if module doesn't exists, otherwise `true`
	 */
	function exists( $slug ) {
		return array_key_exists( $slug, $this->list );
	}


	/**
	 * Check if module is active
	 *
	 * @param string $slug Module slug
	 *
	 *
	 * @uses exists
	 * @uses is_disabled
	 * @uses UM::undash()
	 * @uses UM::options()
	 *
	 * @return bool
	 */
	function is_active( $slug ) {
		if ( UM()->is_legacy ) {
			return false;
		}

		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		if ( $this->is_disabled( $slug ) ) {
			return false;
		}

		$slug = UM()->undash( $slug );
		$is_active = UM()->options()->get( "module_{$slug}_on" );

		return ! empty( $is_active );
	}


	/**
	 * Check if module is disabled
	 *
	 * @param string $slug Module slug
	 *
	 * @uses exists
	 * @uses get_data
	 *
	 * @return bool
	 */
	function is_disabled( $slug ) {
		if ( UM()->is_legacy ) {
			return false;
		}

		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		$data = $this->get_data( $slug );
		return ! empty( $data['disabled'] );
	}


	/**
	 * Check if current user can activate a module
	 *
	 * @param string $slug Module slug
	 *
	 * @uses exists
	 * @uses is_disabled
	 * @uses is_active
	 *
	 * @return bool
	 */
	function can_activate( $slug ) {
		if ( UM()->is_legacy ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		if ( $this->is_disabled( $slug ) ) {
			return false;
		}

		if ( $this->is_active( $slug ) ) {
			return false;
		}

		return apply_filters( 'um_module_can_activate', true, $slug );
	}


	/**
	 * Checking if current user can deactivate a module
	 *
	 * @param string $slug Module slug
	 *
	 * @uses exists
	 * @uses is_disabled
	 * @uses is_active
	 *
	 * @return bool
	 */
	function can_deactivate( $slug ) {
		if ( UM()->is_legacy ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		if ( $this->is_disabled( $slug ) ) {
			return false;
		}

		if ( ! $this->is_active( $slug ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Checking if current user can flush module's data
	 *
	 * @param string $slug Module slug
	 *
	 * @uses exists
	 * @uses is_disabled
	 * @uses is_active
	 * @uses UM::undash()
	 * @uses UM::options()
	 *
	 * @return bool
	 */
	function can_flush( $slug ) {
		if ( UM()->is_legacy ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		if ( $this->is_disabled( $slug ) ) {
			return false;
		}

		if ( $this->is_active( $slug ) ) {
			return false;
		}

		$slug = UM()->undash( $slug );
		$first_activation = UM()->options()->get( "module_{$slug}_first_activation" );
		if ( empty( $first_activation ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Module's activation handler
	 *
	 * @param string $slug Module's slug
	 *
	 * @uses can_activate
	 * @uses install::start()
	 * @uses UM::undash()
	 * @uses UM::options()
	 *
	 * @return bool
	 */
	function activate( $slug ) {
		if ( ! $this->can_activate( $slug ) ) {
			return false;
		}

		$this->install( $slug )->start();

		$slug = UM()->undash( $slug );

		UM()->options()->update( "module_{$slug}_on", true );

		$first_activation = UM()->options()->get( "module_{$slug}_first_activation" );
		if ( empty( $first_activation ) ) {
			UM()->options()->update( "module_{$slug}_first_activation", time() );
		}

		return true;
	}


	/**
	 * Module's deactivation handler
	 *
	 * @param string $slug Module slug
	 *
	 * @uses can_deactivate
	 * @uses UM::undash()
	 * @uses UM::options()
	 *
	 * @return bool
	 */
	function deactivate( $slug ) {
		if ( ! $this->can_deactivate( $slug ) ) {
			return false;
		}

		$slug = UM()->undash( $slug );

		UM()->options()->update( "module_{$slug}_on", false );

		return true;
	}


	/**
	 * Module's flushing data handler
	 *
	 * @param string $slug Module slug
	 *
	 * @uses can_flush
	 * @uses get_data
	 * @uses UM::undash()
	 * @uses UM::options()
	 *
	 * @return bool
	 */
	function flush_data( $slug ) {
		if ( ! $this->can_flush( $slug ) ) {
			return false;
		}

		$data = $this->get_data( $slug );

		$slug = UM()->undash( $slug );
		UM()->options()->remove( "module_{$slug}_first_activation" );

		$uninstall_path = $data['path'] . DIRECTORY_SEPARATOR . 'uninstall.php';
		if ( file_exists( $uninstall_path ) ) {
			/** @noinspection PhpIncludeInspection */
			include_once $uninstall_path;
		}

		return true;
	}


	/**
	 * Load all modules
	 *
	 * @uses get_list
	 * @uses is_active
	 * @uses run
	 */
	function load_modules() {
		// disable modules init when v2 legacy is used
		if ( UM()->is_legacy ) {
			return;
		}

		$modules = $this->get_list();
		if ( empty( $modules ) ) {
			return;
		}

		foreach ( $modules as $slug => $data ) {
			if ( ! $this->is_active( $slug ) ) {
				continue;
			}

			$this->run( $slug );
		}
	}


	/**
	 * Run main class of module
	 *
	 * @param string $slug Module slug
	 *
	 * @uses UM::undash()
	 * @uses UM::call_class()
	 */
	private function run( $slug ) {
		if ( UM()->is_legacy ) {
			return;
		}

		$slug = UM()->undash( $slug );
		UM()->call_class( "umm\\{$slug}\\Init" );
	}


	/**
	 * Installation handler for single module
	 *
	 * @param string $slug Module slug
	 *
	 * @uses UM::undash()
	 * @uses UM::call_class()
	 *
	 * @return mixed
	 */
	private function install( $slug ) {
		if ( UM()->is_legacy ) {
			return null;
		}

		$slug = UM()->undash( $slug );
		return UM()->call_class( "umm\\{$slug}\\Install" );
	}
}
