<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ListTable = new um\admin\list_table\Field_Groups( array(
	'singular' => __( 'Field Group', 'ultimate-member' ),
	'plural'   => __( 'Field Groups', 'ultimate-member' ),
	'ajax'     => false,
) );

$ListTable->set_bulk_actions( array(
	'activate'   => __( 'Activate', 'ultimate-member' ),
	'deactivate' => __( 'Deactivate', 'ultimate-member' ),
	'delete'     => __( 'Delete', 'ultimate-member' ),
) );

$ListTable->set_columns( array(
	'title'       => __( 'Title', 'ultimate-member' ),
	'description' => __( 'Description', 'ultimate-member' ),
	'status'      => __( 'Status', 'ultimate-member' ),
	'key'         => __( 'Key', 'ultimate-member' ),
	'fields'      => __( 'No.of Fields', 'ultimate-member' ),
) );

$ListTable->set_sortable_columns( array(
	'title' => 'title',
) );

$ListTable->prepare_items();
?>

<div class="wrap">
	<h2>
		<?php esc_html_e( 'Field Groups', 'ultimate-member' ); ?>
		<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( array( 'page' => 'um_field_groups', 'tab' => 'add' ), admin_url( 'admin.php' ) ) ) ?>">
			<?php esc_html_e( 'Add New', 'ultimate-member' ); ?>
		</a>
	</h2>

	<?php
	if ( ! empty( $_GET['msg'] ) ) {
		switch ( sanitize_key( $_GET['msg'] ) ) {
			case 'd':
				if ( isset( $_GET['count'] ) ) {
					$count = absint( $_GET['count'] );
					if ( $count > 0 ) {
						$message = sprintf( _n( '%s field group is deleted successfully.','%s field groups are deleted successfully.', $count, 'ultimate-member' ), $count );
						echo '<div id="message" class="updated fade"><p>' . esc_html( $message ) . '</p></div>';
					}
				}
				break;
			case 'a':
				if ( isset( $_GET['count'] ) ) {
					$count = absint( $_GET['count'] );
					if ( $count > 0 ) {
						$message = sprintf( _n( '%s field group is activated successfully.','%s field groups are activated successfully.', $count, 'ultimate-member' ), $count );
						echo '<div id="message" class="updated fade"><p>' . esc_html( $message ) . '</p></div>';
					}
				}
				break;
			case 'da':
				if ( isset( $_GET['count'] ) ) {
					$count = absint( $_GET['count'] );
					if ( $count > 0 ) {
						$message = sprintf( _n( '%s field group is deactivated successfully.','%s field groups are deactivated successfully.', $count, 'ultimate-member' ), $count );
						echo '<div id="message" class="updated fade"><p>' . esc_html( $message ) . '</p></div>';
					}
				}
				break;
		}
	}
	?>

	<form action="" method="get" name="um-field-groups" id="um-field-groups">
		<input type="hidden" name="page" value="um_field_groups" />
		<?php $ListTable->display(); ?>
	</form>
</div>
