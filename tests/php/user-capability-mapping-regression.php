<?php
// Run with: php tests/php/user-capability-mapping-regression.php
define( 'ABSPATH', __DIR__ );

function UM() { return $GLOBALS['um']; }
function get_current_user_id() { return $GLOBALS['viewer']; }
function is_user_logged_in() { return (bool) $GLOBALS['viewer']; }
function absint( $value ) { return abs( (int) $value ); }
function get_userdata( $id ) { return in_array( $id, array( 1, 2 ), true ) ? (object) array( 'ID' => $id ) : false; }
function get_role( $name ) { return 'tester' === $name ? new TestRole() : null; }
function um_user( $key ) { return 'ID' === $key ? $GLOBALS['profile_id'] : ( $GLOBALS['policy'][ $key ] ?? false ); }
function um_fetch_user( $id ) { $GLOBALS['profile_id'] = $id; }
function current_user_can( $cap, ...$args ) { return user_can( get_current_user_id(), $cap, ...$args ); }
function user_can( $id, $cap, ...$args ) {
	if ( ++$GLOBALS['depth'] > 10 ) {
		throw new RuntimeException( 'Recursive capability mapping' );
	}
	$primitive = array( 'edit_user' => 'edit_users', 'delete_user' => 'delete_users' )[ $cap ] ?? $cap;
	$allcaps = $GLOBALS['caps'];
	$allcaps = $GLOBALS['users']->map_caps_by_role( $allcaps, array( $primitive ), array_merge( array( $cap, $id ), $args ) );
	--$GLOBALS['depth'];
	return ! empty( $allcaps[ $primitive ] );
}

require dirname( __DIR__, 2 ) . '/includes/core/class-roles-capabilities.php';
require dirname( __DIR__, 2 ) . '/includes/common/class-users.php';

class TestRole {
	public function has_cap( $cap ) { return ! empty( $GLOBALS['role_caps'][ $cap ] ); }
}
class TestRoles extends \um\core\Roles_Capabilities {
	public function __construct() {}
	public function get_priority_user_role( $user_id ) { return $GLOBALS['role']; }
	public function get_all_user_roles( $user_id, $priority_order = false ) { return array( 'subscriber' ); }
}
class TestProfile {
	public function is_private_profile( $id ) { return $GLOBALS['private']; }
}
class TestUM {
	public function roles() { return $GLOBALS['roles']; }
	public function user() { return new TestProfile(); }
}

$GLOBALS['users'] = new \um\common\Users();
$GLOBALS['roles'] = new TestRoles();
$GLOBALS['um'] = new TestUM();
$GLOBALS['viewer'] = 1;
$GLOBALS['profile_id'] = 1;
$GLOBALS['depth'] = 0;
$GLOBALS['role'] = 'tester';
$GLOBALS['caps'] = array( 'edit_users' => true, 'delete_users' => true );
$GLOBALS['role_caps'] = $GLOBALS['caps'];
$GLOBALS['private'] = false;
$GLOBALS['policy'] = array( 'can_edit_everyone' => true, 'can_delete_everyone' => true, 'can_edit_profile' => false );

function check_result( $label, $actual, $expected ) {
	if ( (bool) $actual !== $expected ) {
		throw new RuntimeException( "$label: unexpected result" );
	}
}

foreach ( array( 'edit', 'delete' ) as $action ) {
	$helper = 'can_current_user_' . $action . '_user';
	check_result( "$action allowed", $GLOBALS['users']->$helper( 2 ), true );
	$GLOBALS['policy'][ 'can_' . $action . '_roles' ] = array( 'editor' );
	check_result( "$action role restriction", $GLOBALS['users']->$helper( 2 ), false );
	$GLOBALS['policy'][ 'can_' . $action . '_roles' ] = array();
	check_result( "$action missing user", $GLOBALS['users']->$helper( 99 ), false );
	$GLOBALS['caps'][ $action . '_users' ] = false;
	check_result( "$action UM policy without WP capability", $GLOBALS['roles']->um_current_user_can( $action, 2 ), true );
	check_result( "$action helper without WP capability", $GLOBALS['users']->$helper( 2 ), false );
	$GLOBALS['caps'][ $action . '_users' ] = true;
}

$GLOBALS['private'] = true;
check_result( 'private profile restriction', $GLOBALS['users']->can_current_user_edit_user( 2 ), false );
$GLOBALS['private'] = false;
check_result( 'self UM edit policy', $GLOBALS['roles']->um_current_user_can( 'edit', 1 ), false );
check_result( 'self helper edit', $GLOBALS['users']->can_current_user_edit_user( 1 ), true );
$GLOBALS['policy']['can_delete_everyone'] = false;
check_result( 'self UM delete policy', $GLOBALS['roles']->um_current_user_can( 'delete', 1 ), false );
check_result( 'self helper delete', $GLOBALS['users']->can_current_user_delete_user( 1 ), true );
$GLOBALS['caps']['manage_options'] = true;
check_result( 'administrator bypass', $GLOBALS['users']->can_current_user_delete_user( 2 ), true );
check_result( 'administrator UM policy', $GLOBALS['roles']->um_current_user_can( 'delete', 2 ), false );
$GLOBALS['caps']['manage_options'] = false;
$GLOBALS['role'] = false;
check_result( 'missing role', $GLOBALS['users']->can_current_user_edit_user( 2 ), false );
check_result( 'filter exited', $GLOBALS['depth'], false );

// Frontend checks use the helper alone; the mapper applies UM policy for non-administrators.
$GLOBALS['role'] = 'tester';
foreach ( array( 'edit', 'delete' ) as $action ) {
	$helper = 'can_current_user_' . $action . '_user';
	$GLOBALS['policy'][ 'can_' . $action . '_everyone' ] = true;
	check_result( "$action frontend allowed", $GLOBALS['users']->$helper( 2 ), true );
	$GLOBALS['caps'][ $action . '_users' ] = false;
	check_result( "$action frontend missing WP capability", $GLOBALS['users']->$helper( 2 ), false );
	$GLOBALS['caps'][ $action . '_users' ] = true;
	$GLOBALS['policy'][ 'can_' . $action . '_everyone' ] = false;
	check_result( "$action frontend UM restriction", $GLOBALS['users']->$helper( 2 ), false );
	$GLOBALS['caps']['manage_options'] = true;
	check_result( "$action frontend administrator bypass", $GLOBALS['users']->$helper( 2 ), true );
	$GLOBALS['caps']['manage_options'] = false;
}
check_result( 'frontend self edit bypass', $GLOBALS['users']->can_current_user_edit_user( 1 ), true );
check_result( 'frontend self delete bypass', $GLOBALS['users']->can_current_user_delete_user( 1 ), true );
$GLOBALS['viewer'] = 0;
check_result( 'anonymous zero user edit', $GLOBALS['users']->can_current_user_edit_user( 0 ), false );
check_result( 'anonymous zero user delete', $GLOBALS['users']->can_current_user_delete_user( 0 ), false );
check_result( 'frontend filter exited', $GLOBALS['depth'], false );
fwrite( STDOUT, "User capability mapping checks passed.\n" );