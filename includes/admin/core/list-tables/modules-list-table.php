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
	var $actions = [];


	/**
	 * @var array
	 */
	var $bulk_actions = [];


	/**
	 * @var array
	 */
	var $columns = [];


	/**
	 * UM_Roles_List_Table constructor.
	 *
	 * @param array $args
	 */
	function __construct( $args = [] ){
		$args = wp_parse_args( $args, [
			'singular'  => __( 'item', 'ultimate-member' ),
			'plural'    => __( 'items', 'ultimate-member' ),
			'ajax'      => false
		] );

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
		return call_user_func_array( [ $this, $name ], $arguments );
	}


	/**
	 *
	 */
	function prepare_items() {
		$screen = $this->screen;

		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, [], $sortable ];

		$modules = UM()->modules()->get_list();

		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 999 );
		$paged = $this->get_pagenum();

		$this->items = array_slice( $modules, ( $paged - 1 ) * $per_page, $per_page );

		$this->set_pagination_args( [
			'total_items' => count( $modules ),
			'per_page'    => $per_page,
		] );
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

		$class = $is_disabled ? 'disabled ' : 'enabled ';
		$class .= $is_active ? 'active' : 'inactive';

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
					[ $this, '_column_' . $column_name ],
					$item,
					$classes,
					$data,
					$primary
				);
			} elseif ( method_exists( $this, 'column_' . $column_name ) ) {
				echo "<td $attributes>";
				echo call_user_func( [ $this, 'column_' . $column_name ], $item );
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
	function set_columns( $args = [] ) {
		if ( count( $this->bulk_actions ) ) {
			$args = array_merge( [ 'cb' => '<input type="checkbox" />' ], $args );
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
	function set_actions( $args = [] ) {
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
	function set_bulk_actions( $args = [] ) {
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
		return [ 'widefat', 'striped', $this->_args['plural'] ];
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
	function column_module( $item ) {
		$actions = [];

		if ( UM()->modules()->can_activate( $item['key'] ) ) {
			$actions['activate'] = '<a href="admin.php?page=um-modules&action=activate&slug=' . $item['key'] . '&_wpnonce=' . wp_create_nonce( 'um_module_activate' . $item['key'] . get_current_user_id() ) . '">' . __( 'Activate', 'ultimate-member' ). '</a>';
		}

		if ( UM()->modules()->can_deactivate( $item['key'] ) ) {
			$actions['deactivate'] = '<a href="admin.php?page=um-modules&action=deactivate&slug=' . $item['key'] . '&_wpnonce=' . wp_create_nonce( 'um_module_deactivate' . $item['key'] . get_current_user_id() ) . '" class="delete">' . __( 'Deactivate', 'ultimate-member' ). '</a>';
		}

		if ( UM()->modules()->can_flush( $item['key'] ) ) {
			$actions['flush-data'] = '<a href="admin.php?page=um-modules&action=flush-data&slug=' . $item['key'] . '&_wpnonce=' . wp_create_nonce( 'um_module_flush' . $item['key'] . get_current_user_id() ) . '" class="delete">' . __( 'Flush data', 'ultimate-member' ). '</a>';
		}

		if ( file_exists( $item['path'] . DIRECTORY_SEPARATOR . 'icon.png' ) ) {
			$column_content = sprintf('<div class="um-module-data-wrapper"><div class="um-module-icon-wrapper">%1$s</div><div class="um-module-title-wrapper">%2$s %3$s</div></div>', '<img class="um-module-icon" src="' . $item['url'] . '/icon.png" title="' . stripslashes( $item['title'] ) . '" />','<strong>' . stripslashes( $item['title'] ) . '</strong>', $this->row_actions( $actions ) );
		} else {
			$column_content = sprintf('<div class="um-module-data-wrapper um-module-no-icon"><div class="um-module-title-wrapper">%1$s %2$s</div></div>', '<strong>' . stripslashes( $item['title'] ) . '</strong>', $this->row_actions( $actions ) );
		}

		return $column_content;
	}
}


$ListTable = new UM_Modules_List_Table( [
	'singular'  => __( 'Module', 'ultimate-member' ),
	'plural'    => __( 'Modules', 'ultimate-member' ),
	'ajax'      => false,
] );

$ListTable->set_bulk_actions( [
	'activate'      => __( 'Activate', 'ultimate-member' ),
	'deactivate'    => __( 'Deactivate', 'ultimate-member' ),
	'flush-data'    => __( 'Flush module data', 'ultimate-member' ),
] );

$ListTable->set_columns( [
	'module'        => __( 'Module', 'ultimate-member' ),
	'description'   => __( 'Description', 'ultimate-member' ),
] );


$ListTable->prepare_items(); ?>

<div class="wrap">
	<h2>
		<?php _e( 'Ultimate Member - Modules', 'ultimate-member' ) ?>
	</h2>

	<?php if ( ! empty( $_GET['msg'] ) ) {
		switch( sanitize_key( $_GET['msg'] ) ) {
			case 'a':
				echo '<div id="message" class="updated fade"><p>' . __( 'Module <strong>activated</strong> successfully.', 'ultimate-member' ) . '</p></div>';
				break;
			case 'd':
				echo '<div id="message" class="updated fade"><p>' . __( 'Module <strong>deactivated</strong> successfully.', 'ultimate-member' ) . '</p></div>';
				break;
			case 'f':
				echo '<div id="message" class="updated fade"><p>' . __( 'Module\'s data is <strong>flushed</strong> successfully.', 'ultimate-member' ) . '</p></div>';
				break;
		}
	} ?>

	<form action="" method="get" name="um-modules" id="um-modules">
		<input type="hidden" name="page" value="um-modules" />
		<?php $ListTable->display(); ?>
	</form>
</div>