<?php
/**
 * Content restriction: per-post "Ultimate Member: Content Restriction" settings, restricted
 * content message, hiding from queries, redirects for single posts, term restriction, restricted
 * blocks and the [um_loggedin] / [um_loggedout] / [um_show_content] shortcodes.
 *
 * Note: UM()->access() caches results per request in static variables keyed by post ID
 * (is_restricted(), get_post_privacy_settings()) and by post type (exclude_posts_array()).
 * Every test therefore uses new posts, one visitor per post, and a post type of its own when the
 * query exclusion is checked.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Access_ContentRestrictionTest
 *
 * @group access
 */
class UM_Access_ContentRestrictionTest extends UM_UnitTestCase {

	const MESSAGE = 'Members only, sorry.';

	public function set_up() {
		parent::set_up();

		$this->create_core_page( 'login' );
		$this->create_um_role( 'student' );

		UM()->options()->update(
			'restricted_access_post_metabox',
			array(
				'post' => 1,
				'page' => 1,
			)
		);
		UM()->options()->update( 'restricted_access_taxonomy_metabox', array( 'category' => 1 ) );
		UM()->options()->update( 'restricted_access_message', self::MESSAGE );
	}

	/**
	 * Create a post with "Ultimate Member: Content Restriction" settings, as saved by the metabox.
	 *
	 * @param array  $settings  Restriction settings (`_um_accessible`, `_um_access_roles`…).
	 * @param string $post_type Post type.
	 * @param array  $postarr   Post data to add.
	 *
	 * @return int Post ID.
	 */
	private function create_restricted_post( array $settings, $post_type = 'post', array $postarr = array() ) {
		$post_id = self::factory()->post->create(
			array_merge(
				array(
					'post_type'    => $post_type,
					'post_status'  => 'publish',
					'post_title'   => 'Secret post',
					'post_content' => 'The secret content.',
					'post_author'  => self::factory()->user->create( array( 'role' => 'editor' ) ),
				),
				$postarr
			)
		);

		update_post_meta( $post_id, 'um_content_restriction', array_merge( array( '_um_custom_access_settings' => '1' ), $settings ) );

		return $post_id;
	}

	/**
	 * A post type used by a single test, so the per-request exclusion cache doesn't leak between tests.
	 *
	 * @return string Post type.
	 */
	private function register_test_post_type() {
		$post_type = 'umt_' . substr( md5( $this->getName() . wp_rand() ), 0, 12 );
		register_post_type(
			$post_type,
			array(
				'public'      => true,
				'has_archive' => true,
			)
		);

		$restricted               = UM()->options()->get( 'restricted_access_post_metabox' );
		$restricted[ $post_type ] = 1;
		UM()->options()->update( 'restricted_access_post_metabox', $restricted );

		return $post_type;
	}

	/**
	 * @param string|null $visitor `guest`, a role ID, or `author`/`administrator`.
	 * @param int         $post_id Post, for `author`.
	 */
	private function visit_as( $visitor, $post_id = 0 ) {
		if ( 'guest' === $visitor ) {
			$this->act_as( 0 );
		} elseif ( 'author' === $visitor ) {
			$this->act_as( (int) get_post( $post_id )->post_author );
		} else {
			$this->act_as( $this->create_user_with_roles( $visitor ) );
		}
	}

	/**
	 * @return array[]
	 */
	public function data_access_matrix() {
		$everyone  = array( '_um_accessible' => '0' );
		$guests    = array( '_um_accessible' => '1' );
		$logged_in = array( '_um_accessible' => '2' );
		$students  = array(
			'_um_accessible'   => '2',
			'_um_access_roles' => array( 'um_student' => '1' ),
		);

		return array(
			'everyone, guest'            => array( $everyone, 'guest', false ),
			'everyone, member'           => array( $everyone, 'subscriber', false ),
			'guests only, guest'         => array( $guests, 'guest', false ),
			'guests only, member'        => array( $guests, 'subscriber', true ),
			'logged in, guest'           => array( $logged_in, 'guest', true ),
			'logged in, member'          => array( $logged_in, 'subscriber', false ),
			'students, guest'            => array( $students, 'guest', true ),
			'students, subscriber'       => array( $students, 'subscriber', true ),
			'students, student'          => array( $students, 'um_student', false ),
			'students, administrator'    => array( $students, 'administrator', false ),
			'students, post author'      => array( $students, 'author', false ),
			'guests only, administrator' => array( $guests, 'administrator', false ),
		);
	}

