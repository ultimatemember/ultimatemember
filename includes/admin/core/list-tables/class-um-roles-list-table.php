<?php
/**
 * Render User Roles table
 *
 * @package um\admin\core
 * @see     \um\admin\core\Admin_Menu::um_roles_pages()
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
	$redirect = remove_query_arg( array( '_wp_http_referer' ), esc_url_raw( wp_unslash( $_REQUEST['_wp_http_referer'] ) ) );
} else {
	$redirect = get_admin_url() . 'admin.php?page=um_roles';
}

// Process bulk actions.
if ( isset( $_GET['action'] ) ) {
	switch ( sanitize_key( wp_unslash( $_GET['action'] ) ) ) {

		case 'delete':
			// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request https://github.com/ultimatemember/ultimatemember/pull/906.
			// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID.
			$role_keys = array();
			if ( isset( $_REQUEST['id'] ) ) {
				check_admin_referer( 'um_role_delete' . sanitize_title( wp_unslash( $_REQUEST['id'] ) ) . get_current_user_id() );
				$role_keys = (array) sanitize_title( wp_unslash( $_REQUEST['id'] ) );
			} elseif ( isset( $_REQUEST['item'] ) ) {
				check_admin_referer( 'bulk-' . sanitize_key( __( 'Roles', 'ultimate-member' ) ) );
				$role_keys = array_map( 'sanitize_title', (array) wp_unslash( $_REQUEST['item'] ) );
			}

			if ( ! count( $role_keys ) ) {
				um_js_redirect( $redirect );
			}

			$um_roles = get_option( 'um_roles', array() );

			$um_custom_roles = array();
			foreach ( $role_keys as $k => $role_key ) {
				$role_meta = get_option( "um_role_{$role_key}_meta" );

				if ( empty( $role_meta['_um_is_custom'] ) ) {
					continue;
				}

				delete_option( "um_role_{$role_key}_meta" );
				$um_roles = array_diff( $um_roles, array( $role_key ) );

				$role_id           = 'um_' . $role_key;
				$um_custom_roles[] = $role_id;

				// check if role exist before removing it.
				if ( get_role( $role_id ) ) {
					remove_role( $role_id );
				}
			}

			// set for users with deleted roles role "Subscriber".
			$args = array(
				'blog_id'     => get_current_blog_id(),
				'role__in'    => $um_custom_roles,
				'number'      => -1,
				'count_total' => false,
				'fields'      => 'ids',
			);

			$users_to_subscriber = get_users( $args );
			if ( ! empty( $users_to_subscriber ) ) {
				foreach ( $users_to_subscriber as $user_id ) {
					$object_user = get_userdata( $user_id );

					if ( ! empty( $object_user ) ) {
						foreach ( $um_custom_roles as $role_id ) {
							$object_user->remove_role( $role_id );
						}
					}

					// update user role if it's empty.
					if ( empty( $object_user->roles ) ) {
						wp_update_user(
							array(
								'ID'   => $user_id,
								'role' => 'subscriber',
							)
						);
					}
				}
			}

			update_option( 'um_roles', $um_roles );

			um_js_redirect( add_query_arg( 'msg', 'd', $redirect ) );
			break;

		case 'reset':
			// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
			// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID.
			$role_keys = array();
			if ( isset( $_REQUEST['id'] ) ) {
				check_admin_referer( 'um_role_reset' . sanitize_title( wp_unslash( $_REQUEST['id'] ) ) . get_current_user_id() );
				$role_keys = (array) sanitize_title( wp_unslash( $_REQUEST['id'] ) );
			} elseif ( isset( $_REQUEST['item'] ) ) {
				check_admin_referer( 'bulk-' . sanitize_key( __( 'Roles', 'ultimate-member' ) ) );
				$role_keys = array_map( 'sanitize_title', (array) wp_unslash( $_REQUEST['item'] ) );
			}

			if ( ! count( $role_keys ) ) {
				um_js_redirect( $redirect );
			}

			foreach ( $role_keys as $k => $role_key ) {
				$role_meta = get_option( "um_role_{$role_key}_meta" );

				if ( ! empty( $role_meta['_um_is_custom'] ) ) {
					unset( $role_keys[ array_search( $role_key, $role_keys, true ) ] );
					continue;
				}

				delete_option( "um_role_{$role_key}_meta" );
			}

			um_js_redirect( add_query_arg( 'msg', 'reset', $redirect ) );
			break;
	}
}

// remove extra query arg.
if ( ! empty( $_GET['_wp_http_referer'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
	um_js_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );
}

// load class WP_List_Table.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


if ( ! class_exists( 'UM_Emails_List_Table' ) ) {

	/**
	 * Class UM_Roles_List_Table
	 */
	class UM_Roles_List_Table extends WP_List_Table {


		/**
		 * No items message.
		 *
		 * @var string
		 */
		public $no_items_message = '';


		/**
		 * A list of sortable columns.
		 *
		 * @var array
		 */
		public $sortable_columns = array();


		/**
		 * Default sorting.
		 *
		 * @var string
		 */
		public $default_sorting_field = '';


		/**
		 * A list of actions.
		 *
		 * @var array
		 */
		public $actions = array();


		/**
		 * A list of bulk actions.
		 *
		 * @var array
		 */
		public $bulk_actions = array();


		/**
		 * A list of columns.
		 *
		 * @var array
		 */
		public $columns = array();


		/**
		 * Class constructor.
		 *
		 * @param array $args  Array of arguments.
		 */
		public function __construct( $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'singular' => __( 'item', 'ultimate-member' ),
					'plural'   => __( 'items', 'ultimate-member' ),
					'ajax'     => false,
				)
			);

			$this->no_items_message = $args['plural'] . ' ' . __( 'not found.', 'ultimate-member' );

			parent::__construct( $args );
		}


		/**
		 * Make private/protected methods readable for backward compatibility.
		 *
		 * @param  string $name      Method to call.
		 * @param  array  $arguments Arguments to pass when calling.
		 *
		 * @return mixed  Return value of the callback, false otherwise.
		 */
		public function __call( $name, $arguments ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}


		/**
		 * Prepares the list of items for displaying.
		 */
		public function prepare_items() {
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
		}


		/**
		 * Column default value.
		 *
		 * @param  array  $item         Item.
		 * @param  string $column_name  Column.
		 *
		 * @return string
		 */
		public function column_default( $item, $column_name ) {
			if ( isset( $item[ $column_name ] ) ) {
				return $item[ $column_name ];
			} else {
				return '';
			}
		}


		/**
		 * Message to be displayed when there are no items.
		 */
		public function no_items() {
			echo esc_html( $this->no_items_message );
		}


		/**
		 * Update sortable columns.
		 *
		 * @param  array $args  A list of sortable columns.
		 *
		 * @return UM_Emails_List_Table
		 */
		public function set_sortable_columns( $args = array() ) {
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
		 * Gets a list of sortable columns.
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			return $this->sortable_columns;
		}


		/**
		 * Update columns.
		 *
		 * @param  array $args  A list of columns.
		 *
		 * @return UM_Emails_List_Table
		 */
		public function set_columns( $args = array() ) {
			if ( count( $this->bulk_actions ) ) {
				$args = array_merge(
					array( 'cb' => '<input type="checkbox" />' ),
					$args
				);
			}
			$this->columns = $args;
			return $this;
		}


		/**
		 * Gets a list of columns.
		 *
		 * @return array
		 */
		public function get_columns() {
			return $this->columns;
		}


		/**
		 * Update actions.
		 *
		 * @param  array $args  A list of actions.
		 *
		 * @return UM_Emails_List_Table
		 */
		public function set_actions( $args = array() ) {
			$this->actions = $args;
			return $this;
		}


		/**
		 * Get actions.
		 *
		 * @return array
		 */
		public function get_actions() {
			return $this->actions;
		}


		/**
		 * Update bulk actions.
		 *
		 * @param  array $args  A list of bulk actions.
		 *
		 * @return UM_Emails_List_Table
		 */
		public function set_bulk_actions( $args = array() ) {
			$this->bulk_actions = $args;
			return $this;
		}


		/**
		 * Retrieves the list of bulk actions available for this table.
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			return $this->bulk_actions;
		}


		/**
		 * Return content for the check-column.
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_cb( $item ) {
			return sprintf( '<input type="checkbox" name="item[]" value="%s" />', $item['key'] );
		}


		/**
		 * Return content for the column "Role Title".
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_title( $item ) {
			$actions = array();
			// for backward compatibility based on #906 pull-request https://github.com/ultimatemember/ultimatemember/pull/906.
			// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID.
			$id = rawurlencode( $item['key'] );

			$actions['edit'] = '<a href="admin.php?page=um_roles&tab=edit&id=' . esc_attr( $id ) . '">' . __( 'Edit', 'ultimate-member' ) . '</a>';

			if ( ! empty( $item['_um_is_custom'] ) ) {
				$actions['delete'] = '<a href="admin.php?page=um_roles&action=delete&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_role_delete' . $item['key'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to delete this role?', 'ultimate-member' ) . '\' );">' . __( 'Delete', 'ultimate-member' ) . '</a>';
			} else {
				$role_meta = get_option( "um_role_{$item['key']}_meta" );

				if ( ! empty( $role_meta ) ) {
					$actions['reset'] = '<a href="admin.php?page=um_roles&action=reset&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_role_reset' . $item['key'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to reset UM role meta?', 'ultimate-member' ) . '\' );">' . __( 'Reset UM Role meta', 'ultimate-member' ) . '</a>';
				}
			}

			return '<strong><a class="row-title" href="admin.php?page=um_roles&tab=edit&id=' . esc_attr( $id ) . '">' . stripslashes( $item['name'] ) . '</a></strong> ' . $this->row_actions( $actions );
		}


		/**
		 * Return content for the column "Role ID".
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_roleid( $item ) {
			return ! empty( $item['_um_is_custom'] ) ? 'um_' . $item['key'] : $item['key'];
		}


		/**
		 * Return content for the column "UM Custom Role".
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_core( $item ) {
			return ! empty( $item['_um_is_custom'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' );
		}


		/**
		 * Return content for the column "WP-Admin Access".
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_admin_access( $item ) {
			return ! empty( $item['_um_can_access_wpadmin'] ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' );
		}


		/**
		 * Return content for the column "Priority".
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_priority( $item ) {
			return ! empty( $item['_um_priority'] ) ? $item['_um_priority'] : '-';
		}


		/**
		 * Set pagination.
		 *
		 * @see   WP_List_Table::set_pagination_args()
		 *
		 * @param array $attr  Array of arguments with information about the pagination.
		 */
		public function um_set_pagination_args( $attr = array() ) {
			$this->set_pagination_args( $attr );
		}
	}
}


