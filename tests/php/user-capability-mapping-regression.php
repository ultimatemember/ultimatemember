<?php
// Run with: php tests/php/user-capability-mapping-regression.php
define( 'ABSPATH', __DIR__ );

function UM() { return $GLOBALS['um']; }
function get_current_user_id() { return $GLOBALS['viewer']; }
function is_user_logged_in() { return (bool) $GLOBALS['viewer']; }
function absint( $value ) { return abs( (int) $value ); }
function get_userdata( $id ) { return in_array( $id, array( 1, 2 ), true ) ? (object) array( 'ID' => $id ) : false; }
function um_user( $key ) { return 'ID' === $key ? $GLOBALS['profile_id'] : ( $GLOBALS['policy'][ $key ] ?? false ); }
function um_fetch_user( $id ) { $GLOBALS['profile_id'] = $id; }
function current_user_can( $cap, ...$args ) { return user_can( get_current_user_id(), $cap, ...$args ); }
function user_can( $id, $cap, ...$args ) {
	if ( ++$GLOBALS['depth'] > 10 ) {
		throw new RuntimeException( 'Recursive capability mapping' );
	}
	$default_caps = array( 'edit_user' => 'edit_users', 'delete_user' => 'delete_users' );
	$required_caps = $GLOBALS['meta_map'][ $cap ] ?? array( $default_caps[ $cap ] ?? $cap );
	$required_caps = (array) $required_caps;
	$allcaps       = $GLOBALS['caps'];
	$allcaps       = $GLOBALS['users']->map_caps_by_role( $allcaps, $required_caps, array_merge( array( $cap, $id ), $args ) );
	--$GLOBALS['depth'];

	foreach ( $required_caps as $required_cap ) {
		if ( empty( $allcaps[ $required_cap ] ) ) {
			return false;
		}
	}

	return true;
}

require dirname( __DIR__, 2 ) . '/includes/core/class-roles-capabilities.php';
require dirname( __DIR__, 2 ) . '/includes/common/class-users.php';

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

$GLOBALS['users']      = new \um\common\Users();
$GLOBALS['roles']      = new TestRoles();
$GLOBALS['um']         = new TestUM();
$GLOBALS['viewer']     = 1;
$GLOBALS['profile_id'] = 1;
$GLOBALS['depth']      = 0;
$GLOBALS['role']       = 'tester';
$GLOBALS['caps']       = array( 'edit_users' => true, 'delete_users' => true );
$GLOBALS['meta_map']   = array();
$GLOBALS['private']    = false;
$GLOBALS['policy']     = array(
	'can_edit_everyone'  => true,
	'can_delete_everyone' => true,
	'can_edit_profile'   => true,
	'can_edit_roles'     => array(),
	'can_delete_roles'   => array(),
	'can_access_private_profile' => true,
);

function check_result( $label, $actual, $expected ) {
	if ( (bool) $actual !== $expected ) {
		throw new RuntimeException( "$label: unexpected result" );
	}
}

foreach ( array( 'edit', 'delete' ) as $action ) {
	$helper       = 'can_current_user_' . $action . '_user';
	$plural_cap   = $action . '_users';
	$singular_cap = $action . '_user';
	$everyone     = 'can_' . $action . '_everyone';

	// The helper's plural-cap check takes precedence over UM restrictions for other users.
	$GLOBALS['policy'][ $everyone ] = false;
	$GLOBALS['policy'][ 'can_' . $action . '_roles' ] = array( 'editor' );
	$GLOBALS['caps'][ $plural_cap ] = true;
	check_result( "$action plural capability overrides UM policy", $GLOBALS['users']->$helper( 2 ), true );
	check_result( "$action singular check is restricted by UM", current_user_can( $singular_cap, 2 ), false );

	// UM settings cannot grant the native capability when it is absent.
	$GLOBALS['policy'][ $everyone ] = true;
	$GLOBALS['policy'][ 'can_' . $action . '_roles' ] = array();
	$GLOBALS['caps'][ $plural_cap ] = false;
	check_result( "$action UM policy cannot grant missing WordPress capability", $GLOBALS['users']->$helper( 2 ), false );
	check_result( "$action singular check fails without primitive capability", current_user_can( $singular_cap, 2 ), false );

	// A third-party meta-cap mapping can grant a target-specific primitive independently.
	$third_party_cap = 'third_party_' . $action . '_user';
	$GLOBALS['meta_map'][ $singular_cap ] = array( $third_party_cap );
	$GLOBALS['caps'][ $third_party_cap ] = true;
	check_result( "$action third-party meta-cap grant", $GLOBALS['users']->$helper( 2 ), true );
	unset( $GLOBALS['meta_map'][ $singular_cap ], $GLOBALS['caps'][ $third_party_cap ] );

	// The UM filter deliberately bypasses its restriction for users with manage_options.
	$GLOBALS['policy'][ $everyone ] = false;
	$GLOBALS['policy'][ 'can_' . $action . '_roles' ] = array( 'editor' );
	$GLOBALS['caps'][ $plural_cap ] = true;
	$GLOBALS['caps']['manage_options'] = true;
	check_result( "$action administrator singular-cap bypass", current_user_can( $singular_cap, 2 ), true );
	$GLOBALS['caps']['manage_options'] = false;
	check_result( "$action non-administrator singular check remains restricted", current_user_can( $singular_cap, 2 ), false );
	check_result( "$action missing user", $GLOBALS['users']->$helper( 99 ), false );
}

