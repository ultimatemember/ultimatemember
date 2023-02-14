<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ListTable = new um\admin\list_table\Roles( array(
	'singular' => __( 'Role', 'ultimate-member' ),
	'plural'   => __( 'Roles', 'ultimate-member' ),
	'ajax'     => false,
) );

$ListTable->set_bulk_actions( array(
	'delete' => __( 'Delete', 'ultimate-member' ),
) );

$ListTable->set_columns( array(
	'title'        => __( 'Role Title', 'ultimate-member' ),
	'roleid'       => __( 'Role ID', 'ultimate-member' ),
	'users'        => __( 'No.of Members', 'ultimate-member' ),
	'core'         => __( 'UM Custom Role', 'ultimate-member' ),
	'admin_access' => __( 'WP-Admin Access', 'ultimate-member' ),
	'priority'     => __( 'Priority', 'ultimate-member' ),
) );

$ListTable->set_sortable_columns( array(
	'title' => 'title',
) );

$ListTable->prepare_items();
?>

<div class="wrap">
	<h2>
		<?php _e( 'User Roles', 'ultimate-member' ) ?>
		<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( array( 'page' => 'um_roles', 'tab' => 'add' ), admin_url( 'admin.php' ) ) ) ?>">
			<?php _e( 'Add New', 'ultimate-member' ) ?>
		</a>
	</h2>

	<?php if ( ! empty( $_GET['msg'] ) ) {
		switch ( sanitize_key( $_GET['msg'] ) ) {
			case 'd':
				if ( isset( $_GET['count'] ) ) {
					$count = absint( $_GET['count'] );
					if ( $count > 0 ) {
						$message = sprintf( _n( '%s user role is <strong>deleted</strong> successfully.','%s user roles are <strong>deleted</strong> successfully.', $count, 'ultimate-member' ), $count );
						echo '<div id="message" class="updated fade"><p>' . $message . '</p></div>';
					}
				}
				break;
			case 'reset':
				if ( isset( $_GET['count'] ) ) {
					$count = absint( $_GET['count'] );
					if ( $count > 0 ) {
						$message = sprintf( _n( '%s user role\'s meta is <strong>flushed</strong> successfully.','%s user roles\' meta are <strong>flushed</strong> successfully.', $count, 'ultimate-member' ), $count );
						echo '<div id="message" class="updated fade"><p>' . $message . '</p></div>';
					}
				}
				break;
		}
	} ?>

	<form action="" method="get" name="um-roles" id="um-roles">
		<input type="hidden" name="page" value="um_roles" />
		<?php $ListTable->display(); ?>
	</form>
</div>