	/**
	 * Who can see a post with "Who can access this content?" settings.
	 *
	 * @group security
	 * @dataProvider data_access_matrix
	 *
	 * @param array  $settings   Restriction settings.
	 * @param string $visitor    Visitor.
	 * @param bool   $restricted Expected result.
	 */
	public function test_access_matrix( array $settings, $visitor, $restricted ) {
		$post_id = $this->create_restricted_post( $settings );
		$this->visit_as( $visitor, $post_id );

		$this->assertSame( $restricted, UM()->access()->is_restricted( $post_id ) );
	}

	public function test_post_without_settings_is_not_restricted() {
		$post_id = self::factory()->post->create();

		$this->assertFalse( UM()->access()->is_restricted( $post_id ) );
	}

	/**
	 * Settings are ignored for post types not enabled in Settings > Access > Restriction Content.
	 */
	public function test_settings_are_ignored_for_not_enabled_post_types() {
		UM()->options()->update( 'restricted_access_post_metabox', array( 'page' => 1 ) );
		$post_id = $this->create_restricted_post( array( '_um_accessible' => '2' ) );

		$this->assertFalse( UM()->access()->is_restricted( $post_id ) );
	}

	/**
	 * @return array[]
	 */
	public function data_restricted_messages() {
		return array(
			'global message' => array( array(), self::MESSAGE ),
			'custom message' => array(
				array(
					'_um_restrict_by_custom_message' => '1',
					'_um_restrict_custom_message'    => 'Please join the club.',
				),
				'Please join the club.',
			),
		);
	}

	/**
	 * The post content is replaced with the restriction message.
	 *
	 * @group security
	 * @dataProvider data_restricted_messages
	 *
	 * @param array  $settings Message settings.
	 * @param string $message  Expected message.
	 */
	public function test_restricted_content_is_replaced_with_message( array $settings, $message ) {
		$post_id = $this->create_restricted_post( array_merge( array( '_um_accessible' => '2' ), $settings ) );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$content = apply_filters( 'the_content', get_post( $post_id )->post_content );

		$this->assertStringContainsString( $message, $content );
		$this->assertStringNotContainsString( 'The secret content.', $content );
		$this->assertStringNotContainsString( 'The secret content.', get_the_excerpt( $post_id ) );
	}

	public function test_allowed_visitor_sees_the_content() {
		$post_id = $this->create_restricted_post( array( '_um_accessible' => '2' ) );
		$this->act_as( $this->create_user_with_roles( 'subscriber' ) );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		$this->assertStringContainsString( 'The secret content.', apply_filters( 'the_content', get_post( $post_id )->post_content ) );
	}

	/**
	 * Restricted posts are listed with the replaced title ("Restricted content") by default.
	 */
	public function test_restricted_post_title_is_replaced_in_lists() {
		$post_type = $this->register_test_post_type();
		$post_id   = $this->create_restricted_post( array( '_um_accessible' => '2' ), $post_type );

		$query = new WP_Query(
			array(
				'post_type'     => $post_type,
				'um_main_query' => true,
			)
		);

		$this->assertSame( array( $post_id ), wp_list_pluck( $query->posts, 'ID' ) );
		$this->assertSame( 'Restricted content', get_the_title( $query->posts[0] ) );
	}

