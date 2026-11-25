<?php
/**
 * Stored XSS via the Rating field (2.14.3): the `data-score` attribute is escaped.
 *
 * @package UM\Tests
 */

/**
 * Class UM_Fields_RatingFieldTest
 *
 * @group security
 */
class UM_Fields_RatingFieldTest extends UM_UnitTestCase {

	const PAYLOAD = '5" onmouseover="alert(document.cookie)" data-x="';

	/**
	 * @return array Rating field settings.
	 */
	private function rating_field() {
		return array(
			'type'     => 'rating',
			'metakey'  => 'um_test_rating',
			'label'    => 'Rating',
			'number'   => 5,
			'public'   => 1,
			'editable' => 1,
			'classes'  => '', // Always present in fields saved by the form builder.
		);
	}

	/**
	 * @param string $html Rendered field.
	 *
	 * @return string The `<div class="um-rating ...">` tag.
	 */
	private function rating_tag( $html ) {
		$this->assertSame( 1, preg_match( '/<div class="um-rating um-raty"[^>]*>/', $html, $m ), 'Rating element not rendered.' );
		return $m[0];
	}

	/**
	 * Value coming back from a submitted form (e.g. after a validation error on Register).
	 */
	public function test_submitted_value_is_escaped_in_data_score() {
		UM()->fields()->set_mode = 'register';
		UM()->form()->post_form  = array( 'um_test_rating' => self::PAYLOAD );

		$tag = $this->rating_tag( UM()->fields()->edit_field( 'um_test_rating', $this->rating_field() ) );

		$this->assertStringNotContainsString( 'onmouseover="alert', $tag );
		$this->assertStringContainsString( 'data-score="' . esc_attr( self::PAYLOAD ) . '"', $tag );
	}

	/**
	 * Value stored in user meta and shown when the profile is edited (the stored XSS case).
	 */
	public function test_stored_meta_value_is_escaped_in_data_score() {
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'um_test_rating', self::PAYLOAD );

		$this->act_as( $user_id );
		um_fetch_user( $user_id );
		UM()->fields()->set_mode = 'profile';
		UM()->fields()->editing  = true;

		$tag = $this->rating_tag( UM()->fields()->edit_field( 'um_test_rating', $this->rating_field() ) );

		$this->assertStringNotContainsString( 'onmouseover="alert', $tag );
		$this->assertStringContainsString( 'data-score="' . esc_attr( self::PAYLOAD ) . '"', $tag );
	}

	public function test_numeric_score_is_rendered_unchanged() {
		UM()->fields()->set_mode = 'register';
		UM()->form()->post_form  = array( 'um_test_rating' => '4' );

		$tag = $this->rating_tag( UM()->fields()->edit_field( 'um_test_rating', $this->rating_field() ) );

		$this->assertStringContainsString( 'data-score="4"', $tag );
	}
}
