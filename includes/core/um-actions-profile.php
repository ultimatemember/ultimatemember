<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Um_profile_content_{main_tab}
 *
 * @param $args
 */
function um_profile_content_main( $args ) {
	extract( $args );

	if ( ! UM()->options()->get( 'profile_tab_main' ) && ! isset( $_REQUEST['um_action'] ) )
		return;

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
		do_action( "um_before_form", $args );

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
		do_action( "um_after_form_fields", $args );

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
		do_action( "um_after_form", $args );

	} else { ?>
		<div class="um-profile-note"><span><i class="um-faicon-lock"></i><?php echo $can_view; ?></span></div>
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
	$files = null;

	if ( isset( $args['user_id'] ) ) {
		if ( UM()->roles()->um_current_user_can( 'edit', $args['user_id'] ) ) {
			UM()->user()->set( $args['user_id'] );
		} else {
			wp_die( __( 'You are not allowed to edit this user.', 'ultimate-member' ) );
		}
	} elseif ( isset( $args['_user_id'] ) ) {
		UM()->user()->set( $args['_user_id'] );
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
		$fields = unserialize( $args['custom_fields'] );
	}

	// loop through fields
	if ( ! empty( $fields ) ) {
		foreach ( $fields as $key => $array ) {

			if ( ! um_can_edit_field( $fields[ $key ] ) && isset( $fields[ $key ]['editable'] ) && ! $fields[ $key ]['editable'] )
				continue;

			if ( $fields[$key]['type'] == 'multiselect' || $fields[$key]['type'] == 'checkbox' && ! isset( $args['submitted'][ $key ] ) ) {
				delete_user_meta( um_user( 'ID' ), $key );
			}

			if ( isset( $args['submitted'][ $key ] ) ) {

				if ( isset( $fields[ $key ]['type'] ) && in_array( $fields[ $key ]['type'], array( 'image', 'file' ) ) &&
				     ( um_is_temp_upload( $args['submitted'][ $key ] ) || $args['submitted'][ $key ] == 'empty_file' ) ) {

					$files[ $key ] = $args['submitted'][ $key ];

				} else {

					if ( isset( $userinfo[ $key ] ) && $args['submitted'][ $key ] != $userinfo[ $key ] ) {
						$to_update[ $key ] = $args['submitted'][ $key ];
					} elseif ( $args['submitted'][ $key ] ) {
						$to_update[ $key ] = $args['submitted'][ $key ];
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

		$args['roles_before_upgrade'] = UM()->roles()->get_all_user_roles( um_user( 'ID' ) );
	}

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_user_pre_updating_profile
	 * @description Some actions before profile submit
	 * @input_vars
	 * [{"var":"$userinfo","type":"array","desc":"Submitted User Data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_user_pre_updating_profile', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_user_pre_updating_profile', 'my_user_pre_updating_profile', 10, 1 );
	 * function my_user_pre_updating_profile( $userinfo ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_user_pre_updating_profile', $to_update );

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_user_pre_updating_profile_array
	 * @description Change submitted data before update profile
	 * @input_vars
	 * [{"var":"$to_update","type":"array","desc":"Profile data upgrade"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_user_pre_updating_profile_array', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_user_pre_updating_profile_array', 'my_user_pre_updating_profile', 10, 1 );
	 * function my_user_pre_updating_profile( $to_update ) {
	 *     // your code here
	 *     return $to_update;
	 * }
	 * ?>
	 */
	$to_update = apply_filters( 'um_user_pre_updating_profile_array', $to_update );


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
		do_action( 'um_after_user_updated', um_user( 'ID' ), $args, $to_update );
	}

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_user_pre_updating_files_array
	 * @description Change submitted files before update profile
	 * @input_vars
	 * [{"var":"$files","type":"array","desc":"Profile data files"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_user_pre_updating_files_array', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_user_pre_updating_files_array', 'my_user_pre_updating_files', 10, 1 );
	 * function my_user_pre_updating_files( $files ) {
	 *     // your code here
	 *     return $files;
	 * }
	 * ?>
	 */
	$files = apply_filters( 'um_user_pre_updating_files_array', $files );

	if ( is_array( $files ) ) {
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_before_user_upload
		 * @description Before file uploaded on complete UM user profile.
		 * @input_vars
		 * [{"var":"$user_id","type":"int","desc":"User ID"},
		 * {"var":"$files","type":"array","desc":"Files data"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_before_user_upload', 'function_name', 10, 2 );
		 * @example
		 * <?php
		 * add_action( 'um_before_user_upload', 'my_before_user_upload', 10, 2 );
		 * function my_before_user_upload( $user_id, $files ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_before_user_upload', um_user( 'ID' ), $files );
		UM()->user()->update_files( $files );
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_after_user_upload
		 * @description After complete UM user profile edit and file uploaded.
		 * @input_vars
		 * [{"var":"$user_id","type":"int","desc":"User ID"},
		 * {"var":"$files","type":"array","desc":"Files data"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_after_user_upload', 'function_name', 10, 2 );
		 * @example
		 * <?php
		 * add_action( 'um_after_user_upload', 'my_after_user_upload', 10, 2 );
		 * function my_after_user_upload( $user_id, $files ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_after_user_upload', um_user( 'ID' ), $files );
	}

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_user_after_updating_profile
	 * @description After upgrade user's profile
	 * @input_vars
	 * [{"var":"$submitted","type":"array","desc":"Form data"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_user_after_updating_profile', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_user_after_updating_profile', 'my_user_after_updating_profile'', 10, 1 );
	 * function my_user_after_updating_profile( $submitted ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_user_after_updating_profile', $to_update );

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
	do_action( 'um_update_profile_full_name', um_user( 'ID' ), $to_update );

	if ( ! isset( $args['is_signup'] ) ) {

		$url = um_user_profile_url( um_user( 'ID' ) );
		exit( wp_redirect( um_edit_my_profile_cancel_uri( $url ) ) );
	}

}
add_action( 'um_user_edit_profile', 'um_user_edit_profile', 10 );


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

		<input type="hidden" name="user_id" id="user_id" value="<?php echo UM()->user()->target_id; ?>"/>

	<?php }
}
add_action( 'um_after_form_fields', 'um_editing_user_id_input' );


/**
 * Meta description
 */
function um_profile_dynamic_meta_desc() {
	if (um_is_core_page( 'user' ) && um_get_requested_user()) {

		um_fetch_user( um_get_requested_user() );

		$content = um_convert_tags( UM()->options()->get( 'profile_desc' ) );
		$user_id = um_user( 'ID' );
		$url = um_user_profile_url();

		if (um_profile( 'profile_photo' )) {
			$avatar = um_user_uploads_uri() . um_profile( 'profile_photo' );
		} else {
			$avatar = um_get_default_avatar_uri();
		}

		um_reset_user(); ?>

		<meta name="description" content="<?php echo $content; ?>">

		<meta property="og:title" content="<?php echo um_get_display_name( $user_id ); ?>"/>
		<meta property="og:type" content="article"/>
		<meta property="og:image" content="<?php echo $avatar; ?>"/>
		<meta property="og:url" content="<?php echo $url; ?>"/>
		<meta property="og:description" content="<?php echo $content; ?>"/>

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
	if ($args['cover_enabled'] == 1) {

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

		<div class="um-cover <?php if (um_profile( 'cover_photo' ) || ( $default_cover && $default_cover['url'] )) echo 'has-cover'; ?>"
		     data-user_id="<?php echo um_profile_id(); ?>" data-ratio="<?php echo $args['cover_ratio']; ?>">

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

				$items = array(
					'<a href="#" class="um-manual-trigger" data-parent=".um-cover" data-child=".um-btn-auto-width">' . __( 'Change cover photo', 'ultimate-member' ) . '</a>',
					'<a href="#" class="um-reset-cover-photo" data-user_id="' . um_profile_id() . '">' . __( 'Remove', 'ultimate-member' ) . '</a>',
					'<a href="#" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
				);

				echo UM()->profile()->new_ui( 'bc', 'div.um-cover', 'click', $items );

			}

			UM()->fields()->add_hidden_field( 'cover_photo' );

			echo $overlay; ?>

			<div class="um-cover-e" data-ratio="<?php echo $args['cover_ratio']; ?>">

				<?php if (um_profile( 'cover_photo' )) { ?>

					<?php

					if (UM()->mobile()->isMobile()) {
						if (UM()->mobile()->isTablet()) {
							echo um_user( 'cover_photo', 1000 );
						} else {
							echo um_user( 'cover_photo', 300 );
						}
					} else {
						echo um_user( 'cover_photo', 1000 );
					}

					?>

				<?php } else if ($default_cover && $default_cover['url']) {

					$default_cover = $default_cover['url'];

					echo '<img src="' . $default_cover . '" alt="" />';

				} else {

					if (!isset( UM()->user()->cannot_edit )) { ?>

						<a href="#" class="um-cover-add um-manual-trigger" data-parent=".um-cover"
						   data-child=".um-btn-auto-width"><span class="um-cover-add-i"><i
									class="um-icon-plus um-tip-n"
									title="<?php _e( 'Upload a cover photo', 'ultimate-member' ); ?>"></i></span></a>

					<?php }

				} ?>

			</div>

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

	if (!$args['cover_enabled']) {
		$classes .= ' no-cover';
	}

	$default_size = str_replace( 'px', '', $args['photosize'] );

	$overlay = '<span class="um-profile-photo-overlay">
			<span class="um-profile-photo-overlay-s">
				<ins>
					<i class="um-faicon-camera"></i>
				</ins>
			</span>
		</span>';

	?>

	<div class="um-header<?php echo $classes; ?>">

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

		<div class="um-profile-photo" data-user_id="<?php echo um_profile_id(); ?>">

			<a href="<?php echo um_user_profile_url(); ?>" class="um-profile-photo-img"
			   title="<?php echo um_user( 'display_name' ); ?>"><?php echo $overlay . get_avatar( um_user( 'ID' ), $default_size ); ?></a>

			<?php

			if (!isset( UM()->user()->cannot_edit )) {

				UM()->fields()->add_hidden_field( 'profile_photo' );

				if (!um_profile( 'profile_photo' )) { // has profile photo

					$items = array(
						'<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . __( 'Upload photo', 'ultimate-member' ) . '</a>',
						'<a href="#" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
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

					echo UM()->profile()->new_ui( 'bc', 'div.um-profile-photo', 'click', $items );

				} else if (UM()->fields()->editing == true) {

					$items = array(
						'<a href="#" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . __( 'Change photo', 'ultimate-member' ) . '</a>',
						'<a href="#" class="um-reset-profile-photo" data-user_id="' . um_profile_id() . '" data-default_src="' . um_get_default_avatar_uri() . '">' . __( 'Remove photo', 'ultimate-member' ) . '</a>',
						'<a href="#" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
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

					echo UM()->profile()->new_ui( 'bc', 'div.um-profile-photo', 'click', $items );

				}

			}

			?>

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

						<a href="<?php echo um_user_profile_url(); ?>"
						   title="<?php echo um_user( 'display_name' ); ?>"><?php echo um_user( 'display_name', 'html' ); ?></a>

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

			<?php if (isset( $args['metafields'] ) && !empty( $args['metafields'] )) { ?>
				<div class="um-meta">

					<?php echo UM()->profile()->show_meta( $args['metafields'] ); ?>

				</div>
			<?php } ?>

			<?php if (UM()->fields()->viewing == true && um_user( 'description' ) && $args['show_bio']) { ?>

				<div class="um-meta-text">
					<?php

					$description = get_user_meta( um_user( 'ID' ), 'description', true );
					if ( UM()->options()->get( 'profile_show_html_bio' ) ) : ?>
						<?php echo make_clickable( wpautop( wp_kses_post( $description ) ) ); ?>
					<?php else : ?>
						<?php echo esc_html( $description ); ?>
					<?php endif; ?>
				</div>

			<?php } else if (UM()->fields()->editing == true && $args['show_bio']) { ?>

				<div class="um-meta-text">
					<textarea id="um-meta-bio"
					          data-character-limit="<?php echo UM()->options()->get( 'profile_bio_maxchars' ); ?>"
					          placeholder="<?php _e( 'Tell us a bit about yourself...', 'ultimate-member' ); ?>"
					          name="<?php echo 'description-' . $args['form_id']; ?>"
					          id="<?php echo 'description-' . $args['form_id']; ?>"><?php if (um_user( 'description' )) {
							echo um_user( 'description' );
						} ?></textarea>
					<span class="um-meta-bio-character um-right"><span
							class="um-bio-limit"><?php echo UM()->options()->get( 'profile_bio_maxchars' ); ?></span></span>
					<?php
					if (UM()->fields()->is_error( 'description' )) {
						echo UM()->fields()->field_error( UM()->fields()->show_error( 'description' ), true );
					}
					?>

				</div>

			<?php } ?>

			<div class="um-profile-status <?php echo um_user( 'account_status' ); ?>">
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
			if ( ! um_can_view_profile( um_get_requested_user() ) && ! um_is_myprofile() )
				um_redirect_home();

			if ( ! UM()->roles()->um_current_user_can( 'edit', um_get_requested_user() ) )
				UM()->user()->cannot_edit = 1;

			um_fetch_user( um_get_requested_user() );
		} else {
			if ( ! is_user_logged_in() )
				um_redirect_home();

			if ( ! um_user( 'can_edit_profile' ) )
				UM()->user()->cannot_edit = 1;
		}
	}

	if ( $mode == 'profile' && UM()->fields()->editing == true ) {
		UM()->fields()->editing = 1;

		if ( um_get_requested_user() ) {
			if ( ! UM()->roles()->um_current_user_can( 'edit', um_get_requested_user() ) ) {
				um_redirect_home();
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
	$output = '';

	if (!is_user_logged_in()) return; // not allowed for guests

	if (isset( UM()->user()->cannot_edit ) && UM()->user()->cannot_edit == 1) return; // do not proceed if user cannot edit

	if (UM()->fields()->editing == true) {

		?>

		<div class="um-profile-edit um-profile-headericon">

			<a href="#" class="um-profile-edit-a um-profile-save"><i class="um-faicon-check"></i></a>

		</div>

	<?php } else { ?>

		<div class="um-profile-edit um-profile-headericon">

			<a href="#" class="um-profile-edit-a"><i class="um-faicon-cog"></i></a>

			<?php

			$items = array(
				'editprofile' => '<a href="' . um_edit_profile_url() . '" class="real_url">' . __( 'Edit Profile', 'ultimate-member' ) . '</a>',
				'myaccount'   => '<a href="' . um_get_core_page( 'account' ) . '" class="real_url">' . __( 'My Account', 'ultimate-member' ) . '</a>',
				'logout'      => '<a href="' . um_get_core_page( 'logout' ) . '" class="real_url">' . __( 'Logout', 'ultimate-member' ) . '</a>',
				'cancel'      => '<a href="#" class="um-dropdown-hide">' . __( 'Cancel', 'ultimate-member' ) . '</a>',
			);

			$cancel = $items['cancel'];

			if (!um_is_myprofile()) {

				$actions = UM()->user()->get_admin_actions();

				unset( $items['myaccount'] );
				unset( $items['logout'] );
				unset( $items['cancel'] );

				if (is_array( $actions )) {
					$items = array_merge( $items, $actions );
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

				$items['cancel'] = $cancel;

			} else {

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

			}

			UM()->profile()->new_ui( $args['header_menu'], 'div.um-profile-edit', 'click', $items );

			?>

		</div>

		<?php
	}

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
	if (UM()->user()->preview == true && is_admin()) return;

	// only when editing
	if (UM()->fields()->editing == false) return;

	?>

	<div class="um-col-alt">

		<?php if (isset( $args['secondary_btn'] ) && $args['secondary_btn'] != 0) { ?>

			<div class="um-left um-half"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>"
			                                    class="um-button"/></div>
			<div class="um-right um-half"><a href="<?php echo um_edit_my_profile_cancel_uri(); ?>"
			                                 class="um-button um-alt"><?php echo $args['secondary_btn_word']; ?></a>
			</div>

		<?php } else { ?>

			<div class="um-center"><input type="submit" value="<?php echo $args['primary_btn_word']; ?>"
			                              class="um-button"/></div>

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
	if ( ! UM()->options()->get( 'profile_menu' ) )
		return;

	// get active tabs
	$tabs = UM()->profile()->tabs_active();

	/**
	 * UM hook
	 *
	 * @type filter
	 * @title um_user_profile_tabs
	 * @description Extend profile tabs
	 * @input_vars
	 * [{"var":"$tabs","type":"array","desc":"Profile Tabs"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage
	 * <?php add_filter( 'um_user_profile_tabs', 'function_name', 10, 1 ); ?>
	 * @example
	 * <?php
	 * add_filter( 'um_user_profile_tabs', 'my_user_profile_tabs', 10, 1 );
	 * function my_user_profile_tabs( $tabs ) {
	 *     // your code here
	 *     return $tabs;
	 * }
	 * ?>
	 */
	$tabs = apply_filters( 'um_user_profile_tabs', $tabs );

	UM()->user()->tabs = $tabs;

	// need enough tabs to continue
	if ( count( $tabs ) <= 1 ) {
		return;
	}

	$active_tab = UM()->profile()->active_tab();

	if ( ! isset( $tabs[ $active_tab ] ) ) {
		$active_tab = 'main';
		UM()->profile()->active_tab = $active_tab;
		UM()->profile()->active_subnav = null;
	}

	// Move default tab priority
	$default_tab = UM()->options()->get( 'profile_menu_default_tab' );
	$dtab = ( isset( $tabs[ $default_tab ] ) ) ? $tabs[ $default_tab ] : 'main';
	if ( isset( $tabs[ $default_tab] ) ) {
		unset( $tabs[ $default_tab ] );
		$dtabs[ $default_tab ] = $dtab;
		$tabs = $dtabs + $tabs;
	} ?>

	<div class="um-profile-nav">

		<?php foreach ( $tabs as $id => $tab ) {

			if ( isset( $tab['hidden'] ) ) {
				continue;
			}

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

			<div class="um-profile-nav-item um-profile-nav-<?php echo $id . ' ' . $profile_nav_class; ?>">
				<?php if ( UM()->options()->get( 'profile_menu_icons' ) ) { ?>
					<a href="<?php echo $nav_link; ?>" class="um-tip-n uimob500-show uimob340-show uimob800-show"
					   title="<?php echo esc_attr( $tab['name'] ); ?>" original-title="<?php echo esc_attr( $tab['name'] ); ?>">

						<i class="<?php echo $tab['icon']; ?>"></i>

						<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
							<span class="um-tab-notifier uimob500-show uimob340-show uimob800-show"><?php echo $tab['notifier']; ?></span>
						<?php } ?>

						<span class="uimob500-hide uimob340-hide uimob800-hide title"><?php echo $tab['name']; ?></span>

					</a>
					<a href="<?php echo $nav_link; ?>" class="uimob500-hide uimob340-hide uimob800-hide"
					   title="<?php echo esc_attr( $tab['name'] ); ?>" original-title="<?php echo esc_attr( $tab['name'] ); ?>">

						<i class="<?php echo $tab['icon']; ?>"></i>

						<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0 ) { ?>
							<span class="um-tab-notifier uimob500-show uimob340-show uimob800-show"><?php echo $tab['notifier']; ?></span>
						<?php } ?>

						<span class="uimob500-hide uimob340-hide uimob800-hide title"><?php echo $tab['name']; ?></span>

					</a>
				<?php } else { ?>
					<a href="<?php echo $nav_link; ?>" title="<?php echo esc_attr( $tab['name'] ); ?>"
					   original-title="<?php echo esc_attr( $tab['name'] ); ?>">

						<?php if ( isset( $tab['notifier'] ) && $tab['notifier'] > 0) { ?>
							<span class="um-tab-notifier uimob500-show uimob340-show uimob800-show"><?php echo $tab['notifier']; ?></span>
						<?php } ?>

						<span class="uimob500-hide uimob340-hide uimob800-hide title"><?php echo $tab['name']; ?></span>

					</a>
				<?php } ?>
			</div>

		<?php } ?>

		<div class="um-clear"></div>

	</div>

	<?php foreach ( $tabs as $id => $tab ) {

		if ( isset( $tab['subnav'] ) && $active_tab == $id ) {

			$active_subnav = ( UM()->profile()->active_subnav() ) ? UM()->profile()->active_subnav() : $tab['subnav_default']; ?>

			<div class="um-profile-subnav">
				<?php foreach ( $tab['subnav'] as $id_s => $subtab ) { ?>

					<a href="<?php echo add_query_arg( 'subnav', $id_s ); ?>" class="<?php if ( $active_subnav == $id_s ) echo 'active'; ?>">
						<?php echo $subtab; ?>
					</a>

				<?php } ?>
			</div>
		<?php }

	}

}
add_action( 'um_profile_menu', 'um_profile_menu', 9 );


/**
 * Clean up file for new uploaded files
 *
 * @param  integer $user_id
 * @param  array   $arr_files
 */
function um_before_user_upload( $user_id, $arr_files ) {
	um_fetch_user( $user_id );

	foreach ( $arr_files as $key => $filename ) {
		if ( um_user( $key ) ) {
			$old_filename = um_user( $key );

			if ( basename( $filename ) != basename( um_user( $key ) ) ||
			     in_array( $old_filename, array( basename( um_user( $key ) ), basename( $filename ) ) ) ||
			     $filename == 'empty_file' ) {

				$path = UM()->files()->upload_basedir;
				delete_user_meta( $user_id, $old_filename );
				if (file_exists( $path . $user_id . '/' . $old_filename )) {
					unlink( $path . $user_id . '/' . $old_filename );
				}
			}
		}
	}
}
add_action( "um_before_user_upload", "um_before_user_upload", 10, 2 );