	/**
	 * "Hide from queries": the post isn't listed for visitors who can't access it.
	 *
	 * @group security
	 */
	public function test_hidden_post_is_excluded_from_queries() {
		$post_type = $this->register_test_post_type();
		$hidden    = $this->create_restricted_post(
			array(
				'_um_accessible'               => '2',
				'_um_access_hide_from_queries' => '1',
			),
			$post_type
		);
		$visible   = self::factory()->post->create( array( 'post_type' => $post_type ) );

		$query = new WP_Query(
			array(
				'post_type'     => $post_type,
				'um_main_query' => true,
			)
		);

		$this->assertSame( array( $visible ), wp_list_pluck( $query->posts, 'ID' ) );
		$this->assertNotContains( $hidden, wp_list_pluck( $query->posts, 'ID' ) );
	}

	public function test_hidden_post_is_listed_for_allowed_visitors() {
		$post_type = $this->register_test_post_type();
		$hidden    = $this->create_restricted_post(
			array(
				'_um_accessible'               => '2',
				'_um_access_hide_from_queries' => '1',
			),
			$post_type
		);
		$this->act_as( $this->create_user_with_roles( 'subscriber' ) );

		$query = new WP_Query(
			array(
				'post_type'     => $post_type,
				'um_main_query' => true,
			)
		);

		$this->assertSame( array( $hidden ), wp_list_pluck( $query->posts, 'ID' ) );
	}

	/**
	 * "Restrict access" → "Redirect": a single restricted post sends guests to the login page and back.
	 *
	 * @group security
	 */
	public function test_single_post_redirects_to_login() {
		$post_id = $this->create_restricted_post(
			array(
				'_um_accessible'      => '2',
				'_um_noaccess_action' => '1',
				'_um_access_redirect' => '0',
			)
		);

		$location = $this->capture_redirect(
			function () use ( $post_id ) {
				$this->go_to( get_permalink( $post_id ) );
			}
		);

		$this->assertStringStartsWith( um_get_core_page( 'login' ), $location );
		$this->assertStringContainsString( 'redirect_to=', $location );
	}

	public function test_single_post_redirects_to_custom_url() {
		$post_id = $this->create_restricted_post(
			array(
				'_um_accessible'          => '2',
				'_um_noaccess_action'     => '1',
				'_um_access_redirect'     => '1',
				'_um_access_redirect_url' => home_url( '/join/' ),
			)
		);

		$location = $this->capture_redirect(
			function () use ( $post_id ) {
				$this->go_to( get_permalink( $post_id ) );
			}
		);

		$this->assertSame( home_url( '/join/' ), $location );
	}

	/**
	 * Comments of restricted posts are closed and not counted.
	 *
	 * @group security
	 */
	public function test_comments_are_closed_for_restricted_posts() {
		$post_id = $this->create_restricted_post( array( '_um_accessible' => '2' ), 'post', array( 'comment_status' => 'open' ) );
		self::factory()->comment->create( array( 'comment_post_ID' => $post_id ) );

		$this->assertFalse( comments_open( $post_id ) );
		$this->assertSame( 0, (int) get_comments_number( $post_id ) );
	}

	/**
	 * A post without own settings inherits the restriction of its category.
	 *
	 * @group security
	 */
	public function test_post_inherits_category_restriction() {
		$category_id = self::factory()->category->create();
		update_term_meta(
			$category_id,
			'um_content_restriction',
			array(
				'_um_custom_access_settings' => '1',
				'_um_accessible'             => '2',
			)
		);
		$post_id = self::factory()->post->create( array( 'post_category' => array( $category_id ) ) );

		$this->assertTrue( UM()->access()->is_restricted( $post_id ) );
	}

	/**
	 * UM core pages (Login, Register, Password Reset…) can't be locked away from guests.
	 */
	public function test_core_pages_ignore_restriction_settings() {
		$login_page = UM()->config()->permalinks['login'];
		update_post_meta(
			$login_page,
			'um_content_restriction',
			array(
				'_um_custom_access_settings' => '1',
				'_um_accessible'             => '2',
			)
		);

		$this->assertFalse( UM()->access()->is_restricted( $login_page ) );
	}

