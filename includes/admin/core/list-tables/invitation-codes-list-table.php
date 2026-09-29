<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_REQUEST['_wp_http_referer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$redirect = remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} else {
	$redirect = get_admin_url() . 'admin.php?page=um_invitation_codes';
}

// Delete actions: single row and bulk.
if ( isset( $_GET['action'] ) && 'delete' === sanitize_key( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
	$ids = array();
	if ( isset( $_REQUEST['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'um_invitation_code_delete' . absint( $_REQUEST['id'] ) . get_current_user_id() );
		$ids = array( absint( $_REQUEST['id'] ) );
	} elseif ( isset( $_REQUEST['item'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'bulk-um_invitation_codes' );
		$ids = array_map( 'absint', (array) wp_unslash( $_REQUEST['item'] ) );
	}

	if ( ! count( array_filter( $ids ) ) ) {
		um_js_redirect( $redirect );
	}

	UM()->invitation_codes()->delete_codes( $ids );

	um_js_redirect( add_query_arg( 'msg', 'd', $redirect ) );
}

//remove extra query arg
if ( ! empty( $_GET['_wp_http_referer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	um_js_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


/**
 * Class UM_Invitation_Codes_List_Table
 *
 * @since 2.15.0
 */
class UM_Invitation_Codes_List_Table extends WP_List_Table {


	/**
	 * UM_Invitation_Codes_List_Table constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'invitation code', 'ultimate-member' ),
				'plural'   => 'um_invitation_codes',
				'ajax'     => false,
			)
		);
	}


	/**
	 * Get list of columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'code'       => __( 'Code', 'ultimate-member' ),
			'status'     => __( 'Status', 'ultimate-member' ),
			'expiry'     => __( 'Expires', 'ultimate-member' ),
			'created_at' => __( 'Created', 'ultimate-member' ),
			'used_by'    => __( 'Used by', 'ultimate-member' ),
		);
	}


	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'code'       => array( 'code', false ),
			'status'     => array( 'status', false ),
			'expiry'     => array( 'expiry', false ),
			'created_at' => array( 'created_at', false ),
		);
	}


	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'ultimate-member' ),
		);
	}


	/**
	 * Get status filter views.
	 *
	 * @return array
	 */
	public function get_views() {
		$counts  = UM()->invitation_codes()->get_counts();
		$current = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$base  = add_query_arg( array( 'page' => 'um_invitation_codes' ), admin_url( 'admin.php' ) );
		$views = array(
			'all'       => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( remove_query_arg( 'status', $base ) ),
				'' === $current ? ' class="current"' : '',
				__( 'All', 'ultimate-member' ),
				$counts['total']
			),
			'available' => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', 'available', $base ) ),
				'available' === $current ? ' class="current"' : '',
				__( 'Available', 'ultimate-member' ),
				$counts['available']
			),
			'expired'   => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', 'expired', $base ) ),
				'expired' === $current ? ' class="current"' : '',
				__( 'Expired', 'ultimate-member' ),
				$counts['expired']
			),
			'used'      => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', 'used', $base ) ),
				'used' === $current ? ' class="current"' : '',
				__( 'Used', 'ultimate-member' ),
				$counts['used']
			),
		);

		return $views;
	}


	/**
	 * Prepare list items.
	 */
	public function prepare_items() {
		$per_page = 20;
		$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args = array(
			'per_page' => $per_page,
			'page'     => $paged,
			'search'   => isset( $_GET['s'] ) ? wp_unslash( $_GET['s'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'status'   => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'order'    => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'DESC', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		$data                  = UM()->invitation_codes()->get_codes( $args );
		$this->items           = $data['items'];
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'code' );

		$this->set_pagination_args(
			array(
				'total_items' => $data['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $data['total'] / $per_page ),
			)
		);
	}


	/**
	 * Message when no codes found.
	 */
	public function no_items() {
		esc_html_e( 'No invitation codes found. Generate your first batch with the form above.', 'ultimate-member' );
	}


	/**
	 * Checkbox column.
	 *
	 * @param object $item Code row.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="item[]" value="%d" />', absint( $item->id ) );
	}


	/**
	 * Code column with row actions.
	 *
	 * @param object $item Code row.
	 *
	 * @return string
	 */
	public function column_code( $item ) {
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'   => 'um_invitation_codes',
					'action' => 'delete',
					'id'     => absint( $item->id ),
				),
				admin_url( 'admin.php' )
			),
			'um_invitation_code_delete' . absint( $item->id ) . get_current_user_id()
		);

		$actions = array(
			'delete' => '<a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Delete this invitation code?', 'ultimate-member' ) ) . '\');">' . esc_html__( 'Delete', 'ultimate-member' ) . '</a>',
		);

		return '<strong><code>' . esc_html( $item->code ) . '</code></strong>' . $this->row_actions( $actions );
	}


	/**
	 * Status column.
	 *
	 * @param object $item Code row.
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		if ( 'used' === $item->status ) {
			return __( 'Used', 'ultimate-member' );
		}

		if ( ! empty( $item->expiry ) && strtotime( $item->expiry ) <= strtotime( current_time( 'mysql' ) ) ) {
			return __( 'Expired', 'ultimate-member' );
		}

		return __( 'Available', 'ultimate-member' );
	}


	/**
	 * Expiry column.
	 *
	 * @param object $item Code row.
	 *
	 * @return string
	 */
	public function column_expiry( $item ) {
		if ( empty( $item->expiry ) ) {
			return __( 'Never', 'ultimate-member' );
		}

		return date_i18n( get_option( 'date_format' ), strtotime( $item->expiry ) );
	}


	/**
	 * Created column.
	 *
	 * @param object $item Code row.
	 *
	 * @return string
	 */
	public function column_created_at( $item ) {
		if ( empty( $item->created_at ) ) {
			return '';
		}

		return date_i18n( get_option( 'date_format' ), strtotime( $item->created_at ) );
	}


	/**
	 * Used by column.
	 *
	 * @param object $item Code row.
	 *
	 * @return string
	 */
	public function column_used_by( $item ) {
		if ( empty( $item->used_by ) ) {
			return '';
		}

		$user = get_userdata( absint( $item->used_by ) );
		if ( ! $user ) {
			return '';
		}

		$edit_link = get_edit_user_link( $user->ID );
		if ( $edit_link ) {
			return '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $user->user_login ) . '</a>';
		}

		return esc_html( $user->user_login );
	}


	/**
	 * Default column output.
	 *
	 * @param object $item        Code row.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( isset( $item->$column_name ) ) {
			return esc_html( $item->$column_name );
		}

		return '';
	}
}

UM()->invitation_codes()->maybe_create_table();

$list_table = new UM_Invitation_Codes_List_Table();
$list_table->prepare_items();

$msg = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( 'd' === $msg ) {
	echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Invitation code(s) deleted.', 'ultimate-member' ) . '</p></div>';
} elseif ( 'g' === $msg ) {
	$generated_count = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	/* translators: %s: number of generated codes. */
	$generated_message = sprintf( _n( '%s invitation code has been generated.', '%s invitation codes have been generated.', $generated_count, 'ultimate-member' ), number_format_i18n( $generated_count ) );
	echo '<div id="message" class="updated fade"><p>' . esc_html( $generated_message ) . '</p></div>';
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Invitation Codes', 'ultimate-member' ); ?></h1>
	<hr class="wp-header-end" />

	<div class="um-invitation-generate postbox">
		<h2 class="hndle"><span><?php esc_html_e( 'Generate invitation codes', 'ultimate-member' ); ?></span></h2>
		<div class="inside">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><label for="um_ic_count"><?php esc_html_e( 'Number of codes', 'ultimate-member' ); ?></label></th>
						<td>
							<input type="number" id="um_ic_count" name="um_ic_count" min="1" max="100" value="10" class="small-text" />
							<span class="description"><?php esc_html_e( 'Between 1 and 100 per batch.', 'ultimate-member' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="um_ic_prefix"><?php esc_html_e( 'Code prefix', 'ultimate-member' ); ?></label></th>
						<td>
							<input type="text" id="um_ic_prefix" name="um_ic_prefix" maxlength="20" class="regular-text" />
							<span class="description"><?php esc_html_e( 'Optional. Letters, numbers, dash and underscore only.', 'ultimate-member' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="um_ic_length"><?php esc_html_e( 'Code length', 'ultimate-member' ); ?></label></th>
						<td>
							<input type="number" id="um_ic_length" name="um_ic_length" min="6" max="32" value="12" class="small-text" />
							<span class="description"><?php esc_html_e( 'Random part length, between 6 and 32 characters.', 'ultimate-member' ); ?></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="um_ic_expiry"><?php esc_html_e( 'Expires on', 'ultimate-member' ); ?></label></th>
						<td>
							<input type="date" id="um_ic_expiry" name="um_ic_expiry" />
							<span class="description"><?php esc_html_e( 'Optional. Codes stop working after this date.', 'ultimate-member' ); ?></span>
						</td>
					</tr>
				</tbody>
			</table>
			<p>
				<button type="button" id="um-invitation-generate" class="button button-primary"><?php esc_html_e( 'Generate', 'ultimate-member' ); ?></button>
				<span class="spinner"></span>
			</p>
			<p class="um-invitation-generate-error" style="display:none;color:#b32d2e;"></p>
		</div>
	</div>

	<form action="" method="get" name="um-invitation-codes" id="um-invitation-codes">
		<input type="hidden" name="page" value="um_invitation_codes" />
		<?php $list_table->views(); ?>
		<?php $list_table->search_box( __( 'Search invitation codes', 'ultimate-member' ), 'um-invitation-codes-search' ); ?>
		<?php $list_table->display(); ?>
	</form>
</div>
