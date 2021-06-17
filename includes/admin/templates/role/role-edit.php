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

	$role_id = sanitize_key( $_GET['id'] );

	$data = get_option( "um_role_{$role_id}_meta" );

	if ( empty( $data['_um_is_custom'] ) ) {
		$data['name'] = $wp_roles->roles[ $role_id ]['name'];
	}
}


if ( ! empty( $_POST['role'] ) ) {

	$id = '';
	$redirect = '';
	$error = '';

	if ( 'add' == sanitize_key( $_GET['tab'] ) ) {
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

		$all_roles = array_keys( UM()->roles()->get_roles() );

		if ( array_key_exists( '_um_priority', $data ) ) {
			$data['_um_priority'] = (int) $data['_um_priority'];
		}

		if ( array_key_exists( '_um_can_access_wpadmin', $data ) ) {
			$data['_um_can_access_wpadmin'] = (bool) $data['_um_can_access_wpadmin'];
		}

		if ( array_key_exists( '_um_can_not_see_adminbar', $data ) ) {
			$data['_um_can_not_see_adminbar'] = (bool) $data['_um_can_not_see_adminbar'];
		}

		if ( array_key_exists( '_um_can_edit_everyone', $data ) ) {
			$data['_um_can_edit_everyone'] = (bool) $data['_um_can_edit_everyone'];
		}

		if ( array_key_exists( '_um_can_edit_roles', $data ) && ! empty( $data['_um_can_edit_roles'] ) ) {
			$data['_um_can_edit_roles'] = array_filter( $data['_um_can_edit_roles'], function( $v, $k ) use ( $all_roles ) {
				return in_array( $v, $all_roles );
			}, ARRAY_FILTER_USE_BOTH );
		}

		if ( array_key_exists( '_um_can_delete_everyone', $data ) ) {
			$data['_um_can_delete_everyone'] = (bool) $data['_um_can_delete_everyone'];
		}

		if ( array_key_exists( '_um_can_delete_roles', $data ) && ! empty( $data['_um_can_delete_roles'] ) ) {
			$data['_um_can_delete_roles'] = array_filter( $data['_um_can_delete_roles'], function( $v, $k ) use ( $all_roles ) {
				return in_array( $v, $all_roles );
			}, ARRAY_FILTER_USE_BOTH );
		}

		if ( array_key_exists( '_um_can_edit_profile', $data ) ) {
			$data['_um_can_edit_profile'] = (bool) $data['_um_can_edit_profile'];
		}

		if ( array_key_exists( '_um_can_delete_profile', $data ) ) {
			$data['_um_can_delete_profile'] = (bool) $data['_um_can_delete_profile'];
		}

		if ( array_key_exists( '_um_can_view_all', $data ) ) {
			$data['_um_can_view_all'] = (bool) $data['_um_can_view_all'];
		}

		if ( array_key_exists( '_um_can_view_roles', $data ) && ! empty( $data['_um_can_view_roles'] ) ) {
			$data['_um_can_view_roles'] = array_filter( $data['_um_can_view_roles'], function( $v, $k ) use ( $all_roles ) {
				return in_array( $v, $all_roles );
			}, ARRAY_FILTER_USE_BOTH );
		}

		if ( array_key_exists( '_um_can_make_private_profile', $data ) ) {
			$data['_um_can_make_private_profile'] = (bool) $data['_um_can_make_private_profile'];
		}

		if ( array_key_exists( '_um_can_access_private_profile', $data ) ) {
			$data['_um_can_access_private_profile'] = (bool) $data['_um_can_access_private_profile'];
		}

		if ( array_key_exists( '_um_profile_noindex', $data ) ) {
			$data['_um_profile_noindex'] = $data['_um_profile_noindex'] !== '' ? (bool) $data['_um_profile_noindex'] : $data['_um_profile_noindex'];
		}

		if ( array_key_exists( '_um_default_homepage', $data ) ) {
			$data['_um_default_homepage'] = (bool) $data['_um_default_homepage'];
		}

		if ( array_key_exists( '_um_redirect_homepage', $data ) ) {
			$data['_um_redirect_homepage'] = esc_url_raw( $data['_um_redirect_homepage'] );
		}

		if ( array_key_exists( '_um_status', $data ) ) {
			$data['_um_status'] = ! in_array( sanitize_key( $data['_um_status'] ), [ 'approved', 'checkmail', 'pending' ] ) ? 'approved' : sanitize_key( $data['_um_status'] );
		}

		if ( array_key_exists( '_um_auto_approve_act', $data ) ) {
			$data['_um_auto_approve_act'] = ! in_array( sanitize_key( $data['_um_auto_approve_act'] ), [ 'redirect_profile', 'redirect_url' ] ) ? 'redirect_profile' : sanitize_key( $data['_um_auto_approve_act'] );
		}

		if ( array_key_exists( '_um_auto_approve_url', $data ) ) {
			$data['_um_auto_approve_url'] = esc_url_raw( $data['_um_auto_approve_url'] );
		}

		if ( array_key_exists( '_um_login_email_activate', $data ) ) {
			$data['_um_login_email_activate'] = (bool) $data['_um_login_email_activate'];
		}

		if ( array_key_exists( '_um_checkmail_action', $data ) ) {
			$data['_um_checkmail_action'] = ! in_array( sanitize_key( $data['_um_checkmail_action'] ), [ 'show_message', 'redirect_url' ] ) ? 'show_message' : sanitize_key( $data['_um_checkmail_action'] );
		}

		if ( array_key_exists( '_um_checkmail_message', $data ) ) {
			$data['_um_checkmail_message'] = sanitize_textarea_field( $data['_um_checkmail_message'] );
		}

		if ( array_key_exists( '_um_checkmail_url', $data ) ) {
			$data['_um_checkmail_url'] = esc_url_raw( $data['_um_checkmail_url'] );
		}

		if ( array_key_exists( '_um_url_email_activate', $data ) ) {
			$data['_um_url_email_activate'] = esc_url_raw( $data['_um_url_email_activate'] );
		}

		if ( array_key_exists( '_um_pending_action', $data ) ) {
			$data['_um_pending_action'] = ! in_array( sanitize_key( $data['_um_pending_action'] ), [ 'show_message', 'redirect_url' ] ) ? 'show_message' : sanitize_key( $data['_um_pending_action'] );
		}

		if ( array_key_exists( '_um_pending_message', $data ) ) {
			$data['_um_pending_message'] = sanitize_textarea_field( $data['_um_pending_message'] );
		}

		if ( array_key_exists( '_um_pending_url', $data ) ) {
			$data['_um_pending_url'] = esc_url_raw( $data['_um_pending_url'] );
		}

		if ( array_key_exists( '_um_after_login', $data ) ) {
			$data['_um_after_login'] = ! in_array( sanitize_key( $data['_um_after_login'] ), [ 'redirect_profile', 'redirect_url', 'refresh', 'redirect_admin' ] ) ? 'redirect_profile' : sanitize_key( $data['_um_after_login'] );
		}

		if ( array_key_exists( '_um_login_redirect_url', $data ) ) {
			$data['_um_login_redirect_url'] = esc_url_raw( $data['_um_login_redirect_url'] );
		}

		if ( array_key_exists( '_um_after_logout', $data ) ) {
			$data['_um_after_logout'] = ! in_array( sanitize_key( $data['_um_after_logout'] ), [ 'redirect_home', 'redirect_url' ] ) ? 'redirect_home' : sanitize_key( $data['_um_after_logout'] );
		}

		if ( array_key_exists( '_um_logout_redirect_url', $data ) ) {
			$data['_um_logout_redirect_url'] = esc_url_raw( $data['_um_logout_redirect_url'] );
		}

		if ( array_key_exists( '_um_after_delete', $data ) ) {
			$data['_um_after_delete'] = ! in_array( sanitize_key( $data['_um_after_delete'] ), [ 'redirect_home', 'redirect_url' ] ) ? 'redirect_home' : sanitize_key( $data['_um_after_delete'] );
		}

		if ( array_key_exists( '_um_delete_redirect_url', $data ) ) {
			$data['_um_delete_redirect_url'] = esc_url_raw( $data['_um_delete_redirect_url'] );
		}

		if ( array_key_exists( 'wp_capabilities', $data ) && ! empty( $data['wp_capabilities'] ) ) {
			$data['wp_capabilities'] = array_map( 'boolval', array_filter( $data['wp_capabilities'] ) );
		}

		$data = apply_filters( 'um_save_role_meta_sanitize', $data );

		if ( 'add' == sanitize_key( $_GET['tab'] ) ) {

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
		} elseif ( 'edit' == sanitize_key( $_GET['tab'] ) && ! empty( $_GET['id'] ) ) {
			$id = sanitize_key( $_GET['id'] );

			$pre_role_meta = get_option( "um_role_{$id}_meta", array() );
			if ( isset( $pre_role_meta['name'] ) ) {
				$data['name'] = $pre_role_meta['name'];
			}

			$redirect = add_query_arg( array( 'page' => 'um_roles', 'tab' => 'edit', 'id' => $id, 'msg'=> 'u' ), admin_url( 'admin.php' ) );
		}


		$all_roles = array_keys( get_editable_roles() );
		if ( 'add' == sanitize_key( $_GET['tab'] ) ) {
			if ( in_array( 'um_' . $id, $all_roles ) || in_array( $id, $all_roles ) ) {
				$error .= __( 'Role already exists!', 'ultimate-member' ) . '<br />';
			}
		}

		if ( '' == $error ) {

			if ( 'add' == sanitize_key( $_GET['tab'] ) ) {
				$roles = get_option( 'um_roles', array() );
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
		<?php echo ( 'add' == sanitize_key( $_GET['tab'] ) ) ? __( 'Add New Role', 'ultimate-member' ) : __( 'Edit Role', 'ultimate-member' ) ?>
		<?php if ( 'edit' == sanitize_key( $_GET['tab'] ) ) { ?>
			<a class="add-new-h2" href="<?php echo esc_url( add_query_arg( array( 'page' => 'um_roles', 'tab' => 'add' ), admin_url( 'admin.php' ) ) ) ?>"><?php _e( 'Add New', 'ultimate-member' ) ?></a>
		<?php } ?>
	</h2>

	<?php if ( ! empty( $_GET['msg'] ) ) {
		switch( sanitize_key( $_GET['msg'] ) ) {
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
		<input type="hidden" name="role[id]" value="<?php echo isset( $_GET['id'] ) ? esc_attr( sanitize_key( $_GET['id'] ) ) : '' ?>" />
		<?php if ( 'add' == sanitize_key( $_GET['tab'] ) ) { ?>
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
							<?php if ( 'add' == sanitize_key( $_GET['tab'] ) ) { ?>
								<label for="title" class="screen-reader-text"><?php _e( 'Title', 'ultimate-member' ) ?></label>
								<input type="text" name="role[name]" placeholder="<?php esc_attr_e( 'Enter Title Here', 'ultimate-member' ) ?>" id="title" value="<?php echo isset( $data['name'] ) ? $data['name'] : '' ?>" />
							<?php } else { ?>
								<span style="float: left;width:100%;"><?php echo isset( $data['name'] ) ? stripslashes( $data['name'] ) : '' ?></span>
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
