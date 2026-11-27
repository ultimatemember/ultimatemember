<?php
/**
 * Base test case for Ultimate Member.
 *
 * @package UM\Tests
 */

/**
 * Class UM_UnitTestCase
 *
 * Adds helpers for UM roles and for acting as a given user. Every test runs inside a DB
 * transaction (WP_UnitTestCase), so options and users created here are rolled back.
 */
abstract class UM_UnitTestCase extends WP_UnitTestCase {

	/**
	 * UM core page IDs as they were before the test (`UM()->config()->permalinks`).
	 *
	 * @var array
	 */
	private $um_core_pages_backup = array();

	/**
	 * Reset in-memory state that survives the DB rollback between tests.
	 */
	public function set_up() {
		parent::set_up();

		$this->um_core_pages_backup = (array) UM()->config()->permalinks;

		// `UM()->options()->update()` changes the in-memory copy too, which the DB rollback doesn't
		// restore. Re-read the settings so every test starts from the baseline written by bootstrap.
		UM()->classes['options'] = new um\core\Options();

		$this->reset_request_state();
		wp_set_current_user( 0 );
		$this->reload_roles();
	}

	/**
	 * Restore in-memory state changed by the test.
	 */
	public function tear_down() {
		UM()->config()->permalinks = $this->um_core_pages_backup;
		$this->reset_request_state();
		unset( $GLOBALS['post'] ); // Tests may set the current page, e.g. for um_is_predefined_page().

		parent::tear_down();
	}

	/**
	 * Clear the request superglobals and the UM form/field state that lives in singletons.
	 */
	protected function reset_request_state() {
		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		$_COOKIE  = array();
		unset( $_SERVER['REQUEST_METHOD'] ); // Set by submit_um_form().

		// Profile ID of the rendered form; UM()->fields()->edit_field() prefers it over um_user( 'ID' ).
		$GLOBALS['_um_profile_id'] = null;

		UM()->form()->errors      = null;
		UM()->form()->post_form   = null;
		UM()->form()->form_suffix = null;
		UM()->form()->preview     = false;
		UM()->user()->preview     = false;
		UM()->fields()->set_mode  = null;
		UM()->fields()->editing   = false;
		UM()->fields()->viewing   = false;

		// Set by set_password_mode() after a valid reset key; switches the shortcode to the Change Password form.
		$change_password = new ReflectionProperty( UM()->password(), 'change_password' );
		if ( PHP_VERSION_ID < 80100 ) {
			$change_password->setAccessible( true );
		}
		$change_password->setValue( UM()->password(), false );
	}

	/**
	 * Create a custom UM role the same way wp-admin > Ultimate Member > User Roles does:
	 * the role key goes to the `um_roles` option and the settings to `um_role_{key}_meta`.
	 *
	 * @param string $key     Role key without the `um_` prefix.
	 * @param array  $meta    UM role settings, e.g. `_um_can_edit_everyone`, `_um_priority`.
	 * @param array  $wp_caps WordPress capabilities of the role.
	 *
	 * @return string Role ID (`um_{key}`).
	 */
	protected function create_um_role( $key, array $meta = array(), array $wp_caps = array( 'read' => true ) ) {
		$role_keys   = get_option( 'um_roles', array() );
		$role_keys[] = $key;
		update_option( 'um_roles', array_values( array_unique( $role_keys ) ) );

		$defaults = array_merge(
			UM()->config()->default_roles_metadata['subscriber'],
			array(
				'name'            => ucwords( str_replace( array( '_', '-' ), ' ', $key ) ),
				'_um_is_custom'   => 1,
				'_um_priority'    => 0,
				'wp_capabilities' => $wp_caps,
			)
		);

		update_option( "um_role_{$key}_meta", array_merge( $defaults, $meta ) );
		$this->reload_roles();

		return 'um_' . $key;
	}

