<?php
/**
 * Member directory cards of profiles the visitor can't view (Profile Privacy "Only me"): the member
 * stays listed with the display name, photos and profile link; profile fields are hidden.
 *
 * @package UM\Tests
 */

/**
 * Class UM_MemberDirectory_PrivateProfileCardTest
 *
 * @group member-directory
 * @group security
 */
class UM_MemberDirectory_PrivateProfileCardTest extends UM_UnitTestCase {

	/**
	 * Directory settings with profile fields in the cards: tagline and the "extra information" section.
	 *
	 * @var array
	 */
	private $directory_data = array(
		'show_tagline'   => 1,
		'tagline_fields' => array( 'country' ),
		'show_userinfo'  => 1,
		'reveal_fields'  => array( 'description' ),
	);

	/**
	 * @var int
	 */
	private $private_id;

	/**
	 * @var int
	 */
	private $public_id;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'user' );

		// Profile Privacy applies only to roles that can make their profile private.
		$this->update_role_meta( 'subscriber', array( '_um_can_make_private_profile' => 1 ) );

		$this->private_id = $this->create_member( 'Private', 'Only me' );
		$this->public_id  = $this->create_member( 'Public', 'Everyone' );
	}

	/**
	 * @param string $first_name First name.
	 * @param string $privacy    Profile Privacy.
	 *
	 * @return int User ID.
	 */
	private function create_member( $first_name, $privacy ) {
		$user_id = self::factory()->user->create(
			array(
				'first_name' => $first_name,
				'last_name'  => 'Member',
				'role'       => 'subscriber',
			)
		);
		UM()->common()->users()->set_status( $user_id, 'approved' );
		update_user_meta( $user_id, 'country', 'Ukraine' );
		update_user_meta( $user_id, 'description', $first_name . ' biography' );
		update_user_meta( $user_id, 'profile_privacy', $privacy );
		UM()->user()->remove_cache( $user_id );

		return $user_id;
	}

	/**
	 * @param int $user_id Member.
	 *
	 * @return array Card data.
	 */
	private function card( $user_id ) {
		return UM()->member_directory()->build_user_card_data( $user_id, $this->directory_data );
	}

	/**
	 * @param string $viewer `guest`, `member`, `owner` or `admin`.
	 */
	private function view_as( $viewer ) {
		if ( 'member' === $viewer ) {
			$this->act_as( $this->create_user_with_roles( 'subscriber' ) );
		} elseif ( 'owner' === $viewer ) {
			$this->act_as( $this->private_id );
		} elseif ( 'admin' === $viewer ) {
			$this->act_as( $this->create_user_with_roles( 'administrator' ) );
		} else {
			$this->act_as( 0 );
		}
	}

	/**
	 * @return array[]
	 */
	public function data_viewers() {
		return array(
			'guest'         => array( 'guest', false ),
			'other member'  => array( 'member', false ),
			'profile owner' => array( 'owner', true ),
			'administrator' => array( 'admin', true ),
		);
	}

	/**
	 * @dataProvider data_viewers
	 *
	 * @param string $viewer        Viewer.
	 * @param bool   $sees_fields   Whether the viewer sees the profile fields of the private profile.
	 */
	public function test_private_profile_card( $viewer, $sees_fields ) {
		$this->view_as( $viewer );

		$card = $this->card( $this->private_id );

		// Always listed with the name, photos and profile link.
		$this->assertSame( 'Private Member', $card['display_name'] );
		$this->assertStringContainsString( '<img', $card['avatar'] );
		$this->assertArrayHasKey( 'cover_photo', $card );
		$this->assertSame( esc_url( um_user_profile_url( $this->private_id ) ), $card['profile_url'] );

		if ( $sees_fields ) {
			$this->assertSame( 'Ukraine', $card['country'] );
			$this->assertStringContainsString( 'Private biography', $card['description'] );
		} else {
			$this->assertArrayNotHasKey( 'country', $card );
			$this->assertArrayNotHasKey( 'description', $card );
			$this->assertSame( '', $card['hook_just_after_name'] );
			$this->assertSame( '', $card['hook_after_user_name'] );
		}
	}

	/**
	 * @dataProvider data_viewers
	 *
	 * @param string $viewer Viewer.
	 */
	public function test_public_profile_card_shows_fields( $viewer ) {
		$this->view_as( $viewer );

		$card = $this->card( $this->public_id );

		$this->assertSame( 'Ukraine', $card['country'] );
		$this->assertStringContainsString( 'Public biography', $card['description'] );
	}

	/**
	 * "Only me" has no effect for roles that can't make their profile private.
	 */
	public function test_only_me_is_ignored_for_roles_without_permission() {
		$this->update_role_meta( 'subscriber', array( '_um_can_make_private_profile' => 0 ) );
		UM()->user()->remove_cache( $this->private_id );

		$this->assertSame( 'Ukraine', $this->card( $this->private_id )['country'] );
	}

	/**
	 * Extensions can still add their data to the cards of private profiles.
	 */
	public function test_card_filter_runs_for_private_profiles() {
		add_filter(
			'um_ajax_get_members_data',
			static function ( $data ) {
				$data['extension'] = 'yes';
				return $data;
			}
		);

		$this->assertSame( 'yes', $this->card( $this->private_id )['extension'] );
	}

	/**
	 * Profile hooks after the name (e.g. extension buttons with profile data) aren't run for a profile
	 * the visitor can't view.
	 */
	public function test_name_hooks_are_not_run_for_private_profiles() {
		add_action(
			'um_members_just_after_name',
			static function () {
				echo 'HOOK OUTPUT';
			}
		);

		$this->assertSame( '', $this->card( $this->private_id )['hook_just_after_name'] );
		$this->assertStringContainsString( 'HOOK OUTPUT', $this->card( $this->public_id )['hook_just_after_name'] );
	}
}
