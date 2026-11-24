<?php
/**
 * Who may edit, delete and list other users, according to UM role settings.
 *
 * UM never grants WordPress capabilities here; it only narrows `edit_user`, `delete_user`
 * and `list_users` for roles that already have the WP primitives (`edit_users`, ...),
 * via the `user_has_cap` filter `um\common\Users::map_caps_by_role()`.
 *
 * @package UM\Tests
 */

/**
 * @group roles
 * @group security
 * @covers \um\common\Users::map_caps_by_role
 * @covers \um\core\Roles_Capabilities::um_current_user_can
 * @covers \um\core\Roles_Capabilities::um_user_can
 */
class UM_Roles_UserCapabilityMatrixTest extends UM_UnitTestCase {

	/**
	 * WP primitives of the "manager" role, so that only UM settings decide.
	 *
	 * @var array
	 */
	private static $manager_caps = array(
		'read'         => true,
		'edit_users'   => true,
		'delete_users' => true,
		'list_users'   => true,
	);

	public function set_up() {
		parent::set_up();

		$this->create_um_role( 'target' );
		$this->create_um_role( 'other' );
	}

	/**
	 * Create the acting user with a custom manager role.
	 *
	 * @param array $settings UM role settings without the `_um_` prefix.
	 * @param array|null $wp_caps  WP capabilities of the manager role. Default: self::$manager_caps.
	 *
	 * @return int
	 */
	private function create_manager( array $settings, $wp_caps = null ) {
		$meta = array();
		foreach ( $settings as $key => $value ) {
			$meta[ '_um_' . $key ] = $value;
		}

		$this->create_um_role( 'manager', $meta, null === $wp_caps ? self::$manager_caps : $wp_caps );

		return $this->create_user_with_roles( 'um_manager' );
	}

	/**
	 * @param string[] $roles   Target user roles.
	 * @param bool     $private Whether the target set their profile to "Only me".
	 *
	 * @return int
	 */
	private function create_target( array $roles, $private = false ) {
		$user_id = $this->create_user_with_roles( $roles );
		if ( $private ) {
			update_user_meta( $user_id, 'profile_privacy', 'Only me' );
		}
		return $user_id;
	}

	/**
	 * @dataProvider data_edit_other_user
	 *
	 * @param array    $settings     Manager UM role settings.
	 * @param string[] $target_roles Target user roles.
	 * @param bool     $private      Target profile is private.
	 * @param bool     $expected     Whether the manager may edit the target.
	 */
	public function test_edit_other_user( array $settings, array $target_roles, $private, $expected ) {
		$manager = $this->create_manager( $settings );
		$target  = $this->create_target( $target_roles, $private );

		$this->act_as( $manager );

		$this->assertSame( $expected, current_user_can( 'edit_user', $target ), 'current_user_can( edit_user )' );
		$this->assertSame( (int) $expected, UM()->roles()->um_current_user_can( 'edit', $target ), 'um_current_user_can( edit )' );
	}

	/**
	 * @return array[]
	 */
	public static function data_edit_other_user() {
		return array(
			'can edit everyone'                         => array( array( 'can_edit_everyone' => 1 ), array( 'um_target' ), false, true ),
			'cannot edit everyone'                      => array( array( 'can_edit_everyone' => 0 ), array( 'um_target' ), false, false ),
			'limited to target role'                    => array( array( 'can_edit_everyone' => 1, 'can_edit_roles' => array( 'um_target' ) ), array( 'um_target' ), false, true ),
			'limited to another role'                   => array( array( 'can_edit_everyone' => 1, 'can_edit_roles' => array( 'um_other' ) ), array( 'um_target' ), false, false ),
			'limited to one of the target roles'        => array( array( 'can_edit_everyone' => 1, 'can_edit_roles' => array( 'um_other' ) ), array( 'um_target', 'um_other' ), false, true ),
			'roles list ignored without edit everyone'  => array( array( 'can_edit_everyone' => 0, 'can_edit_roles' => array( 'um_target' ) ), array( 'um_target' ), false, false ),
			'private profile, no private access'        => array( array( 'can_edit_everyone' => 1, 'can_access_private_profile' => 0 ), array( 'um_target' ), true, false ),
			'private profile, with private access'      => array( array( 'can_edit_everyone' => 1, 'can_access_private_profile' => 1 ), array( 'um_target' ), true, true ),
			'private profile access alone is not enough' => array( array( 'can_edit_everyone' => 0, 'can_access_private_profile' => 1 ), array( 'um_target' ), true, false ),
		);
	}

	/**
	 * @dataProvider data_delete_other_user
	 *
	 * @param array    $settings     Manager UM role settings.
	 * @param string[] $target_roles Target user roles.
	 * @param bool     $expected     Whether the manager may delete the target.
	 */
	public function test_delete_other_user( array $settings, array $target_roles, $expected ) {
		$manager = $this->create_manager( $settings );
		$target  = $this->create_target( $target_roles );

		$this->act_as( $manager );

		$this->assertSame( $expected, current_user_can( 'delete_user', $target ), 'current_user_can( delete_user )' );
		$this->assertSame( (int) $expected, UM()->roles()->um_current_user_can( 'delete', $target ), 'um_current_user_can( delete )' );
	}

