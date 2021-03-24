<?php
namespace um;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Modules
 *
 * @package um
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
	 *
	 */
	function predefined_modules() {
		$modules = [
			'forumwp' => [
				'title'         => __( 'ForumWP integration', 'ultimate-member' ),
				'description'   => __( 'Integrates Ultimate Member with ForumWP.', 'ultimate-member' ),
				'plugin_slug'   => 'um-forumwp/um-forumwp.php',
				'path'          => um_path . 'modules' . DIRECTORY_SEPARATOR . 'forumwp',
				'url'           => um_url . 'modules/forumwp/',
			],
			'online' => [
				'title'         => __( 'Online', 'ultimate-member' ),
				'description'   => __( 'Display online users and show the user online status on your site.', 'ultimate-member' ),
				'plugin_slug'   => 'um-online/um-online.php',
				'path'          => um_path . 'modules' . DIRECTORY_SEPARATOR . 'online',
				'url'           => um_url . 'modules/online/',
			],
			'recaptcha' => [
				'title'         => __( 'Google reCAPTCHA', 'ultimate-member' ),
				'description'   => __( 'Protect your website from spam and integrate Google reCAPTCHA into your Ultimate Member forms.', 'ultimate-member' ),
				'plugin_slug'   => 'um-recaptcha/um-recaptcha.php',
				'path'          => um_path . 'modules' . DIRECTORY_SEPARATOR . 'recaptcha',
				'url'           => um_url . 'modules/recaptcha/',
			],
		];

		$all_plugins = apply_filters( 'all_plugins', get_plugins() );

		foreach ( $modules as $slug => &$data ) {
			$data['key'] = $slug;
			$data['disabled'] = array_key_exists( $data['plugin_slug'], $all_plugins );

			if ( $data['disabled'] ) {
				$data['description'] = '<strong>' . sprintf( __( 'Module will be disabled until "%s" plugin is installed.', 'ultimate-member' ), $all_plugins[ $data['plugin_slug'] ]['Name'] ) . '</strong><br />' . $data['description'];
			}
		}

		$this->list = apply_filters( 'um_predefined_modules', $modules );
	}


	/**
	 * Get list of modules
	 *
	 * @return array
	 */
	function get_list() {
		return $this->list;
	}


	/**
	 * Get module data
	 *
	 * @param string $slug
	 *
	 * @return bool|array
	 */
	function get_data( $slug ) {
		$list = $this->get_list();

		if ( ! $this->exists( $slug ) ) {
			return false;
		}

		return $list[ $slug ];
	}


	/**
	 * @param $slug
	 *
	 * @return bool
	 */
	function exists( $slug ) {
		$modules = $this->get_list();

		return array_key_exists( $slug, $modules );
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
	 * @param array $data Module data
	 */
	private function run( $slug, $data ) {
		if ( ! empty( $data['path'] ) ) {
			$slug = UM()->undash( $slug );
			UM()->call_class( "umm\\{$slug}\\Init" );
		}
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
	 * @param string $slug
	 *
	 */
	function activate( $slug ) {
		if ( ! $this->can_activate( $slug ) ) {
			return;
		}

		$data = $this->get_data( $slug );
		if ( ! empty( $data['path'] ) ) {
			$this->install( $slug )->start();
		}

		$slug = UM()->undash( $slug );

		UM()->options()->update( "module_{$slug}_on", true );

		$first_activation = UM()->options()->get( "module_{$slug}_first_activation" );
		if ( empty( $first_activation ) ) {
			UM()->options()->update( "module_{$slug}_first_activation", time() );
		}
	}


	/**
	 * @param string $slug
	 *
	 */
	function deactivate( $slug ) {
		if ( ! $this->can_deactivate( $slug ) ) {
			return;
		}

		$slug = UM()->undash( $slug );

		UM()->options()->update( "module_{$slug}_on", false );
	}


	/**
	 * @param string $slug
	 *
	 */
	function flush_data( $slug ) {
		if ( ! $this->can_flush( $slug ) ) {
			return;
		}

		$data = $this->get_data( $slug );

		if ( empty( $data['path'] ) ) {
			return;
		}

		$slug = UM()->undash( $slug );
		UM()->options()->remove( "module_{$slug}_first_activation" );

		include_once $data['path'] . DIRECTORY_SEPARATOR . 'uninstall.php';
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

			$this->run( $slug, $data );
		}
	}
}