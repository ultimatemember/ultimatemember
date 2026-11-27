<?php
/**
 * Member directory with a realistic set of "dummy" users (UM_Test_Dummy_Users): who is listed for
 * guests and administrators, role / photo / cover settings, country filter, search, sorting and
 * pagination over many pages. Expected results are computed from the dummy user definitions.
 *
 * @package UM\Tests
 */

/**
 * Class UM_MemberDirectory_DummyUsersDirectoryTest
 *
 * @group member-directory
 */
class UM_MemberDirectory_DummyUsersDirectoryTest extends UM_UnitTestCase {

	/**
	 * Dummy user definitions keyed by login.
	 *
	 * @var array[]
	 */
	private static $dummies = array();

	/**
	 * Dummy user IDs keyed by login.
	 *
	 * @var int[]
	 */
	private static $ids = array();

	/**
	 * Roles of the dummy users; directories are limited to them so other users don't interfere.
	 *
	 * @var string[]
	 */
	private static $dummy_roles = array( 'subscriber', 'contributor', 'author', 'editor' );

	/**
	 * Create the users once for the class (deleted after the class by the WP test suite).
	 *
	 * @param WP_UnitTest_Factory $factory Factory.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$dummies = UM_Test_Dummy_Users::definitions();
		self::$ids     = UM_Test_Dummy_Users::create( $factory, self::$dummies );
	}

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'user' );
		UM()->options()->update( 'ajax_nopriv_rate_limit', false );
	}

	/**
	 * @param array $meta Directory settings to add.
	 *
	 * @return string Directory hash.
	 */
	private function directory( array $meta = array() ) {
		return $this->create_member_directory(
			array_merge(
				array(
					'_um_roles'             => self::$dummy_roles,
					'_um_profiles_per_page' => 100,
				),
				$meta
			)
		);
	}

	/**
	 * @param string $hash   Directory hash.
	 * @param array  $params Request parameters.
	 *
	 * @return string[] Logins of the listed users, in the listed order.
	 */
	private function listed( $hash, array $params = array() ) {
		$logins = array();
		foreach ( $this->directory_user_ids( $this->get_directory_members( $hash, $params ), self::$ids ) as $user_id ) {
			$this->assertNotSame( 0, $user_id, 'A user that is not a dummy user is listed.' );
			$logins[] = array_search( $user_id, self::$ids, true );
		}

		return $logins;
	}

	/**
	 * @param callable|null $filter Extra condition on the definition.
	 *
	 * @return array[] Definitions of users visible to guests and members, newest first.
	 */
	private function expected_visible( $filter = null ) {
		$visible = array_filter(
			self::$dummies,
			static function ( $user ) use ( $filter ) {
				return 'approved' === $user['status'] && ! $user['hidden'] && ( null === $filter || $filter( $user ) );
			}
		);
		uasort(
			$visible,
			static function ( $a, $b ) {
				return $b['registered'] - $a['registered'];
			}
		);

		return $visible;
	}

	public function test_dummy_users_cover_every_case() {
		$statuses = array_unique( wp_list_pluck( self::$dummies, 'status' ) );
		sort( $statuses );

		$this->assertSame( array( 'approved', 'awaiting_admin_review', 'awaiting_email_confirmation', 'inactive', 'rejected' ), $statuses );
		$this->assertContains( true, wp_list_pluck( self::$dummies, 'hidden' ) );
		$this->assertContains( true, wp_list_pluck( self::$dummies, 'photo' ) );
		$this->assertContains( true, wp_list_pluck( self::$dummies, 'cover' ) );
		$this->assertContains( null, wp_list_pluck( self::$dummies, 'last_login' ) );
	}

	/**
	 * Guests see approved members who don't hide their profile, newest first (the default sorting).
	 */
	public function test_guest_sees_approved_not_hidden_members_newest_first() {
		$this->assertSame( array_keys( $this->expected_visible() ), $this->listed( $this->directory() ) );
	}

	public function test_member_sees_the_same_list_as_guests() {
		$this->act_as( self::$ids['dummy00'] );

		$this->assertSame( array_keys( $this->expected_visible() ), $this->listed( $this->directory() ) );
	}

	/**
	 * Administrators (can edit users) also see not approved members and hidden profiles.
	 */
	public function test_administrator_sees_every_member() {
		$this->act_as( $this->create_user_with_roles( 'administrator' ) );

		$listed = $this->listed( $this->directory() );
		sort( $listed );

		$this->assertSame( array_keys( self::$dummies ), $listed );
	}

	/**
	 * With "Allow users to hide their profiles from directory" disabled, hidden profiles are listed.
	 */
	public function test_hidden_profiles_are_listed_when_hiding_is_disabled() {
		UM()->options()->update( 'account_hide_in_directory', false );

		$expected = array_filter(
			self::$dummies,
			static function ( $user ) {
				return 'approved' === $user['status'];
			}
		);
		$listed   = $this->listed( $this->directory() );
		sort( $listed );

		$this->assertSame( array_keys( $expected ), $listed );
	}

