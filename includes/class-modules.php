<?php
namespace um;


if ( ! defined( 'ABSPATH' ) ) exit;


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
	private $list = [];


	/**
	 * Modules constructor.
	 */
	function __construct() {
		add_action( 'um_core_loaded', [ &$this, 'predefined_modules' ], 0 );
	}


	/**
	 * Set modules list
	 * @usedby on `um_core_loaded` hook for modules initialization
	 *
	 * @uses get_plugins() for getting installed plugins list
	 * @uses DIRECTORY_SEPARATOR for getting proper path to modules' directories
	 */
	function predefined_modules() {
		$modules = [
			'jobboardwp'   => [
				'title'         => __( 'JobBoardWP integration', 'ultimate-member' ),
				'description'   => __( 'Integrates Ultimate Member with JobBoardWP.', 'ultimate-member' ),
				'plugin_slug'   => 'um-jobboardwp/um-jobboardwp.php',
			],
			'forumwp'   => [
				'title'         => __( 'ForumWP integration', 'ultimate-member' ),
				'description'   => __( 'Integrates Ultimate Member with ForumWP.', 'ultimate-member' ),
				'plugin_slug'   => 'um-forumwp/um-forumwp.php',
			],
			'online'    => [
				'title'         => __( 'Online', 'ultimate-member' ),
				'description'   => __( 'Display online users and show the user online status on your site.', 'ultimate-member' ),
				'plugin_slug'   => 'um-online/um-online.php',
			],
			'recaptcha' => [
				'title'         => __( 'Google reCAPTCHA', 'ultimate-member' ),
				'description'   => __( 'Protect your website from spam and integrate Google reCAPTCHA into your Ultimate Member forms.', 'ultimate-member' ),
				'plugin_slug'   => 'um-recaptcha/um-recaptcha.php',
			],
			'terms-conditions'   => [
				'title'         => __( 'Terms & Conditions', 'ultimate-member' ),
				'description'   => __( 'Add a terms and condition checkbox to your registration forms & require users to agree to your T&Cs before registering on your site.', 'ultimate-member' ),
				'plugin_slug'   => 'um-terms-conditions/um-terms-conditions.php',
			],
		];

		$all_plugins = apply_filters( 'all_plugins', get_plugins() );

		foreach ( $modules as $slug => &$data ) {
			$data['key'] = $slug;

			$data['path'] = um_path . 'modules' . DIRECTORY_SEPARATOR . $slug;
			$data['url'] = um_url . "modules/{$slug}/";

			// @todo checking the proper module structure function if not proper make 'invalid' data with displaying red line in list table

			// check the module's dir
			if ( ! is_dir( $data['path'] ) ) {

				$data['disabled'] = true;
				$data['description'] = '<strong>' . __( 'Module is hasn\'t been installed properly. Please check the module\'s directory and re-install it.', 'ultimate-member' ) . '</strong><br />' . $data['description'];

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

		$this->list = apply_filters( 'um_predefined_modules', $modules );
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
	 * @param string $slug
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
	 * @param string $slug
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
	 * @return bool
	 */
	function is_active( $slug ) {
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
	 * Check if module is active
	 *
	 * @param string $slug Module slug
	 *
	 * @return bool
	 */
	function is_disabled( $slug ) {
		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		$data = $this->get_data( $slug );
		return ! empty( $data['disabled'] );
	}


	/**
	 * Run main class of module
	 *
	 * @param string $slug Module slug
	 */
	private function run( $slug ) {
		$slug = UM()->undash( $slug );
		UM()->call_class( "umm\\{$slug}\\Init" );
	}


	/**
	 * @param string $slug
	 *
	 * @return mixed
	 */
	function install( $slug ) {
		$slug = UM()->undash( $slug );
		return UM()->call_class( "umm\\{$slug}\\Install" );
	}


	/**
	 * @param string $slug
	 *
	 * @return bool
	 */
	function can_activate( $slug ) {
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

		return true;
	}


	/**
	 * @param string $slug
	 *
	 * @return bool
	 */
	function can_deactivate( $slug ) {
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
	 * @param string $slug
	 *
	 * @return bool
	 */
	function can_flush( $slug ) {
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
	 * @param string $slug Module's slug
	 *
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
	 * @param string $slug
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
	 * @param string $slug
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

		include_once $data['path'] . DIRECTORY_SEPARATOR . 'uninstall.php';

		return true;
	}


	/**
	 * Load all modules
	 */
	function load_modules() {
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
}