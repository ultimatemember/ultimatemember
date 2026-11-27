<?php
/**
 * Logout page: logs the user out and redirects by `redirect_to`, the role setting
 * "Action to be taken after logout" or the `um_logout_redirect_url` filter.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Logout_LogoutPageTest
 *
 * @group logout
 */
class UM_Logout_LogoutPageTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $user_id;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'login' );
		$this->set_current_page( $this->create_core_page( 'logout' ) ); // The visitor is on the Logout page.

		$this->user_id = $this->create_user_with_roles( 'subscriber' );
	}

	/**
	 * Visit the Logout page.
	 *
	 * @param array $request Request parameters (`redirect_to`).
	 *
	 * @return string Redirect location.
	 */
	private function visit_logout( array $request = array() ) {
		$_REQUEST = $request;
		$_GET     = $request;

		return $this->capture_redirect(
			static function () {
				UM()->logout()->logout_page();
			}
		);
	}

	public function test_logs_out_and_redirects_home_by_default() {
		$this->act_as( $this->user_id );

		$location = $this->visit_logout();

		$this->assertSame( 0, get_current_user_id() );
		$this->assertSame( home_url(), $location );
	}

	public function test_redirect_to_is_used() {
		$this->act_as( $this->user_id );

		$location = $this->visit_logout( array( 'redirect_to' => home_url( '/see-you/' ) ) );

		$this->assertSame( 0, get_current_user_id() );
		$this->assertSame( home_url( '/see-you/' ), $location );
	}

	/**
	 * An off-site `redirect_to` is replaced with a local URL (no open redirect).
	 *
	 * @group security
	 */
	public function test_redirect_to_cannot_point_off_site() {
		$this->act_as( $this->user_id );

		$location = $this->visit_logout( array( 'redirect_to' => 'https://attacker.invalid/phish' ) );

		$this->assertStringNotContainsString( 'attacker.invalid', $location );
		$this->assertStringStartsWith( home_url(), $location );
		$this->assertSame( 0, get_current_user_id() );
	}

	public function test_role_logout_redirect_url() {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_after_logout'        => 'redirect_url',
				'_um_logout_redirect_url' => home_url( '/goodbye/' ),
			)
		);
		$this->act_as( $this->user_id );

		$this->assertSame( home_url( '/goodbye/' ), $this->visit_logout() );
	}

	public function test_logout_redirect_url_is_filterable() {
		$this->update_role_meta( 'subscriber', array( '_um_after_logout' => 'redirect_url' ) );
		$this->act_as( $this->user_id );
		add_filter(
			'um_logout_redirect_url',
			static function () {
				return home_url( '/filtered/' );
			}
		);

		$this->assertSame( home_url( '/filtered/' ), $this->visit_logout() );
	}

	/**
	 * @group security
	 */
	public function test_role_logout_redirect_url_cannot_point_off_site() {
		$this->update_role_meta(
			'subscriber',
			array(
				'_um_after_logout'        => 'redirect_url',
				'_um_logout_redirect_url' => 'https://attacker.invalid/',
			)
		);
		$this->act_as( $this->user_id );

		$this->assertStringNotContainsString( 'attacker.invalid', $this->visit_logout() );
	}

	public function test_logged_out_visitor_is_redirected_home() {
		$this->assertSame( home_url(), $this->visit_logout() );
	}

	public function test_logout_fires_wp_logout() {
		$this->act_as( $this->user_id );
		$before = did_action( 'wp_logout' );

		$this->visit_logout();

		$this->assertSame( $before + 1, did_action( 'wp_logout' ) );
	}

	public function test_other_pages_do_not_log_out() {
		$this->act_as( $this->user_id );
		$this->set_current_page( self::factory()->post->create( array( 'post_type' => 'page' ) ) );

		UM()->logout()->logout_page();

		$this->assertSame( $this->user_id, get_current_user_id() );
	}
}
