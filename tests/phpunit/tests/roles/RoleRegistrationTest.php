<?php
/**
 * Custom UM roles: registration in WP_Roles, role data, capability sync.
 *
 * @package UM\Tests
 */

/**
 * @group roles
 * @covers \um\core\Roles_Capabilities
 */
class UM_Roles_RoleRegistrationTest extends UM_UnitTestCase {

	public function test_custom_role_is_registered_in_wp_roles() {
		$role_id = $this->create_um_role(
			'qa_moderator',
			array( 'name' => 'QA Moderator' ),
			array(
				'read'       => true,
				'edit_posts' => true,
			)
		);

		$this->assertSame( 'um_qa_moderator', $role_id );
		$this->assertTrue( wp_roles()->is_role( 'um_qa_moderator' ) );
		$this->assertSame( 'QA Moderator', wp_roles()->role_names['um_qa_moderator'] );

		$role = get_role( 'um_qa_moderator' );
		$this->assertTrue( $role->has_cap( 'read' ) );
		$this->assertTrue( $role->has_cap( 'edit_posts' ) );
		$this->assertFalse( $role->has_cap( 'manage_options' ) );
	}

	public function test_custom_role_stores_capabilities_in_wp_format() {
		$this->create_um_role( 'qa_moderator', array(), array( 'read' => true ) );

		$role_data = wp_roles()->roles['um_qa_moderator'];

		$this->assertSame( array( 'read' => true ), $role_data['capabilities'] );
		$this->assertArrayNotHasKey( 'wp_capabilities', $role_data, 'UM-only key must not leak into WP role data.' );
	}

	public function test_user_with_custom_role_gets_role_capabilities() {
		$this->create_um_role(
			'qa_moderator',
			array(),
			array(
				'read'         => true,
				'upload_files' => true,
			)
		);
		$user_id = $this->create_user_with_roles( 'um_qa_moderator' );

		$this->assertTrue( user_can( $user_id, 'upload_files' ) );
		$this->assertFalse( user_can( $user_id, 'manage_options' ) );
		$this->assertFalse( user_can( $user_id, 'edit_users' ) );
	}

	public function test_custom_role_without_meta_is_not_registered() {
		update_option( 'um_roles', array( 'ghost' ) );
		$this->reload_roles();

		$this->assertFalse( wp_roles()->is_role( 'um_ghost' ) );
	}

	public function test_builtin_role_receives_default_um_settings() {
		$this->assertSame( 1, wp_roles()->roles['subscriber']['_um_can_edit_profile'] );
		$this->assertSame( 1, wp_roles()->roles['administrator']['_um_can_edit_everyone'] );
	}

	public function test_is_role_custom() {
		$roles = UM()->roles();

		$this->assertFalse( $roles->is_role_custom( 'um_qa_moderator' ), 'No UM roles exist yet.' );

		$this->create_um_role( 'qa_moderator' );

		$this->assertTrue( $roles->is_role_custom( 'um_qa_moderator' ) );
		$this->assertFalse( $roles->is_role_custom( 'qa_moderator' ), 'Role ID needs the um_ prefix.' );
		$this->assertFalse( $roles->is_role_custom( 'subscriber' ) );
		$this->assertFalse( $roles->is_role_custom( 'administrator' ) );
	}

	public function test_role_data_strips_um_prefix_from_setting_keys() {
		$this->create_um_role(
			'qa_moderator',
			array(
				'_um_can_edit_everyone' => 1,
				'_um_can_edit_roles'    => array( 'subscriber' ),
			)
		);

		$data = UM()->roles()->role_data( 'um_qa_moderator' );

		$this->assertSame( 1, $data['can_edit_everyone'] );
		$this->assertSame( array( 'subscriber' ), $data['can_edit_roles'] );
		$this->assertArrayNotHasKey( '_um_can_edit_everyone', $data );
	}

	public function test_role_data_for_builtin_and_unknown_roles() {
		$subscriber = UM()->roles()->role_data( 'subscriber' );
		$this->assertSame( 1, $subscriber['can_edit_profile'] );
		$this->assertSame( 0, $subscriber['can_edit_everyone'] );

		$this->assertSame( array(), UM()->roles()->role_data( '' ) );
		$this->assertSame( array(), UM()->roles()->role_data( 'um_does_not_exist' ) );
	}

	public function test_role_data_is_filterable() {
		$this->create_um_role( 'qa_moderator' );

		add_filter(
			'um_change_role_data',
			static function ( $data, $role_key ) {
				if ( 'qa_moderator' === $role_key ) {
					$data['can_edit_everyone'] = 1;
				}
				return $data;
			},
			10,
			2
		);

		$this->assertSame( 1, UM()->roles()->role_data( 'um_qa_moderator' )['can_edit_everyone'] );
	}

	public function test_get_role_name() {
		$this->create_um_role( 'qa_moderator', array( 'name' => 'QA Moderator' ) );

		$this->assertSame( 'QA Moderator', UM()->roles()->get_role_name( 'um_qa_moderator' ) );
		$this->assertSame( 'Subscriber', UM()->roles()->get_role_name( 'subscriber' ) );
		$this->assertFalse( UM()->roles()->get_role_name( 'no_such_role' ) );
	}

	public function test_adding_capability_to_custom_role_syncs_um_meta() {
		$this->create_um_role( 'qa_moderator' );

		get_role( 'um_qa_moderator' )->add_cap( 'upload_files' );

		$meta = get_option( 'um_role_qa_moderator_meta' );
		$this->assertTrue( $meta['wp_capabilities']['upload_files'] );
	}

	public function test_removing_capability_from_stored_custom_role_syncs_um_meta() {
		$this->create_um_role(
			'qa_moderator',
			array(),
			array(
				'read'         => true,
				'upload_files' => true,
			)
		);
		// Any role write stores UM roles in the `wp_user_roles` option too.
		get_role( 'um_qa_moderator' )->add_cap( 'edit_posts' );

		get_role( 'um_qa_moderator' )->remove_cap( 'upload_files' );

		$meta = get_option( 'um_role_qa_moderator_meta' );
		$this->assertArrayNotHasKey( 'upload_files', $meta['wp_capabilities'] );
		$this->assertTrue( $meta['wp_capabilities']['read'] );
	}

	/**
	 * Known bug: `um_on_roles_update()` finds removed caps by diffing against the previous
	 * `wp_user_roles` value, but UM roles are only added to `$wp_roles` in memory. Until something
	 * writes the roles option, a UM role is absent from the old value, so the first `remove_cap()`
	 * is not synced to `um_role_{key}_meta` and the capability comes back on the next request.
	 *
	 * @group known-bug
	 */
	public function test_removed_capability_stays_removed_on_next_request() {
		$this->create_um_role(
			'qa_moderator',
			array(),
			array(
				'read'         => true,
				'upload_files' => true,
			)
		);

		get_role( 'um_qa_moderator' )->remove_cap( 'upload_files' );
		$this->reload_roles(); // Next request.

		$this->assertFalse( get_role( 'um_qa_moderator' )->has_cap( 'upload_files' ) );
	}
}
