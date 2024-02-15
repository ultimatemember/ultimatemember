<?php
if ( ! defined( 'ABSPATH' ) ) {
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
		$screen = $this->screen;

		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, array(), $sortable );

		$templates = UM()->common()->theme()->build_templates_data();

		@uasort(
			$templates,
			function ( $a, $b ) {
				if ( strtolower( $a['status_code'] ) === strtolower( $b['status_code'] ) ) {
					return 0;
				}
				return ( strtolower( $a['status_code'] ) < strtolower( $b['status_code'] ) ) ? -1 : 1;
			}
		);

		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 999 );
		$paged    = $this->get_pagenum();

		$this->items = array_slice( $templates, ( $paged - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => count( $templates ),
				'per_page'    => $per_page,
			)
		);
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
	public function set_columns( $args = array() ) {
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
		return $item['theme_version'] ? $item['theme_version'] : '-';
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$icon = 1 === $item['status_code'] ? 'um-notification-is-active dashicons-yes' : 'dashicons-no-alt';
		return $item['status'] . ' <span class="dashicons um-notification-status ' . esc_attr( $icon ) . '"></span>';
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		// Stop displaying tablenav.
	}
}

$list_table = new UM_Versions_List_Table(
	array(
		'singular' => __( 'Template', 'ultimate-member' ),
		'plural'   => __( 'Templates', 'ultimate-member' ),
		'ajax'     => false,
	)
);

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

$list_table->set_columns( $columns );
$list_table->prepare_items();
?>

<form action="" method="get" name="um-settings-template-versions" id="um-settings-template-versions">
	<input type="hidden" name="page" value="um_options" />
	<input type="hidden" name="tab" value="advanced" />
	<input type="hidden" name="section" value="override_templates" />
	<?php $list_table->display(); ?>
</form>