	/**
	 * @return array[]
	 */
	public static function data_delete_other_user() {
		return array(
			'can delete everyone'                         => array( array( 'can_delete_everyone' => 1 ), array( 'um_target' ), true ),
			'cannot delete everyone'                      => array( array( 'can_delete_everyone' => 0 ), array( 'um_target' ), false ),
			'limited to target role'                      => array( array( 'can_delete_everyone' => 1, 'can_delete_roles' => array( 'um_target' ) ), array( 'um_target' ), true ),
			'limited to another role'                     => array( array( 'can_delete_everyone' => 1, 'can_delete_roles' => array( 'um_other' ) ), array( 'um_target' ), false ),
			'roles list ignored without delete everyone'  => array( array( 'can_delete_everyone' => 0, 'can_delete_roles' => array( 'um_target' ) ), array( 'um_target' ), false ),
			'edit permission does not imply delete'       => array( array( 'can_edit_everyone' => 1, 'can_delete_everyone' => 0 ), array( 'um_target' ), false ),
		);
	}

	public function test_um_settings_do_not_grant_missing_wp_capabilities() {
		$manager = $this->create_manager(
			array(
				'can_edit_everyone'   => 1,
				'can_delete_everyone' => 1,
				'can_view_all'        => 1,
			),
			array( 'read' => true )
		);
		$target  = $this->create_target( array( 'um_target' ) );

		$this->act_as( $manager );

		$this->assertFalse( current_user_can( 'edit_user', $target ) );
		$this->assertFalse( current_user_can( 'delete_user', $target ) );
		$this->assertFalse( current_user_can( 'list_users' ) );
	}

	public function test_administrator_is_not_restricted_by_um_role_settings() {
		$this->update_role_meta(
			'administrator',
			array(
				'_um_can_edit_everyone'   => 0,
				'_um_can_delete_everyone' => 0,
				'_um_can_view_all'        => 0,
			)
		);
		$admin  = $this->create_user_with_roles( 'administrator' );
		$target = $this->create_target( array( 'um_target' ), true );

		$this->act_as( $admin );

		$this->assertTrue( current_user_can( 'edit_user', $target ) );
		$this->assertTrue( current_user_can( 'delete_user', $target ) );
		$this->assertTrue( current_user_can( 'list_users' ) );
	}

	/**
	 * @dataProvider data_list_users
	 *
	 * @param int  $can_view_all UM "can view all" setting.
	 * @param bool $expected     Whether the manager may list users.
	 */
	public function test_list_users( $can_view_all, $expected ) {
		$manager = $this->create_manager( array( 'can_view_all' => $can_view_all ) );

		$this->act_as( $manager );

		$this->assertSame( $expected, current_user_can( 'list_users' ) );
	}

	/**
	 * @return array[]
	 */
	public static function data_list_users() {
		return array(
			'can view all'    => array( 1, true ),
			'cannot view all' => array( 0, false ),
		);
	}

	/**
	 * @dataProvider data_edit_own_profile
	 *
	 * @param int $can_edit_profile UM "can edit profile" setting.
	 */
	public function test_edit_own_profile_follows_um_setting( $can_edit_profile ) {
		$user_id = $this->create_manager( array( 'can_edit_profile' => $can_edit_profile ) );

		$this->act_as( $user_id );

		$this->assertSame( $can_edit_profile, UM()->roles()->um_current_user_can( 'edit', $user_id ) );
	}

	/**
	 * @return array[]
	 */
	public static function data_edit_own_profile() {
		return array(
			'allowed'     => array( 1 ),
			'not allowed' => array( 0 ),
		);
	}

	public function test_logged_out_visitor_has_no_um_permissions() {
		$target = $this->create_target( array( 'um_target' ) );

		$this->act_as( 0 );

		$this->assertFalse( UM()->roles()->um_current_user_can( 'edit', $target ) );
		$this->assertFalse( UM()->roles()->um_current_user_can( 'delete', $target ) );
		$this->assertFalse( UM()->roles()->um_user_can( 'can_view_all' ) );
	}

	public function test_um_user_can_reads_settings_of_priority_role() {
		$this->create_um_role(
			'boss',
			array(
				'_um_priority'       => 10,
				'_um_can_view_all'   => 1,
				'_um_can_view_roles' => array( 'um_target' ),
			)
		);
		$this->create_um_role( 'member', array( '_um_priority' => 1, '_um_can_view_all' => 0 ) );
		$user_id = $this->create_user_with_roles( array( 'um_member', 'um_boss' ) );

		$this->act_as( $user_id );

		$this->assertTrue( UM()->roles()->um_user_can( 'can_view_all' ) );
		$this->assertSame( array( 'um_target' ), UM()->roles()->um_user_can( 'can_view_roles' ) );
		$this->assertFalse( UM()->roles()->um_user_can( 'can_edit_everyone' ) );
		$this->assertFalse( UM()->roles()->um_user_can( 'no_such_permission' ) );
	}

	/**
	 * `editable_roles` (role dropdowns in wp-admin) is narrowed to "can view roles".
	 */
	public function test_editable_roles_are_limited_to_viewable_roles() {
		$manager = $this->create_manager(
			array(
				'can_view_all'   => 1,
				'can_view_roles' => array( 'um_target' ),
			)
		);

		$this->act_as( $manager );

		$roles = apply_filters( 'editable_roles', wp_roles()->roles );

		$this->assertSame( array( 'um_target' ), array_keys( $roles ) );
	}

	public function test_editable_roles_are_not_limited_without_viewable_roles_list() {
		$manager = $this->create_manager( array( 'can_view_all' => 1 ) );

		$this->act_as( $manager );

		$roles = apply_filters( 'editable_roles', wp_roles()->roles );

		$this->assertSame( array_keys( wp_roles()->roles ), array_keys( $roles ) );
	}
}
