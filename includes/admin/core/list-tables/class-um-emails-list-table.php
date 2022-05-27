<?php
/**
 * Render Email table in the tab UM > Settings > Email
 *
 * @package um\admin\core
 * @see     \um\admin\core\Admin_Settings::settings_before_email_tab()
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
	$redirect = remove_query_arg( array( '_wp_http_referer' ), esc_url_raw( wp_unslash( $_REQUEST['_wp_http_referer'] ) ) );
} else {
	$redirect = get_admin_url() . 'admin.php?page=ultimatemember';
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
	 * Class UM_Emails_List_Table
	 */
	class UM_Emails_List_Table extends WP_List_Table {


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
			$columns  = $this->get_columns();
			$hidden   = array();
			$sortable = $this->get_sortable_columns();

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
		 * Return content for the column "Email".
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_email( $item ) {
			$active = UM()->options()->get( $item['key'] . '_on' );

			$icon = ! empty( $active ) ? 'um-notification-is-active dashicons-yes' : 'dashicons-no-alt';
			$link = add_query_arg( array( 'email' => $item['key'] ) );
			$text = '<span class="dashicons um-notification-status ' . esc_attr( $icon ) . '"></span><a href="' . esc_url( $link ) . '"><strong>' . $item['title'] . '</strong></a>';

			if ( ! empty( $item['description'] ) ) {
				$text .= ' <span class="um_tooltip dashicons dashicons-editor-help" title="' . esc_attr__( $item['description'], 'ultimate-member' ) . '"></span>';
			}

			return $text;
		}


		/**
		 * Return content for the column "Recipient(s)".
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_recipients( $item ) {
			if ( 'admin' === $item['recipient'] ) {
				return UM()->options()->get( 'admin_email' );
			} else {
				return __( 'Member', 'ultimate-member' );
			}
		}


		/**
		 * Return content for the column with actions.
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_configure( $item ) {
			return '<a class="button um-email-configure" href="' . esc_url( add_query_arg( array( 'email' => $item['key'] ) ) ) . '" title="' . esc_attr__( 'Edit template', 'ultimate-member' ) . '"><span class="dashicons dashicons-admin-generic"></span></a>';
		}


		/**
		 * Return content for the column with translations.
		 * This column appears is the plugin WPML is active.
		 *
		 * @param  array $item  Item.
		 *
		 * @return string
		 */
		public function column_icl_translations( $item ) {
			return UM()->external_integrations()->wpml_column_content( $item );
		}


		/**
		 * Set pagination.
		 *
		 * @see   WP_List_Table::set_pagination_args()
		 *
		 * @param array $attr  Array of arguments with information about the pagination.
		 */
		public function wpc_set_pagination_args( $attr = array() ) {
			$this->set_pagination_args( $attr );
		}
	}
}


$list_table = new UM_Emails_List_Table(
	array(
		'singular' => __( 'Email Notification', 'ultimate-member' ),
		'plural'   => __( 'Email Notifications', 'ultimate-member' ),
		'ajax'     => false,
	)
);

/**
 * UM hook
 *
 * @type filter
 * @title um_email_templates_columns
 * @description Email Notifications List Table columns
 * @input_vars
 * [{"var":"$columns","type":"array","desc":"Columns"}]
 * @change_log
 * ["Since: 2.0"]
 * @usage add_filter( 'um_email_templates_columns', 'function_name', 10, 1 );
 * @example
 * <?php
 * add_filter( 'um_email_templates_columns', 'my_email_templates_columns', 10, 1 );
 * function my_email_templates_columns( $columns ) {
 *     // your code here
 *     $columns['my-custom-column'] = 'My Custom Column';
 *     return $columns;
 * }
 * ?>
 */
$columns = apply_filters(
	'um_email_templates_columns',
	array(
		'email'      => __( 'Email', 'ultimate-member' ),
		'recipients' => __( 'Recipient(s)', 'ultimate-member' ),
		'configure'  => __( 'Edit', 'ultimate-member' ),
	)
);

$list_table->set_columns( $columns );

$emails         = UM()->config()->email_notifications;
$pagenum        = $list_table->get_pagenum();
$items_per_page = 20;

$list_table->prepare_items();
$list_table->items = array_slice( $emails, ( $pagenum - 1 ) * $items_per_page, $items_per_page );

$list_table->wpc_set_pagination_args(
	array(
		'total_items' => count( $emails ),
		'per_page'    => $items_per_page,
	)
);
?>

<p class="description" style="margin: 20px 0 0 0;">
	<?php
	// translators: %: A link for documentation.
	echo wp_kses_post( sprintf( __( 'You may get more details about email notifications customization <a href="%s" target="_blank">here</a>', 'ultimate-member' ), 'https://docs.ultimatemember.com/article/1335-email-templates' ) );
	?>
</p>

<form action="" method="get" name="um-settings-emails" id="um-settings-emails">
	<input type="hidden" name="page" value="um_options" />
	<input type="hidden" name="tab" value="email" />

	<?php $list_table->display(); ?>
</form>
