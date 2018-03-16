<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
	$redirect = remove_query_arg(array('_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
} else {
	$redirect = get_admin_url(). 'admin.php?page=um_roles';
}

global $wp_roles;

if ( isset( $_GET['action'] ) ) {
	switch ( $_GET['action'] ) {
		/* delete action */
		case 'delete': {
			$role_keys = array();
			if ( isset( $_REQUEST['id'] ) ) {
				check_admin_referer( 'um_role_delete' .  $_REQUEST['id'] . get_current_user_id() );
				$role_keys = (array)$_REQUEST['id'];
			} elseif( isset( $_REQUEST['item'] ) )  {
				check_admin_referer( 'bulk-' . sanitize_key( __( 'Roles', 'ultimate-member' ) ) );
				$role_keys = $_REQUEST['item'];
			}

			if ( ! count( $role_keys ) )
				um_js_redirect( $redirect );

			$um_roles = get_option( 'um_roles' );

			$um_custom_roles = array();
			foreach ( $role_keys as $k => $role_key ) {
				$role_meta = get_option( "um_role_{$role_key}_meta" );

				if ( empty( $role_meta['_um_is_custom'] ) ) {
					continue;
				}

				delete_option( "um_role_{$role_key}_meta" );
				$um_roles = array_diff( $um_roles, array( $role_key ) );

				$roleID = 'um_' . $role_key;
				$um_custom_roles[] = $roleID;

				//check if role exist before removing it
				if ( get_role( $roleID ) ) {
					remove_role( $roleID );
				}
			}

			//set for users with deleted roles role "Subscriber"
			$args = array(
				'blog_id'      => get_current_blog_id(),
				'role__in'     => $um_custom_roles,
				'number'       => -1,
				'count_total'  => false,
				'fields'       => 'ids',
			);
			$users_to_subscriber = get_users( $args );
			if ( ! empty( $users_to_subscriber ) ) {
				foreach ( $users_to_subscriber as $user_id ) {
					$object_user = get_userdata( $user_id );

					if ( ! empty( $object_user ) ) {
						foreach ( $um_custom_roles as $roleID ) {
							$object_user->remove_role( $roleID );
						}
					}

					//update user role if it's empty
					if ( empty( $object_user->roles ) )
						wp_update_user( array( 'ID' => $user_id, 'role' => 'subscriber' ) );
				}
			}

			update_option( 'um_roles', $um_roles );

			um_js_redirect( add_query_arg( 'msg', 'd', $redirect ) );
			break;
		}
		case 'reset': {
			$role_keys = array();
			if ( isset( $_REQUEST['id'] ) ) {
				check_admin_referer( 'um_role_reset' .  $_REQUEST['id'] . get_current_user_id() );
				$role_keys = (array)$_REQUEST['id'];
			} elseif( isset( $_REQUEST['item'] ) )  {
				check_admin_referer( 'bulk-' . sanitize_key( __( 'Roles', 'ultimate-member' ) ) );
				$role_keys = $_REQUEST['item'];
			}

			if ( ! count( $role_keys ) )
				um_js_redirect( $redirect );

			foreach ( $role_keys as $k=>$role_key ) {
				$role_meta = get_option( "um_role_{$role_key}_meta" );

				if ( ! empty( $role_meta['_um_is_custom'] ) ) {
					unset( $role_keys[array_search( $role_key, $role_keys )] );
					continue;
				}

				delete_option( "um_role_{$role_key}_meta" );
			}

			um_js_redirect( add_query_arg( 'msg', 'reset', $redirect ) );
			break;
		}
	}
}

//remove extra query arg
if ( ! empty( $_GET['_wp_http_referer'] ) )
	um_js_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce'), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );

$order_by = 'name';
$order = ( isset( $_GET['order'] ) && 'asc' ==  strtolower( $_GET['order'] ) ) ? 'ASC' : 'DESC';

if( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


/**
 * Class UM_Roles_List_Table
 */
class UM_Roles_List_Table extends WP_List_Table {


	/**
	 * @var string
	 */
	var $no_items_message = '';


	/**
	 * @var array
	 */
	var $sortable_columns = array();


	/**
	 * @var string
	 */
	var $default_sorting_field = '';


	/**
	 * @var array
	 */
	var $actions = array();


	/**
	 * @var array
	 */
	var $bulk_actions = array();


	/**
	 * @var array
	 */
	var $columns = array();


	/**
	 * UM_Roles_List_Table constructor.
	 *
	 * @param array $args
	 */
	function __construct( $args = array() ){
		$args = wp_parse_args( $args, array(
			'singular'  => __( 'item', 'ultimate-member' ),
			'plural'    => __( 'items', 'ultimate-member' ),
			'ajax'      => false
		) );

		$this->no_items_message = $args['plural'] . ' ' . __( 'not found.', 'ultimate-member' );

		parent::__construct( $args );
	}


	/**
	 * @param callable $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	function __call( $name, $arguments ) {
		return call_user_func_array( array( $this, $name ), $arguments );
	}


	/**
	 *
	 */
	function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}


	/**
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		if( isset( $item[ $column_name ] ) ) {
			return $item[ $column_name ];
		} else {
			return '';
		}
	}


	/**
	 *
	 */
	function no_items() {
		echo $this->no_items_message;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_sortable_columns( $args = array() ) {
		$return_args = array();
		foreach( $args as $k=>$val ) {
			if( is_numeric( $k ) ) {
				$return_args[ $val ] = array( $val, $val == $this->default_sorting_field );
			} else if( is_string( $k ) ) {
				$return_args[ $k ] = array( $val, $k == $this->default_sorting_field );
			} else {
				continue;
			}
		}
		$this->sortable_columns = $return_args;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_sortable_columns() {
		return $this->sortable_columns;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_columns( $args = array() ) {
		if( count( $this->bulk_actions ) ) {
			$args = array_merge( array( 'cb' => '<input type="checkbox" />' ), $args );
		}
		$this->columns = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_columns() {
		return $this->columns;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_actions( $args = array() ) {
		$this->actions = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_actions() {
		return $this->actions;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	function set_bulk_actions( $args = array() ) {
		$this->bulk_actions = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	function get_bulk_actions() {
		return $this->bulk_actions;
	}


	/**
	 * @param object $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="item[]" value="%s" />', $item['key'] );
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_title( $item ) {
		$actions = array();

		$actions['edit'] = '<a href="admin.php?page=um_roles&tab=edit&id=' . $item['key'] . '">' . __( 'Edit', 'ultimate-member' ). '</a>';

		if ( ! empty( $item['_um_is_custom'] ) ) {
			$actions['delete'] = '<a href="admin.php?page=um_roles&action=delete&id=' . $item['key'] . '&_wpnonce=' . wp_create_nonce( 'um_role_delete' . $item['key'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to delete this role?', 'ultimate-member' ) . '\' );">' . __( 'Delete', 'ultimate-member' ). '</a>';
		} else {
			$role_meta = get_option( "um_role_{$item['key']}_meta" );

			if ( ! empty( $role_meta ) ) {
				$actions['reset'] = '<a href="admin.php?page=um_roles&action=reset&id=' . $item['key'] . '&_wpnonce=' . wp_create_nonce( 'um_role_reset' . $item['key'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to reset UM role meta?', 'ultimate-member' ) . '\' );">' . __( 'Reset UM Role meta', 'ultimate-member' ). '</a>';
			}
		}



		return sprintf('%1$s %2$s', '<strong><a class="row-title" href="admin.php?page=um_roles&tab=edit&id=' . $item['key'] . '">' . $item['name'] . '</a></strong>', $this->row_actions( $actions ) );
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_roleid( $item ) {
		return ! empty( $item['_um_is_custom'] ) ? 'um_' . $item['key'] : $item['key'];
	}


	/**
	 * @param $item
	 */
	function column_core( $item ) {
		echo ! empty( $item['_um_is_custom'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' );
	}


	/**
	 * @param $item
	 */
	function column_admin_access( $item ) {
		echo ! empty( $item['_um_can_access_wpadmin'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' );
	}


	/**
	 * @param $item
	 */
	function column_priority( $item ) {
		echo ! empty( $item['_um_priority'] ) ? $item['_um_priority'] : '-';
	}


	/**
	 * @param array $attr
	 */
	function um_set_pagination_args( $attr = array() ) {
		$this->set_pagination_args( $attr );
	}
}


$ListTable = new UM_Roles_List_Table( array(
	'singular'  => __( 'Role', 'ultimate-member' ),
	'plural'    => __( 'Roles', 'ultimate-member' ),
	'ajax'      => false
));

$per_page   = 20;
$paged      = $ListTable->get_pagenum();

$ListTable->set_bulk_actions( array(
	'delete' => __( 'Delete', 'ultimate-member' )
) );

$ListTable->set_columns( array(
	'title'         => __( 'Role Title', 'ultimate-member' ),
	'roleid'        => __( 'Role ID', 'ultimate-member' ),
	'users'         => __( 'No.of Members', 'ultimate-member' ),
	'core'          => __( 'UM Custom Role', 'ultimate-member' ),
	'admin_access'  => __( 'WP-Admin Access', 'ultimate-member' ),
	'priority'      => __( 'Priority', 'ultimate-member' ),
) );

$ListTable->set_sortable_columns( array(
	'title' => 'title'
) );

$users_count = count_users();

$roles = array();
$role_keys = get_option( 'um_roles' );

if ( $role_keys ) {
	foreach ( $role_keys as $role_key ) {
		$role_meta = get_option( "um_role_{$role_key}_meta" );
		if ( $role_meta ) {

			$roles['um_' . $role_key] = array(
				'key'   => $role_key,
				'users' => ! empty( $users_count['avail_roles']['um_' . $role_key] ) ? $users_count['avail_roles']['um_' . $role_key] : 0
			);
			$roles['um_' . $role_key] = array_merge( $roles['um_' . $role_key], $role_meta );
		}
	}
}

global $wp_roles;

foreach ( $wp_roles->roles as $roleID => $role_data ) {
	if ( in_array( $roleID, array_keys( $roles ) ) )
		continue;

	$roles[$roleID] = array(
		'key'   => $roleID,
		'users' => ! empty( $users_count['avail_roles'][$roleID] ) ? $users_count['avail_roles'][$roleID] : 0,
		'name' => $role_data['name']
	);

	$role_meta = get_option( "um_role_{$roleID}_meta" );
	if ( $role_meta )
		$roles[$roleID] = array_merge( $roles[$roleID], $role_meta );
}

switch( strtolower( $order ) ) {
	case 'asc':
		uasort( $roles, function( $a, $b ) {
			return strnatcmp( $a['name'], $b['name'] );
		} );
		break;
	case 'desc':
		uasort( $roles, function( $a, $b ) {
			return strnatcmp( $a['name'], $b['name'] ) * -1;
		} );
		break;
}

$ListTable->prepare_items();
$ListTable->items = array_slice( $roles, ( $paged - 1 ) * $per_page, $per_page );
$ListTable->um_set_pagination_args( array( 'total_items' => count( $roles ), 'per_page' => $per_page ) ); ?>

<div class="wrap">
	<h2>
		<?php _e( 'User Roles', 'ultimate-member' ) ?>
		<a class="add-new-h2" href="<?php echo add_query_arg( array( 'page' => 'um_roles', 'tab' => 'add' ), admin_url( 'admin.php' ) ) ?>">
			<?php _e( 'Add New', 'ultimate-member' ) ?>
		</a>
	</h2>

	<?php if ( ! empty( $_GET['msg'] ) ) {
		switch( $_GET['msg'] ) {
			case 'd':
				echo '<div id="message" class="updated fade"><p>' . __( 'User Role <strong>Deleted</strong> Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
		}
	} ?>

	<form action="" method="get" name="um-roles" id="um-roles" style="float: left;margin-right: 10px;">
		<input type="hidden" name="page" value="um_roles" />
		<?php $ListTable->display(); ?>
	</form>
</div>