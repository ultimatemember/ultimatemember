<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * It renders the content of main profile tab.
 *
 * @param array $args
 */
function um_profile_content_main( $args ) {
	if ( ! array_key_exists( 'mode', $args ) ) {
		return;
	}
	$mode = $args['mode'];

	// phpcs:ignore WordPress.Security.NonceVerification -- $_REQUEST is used for echo only
	if ( ! isset( $_REQUEST['um_action'] ) && ! UM()->options()->get( 'profile_tab_main' ) ) {
		return;
	}

	/**
	 * Filters user's ability to view a profile
	 *
	 * @since 1.3.x
	 * @hook  um_profile_can_view_main
	 *
	 * @param {int} $can_view   Can view profile. It's -1 by default.
	 * @param {int} $profile_id User Profile ID.
	 *
	 * @return {int} Can view profile. Set it to -1 for displaying and vice versa to hide.
	 *
	 * @example <caption>Make profile hidden.</caption>
	 * function my_profile_can_view_main( $can_view, $profile_id ) {
	 *     $can_view = 1; // make profile hidden.
	 *     return $can_view;
	 * }
	 * add_filter( 'um_profile_can_view_main', 'my_profile_can_view_main', 10, 2 );
	 */
	$can_view = apply_filters( 'um_profile_can_view_main', -1, um_profile_id() );

	if ( -1 === (int) $can_view ) {
		/**
		 * Fires before UM Form content.
		 *
		 * @since 1.3.x
		 * @hook  um_before_form
		 *
		 * @param {array} $args UM Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action before UM form.</caption>
		 * function my_before_form( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_before_form', 'my_before_form' );
		 */
		do_action( 'um_before_form', $args );
		/**
		 * Fires before UM Form fields.
		 *
		 * Note: $mode can be equals to 'login', 'profile', 'register'.
		 *
		 * @since 1.3.x
		 * @hook  um_before_{$mode}_fields
		 *
		 * @param {array} $args UM Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action before UM Profile form fields.</caption>
		 * function my_before_profile_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_before_profile_fields', 'my_before_profile_fields' );
		 * @example <caption>Make any custom action before UM Login form fields.</caption>
		 * function my_before_login_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_before_login_fields', 'my_before_login_fields' );
		 * @example <caption>Make any custom action before UM Register form fields.</caption>
		 * function my_before_register_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_before_register_fields', 'my_before_register_fields' );
		 */
		do_action( "um_before_{$mode}_fields", $args );
		/**
		 * Fires for rendering UM Form fields.
		 *
		 * Note: $mode can be equals to 'login', 'profile', 'register'.
		 *
		 * @since 1.3.x
		 * @hook  um_main_{$mode}_fields
		 *
		 * @param {array} $args UM Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action when profile form fields are rendered.</caption>
		 * function my_main_profile_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_main_profile_fields', 'my_main_profile_fields' );
		 * @example <caption>Make any custom action when login form fields are rendered.</caption>
		 * function my_main_login_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_main_login_fields', 'my_main_login_fields' );
		 * @example <caption>Make any custom action when register form fields are rendered.</caption>
		 * function my_main_register_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_main_register_fields', 'my_main_register_fields' );
		 */
		do_action( "um_main_{$mode}_fields", $args );
		/**
		 * Fires after UM Form fields.
		 *
		 * @since 1.3.x
		 * @hook  um_after_form_fields
		 *
		 * @param {array} $args UM Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action after UM Form fields.</caption>
		 * function my_after_form_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_after_form_fields', 'my_after_form_fields' );
		 */
		do_action( 'um_after_form_fields', $args );
		/**
		 * Fires after UM Form fields.
		 *
		 * Note: $mode can be equals to 'login', 'profile', 'register'.
		 *
		 * @since 1.3.x
		 * @hook  um_after_{$mode}_fields
		 *
		 * @param {array} $args UM Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action after profile form fields.</caption>
		 * function my_after_profile_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_after_profile_fields', 'my_after_profile_fields' );
		 * @example <caption>Make any custom action after login form fields.</caption>
		 * function my_after_login_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_after_login_fields', 'my_after_login_fields' );
		 * @example <caption>Make any custom action after register form fields.</caption>
		 * function my_after_register_fields( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_after_register_fields', 'my_after_register_fields' );
		 */
		do_action( "um_after_{$mode}_fields", $args );
		/**
		 * Fires after UM Form content.
		 *
		 * @since 1.3.x
		 * @hook  um_after_form
		 *
		 * @param {array} $args UM Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action after UM Form content.</caption>
		 * function my_after_form( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_after_form', 'my_after_form' );
		 */
		do_action( 'um_after_form', $args );

	} else {
		?>
		<div class="um-profile-note">
			<span>
				<i class="um-faicon-lock"></i>
				<?php echo esc_html( $can_view ); ?>
			</span>
		</div>
		<?php
	}
}
add_action( 'um_profile_content_main', 'um_profile_content_main' );

/**
 * Update user's profile (frontend).
 *
 * @param array $args
 * @param array $form_data
 */
