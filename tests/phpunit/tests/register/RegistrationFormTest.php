<?php
/**
 * Registration form ([ultimatemember form_id="…"] in "register" mode): user creation, field validation,
 * registration status by role, role assignment and protection against role/meta injection.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Register_RegistrationFormTest
 *
 * @group register
 */
class UM_Register_RegistrationFormTest extends UM_UnitTestCase {

	const PASSWORD = 'Str0ng-Passw0rd';

	/**
	 * @var int
	 */
	private $form_id;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'user' );
		$this->create_core_page( 'register' );
		$_SERVER['REQUEST_URI'] = '/register/';

		$this->form_id = $this->create_um_form( 'register' );

		reset_phpmailer_instance();
	}

	/**
	 * @param array $overrides Field values to add or replace. A null value removes the field.
	 *
	 * @return array A valid registration submission.
	 */
	private function fields( array $overrides = array() ) {
		$fields = array_merge(
			array(
				'user_login'            => 'newbie',
				'user_email'            => 'newbie@example.org',
				'user_password'         => self::PASSWORD,
				'confirm_user_password' => self::PASSWORD,
				'first_name'            => 'New',
				'last_name'             => 'Member',
			),
			$overrides
		);

		return array_filter(
			$fields,
			static function ( $value ) {
				return null !== $value;
			}
		);
	}

	/**
	 * @param array    $fields  Submitted fields.
	 * @param array    $extra   Raw POST keys to add or replace.
	 * @param int|null $form_id Form ID, the default registration form if null.
	 */
	private function submit( array $fields, array $extra = array(), $form_id = null ) {
		$form_id = $form_id ? $form_id : $this->form_id;
		$this->submit_um_form(
			$form_id,
			$fields,
			array_merge( array( '_wpnonce' => wp_create_nonce( 'um_register_form' . $form_id ) ), $extra )
		);
	}

	/**
	 * @param array    $fields  Submitted fields.
	 * @param array    $extra   Raw POST keys to add or replace.
	 * @param int|null $form_id Form ID.
	 *
	 * @return string Redirect location.
	 */
	private function register( array $fields, array $extra = array(), $form_id = null ) {
		return $this->capture_redirect(
			function () use ( $fields, $extra, $form_id ) {
				$this->submit( $fields, $extra, $form_id );
			}
		);
	}

	public function test_valid_registration_creates_approved_user_and_logs_in() {
		$location = $this->register( $this->fields() );

		$user = get_user_by( 'login', 'newbie' );
		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( 'newbie@example.org', $user->user_email );
		$this->assertSame( array( 'subscriber' ), $user->roles );
		$this->assertTrue( wp_check_password( self::PASSWORD, $user->user_pass, $user->ID ), 'The submitted password must be set.' );

		$this->assertSame( 'approved', UM()->common()->users()->get_status( $user->ID ) );
		$this->assertSame( $user->ID, get_current_user_id(), 'Approved users are logged in after registration.' );
		$this->assertSame( um_user_profile_url( $user->ID ), $location );
		$this->assertSame( '', get_user_meta( $user->ID, '_um_registration_in_progress', true ), 'The in-progress marker must be removed.' );
	}

	public function test_submitted_profile_fields_are_saved() {
		$this->register( $this->fields() );

		$user = get_user_by( 'login', 'newbie' );
		$this->assertSame( 'New', get_user_meta( $user->ID, 'first_name', true ) );
		$this->assertSame( 'Member', get_user_meta( $user->ID, 'last_name', true ) );
		$this->assertSame( (string) $this->form_id, (string) get_user_meta( $user->ID, 'form_id', true ) );
		$this->assertSame( 'New Member', $user->display_name );
	}

	/**
	 * The password is never stored as user meta, neither in plain text nor in `submitted`.
	 *
	 * @group security
	 */
	public function test_password_is_not_stored_in_user_meta() {
		$this->register( $this->fields() );

		$user_id = get_user_by( 'login', 'newbie' )->ID;
		$this->assertSame( '', get_user_meta( $user_id, 'user_password', true ) );
		$this->assertSame( '', get_user_meta( $user_id, 'confirm_user_password', true ) );
		$this->assertStringNotContainsString( self::PASSWORD, maybe_serialize( get_user_meta( $user_id, 'submitted', true ) ) );
	}

	/**
	 * @return array[]
	 */
	public function data_registration_statuses() {
		return array(
			'email confirmation' => array( 'checkmail', 'awaiting_email_confirmation' ),
			'admin review'       => array( 'pending', 'awaiting_admin_review' ),
		);
	}

	/**
	 * Roles with "Registration Status" other than auto-approve: the user is created, but stays logged out.
	 *
	 * @dataProvider data_registration_statuses
	 *
	 * @param string $role_status     `_um_status` role setting.
	 * @param string $expected_status Account status after registration.
	 */
	public function test_registration_status_from_role_settings( $role_status, $expected_status ) {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_status'                 => $role_status,
				"_um_{$role_status}_action"  => 'show_message',
				"_um_{$role_status}_message" => 'Thanks!',
			)
		);

		$location = $this->register( $this->fields() );

		$user_id = get_user_by( 'login', 'newbie' )->ID;
		$this->assertSame( $expected_status, UM()->common()->users()->get_status( $user_id ) );
		$this->assertSame( 0, get_current_user_id(), 'Not approved users must not be logged in.' );
		$this->assertStringContainsString( 'message=' . $role_status, $location );
	}

	public function test_email_confirmation_sends_the_activation_email() {
		$this->update_role_meta( 'subscriber', array( '_um_status' => 'checkmail' ) );
		UM()->options()->update( 'checkmail_email_on', 1 );
		$dispatched = array();
		add_action(
			'um_dispatch_email',
			static function ( $email, $template ) use ( &$dispatched ) {
				$dispatched[] = array( $email, $template );
			},
			1,
			2
		);

		$this->submit( $this->fields() );

		$this->assertContains( array( 'newbie@example.org', 'checkmail_email' ), $dispatched );

		$mailer     = tests_retrieve_phpmailer_instance();
		$recipients = array();
		$i          = 0;
		while ( false !== $mailer->get_sent( $i ) ) {
			$recipients[] = $mailer->get_recipient( 'to', $i )->address;
			++$i;
		}
		$this->assertContains( 'newbie@example.org', $recipients, 'An activation email must be sent to the new user.' );
		$this->assertNotEmpty( get_user_meta( get_user_by( 'login', 'newbie' )->ID, 'account_secret_hash', true ) );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_fields() {
		return array(
			'username missing'         => array( array( 'user_login' => '' ), 'user_login' ),
			'username too short'       => array( array( 'user_login' => 'ab' ), 'user_login' ),
			'username taken'           => array( array( 'user_login' => 'taken' ), 'user_login' ),
			'email invalid'            => array( array( 'user_email' => 'not-an-email' ), 'user_email' ),
			'email taken'              => array( array( 'user_email' => 'taken@example.org' ), 'user_email' ),
			'password missing'         => array(
				array(
					'user_password'         => '',
					'confirm_user_password' => '',
				),
				'user_password',
			),
			'password too short'       => array(
				array(
					'user_password'         => 'Sh0rt',
					'confirm_user_password' => 'Sh0rt',
				),
				'user_password',
			),
			'password too weak'        => array(
				array(
					'user_password'         => 'alllowercase',
					'confirm_user_password' => 'alllowercase',
				),
				'user_password',
			),
			'password with backslash'  => array(
				array(
					'user_password'         => 'Str0ng\\Passw0rd',
					'confirm_user_password' => 'Str0ng\\Passw0rd',
				),
				'user_password',
			),
			'passwords do not match'   => array( array( 'confirm_user_password' => 'Other-Passw0rd' ), 'confirm_user_password' ),
			'password confirm missing' => array( array( 'confirm_user_password' => '' ), 'confirm_user_password' ),
		);
	}

	/**
	 * @dataProvider data_invalid_fields
	 *
	 * @param array  $overrides   Invalid field values.
	 * @param string $error_field Field that must get the error.
	 */
	public function test_invalid_fields_show_error_and_create_no_user( array $overrides, $error_field ) {
		self::factory()->user->create(
			array(
				'user_login' => 'taken',
				'user_email' => 'taken@example.org',
			)
		);
		$users_before = count_users()['total_users'];

		$this->submit( $this->fields( $overrides ) );

		$this->assertTrue( UM()->form()->has_error( $error_field ), "Expected an error on {$error_field}, got: " . wp_json_encode( UM()->form()->errors ) );
		$this->assertSame( $users_before, count_users()['total_users'], 'No user must be created.' );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * @return array[]
	 */
	public function data_blocked_emails() {
		return array(
			'blocked domain' => array( 'someone@spam.example', 'blocked_domain' ),
			'blocked email'  => array( 'bad@example.org', 'blocked_email' ),
		);
	}

	/**
	 * Emails and domains from Settings > Access > Other > "Blocked Email Addresses" can't register.
	 *
	 * @dataProvider data_blocked_emails
	 *
	 * @param string $email Submitted email.
	 * @param string $error Expected `err` query argument.
	 */
	public function test_blocked_emails_cannot_register( $email, $error ) {
		UM()->options()->update( 'blocked_emails', "*@spam.example\nbad@example.org" );

		$location = $this->register( $this->fields( array( 'user_email' => $email ) ) );

		$this->assertStringContainsString( 'err=' . $error, $location );
		$this->assertFalse( get_user_by( 'login', 'newbie' ) );
	}

	/**
	 * CSRF: a forged cross-site POST must not create an account.
	 *
	 * @group security
	 */
	public function test_invalid_nonce_redirects_without_creating_user() {
		$location = $this->register( $this->fields(), array( '_wpnonce' => 'invalid' ) );

		$this->assertStringContainsString( 'err=invalid_nonce', $location );
		$this->assertFalse( get_user_by( 'login', 'newbie' ) );
	}

	/**
	 * @group security
	 */
	public function test_filled_honeypot_stops_the_request() {
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Hello, spam bot!' );

		$this->submit( $this->fields(), array( UM()->honeypot => 'i-am-a-bot' ) );
	}

	/**
	 * @return array[]
	 */
	public function data_injected_roles() {
		return array(
			'administrator' => array( 'administrator' ),
			'editor'        => array( 'editor' ),
			'array value'   => array( array( 'administrator' ) ),
		);
	}

	/**
	 * Privilege escalation: a form without a role field must reject any submitted role.
	 *
	 * @group security
	 * @dataProvider data_injected_roles
	 *
	 * @param string|array $role Injected role.
	 */
	public function test_role_cannot_be_injected_into_form_without_role_field( $role ) {
		try {
			$this->submit( $this->fields(), array( 'role' => $role ) );
			$this->fail( 'wp_die() was expected.' );
		} catch ( WPDieException $e ) {
			$this->assertSame( 'This is not possible for security reasons.', $e->getMessage() );
		}

		$this->assertFalse( get_user_by( 'login', 'newbie' ) );
	}

	/**
	 * Privilege escalation: WordPress internals (capabilities, user level) and arbitrary meta keys
	 * are not stored from the submission.
	 *
	 * @group security
	 */
	public function test_non_form_meta_keys_are_not_saved() {
		global $wpdb;

		$this->register(
			$this->fields(),
			array(
				$wpdb->prefix . 'capabilities' => array( 'administrator' => true ),
				$wpdb->prefix . 'user_level'   => '10',
				'account_status'               => 'approved',
				'um_custom_injected'           => 'evil',
			)
		);

		$user = get_user_by( 'login', 'newbie' );
		$this->assertSame( array( 'subscriber' ), $user->roles );
		$this->assertFalse( $user->has_cap( 'manage_options' ) );
		$this->assertSame( '0', (string) get_user_meta( $user->ID, $wpdb->prefix . 'user_level', true ) );
		$this->assertSame( '', get_user_meta( $user->ID, 'um_custom_injected', true ) );
	}

	/**
	 * Form with a role dropdown: allowed roles are assigned, other roles are rejected.
	 *
	 * @group security
	 */
	public function test_role_field_accepts_only_its_own_roles() {
		$this->create_um_role( 'student' );
		$this->create_um_role( 'teacher' );
		$form_id = $this->create_um_form(
			'register',
			array(),
			array(
				'role_select' => array(
					'title'      => 'Account Type',
					'metakey'    => 'role_select',
					'type'       => 'select',
					'label'      => 'Account Type',
					'required'   => 1,
					'public'     => 1,
					'editable'   => true,
					'options'    => array(
						'um_student' => 'Student',
						'um_teacher' => 'Teacher',
					),
					'position'   => '6',
					'in_row'     => '_um_row_1',
					'in_sub_row' => '0',
					'in_column'  => '1',
					'in_group'   => '',
				),
			)
		);

		// The role field is printed as `role-{form_id}`.
		$this->register( $this->fields( array( 'role' => 'um_teacher' ) ), array(), $form_id );

		$this->assertSame( array( 'um_teacher' ), get_user_by( 'login', 'newbie' )->roles );

		// A role that isn't an option of the field.
		$this->act_as( 0 );
		$this->reset_request_state();
		try {
			$this->submit(
				$this->fields(
					array(
						'user_login' => 'sneaky',
						'user_email' => 'sneaky@example.org',
					)
				),
				array( 'role' => 'administrator' ),
				$form_id
			);
			$this->fail( 'wp_die() was expected.' );
		} catch ( WPDieException $e ) {
			$this->assertSame( 'This is not possible for security reasons.', $e->getMessage() );
		}
		$this->assertFalse( get_user_by( 'login', 'sneaky' ) );
	}

	public function test_form_custom_role_setting_is_used() {
		$role_id = $this->create_um_role( 'customer' );
		$form_id = $this->create_um_form(
			'register',
			array(
				'_um_register_use_custom_settings' => 1,
				'_um_register_role'                => $role_id,
			)
		);

		$this->register( $this->fields(), array(), $form_id );

		$this->assertSame( array( $role_id ), get_user_by( 'login', 'newbie' )->roles );
	}

	/**
	 * A form custom role that is an admin role is not possible through the form settings UI, but even
	 * the global "Default New User Role" falls back to the WP default role if the role doesn't exist.
	 */
	public function test_unknown_form_role_falls_back_to_default_role() {
		$form_id = $this->create_um_form(
			'register',
			array(
				'_um_register_use_custom_settings' => 1,
				'_um_register_role'                => 'no_such_role',
			)
		);

		$this->register( $this->fields(), array(), $form_id );

		$this->assertSame( array( get_option( 'default_role' ) ), get_user_by( 'login', 'newbie' )->roles );
	}

	public function test_redirect_to_is_used_after_registration() {
		$target = home_url( '/welcome-aboard/' );

		$location = $this->register( $this->fields(), array( 'redirect_to' => rawurlencode( $target ) ) );

		$this->assertSame( $target, $location );
	}

	/**
	 * @group security
	 */
	public function test_redirect_to_cannot_point_off_site() {
		$location = $this->register( $this->fields(), array( 'redirect_to' => 'https://attacker.invalid/phish' ) );

		$this->assertStringNotContainsString( 'attacker.invalid', $location );
	}

	public function test_rendered_form_contains_a_valid_nonce_and_fields() {
		$html = do_shortcode( '[ultimatemember form_id="' . $this->form_id . '"]' );

		$this->assertSame( 1, preg_match( '/name="_wpnonce" value="([^"]+)"/', $html, $m ), 'Nonce field missing in the registration form.' );
		$this->assertSame( 1, wp_verify_nonce( $m[1], 'um_register_form' . $this->form_id ) );
		foreach ( array( 'user_login', 'user_email', 'user_password', 'confirm_user_password', 'first_name', 'last_name' ) as $key ) {
			$this->assertStringContainsString( 'name="' . $key . '-' . $this->form_id . '"', $html, "Field {$key} missing." );
		}
	}

	/**
	 * Logged-in users don't see the registration form.
	 */
	public function test_registration_form_is_not_shown_to_logged_in_users() {
		$this->act_as( self::factory()->user->create() );

		$html = do_shortcode( '[ultimatemember form_id="' . $this->form_id . '"]' );

		$this->assertStringNotContainsString( 'name="user_login-' . $this->form_id . '"', $html );
	}
}
