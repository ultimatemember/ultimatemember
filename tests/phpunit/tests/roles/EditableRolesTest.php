<?php
/**
 * Roles a user may pick or be given from the front end (registration/profile forms).
 * Roles with admin-level capabilities must never be offered.
 *
 * @package UM\Tests
 */

/**
 * @group roles
 * @group security
 * @covers \um\core\Roles_Capabilities::get_editable_user_roles
 * @covers \um\core\Roles_Capabilities::get_editable_priority_user_role
 */
class UM_Roles_EditableRolesTest extends UM_UnitTestCase {

	public function test_subscriber_and_safe_custom_roles_are_editable() {
		$this->create_um_role( 'member' );

		$editable = UM()->roles()->get_editable_user_roles();

		$this->assertContains( 'subscriber', $editable );
		$this->assertContains( 'um_member', $editable );
		$this->assertNotContains( 'administrator', $editable );
		$this->assertNotContains( 'editor', $editable );
	}

	/**
	 * @dataProvider data_banned_capabilities
	 *
	 * @param string $capability Admin-level capability.
	 */
	public function test_custom_role_with_banned_capability_is_not_editable( $capability ) {
		$this->create_um_role(
			'escalated',
			array(),
			array(
				'read'      => true,
				$capability => true,
			)
		);

		$this->assertNotContains( 'um_escalated', UM()->roles()->get_editable_user_roles() );
	}

	/**
	 * @return array[]
	 */
	public static function data_banned_capabilities() {
		return array(
			'manage_options'   => array( 'manage_options' ),
			'edit_users'       => array( 'edit_users' ),
			'create_users'     => array( 'create_users' ),
			'delete_users'     => array( 'delete_users' ),
			'promote_users'    => array( 'promote_users' ),
			'list_users'       => array( 'list_users' ),
			'activate_plugins' => array( 'activate_plugins' ),
			'install_plugins'  => array( 'install_plugins' ),
			'edit_plugins'     => array( 'edit_plugins' ),
			'edit_files'       => array( 'edit_files' ),
			'level_10'         => array( 'level_10' ),
		);
	}

	public function test_subscriber_given_banned_capability_is_not_editable() {
		get_role( 'subscriber' )->add_cap( 'promote_users' );

		$this->assertNotContains( 'subscriber', UM()->roles()->get_editable_user_roles() );
	}

	public function test_banned_capabilities_list_is_filterable() {
		$this->create_um_role(
			'publisher',
			array(),
			array(
				'read'          => true,
				'publish_posts' => true,
			)
		);

		add_filter(
			'um_secure_register_form_banned_capabilities',
			static function ( $caps ) {
				$caps[] = 'publish_posts';
				return $caps;
			}
		);

		$this->assertNotContains( 'um_publisher', UM()->roles()->get_editable_user_roles() );
	}

	public function test_editable_priority_role_skips_roles_with_banned_capabilities() {
		$this->create_um_role(
			'staff',
			array( '_um_priority' => 100 ),
			array(
				'read'       => true,
				'edit_users' => true,
			)
		);
		$this->create_um_role( 'member', array( '_um_priority' => 1 ) );
		$user_id = $this->create_user_with_roles( array( 'um_staff', 'um_member' ) );

		$this->assertSame( 'um_staff', UM()->roles()->get_priority_user_role( $user_id ) );
		$this->assertSame( 'um_member', UM()->roles()->get_editable_priority_user_role( $user_id ) );
	}
}