	/**
	 * Every page shows the next members without overlaps or gaps; totals match.
	 */
	public function test_pagination_walks_through_all_members() {
		$hash     = $this->directory( array( '_um_profiles_per_page' => 7 ) );
		$expected = array_keys( $this->expected_visible() );

		$first = $this->get_directory_members( $hash );
		$this->assertSame( count( $expected ), (int) $first['data']['pagination']['total_users'] );
		$this->assertSame( (int) ceil( count( $expected ) / 7 ), (int) $first['data']['pagination']['total_pages'] );

		$all = array();
		for ( $page = 1; $page <= $first['data']['pagination']['total_pages']; $page++ ) {
			$page_logins = $this->listed( $hash, array( 'page' => $page ) );
			$this->assertLessThanOrEqual( 7, count( $page_logins ) );
			$all = array_merge( $all, $page_logins );
		}

		$this->assertSame( $expected, $all );
	}

	/**
	 * A page after the last one is empty.
	 */
	public function test_page_after_the_last_is_empty() {
		$hash = $this->directory( array( '_um_profiles_per_page' => 10 ) );

		$this->assertSame( array(), $this->listed( $hash, array( 'page' => 99 ) ) );
	}

	public function test_max_users_limits_the_total() {
		$hash = $this->directory(
			array(
				'_um_max_users'         => 5,
				'_um_profiles_per_page' => 5,
			)
		);

		$this->assertSame( array_slice( array_keys( $this->expected_visible() ), 0, 5 ), $this->listed( $hash ) );
	}

	/**
	 * @return array[]
	 */
	public function data_roles() {
		return array(
			'subscribers'         => array( array( 'subscriber' ) ),
			'authors'             => array( array( 'author' ) ),
			'editors and authors' => array( array( 'editor', 'author' ) ),
			'contributors'        => array( array( 'contributor' ) ),
		);
	}

	/**
	 * Directory setting "User Roles to display".
	 *
	 * @dataProvider data_roles
	 *
	 * @param string[] $roles Roles.
	 */
	public function test_only_selected_roles_are_listed( array $roles ) {
		$expected = $this->expected_visible(
			static function ( $user ) use ( $roles ) {
				return in_array( $user['role'], $roles, true );
			}
		);

		$this->assertSame( array_keys( $expected ), $this->listed( $this->directory( array( '_um_roles' => $roles ) ) ) );
	}

	/**
	 * @return array[]
	 */
	public function data_photo_settings() {
		return array(
			'with profile photo'   => array( array( '_um_has_profile_photo' => 1 ), true, false ),
			'with cover photo'     => array( array( '_um_has_cover_photo' => 1 ), false, true ),
			'with photo and cover' => array(
				array(
					'_um_has_profile_photo' => 1,
					'_um_has_cover_photo'   => 1,
				),
				true,
				true,
			),
		);
	}

	/**
	 * Directory settings "Only show members who have uploaded a profile photo / cover photo".
	 *
	 * @dataProvider data_photo_settings
	 *
	 * @param array $meta  Directory settings.
	 * @param bool  $photo Profile photo required.
	 * @param bool  $cover Cover photo required.
	 */
	public function test_only_members_with_photos( array $meta, $photo, $cover ) {
		$expected = $this->expected_visible(
			static function ( $user ) use ( $photo, $cover ) {
				return ( ! $photo || $user['photo'] ) && ( ! $cover || $user['cover'] );
			}
		);
		$this->assertNotEmpty( $expected, 'Precondition: the dummy users contain such members.' );

		$this->assertSame( array_keys( $expected ), $this->listed( $this->directory( $meta ) ) );
	}

	/**
	 * @return array[]
	 */
	public function data_country_filters() {
		return array(
			'one country'   => array( array( 'Poland' ) ),
			'two countries' => array( array( 'Ukraine', 'Canada' ) ),
			'no match'      => array( array( 'Japan' ) ),
		);
	}

	/**
	 * Front-end filter by the predefined "Country" field.
	 *
	 * @dataProvider data_country_filters
	 *
	 * @param string[] $countries Selected countries.
	 */
	public function test_country_filter( array $countries ) {
		$hash     = $this->directory(
			array(
				'_um_filters'       => 1,
				'_um_search_fields' => array( 'country' ),
			)
		);
		$expected = $this->expected_visible(
			static function ( $user ) use ( $countries ) {
				return in_array( $user['country'], $countries, true );
			}
		);

		$response = $this->get_directory_members( $hash, array( 'country' => $countries ) );

		$this->assertTrue( $response['data']['is_search'] );
		$this->assertSame( array_keys( $expected ), $this->listed( $hash, array( 'country' => $countries ) ) );
	}

	/**
	 * Fields that aren't configured as filters of the directory are ignored.
	 *
	 * @group security
	 */
	public function test_not_configured_filter_is_ignored() {
		$hash = $this->directory( array( '_um_filters' => 1 ) );

		$this->assertSame(
			array_keys( $this->expected_visible() ),
			$this->listed(
				$hash,
				array(
					'country'        => array( 'Poland' ),
					'account_status' => array( 'rejected' ),
				)
			)
		);
	}

