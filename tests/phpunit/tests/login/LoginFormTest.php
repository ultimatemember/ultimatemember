<?php
/**
 * Login form ([ultimatemember form_id="…"] in "login" mode): authentication, account status checks,
 * nonce, honeypot and the redirect after login.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Login_LoginFormTest
 *
 * @group login
 */
class UM_Login_LoginFormTest extends UM_UnitTestCase {

	const PASSWORD = 'Corr3ct-Horse!';

	/**
	 * @var int
	 */
	private $form_id;

	/**
	 * @var int
	 */
	private $user_id;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'user' );
		$this->create_core_page( 'login' );
		$_SERVER['REQUEST_URI'] = '/login/';

		$this->form_id = $this->create_um_form( 'login' );
		$this->user_id = self::factory()->user->create(
			array(
				'user_login' => 'member',
				'user_email' => 'member@example.org',
				'user_pass'  => self::PASSWORD,
				'role'       => 'subscriber',
			)
		);
		UM()->common()->users()->set_status( $this->user_id, 'approved' );
	}

	/**
	 * @param array $fields Field values to add or replace.
	 * @param array $extra  Raw POST keys to add or replace.
	 *
	 * @return string Redirect location.
	 */
	private function login( array $fields = array(), array $extra = array() ) {
		return $this->capture_redirect(
			function () use ( $fields, $extra ) {
				$this->submit_um_form(
					$this->form_id,
					array_merge(
						array(
							'username'      => 'member',
							'user_password' => self::PASSWORD,
						),
						$fields
					),
					array_merge( array( '_wpnonce' => wp_create_nonce( 'um_login_form' . $this->form_id ) ), $extra )
				);
			}
		);
	}

	/**
	 * Submit the form when no redirect is expected (validation errors).
	 *
	 * @param array $fields Field values to add or replace.
	 */
	private function login_with_errors( array $fields ) {
		$this->submit_um_form(
			$this->form_id,
			array_merge(
				array(
					'username'      => 'member',
					'user_password' => self::PASSWORD,
				),
				$fields
			),
			array( '_wpnonce' => wp_create_nonce( 'um_login_form' . $this->form_id ) )
		);
	}

	public function test_valid_credentials_log_in_and_redirect_to_profile() {
		$location = $this->login();

		$this->assertSame( $this->user_id, get_current_user_id() );
		$this->assertNull( UM()->form()->errors );
		$this->assertSame( um_user_profile_url( $this->user_id ), $location );
		$this->assertNotEmpty( get_user_meta( $this->user_id, '_um_last_login', true ), 'Last login must be stored.' );
	}

	public function test_login_by_email() {
		$this->login( array( 'username' => 'member@example.org' ) );

		$this->assertSame( $this->user_id, get_current_user_id() );
	}

	public function test_successful_login_resets_password_reset_attempts() {
		update_user_meta( $this->user_id, 'password_rst_attempts', 2 );

		$this->login();

		$this->assertSame( '0', get_user_meta( $this->user_id, 'password_rst_attempts', true ) );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_credentials() {
		return array(
			'wrong password'    => array( array( 'user_password' => 'wrong-password' ), 'user_password' ),
			'empty password'    => array( array( 'user_password' => '' ), 'user_password' ),
			'unknown username'  => array( array( 'username' => 'nobody' ), 'user_password' ),
			'unknown email'     => array( array( 'username' => 'nobody@example.org' ), 'user_password' ),
			'empty username'    => array( array( 'username' => '' ), 'username' ),
			'password of other' => array( array( 'username' => 'admin' ), 'user_password' ),
		);
	}

	/**
	 * @dataProvider data_invalid_credentials
	 *
	 * @param array  $fields      Submitted fields.
	 * @param string $error_field Field that must get the error.
	 */
	public function test_invalid_credentials_show_error_and_do_not_log_in( array $fields, $error_field ) {
		$failed = did_action( 'wp_login_failed' );

		$this->login_with_errors( $fields );

		$this->assertTrue( UM()->form()->has_error( $error_field ) );
		$this->assertSame( 0, get_current_user_id() );
		$this->assertSame( $failed + 1, did_action( 'wp_login_failed' ), 'Failed logins must be reported to WordPress (brute-force protection plugins).' );
	}

	/**
	 * A wrong password must not reveal whether the username exists.
	 *
	 * @group security
	 */
	public function test_error_message_is_the_same_for_unknown_user_and_wrong_password() {
		$this->login_with_errors( array( 'user_password' => 'wrong-password' ) );
		$wrong_password = UM()->form()->errors;

		$this->reset_request_state();
		$this->login_with_errors( array( 'username' => 'nobody' ) );

		$this->assertSame( $wrong_password, UM()->form()->errors );
	}

	/**
	 * Authentication errors from other plugins (`authenticate` filter, e.g. a 2FA or lockout plugin)
	 * block the UM login too.
	 *
	 * @group security
	 */
	public function test_authenticate_filter_can_block_the_login() {
		add_filter(
			'authenticate',
			static function () {
				return new WP_Error( 'too_many_retries', 'Locked out.' );
			},
			100
		);

		$this->login_with_errors( array() );

		$this->assertTrue( UM()->form()->has_error( 'user_password' ) );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * @return array[]
	 */
	public function data_blocked_statuses() {
		return array(
			'inactive'                    => array( 'inactive' ),
			'awaiting admin review'       => array( 'awaiting_admin_review' ),
			'awaiting email confirmation' => array( 'awaiting_email_confirmation' ),
			'rejected'                    => array( 'rejected' ),
		);
	}

	/**
	 * Users whose account isn't approved can't log in, even with the right password.
	 *
	 * @group security
	 * @dataProvider data_blocked_statuses
	 *
	 * @param string $status Account status.
	 */
	public function test_not_approved_account_cannot_log_in( $status ) {
		UM()->common()->users()->set_status( $this->user_id, $status );

		$location = $this->login();

		$this->assertSame( 0, get_current_user_id() );
		$this->assertStringContainsString( 'err=' . $status, $location );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_nonces() {
		return array(
			'nonce missing'               => array( '' ),
			'garbage nonce'               => array( 'abc123' ),
			'nonce of the register form'  => array( 'um_register_form' ),
			'nonce of another login form' => array( 'other_form' ),
		);
	}

	/**
	 * CSRF: a forged cross-site POST must not log the visitor in.
	 *
	 * @group security
	 * @dataProvider data_invalid_nonces
	 *
	 * @param string $nonce_action Action to create the nonce for, or a raw value.
	 */
	public function test_invalid_nonce_redirects_without_login( $nonce_action ) {
		if ( 'um_register_form' === $nonce_action ) {
			$nonce = wp_create_nonce( 'um_register_form' . $this->form_id );
		} elseif ( 'other_form' === $nonce_action ) {
			$nonce = wp_create_nonce( 'um_login_form' . ( $this->form_id + 1 ) );
		} else {
			$nonce = $nonce_action;
		}

		$location = $this->login( array(), array( '_wpnonce' => $nonce ) );

		$this->assertStringContainsString( 'err=invalid_nonce', $location );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * A filled honeypot field stops the request before authentication.
	 *
	 * @group security
	 */
	public function test_filled_honeypot_stops_the_request() {
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Hello, spam bot!' );

		$this->login( array(), array( UM()->honeypot => 'i-am-a-bot' ) );
	}

	public function test_redirect_to_is_used_after_login() {
		$target = home_url( '/members-area/' );

		$location = $this->login( array(), array( 'redirect_to' => $target ) );

		$this->assertSame( $target, $location );
	}

	/**
	 * `redirect_to` goes through `um_safe_redirect()`: no open redirect to another host.
	 *
	 * @group security
	 */
	public function test_redirect_to_cannot_point_off_site() {
		$location = $this->login( array(), array( 'redirect_to' => 'https://attacker.invalid/phish' ) );

		$this->assertStringNotContainsString( 'attacker.invalid', $location );
		$this->assertSame( $this->user_id, get_current_user_id() );
	}

	/**
	 * @return array[]
	 */
	public function data_after_login_actions() {
		return array(
			'redirect to profile' => array( 'redirect_profile', 'profile' ),
			'redirect to admin'   => array( 'redirect_admin', 'admin' ),
			'redirect to URL'     => array( 'redirect_url', 'url' ),
			'refresh'             => array( 'refresh', 'current' ),
		);
	}

	/**
	 * The role setting "Action to be taken after login" decides where the user goes.
	 *
	 * @dataProvider data_after_login_actions
	 *
	 * @param string $action   `_um_after_login` role setting.
	 * @param string $expected Expected target.
	 */
	public function test_after_login_role_setting( $action, $expected ) {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_after_login'        => $action,
				'_um_login_redirect_url' => home_url( '/welcome/' ),
			)
		);

		$location = $this->login();

		$targets = array(
			'profile' => um_user_profile_url( $this->user_id ),
			'admin'   => admin_url(),
			'url'     => home_url( '/welcome/' ),
			'current' => UM()->permalinks()->get_current_url(),
		);
		$this->assertSame( $targets[ $expected ], $location );
	}

	public function test_login_redirect_url_is_filterable() {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_after_login'        => 'redirect_url',
				'_um_login_redirect_url' => home_url( '/welcome/' ),
			)
		);
		add_filter(
			'um_login_redirect_url',
			static function () {
				return home_url( '/dashboard/' );
			}
		);

		$this->assertSame( home_url( '/dashboard/' ), $this->login() );
	}

	/**
	 * With "Disable admin login via frontend" enabled, administrators must use wp-login.php.
	 *
	 * @group security
	 */
	public function test_admin_frontend_login_can_be_denied() {
		UM()->options()->update( 'deny_admin_frontend_login', true );
		$admin_id = self::factory()->user->create(
			array(
				'user_login' => 'boss',
				'user_pass'  => self::PASSWORD,
				'role'       => 'administrator',
			)
		);
		UM()->common()->users()->set_status( $admin_id, 'approved' );

		try {
			$this->login( array( 'username' => 'boss' ) );
			$this->fail( 'wp_die() was expected.' );
		} catch ( WPDieException $e ) {
			$this->assertSame( 'This action has been prevented for security measures.', $e->getMessage() );
		}

		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * The login form has no role field; a submitted `role` is dropped and doesn't change the user.
	 *
	 * @group security
	 */
	public function test_submitted_role_is_ignored() {
		$this->login( array(), array( 'role' => 'administrator' ) );

		$this->assertArrayNotHasKey( 'role', UM()->form()->post_form );
		$this->assertSame( array( 'subscriber' ), get_userdata( $this->user_id )->roles );
	}

	public function test_rendered_form_contains_a_valid_nonce_and_fields() {
		$html = do_shortcode( '[ultimatemember form_id="' . $this->form_id . '"]' );

		$this->assertSame( 1, preg_match( '/name="_wpnonce" value="([^"]+)"/', $html, $m ), 'Nonce field missing in the login form.' );
		$this->assertSame( 1, wp_verify_nonce( $m[1], 'um_login_form' . $this->form_id ) );
		$this->assertStringContainsString( 'name="username-' . $this->form_id . '"', $html );
		$this->assertStringContainsString( 'name="user_password-' . $this->form_id . '"', $html );
		$this->assertMatchesRegularExpression( '/name="form_id"[^>]* value="' . $this->form_id . '"/', $html );
	}
}
