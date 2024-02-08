<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
// phpcs:disable WordPress.Security.NonceVerification
if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
	$redirect = remove_query_arg( array( '_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
} else {
	$redirect = get_admin_url() . 'admin.php?page=um_restriction_rules';
}

global $wp_roles;

if ( isset( $_GET['action'] ) ) {
	switch ( sanitize_key( $_GET['action'] ) ) {
		/* delete action */
		case 'delete':
			// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
			// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
			$rule_keys = array();
			if ( isset( $_REQUEST['id'] ) ) {
				check_admin_referer( 'um_restriction_delete' . sanitize_title( $_REQUEST['id'] ) . get_current_user_id() );
				$rule_keys = (array) absint( $_REQUEST['id'] );
			} elseif ( isset( $_REQUEST['item'] ) ) {
				check_admin_referer( 'bulk-' . sanitize_key( __( 'Rules', 'ultimate-member' ) ) );
				$rule_keys = array_map( 'sanitize_title', $_REQUEST['item'] );
			}

			if ( ! count( $rule_keys ) ) {
				um_js_redirect( $redirect );
			}

			$um_rules = get_option( 'um_restriction_rules', array() );

			foreach ( $rule_keys as $k => $rule_key ) {
				$rule_meta = get_option( "um_restriction_rule_{$rule_key}" );

				delete_option( "um_restriction_rule_{$rule_key}" );

				/**
				 * Fires after delete UM restriction rule.
				 *
				 * @since 2.8.x
				 * @hook um_after_delete_restriction_rule
				 *
				 * @param {string} $rule_key  Rule key.
				 * @param {array}  $rule_meta Rule meta.
				 *
				 * @example <caption>Make any custom action after deleting UM rule.</caption>
				 * function my_um_after_delete_restriction_rule( $rule_key, $rule_meta ) {
				 *     // your code here
				 * }
				 * add_action( 'um_after_delete_restriction_rule', 'my_um_after_delete_restriction_rule', 10, 2 );
				 */
				do_action( 'um_after_delete_restriction_rule', $rule_key, $rule_meta );

				unset( $um_rules[ $rule_key ] );
			}

			update_option( 'um_restriction_rules', $um_rules );

			um_js_redirect( add_query_arg( 'msg', 'd', $redirect ) );
			break;
	}
}

//remove extra query arg
if ( ! empty( $_GET['_wp_http_referer'] ) ) {
	um_js_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
}

$order_by = 'title';
$order    = ( isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


/**
 * Class UM_Restrictions_List_Table
 */
class UM_Restrictions_List_Table extends WP_List_Table {


	/**
	 * @var string
	 */
	public $no_items_message = '';


	/**
	 * @var array
	 */
	public $sortable_columns = array();


	/**
	 * @var string
	 */
	public $default_sorting_field = '';


	/**
	 * @var array
	 */
	public $actions = array();


	/**
	 * @var array
	 */
	public $bulk_actions = array();


	/**
	 * @var array
	 */
	public $columns = array();


	/**
	 * UM_Restrictions_List_Table constructor.
	 *
	 * @param array $args
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
	 * @param callable $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		return call_user_func_array( array( $this, $name ), $arguments );
	}


	/**
	 *
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}


	/**
	 * @param object $item
	 * @param string $column_name
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
	 *
	 */
	public function no_items() {
		echo wp_kses( $this->no_items_message, UM()->get_allowed_html( 'admin_notice' ) );
	}


	/**
	 * @param array $args
	 *
	 * @return $this
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
	 * @return array
	 */
	public function get_sortable_columns() {
		return $this->sortable_columns;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_columns( $args = array() ) {
		if ( count( $this->bulk_actions ) ) {
			$args = array_merge( array( 'cb' => '<input type="checkbox" />' ), $args );
		}
		$this->columns = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	public function get_columns() {
		return $this->columns;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_actions( $args = array() ) {
		$this->actions = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	public function get_actions() {
		return $this->actions;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_bulk_actions( $args = array() ) {
		$this->bulk_actions = $args;
		return $this;
	}


	/**
	 * @return array
	 */
	public function get_bulk_actions() {
		return $this->bulk_actions;
	}


	/**
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="item[]" value="%s" />', $item['id'] );
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function column_title( $item ) {
		$actions = array();
		// for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
		// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
		$id = urlencode( $item['id'] );

		$actions['edit']   = '<a href="admin.php?page=um_restriction_rules&tab=edit&id=' . esc_attr( $id ) . '">' . __( 'Edit', 'ultimate-member' ) . '</a>';
		$actions['delete'] = '<a href="admin.php?page=um_restriction_rules&action=delete&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_restriction_delete' . $item['id'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to delete this restriction rule?', 'ultimate-member' ) . '\' );">' . __( 'Delete', 'ultimate-member' ) . '</a>';

		/**
		 * Filters the role actions in WP ListTable Ultimate Member > Access Rules screen.
		 *
		 * @since 2.8.x
		 * @hook um_restriction_row_actions
		 *
		 * @param {array}  $actions Action links.
		 * @param {string} $id      Role key.
		 *
		 * @example <caption>Add custom action to rule's row.</caption>
		 * function my_um_restriction_row_actions( $actions, $id ) {
		 *     $actions['{action_key}'] = "<a href="{action_link}">Action Title</a>";
		 *     return $actions;
		 * }
		 * add_action( 'um_restriction_row_actions', 'my_um_restriction_row_actions', 10, 2 );
		 */
		$actions = apply_filters( 'um_restriction_row_actions', $actions, $id );

		return sprintf( '%1$s %2$s', '<strong><a class="row-title" href="admin.php?page=um_restriction_rules&tab=edit&id=' . esc_attr( $id ) . '">' . stripslashes( $item['title'] ) . '</a></strong>', $this->row_actions( $actions ) );
	}


	/**
	 * @param $item
	 */
	public function column_priority( $item ) {
		echo ! empty( $item['priority'] ) ? $item['priority'] : '-';
	}


	/**
	 * @param $item
	 */
	public function column_status( $item ) {
		echo esc_html( $item['status'] );
	}


	/**
	 * @param $item
	 */
	public function column_descr( $item ) {
		echo wp_kses( $item['description'], UM()->get_allowed_html( 'admin_notice' ) );
	}


	/**
	 * @param array $attr
	 */
	public function um_set_pagination_args( $attr = array() ) {
		$this->set_pagination_args( $attr );
	}
}


$list_table = new UM_Restrictions_List_Table(
	array(
		'singular' => __( 'Rule', 'ultimate-member' ),
		'plural'   => __( 'Rules', 'ultimate-member' ),
		'ajax'     => false,
	)
);

$per_page = 20;
$paged    = $list_table->get_pagenum();

$list_table->set_bulk_actions(
	array(
		'delete' => __( 'Delete', 'ultimate-member' ),
	)
);

$list_table->set_columns(
	array(
		'title'    => __( 'Role Title', 'ultimate-member' ),
		'descr'    => __( 'Description', 'ultimate-member' ),
		'type'     => __( 'Rule type', 'ultimate-member' ),
		'status'   => __( 'Status', 'ultimate-member' ),
		'priority' => __( 'Priority', 'ultimate-member' ),
	)
);

$list_table->set_sortable_columns(
	array(
		'title' => 'title',
	)
);

$rules = get_option( 'um_restriction_rules', array() );

switch ( strtolower( $order ) ) {
	case 'asc':
		uasort(
			$rules,
			function( $a, $b ) {
				return strnatcmp( $a['title'], $b['title'] );
			}
		);
		break;
	case 'desc':
		uasort(
			$rules,
			function( $a, $b ) {
				return strnatcmp( $a['title'], $b['title'] ) * -1;
			}
		);
		break;
}

$list_table->prepare_items();
$list_table->items = array_slice( $rules, ( $paged - 1 ) * $per_page, $per_page );
$list_table->um_set_pagination_args(
	array(
		'total_items' => count( $rules ),
		'per_page'    => $per_page,
	)
);
?>

<div class="wrap">
	<h2>
		<?php esc_html_e( 'Restriction rules', 'ultimate-member' ); ?>
		<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( array( 'page' => 'um_restriction_rules', 'tab' => 'add' ), admin_url( 'admin.php' ) ) ); ?>">
			<?php esc_html_e( 'Add New', 'ultimate-member' ); ?>
		</a>
	</h2>

	<?php
	if ( ! empty( $_GET['msg'] ) ) {
		switch ( sanitize_key( $_GET['msg'] ) ) {
			case 'd':
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Restriction Rule <strong>Deleted</strong> Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
		}
	}
	?>

	<form action="" method="get" name="um-roles" id="um-roles" style="float: left;margin-right: 10px;">
		<input type="hidden" name="page" value="um_restriction_rules" />
		<?php $list_table->display(); ?>
	</form>
</div>
	<?php
// phpcs:enable WordPress.Security.NonceVerification
