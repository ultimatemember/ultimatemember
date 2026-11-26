<?php
/**
 * Change Password form (the "set new password" step of the reset flow) submit handler, 2.14.3:
 * nonce bound to the login (CSRF fix), POST data whitelist, `um_change_password_invalid_nonce_redirect_url`.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Password_ChangePasswordFormTest
 *
 * @group password
 */
class UM_Password_ChangePasswordFormTest extends UM_UnitTestCase {

	const OLD_PASSWORD = 'Old-Passw0rd!';
	const NEW_PASSWORD = 'N3w-Passw0rd!x';

	/**
	 * @var WP_User
	 */
	private $user;

	/**
	 * @var string Password reset key.
	 */
	private $rp_key;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'password-reset' );
		$this->create_core_page( 'login' );
		$_SERVER['REQUEST_URI'] = '/password-reset/?act=reset_password';

		$user_id      = self::factory()->user->create(
			array(
				'user_login' => 'resetter',
				'user_pass'  => self::OLD_PASSWORD,
			)
		);
		$this->user   = get_userdata( $user_id );
		$this->rp_key = get_password_reset_key( $this->user );

		// The reset link handler stores "login:key" in this cookie before the form is shown.
		$_COOKIE[ 'wp-resetpass-' . COOKIEHASH ] = 'resetter:' . $this->rp_key;
	}

	/**
	 * @param array $overrides POST fields to add or replace.
	 *
	 * @return array A valid Change Password form submission.
	 */
	private function change_post( array $overrides = array() ) {
		return array_merge(
			array(
				'_um_password_change'   => '1',
				'_wpnonce'              => wp_create_nonce( 'um_password_change' . 'resetter' ),
				'login'                 => 'resetter',
				'rp_key'                => $this->rp_key,
				'user_password'         => self::NEW_PASSWORD,
				'confirm_user_password' => self::NEW_PASSWORD,
				UM()->honeypot          => '',
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
		UM()->password()->handle_change_password_form();
	}

	/**
	 * @param string $password Password to check.
	 *
	 * @return bool
	 */
	private function password_is( $password ) {
		$user = get_userdata( $this->user->ID );
		return wp_check_password( $password, $user->user_pass, $user->ID );
	}

	public function test_ignores_requests_without_the_form_flag() {
		$before = did_action( 'um_change_password_errors_hook' );

		$this->submit( array( 'login' => 'resetter', 'user_password' => self::NEW_PASSWORD ) );

		$this->assertSame( $before, did_action( 'um_change_password_errors_hook' ) );
		$this->assertTrue( $this->password_is( self::OLD_PASSWORD ) );
	}

	public function test_ignores_requests_without_login() {
		$before = did_action( 'um_change_password_errors_hook' );

		$this->submit( $this->change_post( array( 'login' => '' ) ) );

		$this->assertSame( $before, did_action( 'um_change_password_errors_hook' ) );
		$this->assertTrue( $this->password_is( self::OLD_PASSWORD ) );
	}

	/**
	 * The Account > Password tab posts `_um_account`, not `_um_password_change`; it is validated
	 * in `um_submit_account_errors_hook` now and must not run through this handler.
	 */
	public function test_account_password_tab_submission_is_not_handled_here() {
		$before = did_action( 'um_change_password_errors_hook' );

		$this->submit(
			array(
				'_um_account'           => '1',
				'_um_account_tab'       => 'password',
				'user_password'         => self::NEW_PASSWORD,
				'confirm_user_password' => self::NEW_PASSWORD,
			)
		);

		$this->assertSame( $before, did_action( 'um_change_password_errors_hook' ) );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_nonces() {
		return array(
			'nonce missing'                 => array( null ),
			'garbage nonce'                 => array( 'abc123' ),
			'nonce for another login'       => array( 'um_password_changeadmin' ),
			'nonce of the Reset form'       => array( 'um_password_reset_form' ),
			'nonce action without login'    => array( 'um_password_change' ),
		);
	}

	/**
	 * CSRF: a forged POST must not change the password, even with the victim's reset cookie present.
	 *
	 * @group security
	 * @dataProvider data_invalid_nonces
	 *
	 * @param string|null $nonce_action Action to create the nonce for, a raw value, or null to omit it.
	 */
	public function test_invalid_nonce_redirects_without_changing_password( $nonce_action ) {
		$post = $this->change_post();
		if ( null === $nonce_action ) {
			unset( $post['_wpnonce'] );
		} elseif ( 0 === strpos( $nonce_action, 'um_' ) ) {
			$post['_wpnonce'] = wp_create_nonce( $nonce_action );
		} else {
			$post['_wpnonce'] = $nonce_action;
		}
		$before = did_action( 'um_change_password_errors_hook' );

		$location = $this->capture_redirect(
			function () use ( $post ) {
				$this->submit( $post );
			}
		);

		$this->assertSame( '/password-reset/?act=reset_password&err=invalid_nonce', $location );
		$this->assertSame( $before, did_action( 'um_change_password_errors_hook' ) );
		$this->assertTrue( $this->password_is( self::OLD_PASSWORD ) );
	}

	public function test_invalid_nonce_redirect_url_is_filterable() {
		$custom = home_url( '/custom-error/' );
		add_filter(
			'um_change_password_invalid_nonce_redirect_url',
			static function () use ( $custom ) {
				return $custom;
			}
		);

		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->change_post( array( '_wpnonce' => 'invalid' ) ) );
			}
		);

		$this->assertSame( $custom, $location );
	}

	public function test_valid_submission_changes_password_and_redirects_to_login() {
		update_user_meta( $this->user->ID, 'password_rst_attempts', 2 );
		update_user_meta( $this->user->ID, 'um_set_password_required', true );
		$before = did_action( 'um_after_changing_user_password' );

		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->change_post() );
			}
		);

		$this->assertSame( um_get_core_page( 'login', 'password_changed' ), $location );
		$this->assertTrue( $this->password_is( self::NEW_PASSWORD ) );
		$this->assertSame( '0', get_user_meta( $this->user->ID, 'password_rst_attempts', true ) );
		$this->assertSame( '', get_user_meta( $this->user->ID, 'um_set_password_required', true ) );
		$this->assertSame( $before + 1, did_action( 'um_after_changing_user_password' ) );
	}

	/**
	 * @return array[]
	 */
	public function data_validation_errors() {
		return array(
			'empty password'        => array( array( 'user_password' => '' ), 'user_password' ),
			'empty confirmation'    => array( array( 'confirm_user_password' => '' ), 'confirm_user_password' ),
			'confirmation mismatch' => array( array( 'confirm_user_password' => 'Something-Else1' ), 'confirm_user_password' ),
			'backslash in password' => array(
				array(
					'user_password'         => 'Pass\\word1',
					'confirm_user_password' => 'Pass\\word1',
				),
				'user_password',
			),
		);
	}

	/**
	 * @dataProvider data_validation_errors
	 *
	 * @param array  $overrides   POST overrides.
	 * @param string $error_field Field expected to carry the error.
	 */
	public function test_validation_errors_keep_the_old_password( array $overrides, $error_field ) {
		$this->submit( $this->change_post( $overrides ) );

		$this->assertTrue( UM()->form()->has_error( $error_field ), "Expected an error on {$error_field}." );
		$this->assertCount( 1, UM()->form()->errors );
		$this->assertTrue( $this->password_is( self::OLD_PASSWORD ) );
	}

	/**
	 * The POSTed rp_key must match the key in the reset cookie.
	 *
	 * @group security
	 */
	public function test_mismatched_reset_key_redirects_to_invalid_key() {
		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->change_post( array( 'rp_key' => 'not-the-key' ) ) );
			}
		);

		$this->assertSame( add_query_arg( array( 'updated' => 'invalidkey' ), um_get_core_page( 'password-reset' ) ), $location );
		$this->assertTrue( $this->password_is( self::OLD_PASSWORD ) );
	}

	/**
	 * @group security
	 */
	public function test_missing_reset_cookie_redirects_to_invalid_key() {
		$_COOKIE = array();

		$location = $this->capture_redirect(
			function () {
				$this->submit( $this->change_post() );
			}
		);

		$this->assertSame( add_query_arg( array( 'updated' => 'invalidkey' ), um_get_core_page( 'password-reset' ) ), $location );
		$this->assertTrue( $this->password_is( self::OLD_PASSWORD ) );
	}

	/**
	 * Only the change-password fields reach `um_change_password_errors_hook` / `_process_hook`.
	 *
	 * @group security
	 */
	public function test_submitted_data_is_limited_to_expected_fields() {
		$received = null;
		add_action(
			'um_change_password_errors_hook',
			static function ( $data ) use ( &$received ) {
				$received = $data;
			},
			1
		);

		$this->capture_redirect(
			function () {
				$this->submit(
					$this->change_post(
						array(
							'user_id'  => '1',
							'role'     => 'administrator',
							'_um_role' => 'administrator',
						)
					)
				);
			}
		);

		$expected = array( UM()->honeypot, 'user_password', 'confirm_user_password', 'login', 'rp_key', 'mode', 'form_id' );
		sort( $expected );
		$keys = array_keys( $received );
		sort( $keys );

		$this->assertSame( $expected, $keys );
		$this->assertSame( 'password', $received['mode'] );
		$this->assertSame( 'um_password_id', $received['form_id'] );
	}
}
