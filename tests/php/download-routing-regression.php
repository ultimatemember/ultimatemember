<?php
// Run with: php tests/php/download-routing-regression.php
define( 'ABSPATH', __DIR__ );

function get_query_var( $key ) { return $GLOBALS['query_vars'][ $key ] ?? ''; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_text_field( $value ) { return $value; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === $action; }
function get_post_meta( $id, $key, $single ) { return array( 'document' => $GLOBALS['field_data'] ); }
function get_current_user_id() { return $GLOBALS['viewer']; }
function is_user_logged_in() { return (bool) $GLOBALS['viewer']; }
function is_serialized( $value ) { return false; }
function apply_filters( $name, $value ) { return $value; }
function UM() { return $GLOBALS['um']; }

class TestUser {
	public $target_id = null;
	public $profile = array();
	public function set( $id ) {
		$id = $id ?: get_current_user_id();
		$this->profile = array( 'ID' => $id, 'roles' => array( 3 === $id ? 'administrator' : 'subscriber' ) );
	}
	public function reset() { $this->set( 0 ); }
}
class TestFields {
	public $set_mode = '';
	public $read = false;
	public function field_value( $key ) { $this->read = true; return ''; }
}
class TestRoles {
	public function um_current_user_can( $action, $id ) { return 3 === get_current_user_id() && 10 === $id; }
}
class TestUsers {
	public function can_view_user_profile( $id ) { return $GLOBALS['profile_allowed']; }
	public function can_current_user_edit_user( $id ) { return 3 === get_current_user_id() && 10 === $id; }
}
class TestCommon {
	public function users() { return new TestUsers(); }
}
class TestUM {
	public $user;
	public $fields;
	public function __construct() { $this->user = new TestUser(); $this->fields = new TestFields(); }
	public function user() { return $this->user; }
	public function fields() { return $this->fields; }
	public function roles() { return new TestRoles(); }
	public function common() { return new TestCommon(); }
}
class TestQuery {
	public function set_404() {}
}

require dirname( __DIR__, 2 ) . '/includes/um-short-functions.php';
require dirname( __DIR__, 2 ) . '/includes/core/class-files.php';

$files = ( new ReflectionClass( 'um\core\Files' ) )->newInstanceWithoutConstructor();
$GLOBALS['wp_query'] = new TestQuery();
$GLOBALS['query_vars'] = array( 'um_action' => 'download', 'um_form' => 5, 'um_field' => 'document', 'um_user' => 10, 'um_verify' => 'um-download-nonce-10-5-document' );
$cases = array(
	array( '-1', 2, false ),
	array( '-1', 10, true ),
	array( '-1', 3, true ),
	array( '-3', 2, false ),
	array( '-3', 10, true ),
	array( '-3', 3, true ),
	array( '-1', 0, false ),
	array( '1', 2, true ),
);
foreach ( $cases as $case ) {
	foreach ( array( null, 99 ) as $previous_target ) {
		list( $visibility, $GLOBALS['viewer'], $expected ) = $case;
		$GLOBALS['um'] = new TestUM();
		$GLOBALS['um']->user->set( $GLOBALS['viewer'] );
		$GLOBALS['um']->user->target_id = $previous_target;
		$GLOBALS['field_data'] = array( 'public' => $visibility, 'roles' => array( 'administrator' ), 'type' => 'file' );
		$GLOBALS['profile_allowed'] = true;
		$files->download_routing();
		if ( $GLOBALS['um']->fields->read !== $expected || $previous_target !== $GLOBALS['um']->user->target_id ) {
			fwrite( STDERR, "Visibility $visibility, viewer {$GLOBALS['viewer']}: unexpected access\n" );
			exit( 1 );
		}
	}
}
foreach ( array( 'nonce', 'profile' ) as $gate ) {
	$GLOBALS['um'] = new TestUM();
	$GLOBALS['viewer'] = 10;
	$GLOBALS['profile_allowed'] = 'profile' !== $gate;
	$GLOBALS['query_vars']['um_verify'] = 'nonce' === $gate ? 'invalid' : 'um-download-nonce-10-5-document';
	$files->download_routing();
	if ( $GLOBALS['um']->fields->read ) {
		fwrite( STDERR, "The $gate gate did not prevent access\n" );
		exit( 1 );
	}
}
fwrite( STDOUT, "Download visibility checks passed.\n" );