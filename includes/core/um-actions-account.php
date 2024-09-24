<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate for errors in account form
 *
 * @param array $args
 */
function um_submit_account_errors_hook( $args ) {
	global $current_user;

	if ( ! isset( $args['_um_account'] ) && ! isset( $args['_um_account_tab'] ) ) {
		return;
	}

	$tab = sanitize_key( $args['_um_account_tab'] );

	if ( ! wp_verify_nonce( $args[ 'um_account_nonce_' . $tab ], 'um_update_account_' . $tab ) ) {
		UM()->form()->add_error( 'um_account_security', __( 'Are you hacking? Please try again!', 'ultimate-member' ) );
	}

	switch ( $tab ) {
		case 'delete': {
			// delete account
			if ( UM()->account()->current_password_is_required( 'delete' ) ) {
				if ( strlen( trim( $args['single_user_password'] ) ) === 0 ) {
					UM()->form()->add_error( 'single_user_password', __( 'You must enter your password', 'ultimate-member' ) );
				} else {
					if ( ! wp_check_password( trim( $args['single_user_password'] ), $current_user->data->user_pass, $current_user->data->ID ) ) {
						UM()->form()->add_error( 'single_user_password', __( 'This is not your password', 'ultimate-member' ) );
					}
				}
			}

			UM()->account()->current_tab = 'delete';

			break;
		}

		case 'password': {

			// change password
			UM()->account()->current_tab = 'password';

			if ( isset( $args['user_password'] ) ) {
				$args['user_password'] = trim( $args['user_password'] );
			}

			if ( isset( $args['confirm_user_password'] ) ) {
				$args['confirm_user_password'] = trim( $args['confirm_user_password'] );
			}

			if ( empty( $args['user_password'] ) ) {
				UM()->form()->add_error( 'user_password', __( 'Password is required', 'ultimate-member' ) );
				return;
			}

			if ( empty( $args['confirm_user_password'] ) ) {
				UM()->form()->add_error( 'user_password', __( 'Password confirmation is required', 'ultimate-member' ) );
				return;
			}

			// Check for "\" in password.
			if ( false !== strpos( wp_unslash( $args['user_password'] ), '\\' ) ) {
				UM()->form()->add_error( 'user_password', __( 'Passwords may not contain the character "\\".', 'ultimate-member' ) );
				return;
			}

			if ( ! empty( $args['user_password'] ) && ! empty( $args['confirm_user_password'] ) ) {

				if ( UM()->account()->current_password_is_required( 'password' ) ) {
					if ( empty( $args['current_user_password'] ) ) {
						UM()->form()->add_error( 'current_user_password', __( 'This is not your password', 'ultimate-member' ) );
						return;
					} else {
						if ( ! wp_check_password( $args['current_user_password'], $current_user->data->user_pass, $current_user->data->ID ) ) {
							UM()->form()->add_error( 'current_user_password', __( 'This is not your password', 'ultimate-member' ) );
							return;
						}
					}
				}

				if ( $args['user_password'] && $args['user_password'] !== $args['confirm_user_password'] ) {
					UM()->form()->add_error( 'user_password', __( 'Your new password does not match', 'ultimate-member' ) );
					return;
				}

				if ( UM()->options()->get( 'require_strongpass' ) ) {
					$min_length = UM()->options()->get( 'password_min_chars' );
					$min_length = ! empty( $min_length ) ? $min_length : 8;
					$max_length = UM()->options()->get( 'password_max_chars' );
					$max_length = ! empty( $max_length ) ? $max_length : 30;

					if ( is_user_logged_in() ) {
						um_fetch_user( get_current_user_id() );
					}

					$user_login = um_user( 'user_login' );
					$user_email = um_user( 'user_email' );

					if ( mb_strlen( wp_unslash( $args['user_password'] ) ) < $min_length ) {
						UM()->form()->add_error( 'user_password', sprintf( __( 'Your password must contain at least %d characters', 'ultimate-member' ), $min_length ) );
					}

					if ( mb_strlen( wp_unslash( $args['user_password'] ) ) > $max_length ) {
						UM()->form()->add_error( 'user_password', sprintf( __( 'Your password must contain less than %d characters', 'ultimate-member' ), $max_length ) );
					}

					if ( strpos( strtolower( $user_login ), strtolower( $args['user_password'] )  ) > -1 ) {
						UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your username', 'ultimate-member' ) );
					}

					if ( strpos( strtolower( $user_email ), strtolower( $args['user_password'] )  ) > -1 ) {
						UM()->form()->add_error( 'user_password', __( 'Your password cannot contain the part of your email address', 'ultimate-member' ) );
					}

					if ( ! UM()->validation()->strong_pass( $args['user_password'] ) ) {
						UM()->form()->add_error( 'user_password', __( 'Your password must contain at least one lowercase letter, one capital letter and one number', 'ultimate-member' ) );
					}
				}
			}

			break;
		}

		case 'account':
		case 'general': {
			// errors on general tab
			$account_name_require = UM()->options()->get( 'account_name_require' );

			if ( isset( $args['user_login'] ) ) {
				$args['user_login'] = sanitize_user( $args['user_login'] );
			}
			if ( isset( $args['first_name'] ) ) {
				$args['first_name'] = sanitize_text_field( $args['first_name'] );
			}
			if ( isset( $args['last_name'] ) ) {
				$args['last_name'] = sanitize_text_field( $args['last_name'] );
			}
			if ( isset( $args['user_email'] ) ) {
				$args['user_email'] = sanitize_email( $args['user_email'] );
			}
			if ( isset( $args['single_user_password'] ) ) {
				$args['single_user_password'] = trim( $args['single_user_password'] );
			}

			if ( isset( $args['first_name'] ) && ( strlen( trim( $args['first_name'] ) ) === 0 && $account_name_require ) ) {
				UM()->form()->add_error( 'first_name', __( 'You must provide your first name', 'ultimate-member' ) );
			}

			if ( isset( $args['last_name'] ) && ( strlen( trim( $args['last_name'] ) ) === 0 && $account_name_require ) ) {
				UM()->form()->add_error( 'last_name', __( 'You must provide your last name', 'ultimate-member' ) );
			}

			if ( isset( $args['user_email'] ) ) {

				if ( strlen( trim( $args['user_email'] ) ) === 0 ) {
					UM()->form()->add_error( 'user_email', __( 'You must provide your email', 'ultimate-member' ) );
				}

				if ( ! is_email( $args['user_email'] ) ) {
					UM()->form()->add_error( 'user_email', __( 'Please provide a valid email', 'ultimate-member' ) );
				}

				if ( email_exists( $args['user_email'] ) && email_exists( $args['user_email'] ) !== get_current_user_id() ) {
					UM()->form()->add_error( 'user_email', __( 'Please provide a valid email', 'ultimate-member' ) );
				}
			}

			// check account password
			if ( UM()->account()->current_password_is_required( 'general' ) ) {
				if ( strlen( $args['single_user_password'] ) === 0 ) {
					UM()->form()->add_error( 'single_user_password', __( 'You must enter your password', 'ultimate-member' ) );
				} else {
					if ( ! wp_check_password( $args['single_user_password'], $current_user->data->user_pass, $current_user->data->ID ) ) {
						UM()->form()->add_error( 'single_user_password', __( 'This is not your password', 'ultimate-member' ) );
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
			do_action( 'um_submit_account_' . $tab . '_tab_errors_hook' );
			break;
	}

	UM()->account()->current_tab = $tab;
}
add_action( 'um_submit_account_errors_hook', 'um_submit_account_errors_hook' );


/**
 * Submit account page changes
 *
 * @param $args
 */
function um_submit_account_details( $args ) {
	$tab = ( get_query_var( 'um_tab' ) ) ? get_query_var( 'um_tab' ) : 'general';

	$current_tab = isset( $args['_um_account_tab'] ) ? sanitize_key( $args['_um_account_tab'] ) : '';

	$user_id = um_user( 'ID' );

	//change password account's tab
	if ( 'password' === $current_tab && $args['user_password'] && $args['confirm_user_password'] ) {
		$changes['user_pass'] = trim( $args['user_password'] );
		$args['user_id']      = get_current_user_id();

		UM()->user()->password_changed();

		add_filter( 'send_password_change_email', '__return_false' );

		//clear all sessions with old passwords
		$user = WP_Session_Tokens::get_instance( $args['user_id'] );
		$user->destroy_all();

		wp_set_password( $changes['user_pass'], $args['user_id'] );

		do_action( 'um_before_signon_after_account_changes', $args );

		wp_signon(
			array(
				'user_login'    => um_user( 'user_login' ),
				'user_password' => $changes['user_pass'],
			)
		);
	}

	// delete account
	if ( 'delete' === $current_tab ) {
		if ( current_user_can( 'delete_users' ) || um_user( 'can_delete_profile' ) ) {
			UM()->user()->delete();

			if ( um_user( 'after_delete' ) && um_user( 'after_delete' ) === 'redirect_home' ) {
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
				um_safe_redirect( $redirect_url );
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
	foreach ( $args as $k => $v ) {
		if ( ! in_array( $k, $arr_fields, true ) ) {
			continue;
		}

		if ( 'single_user_password' === $k || 'user_login' === $k ) {
			continue;
		}

		if ( 'first_name' === $k || 'last_name' === $k || 'user_password' === $k ) {
			$v = sanitize_text_field( $v );
		} elseif ( 'user_email' === $k ) {
			$v = sanitize_email( $v );
		} elseif ( 'hide_in_members' === $k || 'um_show_last_login' === $k ) {
			$v = array_map( 'sanitize_text_field', $v );
		}

		$changes[ $k ] = $v;
	}

	if ( isset( $changes['hide_in_members'] ) ) {
		if ( UM()->member_directory()->get_hide_in_members_default() ) {
			if ( __( 'Yes', 'ultimate-member' ) === $changes['hide_in_members'] || 'Yes' === $changes['hide_in_members'] || array_intersect( array( 'Yes', __( 'Yes', 'ultimate-member' ) ), $changes['hide_in_members'] ) ) {
				delete_user_meta( $user_id, 'hide_in_members' );
				unset( $changes['hide_in_members'] );
			}
		} else {
			if ( __( 'No', 'ultimate-member' ) === $changes['hide_in_members'] || 'No' === $changes['hide_in_members'] || array_intersect( array( 'No', __( 'No', 'ultimate-member' ) ), $changes['hide_in_members'] ) ) {
				delete_user_meta( $user_id, 'hide_in_members' );
				unset( $changes['hide_in_members'] );
			}
		}
	}

	if ( isset( $changes['um_show_last_login'] ) ) {
		if ( 'yes' === $changes['um_show_last_login'] || array_intersect( array( 'yes' ), $changes['um_show_last_login'] ) ) {
			delete_user_meta( $user_id, 'um_show_last_login' );
			unset( $changes['um_show_last_login'] );
		}
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

	if ( isset( $changes['first_name'] ) || isset( $changes['last_name'] ) || isset( $changes['nickname'] ) || isset( $changes['user_email'] ) ) {
		$user = get_userdata( $user_id );
		if ( ! empty( $user ) && ! is_wp_error( $user ) ) {
			UM()->user()->previous_data['display_name'] = $user->display_name;

			if ( isset( $changes['first_name'] ) ) {
				UM()->user()->previous_data['first_name'] = $user->first_name;
			}
			if ( isset( $changes['last_name'] ) ) {
				UM()->user()->previous_data['last_name'] = $user->last_name;
			}
			if ( isset( $changes['nickname'] ) ) {
				UM()->user()->previous_data['nickname'] = $user->nickname;
			}
			if ( isset( $changes['user_email'] ) ) {
				UM()->user()->previous_data['user_email'] = $user->user_email;
			}
		}
	}

	UM()->user()->update_profile( $changes, 'account' );

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
			if ( icl_get_current_language() !== icl_get_default_language() ) {
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
 * Maybe clear all sessions except current after changing email. Because email can be used for login.
 * Using a proper hook that triggers on email changed action in WordPress native handlers.
 * It starts to work sitewide in UM Account and there wp_update_user with new user_email attribute is used.
 *
 * @since  2.8.7
 *
 * @param  bool   $send     Whether to send the email.
 * @param  array  $user     The original user array.
 * @param  array  $userdata The updated user array.
 * @return bool
 */
function um_maybe_flush_users_session_update_user( $send, $user, $userdata ) {
	// Clear all sessions except current after changing email. Because email can be used for login.
	if ( get_current_user_id() === $userdata['ID'] ) {
		wp_destroy_other_sessions();
	} else {
		$sessions_manager = WP_Session_Tokens::get_instance( $userdata['ID'] );
		// Remove all the session data for all users.
		$sessions_manager->destroy_all();
	}

	return $send;
}
add_filter( 'send_email_change_email', 'um_maybe_flush_users_session_update_user', 20, 3 );

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
	if ( UM()->account()->current_password_is_required( 'delete' ) ) {
		$text = UM()->options()->get( 'delete_account_text' );
	} else {
		$text = UM()->options()->get( 'delete_account_no_pass_required_text' );
	}

	printf( __( '%s', 'ultimate-member' ), wpautop( htmlspecialchars( $text ) ) );
}
add_action( 'um_before_account_delete', 'um_before_account_delete' );

/**
 * Before notifications account tab content.
 *
 * @param array $args
 *
 * @throws Exception
 */
function um_before_account_notifications( $args = array() ) {
	$output = UM()->account()->get_tab_fields( 'notifications', $args );
	if ( substr_count( $output, '_enable_new_' ) ) {
		?>
		<p><?php esc_html_e( 'Select what email notifications you want to receive', 'ultimate-member' ); ?></p>
		<?php
	}
}
add_action( 'um_before_account_notifications', 'um_before_account_notifications' );

/**
 * Update Profile URL, display name, full name.
 *
 * @version 2.2.5
 *
 * @param   int   $user_id  The user ID.
 * @param   array $changes  An array of fields values.
 */
function um_after_user_account_updated_permalink( $user_id, $changes ) {
	if ( isset( $changes['first_name'] ) || isset( $changes['last_name'] ) ) {
		/** This action is documented in ultimate-member/includes/core/um-actions-register.php */
		do_action( 'um_update_profile_full_name', $user_id, $changes );
	}
}
add_action( 'um_after_user_account_updated', 'um_after_user_account_updated_permalink', 10, 2 );


/**
 * Update Account Email Notification
 *
 * @param $user_id
 * @param $changed
 */
function um_account_updated_notification( $user_id, $changed ) {
	// phpcs:disable WordPress.Security.NonceVerification
	if ( 'password' !== $_POST['_um_account_tab'] || ! UM()->options()->get( 'changedpw_email_on' ) ) {
		// Avoid email duplicates (account changed and password changed) on the password change tab.
		um_fetch_user( $user_id );
		UM()->mail()->send( um_user( 'user_email' ), 'changedaccount_email' );
	}
	// phpcs:enable WordPress.Security.NonceVerification
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


/**
 * Add export and erase user's data in privacy tab
 *
 * @param $args
 */
add_action( 'um_after_account_privacy', 'um_after_account_privacy' );
function um_after_account_privacy( $args ) {
	global $wpdb;
	$user_id = get_current_user_id();
	?>

	<div class="um-field um-field-export_data">
		<div class="um-field-label">
			<label>
				<?php esc_html_e( 'Download your data', 'ultimate-member' ); ?>
			</label>
			<span class="um-tip um-tip-<?php echo is_rtl() ? 'e' : 'w'; ?>" title="<?php esc_attr_e( 'You can request a file with the information that we believe is most relevant and useful to you.', 'ultimate-member' ); ?>">
				<i class="um-icon-help-circled"></i>
			</span>
			<div class="um-clear"></div>
		</div>
		<?php
		$completed = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID
				FROM $wpdb->posts
				WHERE post_author = %d AND
					  post_type = 'user_request' AND
					  post_name = 'export_personal_data' AND
					  post_status = 'request-completed'
				ORDER BY ID DESC
				LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		if ( ! empty( $completed ) ) {

			$exports_url = wp_privacy_exports_url();

			echo '<p>' . esc_html__( 'You could download your previous data:', 'ultimate-member' ) . '</p>';
			echo '<a href="' . esc_url( $exports_url . get_post_meta( $completed['ID'], '_export_file_name', true ) ) . '">' . esc_html__( 'Download Personal Data', 'ultimate-member' ) . '</a>';
			echo '<p>' . esc_html__( 'You could send a new request for an export of personal your data.', 'ultimate-member' ) . '</p>';

		}

		$pending = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID, post_status
				FROM $wpdb->posts
				WHERE post_author = %d AND
					  post_type = 'user_request' AND
					  post_name = 'export_personal_data' AND
					  post_status != 'request-completed'
				ORDER BY ID DESC
				LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		if ( ! empty( $pending ) && 'request-pending' === $pending['post_status'] ) {
			echo '<p>' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your export request.', 'ultimate-member' ) . '</p>';
		} elseif ( ! empty( $pending ) && 'request-confirmed' === $pending['post_status'] ) {
			echo '<p>' . esc_html__( 'The administrator has not yet approved downloading the data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';
		} else {
			if ( UM()->account()->current_password_is_required( 'privacy_download_data' ) ) {
				?>
				<label name="um-export-data">
					<?php esc_html_e( 'Enter your current password to confirm a new export of your personal data.', 'ultimate-member' ); ?>
				</label>
				<div class="um-field-area">
					<?php if ( UM()->options()->get( 'toggle_password' ) ) { ?>
						<div class="um-field-area-password">
							<input id="um-export-data" type="password" placeholder="<?php esc_attr_e( 'Password', 'ultimate-member' ); ?>">
							<span class="um-toggle-password"><i class="um-icon-eye"></i></span>
						</div>
					<?php } else { ?>
						<input id="um-export-data" type="password" placeholder="<?php esc_attr_e( 'Password', 'ultimate-member' ); ?>">
					<?php } ?>
					<div class="um-field-error um-export-data">
						<span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span><?php esc_html_e( 'You must enter a password', 'ultimate-member' ); ?>
					</div>
					<div class="um-field-area-response um-export-data"></div>
				</div>

			<?php } else { ?>

				<label name="um-export-data">
					<?php esc_html_e( 'To export of your personal data, click the button below.', 'ultimate-member' ); ?>
				</label>
				<div class="um-field-area-response um-export-data"></div>

			<?php } ?>

			<a class="um-request-button um-export-data-button" data-action="um-export-data" href="javascript:void(0);">
				<?php esc_html_e( 'Request data', 'ultimate-member' ); ?>
			</a>
		<?php } ?>

	</div>

	<div class="um-field um-field-export_data">
		<div class="um-field-label">
			<label>
				<?php esc_html_e( 'Erase of your data', 'ultimate-member' ); ?>
			</label>
			<span class="um-tip um-tip-<?php echo is_rtl() ? 'e' : 'w'; ?>" title="<?php esc_attr_e( 'You can request erasing of the data that we have about you.', 'ultimate-member' ); ?>">
				<i class="um-icon-help-circled"></i>
			</span>
			<div class="um-clear"></div>
		</div>

		<?php
		$completed = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID
				FROM $wpdb->posts
				WHERE post_author = %d AND
					  post_type = 'user_request' AND
					  post_name = 'remove_personal_data' AND
					  post_status = 'request-completed'
				ORDER BY ID DESC
				LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		if ( ! empty( $completed ) ) {

			echo '<p>' . esc_html__( 'Your personal data has been deleted.', 'ultimate-member' ) . '</p>';
			echo '<p>' . esc_html__( 'You could send a new request for deleting your personal data.', 'ultimate-member' ) . '</p>';

		}

		$pending = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID, post_status
				FROM $wpdb->posts
				WHERE post_author = %d AND
					  post_type = 'user_request' AND
					  post_name = 'remove_personal_data' AND
					  post_status != 'request-completed'
				ORDER BY ID DESC
				LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		if ( ! empty( $pending ) && 'request-pending' === $pending['post_status'] ) {
			echo '<p>' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your deletion request.', 'ultimate-member' ) . '</p>';
		} elseif ( ! empty( $pending ) && 'request-confirmed' === $pending['post_status'] ) {
			echo '<p>' . esc_html__( 'The administrator has not yet approved deleting your data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';
		} else {
			if ( UM()->account()->current_password_is_required( 'privacy_erase_data' ) ) {
				?>
				<label name="um-erase-data">
					<?php esc_html_e( 'Enter your current password to confirm the erasure of your personal data.', 'ultimate-member' ); ?>
					<?php if ( UM()->options()->get( 'toggle_password' ) ) { ?>
						<div class="um-field-area-password">
							<input id="um-erase-data" type="password" placeholder="<?php esc_attr_e( 'Password', 'ultimate-member' ); ?>">
							<span class="um-toggle-password"><i class="um-icon-eye"></i></span>
						</div>
					<?php } else { ?>
						<input id="um-erase-data" type="password" placeholder="<?php esc_attr_e( 'Password', 'ultimate-member' ); ?>">
					<?php } ?>
					<div class="um-field-error um-erase-data">
						<span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span><?php esc_html_e( 'You must enter a password', 'ultimate-member' ); ?>
					</div>
					<div class="um-field-area-response um-erase-data"></div>
				</label>

			<?php } else { ?>

				<label name="um-erase-data">
					<?php esc_html_e( 'Require erasure of your personal data, click on the button below.', 'ultimate-member' ); ?>
					<div class="um-field-area-response um-erase-data"></div>
				</label>

			<?php } ?>

			<a class="um-request-button um-erase-data-button" data-action="um-erase-data" href="javascript:void(0);">
				<?php esc_html_e( 'Request data erase', 'ultimate-member' ); ?>
			</a>
		<?php } ?>

	</div>

	<?php
}


function um_request_user_data() {
	UM()->check_ajax_nonce();

	if ( ! isset( $_POST['request_action'] ) ) {
		wp_send_json_error( __( 'Wrong request.', 'ultimate-member' ) );
	}

	$user_id        = get_current_user_id();
	$password       = ! empty( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
	$user           = get_userdata( $user_id );
	$hash           = $user->data->user_pass;
	$request_action = sanitize_key( $_POST['request_action'] );

	if ( 'um-export-data' === $request_action ) {
		if ( UM()->account()->current_password_is_required( 'privacy_download_data' ) ) {
			if ( ! wp_check_password( $password, $hash ) ) {
				$answer = esc_html__( 'The password you entered is incorrect.', 'ultimate-member' );
				wp_send_json_success( array( 'answer' => $answer ) );
			}
		}
	} elseif ( 'um-erase-data' === $request_action ) {
		if ( UM()->account()->current_password_is_required( 'privacy_erase_data' ) ) {
			if ( ! wp_check_password( $password, $hash ) ) {
				$answer = esc_html__( 'The password you entered is incorrect.', 'ultimate-member' );
				wp_send_json_success( array( 'answer' => $answer ) );
			}
		}
	}

	if ( 'um-export-data' === $request_action ) {
		$request_id = wp_create_user_request( $user->data->user_email, 'export_personal_data' );
	} elseif ( 'um-erase-data' === $request_action ) {
		$request_id = wp_create_user_request( $user->data->user_email, 'remove_personal_data' );
	}

	if ( ! isset( $request_id ) || empty( $request_id ) ) {
		wp_send_json_error( __( 'Wrong request.', 'ultimate-member' ) );
	}

	if ( is_wp_error( $request_id ) ) {
		$answer = esc_html( $request_id->get_error_message() );
	} else {
		wp_send_user_request( $request_id );
		if ( 'um-export-data' === $request_action ) {
			$answer = esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your export request.', 'ultimate-member' );
		} elseif ( 'um-erase-data' === $request_action ) {
			$answer = esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your deletion request.', 'ultimate-member' );
		}
	}

	wp_send_json_success( array( 'answer' => $answer ) );
}
add_action( 'wp_ajax_um_request_user_data', 'um_request_user_data' );