	public function test_search_by_last_name() {
		$hash     = $this->directory( array( '_um_search' => 1 ) );
		$expected = $this->expected_visible(
			static function ( $user ) {
				return 0 === strpos( $user['last_name'], 'Melnyk' );
			}
		);
		$this->assertNotEmpty( $expected, 'Precondition.' );

		$this->assertSame( array_keys( $expected ), $this->listed( $hash, array( 'search' => 'Melnyk' ) ) );
	}

	/**
	 * Searching by the name of a not approved member finds nothing.
	 *
	 * @group security
	 */
	public function test_search_does_not_reveal_not_approved_members() {
		$hash = $this->directory( array( '_um_search' => 1 ) );
		$this->assertSame( 'rejected', self::$dummies['dummy09']['status'], 'Precondition.' );

		$this->assertSame( array(), $this->listed( $hash, array( 'search' => self::$dummies['dummy09']['last_name'] ) ) );
	}

	public function test_sort_by_username() {
		$expected = array_keys( $this->expected_visible() );
		sort( $expected );

		$this->assertSame( $expected, $this->listed( $this->directory( array( '_um_sortby' => 'username' ) ) ) );
	}

	public function test_sort_by_oldest_registration() {
		$this->assertSame(
			array_reverse( array_keys( $this->expected_visible() ) ),
			$this->listed( $this->directory( array( '_um_sortby' => 'user_registered_asc' ) ) )
		);
	}

	public function test_sort_by_first_name() {
		$expected = wp_list_pluck( $this->expected_visible(), 'first_name' );
		sort( $expected );

		$listed = array();
		foreach ( $this->listed( $this->directory( array( '_um_sortby' => 'first_name' ) ) ) as $login ) {
			$listed[] = self::$dummies[ $login ]['first_name'];
		}

		$this->assertSame( array_values( $expected ), $listed );
	}

	/**
	 * Members who logged in most recently first; members who never logged in at the end.
	 */
	public function test_sort_by_last_login() {
		$visible   = $this->expected_visible();
		$logged_in = array_filter(
			$visible,
			static function ( $user ) {
				return null !== $user['last_login'];
			}
		);
		uasort(
			$logged_in,
			static function ( $a, $b ) {
				return $b['last_login'] - $a['last_login'];
			}
		);
		$never = array_diff( array_keys( $visible ), array_keys( $logged_in ) );

		$listed = $this->listed( $this->directory( array( '_um_sortby' => 'last_login' ) ) );

		$this->assertSame( array_keys( $logged_in ), array_slice( $listed, 0, count( $logged_in ) ) );
		$rest = array_slice( $listed, count( $logged_in ) );
		sort( $rest );
		sort( $never );
		$this->assertSame( array_values( $never ), $rest );
	}

	/**
	 * Visitors may switch the sorting only when sorting is enabled for the directory and only to
	 * one of the configured options.
	 */
	public function test_visitor_selected_sorting() {
		$hash     = $this->directory(
			array(
				'_um_enable_sorting' => 1,
				'_um_sorting_fields' => array( 'username', 'user_registered_desc' ),
			)
		);
		$expected = array_keys( $this->expected_visible() );
		sort( $expected );

		$this->assertSame( $expected, $this->listed( $hash, array( 'sorting' => 'username' ) ) );
	}

	/**
	 * Role setting "Can view these user roles only" with the dummy users.
	 */
	public function test_member_role_that_can_view_only_editors() {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_can_view_all'   => 1,
				'_um_can_view_roles' => array( 'editor' ),
			)
		);
		$this->act_as( self::$ids['dummy00'] );
		$expected = $this->expected_visible(
			static function ( $user ) {
				return 'editor' === $user['role'];
			}
		);

		$this->assertSame( array_keys( $expected ), $this->listed( $this->directory() ) );
	}

	/**
	 * Role setting "Can view other member profiles?" off: the directory is empty for the member.
	 */
	public function test_member_role_that_cannot_view_others_sees_nobody() {
		$this->update_role_meta( 'subscriber', array( '_um_can_view_all' => 0 ) );
		$this->act_as( self::$ids['dummy00'] );

		$response = $this->get_directory_members( $this->directory() );

		$this->assertSame( array(), $response['data']['users'] );
		$this->assertSame( 0, (int) $response['data']['pagination']['total_users'] );
	}

	/**
	 * "Show only these users" doesn't override the account status or the hidden profile.
	 *
	 * @group security
	 */
	public function test_show_only_these_users_keeps_visibility_rules() {
		$picked = array( 'dummy01', 'dummy09', 'dummy11', 'dummy12' ); // approved, rejected, hidden, approved.
		$this->assertSame( 'rejected', self::$dummies['dummy09']['status'], 'Precondition.' );
		$this->assertTrue( self::$dummies['dummy11']['hidden'], 'Precondition.' );

		$listed = $this->listed( $this->directory( array( '_um_show_these_users' => $picked ) ) );
		sort( $listed );

		$this->assertSame( array( 'dummy01', 'dummy12' ), $listed );
	}
}
