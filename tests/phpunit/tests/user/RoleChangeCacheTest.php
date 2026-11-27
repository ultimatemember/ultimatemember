<?php
/**
 * Role changes clear the UM profile cache (`um_cache_userdata_{id}`), so the UM role permissions
 * of the new role are used on the next request: wp-admin > Users > Edit User (WordPress role and
 * "Ultimate Member Role"), the "Change role to…" bulk action and WP_User::add_role() / remove_role()
 * called by code (multi-role plugins, memberships).
 *
 * @package UM\Tests
 */

/**
 * Class UM_User_RoleChangeCacheTest
 *
 * @group user
 * @group roles
 */
class UM_User_RoleChangeCacheTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var int
	 */
	private $other_id;

	public function set_up() {
		parent::set_up();

		require_once ABSPATH . 'wp-admin/includes/user.php';

		UM()->options()->update( 'um_profile_object_cache_stop', false );

		// A UM role that can edit other members and access wp-admin.
		$this->create_um_role(
			'manager',
			array(
				'_um_priority'           => 10,
				'_um_can_edit_everyone'  => 1,
				'_um_can_access_wpadmin' => 1,
			),
			array(
				'read'       => true,
				'edit_users' => true,
			)
		);
		$this->create_um_role( 'student', array( '_um_priority' => 1 ) );

		$this->user_id  = $this->create_user_with_roles( 'subscriber' );
		$this->other_id = $this->create_user_with_roles( 'subscriber' );
	}

	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * Warm the UM profile cache of the user, as any front-end request does.
	 */
	private function cache_profile() {
		um_fetch_user( $this->user_id );
		um_reset_user();
		$this->assertNotEmpty( UM()->user()->get_cached_data( $this->user_id ), 'Precondition: the profile is cached.' );
	}

	/**
	 * Save the Edit User screen as an administrator, like wp-admin/user-edit.php does.
	 *
	 * @param array $fields Submitted fields to add or replace (`role`, `um-role`…).
	 */
	private function save_user_edit_screen( array $fields ) {
		$user = get_userdata( $this->user_id );

		$admin_id = $this->create_user_with_roles( 'administrator' );
		$this->act_as( $admin_id );
		set_current_screen( 'user-edit' );

		$_POST    = wp_slash(
			array_merge(
				array(
					'action'       => 'update',
					'user_id'      => (string) $this->user_id,
					'email'        => $user->user_email,
					'nickname'     => $user->nickname,
					'first_name'   => 'Edited',
					'last_name'    => 'User',
					'display_name' => 'Edited User',
					'role'         => $user->roles[0],
					'um-role'      => '',
				),
				$fields
			)
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Simulated request.

		do_action( 'edit_user_profile_update', $this->user_id );
		$result = edit_user( $this->user_id );
		$this->assertIsInt( $result, is_wp_error( $result ) ? $result->get_error_message() : '' );

		set_current_screen( 'front' );
	}

	/**
	 * State of the user on the next request: the cached profile and the permissions UM uses.
	 *
	 * @return array
	 */
	private function next_request_state() {
		$cached = UM()->user()->get_cached_data( $this->user_id );

		// A new request: UM user data is read again (from the cache if there is one).
		um_reset_user_clean();
		wp_set_current_user( $this->user_id );
		um_fetch_user( $this->user_id );

		$state = array(
			'cached_role'        => is_array( $cached ) ? $cached['role'] : null,
			'role'               => um_user( 'role' ),
			'can_edit_everyone'  => (int) um_user( 'can_edit_everyone' ),
			'um_user_can'        => (bool) UM()->roles()->um_user_can( 'can_edit_everyone' ),
			'can_edit_other'     => (bool) UM()->roles()->um_current_user_can( 'edit', $this->other_id ),
			'can_access_wpadmin' => (int) um_user( 'can_access_wpadmin' ),
		);
		um_reset_user();

		return $state;
	}

	public function test_profile_update_clears_the_cache() {
		$this->cache_profile();

		$this->save_user_edit_screen( array() );

		$cached = UM()->user()->get_cached_data( $this->user_id );
		$this->assertTrue( '' === $cached || 'Edited User' === $cached['display_name'], 'The cached profile must be dropped or rebuilt with the new data.' );
	}

	/**
	 * Secondary "Ultimate Member Role" added on the Edit User screen.
	 */
	public function test_adding_um_role_applies_its_permissions() {
		$this->cache_profile();

		$this->save_user_edit_screen( array( 'um-role' => 'um_manager' ) );

		$this->assertEqualsCanonicalizing( array( 'subscriber', 'um_manager' ), get_userdata( $this->user_id )->roles );
		$state = $this->next_request_state();
		$this->assertSame( 'um_manager', $state['role'], 'Priority role.' );
		$this->assertSame( 1, $state['can_edit_everyone'] );
		$this->assertTrue( $state['um_user_can'] );
		$this->assertTrue( $state['can_edit_other'] );
	}

	/**
	 * WordPress role changed on the Edit User screen (UM role settings of the new WP role).
	 */
	public function test_changing_wp_role_applies_its_permissions() {
		$this->update_role_meta(
			'editor',
			array(
				'_um_can_edit_everyone' => 1,
				'_um_can_edit_roles'    => array(),
			)
		);
		$this->cache_profile();

		$this->save_user_edit_screen( array( 'role' => 'editor' ) );

		$this->assertSame( array( 'editor' ), get_userdata( $this->user_id )->roles );
		$state = $this->next_request_state();
		$this->assertSame( 'editor', $state['role'] );
		$this->assertSame( 1, $state['can_edit_everyone'] );
		$this->assertTrue( $state['um_user_can'] );
	}

	/**
	 * UM role replaced by another one on the Edit User screen.
	 */
	public function test_replacing_um_role_applies_new_permissions() {
		get_userdata( $this->user_id )->add_role( 'um_student' );
		UM()->user()->remove_cache( $this->user_id );
		$this->cache_profile();
		$this->assertSame( 0, (int) UM()->user()->get_cached_data( $this->user_id )['can_edit_everyone'], 'Precondition.' );

		$this->save_user_edit_screen( array( 'um-role' => 'um_manager' ) );

		$roles = get_userdata( $this->user_id )->roles;
		$state = $this->next_request_state();
		$this->assertNotContains( 'um_student', $roles, 'The old UM role is replaced.' );
		$this->assertSame( 1, $state['can_edit_everyone'] );
	}

	/**
	 * UM role removed on the Edit User screen ("— No role for Ultimate Member —").
	 */
	public function test_removing_um_role_drops_its_permissions() {
		get_userdata( $this->user_id )->add_role( 'um_manager' );
		UM()->user()->remove_cache( $this->user_id );
		$this->cache_profile();
		$this->assertSame( 1, (int) UM()->user()->get_cached_data( $this->user_id )['can_edit_everyone'], 'Precondition.' );

		$this->save_user_edit_screen( array( 'um-role' => '' ) );

		$roles = get_userdata( $this->user_id )->roles;
		$state = $this->next_request_state();
		$this->assertSame( array( 'subscriber' ), array_values( $roles ) );
		$this->assertSame( 0, $state['can_edit_everyone'] );
		$this->assertFalse( $state['um_user_can'] );
	}

	/**
	 * wp-admin > Users list > "Change role to…" (bulk action) uses WP_User::set_role().
	 */
	public function test_bulk_role_change_applies_new_permissions() {
		$this->cache_profile();

		get_userdata( $this->user_id )->set_role( 'um_manager' );

		$state = $this->next_request_state();
		$this->assertSame( 'um_manager', $state['role'] );
		$this->assertSame( 1, $state['can_edit_everyone'] );
	}

	/**
	 * Roles changed by code with WP_User::add_role() / remove_role() (other plugins, membership
	 * extensions): WordPress fires `add_user_role` / `remove_user_role`, not `set_user_role`.
	 */
	public function test_add_role_by_code_applies_new_permissions() {
		$this->cache_profile();

		get_userdata( $this->user_id )->add_role( 'um_manager' );

		$state = $this->next_request_state();
		$this->assertSame( 'um_manager', $state['role'] );
		$this->assertSame( 1, $state['can_edit_everyone'] );
	}

	public function test_remove_role_by_code_drops_permissions() {
		get_userdata( $this->user_id )->add_role( 'um_manager' );
		UM()->user()->remove_cache( $this->user_id );
		$this->cache_profile();

		get_userdata( $this->user_id )->remove_role( 'um_manager' );

		$state = $this->next_request_state();
		$this->assertSame( 'subscriber', $state['role'] );
		$this->assertSame( 0, $state['can_edit_everyone'] );
	}
}
