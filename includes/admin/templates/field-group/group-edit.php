<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'postbox' );

/**
 * UM hook
 *
 * @type action
 * @title um_field_groups_add_meta_boxes
 * @description Add meta boxes on add/edit UM Role
 * @input_vars
 * [{"var":"$meta","type":"string","desc":"Meta Box Key"}]
 * @change_log
 * ["Since: 2.0"]
 * @usage add_action( 'um_field_groups_add_meta_boxes', 'function_name', 10, 1 );
 * @example
 * <?php
 * add_action( 'um_field_groups_add_meta_boxes', 'my_roles_add_meta_boxes', 10, 1 );
 * function my_roles_add_meta_boxes( $meta ) {
 *     // your code here
 * }
 * ?>
 */
do_action( 'um_field_groups_add_meta_boxes', 'um_field_group_meta' );
/**
 * UM hook
 *
 * @type action
 * @title um_field_groups_add_meta_boxes_um_field_group_meta
 * @description Make add meta boxes on add/edit UM Role
 * @change_log
 * ["Since: 2.0"]
 * @usage add_action( 'um_field_groups_add_meta_boxes_um_field_group_meta', 'function_name', 10 );
 * @example
 * <?php
 * add_action( 'um_field_groups_add_meta_boxes_um_field_group_meta', 'my_roles_add_meta_boxes', 10 );
 * function my_roles_add_meta_boxes() {
 *     // your code here
 * }
 * ?>
 */
do_action( 'um_field_groups_add_meta_boxes_um_field_group_meta' );

$option = array();

$id       = false;
//$draft_id = false;
$data     = array(
	'title'       => '',
	'description' => '',
	'group_key'   => '',
);

if ( ! empty( $_GET['id'] ) ) {
	$id = absint( $_GET['id'] );
	$data = UM()->admin()->field_group()->get_data( $id );
	// replace by draft by default
//	$draft_id = UM()->admin()->field_group()->get_draft_by( $id, 'group' );
//
//	if ( ! empty( $draft_id ) ) {
//		$data = UM()->admin()->field_group()->get_data( $draft_id );
//	} elseif ( ! empty( $id ) ) {
//		$data = UM()->admin()->field_group()->get_data( $id );
//	}
} /*else {
	$draft_id = UM()->admin()->field_group()->get_draft_by( get_current_user_id(), 'user' );
	// load draft on creation if it exists
	if ( ! empty( $draft_id ) ) {
		$data = UM()->admin()->field_group()->get_data( $draft_id );
	}
}*/

global $current_screen;
$screen_id = $current_screen->id; ?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		postboxes.add_postbox_toggles( '<?php echo esc_js( $screen_id ); ?>' );
	});
</script>

<div class="wrap">
	<h2>
		<?php
		if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
			esc_html_e( 'Add New Field Group', 'ultimate-member' );
		} elseif ( 'edit' === sanitize_key( $_GET['tab'] ) ) {
			esc_html_e( 'Edit Field Group', 'ultimate-member' );
			$add_new_link = add_query_arg(
				array(
					'page' => 'um_field_groups',
					'tab'  => 'add',
				),
				admin_url( 'admin.php' )
			);
			?>
			<a class="add-new-h2" href="<?php echo esc_url( $add_new_link ); ?>">
				<?php esc_html_e( 'Add New', 'ultimate-member' ); ?>
			</a>
			<?php
		}
		?>
	</h2>

	<?php
	if ( ! empty( $_GET['msg'] ) ) {
		switch ( sanitize_key( $_GET['msg'] ) ) {
			case 'a':
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Field Group Added Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
			case 'u':
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Field Group Updated Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
		}
	}
	?>

	<form id="um_edit_field_group" action="" method="post">
		<input type="hidden" id="field_group_id" name="field_group[id]" value="<?php echo esc_attr( $id ); ?>" />
<!--		<input type="hidden" id="field_group_draft_id" name="field_group[draft_id]" value="--><?php //echo esc_attr( $draft_id ); ?><!--" />-->
		<?php if ( 'add' === sanitize_key( $_GET['tab'] ) ) { ?>
			<input type="hidden" name="um_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-add-field-group' ) ); ?>" />
		<?php } else { ?>
			<input type="hidden" name="um_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-edit-field-group' ) ); ?>" />
		<?php } ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<label for="title" class="screen-reader-text"><?php esc_html_e( 'Title', 'ultimate-member' ); ?></label>
							<input type="text" name="field_group[title]" placeholder="<?php esc_attr_e( 'Enter Title Here', 'ultimate-member' ); ?>" id="title" value="<?php echo isset( $data['title'] ) ? esc_attr( $data['title'] ) : ''; ?>" />
							<?php if ( 'edit' === sanitize_key( $_GET['tab'] ) ) { ?>
								<span id="um-field-group-key"><?php echo esc_html( sprintf( __( 'Key: %s', 'ultimate-member' ), $data['group_key'] ) ); ?></span>
							<?php } ?>
							<label for="description" class="screen-reader-text"><?php esc_html_e( 'Description', 'ultimate-member' ); ?></label>
							<textarea name="field_group[description]" placeholder="<?php esc_attr_e( 'Enter Description Here', 'ultimate-member' ); ?>" id="description"><?php echo isset( $data['description'] ) ? esc_textarea( $data['description'] ) : ''; ?></textarea>
							<p class="description"><?php esc_html_e( 'Shown in field groups list', 'ultimate-member' ); ?></p>
						</div>
					</div>
				</div>

				<?php
				$object = array(
					'data'   => $data,
					'option' => $option,
				);
				?>

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( 'um_field_group_meta', 'side', $object ); ?>
				</div>
				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( 'um_field_group_meta', 'normal', $object ); ?>
				</div>
			</div>
		</div>
	</form>
</div>
