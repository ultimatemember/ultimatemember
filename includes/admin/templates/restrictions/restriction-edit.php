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

$data   = array();
$option = array();
global $wp_roles;

// phpcs:disable WordPress.Security.NonceVerification
if ( ! empty( $_GET['id'] ) ) {

	// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
	// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
	$rule_id = sanitize_title( $_GET['id'] );

	$data = get_option( "um_restriction_rule_{$rule_id}" );
}

if ( ! empty( $_POST['um_restriction_rules'] ) ) {

	$restriction_id = '';
	$redirect       = '';
	$rule_error     = '';

	if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
		if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-add-restriction-rule' ) ) {
			$rule_error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
		}
	} else {
		if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-edit-restriction-rule' ) ) {
			$rule_error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
		}
	}

	if ( empty( $rule_error ) ) {
		$data = UM()->admin()->sanitize_restriction_rule_meta( $_POST['um_restriction_rules'] );

		if ( 'add' === sanitize_key( $_GET['tab'] ) ) {

			$data['title'] = trim( esc_html( wp_strip_all_tags( $data['title'] ) ) );

			if ( empty( $data['title'] ) ) {
				$rule_error .= __( 'Title is empty!', 'ultimate-member' ) . '<br />';
			}

			$auto_increment = UM()->options()->get( 'custom_restriction_rules_increment' );
			$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
			$restriction_id = $auto_increment;

			$redirect = add_query_arg(
				array(
					'page' => 'um_restriction_rules',
					'tab'  => 'edit',
					'id'   => $restriction_id,
					'msg'  => 'a',
				),
				admin_url( 'admin.php' )
			);
		} elseif ( 'edit' === sanitize_key( $_GET['tab'] ) && ! empty( $_GET['id'] ) ) {
			// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
			// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
			$restriction_id = sanitize_title( $_GET['id'] );

			$pre_rule_meta = get_option( "um_restriction_rule_{$restriction_id}", array() );
			if ( isset( $pre_rule_meta['title'] ) ) {
				$data['title'] = $pre_rule_meta['title'];
			}

			$redirect = add_query_arg(
				array(
					'page' => 'um_restriction_rules',
					'tab'  => 'edit',
					'id'   => $restriction_id,
					'msg'  => 'u',
				),
				admin_url( 'admin.php' )
			);
		}

		if ( '' === $rule_error ) {

			$update = true;
			if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
				$rules   = get_option( 'um_restriction_rules', array() );
				$rules[] = $restriction_id;

				update_option( 'um_restriction_rules', $rules );

				if ( isset( $auto_increment ) ) {
					$auto_increment++;
					UM()->options()->update( 'custom_restriction_rules_increment', $auto_increment );
				}

				$update = false;
			}

			/**
			 * Filters the restriction rule meta before save it to DB.
			 *
			 * @since 2.8.x
			 * @hook  um_restriction_rule_edit_data
			 *
			 * @param {array}   $data            Rule meta.
			 * @param {string}  $restriction_id  Role key.
			 * @param {bool}    $update          Create or update role. "True" if update.
			 *
			 * @return {array}  Rule meta.
			 *
			 * @example <caption>Add custom metadata for rule on saving.</caption>
			 * function my_um_restriction_rule_edit_data( $data, $restriction_id, $update ) {
			 *     $data['{meta_key}'] = {meta_value}; // set your meta key and meta value
			 *     return $data;
			 * }
			 * add_filter( 'um_restriction_rule_edit_data', 'my_um_restriction_rule_edit_data', 10, 3 );
			 */
			$rule_meta = apply_filters( 'um_restriction_rule_edit_data', $data, $restriction_id, $update );
			unset( $rule_meta['id'] );

			update_option( "um_restriction_rule_{$restriction_id}", $rule_meta );

			UM()->user()->remove_cache_all_users();

			um_js_redirect( $redirect );
		}
	}
}

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
			<input type="hidden" name="um_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-edit-restriction-rule' ) ); ?>" />
		<?php } ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<?php if ( 'add' === sanitize_key( $_GET['tab'] ) ) { ?>
								<label for="title" class="screen-reader-text"><?php esc_html_e( 'Title', 'ultimate-member' ); ?></label>
								<input type="text" name="um_restriction_rules[title]" placeholder="<?php esc_html_e( 'Enter Title Here', 'ultimate-member' ); ?>" id="title" value="<?php echo isset( $data['title'] ) ? esc_attr( $data['title'] ) : ''; ?>" />
							<?php } else { ?>
								<span style="display: block;line-height: 37px;font-size: 24px;float: left;width:100%;"><?php echo isset( $data['title'] ) ? esc_html( stripslashes( $data['title'] ) ) : ''; ?></span>
							<?php } ?>
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
