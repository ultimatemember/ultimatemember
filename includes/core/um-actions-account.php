<?php if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Validate for errors in account form
 *
 * @param $args
 */
function um_submit_account_errors_hook( $args ) {

	if ( ! isset( $_POST['um_account_submit'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST[ 'um_account_nonce_' . $_POST['_um_account_tab'] ], 'um_update_account_' . $_POST['_um_account_tab'] ) ) {
		UM()->form()->add_error('um_account_security', __( 'Are you hacking? Please try again!', 'ultimate-member' ) );
	}

	$user = get_user_by( 'login', um_user( 'user_login' ) );

	if ( isset( $_POST['_um_account_tab'] ) ) {
		switch ( $_POST['_um_account_tab'] ) {
			case 'delete': {
				// delete account
				if ( strlen(trim( $_POST['single_user_password'] ) ) == 0 ) {
					UM()->form()->add_error( 'single_user_password', __( 'You must enter your password', 'ultimate-member' ) );
				} else {
					if ( ! wp_check_password( $_POST['single_user_password'], $user->data->user_pass, $user->data->ID ) ) {
						UM()->form()->add_error( 'single_user_password', __( 'This is not your password', 'ultimate-member' ) );
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

					if ( strlen( trim( $_POST['user_email'] ) ) == 0 ) {
						UM()->form()->add_error( 'user_email', __( 'You must provide your e-mail', 'ultimate-member' ) );
					}

					if ( ! is_email( $_POST['user_email'] ) ) {
						UM()->form()->add_error( 'user_email', __( 'Please provide a valid e-mail', 'ultimate-member' ) );
					}

					if ( email_exists( $_POST['user_email'] ) && email_exists( $_POST['user_email'] ) != get_current_user_id() ) {
						UM()->form()->add_error( 'user_email', __( 'Email already linked to another account', 'ultimate-member' ) );
					}
				}

				// check account password
				if ( UM()->options()->get( 'account_general_password' ) ) {
					if ( strlen( trim( $_POST['single_user_password'] ) ) == 0 ) {
						UM()->form()->add_error('single_user_password', __( 'You must enter your password', 'ultimate-member' ) );
					} else {
						if ( ! wp_check_password( $_POST['single_user_password'], $user->data->user_pass, $user->data->ID ) ) {
							UM()->form()->add_error('single_user_password', __( 'This is not your password', 'ultimate-member' ) );
						}
					}
				}

				break;
			}

			default:
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_submit_account_{$tab}_tab_errors_hook
				 * @description On submit account current $tab validation
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_submit_account_{$tab}_tab_errors_hook', 'function_name', 10 );
				 * @example
				 * <?php
				 * add_action( 'um_submit_account_{$tab}_tab_errors_hook', 'my_submit_account_tab_errors', 10 );
				 * function my_submit_account_tab_errors() {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_submit_account_' . $_POST['_um_account_tab'] . '_tab_errors_hook' );
                break;
		}

		UM()->account()->current_tab = $_POST['_um_account_tab'];
	}

}
add_action( 'um_submit_account_errors_hook', 'um_submit_account_errors_hook' );


/**
 * Submit account page changes
 *
 * @param $args
 */
function um_submit_account_details( $args ) {
	$tab = ( get_query_var('um_tab') ) ? get_query_var('um_tab') : 'general';

	$current_tab = isset( $_POST['_um_account_tab'] ) ? $_POST['_um_account_tab']: '';

	$user_id = um_user('ID');

	//change password account's tab
	if ( 'password' == $current_tab && $_POST['user_password'] && $_POST['confirm_user_password'] ) {

		$changes['user_pass'] = $_POST['user_password'];

		$args['user_id'] = $user_id;

		UM()->user()->password_changed();

		add_filter( 'send_password_change_email', '__return_false' );

		//clear all sessions with old passwords
		$user = WP_Session_Tokens::get_instance( $user_id );
		$user->destroy_all();

		wp_set_password( $changes['user_pass'], $user_id );

		wp_signon( array( 'user_login' => um_user( 'user_login' ), 'user_password' =>  $changes['user_pass'] ) );
	}


	// delete account
	$user = get_user_by( 'login', um_user( 'user_login' ) );

	if ( 'delete' == $current_tab && isset( $_POST['single_user_password'] ) &&
	     wp_check_password( $_POST['single_user_password'], $user->data->user_pass, $user->data->ID ) ) {
		if ( current_user_can( 'delete_users' ) || um_user( 'can_delete_profile' ) ) {
			UM()->user()->delete();

			if ( um_user( 'after_delete' ) && um_user( 'after_delete' ) == 'redirect_home' ) {
				um_redirect_home();
			} elseif ( um_user( 'delete_redirect_url' ) ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_delete_account_redirect_url
				 * @description Change redirect URL after delete account
				 * @input_vars
				 * [{"var":"$url","type":"string","desc":"Redirect URL"},
				 * {"var":"$id","type":"int","desc":"User ID"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage
				 * <?php add_filter( 'um_delete_account_redirect_url', 'function_name', 10, 2 ); ?>
				 * @example
				 * <?php
				 * add_filter( 'um_delete_account_redirect_url', 'my_delete_account_redirect_url', 10, 2 );
				 * function my_delete_account_redirect_url( $url, $id ) {
				 *     // your code here
				 *     return $url;
				 * }
				 * ?>
				 */
				$redirect_url = apply_filters( 'um_delete_account_redirect_url', um_user( 'delete_redirect_url' ), $user_id );
				exit( wp_redirect( $redirect_url ) );
			} else {
				um_redirect_home();
			}
		}
	}

	$arr_fields = array();
	if ( UM()->account()->is_secure_enabled() ) {
		$account_fields = get_user_meta( $user_id, 'um_account_secure_fields', true );

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_secure_account_fields
		 * @description Change secure account fields
		 * @input_vars
		 * [{"var":"$fields","type":"array","desc":"Secure account fields"},
		 * {"var":"$user_id","type":"int","desc":"User ID"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_secure_account_fields', 'function_name', 10, 2 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_secure_account_fields', 'my_secure_account_fields', 10, 2 );
		 * function my_secure_account_fields( $fields, $user_id ) {
		 *     // your code here
		 *     return $fields;
		 * }
		 * ?>
		 */
		$secure_fields = apply_filters( 'um_secure_account_fields', $account_fields, $user_id );

		if ( isset( $secure_fields[ $current_tab ] ) && is_array( $secure_fields[ $current_tab ] ) ) {
			$arr_fields = array_merge( $arr_fields, $secure_fields[ $current_tab ] );
		}
	}

	$changes = array();
	foreach ( $_POST as $k => $v ) {
		if ( ! in_array( $k, $arr_fields ) ) {
			continue;
		}

		$changes[ $k ] = $v;
	}

	if ( isset( $changes['hide_in_members'] ) && ( $changes['hide_in_members'] == __( 'No', 'ultimate-member' ) || $changes['hide_in_members'] == 'No' ) ) {
		delete_user_meta( $user_id, 'hide_in_members' );
		unset( $changes['hide_in_members'] );
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_account_pre_updating_profile_array
	 * @description Change update profile data before saving
	 * @input_vars
	 * [{"var":"$changes","type":"array","desc":"Profile changes array"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_account_pre_updating_profile_array', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_account_pre_updating_profile_array', 'my_account_pre_updating_profile', 10, 1 );
	 * function my_account_pre_updating_profile( $changes ) {
	 *     // your code here
	 *     return $changes;
	 * }
	 * ?>
	 */
	$changes = apply_filters( 'um_account_pre_updating_profile_array', $changes );

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_account_pre_update_profile
	 * @description Fired on account page, just before updating profile
	 * @input_vars
	 * [{"var":"$changes","type":"array","desc":"Submitted data"},
	 * {"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_account_pre_update_profile', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_action( 'um_account_pre_update_profile', 'my_account_pre_update_profile', 10, 2 );
	 * function my_account_pre_update_profile( $changes, $user_id ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_account_pre_update_profile', $changes, $user_id );

	UM()->user()->update_profile( $changes );


	if ( UM()->account()->is_secure_enabled() ) {
		update_user_meta( $user_id, 'um_account_secure_fields', array() );
	}

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_post_account_update
	 * @description Fired on account page, after updating profile
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_post_account_update', 'function_name', 10 );
	 * @example
	 * <?php
	 * add_action( 'um_post_account_update', 'my_post_account_update', 10 );
	 * function my_account_pre_update_profile() {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_post_account_update' );
	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_after_user_account_updated
	 * @description Fired on account page, after updating profile
	 * @input_vars
	 * [{"var":"$user_id","type":"int","desc":"User ID"},
	 * {"var":"$changes","type":"array","desc":"Submitted data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_after_user_account_updated', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_action( 'um_after_user_account_updated', 'my_after_user_account_updated', 10, 2 );
	 * function my_after_user_account_updated( $user_id, $changes ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_after_user_account_updated', $user_id, $changes );

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
add_action( 'um_submit_account_details', 'um_submit_account_details' );


/**
 * Hidden inputs for account form
 *
 * @param $args
 */
function um_account_page_hidden_fields( $args ) {
	?>

	<input type="hidden" name="_um_account" id="_um_account" value="1" />
	<input type="hidden" name="_um_account_tab" id="_um_account_tab" value="<?php echo esc_attr( UM()->account()->current_tab ); ?>" />

	<?php
}
add_action( 'um_account_page_hidden_fields', 'um_account_page_hidden_fields' );


/**
 * Before delete account tab content
 */
function um_before_account_delete() {
	echo wpautop( htmlspecialchars( UM()->options()->get( 'delete_account_text' ) ) );
}
add_action( 'um_before_account_delete', 'um_before_account_delete' );


/**
 * Before notifications account tab content
 *
 * @param array $args
 *
 * @throws Exception
 */
function um_before_account_notifications( $args = array() ) {
	$output = UM()->account()->get_tab_fields( 'notifications', $args );
	if ( substr_count( $output, '_enable_new_' ) ) { ?>

		<p><?php _e( 'Select what email notifications do you want to receive', 'ultimate-member' ); ?></p>

	<?php }
}
add_action( 'um_before_account_notifications', 'um_before_account_notifications' );


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


/**
 * Update Account Email Notification
 *
 * @param $user_id
 * @param $changed
 */
function um_account_updated_notification( $user_id, $changed ) {
	um_fetch_user( $user_id );
	UM()->mail()->send( um_user( 'user_email' ), 'changedaccount_email' );
}
add_action( 'um_after_user_account_updated', 'um_account_updated_notification', 20, 2 );


/**
 * Disable WP native email notification when change email on user account
 *
 * @param $user_id
 * @param $changed
 */
function um_disable_native_email_notificatiion( $changed, $user_id ) {
	add_filter( 'send_email_change_email', '__return_false' );
}
add_action( 'um_account_pre_update_profile', 'um_disable_native_email_notificatiion', 10, 2 );