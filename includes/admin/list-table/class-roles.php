<?php
namespace um\admin\list_table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Roles
 */
class Roles extends \WP_List_Table {


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
	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'singular' => __( 'item', 'ultimate-member' ),
			'plural'   => __( 'items', 'ultimate-member' ),
			'ajax'     => false,
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
		$screen = $this->screen;

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 20 );
		$paged = $this->get_pagenum();

		$users_count = count_users();

		$roles = array();
		$role_keys = get_option( 'um_roles', array() );

		if ( $role_keys ) {
			foreach ( $role_keys as $role_key ) {
				$role_meta = get_option( "um_role_{$role_key}_meta" );
				if ( $role_meta ) {
					$roles[ 'um_' . $role_key ] = array(
						'key'   => $role_key,
						'users' => ! empty( $users_count['avail_roles'][ 'um_' . $role_key ] ) ? $users_count['avail_roles'][ 'um_' . $role_key ] : 0,
					);
					$roles[ 'um_' . $role_key ] = array_merge( $roles[ 'um_' . $role_key ], $role_meta );
				}
			}
		}

		global $wp_roles;

		foreach ( $wp_roles->roles as $roleID => $role_data ) {
			if ( in_array( $roleID, array_keys( $roles ) ) ) {
				continue;
			}

			$roles[ $roleID ] = array(
				'key'   => $roleID,
				'users' => ! empty( $users_count['avail_roles'][ $roleID ] ) ? $users_count['avail_roles'][ $roleID ] : 0,
				'name'  => $role_data['name'],
			);

			$role_meta = get_option( "um_role_{$roleID}_meta" );
			if ( $role_meta ) {
				$roles[ $roleID ] = array_merge( $roles[ $roleID ], $role_meta );
			}
		}

		$order = ( isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( $_GET['order'] ) ) ) ? 'asc' : 'desc';

		switch( $order ) {
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

		$this->items = array_slice( $roles, ( $paged - 1 ) * $per_page, $per_page );

		$this->set_pagination_args( array(
			'total_items' => count( $roles ),
			'per_page'    => $per_page,
		) );
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
		foreach ( $args as $k => $val ) {
			if ( is_numeric( $k ) ) {
				$return_args[ $val ] = array( $val, $val === $this->default_sorting_field );
			} elseif ( is_string( $k ) ) {
				$return_args[ $k ] = array( $val, $k === $this->default_sorting_field );
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
		// for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
		// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
		$id = urlencode( $item['key'] );

		$actions['edit'] = '<a href="admin.php?page=um_roles&tab=edit&id=' . esc_attr( $id ) . '">' . __( 'Edit', 'ultimate-member' ) . '</a>';

		if ( ! empty( $item['_um_is_custom'] ) ) {
			$actions['delete'] = '<a href="admin.php?page=um_roles&action=delete&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_role_delete' . $item['key'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to delete this role?', 'ultimate-member' ) . '\' );">' . __( 'Delete', 'ultimate-member' ) . '</a>';
		} else {
			$role_meta = get_option( "um_role_{$item['key']}_meta" );

			if ( ! empty( $role_meta ) ) {
				$actions['reset'] = '<a href="admin.php?page=um_roles&action=reset&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_role_reset' . $item['key'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to reset UM role meta?', 'ultimate-member' ) . '\' );">' . __( 'Reset UM Role meta', 'ultimate-member' ) . '</a>';
			}
		}

		return sprintf('%1$s %2$s', '<strong><a class="row-title" href="admin.php?page=um_roles&tab=edit&id=' . esc_attr( $id ) . '">' . stripslashes( $item['name'] ) . '</a></strong>', $this->row_actions( $actions ) );
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
}
