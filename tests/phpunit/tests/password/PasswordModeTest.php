<?php
/**
 * Password reset page bootstrap, 2.14.3: `form_init()` was split into `set_password_mode()` (key/cookie
 * validation) and the two form handlers; `is_reset_request()` / `is_change_request()` are deprecated.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Password_PasswordModeTest
 *
 * @group password
 */
class UM_Password_PasswordModeTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $page_id;

	/**
	 * @var WP_User
	 */
	private $user;

	public function set_up() {
		parent::set_up();

		$this->page_id = $this->create_core_page( 'password-reset' );
		$user_id       = self::factory()->user->create( array( 'user_login' => 'resetter' ) );
		$this->user    = get_userdata( $user_id );
	}

	/**
	 * Open the password reset page as the main query, the way `template_redirect` sees it.
	 *
	 * @param array $query Query args, e.g. `act`.
	 */
	private function visit_reset_page( array $query = array() ) {
		$this->go_to( get_permalink( $this->page_id ) );

		$_GET                   = $query;
		$_REQUEST               = $query;
		$_SERVER['REQUEST_URI'] = add_query_arg( $query, '/password-reset/' );
	}

	/**
	 * @return string Reset key cookie name.
	 */
	private function cookie_name() {
		return 'wp-resetpass-' . COOKIEHASH;
	}

	/**
	 * @return string URL for an invalid / expired key.
	 */
	private function key_error_url( $code ) {
		return add_query_arg( array( 'updated' => $code ), um_get_core_page( 'password-reset' ) );
	}

	public function test_template_redirect_hooks() {
		$password = UM()->password();

		$this->assertSame( 10001, has_action( 'template_redirect', array( $password, 'set_password_mode' ) ) );
		$this->assertSame( 10002, has_action( 'template_redirect', array( $password, 'handle_reset_password_form' ) ) );
		$this->assertSame( 10002, has_action( 'template_redirect', array( $password, 'handle_change_password_form' ) ) );
		$this->assertFalse( method_exists( $password, 'form_init' ), 'form_init() was removed in 2.14.3.' );
	}

	public function test_does_nothing_outside_the_password_reset_page() {
		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page' ) ) ) );
		$_REQUEST['act'] = 'reset_password';

		UM()->password()->set_password_mode();

		$this->assertNull( UM()->fields()->set_mode );
	}

	public function test_sets_password_mode_on_the_password_reset_page() {
		$this->visit_reset_page();

		UM()->password()->set_password_mode();

		$this->assertSame( 'password', UM()->fields()->set_mode );
	}

	/**
	 * Email link `?act=reset_password&hash=...&login=...`: the key moves to a cookie, the URL is cleaned.
	 */
	public function test_reset_link_moves_key_to_cookie_and_cleans_the_url() {
		$this->visit_reset_page(
			array(
				'act'   => 'reset_password',
				'hash'  => 'abc',
				'login' => 'resetter',
			)
		);

		$location = $this->capture_redirect(
			static function () {
				UM()->password()->set_password_mode();
			}
		);

		$this->assertSame( '/password-reset/?act=reset_password', $location );
	}

	/**
	 * @group security
	 */
	public function test_missing_cookie_redirects_to_invalid_key() {
		$this->visit_reset_page( array( 'act' => 'reset_password' ) );

		$location = $this->capture_redirect(
			static function () {
				UM()->password()->set_password_mode();
			}
		);

		$this->assertSame( $this->key_error_url( 'invalidkey' ), $location );
	}

	/**
	 * @group security
	 */
	public function test_wrong_key_redirects_to_invalid_key() {
		get_password_reset_key( $this->user );
		$this->visit_reset_page( array( 'act' => 'reset_password' ) );
		$_COOKIE[ $this->cookie_name() ] = 'resetter:wrong-key';

		$location = $this->capture_redirect(
			static function () {
				UM()->password()->set_password_mode();
			}
		);

		$this->assertSame( $this->key_error_url( 'invalidkey' ), $location );
	}

	public function test_expired_key_redirects_to_expired_key() {
		$key = get_password_reset_key( $this->user );
		add_filter( 'password_reset_expiration', '__return_zero' );
		$this->visit_reset_page( array( 'act' => 'reset_password' ) );
		$_COOKIE[ $this->cookie_name() ] = 'resetter:' . $key;

		$location = $this->capture_redirect(
			static function () {
				UM()->password()->set_password_mode();
			}
		);

		$this->assertSame( $this->key_error_url( 'expiredkey' ), $location );
	}

	/**
	 * @group security
	 */
	public function test_posted_key_must_match_cookie_key() {
		$key = get_password_reset_key( $this->user );
		$this->visit_reset_page( array( 'act' => 'reset_password' ) );
		$_COOKIE[ $this->cookie_name() ] = 'resetter:' . $key;
		$_POST                           = array(
			'user_password' => 'x',
			'rp_key'        => 'another-key',
		);

		$location = $this->capture_redirect(
			static function () {
				UM()->password()->set_password_mode();
			}
		);

		$this->assertSame( $this->key_error_url( 'invalidkey' ), $location );
	}

	/**
	 * Valid key: no redirect, and the shortcode shows the Change Password form with a nonce bound to the login.
	 */
	public function test_valid_key_shows_change_password_form_with_login_bound_nonce() {
		$key = get_password_reset_key( $this->user );
		$this->visit_reset_page( array( 'act' => 'reset_password' ) );
		$_COOKIE[ $this->cookie_name() ] = 'resetter:' . $key;

		UM()->password()->set_password_mode();
		$html = UM()->password()->ultimatemember_password();

		$this->assertStringContainsString( 'name="_um_password_change"', $html );
		$this->assertStringContainsString( 'name="login" value="resetter"', $html );
		$this->assertStringContainsString( 'name="rp_key" value="' . esc_attr( $key ) . '"', $html );

		$this->assertSame( 1, preg_match( '/name="_wpnonce" value="([^"]+)"/', $html, $m ), 'Nonce field missing in password-change.php' );
		$this->assertSame( 1, wp_verify_nonce( $m[1], 'um_password_change' . 'resetter' ) );
		$this->assertFalse( wp_verify_nonce( $m[1], 'um_password_change' . 'someone-else' ) );
	}

	/**
	 * @return array[]
	 */
	public function data_deprecated_request_checks() {
		return array(
			'reset request'         => array( 'is_reset_request', array( '_um_password_reset' => '1' ), true ),
			'not a reset request'   => array( 'is_reset_request', array(), false ),
			'change request'        => array( 'is_change_request', array( '_um_password_change' => '1' ), true ),
			'account password tab'  => array(
				'is_change_request',
				array(
					'_um_account'     => '1',
					'_um_account_tab' => 'password',
				),
				true,
			),
			'account other tab'     => array(
				'is_change_request',
				array(
					'_um_account'     => '1',
					'_um_account_tab' => 'general',
				),
				false,
			),
		);
	}

	/**
	 * Deprecated in 2.14.3 but kept for extensions: they still answer and emit a deprecation notice.
	 *
	 * @dataProvider data_deprecated_request_checks
	 *
	 * @param string $method   Method name.
	 * @param array  $post     $_POST.
	 * @param bool   $expected Expected result.
	 */
	public function test_deprecated_request_checks_still_work( $method, array $post, $expected ) {
		$this->setExpectedDeprecated( 'um\core\Password::' . $method );
		$_POST = $post;

		$this->assertSame( $expected, UM()->password()->$method() );
	}
}
