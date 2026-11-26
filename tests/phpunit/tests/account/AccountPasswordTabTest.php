<?php
/**
 * Account > Change Password tab validation (`um_submit_account_errors_hook`), 2.14.3:
 * confirmation errors are shown under `confirm_user_password`, and the password change
 * request limit is checked in the Account form handler.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Account_AccountPasswordTabTest
 *
 * @group account
 * @group password
 */
class UM_Account_AccountPasswordTabTest extends UM_UnitTestCase {

	const CURRENT_PASSWORD = 'Curr3nt-Passw0rd';
	const NEW_PASSWORD     = 'Brand-N3w-Passw0rd';

	/**
	 * @var int
	 */
	private $user_id;

	public function set_up() {
		parent::set_up();

		$this->user_id = self::factory()->user->create(
			array(
				'user_login' => 'account_owner',
				'user_email' => 'owner@example.org',
				'user_pass'  => self::CURRENT_PASSWORD,
			)
		);
		$this->act_as( $this->user_id );

		UM()->options()->update( 'change_password_request_limit', false );
		UM()->options()->update( 'require_strongpass', false );
	}

	/**
	 * @param array $overrides Fields to add or replace.
	 *
	 * @return array A valid Account > Password tab submission (as passed to the errors hook).
	 */
	private function account_args( array $overrides = array() ) {
		return array_merge(
			array(
				'_um_account'               => '1',
				'_um_account_tab'           => 'password',
				'um_account_nonce_password' => wp_create_nonce( 'um_update_account_password-' . $this->user_id ),
				'current_user_password'     => self::CURRENT_PASSWORD,
				'user_password'             => self::NEW_PASSWORD,
				'confirm_user_password'     => self::NEW_PASSWORD,
			),
			$overrides
		);
	}

	/**
	 * @return string
	 */
	private function rate_limit_transient() {
		return '_um_change_password_rate_limit__' . $this->user_id;
	}

	public function test_valid_submission_has_no_errors() {
		um_submit_account_errors_hook( $this->account_args() );

		$this->assertNull( UM()->form()->errors );
		$this->assertSame( 'password', UM()->account()->current_tab );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_submissions() {
		return array(
			'new password empty'                   => array( array( 'user_password' => '' ), 'user_password' ),
			'confirmation empty'                   => array( array( 'confirm_user_password' => '' ), 'confirm_user_password' ),
			'confirmation does not match'          => array( array( 'confirm_user_password' => 'Different-Passw0rd' ), 'confirm_user_password' ),
			'mismatch is reported before current'  => array(
				array(
					'confirm_user_password' => 'Different-Passw0rd',
					'current_user_password' => 'wrong',
				),
				'confirm_user_password',
			),
			'current password empty'               => array( array( 'current_user_password' => '' ), 'current_user_password' ),
			'current password wrong'               => array( array( 'current_user_password' => 'wrong' ), 'current_user_password' ),
			'backslash in password'                => array(
				array(
					// Account password fields reach the hook still slashed (see ignore_formdata_unslash()).
					'user_password'         => wp_slash( 'Pass\\word1' ),
					'confirm_user_password' => wp_slash( 'Pass\\word1' ),
				),
				'user_password',
			),
		);
	}

	/**
	 * Each problem is reported once, under the field that caused it.
	 *
	 * @dataProvider data_invalid_submissions
	 *
	 * @param array  $overrides   Submission overrides.
	 * @param string $error_field Field that must carry the error.
	 */
	public function test_errors_are_shown_under_the_proper_field( array $overrides, $error_field ) {
		um_submit_account_errors_hook( $this->account_args( $overrides ) );

		$this->assertSame( array( $error_field ), array_keys( (array) UM()->form()->errors ) );
	}

	public function test_mismatch_message() {
		um_submit_account_errors_hook( $this->account_args( array( 'confirm_user_password' => 'Different-Passw0rd' ) ) );

		$this->assertSame( 'Your new password does not match', UM()->form()->errors['confirm_user_password'] );
	}

	/**
	 * @group security
	 */
	public function test_invalid_account_nonce_is_rejected() {
		um_submit_account_errors_hook( $this->account_args( array( 'um_account_nonce_password' => 'forged' ) ) );

		$this->assertTrue( UM()->form()->has_error( 'um_account_security' ) );
	}

	/**
	 * @group security
	 */
	public function test_request_limit_blocks_a_second_change_in_the_interval() {
		UM()->options()->update( 'change_password_request_limit', true );

		um_submit_account_errors_hook( $this->account_args() );
		$this->assertNull( UM()->form()->errors, 'The first change is allowed.' );
		$this->assertNotEmpty( get_transient( $this->rate_limit_transient() ) );

		UM()->form()->errors = null;
		um_submit_account_errors_hook( $this->account_args() );

		$this->assertTrue( UM()->form()->has_error( 'user_password' ) );
		$this->assertStringContainsString( 'password change limit', UM()->form()->errors['user_password'] );
	}

	/**
	 * Typos in the confirmation or the current password don't use up the rate limit window.
	 *
	 * @return array[]
	 */
	public function data_rejected_before_limit() {
		return array(
			'confirmation mismatch'  => array( array( 'confirm_user_password' => 'Different-Passw0rd' ) ),
			'wrong current password' => array( array( 'current_user_password' => 'wrong' ) ),
			'empty confirmation'     => array( array( 'confirm_user_password' => '' ) ),
		);
	}

	/**
	 * @dataProvider data_rejected_before_limit
	 *
	 * @param array $overrides Submission overrides.
	 */
	public function test_rejected_submission_does_not_consume_the_limit( array $overrides ) {
		UM()->options()->update( 'change_password_request_limit', true );

		um_submit_account_errors_hook( $this->account_args( $overrides ) );
		$this->assertFalse( get_transient( $this->rate_limit_transient() ) );

		UM()->form()->errors = null;
		um_submit_account_errors_hook( $this->account_args() );
		$this->assertNull( UM()->form()->errors, 'A corrected submission must go through.' );
	}

	public function test_without_request_limit_repeated_changes_are_allowed() {
		um_submit_account_errors_hook( $this->account_args() );
		um_submit_account_errors_hook( $this->account_args() );

		$this->assertNull( UM()->form()->errors );
		$this->assertFalse( get_transient( $this->rate_limit_transient() ) );
	}

	/**
	 * @return array[]
	 */
	public function data_weak_passwords() {
		return array(
			'too short'          => array( 'Ab1' ),
			'contains username'  => array( 'account_owner' ),
			'no upper/digit'     => array( 'alllowercaseletters' ),
		);
	}

	/**
	 * @dataProvider data_weak_passwords
	 *
	 * @param string $password Weak password.
	 */
	public function test_strong_password_rules( $password ) {
		UM()->options()->update( 'require_strongpass', true );

		um_submit_account_errors_hook(
			$this->account_args(
				array(
					'user_password'         => $password,
					'confirm_user_password' => $password,
				)
			)
		);

		$this->assertTrue( UM()->form()->has_error( 'user_password' ) );
	}

	/**
	 * Logged-out requests are ignored by the Account validation.
	 */
	public function test_logged_out_request_is_ignored() {
		$this->act_as( 0 );

		um_submit_account_errors_hook( $this->account_args( array( 'confirm_user_password' => '' ) ) );

		$this->assertNull( UM()->form()->errors );
	}
}
