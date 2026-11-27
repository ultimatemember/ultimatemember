<?php
/**
 * Account page ([ultimatemember_account]): access, tabs, General / Privacy / Delete / Password
 * submissions and the whitelist of fields that can be saved from the page.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Account_AccountPageTest
 *
 * @group account
 */
class UM_Account_AccountPageTest extends UM_UnitTestCase {

	const PASSWORD = 'Acc0unt-Passw0rd';

	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var int
	 */
	private $account_page_id;

	public function set_up() {
		parent::set_up();

		$this->account_page_id = $this->create_core_page( 'account' );
		$this->create_core_page( 'login' );
		$this->create_core_page( 'user' );
		$this->set_current_page( $this->account_page_id ); // Requests below happen on the Account page.

		UM()->options()->update( 'account_general_password', false );
		UM()->options()->update( 'account_name', true );
		UM()->options()->update( 'account_email', true );

		$this->user_id = self::factory()->user->create(
			array(
				'user_login' => 'account_user',
				'user_email' => 'account_user@example.org',
				'user_pass'  => self::PASSWORD,
				'first_name' => 'Old',
				'last_name'  => 'Name',
				'role'       => 'subscriber',
			)
		);
		UM()->common()->users()->set_status( $this->user_id, 'approved' );

		$this->reset_account_state();
		$this->act_as( $this->user_id );
	}

	public function tear_down() {
		$this->reset_account_state();
		parent::tear_down();
	}

	/**
	 * `UM()->account()` caches rendered tabs and displayed fields in memory.
	 */
	private function reset_account_state() {
		$account                   = UM()->account();
		$account->tab_output       = array();
		$account->displayed_fields = array();
		$account->current_tab      = 'general';
		$account->tabs             = null;

		$exist = new ReflectionProperty( $account, 'account_exist' );
		if ( PHP_VERSION_ID < 80100 ) {
			$exist->setAccessible( true );
		}
		$exist->setValue( $account, array() );
	}

	/**
	 * Render the Account page. This also stores the displayed fields, the only fields the page can save.
	 *
	 * @return string HTML.
	 */
	private function render() {
		return do_shortcode( '[ultimatemember_account]' );
	}

