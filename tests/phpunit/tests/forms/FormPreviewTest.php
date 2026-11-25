<?php
/**
 * Registration form for logged-in users and the form preview, 2.14.3: the preview is detected with
 * `UM()->form()->preview` (set by wp-admin > Ultimate Member > Forms > Preview) instead of `$_POST['act_id']`.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Forms_FormPreviewTest
 *
 * @group forms
 */
class UM_Forms_FormPreviewTest extends UM_UnitTestCase {

	const ALREADY_REGISTERED = 'You are already registered.';

	/**
	 * @var int
	 */
	private $form_id;

	public function set_up() {
		parent::set_up();

		$this->form_id = self::factory()->post->create(
			array(
				'post_type'   => 'um_form',
				'post_status' => 'publish',
				'post_title'  => 'Registration',
			)
		);
		update_post_meta( $this->form_id, '_um_mode', 'register' );
		update_post_meta( $this->form_id, '_um_custom_fields', array() );
	}

	/**
	 * @return string Rendered `[ultimatemember form_id="..."]`.
	 */
	private function render_form() {
		return UM()->shortcodes()->load( array( 'form_id' => $this->form_id ) );
	}

	public function test_preview_is_off_by_default() {
		$this->assertFalse( UM()->form()->preview );
	}

	public function test_logged_out_visitor_sees_the_registration_form() {
		$html = $this->render_form();

		$this->assertStringNotContainsString( self::ALREADY_REGISTERED, $html );
		$this->assertStringContainsString( 'um-register', $html );
	}

	public function test_logged_in_user_gets_already_registered_message() {
		$this->act_as( self::factory()->user->create() );

		$this->assertSame( self::ALREADY_REGISTERED, $this->render_form() );
	}

	/**
	 * Up to 2.14.2 any frontend POST with `act_id=um_admin_preview_form` skipped this check.
	 *
	 * @group security
	 */
	public function test_act_id_in_post_no_longer_acts_as_preview() {
		$this->act_as( self::factory()->user->create() );
		$_POST['act_id']    = 'um_admin_preview_form';
		$_REQUEST['act_id'] = 'um_admin_preview_form';

		$this->assertSame( self::ALREADY_REGISTERED, $this->render_form() );
	}

	/**
	 * The admin is logged in while previewing a registration form in wp-admin.
	 */
	public function test_admin_preview_shows_registration_form_to_logged_in_admin() {
		$this->act_as( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		UM()->form()->preview = true;

		$html = $this->render_form();

		$this->assertStringNotContainsString( self::ALREADY_REGISTERED, $html );
		$this->assertStringContainsString( 'um-register', $html );
	}

	public function test_filter_still_allows_registration_form_for_logged_in_users() {
		$this->act_as( self::factory()->user->create() );
		add_filter( 'um_registration_for_loggedin_users', '__return_true' );

		$html = $this->render_form();

		$this->assertStringNotContainsString( self::ALREADY_REGISTERED, $html );
		$this->assertStringContainsString( 'um-register', $html );
	}
}
