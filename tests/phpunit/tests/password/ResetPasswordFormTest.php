<?php
/**
 * Password Reset form ("Forgot password", [ultimatemember_password]) submit handler, 2.14.3:
 * nonce verification (CSRF fix), POST data whitelist, `um_reset_password_invalid_nonce_redirect_url`.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Password_ResetPasswordFormTest
 *
 * @group password
 */
class UM_Password_ResetPasswordFormTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $user_id;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'password-reset' );
		$_SERVER['REQUEST_URI'] = '/password-reset/';

		$this->user_id = self::factory()->user->create(
			array(
				'user_login' => 'forgetful',
				'user_email' => 'forgetful@example.org',
			)
		);
		UM()->common()->users()->set_status( $this->user_id, 'approved' );

		reset_phpmailer_instance();
	}

	/**
	 * @param array $overrides POST fields to add or replace.
	 *
	 * @return array A valid Password Reset form submission.
	 */
	private function reset_post( array $overrides = array() ) {
		return array_merge(
			array(
				'_um_password_reset' => '1',
				'_wpnonce'           => wp_create_nonce( 'um_password_reset_form' ),
				'_wp_http_referer'   => '/password-reset/',
				'username_b'         => 'forgetful',
				UM()->honeypot       => '',
			),
			$overrides
		);
	}

	/**
	 * @param array $post Submitted fields.
	 */
	private function submit( array $post ) {
		$_POST    = wp_slash( $post ); // WordPress adds slashes to request data (wp_magic_quotes).
		$_REQUEST = $_POST;
		UM()->password()->handle_reset_password_form();
	}

	public function test_ignores_requests_without_the_form_flag() {
		$before = did_action( 'um_reset_password_errors_hook' );

		$this->submit( array( 'username_b' => 'forgetful' ) );

		$this->assertSame( $before, did_action( 'um_reset_password_errors_hook' ) );
		$this->assertNull( UM()->form()->post_form );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_nonces() {
		return array(
			'nonce missing'                 => array( null ),
			'garbage nonce'                 => array( 'abc123' ),
			'nonce of Change Password form' => array( 'um_password_changeforgetful' ),
			'nonce of another action'       => array( 'um_password_reset' ),
		);
	}

	/**
	 * CSRF: a forged cross-site POST must not trigger the reset email nor consume reset attempts.
	 *
	 * @group security
	 * @dataProvider data_invalid_nonces
	 *
	 * @param string|null $nonce_action Action to create the nonce for, a raw value, or null to omit it.
	 */
	public function test_invalid_nonce_redirects_without_processing( $nonce_action ) {
		$post = $this->reset_post();
		if ( null === $nonce_action ) {
			unset( $post['_wpnonce'] );
		} elseif ( 0 === strpos( $nonce_action, 'um_' ) ) {
			$post['_wpnonce'] = wp_create_nonce( $nonce_action );
		} else {
			$post['_wpnonce'] = $nonce_action;
		}
		$before = did_action( 'um_reset_password_errors_hook' );

		$location = $this->capture_redirect(
			function () use ( $post ) {
				$this->submit( $post );
			}
		);

		$this->assertSame( '/password-reset/?err=invalid_nonce', $location );
		$this->assertSame( $before, did_action( 'um_reset_password_errors_hook' ) );
		$this->assertSame( '', get_user_meta( $this->user_id, 'password_rst_attempts', true ) );
		$this->assertEmpty( tests_retrieve_phpmailer_instance()->get_sent() );
	}

	public function test_invalid_nonce_redirect_url_is_filterable() {
		$custom = home_url( '/custom-error/' );
		add_filter(
			'um_reset_password_invalid_nonce_redirect_url',
			static function () use ( $custom ) {
				return $custom;
			}
		);

		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->reset_post( array( '_wpnonce' => 'invalid' ) ) );
			}
		);

		$this->assertSame( $custom, $location );
	}

	/**
	 * The filtered URL still goes through `um_safe_redirect()`, so it can't become an open redirect.
	 *
	 * @group security
	 */
	public function test_invalid_nonce_redirect_url_cannot_point_off_site() {
		add_filter(
			'um_reset_password_invalid_nonce_redirect_url',
			static function () {
				return 'https://attacker.invalid/phish';
			}
		);

		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->reset_post( array( '_wpnonce' => 'invalid' ) ) );
			}
		);

		$this->assertStringNotContainsString( 'attacker.invalid', $location );
	}

	public function test_valid_request_sends_reset_email_and_redirects_to_check_email() {
		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->reset_post() );
			}
		);

		$this->assertSame( um_get_core_page( 'password-reset', 'checkemail' ), $location );
		$this->assertNull( UM()->form()->errors );
		$this->assertSame( '1', get_user_meta( $this->user_id, 'password_rst_attempts', true ) );
		$this->assertNotEmpty( get_userdata( $this->user_id )->user_activation_key, 'A reset key must be generated.' );
	}

	public function test_request_by_email_is_accepted() {
		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->reset_post( array( 'username_b' => 'forgetful@example.org' ) ) );
			}
		);

		$this->assertSame( um_get_core_page( 'password-reset', 'checkemail' ), $location );
		$this->assertSame( '1', get_user_meta( $this->user_id, 'password_rst_attempts', true ) );
	}

	/**
	 * Unknown users get the same "check your email" answer (no user enumeration).
	 */
	public function test_unknown_user_gets_the_same_redirect() {
		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->reset_post( array( 'username_b' => 'nobody-here' ) ) );
			}
		);

		$this->assertSame( um_get_core_page( 'password-reset', 'checkemail' ), $location );
	}

	public function test_empty_username_shows_field_error() {
		$this->submit( $this->reset_post( array( 'username_b' => '' ) ) );

		$this->assertTrue( UM()->form()->has_error( 'username_b' ) );
	}

	public function test_reset_attempts_limit_blocks_the_request() {
		UM()->options()->update( 'enable_reset_password_limit', true );
		UM()->options()->update( 'reset_password_limit_number', 3 );
		update_user_meta( $this->user_id, 'password_rst_attempts', 3 );

		$this->submit( $this->reset_post() );

		$this->assertTrue( UM()->form()->has_error( 'username_b' ) );
		$this->assertSame( '3', get_user_meta( $this->user_id, 'password_rst_attempts', true ) );
	}

	/**
	 * Only the reset form fields reach the `um_reset_password_errors_hook` / `_process_hook` callbacks.
	 *
	 * @group security
	 */
	public function test_submitted_data_is_limited_to_expected_fields() {
		$received = null;
		add_action(
			'um_reset_password_errors_hook',
			static function ( $data ) use ( &$received ) {
				$received = $data;
			},
			1
		);

		$this->capture_redirect(
			function () {
				$this->submit(
					$this->reset_post(
						array(
							'role'          => 'administrator',
							'user_password' => 'injected',
							'form_id'       => '123',
						)
					)
				);
			}
		);

		$expected_keys = array( UM()->honeypot, 'username_b', 'mode' );
		sort( $expected_keys );
		$keys = array_keys( $received );
		sort( $keys );

		$this->assertSame( $expected_keys, $keys );
		$this->assertSame( 'password', $received['mode'] );
		$this->assertSame( 'forgetful', $received['username_b'] );
	}

	public function test_form_data_mode_is_always_password() {
		$received = null;
		add_action(
			'um_reset_password_errors_hook',
			static function ( $data, $form_data ) use ( &$received ) {
				$received = $form_data;
			},
			1,
			2
		);

		$this->capture_redirect(
			function () {
				$this->submit( $this->reset_post( array( 'mode' => 'profile' ) ) );
			}
		);

		$this->assertSame( array( 'mode' => 'password' ), $received );
	}

	/**
	 * The Password Reset form rendered by the shortcode posts back a valid nonce.
	 */
	public function test_rendered_form_contains_a_valid_nonce() {
		$html = UM()->password()->ultimatemember_password();

		$this->assertSame( 1, preg_match( '/name="_wpnonce" value="([^"]+)"/', $html, $m ), 'Nonce field missing in password-reset.php' );
		$this->assertSame( 1, wp_verify_nonce( $m[1], 'um_password_reset_form' ) );
		$this->assertStringContainsString( 'name="_um_password_reset"', $html );
	}

	/**
	 * When another UM form was rendered earlier on the same page (e.g. a login form in the header),
	 * `UM()->form()->form_suffix` is "-{id}" and the reset field is printed as `username_b-{id}`.
	 * Up to 2.14.2 the handler matched the key with `strstr()`; the 2.14.3 whitelist keeps only
	 * `username_b`, so such a submission now fails with "Please provide your username or email".
	 *
	 * @group known-bug
	 */
	public function test_username_field_with_form_suffix_is_accepted() {
		UM()->form()->form_suffix = '-123';
		$html                     = UM()->password()->ultimatemember_password();
		$this->assertStringContainsString( 'name="username_b-123"', $html, 'Precondition: field name carries the suffix.' );

		$post = $this->reset_post();
		unset( $post['username_b'] );
		$post['username_b-123'] = 'forgetful';

		UM()->form()->form_suffix = null;
		$location                 = $this->capture_redirect(
			function () use ( $post ) {
				$this->submit( $post );
			}
		);

		$this->assertSame( um_get_core_page( 'password-reset', 'checkemail' ), $location );
	}
}
