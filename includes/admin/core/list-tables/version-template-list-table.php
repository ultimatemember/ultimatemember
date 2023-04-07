<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class UM_Versions_List_Table
 */
class UM_Versions_List_Table extends WP_List_Table {


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
	 * UM_Versions_List_Table constructor.
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
		echo $this->no_items_message;
	}


	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_sortable_columns( $args = array() ) {
		$return_args = array();
		foreach ( $args as $k=>$val ) {
			if ( is_numeric( $k ) ) {
				$return_args[ $val ] = array( $val, $val == $this->default_sorting_field );
			} elseif( is_string( $k ) ) {
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
	 * @param $item
	 *
	 * @return string
	 */
	public function column_template( $item ) {
		$output  = esc_html__( 'Core path - ', 'ultimate-member' );
		$output .= $item['core_file'] . '<br>';
		$output .= esc_html__( 'Theme path - ', 'ultimate-member' );
		$output .= $item['theme_file'];

		return $output;
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function column_core_version( $item ) {
		return $item['core_version'];
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function column_theme_version( $item ) {
		$theme_version = $item['theme_version'] ? $item['theme_version'] : '-';

		return $theme_version;
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$icon = 1 === $item['status_code'] ? 'um-notification-is-active dashicons-yes' : 'dashicons-no-alt';
		$text = $item['status'] . ' <span class="dashicons um-notification-status ' . esc_attr( $icon ) . '"></span>';

		return $text;
	}


	/**
	 * @param array $attr
	 */
	public function wpc_set_pagination_args( $attr = array() ) {
		$this->set_pagination_args( $attr );
	}
}

$ListTable = new UM_Versions_List_Table(
	array(
		'singular' => __( 'Template', 'ultimate-member' ),
		'plural'   => __( 'Templates', 'ultimate-member' ),
		'ajax'     => false,
	)
);

$per_page = 999;
$paged    = $ListTable->get_pagenum();

/**
 * UM hook
 *
 * @type filter
 * @title um_versions_templates_columns
 * @description Version Templates List Table columns
 * @input_vars
 * [{"var":"$columns","type":"array","desc":"Columns"}]
 * @change_log
 * ["Since: 2.0"]
 * @usage add_filter( 'um_versions_templates_columns', 'function_name', 10, 1 );
 * @example
 * <?php
 * add_filter( 'um_versions_templates_columns', 'um_versions_templates_columns', 10, 1 );
 * function um_versions_templates_columns( $columns ) {
 *     // your code here
 *     $columns['my-custom-column'] = 'My Custom Column';
 *     return $columns;
 * }
 * ?>
 */
$columns = apply_filters(
	'um_versions_templates_columns',
	array(
		'template'      => __( 'Template', 'ultimate-member' ),
		'core_version'  => __( 'Core version', 'ultimate-member' ),
		'theme_version' => __( 'Theme version', 'ultimate-member' ),
		'status'        => __( 'Status', 'ultimate-member' ),
	)
);

$ListTable->set_columns( $columns );

$templates = UM()->admin_settings()->get_override_templates();

$ListTable->prepare_items();
$ListTable->items = array_slice( $templates, ( $paged - 1 ) * $per_page, $per_page );
$ListTable->wpc_set_pagination_args(
	array(
		'total_items' => count( $templates ),
		'per_page'    => $per_page,
	)
); ?>

<form action="" method="get" name="um-settings-template-versions" id="um-settings-template-versions">
	<input type="hidden" name="page" value="um_options" />
	<input type="hidden" name="tab" value="override_templates" />

	<?php $ListTable->display(); ?>
</form>