// Editing a private profile is UM-restricted in the singular capability check, not by the helper's plural fast path.
$GLOBALS['policy']['can_edit_everyone'] = true;
$GLOBALS['policy']['can_edit_roles'] = array();
$GLOBALS['policy']['can_access_private_profile'] = false;
$GLOBALS['private'] = true;
$GLOBALS['caps']['edit_users'] = true;
check_result( 'private profile singular-cap restriction', current_user_can( 'edit_user', 2 ), false );
check_result( 'private profile plural-cap priority', $GLOBALS['users']->can_current_user_edit_user( 2 ), true );
$GLOBALS['private'] = false;
$GLOBALS['policy']['can_access_private_profile'] = true;

// Self-edit/delete decisions use UM settings and do not fall through to WordPress caps.
$GLOBALS['caps']['edit_users'] = false;
$GLOBALS['policy']['can_edit_profile'] = true;
check_result( 'self edit allowed by UM without WordPress capability', $GLOBALS['users']->can_current_user_edit_user( 1 ), true );
$GLOBALS['policy']['can_edit_profile'] = false;
$GLOBALS['caps']['edit_users'] = true;
check_result( 'self edit denied by UM despite WordPress capability', $GLOBALS['users']->can_current_user_edit_user( 1 ), false );

$GLOBALS['caps']['delete_users'] = false;
$GLOBALS['policy']['can_delete_everyone'] = true;
$GLOBALS['policy']['can_delete_roles'] = array();
check_result( 'self delete allowed by UM without WordPress capability', $GLOBALS['users']->can_current_user_delete_user( 1 ), true );
$GLOBALS['policy']['can_delete_roles'] = array( 'editor' );
check_result( 'self delete role restriction', $GLOBALS['users']->can_current_user_delete_user( 1 ), false );
$GLOBALS['policy']['can_delete_everyone'] = false;
$GLOBALS['caps']['delete_users'] = true;
check_result( 'self delete denied by UM despite WordPress capability', $GLOBALS['users']->can_current_user_delete_user( 1 ), false );

// A missing priority UM role does not block another user's native plural capability.
$GLOBALS['role'] = false;
$GLOBALS['policy']['can_edit_everyone'] = false;
$GLOBALS['policy']['can_delete_everyone'] = false;
$GLOBALS['caps']['edit_users'] = true;
$GLOBALS['caps']['delete_users'] = true;
check_result( 'missing priority role does not block edit plural capability', $GLOBALS['users']->can_current_user_edit_user( 2 ), true );
check_result( 'missing priority role does not block delete plural capability', $GLOBALS['users']->can_current_user_delete_user( 2 ), true );

$GLOBALS['viewer'] = 0;
check_result( 'anonymous edit denied', $GLOBALS['users']->can_current_user_edit_user( 2 ), false );
check_result( 'anonymous delete denied', $GLOBALS['users']->can_current_user_delete_user( 2 ), false );
check_result( 'capability filter exited', $GLOBALS['depth'], false );
fwrite( STDOUT, "User capability mapping checks passed.\n" );