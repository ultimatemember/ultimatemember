<?php
/**
 * Profile form (edit mode of [ultimatemember form_id="…"] in "profile" mode): who can edit whom,
 * which fields are saved, nonce, and protection against role/meta injection.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Profile_ProfileFormTest
 *
 * @group profile
 */
class UM_Profile_ProfileFormTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $form_id;

	/**
	 * @var int
	 */
	private $owner_id;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'user' );
		$_SERVER['REQUEST_URI'] = '/user/';

		$text = array(
			'type'       => 'text',
			'public'     => 1,
			'editable'   => true,
			'required'   => 0,
			'in_row'     => '_um_row_1',
			'in_sub_row' => '0',
			'in_column'  => '1',
			'in_group'   => '',
		);

		$this->form_id = $this->create_um_form(
			'profile',
			array(),
			array(
				'first_name'     => array_merge( $text, array( 'metakey' => 'first_name', 'title' => 'First Name', 'label' => 'First Name', 'position' => '1' ) ),
				'last_name'      => array_merge( $text, array( 'metakey' => 'last_name', 'title' => 'Last Name', 'label' => 'Last Name', 'position' => '2' ) ),
				'favorite_color' => array_merge( $text, array( 'metakey' => 'favorite_color', 'title' => 'Favorite color', 'label' => 'Favorite color', 'position' => '3' ) ),
				'plan'           => array_merge(
					$text,
					array(
						'type'     => 'select',
						'metakey'  => 'plan',
						'title'    => 'Plan',
						'label'    => 'Plan',
						'options'  => array( 'Basic', 'Pro' ),
						'position' => '4',
					)
				),
				'member_number'  => array_merge(
					$text,
					array(
						'metakey'  => 'member_number',
						'title'    => 'Member number',
						'label'    => 'Member number',
						'editable' => false,
						'position' => '5',
					)
				),
				'user_email'     => array_merge( $text, array( 'metakey' => 'user_email', 'title' => 'Email', 'label' => 'Email', 'position' => '6' ) ),
			)
		);

		$this->owner_id = self::factory()->user->create(
			array(
				'user_login' => 'owner',
				'user_email' => 'owner@example.org',
				'role'       => 'subscriber',
			)
		);
		UM()->common()->users()->set_status( $this->owner_id, 'approved' );
		update_user_meta( $this->owner_id, 'first_name', 'Old' );
		update_user_meta( $this->owner_id, 'favorite_color', 'red' );
		update_user_meta( $this->owner_id, 'member_number', '42' );
	}

	/**
	 * Submit the profile form of `$profile_id` as the current user.
	 *
	 * @param int   $profile_id Edited user.
	 * @param array $fields     Field values.
	 * @param array $extra      Raw POST keys to add or replace.
	 *
	 * @return string Redirect location.
	 */
	private function save_profile( $profile_id, array $fields, array $extra = array() ) {
		return $this->capture_redirect(
			function () use ( $profile_id, $fields, $extra ) {
				$this->submit_um_form(
					$this->form_id,
					$fields,
					array_merge(
						array(
							'user_id'       => (string) $profile_id,
							'profile_nonce' => wp_create_nonce( 'um-profile-nonce' . $this->form_id . $profile_id ),
						),
						$extra
					)
				);
			}
		);
	}

	/**
	 * Run code that must end with wp_die() and return the message.
	 *
	 * @param callable $callback Code under test.
	 *
	 * @return string wp_die() message.
	 */
	private function expect_wp_die( callable $callback ) {
		try {
			$callback();
		} catch ( WPDieException $e ) {
			return $e->getMessage();
		} catch ( UM_Test_Redirect_Exception $e ) {
			$this->fail( 'wp_die() was expected, got a redirect to ' . $e->location );
		}

		$this->fail( 'wp_die() was expected.' );
		return '';
	}

	public function test_owner_updates_own_profile() {
		$this->act_as( $this->owner_id );
		// The form is submitted on the User page.
		$GLOBALS['post'] = get_post( UM()->config()->permalinks['user'] );

		$location = $this->save_profile(
			$this->owner_id,
			array(
				'first_name'     => 'Ada',
				'last_name'      => 'Lovelace',
				'favorite_color' => 'blue',
				'plan'           => 'Pro',
			)
		);

		$this->assertSame( 'Ada', get_user_meta( $this->owner_id, 'first_name', true ) );
		$this->assertSame( 'Lovelace', get_user_meta( $this->owner_id, 'last_name', true ) );
		$this->assertSame( 'blue', get_user_meta( $this->owner_id, 'favorite_color', true ) );
		$this->assertSame( 'Pro', get_user_meta( $this->owner_id, 'plan', true ) );
		$this->assertSame( 'Ada Lovelace', get_userdata( $this->owner_id )->display_name, 'Display name follows the "full_name" setting.' );
		$this->assertSame( um_user_profile_url( $this->owner_id ), $location );
	}

	public function test_text_values_are_sanitized() {
		$this->act_as( $this->owner_id );

		$this->save_profile( $this->owner_id, array( 'favorite_color' => '<script>alert(1)</script>green' ) );

		$this->assertStringNotContainsString( '<script>', get_user_meta( $this->owner_id, 'favorite_color', true ) );
	}

	public function test_select_value_outside_of_options_is_not_saved() {
		$this->act_as( $this->owner_id );
		update_user_meta( $this->owner_id, 'plan', 'Basic' );

		$this->save_profile( $this->owner_id, array( 'plan' => 'Enterprise' ) );

		$this->assertSame( 'Basic', get_user_meta( $this->owner_id, 'plan', true ) );
	}

	public function test_not_editable_field_is_not_saved() {
		$this->act_as( $this->owner_id );

		$this->save_profile( $this->owner_id, array( 'member_number' => '1' ) );

		$this->assertSame( '42', get_user_meta( $this->owner_id, 'member_number', true ) );
	}

	/**
	 * Username and email can't be changed through the profile form (only on the Account page).
	 *
	 * @group security
	 */
	public function test_restricted_fields_are_not_saved() {
		$this->act_as( $this->owner_id );

		$this->save_profile(
			$this->owner_id,
			array(
				'user_email' => 'hijacked@example.org',
				'user_login' => 'hijacked',
			)
		);

		$user = get_userdata( $this->owner_id );
		$this->assertSame( 'owner@example.org', $user->user_email );
		$this->assertSame( 'owner', $user->user_login );
	}

	/**
	 * Privilege escalation: meta keys that aren't fields of the form are never saved.
	 *
	 * @group security
	 */
	public function test_non_form_meta_keys_are_not_saved() {
		global $wpdb;
		$this->act_as( $this->owner_id );

		$this->save_profile(
			$this->owner_id,
			array( 'favorite_color' => 'blue' ),
			array(
				$wpdb->prefix . 'capabilities' => array( 'administrator' => true ),
				$wpdb->prefix . 'user_level'   => '10',
				'account_status'               => 'approved',
				'um_custom_injected'           => 'evil',
				'favorite_color-999'           => 'other-form',
			)
		);

		$user = get_userdata( $this->owner_id );
		$this->assertSame( array( 'subscriber' ), $user->roles );
		$this->assertFalse( $user->has_cap( 'manage_options' ) );
		$this->assertSame( '', get_user_meta( $this->owner_id, 'um_custom_injected', true ) );
		$this->assertSame( 'blue', get_user_meta( $this->owner_id, 'favorite_color', true ) );
	}

	/**
	 * Privilege escalation: a profile form without a role field rejects a submitted role.
	 *
	 * @group security
	 */
	public function test_role_cannot_be_changed_without_role_field() {
		$this->act_as( $this->owner_id );

		$message = $this->expect_wp_die(
			function () {
				$this->save_profile( $this->owner_id, array( 'favorite_color' => 'blue' ), array( 'role' => 'administrator' ) );
			}
		);

		$this->assertSame( 'This is not possible for security reasons.', $message );
		$this->assertSame( array( 'subscriber' ), get_userdata( $this->owner_id )->roles );
	}

	/**
	 * IDOR: a member can't edit another member's profile by changing `user_id`.
	 *
	 * @group security
	 */
	public function test_member_cannot_edit_another_members_profile() {
		$intruder_id = $this->create_user_with_roles( 'subscriber' );
		$this->act_as( $intruder_id );

		$message = $this->expect_wp_die(
			function () {
				$this->save_profile( $this->owner_id, array( 'favorite_color' => 'hacked' ) );
			}
		);

		$this->assertSame( 'You are not allowed to edit this user.', $message );
		$this->assertSame( 'red', get_user_meta( $this->owner_id, 'favorite_color', true ) );
	}

	/**
	 * The nonce is bound to the edited user: a nonce for the own profile doesn't work for another one.
	 *
	 * @group security
	 */
	public function test_nonce_of_own_profile_does_not_work_for_another_profile() {
		$intruder_id = $this->create_user_with_roles( 'subscriber' );
		$this->act_as( $intruder_id );

		$message = $this->expect_wp_die(
			function () use ( $intruder_id ) {
				$this->save_profile(
					$this->owner_id,
					array( 'favorite_color' => 'hacked' ),
					array( 'profile_nonce' => wp_create_nonce( 'um-profile-nonce' . $this->form_id . $intruder_id ) )
				);
			}
		);

		$this->assertSame( 'This is not possible for security reasons.', $message );
		$this->assertSame( 'red', get_user_meta( $this->owner_id, 'favorite_color', true ) );
	}

	/**
	 * @group security
	 */
	public function test_logged_out_visitor_cannot_edit_profile() {
		$this->expect_wp_die(
			function () {
				$this->save_profile( $this->owner_id, array( 'favorite_color' => 'hacked' ) );
			}
		);

		$this->assertSame( 'red', get_user_meta( $this->owner_id, 'favorite_color', true ) );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_nonces() {
		return array(
			'nonce missing' => array( '' ),
			'garbage nonce' => array( 'abc123' ),
		);
	}

	/**
	 * CSRF: a forged cross-site POST must not change the profile.
	 *
	 * @group security
	 * @dataProvider data_invalid_nonces
	 *
	 * @param string $nonce Submitted nonce.
	 */
	public function test_invalid_nonce_stops_the_request( $nonce ) {
		$this->act_as( $this->owner_id );

		$message = $this->expect_wp_die(
			function () use ( $nonce ) {
				$this->save_profile( $this->owner_id, array( 'favorite_color' => 'hacked' ), array( 'profile_nonce' => $nonce ) );
			}
		);

		$this->assertSame( 'This is not possible for security reasons.', $message );
		$this->assertSame( 'red', get_user_meta( $this->owner_id, 'favorite_color', true ) );
	}

	/**
	 * Roles with "Can edit other member accounts?" can edit any profile, including fields
	 * that aren't editable for the owner.
	 */
	public function test_role_with_can_edit_everyone_edits_other_profiles() {
		$manager_role = $this->create_um_role(
			'manager',
			array(
				'_um_can_edit_everyone' => 1,
				'_um_can_edit_roles'    => array(),
			),
			array(
				'read'       => true,
				'edit_users' => true,
			)
		);
		$this->act_as( $this->create_user_with_roles( $manager_role ) );

		$this->save_profile(
			$this->owner_id,
			array(
				'favorite_color' => 'green',
				'member_number'  => '7',
			)
		);

		$this->assertSame( 'green', get_user_meta( $this->owner_id, 'favorite_color', true ) );
		$this->assertSame( '7', get_user_meta( $this->owner_id, 'member_number', true ) );
	}

	/**
	 * Roles without "Can edit their profile?" can't edit even their own profile.
	 *
	 * @group security
	 */
	public function test_role_without_can_edit_profile_cannot_edit_own_profile() {
		$this->update_role_meta( 'subscriber', array( '_um_can_edit_profile' => 0 ) );
		$this->act_as( $this->owner_id );

		$this->expect_wp_die(
			function () {
				$this->save_profile( $this->owner_id, array( 'favorite_color' => 'blue' ) );
			}
		);

		$this->assertSame( 'red', get_user_meta( $this->owner_id, 'favorite_color', true ) );
	}

	public function test_redirect_after_update_is_filterable() {
		$this->act_as( $this->owner_id );
		add_filter(
			'um_update_profile_redirect_after',
			static function () {
				return home_url( '/thanks/' );
			}
		);

		$location = $this->save_profile( $this->owner_id, array( 'favorite_color' => 'blue' ) );

		$this->assertStringStartsWith( home_url( '/thanks/' ), $location );
	}
}