	/**
	 * Change UM settings of an existing role (built-in WP role or custom UM role).
	 *
	 * @param string $role_id Role ID, e.g. `subscriber` or `um_manager`.
	 * @param array  $meta    UM role settings to merge.
	 */
	protected function update_role_meta( $role_id, array $meta ) {
		$key  = 0 === strpos( $role_id, 'um_' ) ? substr( $role_id, 3 ) : $role_id;
		$data = get_option( "um_role_{$key}_meta", array() );

		update_option( "um_role_{$key}_meta", array_merge( (array) $data, $meta ) );
		$this->reload_roles();
	}

	/**
	 * Create a user with one or more roles.
	 *
	 * @param string|string[] $roles Role ID(s). The first one is the primary role.
	 *
	 * @return int User ID.
	 */
	protected function create_user_with_roles( $roles ) {
		$roles   = (array) $roles;
		$user_id = self::factory()->user->create( array( 'role' => array_shift( $roles ) ) );

		$user = get_userdata( $user_id );
		foreach ( $roles as $role ) {
			$user->add_role( $role );
		}

		UM()->user()->remove_cache( $user_id );

		return $user_id;
	}

	/**
	 * Log in as the given user, the way a request would: WP current user + UM current profile.
	 *
	 * @param int $user_id User ID, 0 for a logged-out visitor.
	 */
	protected function act_as( $user_id ) {
		wp_set_current_user( $user_id );
		UM()->user()->remove_cache( $user_id );
		um_reset_user();
	}

	/**
	 * Re-run `wp_roles_init` so UM roles from the options are (re)registered in `$wp_roles`,
	 * and drop UM user caches that hold role permissions.
	 */
	protected function reload_roles() {
		wp_roles()->for_site();
		UM()->user()->remove_cache_all_users();
		um_reset_user_clean();
	}

