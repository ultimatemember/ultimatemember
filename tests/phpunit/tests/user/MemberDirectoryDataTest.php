<?php
/**
 * `um_member_directory_data` user meta: the summary the member directory queries (account status,
 * hidden profile, photos). It is kept in sync when the source user meta changes.
 *
 * @package UM\Tests
 */

/**
 * Class UM_User_MemberDirectoryDataTest
 *
 * @group user
 * @group member-directory
 */
class UM_User_MemberDirectoryDataTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $user_id;

	public function set_up() {
		parent::set_up();

		$this->user_id = self::factory()->user->create();
		UM()->common()->users()->set_status( $this->user_id, 'approved' );
	}

	/**
	 * @return array Directory data of the test user.
	 */
	private function md_data() {
		return get_user_meta( $this->user_id, 'um_member_directory_data', true );
	}

	public function test_new_user_gets_default_data() {
		$this->assertSame(
			array(
				'account_status'  => 'approved',
				'hide_in_members' => false,
				'profile_photo'   => false,
				'cover_photo'     => false,
				'verified'        => false,
			),
			$this->md_data()
		);
	}

	/**
	 * @return array[]
	 */
	public function data_statuses() {
		return array(
			'pending'   => array( 'awaiting_admin_review' ),
			'checkmail' => array( 'awaiting_email_confirmation' ),
			'rejected'  => array( 'rejected' ),
			'inactive'  => array( 'inactive' ),
		);
	}

	/**
	 * @dataProvider data_statuses
	 *
	 * @param string $status Account status.
	 */
	public function test_status_change_is_synced( $status ) {
		UM()->common()->users()->set_status( $this->user_id, $status );

		$this->assertSame( $status, $this->md_data()['account_status'] );
	}

	public function test_hidden_profile_is_synced() {
		update_user_meta( $this->user_id, 'hide_in_members', array( 'Yes' ) );
		$this->assertTrue( $this->md_data()['hide_in_members'] );

		delete_user_meta( $this->user_id, 'hide_in_members' );
		$this->assertFalse( $this->md_data()['hide_in_members'] );
	}

	public function test_photos_are_synced() {
		update_user_meta( $this->user_id, 'profile_photo', 'profile_photo.jpg' );
		update_user_meta( $this->user_id, 'cover_photo', 'cover_photo.jpg' );

		$this->assertTrue( $this->md_data()['profile_photo'] );
		$this->assertTrue( $this->md_data()['cover_photo'] );

		delete_user_meta( $this->user_id, 'profile_photo' );
		delete_user_meta( $this->user_id, 'cover_photo' );

		$this->assertFalse( $this->md_data()['profile_photo'] );
		$this->assertFalse( $this->md_data()['cover_photo'] );
	}

	/**
	 * Deleting a user sets UM()->user()->deleted_user_id, and on_delete_usermeta() then skips every
	 * user, not just the deleted one, for the rest of the request. In a request that deletes a user
	 * and changes other users afterwards (WP-CLI, imports, bulk actions), the directory data of the
	 * other users goes stale: e.g. a profile stays hidden after "hide my profile" is removed.
	 *
	 * @group known-bug
	 */
	public function test_sync_keeps_working_after_another_user_is_deleted() {
		update_user_meta( $this->user_id, 'hide_in_members', array( 'Yes' ) );

		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( self::factory()->user->create() );

		delete_user_meta( $this->user_id, 'hide_in_members' );

		$this->assertFalse( $this->md_data()['hide_in_members'] );
	}
}
