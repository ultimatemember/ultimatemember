<?php if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Class UM_Modules_List_Table
 */
class UM_Modules_List_Table extends WP_List_Table {


	/**
	 * @var string
	 */
	var $no_items_message = '';


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

		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, array(), $sortable );

		$modules = UM()->modules()->get_list();

		@uasort($modules, function ( $a, $b ) {
			if ( strtolower( $a['title'] ) == strtolower( $b['title'] ) ) {
				return 0;
			}
			return ( strtolower( $a['title'] ) < strtolower( $b['title'] ) ) ? -1 : 1;
		});

		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 999 );
		$paged = $this->get_pagenum();

		$this->items = array_slice( $modules, ( $paged - 1 ) * $per_page, $per_page );

		$this->set_pagination_args( array(
			'total_items' => count( $modules ),
			'per_page'    => $per_page,
		) );
	}


	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object|array $item The current item
	 */
	public function single_row( $item ) {
		$is_active = UM()->modules()->is_active( $item['key'] );
		$is_disabled = UM()->modules()->is_disabled( $item['key'] );

		if ( ! UM()->is_legacy ) {
			$class = $is_disabled ? 'disabled ' : 'enabled ';
			$class .= $is_active ? 'active' : 'inactive';
		} else {
			$class = 'unavailable ';
		}

		echo sprintf( '<tr class="%s">', esc_attr( $class ) );
		$this->single_row_columns( $item );
		echo '</tr>';
	}


	function single_row_columns( $item ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

		foreach ( $columns as $column_name => $column_display_name ) {
			$classes = "$column_name column-$column_name";
			if ( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}

			if ( in_array( $column_name, $hidden, true ) ) {
				$classes .= ' hidden';
			}

			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
			$data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

			$attributes = "class='$classes'";

			if ( 'cb' === $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb( $item );
				echo '</th>';
			} elseif ( method_exists( $this, '_column_' . $column_name ) ) {
				echo call_user_func(
					array( $this, '_column_' . $column_name ),
					$item,
					$classes,
					$data,
					$primary
				);
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				echo "<td $attributes>";
				echo call_user_func( array( $this, 'column_' . $column_name ), $item );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			} else {
				echo "<td $attributes>";
				echo $this->column_default( $item, $column_name );
				echo $this->handle_row_actions( $item, $column_name, $primary );
				echo '</td>';
			}
		}
	}


	/**
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		if ( isset( $item[ $column_name ] ) ) {
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
	function set_columns( $args = array() ) {
		if ( count( $this->bulk_actions ) ) {
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


	function get_table_classes() {
		return array( 'widefat', $this->_args['plural'] );
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
	 * @param object $item
	 *
	 * @return string
	 */
	function column_type( $item ) {
		$type = '';
		switch ( $item['type'] ) {
			case 'free':
				$type = __( 'Free', 'ultimate-member' );
				break;
			case 'pro':
				$type = __( 'Pro', 'ultimate-member' );
				break;
			case 'premium':
				$type = __( 'Premium', 'ultimate-member' );
				break;
		}

		return $type;
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_module_title( $item ) {
		$actions = array();

		if ( ! UM()->is_legacy ) {
			if ( UM()->modules()->can_activate( $item['key'] ) ) {
				$actions['activate'] = '<a href="admin.php?page=um_options&tab=modules&action=activate&slug=' . esc_attr( $item['key'] ) . '&_wpnonce=' . wp_create_nonce( 'um_module_activate' . $item['key'] . get_current_user_id() ) . '">' . __( 'Activate', 'ultimate-member' ). '</a>';
			}

			$module_data = UM()->modules()->get_data( $item['key'] );

			if ( array_key_exists( 'docs_url', $module_data ) ) {
				$actions['docs'] = '<a href="' . esc_url_raw( $module_data['docs_url'] ) . '" target="_blank">' . __( 'Documentation', 'ultimate-member' ). '</a>';
			}

			if ( UM()->modules()->can_deactivate( $item['key'] ) ) {
				$actions['deactivate'] = '<a href="admin.php?page=um_options&tab=modules&action=deactivate&slug=' . esc_attr( $item['key'] ) . '&_wpnonce=' . wp_create_nonce( 'um_module_deactivate' . $item['key'] . get_current_user_id() ) . '" class="delete">' . __( 'Deactivate', 'ultimate-member' ). '</a>';
			}

			if ( UM()->modules()->can_flush( $item['key'] ) ) {
				$actions['flush-data'] = '<a href="admin.php?page=um_options&tab=modules&action=flush-data&slug=' . esc_attr( $item['key'] ) . '&_wpnonce=' . wp_create_nonce( 'um_module_flush' . $item['key'] . get_current_user_id() ) . '" class="delete">' . __( 'Flush data', 'ultimate-member' ). '</a>';
			}

			$actions = apply_filters( 'um_module_list_table_actions', $actions, $item['key'] );
		}

		$column_content = sprintf('<div class="um-module-data-wrapper"><div class="um-module-title-wrapper">%1$s %2$s</div></div>', '<strong>' . esc_html( $item['title'] ) . '</strong>', $this->row_actions( $actions, true ) );

		return $column_content;
	}
}


