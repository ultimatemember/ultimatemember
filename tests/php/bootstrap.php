<?php
/**
 * Ultimate Member Tests Bootstrap
 *
 * @since 1.3.84
 */
class UltimateMember_Unit_Tests_Bootstrap {
	/** @var \UltimateMember_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing includes directory */
	public $includes_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment.
	 *
	 * @since 1.3.84
	 */
	public function __construct() {
		define( 'DOING_AJAX', true );
		ini_set( 'display_errors','on' );
		error_reporting( E_ALL );
		$this->tests_dir    = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'tests';
		$this->includes_dir    = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'includes';
		$this->plugin_dir   = dirname( dirname( dirname( $this->tests_dir ) ) );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

		// load test function so tests_add_filter() is available
		require_once( $this->wp_tests_dir . '/includes/functions.php' );

		// load UltimateMember
		tests_add_filter( 'plugins_loaded', array( $this, 'load_plugin' ) );

		// install UltimateMember
		tests_add_filter( 'setup_theme', array( $this, 'install_plugin' ) );

		// load the WP testing environment
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		// load UltimateMember testing framework
		$this->includes();
	}

	/**
	 * Load UltimateMember.
	 *
	 * @since 1.3.84
	 */
	public function load_plugin() {
		//require_once( $this->plugin_dir . '/index.php' );
	}

	/**
	 * Install UltimateMember after the test environment and UltimateMember have been loaded.
	 *
	 * @since 1.3.84
	 */
	public function install_plugin() {
		global $wp_version;

		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		if ( version_compare( $wp_version, '4.7.0' ) >= 0 ) {
			$GLOBALS['wp_roles'] = new WP_Roles();
		} else {
			$GLOBALS['wp_roles']->reinit();
		}
	}

	/**
	 * Load UltimateMember-specific test cases and framework.
	 *
	 * @since 1.3.84
	 */
	public function includes() {
		// framework
		//require_once( $this->includes_dir . '/factories/class-UltimateMember-factory.php' );
		//require_once( $this->includes_dir . '/class-UltimateMember-base-test.php' );
	}

	/**
	 * Get the single class instance.
	 *
	 * @since 1.3.84
	 * @return UltimateMember_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
UltimateMember_Unit_Tests_Bootstrap::instance();
