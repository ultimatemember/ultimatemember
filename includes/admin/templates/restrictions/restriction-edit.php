<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'postbox' );
wp_enqueue_media();

/**
 * UM hook
 *
 * @type action
 * @title um_roles_add_meta_boxes
 * @description Add meta boxes on add/edit UM Role
 * @input_vars
 * [{"var":"$meta","type":"string","desc":"Meta Box Key"}]
 * @change_log
 * ["Since: 2.0"]
 * @usage add_action( 'um_roles_add_meta_boxes', 'function_name', 10, 1 );
 * @example
 * <?php
 * add_action( 'um_roles_add_meta_boxes', 'my_roles_add_meta_boxes', 10, 1 );
 * function my_roles_add_meta_boxes( $meta ) {
 *     // your code here
 * }
 * ?>
 */
do_action( 'um_restriction_rules_add_meta_boxes', 'um_rule_meta' );
/**
 * UM hook
 *
 * @type action
 * @title um_roles_add_meta_boxes_um_role_meta
 * @description Make add meta boxes on add/edit UM Role
 * @change_log
 * ["Since: 2.0"]
 * @usage add_action( 'um_roles_add_meta_boxes_um_role_meta', 'function_name', 10 );
 * @example
 * <?php
 * add_action( 'um_roles_add_meta_boxes_um_role_meta', 'my_roles_add_meta_boxes', 10 );
 * function my_roles_add_meta_boxes() {
 *     // your code here
 * }
 * ?>
 */
do_action( 'um_restriction_rules_add_meta_boxes_um_rule_meta' );

$data      = array();
$rule_meta = array();
$option    = array();
global $wp_roles;

// phpcs:disable WordPress.Security.NonceVerification
if ( ! empty( $_GET['id'] ) ) {
	$rule_id = absint( $_GET['id'] );

	$rule_meta  = get_option( "um_restriction_rule_{$rule_id}" );
	$data_rules = get_option( 'um_restriction_rules' );
	$data       = $data_rules[ $rule_id ];
}

global $current_screen;
$screen_id = $current_screen->id;
?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		postboxes.add_postbox_toggles( '<?php echo esc_js( $screen_id ); ?>' );
	});
</script>

<div class="wrap">
	<h2>
		<?php
		if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
			esc_html_e( 'Add New Restriction Rule', 'ultimate-member' );
		} elseif ( 'edit' === sanitize_key( $_GET['tab'] ) ) {
			esc_html_e( 'Edit Restriction Rule', 'ultimate-member' );
			$add_new_link = add_query_arg(
				array(
					'page' => 'um_restriction_rules',
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
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Restriction Rule Added Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
			case 'u':
				echo '<div id="message" class="updated fade"><p>' . esc_html__( 'Restriction Rule Updated Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
		}
	}

	if ( ! empty( $rule_error ) ) {
		?>
		<div id="message" class="error fade">
			<p><?php echo wp_kses( $rule_error, UM()->get_allowed_html( 'admin_notice' ) ); ?></p>
		</div>
	<?php } ?>

	<form id="um_edit_restriction_rule" action="" method="post">
		<input type="hidden" name="restriction_rule[id]" value="<?php echo isset( $_GET['id'] ) ? esc_attr( sanitize_key( $_GET['id'] ) ) : ''; ?>" />
		<?php if ( 'add' === sanitize_key( $_GET['tab'] ) ) { ?>
			<input type="hidden" name="um_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-add-restriction-rule' ) ); ?>" />
		<?php } else { ?>
			<input type="hidden" name="um_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-edit-restriction-rule' . $_GET['id'] ) ); ?>" />
		<?php } ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<label for="title" class="screen-reader-text"><?php esc_html_e( 'Title', 'ultimate-member' ); ?></label>
							<input type="text" name="um_restriction_rules[title]" required placeholder="<?php esc_html_e( 'Enter Title Here', 'ultimate-member' ); ?>" id="title" value="<?php echo isset( $data['title'] ) ? esc_attr( $data['title'] ) : ''; ?>" />
							<br /><br />
							<textarea style="width: 100%;" name="um_restriction_rules[_um_description]" id="description" placeholder="<?php esc_attr_e( 'Restriction Rule Description', 'ultimate-member' ); ?>"><?php echo isset( $data['_um_description'] ) ? esc_attr( $data['_um_description'] ) : ''; ?></textarea>
						</div>
					</div>
				</div>

				<?php
				$object = array(
					'data'    => $data,
					'option'  => $option,
					'action'  => ! empty( $rule_meta['action'] ) ? $rule_meta['action'] : array(),
					'include' => ! empty( $rule_meta['include'] ) ? $rule_meta['include'] : array(),
					'exclude' => ! empty( $rule_meta['exclude'] ) ? $rule_meta['exclude'] : array(),
					'rules'   => ! empty( $rule_meta['rules'] ) ? $rule_meta['rules'] : array(),
				);
				?>

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( 'um_restriction_rule_meta', 'side', $object ); ?>
				</div>
				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( 'um_restriction_rule_meta', 'normal', $object ); ?>
				</div>
			</div>
		</div>
	</form>
</div>
<?php
// phpcs:disable WordPress.Security.NonceVerification