	/**
	 * Create a page and register it as a UM core page (e.g. `password-reset`, `login`, `account`):
	 * in `UM()->config()->permalinks` (restored in tear_down()) and in the predefined page setting.
	 *
	 * @param string $slug UM core page key.
	 *
	 * @return int Page ID.
	 */
	protected function create_core_page( $slug ) {
		$page_id = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => $slug,
				'post_title'  => ucwords( str_replace( '-', ' ', $slug ) ),
			)
		);

		UM()->config()->permalinks[ $slug ] = $page_id;
		// Predefined page setting used by um_is_predefined_page(); reset in set_up() of the next test.
		UM()->options()->update( UM()->options()->get_predefined_page_option_key( $slug ), $page_id );

		return $page_id;
	}

	/**
	 * Create a published UM form the way wp-admin > Ultimate Member > Forms does. The setup in
	 * bootstrap runs without a logged-in admin, so the default core forms are not installed;
	 * tests create their own forms from `UM()->config()->core_form_meta`.
	 *
	 * @param string $mode          `login`, `register` or `profile`.
	 * @param array  $meta          Form meta to add or replace, e.g. `_um_register_role`.
	 * @param array  $custom_fields Custom fields to add to (or replace in) the default fields of the mode.
	 *
	 * @return int Form ID.
	 */
	protected function create_um_form( $mode, array $meta = array(), array $custom_fields = array() ) {
		$defaults = UM()->config()->core_form_meta[ $mode ];
		unset( $defaults['_um_core'] ); // Not a core form: the core form IDs live in the `um_core_forms` option.

		$defaults['_um_custom_fields'] = array_merge( $defaults['_um_custom_fields'], $custom_fields );

		$form_id = self::factory()->post->create(
			array(
				'post_type'   => 'um_form',
				'post_status' => 'publish',
				'post_title'  => ucfirst( $mode ) . ' form',
			)
		);

		foreach ( array_merge( $defaults, $meta ) as $meta_key => $meta_value ) {
			update_post_meta( $form_id, $meta_key, $meta_value );
		}

		return $form_id;
	}

	/**
	 * Submit a UM Login, Registration or Profile form through `UM()->form()->form_init()`, the
	 * same way a front-end POST request does. Field names get the `-{form_id}` suffix the
	 * rendered form uses, and the data is slashed like WordPress request data.
	 *
	 * Use together with capture_redirect() when the submission is expected to redirect.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $fields  Field values by field key (without the form suffix).
	 * @param array $extra   Raw POST keys added as is, e.g. `_wpnonce`, `redirect_to`, `profile_nonce`.
	 */
	protected function submit_um_form( $form_id, array $fields, array $extra = array() ) {
		$post = array(
			'form_id'      => (string) $form_id,
			UM()->honeypot => '',
		);
		foreach ( $fields as $key => $value ) {
			$post[ $key . '-' . $form_id ] = $value;
		}
		$post = array_merge( $post, $extra );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = wp_slash( $post );
		$_REQUEST                  = $_POST;

		UM()->form()->form_init();
	}

	/**
	 * Run a UM AJAX handler and return its decoded JSON response.
	 *
	 * `wp_send_json_*()` ends with `wp_die()` during AJAX requests; the AJAX die handler throws
	 * here, so the test keeps running.
	 *
	 * @param callable $callback AJAX handler.
	 *
	 * @return array|int Decoded JSON response, or -1 when `check_ajax_referer()` failed.
	 */
	protected function call_ajax( callable $callback ) {
		$die_handler = static function () {
			return static function ( $message ) {
				throw new WPAjaxDieContinueException( is_scalar( $message ) ? (string) $message : '' );
			};
		};

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $die_handler, PHP_INT_MAX );

		$ob_level = ob_get_level();
		ob_start();
		$message = '';
		try {
			$callback();
		} catch ( WPAjaxDieContinueException $e ) {
			$message = $e->getMessage();
		} finally {
			$output = '';
			while ( ob_get_level() > $ob_level ) {
				$output = ob_get_clean() . $output;
			}
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', $die_handler, PHP_INT_MAX );
		}

		if ( '' === $output && '-1' === $message ) {
			return -1;
		}

		$this->assertJson( $output, 'The AJAX handler did not send JSON.' );
		return json_decode( $output, true );
	}

	/**
	 * Run code that is expected to redirect and return the redirect location.
	 *
	 * UM handlers call `exit` after `wp_safe_redirect()`. A `wp_redirect` filter throws before the
	 * header is sent, so the test keeps running. While the code runs, the CLI-only warning
	 * "Cannot modify header information" from PHP's `setcookie()` / `header()` is ignored.
	 * `UM()->password()->setcookie()` closes all output buffers; they are re-opened here so
	 * PHPUnit's own output buffer stays balanced.
	 *
	 * @param callable $callback Code under test.
	 *
	 * @return string Redirect location (after `wp_safe_redirect()` validation).
	 */
	protected function capture_redirect( callable $callback ) {
		$ob_level = ob_get_level();
		$catcher  = static function ( $location ) {
			throw new UM_Test_Redirect_Exception( $location );
		};

		$previous_handler = null;
		$previous_handler = set_error_handler(
			static function ( $errno, $errstr, $errfile = '', $errline = 0 ) use ( &$previous_handler ) {
				if ( E_WARNING === $errno && 0 === strpos( $errstr, 'Cannot modify header information' ) ) {
					return true;
				}
				return $previous_handler ? call_user_func( $previous_handler, $errno, $errstr, $errfile, $errline ) : false;
			}
		);

		add_filter( 'wp_redirect', $catcher, PHP_INT_MAX );
		try {
			$callback();
		} catch ( UM_Test_Redirect_Exception $e ) {
			return $e->location;
		} finally {
			remove_filter( 'wp_redirect', $catcher, PHP_INT_MAX );
			restore_error_handler();
			while ( ob_get_level() < $ob_level ) {
				ob_start();
			}
		}

		$this->fail( 'A redirect was expected, but the code returned normally.' );
		return '';
	}
}

/**
 * Thrown from the `wp_redirect` filter by UM_UnitTestCase::capture_redirect().
 */
class UM_Test_Redirect_Exception extends Exception {

	/**
	 * Redirect location.
	 *
	 * @var string
	 */
	public $location;

	/**
	 * @param string $location Redirect location.
	 */
	public function __construct( $location ) {
		parent::__construct( 'Redirect to ' . $location );
		$this->location = $location;
	}
}
