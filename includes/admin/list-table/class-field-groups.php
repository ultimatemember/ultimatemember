<?php
namespace um\admin\list_table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Field_Groups
 */
class Field_Groups extends \WP_List_Table {

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
	 * Fields_Groups constructor.
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
		global $wpdb;

		$screen = $this->screen;

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 20 );
		$paged    = $this->get_pagenum();

		$order = ( isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_key( $_GET['order'] ) ) ) ? 'ASC' : 'DESC';

		$fields_groups_total = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}um_field_groups"
		);

		$this->items = array();
		if ( ! empty( $fields_groups_total ) ) {
			$this->items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT fg.*,
						  COUNT(f.group_id) AS `fields`
					FROM {$wpdb->prefix}um_field_groups AS fg
					LEFT JOIN {$wpdb->prefix}um_fields AS f ON ( f.group_id = fg.id AND f.type != 'row' )
					GROUP BY fg.id
					ORDER BY fg.title {$order} 
					LIMIT %d, %d",
					( $paged - 1 ) * $per_page,
					$per_page
				),
				ARRAY_A
			);
		}

		$this->set_pagination_args( array(
			'total_items' => $fields_groups_total,
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
		return sprintf( '<input type="checkbox" name="item[]" value="%s" />', $item['id'] );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_title( $item ) {
		$actions = array();

		$actions['edit']      = '<a href="admin.php?page=um_field_groups&tab=edit&id=' . esc_attr( $item['id'] ) . '">' . __( 'Edit', 'ultimate-member' ) . '</a>';
		if ( ! empty( $item['status'] ) && 'active' === $item['status'] ) {
			$actions['deactivate'] = '<a href="admin.php?page=um_field_groups&action=deactivate&id=' . esc_attr( $item['id'] ) . '&_wpnonce=' . wp_create_nonce( 'um_field_group_deactivate' . $item['id'] . get_current_user_id() ) . '" onclick="return confirm( \'' . esc_html__( 'Are you sure you want to deactivate this fields group?', 'ultimate-member' ) . '\' );">' . esc_html__( 'Deactivate', 'ultimate-member' ) . '</a>';
		} elseif ( ! empty( $item['status'] ) && 'inactive' === $item['status'] ) {
			$actions['activate'] = '<a href="admin.php?page=um_field_groups&action=activate&id=' . esc_attr( $item['id'] ) . '&_wpnonce=' . wp_create_nonce( 'um_field_group_activate' . $item['id'] . get_current_user_id() ) . '" onclick="return confirm( \'' . esc_html__( 'Are you sure you want to activate this fields group?', 'ultimate-member' ) . '\' );">' . esc_html__( 'Activate', 'ultimate-member' ) . '</a>';
		}
		$actions['delete'] = '<a href="admin.php?page=um_field_groups&action=delete&id=' . esc_attr( $item['id'] ) . '&_wpnonce=' . wp_create_nonce( 'um_field_group_delete' . $item['id'] . get_current_user_id() ) . '" onclick="return confirm( \'' . esc_html__( 'Are you sure you want to delete this fields group?', 'ultimate-member' ) . '\' );">' . esc_html__( 'Delete', 'ultimate-member' ) . '</a>';

		return sprintf('%1$s %2$s', '<strong><a class="row-title" href="admin.php?page=um_field_groups&tab=edit&id=' . esc_attr( $item['id'] ) . '">' . esc_html( $item['title'] ) . '</a></strong>', $this->row_actions( $actions ) );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_description( $item ) {
		return ! empty( $item['description'] ) ? nl2br( esc_html( $item['description'] ) ) : '';
	}

	/**
	 * @param $item
	 */
	function column_key( $item ) {
		echo ! empty( $item['group_key'] ) ? esc_html( $item['group_key'] ) : '';
	}

	/**
	 * @param $item
	 */
	function column_fields( $item ) {
		echo ! empty( $item['fields'] ) ? esc_html( $item['fields'] ) : 0;
	}

	/**
	 * @param $item
	 */
	function column_status( $item ) {
		$statuses_map = array(
			'active'   => __( 'Active', 'ultimate-member' ),
			'inactive' => __( 'Inactive', 'ultimate-member' ),
			'invalid'  => __( 'Invalid', 'ultimate-member' ),
		);
		echo ( ! empty( $item['status'] ) && array_key_exists( $item['status'], $statuses_map ) ) ? esc_html( $statuses_map[ $item['status'] ] ) : esc_html( $statuses_map['invalid'] );
	}
}
