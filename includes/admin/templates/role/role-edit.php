<?php if ( ! defined( 'ABSPATH' ) ) exit;

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

$data = array();
$option = array();
global $wp_roles;

if ( ! empty( $_GET['id'] ) ) {
	$data = get_option( "um_role_{$_GET['id']}_meta" );

	if ( empty( $data['_um_is_custom'] ) ) {
		$data['name'] = $wp_roles->roles[ $_GET['id'] ]['name'];
	}
}


if ( ! empty( $_POST['role'] ) ) {

	$id = '';
	$redirect = '';
	$error = '';

	if ( 'add' == $_GET['tab'] ) {
		if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-add-role' ) ) {
			$error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
		}
	} else {
		if ( ! wp_verify_nonce( $_POST['um_nonce'], 'um-edit-role' ) ) {
			$error = __( 'Security Issue', 'ultimate-member' ) . '<br />';
		}
	}

	if ( empty( $error ) ) {

		$data = $_POST['role'];

		if ( 'add' == $_GET['tab'] ) {

			$data['name'] = trim( esc_html( strip_tags( $data['name'] ) ) );

			if ( empty( $data['name'] ) ) {
				$error .= __( 'Title is empty!', 'ultimate-member' ) . '<br />';
			}

			if ( preg_match( "/[a-z0-9]+$/i", $data['name'] ) ) {
				$id = sanitize_title( $data['name'] );
			} else {
				$auto_increment = UM()->options()->get( 'custom_roles_increment' );
				$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
				$id = 'custom_role_' . $auto_increment;
			}

			$redirect = add_query_arg( array( 'page'=>'um_roles', 'tab'=>'edit', 'id'=>$id, 'msg'=>'a' ), admin_url( 'admin.php' ) );
		} elseif ( 'edit' == $_GET['tab'] && ! empty( $_GET['id'] ) ) {
			$id = $_GET['id'];

			$pre_role_meta = get_option( "um_role_{$id}_meta", array() );
			if ( isset( $pre_role_meta['name'] ) ) {
				$data['name'] = $pre_role_meta['name'];
			}

			$redirect = add_query_arg( array( 'page' => 'um_roles', 'tab' => 'edit', 'id' => $id, 'msg'=> 'u' ), admin_url( 'admin.php' ) );
		}


		$all_roles = array_keys( get_editable_roles() );
		if ( 'add' == $_GET['tab'] ) {
			if ( in_array( 'um_' . $id, $all_roles ) || in_array( $id, $all_roles ) ) {
				$error .= __( 'Role already exists!', 'ultimate-member' ) . '<br />';
			}
		}

		if ( '' == $error ) {

			if ( 'add' == $_GET['tab'] ) {
				$roles = get_option( 'um_roles' );
				$roles[] = $id;

				update_option( 'um_roles', $roles );

				if ( isset( $auto_increment ) ) {
					$auto_increment++;
					UM()->options()->update( 'custom_roles_increment', $auto_increment );
				}
			}

			$role_meta = $data;
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
		<?php echo ( 'add' == $_GET['tab'] ) ? __( 'Add New Role', 'ultimate-member' ) : __( 'Edit Role', 'ultimate-member' ) ?>
		<?php if ( 'edit' == $_GET['tab'] ) { ?>
			<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( array( 'page' => 'um_roles', 'tab' => 'add' ), admin_url( 'admin.php' ) ) ) ?>"><?php _e( 'Add New', 'ultimate-member' ) ?></a>
		<?php } ?>
	</h2>

	<?php if ( ! empty( $_GET['msg'] ) ) {
		switch( $_GET['msg'] ) {
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
			<p><?php echo $error ?></p>
		</div>
	<?php } ?>

	<form id="um_edit_role" action="" method="post">
		<input type="hidden" name="role[id]" value="<?php echo isset( $_GET['id'] ) ? esc_attr( $_GET['id'] ) : '' ?>" />
		<?php if ( 'add' == $_GET['tab'] ) { ?>
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
							<?php if ( 'add' == $_GET['tab'] ) { ?>
								<label for="title" class="screen-reader-text"><?php _e( 'Title', 'ultimate-member' ) ?></label>
								<input type="text" name="role[name]" placeholder="<?php esc_attr_e( 'Enter Title Here', 'ultimate-member' ) ?>" id="title" value="<?php echo isset( $data['name'] ) ? $data['name'] : '' ?>" />
							<?php } else { ?>
								<span style="float: left;width:100%;"><?php echo isset( $data['name'] ) ? $data['name'] : '' ?></span>
							<?php } ?>
						</div>
					</div>
				</div>

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( 'um_role_meta', 'side', array( 'data' => $data, 'option' => $option ) ); ?>
				</div>
				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( 'um_role_meta', 'normal', array( 'data' => $data, 'option' => $option ) ); ?>
				</div>
			</div>
		</div>
	</form>
</div>