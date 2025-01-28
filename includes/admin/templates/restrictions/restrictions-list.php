<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$list_table = new um\admin\list_table\Restriction_Rules(
	array(
		'singular' => __( 'Rule', 'ultimate-member' ),
		'plural'   => __( 'Rules', 'ultimate-member' ),
		'ajax'     => false,
	)
);

$list_table->set_bulk_actions(
	array(
		'delete'     => __( 'Delete', 'ultimate-member' ),
		'activate'   => __( 'Activate', 'ultimate-member' ),
		'deactivate' => __( 'Deactivate', 'ultimate-member' ),
	)
);

$list_table->set_columns(
	array(
		'title'       => __( 'Role Title', 'ultimate-member' ),
		'description' => __( 'Description', 'ultimate-member' ),
		/*		'type'        => __( 'Rule type', 'ultimate-member' ), // @todo uncomment as soon as type isn't hardcoded*/
		'status'      => __( 'Status', 'ultimate-member' ),
		'entities'    => __( 'Entities', 'ultimate-member' ),
		'rules'       => __( 'Rules', 'ultimate-member' ),
		'action'      => __( 'Action', 'ultimate-member' ),
	)
);

$list_table->prepare_items();

$url_args = array(
	'page' => 'um_restriction_rules',
	'tab'  => 'add',
);
?>

<div class="wrap">
	<h2>
		<?php esc_html_e( 'Restriction rules', 'ultimate-member' ); ?>
		<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( $url_args, admin_url( 'admin.php' ) ) ); ?>">
			<?php esc_html_e( 'Add New', 'ultimate-member' ); ?>
		</a>
	</h2>

	<?php
	if ( ! empty( $_GET['msg'] ) ) {
		switch ( sanitize_key( $_GET['msg'] ) ) {
			case 'd':
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Restriction Rule Deleted Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
			case 'act':
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Restriction Rule Activated Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
			case 'deact':
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Restriction Rule Deactivated Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification
	?>
	<form action="" method="get" name="um-restriction-rules" id="um-restriction-rules">
		<input type="hidden" name="page" value="um_restriction_rules" />
		<input type="hidden" id="um_restriction_rules_nonce" name="um_restriction_rules_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um_restriction_rules_order' ) ); ?>" />
		<?php $list_table->display(); ?>
	</form>
</div>
