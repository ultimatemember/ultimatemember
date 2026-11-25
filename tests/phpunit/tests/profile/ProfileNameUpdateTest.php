<?php
/**
 * Sensitive data exposure (2.14.3): a failed display name update no longer dumps data in wp_die().
 *
 * @package UM\Tests
 */

/**
 * Class UM_Profile_ProfileNameUpdateTest
 *
 * @group security
 */
class UM_Profile_ProfileNameUpdateTest extends UM_UnitTestCase {

	/**
	 * @var string
	 */
	private $log_file;

	/**
	 * @var string|false
	 */
	private $previous_error_log;

	public function set_up() {
		parent::set_up();

		$this->log_file           = wp_tempnam( 'um-error-log' );
		$this->previous_error_log = ini_set( 'error_log', $this->log_file );
	}

	public function tear_down() {
		ini_set( 'error_log', (string) $this->previous_error_log );
		if ( file_exists( $this->log_file ) ) {
			unlink( $this->log_file );
		}

		parent::tear_down();
	}

	public function test_display_name_is_synced_from_first_and_last_name() {
		UM()->options()->update( 'display_name', 'full_name' );
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'first_name', 'Ada' );
		update_user_meta( $user_id, 'last_name', 'Lovelace' );

		um_update_profile_full_name( $user_id, array( 'first_name' => 'Ada', 'last_name' => 'Lovelace' ) );

		$this->assertSame( 'Ada Lovelace', get_userdata( $user_id )->display_name );
	}

	/**
	 * `wp_update_user()` fails (here: unknown user ID). The visitor gets a generic message,
	 * the submitted changes are not echoed, and the error code goes to the PHP error log.
	 */
	public function test_failed_update_shows_generic_message_without_submitted_data() {
		UM()->options()->update( 'display_name', 'full_name' );
		$missing_user_id = self::factory()->user->create();
		wp_delete_user( $missing_user_id );

		$changes = array(
			'first_name' => 'Secret-First',
			'user_email' => 'private-address@example.org',
		);

		try {
			um_update_profile_full_name( $missing_user_id, $changes );
			$this->fail( 'wp_die() was expected.' );
		} catch ( WPDieException $e ) {
			$message = $e->getMessage();
		}

		$this->assertSame( 'Unable to update your profile name. Please try again later.', $message );
		$this->assertStringNotContainsString( 'private-address@example.org', $message );
		$this->assertStringNotContainsString( '<pre>', $message );
		$this->assertStringNotContainsString( 'dump', $message );

		$this->assertStringContainsString( 'Ultimate Member profile update failed: invalid_user_id', file_get_contents( $this->log_file ) );
	}
}
