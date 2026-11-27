<?php
/**
 * UM user profile cache (`um_cache_userdata_{id}` options): profile data merged with the role
 * settings, reused by um_fetch_user() until it is removed.
 *
 * @package UM\Tests
 */

/**
 * Class UM_User_ProfileCacheTest
 *
 * @group user
 */
class UM_User_ProfileCacheTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $user_id;

	public function set_up() {
		parent::set_up();

		UM()->options()->update( 'um_profile_object_cache_stop', false );
		$this->user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
	}

	public function test_fetched_profile_is_cached_with_role_settings() {
		um_fetch_user( $this->user_id );

		$cached = UM()->user()->get_cached_data( $this->user_id );
		$this->assertIsArray( $cached );
		$this->assertSame( $this->user_id, (int) $cached['ID'] );
		$this->assertArrayHasKey( 'can_edit_profile', $cached, 'Role settings are part of the cached profile.' );
	}

	public function test_remove_cache_drops_the_user_cache() {
		um_fetch_user( $this->user_id );

		UM()->user()->remove_cache( $this->user_id );

		$this->assertSame( '', UM()->user()->get_cached_data( $this->user_id ) );
	}

	public function test_cache_can_be_disabled() {
		UM()->options()->update( 'um_profile_object_cache_stop', true );
		UM()->user()->remove_cache( $this->user_id ); // Cached on user creation.

		um_fetch_user( $this->user_id );

		$this->assertSame( '', UM()->user()->get_cached_data( $this->user_id ) );
		$this->assertFalse( get_option( 'um_cache_userdata_' . $this->user_id ) );
	}

	/**
	 * Saving a role in wp-admin (role-edit.php) calls remove_cache_all_users(). It deletes the
	 * `um_cache_userdata_*` options with a direct SQL query and doesn't touch the object cache, so
	 * get_option() keeps returning the old profiles. On sites with a persistent object cache (Redis,
	 * Memcached) users keep their old role permissions until the cache expires.
	 *
	 * @group known-bug
	 */
	public function test_remove_cache_all_users_also_clears_the_object_cache() {
		um_fetch_user( $this->user_id );
		$this->assertNotEmpty( UM()->user()->get_cached_data( $this->user_id ), 'Precondition: the profile is cached.' );

		UM()->user()->remove_cache_all_users();

		$this->assertSame( '', UM()->user()->get_cached_data( $this->user_id ) );
	}

	/**
	 * Role setting changes reach users whose profile was cached before the change.
	 *
	 * @group known-bug
	 */
	public function test_role_setting_change_applies_to_cached_profiles() {
		um_fetch_user( $this->user_id );
		$this->assertSame( 1, (int) um_user( 'can_delete_profile' ), 'Precondition.' );

		$meta                           = get_option( 'um_role_subscriber_meta' );
		$meta['_um_can_delete_profile'] = 0;
		update_option( 'um_role_subscriber_meta', $meta );
		UM()->user()->remove_cache_all_users(); // What wp-admin does after saving the role.
		um_reset_user_clean();

		um_fetch_user( $this->user_id );
		$this->assertSame( 0, (int) um_user( 'can_delete_profile' ) );
	}
}