	/**
	 * @return array[]
	 */
	public function data_restricted_blocks() {
		$logged_in = array(
			'um_is_restrict'     => true,
			'um_who_access'      => '1',
			'um_message_type'    => '2',
			'um_message_content' => 'Log in to see this block.',
		);
		$students  = array_merge( $logged_in, array( 'um_roles_access' => array( 'um_student' ) ) );
		$guests    = array(
			'um_is_restrict' => true,
			'um_who_access'  => '2',
		);

		return array(
			'logged-in block, guest'    => array( $logged_in, 'guest', 'Log in to see this block.' ),
			'logged-in block, member'   => array( $logged_in, 'subscriber', 'BLOCK' ),
			'student block, subscriber' => array( $students, 'subscriber', 'Log in to see this block.' ),
			'student block, student'    => array( $students, 'um_student', 'BLOCK' ),
			'guest block, guest'        => array( $guests, 'guest', 'BLOCK' ),
			'guest block, member'       => array( $guests, 'subscriber', '' ),
			'no restriction, guest'     => array( array(), 'guest', 'BLOCK' ),
		);
	}

	/**
	 * Gutenberg block restriction ("Ultimate Member: Content Restriction" in the block inspector).
	 *
	 * @group security
	 * @dataProvider data_restricted_blocks
	 *
	 * @param array  $attrs    Block attributes.
	 * @param string $visitor  Visitor.
	 * @param string $expected Rendered output.
	 */
	public function test_restricted_blocks( array $attrs, $visitor, $expected ) {
		UM()->options()->update( 'restricted_blocks', true );
		$this->visit_as( $visitor );

		$output = UM()->access()->restrict_blocks(
			'BLOCK',
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => $attrs,
			)
		);

		$this->assertSame( $expected, $output );
	}

	public function test_block_restriction_is_off_unless_enabled() {
		UM()->options()->update( 'restricted_blocks', false );

		$output = UM()->access()->restrict_blocks(
			'BLOCK',
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => array(
					'um_is_restrict' => true,
					'um_who_access'  => '1',
				),
			)
		);

		$this->assertSame( 'BLOCK', $output );
	}

	/**
	 * @return array[]
	 */
	public function data_shortcodes() {
		return array(
			'loggedin, guest'                  => array( '[um_loggedin show_lock="no"]SECRET[/um_loggedin]', 'guest', false ),
			'loggedin, member'                 => array( '[um_loggedin]SECRET[/um_loggedin]', 'subscriber', true ),
			'loggedout, guest'                 => array( '[um_loggedout]SECRET[/um_loggedout]', 'guest', true ),
			'loggedout, member'                => array( '[um_loggedout]SECRET[/um_loggedout]', 'subscriber', false ),
			'show_content roles, guest'        => array( '[um_show_content roles="um_student"]SECRET[/um_show_content]', 'guest', false ),
			'show_content roles, other role'   => array( '[um_show_content roles="um_student"]SECRET[/um_show_content]', 'subscriber', false ),
			'show_content roles, allowed role' => array( '[um_show_content roles="um_student,editor"]SECRET[/um_show_content]', 'um_student', true ),
			'show_content not, excluded role'  => array( '[um_show_content not="um_student"]SECRET[/um_show_content]', 'um_student', false ),
			'show_content not, other role'     => array( '[um_show_content not="um_student"]SECRET[/um_show_content]', 'subscriber', true ),
		);
	}

	/**
	 * @dataProvider data_shortcodes
	 *
	 * @param string $shortcode Shortcode.
	 * @param string $visitor   Visitor.
	 * @param bool   $visible   Whether the content must be shown.
	 */
	public function test_content_shortcodes( $shortcode, $visitor, $visible ) {
		$this->visit_as( $visitor );

		$output = do_shortcode( $shortcode );

		if ( $visible ) {
			$this->assertStringContainsString( 'SECRET', $output );
		} else {
			$this->assertStringNotContainsString( 'SECRET', $output );
		}
	}

	public function test_loggedin_shortcode_shows_lock_notice_to_guests() {
		$output = do_shortcode( '[um_loggedin]SECRET[/um_loggedin]' );

		$this->assertStringNotContainsString( 'SECRET', $output );
		$this->assertStringContainsString( 'restricted to logged-in users', $output );
	}
}
