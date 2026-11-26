<?php
/**
 * PHP Object Injection hardening (2.14.3): `um_maybe_unserialize()` replaces WordPress `maybe_unserialize()`.
 *
 * @package UM\Tests
 */

/**
 * Gadget class: records whether PHP ever instantiated it from a serialized string.
 */
class UM_Test_Unserialize_Gadget {

	/**
	 * @var bool
	 */
	public static $woken = false;

	/**
	 * Runs only when unserialize() creates a real instance.
	 */
	public function __wakeup() {
		self::$woken = true;
	}
}

/**
 * Class UM_Security_MaybeUnserializeTest
 *
 * @group security
 */
class UM_Security_MaybeUnserializeTest extends UM_UnitTestCase {

	public function set_up() {
		parent::set_up();
		UM_Test_Unserialize_Gadget::$woken = false;
	}

	/**
	 * @return array[]
	 */
	public function data_plain_values() {
		return array(
			'array'          => array( array( 'a' => 1, 'b' => array( 'x', 'y' ) ) ),
			'list of roles'  => array( array( 'subscriber', 'um_member' ) ),
			'string'         => array( 'hello' ),
			'int'            => array( 42 ),
			'bool false'     => array( false ),
			'empty array'    => array( array() ),
		);
	}

	/**
	 * Serialized arrays and scalars still round-trip, so existing stored settings keep working.
	 *
	 * @dataProvider data_plain_values
	 *
	 * @param mixed $value Value to serialize.
	 */
	public function test_serialized_value_round_trips( $value ) {
		$this->assertSame( $value, um_maybe_unserialize( serialize( $value ) ) );
	}

	/**
	 * Non-serialized input is returned untouched (same contract as `maybe_unserialize()`).
	 */
	public function test_non_serialized_input_is_returned_as_is() {
		$object = (object) array( 'sections' => 'a' );

		$this->assertSame( 'plain text', um_maybe_unserialize( 'plain text' ) );
		$this->assertSame( array( 1, 2 ), um_maybe_unserialize( array( 1, 2 ) ) );
		$this->assertSame( 7, um_maybe_unserialize( 7 ) );
		// Plugin updater passes json_decode() results; objects must pass through unchanged.
		$this->assertSame( $object, um_maybe_unserialize( $object ) );
	}

	/**
	 * Control: the gadget really is instantiated by WordPress `maybe_unserialize()`.
	 * Without this the next test could pass for the wrong reason.
	 */
	public function test_control_wp_maybe_unserialize_instantiates_objects() {
		$result = maybe_unserialize( serialize( new UM_Test_Unserialize_Gadget() ) );

		$this->assertInstanceOf( UM_Test_Unserialize_Gadget::class, $result );
		$this->assertTrue( UM_Test_Unserialize_Gadget::$woken );
	}

	public function test_serialized_object_is_not_instantiated() {
		$result = um_maybe_unserialize( serialize( new UM_Test_Unserialize_Gadget() ) );

		$this->assertInstanceOf( '__PHP_Incomplete_Class', $result );
		$this->assertFalse( UM_Test_Unserialize_Gadget::$woken );
	}

	public function test_object_nested_in_array_is_not_instantiated() {
		$payload = serialize(
			array(
				'role'  => array( 'subscriber' ),
				'field' => new UM_Test_Unserialize_Gadget(),
			)
		);

		$result = um_maybe_unserialize( $payload );

		$this->assertSame( array( 'subscriber' ), $result['role'] );
		$this->assertInstanceOf( '__PHP_Incomplete_Class', $result['field'] );
		$this->assertFalse( UM_Test_Unserialize_Gadget::$woken );
	}

	/**
	 * A form's `custom_fields` value comes from post meta / POST data; it must not create objects.
	 */
	public function test_form_custom_field_roles_does_not_instantiate_objects() {
		$payload = serialize(
			array(
				'gadget' => new UM_Test_Unserialize_Gadget(),
			)
		);

		UM()->form()->custom_field_roles( $payload );

		$this->assertFalse( UM_Test_Unserialize_Gadget::$woken );
	}

	public function test_form_custom_field_roles_still_reads_role_options() {
		$fields = array(
			'role_select' => array(
				'type'    => 'select',
				'metakey' => 'role_select',
				'options' => array( 'subscriber' => 'Subscriber' ),
			),
		);

		$roles = UM()->form()->custom_field_roles( serialize( $fields ) );

		$this->assertSame( array( 'subscriber' ), $roles );
	}

	/**
	 * Regression guard: every `maybe_unserialize()` call in plugin code goes through the UM wrapper.
	 * Uses the PHP tokenizer, so comments and method names (e.g. `->maybe_unserialize()`) don't count.
	 */
	public function test_plugin_code_does_not_call_wp_maybe_unserialize() {
		$root  = wp_normalize_path( dirname( __DIR__, 4 ) );
		$calls = array();

		foreach ( array( 'includes', 'templates' ) as $dir ) {
			$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $dir, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $files as $file ) {
				$path = wp_normalize_path( $file->getPathname() );
				if ( 'php' !== $file->getExtension() || false !== strpos( $path, '/includes/lib/' ) ) {
					continue;
				}

				$tokens = token_get_all( file_get_contents( $path ) );
				foreach ( $tokens as $i => $token ) {
					if ( ! is_array( $token ) || T_STRING !== $token[0] || 'maybe_unserialize' !== strtolower( $token[1] ) ) {
						continue;
					}

					$prev = $this->neighbour_token( $tokens, $i, -1 );
					$next = $this->neighbour_token( $tokens, $i, 1 );
					if ( '(' !== $next || in_array( $prev, array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ), true ) ) {
						continue;
					}

					$calls[] = substr( $path, strlen( $root ) + 1 ) . ':' . $token[2];
				}
			}
		}

		$this->assertSame( array(), $calls, 'Use um_maybe_unserialize() instead of maybe_unserialize().' );
	}

	/**
	 * @param array $tokens    Tokens.
	 * @param int   $index     Start index.
	 * @param int   $direction -1 or 1.
	 *
	 * @return int|string|null Token ID for array tokens, the char for simple tokens.
	 */
	private function neighbour_token( array $tokens, $index, $direction ) {
		for ( $i = $index + $direction; isset( $tokens[ $i ] ); $i += $direction ) {
			if ( is_array( $tokens[ $i ] ) && in_array( $tokens[ $i ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			return is_array( $tokens[ $i ] ) ? $tokens[ $i ][0] : $tokens[ $i ];
		}
		return null;
	}
}
