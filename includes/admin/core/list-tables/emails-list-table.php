<?php if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
	$redirect = remove_query_arg( array( '_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
} else {
	$redirect = get_admin_url(). 'admin.php?page=ultimatemember';
}

//remove extra query arg
if ( ! empty( $_GET['_wp_http_referer'] ) ) {
	um_js_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


/**
 * Class UM_Emails_List_Table
 */
class UM_Emails_List_Table extends WP_List_Table {


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
	 * UM_Emails_List_Table constructor.
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
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		// Stop displaying tablenav.
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


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_email( $item ) {
		$active = UM()->options()->get( $item['key'] . '_on' );

		$icon       = ! empty( $active ) ? 'um-notification-is-active dashicons-yes' : 'dashicons-no-alt';
		$icon_title = ! empty( $active ) ? __( 'Enabled', 'ultimate-member' ) : __( 'Disabled', 'ultimate-member' );

		$link = add_query_arg( array( 'email' => $item['key'] ), remove_query_arg( 'paged' ) );
		$text = '<span class="dashicons um-notification-status ' . esc_attr( $icon ) . '" title="' . esc_attr( $icon_title ) . '"></span><a href="' . esc_url( $link ) . '"><strong>' . esc_html( $item['title'] ) . '</strong></a>';

		if ( ! empty( $item['description'] ) ) {
			$text .= ' <span class="um_tooltip dashicons dashicons-editor-help" title="' . esc_attr( $item['description'] ) . '"></span>';
		}

		return $text;
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_recipients( $item ) {
		if ( 'admin' === $item['recipient'] ) {
			return UM()->options()->get( 'admin_email' );
		}

		return __( 'Member', 'ultimate-member' );
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_configure( $item ) {
		$edit_link = add_query_arg( array( 'email' => $item['key'] ), remove_query_arg( 'paged' ) );
		return '<a class="button um-email-configure" href="' . esc_url( $edit_link ) . '" title="' . esc_attr__( 'Manage', 'ultimate-member' ) . '">' . esc_html__( 'Manage', 'ultimate-member' ) . '</a>';
	}


	/**
	 * @param $item
	 *
	 * @return string
	 */
	function column_icl_translations( $item ) {
		return UM()->external_integrations()->wpml_column_content( $item );
	}


	/**
	 * @param array $attr
	 */
	function wpc_set_pagination_args( $attr = array() ) {
		$this->set_pagination_args( $attr );
	}
}

$ListTable = new UM_Emails_List_Table( array(
	'singular' => __( 'Email Notification', 'ultimate-member' ),
	'plural'   => __( 'Email Notifications', 'ultimate-member' ),
	'ajax'     => false,
));

$per_page = 999;
$paged    = $ListTable->get_pagenum();

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
$columns = apply_filters( 'um_email_templates_columns', array(
	'email'      => __( 'Email', 'ultimate-member' ),
	'recipients' => __( 'Recipient(s)', 'ultimate-member' ),
	'configure'  => '',
) );

$ListTable->set_columns( $columns );

$emails = UM()->config()->email_notifications;

$ListTable->prepare_items();
$ListTable->items = array_slice( $emails, ( $paged - 1 ) * $per_page, $per_page );
$ListTable->wpc_set_pagination_args( array( 'total_items' => count( $emails ), 'per_page' => $per_page ) );
?>

<h2 class="title"><?php esc_html_e( 'Email notifications', 'ultimate-member' ); ?></h2>
<p>
	<?php esc_html_e( 'Email notifications sent from Ultimate Member are listed below. Click on an email to configure it.', 'ultimate-member' ); ?>
	<br />
	<?php esc_html_e( 'Emails should be sent from an email using your website\'s domain name. We highly recommend using a SMTP service email delivery.', 'ultimate-member' ); ?>
	<?php echo wp_kses( sprintf( __( 'Please see this <a href="%s" target="_blank">doc</a> for more information.', 'ultimate-member' ), 'https://docs.ultimatemember.com/article/116-not-receiving-user-emails-or-admin-notifications' ), UM()->get_allowed_html( 'admin_notice' ) ); ?>
</p>
<div class="clear"></div>
<form action="" method="get" name="um-settings-emails" id="um-settings-emails">
	<input type="hidden" name="page" value="um_options" />
	<input type="hidden" name="tab" value="email" />

	<?php $ListTable->display(); ?>
</form>
<div class="clear"></div>
