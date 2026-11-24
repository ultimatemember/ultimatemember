<?php
/**
 * Base test case for Ultimate Member.
 *
 * @package UM\Tests
 */

/**
 * Class UM_UnitTestCase
 *
 * Adds helpers for UM roles and for acting as a given user. Every test runs inside a DB
 * transaction (WP_UnitTestCase), so options and users created here are rolled back.
 */
abstract class UM_UnitTestCase extends WP_UnitTestCase {

	/**
	 * Reset in-memory state that survives the DB rollback between tests.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( 0 );
		$this->reload_roles();
	}

	/**
	 * Create a custom UM role the same way wp-admin > Ultimate Member > User Roles does:
	 * the role key goes to the `um_roles` option and the settings to `um_role_{key}_meta`.
	 *
	 * @param string $key     Role key without the `um_` prefix.
	 * @param array  $meta    UM role settings, e.g. `_um_can_edit_everyone`, `_um_priority`.
	 * @param array  $wp_caps WordPress capabilities of the role.
	 *
	 * @return string Role ID (`um_{key}`).
	 */
	protected function create_um_role( $key, array $meta = array(), array $wp_caps = array( 'read' => true ) ) {
		$role_keys   = get_option( 'um_roles', array() );
		$role_keys[] = $key;
		update_option( 'um_roles', array_values( array_unique( $role_keys ) ) );

		$defaults = array_merge(
			UM()->config()->default_roles_metadata['subscriber'],
			array(
				'name'            => ucwords( str_replace( array( '_', '-' ), ' ', $key ) ),
				'_um_is_custom'   => 1,
				'_um_priority'    => 0,
				'wp_capabilities' => $wp_caps,
			)
		);

		update_option( "um_role_{$key}_meta", array_merge( $defaults, $meta ) );
		$this->reload_roles();

		return 'um_' . $key;
	}

	/**
	 * Change UM settings of an existing role (built-in WP role or custom UM role).
	 *
	 * @param string $role_id Role ID, e.g. `subscriber` or `um_manager`.
	 * @param array  $meta    UM role settings to merge.
	 */
	protected function update_role_meta( $role_id, array $meta ) {
		$key  = 0 === strpos( $role_id, 'um_' ) ? substr( $role_id, 3 ) : $role_id;
		$data = get_option( "um_role_{$key}_meta", array() );

		update_option( "um_role_{$key}_meta", array_merge( (array) $data, $meta ) );
		$this->reload_roles();
	}

	/**
	 * Create a user with one or more roles.
	 *
	 * @param string|string[] $roles Role ID(s). The first one is the primary role.
	 *
	 * @return int User ID.
	 */
	protected function create_user_with_roles( $roles ) {
		$roles   = (array) $roles;
		$user_id = self::factory()->user->create( array( 'role' => array_shift( $roles ) ) );

		$user = get_userdata( $user_id );
		foreach ( $roles as $role ) {
			$user->add_role( $role );
		}

		UM()->user()->remove_cache( $user_id );

		return $user_id;
	}

	/**
	 * Log in as the given user, the way a request would: WP current user + UM current profile.
	 *
	 * @param int $user_id User ID, 0 for a logged-out visitor.
	 */
	protected function act_as( $user_id ) {
		wp_set_current_user( $user_id );
		UM()->user()->remove_cache( $user_id );
		um_reset_user();
	}

	/**
	 * Re-run `wp_roles_init` so UM roles from the options are (re)registered in `$wp_roles`,
	 * and drop UM user caches that hold role permissions.
	 */
	protected function reload_roles() {
		wp_roles()->for_site();
		UM()->user()->remove_cache_all_users();
		um_reset_user_clean();
	}
}
