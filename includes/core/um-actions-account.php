<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Validate for errors in account form
 */
add_action( 'um_submit_account_errors_hook', 'um_submit_account_errors_hook' );

function um_submit_account_errors_hook( $args ) {

	if ( ! isset( $_POST['um_account_submit'] ) )
		return;

	$user = get_user_by( 'login', um_user( 'user_login' ) );

	if ( isset( $_POST['_um_account_tab'] ) ) {
		switch ( $_POST['_um_account_tab'] ) {
			case 'delete': {
				// delete account
				if ( strlen(trim( $_POST['single_user_password'] ) ) == 0 ) {
					UM()->form()->add_error('single_user_password', __('You must enter your password','ultimate-member') );
				} else {
					if (  ! wp_check_password( $_POST['single_user_password'], $user->data->user_pass, $user->data->ID ) ) {
						UM()->form()->add_error('single_user_password', __('This is not your password','ultimate-member') );
					}
				}

				UM()->account()->current_tab = 'delete';

				break;
			}

			case 'password': {
				// change password
				if ( ( isset( $_POST['current_user_password'] ) && $_POST['current_user_password'] != '' ) ||
					( isset( $_POST['user_password'] ) && $_POST['user_password'] != '' ) ||
					( isset( $_POST['confirm_user_password'] ) && $_POST['confirm_user_password'] != '') ) {

					if ( $_POST['current_user_password'] == '' || ! wp_check_password( $_POST['current_user_password'], $user->data->user_pass, $user->data->ID ) ) {

						UM()->form()->add_error('current_user_password', __('This is not your password','ultimate-member') );
						UM()->account()->current_tab = 'password';
					} else { // correct password

						if ( $_POST['user_password'] != $_POST['confirm_user_password'] && $_POST['user_password'] ) {
							UM()->form()->add_error('user_password', __('Your new password does not match','ultimate-member') );
							UM()->account()->current_tab = 'password';
						}

						if ( UM()->options()->get( 'account_require_strongpass' ) ) {

							if ( strlen( utf8_decode( $_POST['user_password'] ) ) < 8 ) {
								UM()->form()->add_error('user_password', __('Your password must contain at least 8 characters','ultimate-member') );
							}

							if ( strlen( utf8_decode( $_POST['user_password'] ) ) > 30 ) {
								UM()->form()->add_error('user_password', __('Your password must contain less than 30 characters','ultimate-member') );
							}

							if ( ! UM()->validation()->strong_pass( $_POST['user_password'] ) ) {
								UM()->form()->add_error('user_password', __('Your password must contain at least one lowercase letter, one capital letter and one number','ultimate-member') );
							}

						}

					}
				}

				break;
			}

			case 'account':
			case 'general': {
				// errors on general tab

				$account_name_require = UM()->options()->get( 'account_name_require' );

				if ( ! empty( $_POST['user_login'] ) && ! validate_username( $_POST['user_login'] ) ) {
					UM()->form()->add_error('user_login', __( 'Your username is invalid', 'ultimate-member' ) );
					return;
				}

				if ( isset( $_POST['first_name'] ) && ( strlen( trim( $_POST['first_name'] ) ) == 0 && $account_name_require ) ) {
					UM()->form()->add_error( 'first_name', __( 'You must provide your first name', 'ultimate-member' ) );
				}

				if ( isset( $_POST['last_name'] ) && ( strlen( trim( $_POST['last_name'] ) ) == 0 && $account_name_require ) ) {
					UM()->form()->add_error( 'last_name', __( 'You must provide your last name', 'ultimate-member' ) );
				}

				if ( isset( $_POST['user_email'] ) ) {
					if ( strlen( trim( $_POST['user_email'] ) ) == 0 )
						UM()->form()->add_error( 'user_email', __( 'You must provide your e-mail', 'ultimate-member' ) );

					if ( ! is_email( $_POST['user_email'] ) )
						UM()->form()->add_error( 'user_email', __( 'Please provide a valid e-mail', 'ultimate-member' ) );

					if ( email_exists( $_POST['user_email'] ) && email_exists( $_POST['user_email'] ) != get_current_user_id() )
						UM()->form()->add_error( 'user_email', __( 'Email already linked to another account', 'ultimate-member' ) );
				}

				break;
			}

			default:
				do_action( 'um_submit_account_' . $_POST['_um_account_tab'] . '_tab_errors_hook' );
                break;
		}

		UM()->account()->current_tab = $_POST['_um_account_tab'];
	}

}


	/**
	 * Submit account page changes
	 */
	add_action('um_submit_account_details','um_submit_account_details');
	function um_submit_account_details( $args ) {
		$tab = ( get_query_var('um_tab') ) ? get_query_var('um_tab') : 'general';

		$current_tab = isset( $_POST['_um_account_tab'] ) ? $_POST['_um_account_tab']: '';

		//change password account's tab
		if ( 'password' == $current_tab && $_POST['user_password'] && $_POST['confirm_user_password'] ) {

			$changes['user_pass'] = $_POST['user_password'];

			$args['user_id'] = um_user('ID');

			do_action( 'send_password_change_email', $args );

			wp_set_password( $changes['user_pass'], um_user( 'ID' ) );
			
			wp_signon( array( 'user_login' => um_user( 'user_login' ), 'user_password' =>  $changes['user_pass'] ) );
		}


		// delete account
		$user = get_user_by( 'login', um_user( 'user_login' ) );

		if ( 'delete' == $current_tab && isset( $_POST['single_user_password'] ) && wp_check_password( $_POST['single_user_password'], $user->data->user_pass, $user->data->ID ) ) {
			if ( current_user_can( 'delete_users' ) || um_user( 'can_delete_profile' ) ) {
				if ( ! um_user( 'super_admin' ) ) {
					UM()->user()->delete();

					if ( um_user( 'after_delete' ) && um_user( 'after_delete' ) == 'redirect_home' ) {
						um_redirect_home();
					} elseif ( um_user( 'delete_redirect_url' ) ) {
						exit( wp_redirect( um_user( 'delete_redirect_url' ) ) );
					} else {
						um_redirect_home();
					}
				}
			}
		}


		$arr_fields = array();
		$account_fields = get_user_meta( um_user('ID'), 'um_account_secure_fields', true );
		$secure_fields = apply_filters( 'um_secure_account_fields', $account_fields , um_user( 'ID' ) );
		
		if ( is_array( $secure_fields  ) ) {
			foreach ( $secure_fields as $tab_key => $fields ) {
				foreach ( $fields as $key => $value ) {
					$arr_fields[ ] = $key;
				}
			}
		}

 
        $changes = array();
		foreach ( $_POST as $k => $v ) {
			if ( strstr( $k, 'password' ) || strstr( $k, 'um_account' ) || ! in_array( $k, $arr_fields ) )
				continue;

			$changes[ $k ] = $v;
		}

		if ( isset( $changes['hide_in_members'] ) && ( $changes['hide_in_members'] == __('No','ultimate-member') || $changes['hide_in_members'] == 'No' ) ) {
			delete_user_meta( um_user('ID'), 'hide_in_members' );
			unset( $changes['hide_in_members'] );
		}

		$changes = apply_filters( 'um_account_pre_updating_profile_array', $changes );

		// fired on account page, just before updating profile
		do_action('um_account_pre_update_profile', $changes, um_user('ID') );

		UM()->user()->update_profile( $changes );
       	
		do_action('um_post_account_update');

		do_action( 'um_after_user_account_updated', get_current_user_id(), $changes );

		$url = '';
		if ( um_is_core_page( 'account' ) ) {

			$url = UM()->account()->tab_link( $tab );

			$url = add_query_arg( 'updated', 'account', $url );

			if ( function_exists( 'icl_get_current_language' ) ) {
				if ( icl_get_current_language() != icl_get_default_language() ) {
					$url = UM()->permalinks()->get_current_url( true );
					$url = add_query_arg( 'updated', 'account', $url );

                    um_js_redirect( $url );
				}
			}
		}

		um_js_redirect( $url );
	}



	/**
	 * Hidden inputs for account form
	 */
	add_action('um_account_page_hidden_fields','um_account_page_hidden_fields');
	function um_account_page_hidden_fields( $args ) {
		?>

		<input type="hidden" name="_um_account" id="_um_account" value="1" />
		<input type="hidden" name="_um_account_tab" id="_um_account_tab" value="<?php echo UM()->account()->current_tab;?>" />

		<?php

	}


	/**
	 * Before delete account tab content
	 */
	add_action( 'um_before_account_delete', 'um_before_account_delete' );
	function um_before_account_delete() {
		echo wpautop( UM()->options()->get( 'delete_account_text' ) );
	}


	/**
	 * Before notifications account tab content
	 */
	add_action( 'um_before_account_notifications', 'um_before_account_notifications' );
	function um_before_account_notifications() { ?>
		<div class="um-field">
			<div class="um-field-label">
				<label for=""><?php _e( 'Email me when', 'ultimate-member' ); ?></label>
				<div class="um-clear"></div>
			</div>
		</div>
	<?php }


	/**
	 *  Update account fields to secure the account submission
	 */
	add_action( 'wp_footer', 'um_account_secure_registered_fields' );
	function um_account_secure_registered_fields(){
		$secure_fields = UM()->account()->register_fields;
		update_user_meta( um_user('ID'), 'um_account_secure_fields', $secure_fields );
	}


/**
 * Update Profile URL
 *
 * @param $user_id
 * @param $changed
 */
function um_after_user_account_updated_permalink( $user_id, $changed ) {
	UM()->user()->generate_profile_slug( $user_id );
}
add_action( 'um_after_user_account_updated', 'um_after_user_account_updated_permalink', 10, 2 );