function um_user_edit_profile( $args, $form_data ) {
	$to_update = null;
	$files     = array();

	$user_id = null;
	if ( isset( $args['user_id'] ) ) {
		$user_id = $args['user_id'];
	} elseif ( isset( $args['_user_id'] ) ) {
		$user_id = $args['_user_id'];
	}

	if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
		UM()->user()->set( $user_id );
	} else {
		wp_die( esc_html__( 'You are not allowed to edit this user.', 'ultimate-member' ) );
	}

	$userinfo = UM()->user()->profile;

	/**
	 * Fires before collecting data to update on profile form submit.
	 *
	 * @since 1.3.x
	 * @hook um_user_before_updating_profile
	 *
	 * @param {array} $userinfo Userdata.
	 *
	 * @example <caption>Make any custom action before collecting data to update on profile form submit.</caption>
	 * function my_user_before_updating_profile( $role_key, $role_meta ) {
	 *     // your code here
	 * }
	 * add_action( 'um_user_before_updating_profile', 'my_user_before_updating_profile', 10, 2 );
	 */
	do_action( 'um_user_before_updating_profile', $userinfo );

	$fields = maybe_unserialize( $form_data['custom_fields'] );
	$fields = apply_filters( 'um_user_edit_profile_fields', $fields, $args, $form_data );

	// loop through fields
	if ( ! empty( $fields ) ) {
		$arr_restricted_fields = UM()->fields()->get_restricted_fields_for_edit( $user_id );

		foreach ( $fields as $key => $array ) {
			if ( ! isset( $array['type'] ) ) {
				continue;
			}

			if ( isset( $array['edit_forbidden'] ) ) {
				continue;
			}

			if ( is_array( $array ) ) {
				$origin_data = UM()->fields()->get_field( $key );
				if ( is_array( $origin_data ) ) {
					// Merge data passed with original field data.
					$array = array_merge( $origin_data, $array );
				}
			}

			// required option? 'required_opt' - it's field attribute predefined in the field data in code
			// @todo can be unnecessary. it's used in 1 place (user account).
			if ( isset( $array['required_opt'] ) ) {
				$opt = $array['required_opt'];
				if ( UM()->options()->get( $opt[0] ) !== $opt[1] ) {
					continue;
				}
			}

			// fields that need to be disabled in edit mode (profile) (email, username, etc.)
			if ( is_array( $arr_restricted_fields ) && in_array( $key, $arr_restricted_fields, true ) ) {
				continue;
			}

			if ( ! um_can_edit_field( $array ) || ! um_can_view_field( $array ) ) {
				continue;
			}

			// skip saving role here
			if ( in_array( $key, array( 'role', 'role_select', 'role_radio' ), true ) ) {
				continue;
			}

			//the same code in class-validation.php validate_fields_values for registration form
			//rating field validation
			if ( 'rating' === $array['type'] && isset( $args['submitted'][ $key ] ) ) {
				if ( ! is_numeric( $args['submitted'][ $key ] ) ) {
					continue;
				} else {
					if ( $array['number'] == 5 ) {
						if ( ! in_array( $args['submitted'][ $key ], range( 1, 5 ) ) ) {
							continue;
						}
					} elseif ( $array['number'] == 10 ) {
						if ( ! in_array( $args['submitted'][ $key ], range( 1, 10 ) ) ) {
							continue;
						}
					}
				}
			}

			/**
			 * Returns dropdown/multi-select options keys from a callback function
			 * @since 2019-05-30
			 */
			$has_custom_source = apply_filters( "um_has_dropdown_options_source__{$key}", false );
			if ( isset( $array['options'] ) && in_array( $array['type'], array( 'select', 'multiselect' ), true ) ) {
				$options = $array['options'];
				if ( ! empty( $array['custom_dropdown_options_source'] ) && function_exists( $array['custom_dropdown_options_source'] ) && ! $has_custom_source ) {
					if ( ! UM()->fields()->is_source_blacklisted( $array['custom_dropdown_options_source'] ) ) {
						$callback_result = call_user_func( $array['custom_dropdown_options_source'], $array['options'] );
						if ( is_array( $callback_result ) ) {
							$options = array_keys( $callback_result );
						}
					}
				}
				$array['options'] = apply_filters( "um_custom_dropdown_options__{$key}", $options );
			}

			//validation of correct values from options in wp-admin
			$stripslashes = '';
			if ( isset( $args['submitted'][ $key ] ) && is_string( $args['submitted'][ $key ] ) ) {
				$stripslashes = wp_unslash( $args['submitted'][ $key ] );
			}

			if ( 'select' === $array['type'] ) {
				if ( ! empty( $array['options'] ) && ! empty( $stripslashes ) && ! in_array( $stripslashes, array_map( 'trim', $array['options'] ) ) && ! $has_custom_source  ) {
					continue;
				}

				//update empty user meta
				if ( ! isset( $args['submitted'][ $key ] ) || '' === $args['submitted'][ $key ] ) {
					update_user_meta( $user_id, $key, '' );
				}
			}

			//validation of correct values from options in wp-admin
			//the user cannot set invalid value in the hidden input at the page
			if ( in_array( $array['type'], array( 'multiselect', 'checkbox', 'radio' ), true ) ) {
				if ( ! empty( $args['submitted'][ $key ] ) && ! empty( $array['options'] ) ) {
					if ( is_array( $args['submitted'][ $key ] ) ) {
						$args['submitted'][ $key ] = array_map( 'stripslashes', array_map( 'trim', $args['submitted'][ $key ] ) );
						if ( is_array( $array['options'] ) ) {
							$args['submitted'][ $key ] = array_intersect( $args['submitted'][ $key ], array_map( 'trim', $array['options'] ) );
						} else {
							$args['submitted'][ $key ] = array_intersect( $args['submitted'][ $key ], array( trim( $array['options'] ) ) );
						}
					} else {
						if ( is_array( $array['options'] ) ) {
							$args['submitted'][ $key ] = array_intersect( array( stripslashes( trim( $args['submitted'][ $key ] ) ) ), array_map( 'trim', $array['options'] ) );
						} else {
							$args['submitted'][ $key ] = array_intersect( array( stripslashes( trim( $args['submitted'][ $key ] ) ) ), array( trim( $array['options'] ) ) );
						}
					}
				}

				// update empty user meta
				if ( ! isset( $args['submitted'][ $key ] ) || '' === $args['submitted'][ $key ] ) {
					update_user_meta( $user_id, $key, array() );
				}
			}

			if ( isset( $args['submitted'][ $key ] ) ) {
				if ( in_array( $array['type'], array( 'image', 'file' ), true ) ) {
					if ( um_is_temp_file( $args['submitted'][ $key ] ) || 'empty_file' === $args['submitted'][ $key ] ) {
						$files[ $key ] = $args['submitted'][ $key ];
					} elseif( um_is_file_owner( UM()->uploader()->get_upload_base_url() . $user_id . '/' . $args['submitted'][ $key ], $user_id ) ) {

					} else {
						$files[ $key ] = 'empty_file';
					}
				} else {
					if ( 'password' === $array['type'] ) {
						$to_update[ $key ]         = wp_hash_password( $args['submitted'][ $key ] );
						// translators: %s: title.
						$args['submitted'][ $key ] = sprintf( __( 'Your choosed %s', 'ultimate-member' ), $array['title'] );
					} else {
						if ( isset( $userinfo[ $key ] ) && $args['submitted'][ $key ] !== $userinfo[ $key ] ) {
							$to_update[ $key ] = $args['submitted'][ $key ];
						} elseif ( '' !== $args['submitted'][ $key ] ) {
							$to_update[ $key ] = $args['submitted'][ $key ];
						}
					}
				}

				// use this filter after all validations has been completed, and we can extend data based on key
				$to_update = apply_filters( 'um_change_usermeta_for_update', $to_update, $args, $fields, $key );
			}
		}
	}

	$description_key = UM()->profile()->get_show_bio_key( $args );
	if ( ! isset( $to_update[ $description_key ] ) && isset( $args['submitted'][ $description_key ] ) ) {
		if ( ! empty( $form_data['use_custom_settings'] ) && ! empty( $form_data['show_bio'] ) ) {
			$to_update[ $description_key ] = $args['submitted'][ $description_key ];
		} else {
			if ( UM()->options()->get( 'profile_show_bio' ) ) {
				$to_update[ $description_key ] = $args['submitted'][ $description_key ];
			}
		}
	}

	// Secure selected role.
	// It's for a legacy case `array_key_exists( 'editable', $fields['role'] )` and similar.
	if ( ( isset( $fields['role'] ) && ( ! array_key_exists( 'editable', $fields['role'] ) || ! empty( $fields['role']['editable'] ) ) && um_can_view_field( $fields['role'] ) ) ||
		( isset( $fields['role_select'] ) && ( ! array_key_exists( 'editable', $fields['role_select'] ) || ! empty( $fields['role_select']['editable'] ) ) && um_can_view_field( $fields['role_select'] ) ) ||
		( isset( $fields['role_radio'] ) && ( ! array_key_exists( 'editable', $fields['role_radio'] ) || ! empty( $fields['role_radio']['editable'] ) ) && um_can_view_field( $fields['role_radio'] ) ) ) {

		if ( ! empty( $args['submitted']['role'] ) ) {
			global $wp_roles;
			$exclude_roles = array_diff( array_keys( $wp_roles->roles ), UM()->roles()->get_editable_user_roles() );

			if ( ! in_array( $args['submitted']['role'], $exclude_roles, true ) ) {
				$to_update['role'] = $args['submitted']['role'];
			}

			$args['roles_before_upgrade'] = UM()->roles()->get_all_user_roles( $user_id );
		}
	}

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_user_pre_updating_profile
	 * @description Some actions before profile submit
	 * @input_vars
	 * [{"var":"$userinfo","type":"array","desc":"Submitted User Data"},
	 * {"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_user_pre_updating_profile', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_action( 'um_user_pre_updating_profile', 'my_user_pre_updating_profile', 10, 2 );
	 * function my_user_pre_updating_profile( $userinfo, $user_id ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_user_pre_updating_profile', $to_update, $user_id, $form_data );

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_user_pre_updating_profile_array
	 * @description Change submitted data before update profile
	 * @input_vars
	 * [{"var":"$to_update","type":"array","desc":"Profile data upgrade"},
	 * {"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_user_pre_updating_profile_array', 'function_name', 10, 2 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_user_pre_updating_profile_array', 'my_user_pre_updating_profile', 10, 2 );
	 * function my_user_pre_updating_profile( $to_update, $user_id ) {
	 *     // your code here
	 *     return $to_update;
	 * }
	 * ?>
	 */
	$to_update = apply_filters( 'um_user_pre_updating_profile_array', $to_update, $user_id, $form_data );

	if ( is_array( $to_update ) ) {
		if ( isset( $to_update['first_name'] ) || isset( $to_update['last_name'] ) || isset( $to_update['nickname'] ) ) {
			$user = get_userdata( $user_id );
			if ( ! empty( $user ) && ! is_wp_error( $user ) ) {
				UM()->user()->previous_data['display_name'] = $user->display_name;

				if ( isset( $to_update['first_name'] ) ) {
					UM()->user()->previous_data['first_name'] = $user->first_name;
				}

				if ( isset( $to_update['last_name'] ) ) {
					UM()->user()->previous_data['last_name'] = $user->last_name;
				}

				if ( isset( $to_update['nickname'] ) ) {
					UM()->user()->previous_data['nickname'] = $user->nickname;
				}
			}
		}

		UM()->user()->update_profile( $to_update );
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_after_user_updated
		 * @description Some actions after user profile updated
		 * @input_vars
		 * [{"var":"$user_id","type":"int","desc":"User ID"},
		 * {"var":"$args","type":"array","desc":"Form Data"},
		 * {"var":"$userinfo","type":"array","desc":"Submitted User Data"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_after_user_updated', 'function_name', 10, 33 );
		 * @example
		 * <?php
		 * add_action( 'um_after_user_updated', 'my_after_user_updated', 10, 3 );
		 * function my_after_user_updated( $user_id, $args, $userinfo ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_after_user_updated', $user_id, $args, $to_update );
	}

	/** This action is documented in ultimate-member/includes/core/um-actions-register.php */
	$files = apply_filters( 'um_user_pre_updating_files_array', $files, $user_id );
	if ( ! empty( $files ) && is_array( $files ) ) {
		UM()->uploader()->replace_upload_dir = true;
		UM()->uploader()->move_temporary_files( $user_id, $files );
		UM()->uploader()->replace_upload_dir = false;
	}

	/** This action is documented in ultimate-member/includes/core/um-actions-register.php */
	do_action( 'um_update_profile_full_name', $user_id, $to_update );

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_user_after_updating_profile
	 * @description After upgrade user's profile
	 * @input_vars
	 * [{"var":"$submitted","type":"array","desc":"Form data"},
	 * {"var":"$user_id","type":"int","desc":"User Id"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_user_after_updating_profile', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_user_after_updating_profile', 'my_user_after_updating_profile'', 10, 2 );
	 * function my_user_after_updating_profile( $submitted, $user_id ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_user_after_updating_profile', $to_update, $user_id, $args );

	// Finally redirect to profile.
	$url = um_user_profile_url( $user_id );
	$url = apply_filters( 'um_update_profile_redirect_after', $url, $user_id, $args );
	// Not `um_safe_redirect()` because predefined user profile page is situated on the same host.
	wp_safe_redirect( um_edit_my_profile_cancel_uri( $url ) );
	exit;
}
add_action( 'um_user_edit_profile', 'um_user_edit_profile', 10, 2 );


/**
 * Validate nonce when profile form submit.
 *
 * @param array $submitted_data
 */
function um_profile_validate_nonce( $submitted_data ) {
	$user_id = isset( $submitted_data['user_id'] ) ? $submitted_data['user_id'] : '';
	$nonce   = isset( $submitted_data['profile_nonce'] ) ? $submitted_data['profile_nonce'] : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'um-profile-nonce' . $user_id ) ) {
		wp_die( esc_html__( 'This is not possible for security reasons.', 'ultimate-member' ) );
	}
}
add_action( 'um_submit_form_errors_hook__profile', 'um_profile_validate_nonce', 1 );

// @todo maybe remove that because double validate
add_filter( 'um_user_pre_updating_files_array', array( UM()->validation(), 'validate_files' ) );
// @todo maybe remove that because double validate
add_filter( 'um_before_save_filter_submitted', array( UM()->validation(), 'validate_fields_values' ), 10, 3 );

/**
 * Leave roles for User, which are not in the list of update profile (are default WP or 3rd plugins roles)
 *
 * @param $user_id
 * @param $args
 * @param $to_update
 */
function um_restore_default_roles( $user_id, $args, $to_update ) {
	if ( ! empty( $args['submitted']['role'] ) && ! empty( $to_update['role'] ) ) {
		$wp_user = new WP_User( $user_id );

		$leave_roles = array_diff( $args['roles_before_upgrade'], UM()->roles()->get_editable_user_roles() );

		if ( UM()->roles()->is_role_custom( $to_update['role'] ) ) {
			$wp_user->remove_role( $to_update['role'] );
			$roles = array_merge( $leave_roles, array( $to_update['role'] ) );
		} else {
			$roles = array_merge( array( $to_update['role'] ), $leave_roles );
		}

		foreach ( $roles as $role_k ) {
			$wp_user->add_role( $role_k );
		}
	}
}
add_action( 'um_after_user_updated', 'um_restore_default_roles', 10, 3 );

/**
 * If editing another user
 *
 * @param $args
 */
function um_editing_user_id_input( $args ) {
	if ( true === UM()->fields()->editing && 'profile' === UM()->fields()->set_mode && UM()->user()->target_id ) {
		?>
		<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( UM()->user()->target_id ); ?>" />
		<input type="hidden" name="profile_nonce" id="profile_nonce" value="<?php echo esc_attr( UM()->form()->nonce ); ?>" />
		<?php
	}
}
add_action( 'um_after_form_fields', 'um_editing_user_id_input' );

if ( ! function_exists( 'um_profile_remove_wpseo' ) ) {
	/**
	 * Remove Yoast from front end for the Profile page
	 *
	 * @see   https://gist.github.com/amboutwe/1c847f9c706ff6f8c9eca76abea23fb6
	 * @since 2.1.6
	 */
	function um_profile_remove_wpseo() {
		if ( um_is_core_page( 'user' ) && um_get_requested_user() ) {

			/* Yoast SEO 12.4 */
			if ( isset( $GLOBALS['wpseo_front'] ) && is_object( $GLOBALS['wpseo_front'] ) ) {
				remove_action( 'wp_head', array( $GLOBALS['wpseo_front'], 'head' ), 1 );
			} elseif ( class_exists( 'WPSEO_Frontend' ) && is_callable( array( 'WPSEO_Frontend', 'get_instance' ) ) ) {
				remove_action( 'wp_head', array( WPSEO_Frontend::get_instance(), 'head' ), 1 );
			}

			/* Yoast SEO 14.1 */
			remove_all_filters( 'wpseo_head' );

			/* Restore title and canonical if broken */
			if ( ! has_action( 'wp_head', '_wp_render_title_tag' ) ) {
				add_action( 'wp_head', '_wp_render_title_tag', 18 );
			}
			if ( ! has_action( 'wp_head', 'rel_canonical' ) ) {
				add_action( 'wp_head', 'rel_canonical', 18 );
			}
		}
	}
}
add_action( 'get_header', 'um_profile_remove_wpseo', 8 );

/**
 * The profile page SEO tags
 *
 * @see https://ogp.me/ - The Open Graph protocol
 * @see https://developer.twitter.com/en/docs/tweets/optimize-with-cards/overview/summary - The Twitter Summary card
 * @see https://schema.org/Person - The schema.org Person schema
 */
function um_profile_dynamic_meta_desc() {
	if ( um_is_core_page( 'user' ) && um_get_requested_user() ) {
		$user_id = um_get_requested_user();

		if ( um_user( 'ID' ) !== $user_id ) {
			um_fetch_user( $user_id );
		}

		/**
		 * Settings by the priority:
		 *  "Search engine visibility" in [wp-admin > Settings > Reading]
		 *  "Profile Privacy" in [Account > Privacy]
		 *  "Avoid indexing my profile by search engines in [Account > Privacy]
		 *  "Avoid indexing profile by search engines" in [wp-admin > Ultimate Member > User Roles > Edit Role]
		 *  "Avoid indexing profile by search engines" in [wp-admin > Ultimate Member > Settings > General > Users]
		 */
		if ( UM()->user()->is_profile_noindex( $user_id ) ) {
			echo '<meta name="robots" content="noindex, nofollow" />';
			return;
		}

		$locale    = get_user_locale( $user_id );
		$site_name = get_bloginfo( 'name' );

		$twitter = (string) um_user( 'twitter' );
		if ( ! empty( $twitter ) ) {
			$twitter = trim( str_replace( 'https://twitter.com/', '', $twitter ), "/ \n\r\t\v\0" );
		}

		$title       = trim( um_user( 'display_name' ) );
		$description = um_convert_tags( UM()->options()->get( 'profile_desc' ) );
		$url         = um_user_profile_url( $user_id );

		/**
		 * Filters the profile SEO image size. Default 190. Available 'original'.
		 *
		 * @param {string} $image_size Image size.
		 * @param {int}    $user_id    User ID.
		 *
		 * @return {array} Changed image type
		 *
		 * @since 2.5.5
		 * @hook um_profile_dynamic_meta_image_size
		 *
		 * @example <caption>Change meta image to cover photo `cover_photo`.</caption>
		 * function my_um_profile_dynamic_meta_image_size( $image_size, $user_id ) {
		 *     return 'original';
		 * }
		 * add_filter( 'um_profile_dynamic_meta_image_size', 'my_um_profile_dynamic_meta_image_size', 10, 2 );
		 */
		$image_size = apply_filters( 'um_profile_dynamic_meta_image_size', 190, $user_id );
		/**
		 * Filters the profile SEO image type. Default 'profile_photo'. Available 'cover_photo', 'profile_photo'.
		 *
		 * @param {string} $image_type Image type - cover_photo or profile_photo.
		 * @param {int}    $user_id    User ID.
		 *
		 * @return {string} Changed image type
		 *
		 * @since 2.5.5
		 * @hook um_profile_dynamic_meta_image_type
		 *
		 * @example <caption>Change meta image to cover photo `cover_photo`.</caption>
		 * function my_um_profile_dynamic_meta_image_type( $image_type, $user_id ) {
		 *     return 'cover_photo';
		 * }
		 * add_filter( 'um_profile_dynamic_meta_image_type', 'my_um_profile_dynamic_meta_image_type', 10, 2 );
		 */
		$image_type = apply_filters( 'um_profile_dynamic_meta_image_type', 'profile_photo', $user_id );

		if ( 'cover_photo' === $image_type ) {
			if ( is_numeric( $image_size ) ) {
				$sizes = UM()->options()->get( 'cover_thumb_sizes' );
				if ( is_array( $sizes ) ) {
					$image_size = um_closest_num( $sizes, $image_size );
				}
				$image = um_get_cover_uri( um_profile( 'cover_photo' ), $image_size );
			} else {
				$image = um_get_cover_uri( um_profile( 'cover_photo' ), null );
			}
		} elseif ( is_numeric( $image_size ) ) {
			$sizes = UM()->options()->get( 'photo_thumb_sizes' );
			if ( is_array( $sizes ) ) {
				$image_size = um_closest_num( $sizes, $image_size );
			}
			$image = um_get_user_avatar_url( $user_id, $image_size );
		} else {
			$image = um_get_user_avatar_url( $user_id, 'original' );
		}

		$image      = current( explode( '?', $image ) ); // strip $_GET attributes from photo URL.
		$image_url  = wp_parse_url( $image );
		$image_name = explode( '/', $image_url['path'] );
		$image_name = end( $image_name );
		$image_info = wp_check_filetype( $image_name );

		$person = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'ProfilePage',
			'dateCreated'  => um_user( 'user_registered' ),
			'dateModified' => gmdate( 'Y-m-d H:i:s', um_user( 'last_update' ) ),
			'mainEntity'   => array(
				'@type'         => 'Person',
				'name'          => esc_attr( $title ),
				'alternateName' => um_user( 'user_login' ),
				'description'   => esc_attr( stripslashes( $description ) ),
				'image'         => esc_url( $image ),
				'sameAs'        => array(
					$url,
				),
			),
		);
		/**
		 * Filters changing the schema.org of profile's person.
		 *
		 * @param {array} $person  Data of the profile person.
		 * @param {int}   $user_id User ID.
		 *
		 * @return {array} Changed person's data.
		 *
		 * @since 2.8.7
		 * @hook um_profile_dynamic_meta_profile_schema
		 *
		 * @example <caption>Change name of person.</caption>
		 * function my_um_profile_dynamic_meta_profile_schema( $core_search_fields ) {
		 *     $person['mainEntity']['name'] = 'John Doe';
		 * }
		 * add_filter( 'um_profile_dynamic_meta_profile_schema', 'my_um_profile_dynamic_meta_profile_schema' );
		 */
		$person = apply_filters( 'um_profile_dynamic_meta_profile_schema', $person, $user_id );

		um_reset_user();
		?>
		<!-- START - Ultimate Member profile SEO meta tags -->

		<link rel="image_src" href="<?php echo esc_url( $image ); ?>"/>

		<meta name="description" content="<?php echo esc_attr( $description ); ?>"/>

		<meta property="og:type" content="profile"/>
		<meta property="og:locale" content="<?php echo esc_attr( $locale ); ?>"/>
		<meta property="og:site_name" content="<?php echo esc_attr( $site_name ); ?>"/>
		<meta property="og:title" content="<?php echo esc_attr( $title ); ?>"/>
		<meta property="og:description" content="<?php echo esc_attr( $description ); ?>"/>
		<meta property="og:image" content="<?php echo esc_url( $image ); ?>"/>
		<meta property="og:image:alt" content="<?php esc_attr_e( 'Profile photo', 'ultimate-member' ); ?>"/>
		<?php if ( is_numeric( $image_size ) ) { ?>
			<meta property="og:image:height" content="<?php echo absint( $image_size ); ?>"/>
			<meta property="og:image:width" content="<?php echo absint( $image_size ); ?>"/>
		<?php } ?>
		<?php if ( is_ssl() ) { ?>
			<meta property="og:image:secure_url" content="<?php echo esc_url( $image ); ?>"/>
		<?php } ?>
		<?php if ( $image_info['type'] ) { ?>
			<meta property="og:image:type" content="<?php echo esc_attr( $image_info['type'] ); ?>" />
		<?php } ?>
		<meta property="og:url" content="<?php echo esc_url( $url ); ?>"/>

		<meta name="twitter:card" content="summary"/>
		<?php if ( $twitter ) { ?>
			<meta name="twitter:site" content="@<?php echo esc_attr( $twitter ); ?>"/>
		<?php } ?>
		<meta name="twitter:title" content="<?php echo esc_attr( $title ); ?>"/>
		<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>"/>
		<meta name="twitter:image" content="<?php echo esc_url( $image ); ?>"/>
		<meta name="twitter:image:alt" content="<?php esc_attr_e( 'Profile photo', 'ultimate-member' ); ?>"/>
		<meta name="twitter:url" content="<?php echo esc_url( $url ); ?>"/>

		<script type="application/ld+json"><?php echo wp_json_encode( $person ); ?></script>

		<!-- END - Ultimate Member profile SEO meta tags -->
		<?php
	}
}
add_action( 'wp_head', 'um_profile_dynamic_meta_desc', 20 );

/**
 * Profile header cover
 *
 * @param $args
 */
function um_profile_header_cover_area( $args ) {
	if ( isset( $args['cover_enabled'] ) && $args['cover_enabled'] == 1 ) {

		$default_cover = UM()->options()->get( 'default_cover' );

		$overlay = '<span class="um-cover-overlay">
				<span class="um-cover-overlay-s">
					<ins>
						<i class="um-faicon-picture-o"></i>
						<span class="um-cover-overlay-t">' . __( 'Change your cover photo', 'ultimate-member' ) . '</span>
					</ins>
				</span>
			</span>';

		?>

		<div class="um-cover <?php if ( um_user( 'cover_photo' ) || ( $default_cover && $default_cover['url'] ) ) echo 'has-cover'; ?>"
			 data-user_id="<?php echo esc_attr( um_profile_id() ); ?>" data-ratio="<?php echo esc_attr( $args['cover_ratio'] ); ?>">

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_cover_area_content
			 * @description Cover area content change
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_cover_area_content', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_cover_area_content', 'my_cover_area_content', 10, 1 );
			 * function my_cover_area_content( $user_id ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_cover_area_content', um_profile_id() );
			if ( true === UM()->fields()->editing ) {

				$hide_remove = um_user( 'cover_photo' ) ? false : ' style="display:none;"';

				$text = ! um_user( 'cover_photo' ) ? __( 'Upload a cover photo', 'ultimate-member' ) : __( 'Change cover photo', 'ultimate-member' ) ;

				$items = array(
					'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-cover" data-child=".um-btn-auto-width">' . $text . '</a>',
					'<a href="javascript:void(0);" class="um-reset-cover-photo" data-user_id="' . um_profile_id() . '" ' . $hide_remove . '>' . __( 'Remove', 'ultimate-member' ) . '</a>',
					'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
				);

				$items = apply_filters( 'um_cover_area_content_dropdown_items', $items, um_profile_id() );

				UM()->profile()->new_ui( 'bc', 'div.um-cover', 'click', $items );
			} else {

				if ( ! isset( UM()->user()->cannot_edit ) && ! um_user( 'cover_photo' ) ) {

					$items = array(
						'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-cover" data-child=".um-btn-auto-width">' . __( 'Upload a cover photo', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
					);

					$items = apply_filters( 'um_cover_area_content_dropdown_items', $items, um_profile_id() );

					UM()->profile()->new_ui( 'bc', 'div.um-cover', 'click', $items );

				}

			}

			UM()->fields()->add_hidden_field( 'cover_photo' ); ?>

			<div class="um-cover-e" data-ratio="<?php echo esc_attr( $args['cover_ratio'] ); ?>">

				<?php if ( um_user( 'cover_photo' ) ) {

					$get_cover_size = $args['coversize'];

					if ( ! $get_cover_size || $get_cover_size == 'original' ) {
						$size = null;
					} else {
						$size = $get_cover_size;
					}

					if ( UM()->mobile()->isMobile() ) {

						// set for mobile width = 300 by default but can be changed via filter
						if ( ! UM()->mobile()->isTablet() ) {
							$size = 300;
						}

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_mobile_cover_photo
						 * @description Add size for mobile device
						 * @input_vars
						 * [{"var":"$size","type":"int","desc":"Form's agrument - Cover Photo size"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage
						 * <?php add_filter( 'um_mobile_cover_photo', 'change_size', 10, 1 ); ?>
						 * @example
						 * <?php
						 * add_filter( 'um_mobile_cover_photo', 'um_change_cover_mobile_size', 10, 1 );
						 * function um_change_cover_mobile_size( $size ) {
						 *     // your code here
						 *     return $size;
						 * }
						 * ?>
						 */
						$size = apply_filters( 'um_mobile_cover_photo', $size );
					}

					echo um_user( 'cover_photo', $size );

				} elseif ( $default_cover && $default_cover['url'] ) {

					$default_cover = $default_cover['url'];

					echo '<img src="' . esc_url( $default_cover ) . '" alt="" />';

				} else {

					if ( ! isset( UM()->user()->cannot_edit ) ) { ?>

						<a href="javascript:void(0);" class="um-cover-add"><span class="um-cover-add-i"><i
									class="um-icon-plus um-tip-n"
									title="<?php esc_attr_e( 'Upload a cover photo', 'ultimate-member' ); ?>"></i></span></a>

					<?php }

				} ?>

			</div>

			<?php echo $overlay; ?>

		</div>

		<?php

	}

}
add_action( 'um_profile_header_cover_area', 'um_profile_header_cover_area', 9 );


/**
 * Show social links as icons below profile name
 *
 * @param $args
 */
function um_social_links_icons( $args ) {
	if ( ! empty( $args['show_social_links'] ) ) {

		echo '<div class="um-profile-connect um-member-connect">';
		UM()->fields()->show_social_urls();
		echo '</div>';

	}
}
add_action( 'um_after_profile_header_name_args', 'um_social_links_icons', 50 );


/**
 * Profile header
 *
 * @param $args
 */
function um_profile_header( $args ) {
	$classes = null;

	if ( ! $args['cover_enabled'] ) {
		$classes .= ' no-cover';
	}

	$default_size = str_replace( 'px', '', $args['photosize'] );

	// Switch on/off the profile photo uploader
	$disable_photo_uploader = empty( $args['use_custom_settings'] ) ? UM()->options()->get( 'disable_profile_photo_upload' ) : $args['disable_photo_upload'];

	if ( ! empty( $disable_photo_uploader ) ) {
		$args['disable_photo_upload'] = 1;
		$overlay = '';
	} else {
		$overlay = '<span class="um-profile-photo-overlay">
			<span class="um-profile-photo-overlay-s">
				<ins>
					<i class="um-faicon-camera"></i>
				</ins>
			</span>
		</span>';
	} ?>

	<div class="um-header<?php echo esc_attr( $classes ); ?>">

		<?php
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_pre_header_editprofile
		 * @description Insert some content before edit profile header
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_pre_header_editprofile', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_pre_header_editprofile', 'my_pre_header_editprofile', 10, 1 );
		 * function my_pre_header_editprofile( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_pre_header_editprofile', $args ); ?>

		<div class="um-profile-photo" data-user_id="<?php echo esc_attr( um_profile_id() ); ?>" <?php echo wp_kses( UM()->fields()->aria_valid_attributes( UM()->fields()->is_error( 'profile_photo' ), 'profile_photo' ), UM()->get_allowed_html( 'templates' ) ); ?>>

			<a href="<?php echo esc_url( um_user_profile_url() ); ?>" class="um-profile-photo-img" title="<?php echo esc_attr( um_user( 'display_name' ) ); ?>">
				<?php if ( ! $default_size || $default_size == 'original' ) {
					$profile_photo = UM()->uploader()->get_upload_base_url() . um_user( 'ID' ) . "/" . um_profile( 'profile_photo' );

					$data = um_get_user_avatar_data( um_user( 'ID' ) );
					echo $overlay . sprintf( '<img src="%s" class="%s" alt="%s" data-default="%s" onerror="%s" />',
						esc_url( $profile_photo ),
						esc_attr( $data['class'] ),
						esc_attr( $data['alt'] ),
						esc_attr( $data['default'] ),
						'if ( ! this.getAttribute(\'data-load-error\') ){ this.setAttribute(\'data-load-error\', \'1\');this.setAttribute(\'src\', this.getAttribute(\'data-default\'));}'
					);
				} else {
					echo $overlay . get_avatar( um_user( 'ID' ), $default_size );
				} ?>
			</a>

			<?php if ( empty( $disable_photo_uploader ) && empty( UM()->user()->cannot_edit ) ) {

				UM()->fields()->add_hidden_field( 'profile_photo' );

				if ( ! um_profile( 'profile_photo' ) ) { // has profile photo

					$items = array(
						'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . __( 'Upload photo', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
					);

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_user_photo_menu_view
					 * @description Change user photo on menu view
					 * @input_vars
					 * [{"var":"$items","type":"array","desc":"User Photos"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_user_photo_menu_view', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_user_photo_menu_view', 'my_user_photo_menu_view', 10, 1 );
					 * function my_user_photo_menu_view( $items ) {
					 *     // your code here
					 *     return $items;
					 * }
					 * ?>
					 */
					$items = apply_filters( 'um_user_photo_menu_view', $items );

					UM()->profile()->new_ui( 'bc', 'div.um-profile-photo', 'click', $items );

				} elseif ( true === UM()->fields()->editing ) {

					$items = array(
						'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . __( 'Change photo', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-reset-profile-photo" data-user_id="' . esc_attr( um_profile_id() ) . '" data-default_src="' . esc_url( um_get_default_avatar_uri() ) . '">' . __( 'Remove photo', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
					);

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_user_photo_menu_edit
					 * @description Change user photo on menu edit
					 * @input_vars
					 * [{"var":"$items","type":"array","desc":"User Photos"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_user_photo_menu_edit', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_user_photo_menu_edit', 'my_user_photo_menu_edit', 10, 1 );
					 * function my_user_photo_menu_edit( $items ) {
					 *     // your code here
					 *     return $items;
					 * }
					 * ?>
					 */
					$items = apply_filters( 'um_user_photo_menu_edit', $items );

					UM()->profile()->new_ui( 'bc', 'div.um-profile-photo', 'click', $items );

				}

			} ?>

		</div>

		<div class="um-profile-meta">

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_before_profile_main_meta
			 * @description Insert before profile main meta block
			 * @input_vars
			 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
			 * @change_log
			 * ["Since: 2.0.1"]
			 * @usage add_action( 'um_before_profile_main_meta', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_action( 'um_before_profile_main_meta', 'my_before_profile_main_meta', 10, 1 );
			 * function my_before_profile_main_meta( $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_before_profile_main_meta', $args ); ?>

			<div class="um-main-meta">

				<?php if ( $args['show_name'] ) { ?>
					<div class="um-name">

						<a href="<?php echo esc_url( um_user_profile_url() ); ?>"
						   title="<?php echo esc_attr( um_user( 'display_name' ) ); ?>"><?php echo um_user( 'display_name', 'html' ); ?></a>

						<?php
						/**
						 * UM hook
						 *
						 * @type action
						 * @title um_after_profile_name_inline
						 * @description Insert after profile name some content
						 * @input_vars
						 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_action( 'um_after_profile_name_inline', 'function_name', 10, 1 );
						 * @example
						 * <?php
						 * add_action( 'um_after_profile_name_inline', 'my_after_profile_name_inline', 10, 1 );
						 * function my_after_profile_name_inline( $args ) {
						 *     // your code here
						 * }
						 * ?>
						 */
						do_action( 'um_after_profile_name_inline', $args ); ?>

					</div>
				<?php } ?>

				<div class="um-clear"></div>

				<?php
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_profile_header_name_args
				 * @description Insert after profile header name some content
				 * @input_vars
				 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_after_profile_header_name_args', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_action( 'um_after_profile_header_name_args', 'my_after_profile_header_name_args', 10, 1 );
				 * function my_after_profile_header_name_args( $args ) {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_profile_header_name_args', $args );
				/**
				 * UM hook
				 *
				 * @type action
				 * @title um_after_profile_name_inline
				 * @description Insert after profile name some content
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_action( 'um_after_profile_name_inline', 'function_name', 10 );
				 * @example
				 * <?php
				 * add_action( 'um_after_profile_name_inline', 'my_after_profile_name_inline', 10 );
				 * function my_after_profile_name_inline() {
				 *     // your code here
				 * }
				 * ?>
				 */
				do_action( 'um_after_profile_header_name' ); ?>

			</div>

			<?php if ( ! empty( $args['metafields'] ) ) { ?>
				<div class="um-meta">
					<?php echo UM()->profile()->show_meta( $args['metafields'], $args ); ?>
				</div>
				<?php
			}

			$show_bio       = false;
			$bio_html       = false;
			$global_setting = UM()->options()->get( 'profile_show_html_bio' );
			if ( ! empty( $args['use_custom_settings'] ) ) {
				if ( ! empty( $args['show_bio'] ) ) {
					$show_bio = true;
					$bio_html = ! empty( $global_setting );
				}
			} else {
				$global_show_bio = UM()->options()->get( 'profile_show_bio' );
				if ( ! empty( $global_show_bio ) ) {
					$show_bio = true;
					$bio_html = ! empty( $global_setting );
				}
			}

			if ( $show_bio ) {
				$description_key = UM()->profile()->get_show_bio_key( $args );

				if ( true === UM()->fields()->viewing && um_user( $description_key ) ) {
					?>
					<div class="um-meta-text">
						<?php
						$description = get_user_meta( um_user( 'ID' ), $description_key, true );

						if ( $bio_html ) {
							echo wp_kses_post( nl2br( make_clickable( wpautop( $description ) ) ) );
						} else {
							echo nl2br( esc_html( $description ) );
						}
						?>
					</div>
					<?php
				} elseif ( true === UM()->fields()->editing ) {
					if ( ! empty( $args['custom_fields'][ $description_key ] ) ) {
						if ( ! empty( $args['custom_fields'][ $description_key ]['html'] ) && $bio_html ) {
							$description_value = UM()->fields()->field_value( $description_key );
						} else {
							$description_value = wp_strip_all_tags( UM()->fields()->field_value( $description_key ) );
						}
					} else {
						if ( $bio_html ) {
							$description_value = UM()->fields()->field_value( $description_key );
						} else {
							$description_value = wp_strip_all_tags( UM()->fields()->field_value( $description_key ) );
						}
					}

					if ( ! empty( $args['custom_fields'][ $description_key ]['max_chars'] ) ) {
						$limit = $args['custom_fields'][ $description_key ]['max_chars'];
					} else {
						$limit = UM()->options()->get( 'profile_bio_maxchars' );
					}
					?>

					<div class="um-meta-text">
						<textarea id="um-meta-bio" data-html="<?php echo esc_attr( $bio_html ); ?>"
								data-character-limit="<?php echo esc_attr( $limit ); ?>"
								placeholder="<?php esc_attr_e( 'Tell us a bit about yourself...', 'ultimate-member' ); ?>"
								name="<?php echo esc_attr( $description_key ); ?>" <?php echo wp_kses( UM()->fields()->aria_valid_attributes( UM()->fields()->is_error( $description_key ), 'um-meta-bio' ), UM()->get_allowed_html( 'templates' ) ); ?>><?php echo esc_textarea( $description_value ); ?></textarea>
						<span class="um-meta-bio-character um-right">
							<span class="um-bio-limit"><?php echo esc_html( $limit ); ?></span>
						</span>
						<?php
						if ( UM()->fields()->is_error( $description_key ) ) {
							echo wp_kses( UM()->fields()->field_error( UM()->fields()->show_error( $description_key ), 'um-meta-bio', true ), UM()->get_allowed_html( 'templates' ) );
						}
						?>
					</div>
					<?php
				}
			}
			?>

			<div class="um-profile-status <?php echo esc_attr( UM()->common()->users()->get_status( um_user( 'ID' ) ) ); ?>">
				<span>
					<?php
					// translators: %s: profile status.
					echo esc_html( sprintf( __( 'This user account status is %s', 'ultimate-member' ), UM()->common()->users()->get_status( um_user( 'ID' ), 'formatted' ) ) );
					?>
				</span>
			</div>

			<?php
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_after_header_meta
			 * @description Insert after header meta some content
			 * @input_vars
			 * [{"var":"$user_id","type":"int","desc":"User ID"},
			 * {"var":"$args","type":"array","desc":"Form Arguments"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_after_header_meta', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_action( 'um_after_header_meta', 'my_after_header_meta', 10, 2 );
			 * function my_after_header_meta( $user_id, $args ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_after_header_meta', um_user( 'ID' ), $args );
			?>
		</div>
		<div class="um-clear"></div>

		<?php
		if ( UM()->fields()->is_error( 'profile_photo' ) ) {
			echo wp_kses( UM()->fields()->field_error( UM()->fields()->show_error( 'profile_photo' ), 'profile_photo', true ), UM()->get_allowed_html( 'templates' ) );
		}

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_after_header_info
		 * @description Insert after header info some content
		 * @input_vars
		 * [{"var":"$user_id","type":"int","desc":"User ID"},
		 * {"var":"$args","type":"array","desc":"Form Arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_after_header_info', 'function_name', 10, 2 );
		 * @example
		 * <?php
		 * add_action( 'um_after_header_info', 'my_after_header_info', 10, 2 );
		 * function my_after_header_info( $user_id, $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_after_header_info', um_user( 'ID' ), $args ); ?>

	</div>

	<?php
}
add_action( 'um_profile_header', 'um_profile_header', 9 );

/**
 * Adds profile permissions to view/edit.
 *
 * @param array $args
 */
function um_pre_profile_shortcode( $args ) {
	// It handles only UM Profile forms.
	if ( ! array_key_exists( 'mode', $args ) || 'profile' !== $args['mode'] ) {
		return;
	}

	// disable for the REST API requests.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	if ( true === UM()->fields()->editing ) {
		if ( um_get_requested_user() ) {
			if ( ! UM()->roles()->um_current_user_can( 'edit', um_get_requested_user() ) ) {
				um_redirect_home( um_get_requested_user(), um_is_myprofile() );
			}
			um_fetch_user( um_get_requested_user() );
		}
	} else {
		UM()->fields()->viewing = true;

		if ( um_get_requested_user() ) {
			if ( ! um_is_myprofile() && ! um_can_view_profile( um_get_requested_user() ) ) {
				um_redirect_home( um_get_requested_user(), um_is_myprofile() );
			}

			if ( ! UM()->roles()->um_current_user_can( 'edit', um_get_requested_user() ) ) {
				UM()->user()->cannot_edit = 1;
			}

			um_fetch_user( um_get_requested_user() );
		} else {
			if ( ! is_user_logged_in() ) {
				um_redirect_home( um_get_requested_user(), um_is_myprofile() );
			}

			if ( ! um_user( 'can_edit_profile' ) ) {
				UM()->user()->cannot_edit = 1;
			}
		}
	}

	UM()->fields()->set_mode = 'profile';
}
add_action( 'um_pre_profile_shortcode', 'um_pre_profile_shortcode' );

/**
 * Display the edit profile icon
 *
 * @param $args
 */
function um_add_edit_icon( $args ) {
	if ( ! is_user_logged_in() ) {
		// not allowed for guests
		return;
	}

	// do not proceed if user cannot edit

	if ( true === UM()->fields()->editing ) { ?>

		<div class="um-profile-edit um-profile-headericon">
			<a href="javascript:void(0);" class="um-profile-edit-a um-profile-save"><i class="um-faicon-check"></i></a>
		</div>

		<?php return;
	}

	if ( ! um_is_myprofile() ) {

		if ( ! UM()->roles()->um_current_user_can( 'edit', um_profile_id() ) && ! UM()->roles()->um_current_user_can( 'delete', um_profile_id() ) ) {
			return;
		}

		$items = UM()->user()->get_admin_actions();
		if ( UM()->roles()->um_current_user_can( 'edit', um_profile_id() ) ) {
			$items['editprofile'] = '<a href="' . esc_url( um_edit_profile_url() ) . '" class="real_url">' . __( 'Edit Profile', 'ultimate-member' ) . '</a>';
		}

		/**
		* UM hook
		*
		* @type filter
		* @title um_profile_edit_menu_items
		* @description Edit menu items on profile page
		* @input_vars
		* [{"var":"$items","type":"array","desc":"User Menu"},
		* {"var":"$user_id","type":"int","desc":"Profile ID"}]
		* @change_log
		* ["Since: 2.0"]
		* @usage
		* <?php add_filter( 'um_profile_edit_menu_items', 'function_name', 10, 2 ); ?>
		* @example
		* <?php
		* add_filter( 'um_profile_edit_menu_items', 'my_profile_edit_menu_items', 10, 2 );
		* function my_profile_edit_menu_items( $items, $user_id ) {
		*     // your code here
		*     return $items;
		* }
		* ?>
		*/
		$items = apply_filters( 'um_profile_edit_menu_items', $items, um_profile_id() );

		$items['cancel'] = '<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>';

	} else {
		$items = array(
			'editprofile' => '<a href="' . esc_url( um_edit_profile_url() ) . '" class="real_url">' . __( 'Edit Profile', 'ultimate-member' ) . '</a>',
			'myaccount'   => '<a href="' . esc_url( um_get_core_page( 'account' ) ) . '" class="real_url">' . __( 'My Account', 'ultimate-member' ) . '</a>',
			'logout'      => '<a href="' . esc_url( um_get_core_page( 'logout' ) ) . '" class="real_url">' . __( 'Logout', 'ultimate-member' ) . '</a>',
			'cancel'      => '<a href="javascript:void(0);" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
		);

		if ( ! empty( UM()->user()->cannot_edit ) ) {
			unset( $items['editprofile'] );
		}

		/**
		* UM hook
		*
		* @type filter
		* @title um_myprofile_edit_menu_items
		* @description Edit menu items on my profile page
		* @input_vars
		* [{"var":"$items","type":"array","desc":"User Menu"}]
		* @change_log
		* ["Since: 2.0"]
		* @usage
		* <?php add_filter( 'um_myprofile_edit_menu_items', 'function_name', 10, 1 ); ?>
		* @example
		* <?php
		* add_filter( 'um_myprofile_edit_menu_items', 'my_myprofile_edit_menu_items', 10, 1 );
		* function my_myprofile_edit_menu_items( $items ) {
		*     // your code here
		*     return $items;
		* }
		* ?>
		*/
		$items = apply_filters( 'um_myprofile_edit_menu_items', $items );
	} ?>

	<div class="um-profile-edit um-profile-headericon">

		<a href="javascript:void(0);" class="um-profile-edit-a"><i class="um-faicon-cog"></i></a>

		<?php UM()->profile()->new_ui( $args['header_menu'], 'div.um-profile-edit', 'click', $items ); ?>

	</div>

	<?php
}
add_action( 'um_pre_header_editprofile', 'um_add_edit_icon' );


/**
 * Show Fields
 *
 * @param $args
 */
function um_add_profile_fields( $args ) {
	if ( true === UM()->fields()->editing ) {

		echo UM()->fields()->display( 'profile', $args );

	} else {

		UM()->fields()->viewing = true;

		echo UM()->fields()->display_view( 'profile', $args );

	}

}
add_action( 'um_main_profile_fields', 'um_add_profile_fields', 100 );


/**
 * Form processing
 *
 * @param array $args
 * @param array $form_data
 */
function um_submit_form_profile( $args, $form_data ) {
	if ( isset( UM()->form()->errors ) ) {
		return;
	}

	UM()->fields()->set_mode = 'profile';
	UM()->fields()->editing  = true;

	if ( ! empty( $args['submitted'] ) ) {
		$args['submitted'] = UM()->form()->clean_submitted_data( $args['submitted'] );
	}

	/**
	 * Fires on successful submit profile form.
	 *
	 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
	 * * 10 - `um_user_edit_profile()` Profile form main handler.
	 *
	 * @since 1.3.x
	 * @hook um_user_edit_profile
	 *
	 * @param {array} $post      $_POST Submission array.
	 * @param {array} $form_data UM form data. Since 2.6.7
	 *
	 * @example <caption>Make any custom action on successful submit profile form.</caption>
	 * function my_user_edit_profile( $post, $form_data ) {
	 *     // your code here
	 * }
	 * // Don't use priority >= 10 because there is native Ultimate Member handler on it.
	 * add_action( 'um_user_edit_profile', 'my_user_edit_profile', 9, 2 );
	 */
	do_action( 'um_user_edit_profile', $args, $form_data );
}
add_action( 'um_submit_form_profile', 'um_submit_form_profile', 10, 2 );

/**
 * Show the submit button (highest priority)
 *
 * @param $args
 */
function um_add_submit_button_to_profile( $args ) {
	// DO NOT add when reviewing user's details
	if ( UM()->user()->preview == true && is_admin() ) {
		return;
	}

	// only when editing
	if ( false === UM()->fields()->editing ) {
		return;
	}

	if ( ! isset( $args['primary_btn_word'] ) || $args['primary_btn_word'] == '' ){
		$args['primary_btn_word'] = UM()->options()->get( 'profile_primary_btn_word' );
	}
	if ( ! isset( $args['secondary_btn_word'] ) || $args['secondary_btn_word'] == '' ){
		$args['secondary_btn_word'] = UM()->options()->get( 'profile_secondary_btn_word' );
	} ?>

	<div class="um-col-alt">

		<?php if ( ! empty( $args['secondary_btn'] ) ) { ?>

			<div class="um-left um-half">
				<input type="submit" value="<?php esc_attr_e( wp_unslash( $args['primary_btn_word'] ), 'ultimate-member' ); ?>" class="um-button" />
			</div>
			<div class="um-right um-half">
				<a href="<?php echo esc_url( um_edit_my_profile_cancel_uri() ); ?>" class="um-button um-alt">
					<?php _e( wp_unslash( $args['secondary_btn_word'] ), 'ultimate-member' ); ?>
				</a>
			</div>

		<?php } else { ?>

			<div class="um-center">
				<input type="submit" value="<?php esc_attr_e( wp_unslash( $args['primary_btn_word'] ), 'ultimate-member' ); ?>" class="um-button" />
			</div>

		<?php } ?>

		<div class="um-clear"></div>

	</div>

	<?php
}
add_action( 'um_after_profile_fields', 'um_add_submit_button_to_profile', 1000 );


/**
 * Display the available profile tabs
 *
 * @param array $args
 */
function um_profile_menu( $args ) {
	if ( ! UM()->options()->get( 'profile_menu' ) ) {
		return;
	}

	// get active tabs
	$tabs = UM()->profile()->tabs_active();

	$all_tabs = $tabs;

	$tabs = array_filter( $tabs, function( $item ) {
		if ( ! empty( $item['hidden'] ) ) {
			return false;
		}
		return true;
	});

	$active_tab = UM()->profile()->active_tab();
	//check here tabs with hidden also, to make correct check of active tab
	if ( ! isset( $all_tabs[ $active_tab ] ) || um_is_on_edit_profile() ) {
		$active_tab = 'main';
		UM()->profile()->active_tab = $active_tab;
		UM()->profile()->active_subnav = null;
	}

	$has_subnav = false;
	if ( count( $tabs ) == 1 ) {
		foreach ( $tabs as $tab ) {
			if ( isset( $tab['subnav'] ) ) {
				$has_subnav = true;
			}
		}
	}

	// need enough tabs to continue
	if ( count( $tabs ) <= 1 && ! $has_subnav && count( $all_tabs ) === count( $tabs ) ) {
		return;
	}

	if ( count( $tabs ) > 1 || count( $all_tabs ) > count( $tabs ) ) {
		// Move default tab priority
		$default_tab = UM()->options()->get( 'profile_menu_default_tab' );
		$dtab = ( isset( $tabs[ $default_tab ] ) ) ? $tabs[ $default_tab ] : 'main';
		if ( isset( $tabs[ $default_tab ] ) ) {
			unset( $tabs[ $default_tab ] );
			$dtabs[ $default_tab ] = $dtab;
			$tabs = $dtabs + $tabs;
		}

		if ( ! empty( $tabs ) ) { ?>

			<div class="um-profile-nav">

				<?php foreach ( $tabs as $id => $tab ) {

					$nav_link = UM()->permalinks()->get_current_url( UM()->is_permalinks );
					$nav_link = remove_query_arg( 'um_action', $nav_link );
					$nav_link = remove_query_arg( 'subnav', $nav_link );
					$nav_link = add_query_arg( 'profiletab', $id, $nav_link );

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_profile_menu_link_{$id}
					 * @description Change profile menu link by tab $id
					 * @input_vars
					 * [{"var":"$nav_link","type":"string","desc":"Profile Tab Link"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_profile_menu_link_{$id}', 'function_name', 10, 1 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_profile_menu_link_{$id}', 'my_profile_menu_link', 10, 1 );
					 * function my_profile_menu_link( $nav_link ) {
					 *     // your code here
					 *     return $nav_link;
					 * }
					 * ?>
					 */
					$nav_link = apply_filters( "um_profile_menu_link_{$id}", $nav_link );

					/**
					 * Filters a profile menu navigation links' tag attributes.
					 *
					 * @since 2.6.3
					 * @hook um_profile_menu_link_{$id}_attrs
					 *
					 * @param {string} $profile_nav_attrs Link's tag attributes.
					 * @param {array}  $args              Profile form arguments.
					 *
					 * @return {string} Link's tag attributes.
					 *
					 * @example <caption>Add a link's tag attributes.</caption>
					 * function um_profile_menu_link_attrs( $profile_nav_attrs ) {
					 *     // your code here
					 *     return $profile_nav_attrs;
					 * }
					 * add_filter( 'um_profile_menu_link_{$id}_attrs', 'um_profile_menu_link_attrs', 10, 1 );
					 */
					$profile_nav_attrs = apply_filters( "um_profile_menu_link_{$id}_attrs", '', $args );

					$profile_nav_class = '';
					if ( ! UM()->options()->get( 'profile_menu_icons' ) ) {
						$profile_nav_class .= ' without-icon';
					}

					if ( $id == $active_tab ) {
						$profile_nav_class .= ' active';
					} ?>

					<div class="um-profile-nav-item um-profile-nav-<?php echo esc_attr( $id . ' ' . $profile_nav_class ); ?>">
						<?php if ( UM()->options()->get( 'profile_menu_icons' ) ) { ?>
							<a href="<?php echo esc_url( $nav_link ); ?>" class="uimob800-show uimob500-show uimob340-show um-tip-n"
							   title="<?php echo esc_attr( $tab['name'] ); ?>" original-title="<?php echo esc_attr( $tab['name'] ); ?>" <?php echo esc_attr( $profile_nav_attrs ); ?>>

								<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>

								<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
									<span class="um-tab-notifier uimob800-show uimob500-show uimob340-show"><?php echo $tab['notifier']; ?></span>
								<?php } ?>

								<span class="uimob800-hide uimob500-hide uimob340-hide title"><?php echo esc_html( $tab['name'] ); ?></span>
							</a>
							<a href="<?php echo esc_url( $nav_link ); ?>" class="uimob800-hide uimob500-hide uimob340-hide"
							   title="<?php echo esc_attr( $tab['name'] ); ?>" <?php echo esc_attr( $profile_nav_attrs ); ?>>

								<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>

								<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
									<span class="um-tab-notifier"><?php echo $tab['notifier']; ?></span>
								<?php } ?>

								<span class="title"><?php echo esc_html( $tab['name'] ); ?></span>
							</a>
						<?php } else { ?>
							<a href="<?php echo esc_url( $nav_link ); ?>" class="uimob800-show uimob500-show uimob340-show um-tip-n"
							   title="<?php echo esc_attr( $tab['name'] ); ?>" original-title="<?php echo esc_attr( $tab['name'] ); ?>" <?php echo esc_attr( $profile_nav_attrs ); ?>>

								<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>

								<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
									<span class="um-tab-notifier uimob800-show uimob500-show uimob340-show"><?php echo $tab['notifier']; ?></span>
								<?php } ?>
							</a>
							<a href="<?php echo esc_url( $nav_link ); ?>" class="uimob800-hide uimob500-hide uimob340-hide"
							   title="<?php echo esc_attr( $tab['name'] ); ?>" <?php echo esc_attr( $profile_nav_attrs ); ?>>

								<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
									<span class="um-tab-notifier"><?php echo $tab['notifier']; ?></span>
								<?php } ?>

								<span class="title"><?php echo esc_html( $tab['name'] ); ?></span>
							</a>
						<?php } ?>
					</div>

				<?php } ?>

				<div class="um-clear"></div>

			</div>

		<?php }
	}

	foreach ( $tabs as $id => $tab ) {

		if ( isset( $tab['subnav'] ) && $active_tab == $id ) {

			$active_subnav = ( UM()->profile()->active_subnav() ) ? UM()->profile()->active_subnav() : $tab['subnav_default']; ?>

			<div class="um-profile-subnav">
				<?php foreach ( $tab['subnav'] as $id_s => $subtab ) {

					$subnav_link = add_query_arg( 'subnav', $id_s );
					$subnav_link = apply_filters( 'um_user_profile_subnav_link', $subnav_link, $id_s, $subtab ); ?>

					<a href="<?php echo esc_url( $subnav_link ); ?>" class="<?php echo $active_subnav == $id_s ? 'active' : ''; ?>">
						<?php echo $subtab; ?>
					</a>

				<?php } ?>
			</div>
		<?php }

	}

}
add_action( 'um_profile_menu', 'um_profile_menu', 9 );