$list_table = new UM_Roles_List_Table(
	array(
		'singular' => __( 'Role', 'ultimate-member' ),
		'plural'   => __( 'Roles', 'ultimate-member' ),
		'ajax'     => false,
	)
);

$list_table->set_bulk_actions(
	array(
		'delete' => __( 'Delete', 'ultimate-member' ),
	)
);

// Configure columns.
$list_table->set_columns(
	array(
		'title'        => __( 'Role Title', 'ultimate-member' ),
		'roleid'       => __( 'Role ID', 'ultimate-member' ),
		'users'        => __( 'No.of Members', 'ultimate-member' ),
		'core'         => __( 'UM Custom Role', 'ultimate-member' ),
		'admin_access' => __( 'WP-Admin Access', 'ultimate-member' ),
		'priority'     => __( 'Priority', 'ultimate-member' ),
	)
);

$list_table->set_sortable_columns(
	array(
		'title' => 'title',
	)
);

// Prepare roles.
$roles       = array();
$users_count = count_users();

// Get UM roles.
$role_keys = get_option( 'um_roles', array() );
if ( is_array( $role_keys ) ) {
	foreach ( $role_keys as $role_key ) {
		$role_id = 'um_' . $role_key;

		$roles[ $role_id ] = array(
			'key'   => $role_id,
			'users' => empty( $users_count['avail_roles'][ $role_id ] ) ? 0 : $users_count['avail_roles'][ $role_id ],
		);

		$role_meta = get_option( "um_role_{$role_key}_meta" );
		if ( $role_meta ) {
			$roles[ $role_id ] = array_merge( $roles[ $role_id ], (array) $role_meta );
		}
	}
}

