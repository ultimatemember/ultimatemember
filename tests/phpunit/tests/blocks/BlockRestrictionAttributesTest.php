<?php
/**
 * Block restriction schema parity: the UM attributes must exist in the server REST schema for
 * blocks registered with plain arguments too. Blocks with a strict attribute schema broke in the
 * editor when those attributes were sent to the block renderer endpoint (issue #1935).
 *
 * @package UM\Tests
 */

/**
 * Class UM_Blocks_BlockRestrictionAttributesTest
 *
 * @group blocks
 */
class UM_Blocks_BlockRestrictionAttributesTest extends UM_UnitTestCase {

	const BLOCK_NAME      = 'test/block-restriction';
	const LIFTERLMS_BLOCK = 'llms/test-block';

	/**
	 * @var string[]
	 */
	private $registered_blocks = array();

	public function set_up() {
		parent::set_up();

		UM()->options()->update( 'restricted_blocks', true );
	}

	public function tear_down() {
		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( $this->registered_blocks as $block_name ) {
			if ( $registry->is_registered( $block_name ) ) {
				$registry->unregister( $block_name );
			}
		}
		$this->registered_blocks = array();

		UM()->options()->update( 'restricted_blocks', false );

		parent::tear_down();
	}

	/**
	 * @param string $block_name
	 * @param array  $attributes
	 *
	 * @return WP_Block_Type
	 */
	private function register_test_block( $block_name, $attributes = array() ) {
		$block = register_block_type(
			$block_name,
			array(
				'render_callback' => '__return_empty_string',
				'attributes'      => $attributes,
			)
		);

		$this->registered_blocks[] = $block_name;

		return $block;
	}

	/**
	 * @return array[]
	 */
	public function data_restriction_attributes() {
		return array(
			'um_is_restrict'     => array( 'um_is_restrict', 'boolean' ),
			'um_who_access'      => array( 'um_who_access', 'string' ),
			'um_roles_access'    => array( 'um_roles_access', 'array' ),
			'um_message_type'    => array( 'um_message_type', 'string' ),
			'um_message_content' => array( 'um_message_content', 'string' ),
		);
	}

	/**
	 * @dataProvider data_restriction_attributes
	 *
	 * @param string $attribute
	 * @param string $type
	 */
	public function test_restriction_attribute_added_to_block_registered_with_args( $attribute, $type ) {
		$block = $this->register_test_block(
			self::BLOCK_NAME,
			array(
				'heading' => array(
					'type'    => 'string',
					'default' => '',
				),
			)
		);

		$attributes = $block->get_attributes();

		$this->assertArrayHasKey( 'heading', $attributes );
		$this->assertArrayHasKey( $attribute, $attributes );
		$this->assertSame( $type, $attributes[ $attribute ]['type'] );
	}

	public function test_restriction_attributes_not_added_when_setting_disabled() {
		UM()->options()->update( 'restricted_blocks', false );

		$block = $this->register_test_block(
			self::BLOCK_NAME,
			array(
				'heading' => array( 'type' => 'string' ),
			)
		);

		$this->assertArrayNotHasKey( 'um_is_restrict', $block->get_attributes() );
	}

	public function test_existing_restriction_attribute_not_overridden() {
		$block = $this->register_test_block(
			self::BLOCK_NAME,
			array(
				'um_is_restrict' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			)
		);

		$attributes = $block->get_attributes();

		$this->assertTrue( $attributes['um_is_restrict']['default'] );
	}

	public function test_editor_blocklist_does_not_remove_server_schema() {
		// The prefix blocklist lives in the editor script only. Blocks listed there must keep the
		// attributes in the REST schema, otherwise the editor request is rejected again.
		$block = $this->register_test_block( self::LIFTERLMS_BLOCK );

		$this->assertArrayHasKey( 'um_is_restrict', $block->get_attributes() );
	}

	public function test_metadata_settings_get_restriction_attributes() {
		$metadata = array(
			'name'       => 'test/metadata-block',
			'attributes' => array(
				'heading' => array( 'type' => 'string' ),
			),
		);

		$settings = apply_filters( 'block_type_metadata_settings', $metadata, $metadata );

		$this->assertArrayHasKey( 'um_is_restrict', $settings['attributes'] );
		$this->assertArrayHasKey( 'heading', $settings['attributes'] );
	}

	public function test_block_renderer_rest_request_accepts_restriction_attributes() {
		$this->register_test_block(
			self::BLOCK_NAME,
			array(
				'heading' => array(
					'type'    => 'string',
					'default' => '',
				),
			)
		);

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/block-renderer/' . self::BLOCK_NAME );
		$request->set_param( 'context', 'edit' );
		$request->set_param(
			'attributes',
			array(
				'heading'            => 'Title',
				'um_is_restrict'     => false,
				'um_who_access'      => '0',
				'um_roles_access'    => array(),
				'um_message_type'    => '0',
				'um_message_content' => '',
			)
		);

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
	}
}