$ListTable = new UM_Modules_List_Table( array(
	'singular' => __( 'Module', 'ultimate-member' ),
	'plural'   => __( 'Modules', 'ultimate-member' ),
	'ajax'     => false,
) );

$bulk_actions = array();
if ( ! UM()->is_legacy ) {
	$bulk_actions = array(
		'activate'   => __( 'Activate', 'ultimate-member' ),
		'deactivate' => __( 'Deactivate', 'ultimate-member' ),
		'flush-data' => __( 'Flush module data', 'ultimate-member' ),
	);
}

$ListTable->set_bulk_actions( $bulk_actions );

$ListTable->set_columns( array(
	'module_title' => __( 'Module', 'ultimate-member' ),
	'type'         => __( 'Type', 'ultimate-member' ),
	'description'  => __( 'Description', 'ultimate-member' ),
) );

$ListTable->prepare_items();

if ( ! empty( $_GET['msg'] ) ) {
	switch( sanitize_key( $_GET['msg'] ) ) {
		case 'a':
			echo '<div class="clear"></div><div id="message" class="updated fade"><p>' . __( 'Module <strong>activated</strong> successfully.', 'ultimate-member' ) . '</p></div>';
			break;
		case 'd':
			echo '<div class="clear"></div><div id="message" class="updated fade"><p>' . __( 'Module <strong>deactivated</strong> successfully.', 'ultimate-member' ) . '</p></div>';
			break;
		case 'f':
			echo '<div class="clear"></div><div id="message" class="updated fade"><p>' . __( 'Module\'s data is <strong>flushed</strong> successfully.', 'ultimate-member' ) . '</p></div>';
			break;
	}
} ?>

<div class="clear"></div>

<?php ob_start(); ?>

<div id="um-plan">
	<p><?php esc_html_e( 'You are using the free version of Ultimate Member. With this you have access to the modules below. Upgrade to Ultimate Member Pro to get access to the pro modules.', 'ultimate-member' ); ?></p>
	<p><?php echo wp_kses( sprintf( __( 'Click <a href="%s" target="_blank">here</a> to view our different plans for Ultimate Member Pro.', 'ultimate-member' ), 'https://ultimatemember.com/pricing-beta/' ), array( 'a' => array( 'href' => array(), 'target' => true ) ) ); ?></p>
</div>

<?php
$same_page_license = ob_get_clean();
$same_page_license = apply_filters( 'um_modules_page_same_page_license', $same_page_license );

echo $same_page_license;
?>

<form action="" method="get" name="um-modules" id="um-modules">
	<input type="hidden" name="page" value="um_options" />
	<input type="hidden" name="tab" value="modules" />
	<?php $ListTable->display(); ?>
</form>