// Get non UM roles.
global $wp_roles;
foreach ( $wp_roles->roles as $role_id => $role_data ) {
	if ( in_array( $role_id, array_keys( $roles ), true ) ) {
		continue;
	}

	$roles[ $role_id ] = array(
		'key'   => $role_id,
		'users' => empty( $users_count['avail_roles'][ $role_id ] ) ? 0 : $users_count['avail_roles'][ $role_id ],
		'name'  => $role_data['name'],
	);

	$role_meta = get_option( "um_role_{$role_id}_meta" );
	if ( $role_meta ) {
		$roles[ $role_id ] = array_merge( $roles[ $role_id ], (array) $role_meta );
	}
}

// Sort roles.
if ( empty( $_GET['orderby'] ) || 'title' === sanitize_key( $_GET['orderby'] ) ) {
	switch ( isset( $_GET['order'] ) ? strtolower( sanitize_key( $_GET['order'] ) ) : 'DESC' ) {
		default:
		case 'asc':
			uasort(
				$roles,
				function( $a, $b ) {
					return strnatcmp( $a['name'], $b['name'] );
				}
			);
			break;
		case 'desc':
			uasort(
				$roles,
				function( $a, $b ) {
					return strnatcmp( $a['name'], $b['name'] ) * -1;
				}
			);
			break;
	}
}

$pagenum        = $list_table->get_pagenum();
$items_per_page = 20;

$list_table->prepare_items();
$list_table->items = array_slice( $roles, ( $pagenum - 1 ) * $items_per_page, $items_per_page );

$list_table->um_set_pagination_args(
	array(
		'total_items' => count( $roles ),
		'per_page'    => $items_per_page,
	)
);

$add_new_link = add_query_arg(
	array(
		'page' => 'um_roles',
		'tab'  => 'add',
	),
	admin_url( 'admin.php' )
);
?>

<div class="wrap">
	<h2>
		<?php esc_html_e( 'User Roles', 'ultimate-member' ); ?>
		<a class="add-new-h2" href="<?php echo esc_url( $add_new_link ); ?>">
			<?php esc_html_e( 'Add New', 'ultimate-member' ); ?>
		</a>
	</h2>

	<?php
	if ( ! empty( $_GET['msg'] ) ) {
		switch ( sanitize_key( $_GET['msg'] ) ) {
			case 'd':
				?>
		<div id="message" class="updated fade">
			<p>
				<?php esc_html_e( 'User Role <strong>Deleted</strong> Successfully.', 'ultimate-member' ); ?>
			</p>
		</div>
				<?php
				break;
		}
	}
	?>

	<form action="" method="get" name="um-roles" id="um-roles" style="float: left;margin-right: 10px;">
		<input type="hidden" name="page" value="um_roles" />

		<?php $list_table->display(); ?>
	</form>
</div>
