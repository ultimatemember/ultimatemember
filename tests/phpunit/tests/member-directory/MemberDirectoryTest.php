<?php
/**
 * Member directory AJAX query (`um_get_members`): who is listed, directory privacy, role and user
 * filters, search, pagination, nonce and the data exposed in user cards.
 *
 * @package UM\Tests
 */

/**
 * Class UM_MemberDirectory_MemberDirectoryTest
 *
 * @group member-directory
 */
class UM_MemberDirectory_MemberDirectoryTest extends UM_UnitTestCase {

	/**
	 * Users by key.
	 *
	 * @var int[]
	 */
	private $users = array();

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'user' );
		UM()->options()->update( 'ajax_nopriv_rate_limit', false );

		$this->create_um_role( 'student' );

		$members    = array(
			'ada'     => array( 'Ada', 'Lovelace', 'subscriber', 'approved' ),
			'alan'    => array( 'Alan', 'Turing', 'subscriber', 'approved' ),
			'grace'   => array( 'Grace', 'Hopper', 'um_student', 'approved' ),
			'pending' => array( 'Pending', 'Person', 'subscriber', 'awaiting_admin_review' ),
			'email'   => array( 'Unconfirmed', 'Person', 'subscriber', 'awaiting_email_confirmation' ),
			'banned'  => array( 'Rejected', 'Person', 'subscriber', 'rejected' ),
			'off'     => array( 'Inactive', 'Person', 'subscriber', 'inactive' ),
		);
		$registered = strtotime( '2026-01-01 10:00:00' );
		foreach ( $members as $login => $member ) {
			list( $first, $last, $role, $status ) = $member;
			$registered                          += DAY_IN_SECONDS; // Distinct dates: the default sorting is "newest first".

			$user_id = self::factory()->user->create(
				array(
					'user_login'      => $login,
					'user_email'      => $login . '@example.org',
					'first_name'      => $first,
					'last_name'       => $last,
					'role'            => $role,
					'user_registered' => gmdate( 'Y-m-d H:i:s', $registered ),
				)
			);
			UM()->common()->users()->set_status( $user_id, $status );

			$this->users[ $login ] = $user_id;
		}
	}

	/**
	 * @param array $meta Directory settings.
	 *
	 * @return string Directory hash.
	 */
	private function create_directory( array $meta = array() ) {
		return $this->create_member_directory( $meta );
	}

	/**
	 * @param string $hash   Directory hash.
	 * @param array  $params Request parameters.
	 *
	 * @return array|int Response.
	 */
	private function get_members( $hash, array $params = array() ) {
		return $this->get_directory_members( $hash, $params );
	}

	/**
	 * @param array $response AJAX response.
	 *
	 * @return string[] Logins of the listed users.
	 */
	private function listed( array $response ) {
		$logins = array();
		foreach ( $this->directory_user_ids( $response, $this->users ) as $user_id ) {
			$login    = array_search( $user_id, $this->users, true );
			$logins[] = false === $login ? 'other' : $login;
		}
		sort( $logins );

		return $logins;
	}

	/**
	 * The "Members" directory created on plugin installation (UM()->setup()->install_default_forms())
	 * stores `_um_roles` as an empty array. UM()->query()->post_data() returns the raw meta value
	 * `a:0:{}`, which isn't empty, so show_selected_roles() sets `role__in` to an empty array and
	 * ajax_get_members() returns no users at all until the directory is saved in wp-admin once
	 * (the metabox handler doesn't store empty role lists).
	 *
	 * @group known-bug
	 */
	public function test_default_members_directory_lists_members_after_installation() {
		$this->act_as( $this->create_user_with_roles( 'administrator' ) );
		delete_option( 'um_is_installed' );
		UM()->setup()->install_default_forms();
		$core_directories = get_option( 'um_core_directories' );
		$this->assertNotEmpty( $core_directories['members'], 'Precondition: the core directory is installed.' );

		$hash = UM()->member_directory()->set_directory_hash( $core_directories['members'] );
		$this->act_as( $this->users['ada'] ); // The default directory is visible to logged-in users only.

		$this->assertSame( array( 'ada', 'alan', 'grace' ), $this->listed( $this->get_members( $hash ) ) );
	}

	public function test_guests_see_only_approved_members() {
		$hash = $this->create_directory();

		$this->assertSame( array( 'ada', 'alan', 'grace' ), $this->listed( $this->get_members( $hash ) ) );
	}

	public function test_members_see_only_approved_members() {
		$hash = $this->create_directory();
		$this->act_as( $this->users['ada'] );

		$this->assertSame( array( 'ada', 'alan', 'grace' ), $this->listed( $this->get_members( $hash ) ) );
	}

	/**
	 * Users who can edit other users (administrators) see members of every status.
	 */
	public function test_administrators_also_see_not_approved_members() {
		$hash = $this->create_directory( array( '_um_roles' => array( 'subscriber', 'um_student' ) ) );
		$this->act_as( $this->create_user_with_roles( 'administrator' ) );

		$this->assertSame(
			array( 'ada', 'alan', 'banned', 'email', 'grace', 'off', 'pending' ),
			$this->listed( $this->get_members( $hash ) )
		);
	}

	public function test_only_selected_roles_are_listed() {
		$hash = $this->create_directory( array( '_um_roles' => array( 'um_student' ) ) );

		$this->assertSame( array( 'grace' ), $this->listed( $this->get_members( $hash ) ) );
	}

	public function test_show_only_these_users() {
		$hash = $this->create_directory( array( '_um_show_these_users' => array( 'ada', 'grace', 'pending' ) ) );

		$this->assertSame( array( 'ada', 'grace' ), $this->listed( $this->get_members( $hash ) ), 'Not approved users stay hidden.' );
	}

	public function test_exclude_these_users() {
		$hash = $this->create_directory( array( '_um_exclude_these_users' => array( 'alan' ) ) );

		$this->assertSame( array( 'ada', 'grace' ), $this->listed( $this->get_members( $hash ) ) );
	}

	/**
	 * Account > Privacy > "Hide my profile from directory".
	 */
	public function test_member_who_hides_profile_is_not_listed() {
		UM()->options()->update( 'account_hide_in_directory', true );
		update_user_meta( $this->users['alan'], 'hide_in_members', array( 'Yes' ) );
		$hash = $this->create_directory();

		$this->assertSame( array( 'ada', 'grace' ), $this->listed( $this->get_members( $hash ) ) );
	}

	public function test_hidden_profile_is_listed_when_the_account_option_is_disabled() {
		UM()->options()->update( 'account_hide_in_directory', false );
		update_user_meta( $this->users['alan'], 'hide_in_members', array( 'Yes' ) );
		$hash = $this->create_directory();

		$this->assertContains( 'alan', $this->listed( $this->get_members( $hash ) ) );
	}

	/**
	 * Role setting "Can view these user roles only".
	 */
	public function test_role_can_view_only_selected_roles() {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_can_view_all'   => 1,
				'_um_can_view_roles' => array( 'um_student' ),
			)
		);
		$hash = $this->create_directory();
		$this->act_as( $this->users['ada'] );

		$this->assertSame( array( 'grace' ), $this->listed( $this->get_members( $hash ) ) );
	}

	public function test_search_by_name() {
		$hash = $this->create_directory( array( '_um_search' => 1 ) );

		$response = $this->get_members( $hash, array( 'search' => 'Lovelace' ) );

		$this->assertSame( array( 'ada' ), $this->listed( $response ) );
		$this->assertTrue( $response['data']['is_search'] );
	}

	/**
	 * The search can't be used to discover members who aren't listed (not approved accounts).
	 *
	 * @group security
	 */
	public function test_search_does_not_match_not_approved_members() {
		$hash = $this->create_directory( array( '_um_search' => 1 ) );

		$this->assertSame( array(), $this->listed( $this->get_members( $hash, array( 'search' => 'Person' ) ) ) );
	}

	public function test_pagination() {
		$hash = $this->create_directory( array( '_um_profiles_per_page' => 2 ) );

		$first  = $this->get_members( $hash, array( 'page' => 1 ) );
		$second = $this->get_members( $hash, array( 'page' => 2 ) );

		$this->assertCount( 2, $first['data']['users'] );
		$this->assertCount( 1, $second['data']['users'] );
		$this->assertSame( 3, (int) $first['data']['pagination']['total_users'] );
		$this->assertSame( 2, (int) $first['data']['pagination']['total_pages'] );
		$this->assertSame( 2, (int) $second['data']['pagination']['current_page'] );
		// Newest first: grace, alan | ada.
		$this->assertSame( array( 'alan', 'grace' ), $this->listed( $first ) );
		$this->assertSame( array( 'ada' ), $this->listed( $second ) );
	}

	public function test_max_users_limits_the_results() {
		$hash = $this->create_directory(
			array(
				'_um_max_users'         => 1,
				'_um_profiles_per_page' => 12,
			)
		);

		$this->assertCount( 1, $this->get_members( $hash )['data']['users'] );
	}

	/**
	 * @return array[]
	 */
	public function data_directory_privacy() {
		return array(
			'anyone, guest'                => array( 0, null, true ),
			'anyone, member'               => array( 0, 'subscriber', true ),
			'guests only, guest'           => array( 1, null, true ),
			'guests only, member'          => array( 1, 'subscriber', false ),
			'logged in only, guest'        => array( 2, null, false ),
			'logged in only, member'       => array( 2, 'subscriber', true ),
			'selected roles, guest'        => array( 3, null, false ),
			'selected roles, other role'   => array( 3, 'subscriber', false ),
			'selected roles, allowed role' => array( 3, 'um_student', true ),
		);
	}

	/**
	 * Directory setting "Who can see this member directory".
	 *
	 * @group security
	 * @dataProvider data_directory_privacy
	 *
	 * @param int         $privacy  `_um_privacy` value.
	 * @param string|null $role     Role of the visitor, null for a guest.
	 * @param bool        $can_view Expected access.
	 */
	public function test_directory_privacy( $privacy, $role, $can_view ) {
		$hash = $this->create_directory(
			array(
				'_um_privacy'       => $privacy,
				'_um_privacy_roles' => array( 'um_student' ),
			)
		);
		if ( $role ) {
			$this->act_as( $this->create_user_with_roles( $role ) );
		}

		$response = $this->get_members( $hash );

		$this->assertSame( $can_view, $response['success'] );
		if ( ! $can_view ) {
			$this->assertSame( 'You cannot see this member directory', $response['data'] );
		}
	}

	/**
	 * @group security
	 */
	public function test_invalid_nonce_is_rejected() {
		$hash = $this->create_directory();

		$this->assertSame( -1, $this->get_members( $hash, array( '_wpnonce' => 'invalid' ) ) );
	}

	/**
	 * The nonce is bound to the directory: a nonce of a public directory doesn't open a private one.
	 *
	 * @group security
	 */
	public function test_nonce_of_another_directory_is_rejected() {
		$public  = $this->create_directory();
		$private = $this->create_directory( array( '_um_privacy' => 2 ) );

		$this->assertSame( -1, $this->get_members( $private, array( '_wpnonce' => wp_create_nonce( 'um-directory-' . $public ) ) ) );
	}

	public function test_unknown_directory_returns_an_error() {
		$response = $this->get_members( 'nope1' );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Wrong member directory data', $response['data'] );
	}

	/**
	 * User cards don't expose private data: email, login, role or account status.
	 *
	 * @group security
	 */
	public function test_cards_do_not_expose_private_data() {
		$hash = $this->create_directory();

		$response = $this->get_members( $hash );
		$json     = wp_json_encode( $response );

		$this->assertStringNotContainsString( '@example.org', $json );
		foreach ( $response['data']['users'] as $card ) {
			$this->assertSame( 'undefined', $card['role'] );
			$this->assertSame( 'undefined', $card['account_status'] );
			$this->assertFalse( $card['can_edit'] );
			$this->assertSame( '', $card['edit_profile_url'] );
		}
	}

	public function test_card_contains_name_and_profile_url() {
		$hash = $this->create_directory( array( '_um_roles' => array( 'um_student' ) ) );

		$card = $this->get_members( $hash )['data']['users'][0];

		$this->assertSame( 'Grace Hopper', $card['display_name'] );
		$this->assertSame( esc_url( um_user_profile_url( $this->users['grace'] ) ), $card['profile_url'] );
	}

	/**
	 * Directory showing profile fields in the cards (tagline and "reveal" section).
	 *
	 * @return string Directory hash.
	 */
	private function create_directory_with_card_fields() {
		return $this->create_directory(
			array(
				'_um_show_tagline'   => 1,
				'_um_tagline_fields' => array( 'country' ),
				'_um_show_userinfo'  => 1,
				'_um_reveal_fields'  => array( 'description' ),
			)
		);
	}

	/**
	 * @param string $login   Member login.
	 * @param string $privacy `profile_privacy` value, empty for the default.
	 *
	 * @return array Card of the member.
	 */
	private function card_with_profile_fields( $login, $privacy ) {
		update_user_meta( $this->users[ $login ], 'country', 'Ukraine' );
		update_user_meta( $this->users[ $login ], 'description', 'Private biography' );
		if ( $privacy ) {
			// Profile Privacy is available only to roles that can make their profile private.
			$this->update_role_meta( 'subscriber', array( '_um_can_make_private_profile' => 1 ) );
			update_user_meta( $this->users[ $login ], 'profile_privacy', $privacy );
		}

		$response = $this->get_members( $this->create_directory_with_card_fields(), array( 'search' => $login ) );
		$this->assertSame( array( $login ), $this->listed( $response ), 'Precondition: only the member is found.' );

		return $response['data']['users'][0];
	}

	/**
	 * Cards show the profile fields configured in the directory (control case for private profiles).
	 */
	public function test_card_shows_configured_profile_fields() {
		$card = $this->card_with_profile_fields( 'alan', '' );

		$this->assertSame( 'Ukraine', $card['country'] );
		$this->assertStringContainsString( 'Private biography', $card['description'] );
	}

	/**
	 * Profile Privacy "Only me" closes the profile page, but the member stays in directories with the
	 * display name, profile photo and cover photo. Only "Hide my profile from directory" removes a
	 * member from directories.
	 */
	public function test_private_profile_is_listed_with_name_and_photos() {
		$card = $this->card_with_profile_fields( 'alan', 'Only me' );

		$this->assertSame( 'Alan Turing', $card['display_name'] );
		$this->assertStringContainsString( '<img', $card['avatar'] );
		$this->assertArrayHasKey( 'cover_photo', $card );
		$this->assertTrue( UM()->user()->is_private_profile( $this->users['alan'] ) );
	}

	/**
	 * A private ("Only me") profile shows only the name and photos in directories; other profile data
	 * is hidden. build_user_card_data() doesn't check the profile privacy, so the tagline and the
	 * "reveal" fields (here the country and the biography) are shown to everyone.
	 * Fixed on the design/scheme branch (3.0): both the old and the New UI card builders return only
	 * the name, photos and profile link when UM()->common()->users()->can_view_user_profile() is false.
	 *
	 * @group security
	 * @group known-bug
	 */
	public function test_private_profile_card_hides_profile_fields() {
		$card = $this->card_with_profile_fields( 'alan', 'Only me' );

		$this->assertArrayNotHasKey( 'country', $card );
		$this->assertArrayNotHasKey( 'description', $card );
	}

	/**
	 * Guests are limited to 10 requests per minute per IP when the rate limit is enabled.
	 *
	 * @group security
	 */
	public function test_guest_requests_are_rate_limited() {
		UM()->options()->update( 'ajax_nopriv_rate_limit', true );
		$hash = $this->create_directory();

		for ( $i = 0; $i < 10; $i++ ) {
			$this->assertTrue( $this->get_members( $hash )['success'], "Request {$i} must pass." );
		}
		$response = $this->get_members( $hash );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Too many requests', $response['data'] );
	}
}