	/**
	 * Submit an Account tab as the current user and run the handler.
	 *
	 * @param string $tab    Tab key.
	 * @param array  $fields Submitted fields.
	 * @param array  $extra  Raw POST keys to add or replace (e.g. a nonce).
	 *
	 * @return array {
	 *     @type string $result   `updated` (data saved), `redirect` or `none` (validation errors).
	 *     @type string $location Redirect location.
	 * }
	 */
	private function submit( $tab, array $fields, array $extra = array() ) {
		$_POST    = wp_slash(
			array_merge(
				array(
					'_um_account'              => '1',
					'_um_account_tab'          => $tab,
					'um_account_nonce_' . $tab => wp_create_nonce( "um_update_account_{$tab}-" . get_current_user_id() ),
				),
				$fields,
				$extra
			)
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Simulated request.

		$updated = static function () {
			throw new UM_Test_Stop_Exception();
		};
		add_action( 'um_after_user_account_updated', $updated, PHP_INT_MAX );

		$result = array(
			'result'   => 'none',
			'location' => '',
		);
		try {
			$result['location'] = $this->capture_redirect(
				static function () {
					UM()->account()->account_submit();
				}
			);
			$result['result']   = 'redirect';
		} catch ( UM_Test_Stop_Exception $e ) {
			$result['result'] = 'updated';
		} catch ( PHPUnit\Framework\AssertionFailedError $e ) {
			// capture_redirect() fails when the handler returns: validation errors.
			if ( false === strpos( $e->getMessage(), 'A redirect was expected' ) ) {
				throw $e;
			}
		} finally {
			remove_action( 'um_after_user_account_updated', $updated, PHP_INT_MAX );
		}

		return $result;
	}

	/**
	 * Logged-out visitors are sent to the login page and come back to the Account page after login.
	 */
	public function test_logged_out_visitor_is_redirected_to_login() {
		$this->act_as( 0 );

		$location = $this->capture_redirect(
			static function () {
				UM()->account()->account_page_restrict();
			}
		);

		$this->assertStringStartsWith( um_get_core_page( 'login' ), $location );
		$this->assertStringContainsString( 'redirect_to=' . rawurlencode( um_get_core_page( 'account' ) ), $location );
		$this->assertSame( '', $this->render() );
	}

	public function test_default_tabs_are_rendered() {
		$html = $this->render();

		foreach ( array( 'general', 'password', 'privacy', 'delete' ) as $tab ) {
			$this->assertStringContainsString( 'data-tab="' . $tab . '"', $html, "Tab {$tab} missing." );
		}
		$this->assertStringContainsString( 'value="Old"', $html, 'Fields are filled with the user data.' );
	}

	public function test_delete_tab_is_hidden_without_permission() {
		$this->update_role_meta( 'subscriber', array( '_um_can_delete_profile' => 0 ) );
		$this->act_as( $this->user_id );

		$this->assertStringNotContainsString( 'data-tab="delete"', $this->render() );
	}

	public function test_general_tab_updates_name_and_email() {
		$this->render();

		$result = $this->submit(
			'general',
			array(
				'first_name' => 'New',
				'last_name'  => 'Person',
				'user_email' => 'new-address@example.org',
			)
		);

		$this->assertSame( 'updated', $result['result'] );
		$user = get_userdata( $this->user_id );
		$this->assertSame( 'New', $user->first_name );
		$this->assertSame( 'Person', $user->last_name );
		$this->assertSame( 'new-address@example.org', $user->user_email );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_general_fields() {
		return array(
			'email empty'   => array( array( 'user_email' => '' ), 'user_email' ),
			'email invalid' => array( array( 'user_email' => 'not-an-email' ), 'user_email' ),
			'email taken'   => array( array( 'user_email' => 'taken@example.org' ), 'user_email' ),
		);
	}

	/**
	 * @dataProvider data_invalid_general_fields
	 *
	 * @param array  $fields      Submitted fields.
	 * @param string $error_field Field that must get the error.
	 */
	public function test_general_tab_validation( array $fields, $error_field ) {
		self::factory()->user->create( array( 'user_email' => 'taken@example.org' ) );
		$this->render();

		$result = $this->submit( 'general', $fields );

		$this->assertSame( 'none', $result['result'] );
		$this->assertTrue( UM()->form()->has_error( $error_field ) );
		$this->assertSame( 'account_user@example.org', get_userdata( $this->user_id )->user_email );
	}

	public function test_first_name_can_be_required() {
		UM()->options()->update( 'account_name_require', true );
		$this->render();

		$result = $this->submit( 'general', array( 'first_name' => '' ) );

		$this->assertSame( 'none', $result['result'] );
		$this->assertTrue( UM()->form()->has_error( 'first_name' ) );
	}

	/**
	 * @return array[]
	 */
	public function data_general_password() {
		return array(
			'right password' => array( self::PASSWORD, true ),
			'wrong password' => array( 'wrong', false ),
			'no password'    => array( '', false ),
		);
	}

	/**
	 * With "Require password to update account" enabled, the current password confirms the change.
	 *
	 * @group security
	 * @dataProvider data_general_password
	 *
	 * @param string $password Submitted password.
	 * @param bool   $saved    Whether the change must be saved.
	 */
	public function test_general_tab_requires_current_password( $password, $saved ) {
		UM()->options()->update( 'account_general_password', true );
		$this->render();

		$result = $this->submit(
			'general',
			array(
				'user_email'           => 'new-address@example.org',
				'single_user_password' => $password,
			)
		);

		$this->assertSame( $saved ? 'updated' : 'none', $result['result'] );
		$this->assertSame( $saved ? 'new-address@example.org' : 'account_user@example.org', get_userdata( $this->user_id )->user_email );
		if ( ! $saved ) {
			$this->assertTrue( UM()->form()->has_error( 'single_user_password' ) );
		}
	}

	/**
	 * CSRF: a forged POST is redirected back with `err=account` and changes nothing.
	 *
	 * @group security
	 */
	public function test_invalid_nonce_changes_nothing() {
		$this->render();

		$result = $this->submit( 'general', array( 'first_name' => 'Hacked' ), array( 'um_account_nonce_general' => 'invalid' ) );

		$this->assertSame( 'redirect', $result['result'] );
		$this->assertStringContainsString( 'err=account', $result['location'] );
		$this->assertSame( 'Old', get_userdata( $this->user_id )->first_name );
	}

	/**
	 * The nonce is bound to the user: another user's Account nonce doesn't work.
	 *
	 * @group security
	 */
	public function test_nonce_of_another_user_is_rejected() {
		$other_id = self::factory()->user->create();
		$this->act_as( $other_id );
		$foreign_nonce = wp_create_nonce( 'um_update_account_general-' . $other_id );
		$this->act_as( $this->user_id );
		$this->render();

		$result = $this->submit( 'general', array( 'first_name' => 'Hacked' ), array( 'um_account_nonce_general' => $foreign_nonce ) );

		$this->assertSame( 'redirect', $result['result'] );
		$this->assertSame( 'Old', get_userdata( $this->user_id )->first_name );
	}

	/**
	 * Only fields displayed on the rendered tab are saved; anything else in the POST is ignored.
	 *
	 * @group security
	 */
	public function test_only_displayed_fields_are_saved() {
		global $wpdb;
		$this->render();

		$this->submit(
			'general',
			array(
				'first_name'                   => 'New',
				'user_login'                   => 'renamed',
				'role'                         => 'administrator',
				$wpdb->prefix . 'capabilities' => array( 'administrator' => true ),
				'account_status'               => 'approved',
				'description'                  => 'injected',
				'hide_in_members'              => array( 'Yes' ), // Field of another tab.
			)
		);

		$user = get_userdata( $this->user_id );
		$this->assertSame( 'New', $user->first_name );
		$this->assertSame( 'account_user', $user->user_login );
		$this->assertSame( array( 'subscriber' ), $user->roles );
		$this->assertSame( '', get_user_meta( $this->user_id, 'description', true ) );
		$this->assertSame( '', get_user_meta( $this->user_id, 'hide_in_members', true ) );
	}

	/**
	 * Without a rendered Account page there is no whitelist, so nothing is saved.
	 *
	 * @group security
	 */
	public function test_nothing_is_saved_without_rendering_the_page() {
		delete_user_meta( $this->user_id, 'um_account_secure_fields' );

		$this->submit( 'general', array( 'first_name' => 'New' ) );

		$this->assertSame( 'Old', get_userdata( $this->user_id )->first_name );
	}

	public function test_privacy_tab_hides_the_profile_from_directories() {
		UM()->options()->update( 'account_hide_in_directory', true );

		$this->assertStringContainsString( 'hide_in_members', $this->render() );

		$result = $this->submit( 'privacy', array( 'hide_in_members' => array( 'Yes' ) ) );

		$this->assertSame( 'updated', $result['result'] );
		$this->assertSame( array( 'Yes' ), get_user_meta( $this->user_id, 'hide_in_members', true ) );
		$this->assertTrue( get_user_meta( $this->user_id, 'um_member_directory_data', true )['hide_in_members'] );
	}

	/**
	 * Choosing the default value removes the meta instead of storing it.
	 */
	public function test_privacy_tab_default_value_removes_the_meta() {
		UM()->options()->update( 'account_hide_in_directory', true );
		update_user_meta( $this->user_id, 'hide_in_members', array( 'Yes' ) );
		$this->render();

		$this->submit( 'privacy', array( 'hide_in_members' => array( 'No' ) ) );

		$this->assertSame( '', get_user_meta( $this->user_id, 'hide_in_members', true ) );
		$this->assertFalse( get_user_meta( $this->user_id, 'um_member_directory_data', true )['hide_in_members'] );
	}

	public function test_profile_privacy_is_saved_for_roles_that_can_make_profile_private() {
		$this->update_role_meta( 'subscriber', array( '_um_can_make_private_profile' => 1 ) );
		$this->act_as( $this->user_id );

		$this->assertStringContainsString( 'name="profile_privacy', $this->render() );
		$result = $this->submit( 'privacy', array( 'profile_privacy' => 'Only me' ) );

		$this->assertSame( 'updated', $result['result'] );
		$this->assertSame( 'Only me', get_user_meta( $this->user_id, 'profile_privacy', true ) );
	}

	/**
	 * "Profile Privacy" isn't shown to roles without "Can make their profile private?", but
	 * init_displayed_fields() adds every privacy field to the account whitelist
	 * (`um_account_secure_fields`) before edit_field() drops it for `required_perm`. A crafted POST
	 * can therefore set `profile_privacy` (and `profile_noindex`) anyway.
	 *
	 * @group security
	 * @group known-bug
	 */
	public function test_profile_privacy_is_not_saved_without_role_permission() {
		$this->assertStringNotContainsString( 'name="profile_privacy', $this->render(), 'Precondition: the field is hidden.' );

		$this->submit( 'privacy', array( 'profile_privacy' => 'Only me' ) );

		$this->assertSame( '', get_user_meta( $this->user_id, 'profile_privacy', true ) );
	}

	public function test_delete_account_with_password() {
		$this->render();

		$result = $this->submit( 'delete', array( 'single_user_password' => self::PASSWORD ) );

		$this->assertSame( 'redirect', $result['result'] );
		$this->assertSame( home_url(), $result['location'] );
		$this->assertFalse( get_userdata( $this->user_id ) );
	}

	/**
	 * @return array[]
	 */
	public function data_wrong_delete_passwords() {
		return array(
			'wrong password' => array( 'wrong' ),
			'no password'    => array( '' ),
		);
	}

	/**
	 * @group security
	 * @dataProvider data_wrong_delete_passwords
	 *
	 * @param string $password Submitted password.
	 */
	public function test_delete_account_requires_the_password( $password ) {
		$this->render();

		$result = $this->submit( 'delete', array( 'single_user_password' => $password ) );

		$this->assertSame( 'none', $result['result'] );
		$this->assertTrue( UM()->form()->has_error( 'single_user_password' ) );
		$this->assertInstanceOf( WP_User::class, get_userdata( $this->user_id ) );
	}

	/**
	 * A role without "Can delete their account?" can't delete it with a forged POST either.
	 *
	 * @group security
	 */
	public function test_delete_account_requires_role_permission() {
		$this->update_role_meta( 'subscriber', array( '_um_can_delete_profile' => 0 ) );
		$this->act_as( $this->user_id );
		$this->render();

		$this->submit( 'delete', array( 'single_user_password' => self::PASSWORD ) );

		$this->assertInstanceOf( WP_User::class, get_userdata( $this->user_id ) );
	}

	public function test_delete_redirect_url_from_role_settings() {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_after_delete'        => 'redirect_url',
				'_um_delete_redirect_url' => home_url( '/goodbye/' ),
			)
		);
		$this->act_as( $this->user_id );
		$this->render();

		$result = $this->submit( 'delete', array( 'single_user_password' => self::PASSWORD ) );

		$this->assertSame( home_url( '/goodbye/' ), $result['location'] );
		$this->assertFalse( get_userdata( $this->user_id ) );
	}

	public function test_password_tab_changes_the_password() {
		UM()->options()->update( 'change_password_request_limit', false );
		UM()->options()->update( 'require_strongpass', false );
		$this->render();
		$emails = array();
		add_action(
			'um_dispatch_email',
			static function ( $email, $template ) use ( &$emails ) {
				$emails[] = $template;
			},
			1,
			2
		);

		$result = $this->submit(
			'password',
			array(
				'current_user_password' => self::PASSWORD,
				'user_password'         => 'N3w-Passw0rd!',
				'confirm_user_password' => 'N3w-Passw0rd!',
			)
		);

		$this->assertSame( 'updated', $result['result'] );
		$user = get_userdata( $this->user_id );
		$this->assertTrue( wp_check_password( 'N3w-Passw0rd!', $user->user_pass, $user->ID ) );
		$this->assertContains( 'changedpw_email', $emails, 'The "password changed" email is sent.' );
	}
}
