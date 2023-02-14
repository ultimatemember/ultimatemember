<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ListTable = new um\admin\list_table\Modules( array(
	'singular' => __( 'Module', 'ultimate-member' ),
	'plural'   => __( 'Modules', 'ultimate-member' ),
	'ajax'     => false,
) );

$bulk_actions = array();
if ( ! UM()->is_legacy ) {
	$bulk_actions = array(
		'activate'   => __( 'Activate', 'ultimate-member' ),
		'deactivate' => __( 'Deactivate', 'ultimate-member' ),
		'flush-data' => __( 'Flush module data', 'ultimate-member' ),
	);
}

$ListTable->set_bulk_actions( $bulk_actions );

$ListTable->set_columns( array(
	'module_title' => __( 'Module', 'ultimate-member' ),
	'type'         => __( 'Type', 'ultimate-member' ),
	'description'  => __( 'Description', 'ultimate-member' ),
) );

$ListTable->prepare_items();

if ( ! empty( $_GET['msg'] ) ) {
	switch( sanitize_key( $_GET['msg'] ) ) {
		case 'a':
			echo '<div class="clear"></div><div id="message" class="updated fade"><p>' . __( 'Module <strong>activated</strong> successfully.', 'ultimate-member' ) . '</p></div>';
			break;
		case 'd':
			echo '<div class="clear"></div><div id="message" class="updated fade"><p>' . __( 'Module <strong>deactivated</strong> successfully.', 'ultimate-member' ) . '</p></div>';
			break;
		case 'f':
			echo '<div class="clear"></div><div id="message" class="updated fade"><p>' . __( 'Module\'s data is <strong>flushed</strong> successfully.', 'ultimate-member' ) . '</p></div>';
			break;
	}
} ?>

<div class="clear"></div>

<?php ob_start(); ?>

<div id="um-plan">
	<p><?php esc_html_e( 'You are using the free version of Ultimate Member. With this you have access to the modules below. Upgrade to Ultimate Member Pro to get access to the pro modules.', 'ultimate-member' ); ?></p>
	<p><?php echo wp_kses( sprintf( __( 'Click <a href="%s" target="_blank">here</a> to view our different plans for Ultimate Member Pro.', 'ultimate-member' ), 'https://ultimatemember.com/pricing/' ), array( 'a' => array( 'href' => array(), 'target' => true ) ) ); ?></p>
</div>

<?php
$same_page_license = ob_get_clean();
$same_page_license = apply_filters( 'um_modules_page_same_page_license', $same_page_license );

echo $same_page_license;
?>

<form action="" method="get" name="um-modules" id="um-modules">
	<input type="hidden" name="page" value="ultimatemember" />
	<input type="hidden" name="tab" value="modules" />
	<?php $ListTable->display(); ?>
</form>
