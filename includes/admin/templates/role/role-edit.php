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
do_action( 'um_roles_add_meta_boxes', 'um_role_meta' );
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
do_action( 'um_roles_add_meta_boxes_um_role_meta' );

$data   = array();
$option = array();
global $wp_roles;

if ( ! empty( $_GET['id'] ) ) {

	// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
	// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
	$role_id = sanitize_title( $_GET['id'] );

	$data = get_option( "um_role_{$role_id}_meta" );

	if ( empty( $data['_um_is_custom'] ) ) {
		$data['name'] = $wp_roles->roles[ $role_id ]['name'];
	}
}


if ( ! empty( $_POST['role'] ) ) {

	$id       = '';
	$redirect = '';
	$error    = '';

	if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
		if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-add-role' ) ) {
			$error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
		}
	} else {
		if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-edit-role' ) ) {
			$error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
		}
	}

	if ( empty( $error ) ) {

		$data = UM()->admin()->sanitize_role_meta( $_POST['role'] );

		if ( 'add' === sanitize_key( $_GET['tab'] ) ) {

			$data['name'] = trim( esc_html( strip_tags( $data['name'] ) ) );

			if ( empty( $data['name'] ) ) {
				$error .= __( 'Title is empty!', 'ultimate-member' ) . '<br />';
			}

			if ( preg_match( "/^[\p{Latin}\d\-_ ]+$/i", $data['name'] ) ) {
				// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
				// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
				$id = sanitize_title( $data['name'] );
			} else {
				$auto_increment = UM()->options()->get( 'custom_roles_increment' );
				$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
				$id             = 'custom_role_' . $auto_increment;
			}

			$redirect = add_query_arg( array( 'page' => 'um_roles', 'tab' => 'edit', 'id' => $id, 'msg' => 'a' ), admin_url( 'admin.php' ) );
		} elseif ( 'edit' === sanitize_key( $_GET['tab'] ) && ! empty( $_GET['id'] ) ) {
			// uses sanitize_title instead of sanitize_key for backward compatibility based on #906 pull-request (https://github.com/ultimatemember/ultimatemember/pull/906)
			// roles e.g. "潜水艦subs" with both latin + not-UTB-8 symbols had invalid role ID
			$id = sanitize_title( $_GET['id'] );

			$pre_role_meta = get_option( "um_role_{$id}_meta", array() );
			if ( isset( $pre_role_meta['name'] ) ) {
				$data['name'] = $pre_role_meta['name'];
			}

			$redirect = add_query_arg( array( 'page' => 'um_roles', 'tab' => 'edit', 'id' => $id, 'msg'=> 'u' ), admin_url( 'admin.php' ) );
		}


		$all_roles = array_keys( get_editable_roles() );
		if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
			if ( in_array( 'um_' . $id, $all_roles, true ) || in_array( $id, $all_roles, true ) ) {
				$error .= __( 'Role already exists!', 'ultimate-member' ) . '<br />';
			}
		}

		if ( '' === $error ) {

			$update = true;
			if ( 'add' === sanitize_key( $_GET['tab'] ) ) {
				$roles   = get_option( 'um_roles', array() );
				$roles[] = $id;

				update_option( 'um_roles', $roles );

				if ( isset( $auto_increment ) ) {
					$auto_increment++;
					UM()->options()->update( 'custom_roles_increment', $auto_increment );
				}

				$update = false;
			}

			/**
			 * Filters the role meta before save it to DB.
			 *
			 * @param {array}  $data   Role meta.
			 * @param {string} $id     Role key.
			 * @param {bool}   $update Create or update role. "True" if update.
			 *
			 * @since 2.6.3
			 * @hook um_role_edit_data
			 *
			 * @example <caption>Add custom metadata for role on saving.</caption>
			 * function my_custom_um_role_edit_data( $data, $id, $update ) {
			 *     $data['{meta_key}'] = {meta_value}; // set your meta key and meta value
			 *     return $data;
			 * }
			 * add_action( 'um_role_edit_data', 'my_custom_um_role_edit_data', 10, 3 );
			 * @example <caption>Force remove role's metadata on saving when update.</caption>
			 * function my_custom_um_role_edit_data( $data, $id, $update ) {
			 *     if ( true === $update ) {
			 *         unset( $data['{meta_key}'] ); // set your meta key
			 *     }
			 *     return $data;
			 * }
			 * add_action( 'um_role_edit_data', 'my_custom_um_role_edit_data', 10, 3 );
			 */
			$role_meta = apply_filters( 'um_role_edit_data', $data, $id, $update );
			unset( $role_meta['id'] );

			update_option( "um_role_{$id}_meta", $role_meta );

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
			esc_html_e( 'Add New Role', 'ultimate-member' );
		} elseif ( 'edit' === sanitize_key( $_GET['tab'] ) ) {
			esc_html_e( 'Edit Role', 'ultimate-member' );
			$add_new_link = add_query_arg(
				array(
					'page' => 'um_roles',
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
				echo '<div id="message" class="updated fade"><p>' . __( 'User Role <strong>Added</strong> Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
			case 'u':
				echo '<div id="message" class="updated fade"><p>' . __( 'User Role <strong>Updated</strong> Successfully.', 'ultimate-member' ) . '</p></div>';
				break;
		}
	}

	if ( ! empty( $error ) ) { ?>
		<div id="message" class="error fade">
			<p><?php echo $error; ?></p>
		</div>
	<?php } ?>

	<form id="um_edit_role" action="" method="post">
		<input type="hidden" name="role[id]" value="<?php echo isset( $_GET['id'] ) ? esc_attr( sanitize_key( $_GET['id'] ) ) : '' ?>" />
		<?php if ( 'add' === sanitize_key( $_GET['tab'] ) ) { ?>
			<input type="hidden" name="role[_um_is_custom]" value="1" />
			<input type="hidden" name="um_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-add-role' ) ) ?>" />
		<?php } else { ?>
			<input type="hidden" name="role[_um_is_custom]" value="<?php echo ! empty( $data['_um_is_custom'] ) ? 1 : 0 ?>" />
			<input type="hidden" name="um_nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-edit-role' ) ) ?>" />
		<?php } ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<?php if ( 'add' === sanitize_key( $_GET['tab'] ) ) { ?>
								<label for="title" class="screen-reader-text"><?php _e( 'Title', 'ultimate-member' ) ?></label>
								<input type="text" name="role[name]" placeholder="<?php esc_attr_e( 'Enter Title Here', 'ultimate-member' ) ?>" id="title" value="<?php echo isset( $data['name'] ) ? $data['name'] : '' ?>" />
							<?php } else { ?>
								<span style="float: left;width:100%;"><?php echo isset( $data['name'] ) ? stripslashes( $data['name'] ) : '' ?></span>
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
					<?php do_meta_boxes( 'um_role_meta', 'side', $object ); ?>
				</div>
				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( 'um_role_meta', 'normal', $object ); ?>
				</div>
			</div>
		</div>
	</form>
</div>
