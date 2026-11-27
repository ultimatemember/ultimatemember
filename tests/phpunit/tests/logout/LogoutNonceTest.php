<?php
/**
 * Logout page nonce (2.14.4): visiting the Logout page logs the user out only with a valid
 * `log-out` nonce; without it the user is asked to confirm. Logout links built by UM and menu links
 * to the Logout page carry the nonce.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Logout_LogoutNonceTest
 *
 * @group logout
 * @group security
 */
class UM_Logout_LogoutNonceTest extends UM_UnitTestCase {

	/**
	 * @var int
	 */
	private $user_id;

	/**
	 * @var int
	 */
	private $logout_page_id;

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'login' );
		$this->logout_page_id = $this->create_core_page( 'logout' );
		$this->user_id        = $this->create_user_with_roles( 'subscriber' );
	}

	public function tear_down() {
		unset( $GLOBALS['post'] );
		parent::tear_down();
	}

	/**
	 * Visit the Logout page.
	 *
	 * @param array $request Request parameters (`_wpnonce`, `redirect_to`).
	 *
	 * @return array {
	 *     @type string $result   `redirect` or `confirm` (wp_die() confirmation screen).
	 *     @type string $location Redirect location.
	 *     @type string $message  Confirmation screen.
	 * }
	 */
	private function visit_logout( array $request ) {
		$GLOBALS['post'] = get_post( $this->logout_page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The visitor is on the Logout page.
		$_GET            = $request;
		$_REQUEST        = $request;

		try {
			$location = $this->capture_redirect(
				static function () {
					UM()->logout()->logout_page();
				}
			);
			return array(
				'result'   => 'redirect',
				'location' => $location,
				'message'  => '',
			);
		} catch ( WPDieException $e ) {
			return array(
				'result'   => 'confirm',
				'location' => '',
				'message'  => $e->getMessage(),
			);
		}
	}

	/**
	 * @param string $url URL.
	 *
	 * @return array Query arguments.
	 */
	private function query_args( $url ) {
		wp_parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $args );
		return $args;
	}

	public function test_valid_nonce_logs_out() {
		$this->act_as( $this->user_id );

		$result = $this->visit_logout( array( '_wpnonce' => wp_create_nonce( 'log-out' ) ) );

		$this->assertSame( 'redirect', $result['result'] );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * @return array[]
	 */
	public function data_invalid_nonces() {
		return array(
			'nonce missing'           => array( null ),
			'garbage nonce'           => array( 'abc123' ),
			'nonce of another action' => array( 'um_login_form' ),
		);
	}

	/**
	 * CSRF: a link or an image on another site can't log the user out. The user is asked to confirm.
	 *
	 * @dataProvider data_invalid_nonces
	 *
	 * @param string|null $nonce_action Action to create the nonce for, a raw value, or null to omit it.
	 */
	public function test_invalid_nonce_asks_for_confirmation( $nonce_action ) {
		$this->act_as( $this->user_id );
		$request = array();
		if ( 'um_login_form' === $nonce_action ) {
			$request['_wpnonce'] = wp_create_nonce( 'um_login_form' );
		} elseif ( null !== $nonce_action ) {
			$request['_wpnonce'] = $nonce_action;
		}

		$result = $this->visit_logout( $request );

		$this->assertSame( 'confirm', $result['result'] );
		$this->assertSame( $this->user_id, get_current_user_id(), 'The user must stay logged in.' );
		$this->assertStringContainsString( 'Do you really want to', $result['message'] );
	}

	/**
	 * The confirmation link carries a valid nonce and keeps `redirect_to`.
	 */
	public function test_confirmation_link_logs_out() {
		$this->act_as( $this->user_id );
		$result = $this->visit_logout( array( 'redirect_to' => home_url( '/see-you/' ) ) );
		$this->assertSame( 1, preg_match( '/href="([^"]+)"/', $result['message'], $m ), 'The confirmation screen must contain the logout link.' );

		$args = $this->query_args( html_entity_decode( $m[1] ) );
		$this->assertSame( 1, wp_verify_nonce( $args['_wpnonce'], 'log-out' ) );
		$this->assertSame( home_url( '/see-you/' ), $args['redirect_to'] );

		$result = $this->visit_logout( $args );

		$this->assertSame( 'redirect', $result['result'] );
		$this->assertSame( home_url( '/see-you/' ), $result['location'] );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * The confirmation screen doesn't turn `redirect_to` into an open redirect or markup.
	 */
	public function test_confirmation_link_escapes_redirect_to() {
		$this->act_as( $this->user_id );

		$result = $this->visit_logout( array( 'redirect_to' => '"><script>alert(1)</script>' ) );

		$this->assertSame( 'confirm', $result['result'] );
		$this->assertStringNotContainsString( '<script>', $result['message'] );
	}

	public function test_nonce_verification_can_be_disabled() {
		add_filter( 'um_logout_allow_nonce_verification', '__return_false' );
		$this->act_as( $this->user_id );

		$result = $this->visit_logout( array() );

		$this->assertSame( 'redirect', $result['result'] );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * Guests are still sent to the homepage, no confirmation.
	 */
	public function test_guest_is_redirected_home() {
		$result = $this->visit_logout( array() );

		$this->assertSame( 'redirect', $result['result'] );
		$this->assertSame( home_url(), $result['location'] );
	}

	public function test_um_get_logout_url() {
		$this->act_as( $this->user_id );

		$url  = um_get_logout_url( home_url( '/after/?a=1&b=2' ) );
		$args = $this->query_args( $url );

		$this->assertStringStartsWith( um_get_core_page( 'logout' ), $url );
		$this->assertSame( 1, wp_verify_nonce( $args['_wpnonce'], 'log-out' ) );
		$this->assertSame( home_url( '/after/?a=1&b=2' ), $args['redirect_to'], 'redirect_to must survive with its own query arguments.' );
		$this->assertArrayNotHasKey( 'redirect_to', $this->query_args( um_get_logout_url() ) );
	}

	/**
	 * Logout links rendered by UM: profile menu, logged-in widget (login form) and the own directory card.
	 */
	public function test_um_logout_links_carry_the_nonce() {
		$this->act_as( $this->user_id );

		ob_start();
		do_action( 'um_logout_user_links', array() );
		$widget = ob_get_clean();
		$this->assertSame( 1, preg_match( '/href="([^"]*_wpnonce[^"]*)"/', $widget, $m ), 'Logout link of the logged-in widget.' );
		$this->assertSame( 1, wp_verify_nonce( $this->query_args( html_entity_decode( $m[1] ) )['_wpnonce'], 'log-out' ) );

		$actions = UM()->member_directory()->build_user_card_data( $this->user_id, array() )['dropdown_actions'];
		$this->assertArrayHasKey( 'um-logout', $actions );
		$this->assertSame( 1, wp_verify_nonce( $this->query_args( $actions['um-logout']['url'] )['_wpnonce'], 'log-out' ) );
	}

	/**
	 * Classic menus: links to the Logout page get the nonce, other links stay unchanged.
	 */
	public function test_menu_links_to_logout_page_get_the_nonce() {
		$this->act_as( $this->user_id );
		$item = (object) array( 'ID' => 1 );

		$logout = apply_filters( 'nav_menu_link_attributes', array( 'href' => get_permalink( $this->logout_page_id ) ), $item, (object) array(), 0 );
		$other  = apply_filters( 'nav_menu_link_attributes', array( 'href' => home_url( '/about/' ) ), $item, (object) array(), 0 );

		$this->assertSame( 1, wp_verify_nonce( $this->query_args( $logout['href'] )['_wpnonce'], 'log-out' ) );
		$this->assertSame( home_url( '/about/' ), $other['href'] );
	}

	public function test_custom_menu_link_keeps_its_redirect_to() {
		$this->act_as( $this->user_id );
		$href = add_query_arg( 'redirect_to', rawurlencode( home_url( '/bye/' ) ), get_permalink( $this->logout_page_id ) );

		$atts = apply_filters( 'nav_menu_link_attributes', array( 'href' => $href ), (object) array( 'ID' => 1 ), (object) array(), 0 );
		$args = $this->query_args( $atts['href'] );

		$this->assertSame( home_url( '/bye/' ), $args['redirect_to'] );
		$this->assertSame( 1, wp_verify_nonce( $args['_wpnonce'], 'log-out' ) );
	}

	public function test_menu_links_are_unchanged_for_guests() {
		$href = get_permalink( $this->logout_page_id );

		$atts = apply_filters( 'nav_menu_link_attributes', array( 'href' => $href ), (object) array( 'ID' => 1 ), (object) array(), 0 );

		$this->assertSame( $href, $atts['href'] );
	}

	/**
	 * Navigation block (block themes): navigation links to the Logout page get the nonce.
	 */
	public function test_navigation_block_links_get_the_nonce() {
		$this->act_as( $this->user_id );

		$block = apply_filters(
			'render_block_data',
			array(
				'blockName' => 'core/navigation-link',
				'attrs'     => array(
					'url'  => get_permalink( $this->logout_page_id ),
					'kind' => 'post-type',
					'id'   => $this->logout_page_id,
				),
			),
			array(),
			null
		);

		$this->assertSame( 1, wp_verify_nonce( $this->query_args( $block['attrs']['url'] )['_wpnonce'], 'log-out' ) );
	}

	/**
	 * Pretty permalinks: the Logout page URL is recognized with or without the trailing slash.
	 */
	public function test_logout_page_url_detection_with_pretty_permalinks() {
		$this->set_permalink_structure( '/%postname%/' );
		$url = get_permalink( $this->logout_page_id );

		$this->assertTrue( UM()->logout()->is_logout_page_url( $url ) );
		$this->assertTrue( UM()->logout()->is_logout_page_url( untrailingslashit( $url ) ) );
		$this->assertTrue( UM()->logout()->is_logout_page_url( add_query_arg( 'redirect_to', 'x', $url ) ) );
		$this->assertFalse( UM()->logout()->is_logout_page_url( home_url( '/logout-help/' ) ) );
		$this->assertFalse( UM()->logout()->is_logout_page_url( 'https://other.example/logout/' ) );
	}
}
