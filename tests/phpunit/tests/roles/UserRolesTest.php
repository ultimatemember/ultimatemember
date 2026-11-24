<?php
/**
 * Roles of a particular user: UM role lookup, priority, set_role().
 *
 * @package UM\Tests
 */

/**
 * @group roles
 * @covers \um\core\Roles_Capabilities
 */
class UM_Roles_UserRolesTest extends UM_UnitTestCase {

	public function test_get_um_user_role_returns_the_custom_um_role() {
		$this->create_um_role( 'member' );
		$user_id = $this->create_user_with_roles( array( 'subscriber', 'um_member' ) );

		$this->assertSame( 'um_member', UM()->roles()->get_um_user_role( $user_id ) );
	}

	public function test_get_um_user_role_is_false_without_custom_um_role() {
		$this->create_um_role( 'member' );
		$user_id = $this->create_user_with_roles( 'subscriber' );

		$this->assertFalse( UM()->roles()->get_um_user_role( $user_id ) );
	}

	public function test_get_um_user_role_is_false_when_no_um_roles_exist() {
		$user_id = $this->create_user_with_roles( 'subscriber' );

		$this->assertFalse( UM()->roles()->get_um_user_role( $user_id ) );
	}

	public function test_priority_role_is_the_one_with_highest_priority() {
		$this->create_um_role( 'low', array( '_um_priority' => 5 ) );
		$this->create_um_role( 'high', array( '_um_priority' => 10 ) );
		$user_id = $this->create_user_with_roles( array( 'subscriber', 'um_low', 'um_high' ) );

		$this->assertSame( 'um_high', UM()->roles()->get_priority_user_role( $user_id ) );
		$this->assertSame(
			array( 'um_high', 'um_low', 'subscriber' ),
			UM()->roles()->get_all_user_roles( $user_id, true )
		);
	}

	public function test_builtin_role_priority_is_respected() {
		$this->create_um_role( 'member', array( '_um_priority' => 5 ) );
		$this->update_role_meta( 'editor', array( '_um_priority' => 50 ) );
		$user_id = $this->create_user_with_roles( array( 'um_member', 'editor' ) );

		$this->assertSame( 'editor', UM()->roles()->get_priority_user_role( $user_id ) );
	}

	public function test_get_all_user_roles_without_priority_keeps_assignment_order() {
		$this->create_um_role( 'high', array( '_um_priority' => 10 ) );
		$user_id = $this->create_user_with_roles( array( 'subscriber', 'um_high' ) );

		$this->assertSame( array( 'subscriber', 'um_high' ), UM()->roles()->get_all_user_roles( $user_id ) );
	}

	public function test_role_lookups_for_missing_user() {
		$this->assertFalse( UM()->roles()->get_all_user_roles( PHP_INT_MAX ) );
		$this->assertFalse( UM()->roles()->get_priority_user_role( PHP_INT_MAX ) );
	}

	public function test_set_role_replaces_previous_um_role_and_keeps_wp_role() {
		$this->create_um_role( 'old' );
		$this->create_um_role( 'new' );
		$user_id = $this->create_user_with_roles( array( 'subscriber', 'um_old' ) );

		$upgrades = array();
		add_action(
			'um_member_role_upgrade',
			static function ( $old_role, $new_role ) use ( &$upgrades ) {
				$upgrades[] = array( $old_role, $new_role );
			},
			10,
			2
		);

		$result = UM()->roles()->set_role( $user_id, 'um_new' );

		$this->assertSame( 'um_new', $result );
		$roles = get_userdata( $user_id )->roles;
		$this->assertContains( 'um_new', $roles );
		$this->assertContains( 'subscriber', $roles );
		$this->assertNotContains( 'um_old', $roles );
		$this->assertSame( array( array( 'um_old', 'um_new' ) ), $upgrades );
	}

	public function test_set_role_to_current_role_changes_nothing() {
		$this->create_um_role( 'member' );
		$user_id = $this->create_user_with_roles( array( 'subscriber', 'um_member' ) );
		$fired   = did_action( 'um_after_user_role_is_updated' );

		$this->assertFalse( UM()->roles()->set_role( $user_id, 'um_member' ) );
		$this->assertSame( $fired, did_action( 'um_after_user_role_is_updated' ) );
	}

	public function test_set_role_to_builtin_role_the_user_already_has_changes_nothing() {
		$user_id = $this->create_user_with_roles( 'subscriber' );

		$this->assertFalse( UM()->roles()->set_role( $user_id, 'subscriber' ) );
		$this->assertSame( array( 'subscriber' ), array_values( get_userdata( $user_id )->roles ) );
	}

	public function test_set_role_for_missing_user_returns_false() {
		$this->create_um_role( 'member' );

		$this->assertFalse( UM()->roles()->set_role( PHP_INT_MAX, 'um_member' ) );
	}
}
