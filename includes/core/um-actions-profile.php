<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Um_profile_content_{main_tab}
 *
 * @param $args
 */
function um_profile_content_main( $args ) {

	/**
	 * @var $mode
	 */
	extract( $args );

	if ( ! UM()->options()->get( 'profile_tab_main' ) && ! isset( $_REQUEST['um_action'] ) ) {
		return;
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_profile_can_view_main
	 * @description Check user can view profile
	 * @input_vars
	 * [{"var":"$view","type":"bool","desc":"Can view?"},
	 * {"var":"$user_id","type":"int","desc":"User profile ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_profile_can_view_main', 'function_name', 10, 2 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_profile_can_view_main', 'my_profile_can_view_main', 10, 2 );
	 * function my_profile_can_view_main( $view, $user_id ) {
	 *     // your code here
	 *     return $view;
	 * }
	 * ?>
	 */
	$can_view = apply_filters( 'um_profile_can_view_main', -1, um_profile_id() );

	if ( $can_view == -1 ) {
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_before_form
		 * @description Some actions before profile form
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_before_form', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_before_form', 'my_before_form', 10, 1 );
		 * function my_before_form( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_before_form', $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_before_{$mode}_fields
		 * @description Some actions before profile form fields
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"{Profile} form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_before_{$mode}_fields', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_before_{$mode}_fields', 'my_before_fields', 10, 1 );
		 * function my_before_form( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( "um_before_{$mode}_fields", $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_main_{$mode}_fields
		 * @description Some actions before login form fields
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Login form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_before_{$mode}_fields', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_before_{$mode}_fields', 'my_before_fields', 10, 1 );
		 * function my_before_form( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( "um_main_{$mode}_fields", $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_after_form_fields
		 * @description Some actions after login form fields
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Login form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_after_form_fields', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_after_form_fields', 'my_after_form_fields', 10, 1 );
		 * function my_after_form_fields( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_after_form_fields', $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_after_{$mode}_fields
		 * @description Some actions after profile form fields
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_after_{$mode}_fields', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_after_{$mode}_fields', 'my_after_form_fields', 10, 1 );
		 * function my_after_form_fields( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( "um_after_{$mode}_fields", $args );

		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_after_form
		 * @description Some actions after profile form fields
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Profile form shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_after_form', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_after_form', 'my_after_form', 10, 1 );
		 * function my_after_form( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_after_form', $args );

	} else { ?>

		<div class="um-profile-note">
			<span>
				<i class="um-faicon-lock"></i>
				<?php echo $can_view; ?>
			</span>
		</div>

	<?php }
}
add_action( 'um_profile_content_main', 'um_profile_content_main' );


/**
 * Update user's profile
 *
 * @param array $args
 */
function um_user_edit_profile( $args ) {
	$to_update = null;
	$files = array();

	$user_id = null;
	if ( isset( $args['user_id'] ) ) {
		$user_id = $args['user_id'];
	} elseif ( isset( $args['_user_id'] ) ) {
		$user_id = $args['_user_id'];
	}

	if ( UM()->roles()->um_current_user_can( 'edit', $user_id ) ) {
		UM()->user()->set( $user_id );
	} else {
		wp_die( __( 'You are not allowed to edit this user.', 'ultimate-member' ) );
	}

	$userinfo = UM()->user()->profile;

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_user_before_updating_profile
	 * @description Some actions before profile submit
	 * @input_vars
	 * [{"var":"$userinfo","type":"array","desc":"User Data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_user_before_updating_profile', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_user_before_updating_profile', 'my_user_before_updating_profile', 10, 1 );
	 * function my_user_before_updating_profile( $userinfo ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_user_before_updating_profile', $userinfo );

	if ( ! empty( $args['custom_fields'] ) ) {
		$fields = apply_filters( 'um_user_edit_profile_fields', unserialize( $args['custom_fields'] ), $args );
	}

	// loop through fields
	if ( ! empty( $fields ) ) {

		foreach ( $fields as $key => $array ) {

			if ( ! isset( $array['type'] ) ) {
				continue;
			}

			if ( isset( $array['edit_forbidden'] ) ) {
				continue;
			}

			// required option? 'required_opt' - it's field attribute predefined in the field data in code
			if ( isset( $array['required_opt'] ) ) {
				$opt = $array['required_opt'];
				if ( UM()->options()->get( $opt[0] ) != $opt[1] ) {
					continue;
				}
			}

			// fields that need to be disabled in edit mode (profile) (email, username, etc.)
			$arr_restricted_fields = UM()->fields()->get_restricted_fields_for_edit( $user_id );
			if ( in_array( $key, $arr_restricted_fields ) ) {
				continue;
			}

			if ( ! um_can_edit_field( $array ) || ! um_can_view_field( $array ) ) {
				continue;
			}

			//the same code in class-validation.php validate_fields_values for registration form
			//rating field validation
			if ( $array['type'] == 'rating' && isset( $args['submitted'][ $key ] ) ) {
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
			if ( isset( $array['options'] ) && in_array( $array['type'], array( 'select', 'multiselect' ) ) ) {

				$options = array();
				if ( ! empty( $array['custom_dropdown_options_source'] ) && function_exists( $array['custom_dropdown_options_source'] ) && ! $has_custom_source  ) {
					$callback_result = call_user_func( $array['custom_dropdown_options_source'], $array['options'] );
					if ( is_array( $callback_result ) ) {
						$options = array_keys( $callback_result );
					}
				}

				$array['options'] = apply_filters( "um_custom_dropdown_options__{$key}", $options );

			}


			//validation of correct values from options in wp-admin
			$stripslashes = '';
			if ( isset( $args['submitted'][ $key ] ) && is_string( $args['submitted'][ $key ] ) ) {
				$stripslashes = stripslashes( $args['submitted'][ $key ] );
			}

			if ( in_array( $array['type'], array( 'select' ) ) ) {
				if ( ! empty( $array['options'] ) && ! empty( $stripslashes ) && ! in_array( $stripslashes, array_map( 'trim', $array['options'] ) ) && ! $has_custom_source  ) {
					continue;
				}

				//update empty user meta
				if ( ! isset( $args['submitted'][ $key ] ) || $args['submitted'][ $key ] == '' ) {
					update_user_meta( $user_id, $key, '' );
				}
			}

			//validation of correct values from options in wp-admin
			//the user cannot set invalid value in the hidden input at the page
			if ( in_array( $array['type'], array( 'multiselect', 'checkbox', 'radio' ) ) ) {
				if ( ! empty( $args['submitted'][ $key ] ) && ! empty( $array['options'] ) ) {
					$args['submitted'][ $key ] = array_map( 'stripslashes', array_map( 'trim', $args['submitted'][ $key ] ) );
					$args['submitted'][ $key ] = array_intersect( $args['submitted'][ $key ], array_map( 'trim', $array['options'] ) );
				}

				// update empty user meta
				if ( ! isset( $args['submitted'][ $key ] ) || $args['submitted'][ $key ] == '' ) {
					update_user_meta( $user_id, $key, array() );
				}
			}

			if ( isset( $args['submitted'][ $key ] ) ) {

				if ( isset( $array['type'] ) && in_array( $array['type'], array( 'image', 'file' ) ) ) {

					if ( um_is_temp_file( $args['submitted'][ $key ] ) || $args['submitted'][ $key ] == 'empty_file' ) {
						$files[ $key ] = $args['submitted'][ $key ];
					} elseif( um_is_file_owner( UM()->uploader()->get_upload_base_url() . $user_id . '/' . $args['submitted'][ $key ], $user_id ) ) {

					} else {
						$files[ $key ] = 'empty_file';
					}

				} else {
					if ( $array['type'] == 'password' ) {
						$to_update[ $key ] = wp_hash_password( $args['submitted'][ $key ] );
						$args['submitted'][ $key ] = sprintf( __( 'Your choosed %s', 'ultimate-member' ), $array['title'] );
					} else {
						if ( isset( $userinfo[ $key ] ) && $args['submitted'][ $key ] != $userinfo[ $key ] ) {
							$to_update[ $key ] = $args['submitted'][ $key ];
						} elseif ( $args['submitted'][ $key ] != '' ) {
							$to_update[ $key ] = $args['submitted'][ $key ];
						}
					}

				}

			}
		}
	}


	if ( isset( $args['submitted']['description'] ) ) {
		$to_update['description'] = $args['submitted']['description'];
	}

	if ( ! empty( $args['submitted']['role'] ) ) {
		global $wp_roles;
		$role_keys = array_map( function( $item ) {
			return 'um_' . $item;
		}, get_option( 'um_roles' ) );
		$exclude_roles = array_diff( array_keys( $wp_roles->roles ), array_merge( $role_keys, array( 'subscriber' ) ) );

		if ( ! in_array( $args['submitted']['role'], $exclude_roles ) ) {
			$to_update['role'] = $args['submitted']['role'];
		}

		$args['roles_before_upgrade'] = UM()->roles()->get_all_user_roles( $user_id );
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
	do_action( 'um_user_pre_updating_profile', $to_update, $user_id );

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
	$to_update = apply_filters( 'um_user_pre_updating_profile_array', $to_update, $user_id );


	if ( is_array( $to_update ) ) {
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

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_user_pre_updating_files_array
	 * @description Change submitted files before update profile
	 * @input_vars
	 * [{"var":"$files","type":"array","desc":"Profile data files"},
	 * {"var":"$user_id","type":"int","desc":"User ID"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_user_pre_updating_files_array', 'function_name', 10, 2 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_user_pre_updating_files_array', 'my_user_pre_updating_files', 10, 2 );
	 * function my_user_pre_updating_files( $files, $user_id ) {
	 *     // your code here
	 *     return $files;
	 * }
	 * ?>
	 */
	$files = apply_filters( 'um_user_pre_updating_files_array', $files, $user_id );

	if ( ! empty( $files ) && is_array( $files ) ) {
		UM()->uploader()->replace_upload_dir = true;
		UM()->uploader()->move_temporary_files( $user_id, $files );
		UM()->uploader()->replace_upload_dir = false;
	}

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
	do_action( 'um_user_after_updating_profile', $to_update, $user_id );

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_update_profile_full_name
	 * @description On update user profile change full name
	 * @input_vars
	 * [{"var":"$user_id","type":"int","desc":"User ID"},
	 * {"var":"$args","type":"array","desc":"Form data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_update_profile_full_name', 'function_name', 10, 2 );
	 * @example
	 * <?php
	 * add_action( 'um_update_profile_full_name', 'my_update_profile_full_name', 10, 2 );
	 * function my_update_profile_full_name( $user_id, $args ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_update_profile_full_name', $user_id, $to_update );

	if ( ! isset( $args['is_signup'] ) ) {

		$url = um_user_profile_url( $user_id );
		exit( wp_redirect( um_edit_my_profile_cancel_uri( $url ) ) );
	}
}
add_action( 'um_user_edit_profile', 'um_user_edit_profile', 10 );


add_filter( 'um_user_pre_updating_files_array', array( UM()->validation(), 'validate_files' ), 10, 1 );
add_filter( 'um_before_save_filter_submitted', array( UM()->validation(), 'validate_fields_values' ), 10, 2 );

/**
 * Leave roles for User, which are not in the list of update profile (are default WP or 3rd plugins roles)
 *
 * @param $user_id
 * @param $args
 * @param $to_update
 */
function um_restore_default_roles( $user_id, $args, $to_update ) {
	if ( ! empty( $args['submitted']['role'] ) ) {
		$wp_user = new WP_User( $user_id );

		$role_keys = array_map( function( $item ) {
			return 'um_' . $item;
		}, get_option( 'um_roles' ) );

		$leave_roles = array_diff( $args['roles_before_upgrade'], array_merge( $role_keys, array( 'subscriber' ) ) );

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
	if ( UM()->fields()->editing == 1 && UM()->fields()->set_mode == 'profile' && UM()->user()->target_id ) { ?>

		<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( UM()->user()->target_id ); ?>" />

	<?php }
}
add_action( 'um_after_form_fields', 'um_editing_user_id_input' );


/**
 * Meta description
 */
function um_profile_dynamic_meta_desc() {
	if ( um_is_core_page( 'user' ) && um_get_requested_user() ) {

		um_fetch_user( um_get_requested_user() );

		$content = um_convert_tags( UM()->options()->get( 'profile_desc' ) );
		$user_id = um_user( 'ID' );

		$url = um_user_profile_url();
		$avatar = um_get_user_avatar_url( $user_id, 'original' );

		um_reset_user(); ?>

		<meta name="description" content="<?php echo esc_attr( $content ); ?>">

		<meta property="og:title" content="<?php echo esc_attr( um_get_display_name( $user_id ) ); ?>"/>
		<meta property="og:type" content="article"/>
		<meta property="og:image" content="<?php echo esc_url( $avatar ); ?>"/>
		<meta property="og:url" content="<?php echo esc_url( $url ); ?>"/>
		<meta property="og:description" content="<?php echo esc_attr( $content ); ?>"/>

		<?php
	}
}
add_action( 'wp_head', 'um_profile_dynamic_meta_desc', 9999999 );


/**
 * Profile header cover
 *
 * @param $args
 */
function um_profile_header_cover_area( $args ) {
	if ( $args['cover_enabled'] == 1 ) {

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
			if ( UM()->fields()->editing ) {

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

		<div class="um-profile-photo" data-user_id="<?php echo esc_attr( um_profile_id() ); ?>">

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

				} elseif ( UM()->fields()->editing == true ) {

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

					<?php echo UM()->profile()->show_meta( $args['metafields'] ); ?>

				</div>
			<?php }

			if ( UM()->fields()->viewing == true && um_user( 'description' ) && $args['show_bio'] ) { ?>

				<div class="um-meta-text">
					<?php $description = get_user_meta( um_user( 'ID' ), 'description', true );

					if ( UM()->options()->get( 'profile_show_html_bio' ) ) {
						echo make_clickable( wpautop( wp_kses_post( $description ) ) );
					} else {
						echo esc_html( $description );
					} ?>
				</div>

			<?php } elseif ( UM()->fields()->editing == true && $args['show_bio'] ) { ?>

				<div class="um-meta-text">
					<textarea id="um-meta-bio"
							  data-character-limit="<?php echo esc_attr( UM()->options()->get( 'profile_bio_maxchars' ) ); ?>"
							  placeholder="<?php esc_attr_e( 'Tell us a bit about yourself...', 'ultimate-member' ); ?>"
							  name="<?php echo esc_attr( 'description-' . $args['form_id'] ); ?>"
							  id="<?php echo esc_attr( 'description-' . $args['form_id'] ); ?>"><?php echo UM()->fields()->field_value( 'description' ) ?></textarea>
					<span class="um-meta-bio-character um-right"><span
							class="um-bio-limit"><?php echo UM()->options()->get( 'profile_bio_maxchars' ); ?></span></span>

					<?php if ( UM()->fields()->is_error( 'description' ) ) {
						echo UM()->fields()->field_error( UM()->fields()->show_error( 'description' ), true );
					} ?>

				</div>

			<?php } ?>

			<div class="um-profile-status <?php echo esc_attr( um_user( 'account_status' ) ); ?>">
				<span><?php printf( __( 'This user account status is %s', 'ultimate-member' ), um_user( 'account_status_name' ) ); ?></span>
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
			do_action( 'um_after_header_meta', um_user( 'ID' ), $args ); ?>

		</div>
		<div class="um-clear"></div>

		<?php if ( UM()->fields()->is_error( 'profile_photo' ) ) {
			echo UM()->fields()->field_error( UM()->fields()->show_error( 'profile_photo' ), 'force_show' );
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
 * Adds profile permissions to view/edit
 *
 * @param $args
 */
function um_pre_profile_shortcode( $args ) {
	/**
	 * @var $mode
	 */
	extract( $args );

	if ( $mode == 'profile' && UM()->fields()->editing == false ) {
		UM()->fields()->viewing = 1;

		if ( um_get_requested_user() ) {
			if ( ! um_can_view_profile( um_get_requested_user() ) && ! um_is_myprofile() ) {
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

	if ( $mode == 'profile' && UM()->fields()->editing == true ) {
		UM()->fields()->editing = 1;

		if ( um_get_requested_user() ) {
			if ( ! UM()->roles()->um_current_user_can( 'edit', um_get_requested_user() ) ) {
				um_redirect_home( um_get_requested_user(), um_is_myprofile() );
			}
			um_fetch_user( um_get_requested_user() );
		}

	}

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

	if ( UM()->fields()->editing == true ) { ?>

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
	if ( UM()->fields()->editing == true ) {

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
 * @param $args
 */
function um_submit_form_profile( $args ) {
	if ( isset( UM()->form()->errors ) ) {
		return;
	}

	UM()->fields()->set_mode  = 'profile';
	UM()->fields()->editing = true;

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_user_edit_profile
	 * @description Run on successful submit profile form
	 * @input_vars
	 * [{"var":"$args","type":"array","desc":"Form Arguments"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_user_edit_profile', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_user_edit_profile', 'my_user_edit_profile', 10, 1 );
	 * function my_user_edit_profile( $args ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_user_edit_profile', $args );
}
add_action( 'um_submit_form_profile', 'um_submit_form_profile', 10 );


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
	if ( UM()->fields()->editing == false ) {
		return;
	}

	if ( ! isset( $args['primary_btn_word'] ) || $args['primary_btn_word'] == '' ){
		$args['primary_btn_word'] = UM()->options()->get( 'profile_primary_btn_word' );
	}
	if ( ! isset( $args['secondary_btn_word'] ) || $args['secondary_btn_word'] == '' ){
		$args['secondary_btn_word'] = UM()->options()->get( 'profile_secondary_btn_word' );
	} ?>

	<div class="um-col-alt">

		<?php if ( isset( $args['secondary_btn'] ) && $args['secondary_btn'] != 0 ) { ?>

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

					$nav_link = UM()->permalinks()->get_current_url( get_option( 'permalink_structure' ) );
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
							   title="<?php echo esc_attr( $tab['name'] ); ?>" original-title="<?php echo esc_attr( $tab['name'] ); ?>">

								<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>

								<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
									<span class="um-tab-notifier uimob800-show uimob500-show uimob340-show"><?php echo $tab['notifier']; ?></span>
								<?php } ?>

								<span class="uimob800-hide uimob500-hide uimob340-hide title"><?php echo esc_html( $tab['name'] ); ?></span>
							</a>
							<a href="<?php echo esc_url( $nav_link ); ?>" class="uimob800-hide uimob500-hide uimob340-hide"
							   title="<?php echo esc_attr( $tab['name'] ); ?>">

								<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>

								<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
									<span class="um-tab-notifier"><?php echo $tab['notifier']; ?></span>
								<?php } ?>

								<span class="title"><?php echo esc_html( $tab['name'] ); ?></span>
							</a>
						<?php } else { ?>
							<a href="<?php echo esc_url( $nav_link ); ?>" class="uimob800-show uimob500-show uimob340-show um-tip-n"
							   title="<?php echo esc_attr( $tab['name'] ); ?>" original-title="<?php echo esc_attr( $tab['name'] ); ?>">

								<i class="<?php echo esc_attr( $tab['icon'] ); ?>"></i>

								<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
									<span class="um-tab-notifier uimob800-show uimob500-show uimob340-show"><?php echo $tab['notifier']; ?></span>
								<?php } ?>
							</a>
							<a href="<?php echo esc_url( $nav_link ); ?>" class="uimob800-hide uimob500-hide uimob340-hide"
							   title="<?php echo esc_attr( $tab['name'] ); ?>">

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