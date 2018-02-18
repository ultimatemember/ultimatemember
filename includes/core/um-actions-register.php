<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


	/**
	 * Account automatically approved
	 */
	add_action('um_post_registration_approved_hook', 'um_post_registration_approved_hook', 10, 2);
	function um_post_registration_approved_hook($user_id, $args){
		um_fetch_user( $user_id );

		UM()->user()->approve();
	}

	/**
	 * Account needs email validation
	 */
	add_action('um_post_registration_checkmail_hook', 'um_post_registration_checkmail_hook', 10, 2);
	function um_post_registration_checkmail_hook($user_id, $args){
		um_fetch_user( $user_id );

		UM()->user()->email_pending();
	}

	/**
	 * Account needs admin review
	 */
	add_action('um_post_registration_pending_hook', 'um_post_registration_pending_hook', 10, 2);
	function um_post_registration_pending_hook($user_id, $args){
		um_fetch_user( $user_id );

		UM()->user()->pending();
	}


	/**
	 * After insert a new user
	 * run at frontend and backend
	 *
	 * @param $user_id
	 * @param $args
	 */
	function um_after_insert_user( $user_id, $args ) {
        //clear Users cached queue
        UM()->user()->remove_cached_queue();

        if ( ! empty( $args['submitted'] ) ) {
            um_fetch_user( $user_id );
			UM()->user()->set_registration_details( $args['submitted'] );
		}

		do_action( 'um_registration_set_extra_data', $user_id, $args );

		//redirects handlers at 100 priority, you can add some info before redirects
        //after complete UM user registration
        do_action( 'um_registration_complete', $user_id, $args );
	}
	add_action( 'um_user_register', 'um_after_insert_user', 10, 2 );


    /**
     * Send notification about registration
     *
     * @param $user_id
     * @param $args
     */
	function um_send_registration_notification( $user_id, $args ) {
		um_fetch_user( $user_id );

		if ( um_user( 'status' ) != 'pending' ) {
			UM()->mail()->send( um_admin_email(), 'notification_new_user', array( 'admin' => true ) );
		} else {
			UM()->mail()->send( um_admin_email(), 'notification_review', array( 'admin' => true ) );
		}
	}
    add_action( 'um_registration_complete', 'um_send_registration_notification', 10, 2 );


    /**
     * Check user status and redirect it after registration
     *
     * @param $user_id
     * @param $args
     */
	function um_check_user_status( $user_id, $args ) {
        $status = um_user( 'status' );

        do_action( "um_post_registration_{$status}_hook", $user_id, $args );

		if ( ! is_admin() ) {

			do_action( "track_{$status}_user_registration" );

			// Priority redirect
			if ( isset( $args['redirect_to'] ) ) {
				exit( wp_redirect( urldecode( $args['redirect_to'] ) ) );
			}

            if ( $status == 'approved' ) {

				UM()->user()->auto_login( $user_id );
				UM()->user()->generate_profile_slug( $user_id );

				do_action( 'um_registration_after_auto_login', $user_id );

				if ( um_user( 'auto_approve_act' ) == 'redirect_url' && um_user( 'auto_approve_url' ) !== '' ) {
					exit( wp_redirect( um_user( 'auto_approve_url' ) ) );
				}

				if ( um_user( 'auto_approve_act' ) == 'redirect_profile' ) {
				    exit( wp_redirect( um_user_profile_url() ) );
				}

			}

			if ( $status != 'approved' ) {

				if ( um_user( $status . '_action' ) == 'redirect_url' && um_user( $status . '_url' ) != '' ) {
					exit( wp_redirect( um_user( $status . '_url' ) ) );
				}

				if ( um_user( $status . '_action' ) == 'show_message' && um_user( $status . '_message' ) != '' ) {

					$url  = UM()->permalinks()->get_current_url();
					$url  = add_query_arg( 'message', esc_attr( $status ), $url );
					//add only priority role to URL
					$url  = add_query_arg( 'um_role', esc_attr( um_user( 'role' ) ), $url );
					$url  = add_query_arg( 'um_form_id', esc_attr( $args['form_id'] ), $url );

					exit( wp_redirect( $url ) );
				}

			}

		}

	}
    add_action( 'um_registration_complete', 'um_check_user_status', 100, 2 );


	/**
	 * Registration form submit handler
	 *
	 * @param $args
	 * @return bool|int|WP_Error
	 */
	function um_submit_form_register( $args ) {
		if ( isset( UM()->form()->errors ) )
			return false;

        $args = apply_filters( 'um_add_user_frontend_submitted', $args );

		extract( $args );

		if ( isset( $username ) && ! isset( $user_login ) ) {
			$user_login = $username;
		}

		if ( ! empty( $first_name ) && ! empty( $last_name ) && ! isset( $user_login ) ) {

			if ( UM()->options()->get( 'permalink_base' ) == 'name' ) {
				$user_login = rawurlencode( strtolower( str_replace( " ", ".", $first_name . " " . $last_name ) ) );
			} elseif ( UM()->options()->get( 'permalink_base' ) == 'name_dash' ) {
				$user_login = rawurlencode( strtolower( str_replace( " ", "-", $first_name . " " . $last_name ) ) );
			} elseif ( UM()->options()->get( 'permalink_base' ) == 'name_plus' ) {
				$user_login = strtolower( str_replace( " ", "+", $first_name . " " . $last_name ) );
			} else {
				$user_login = strtolower( str_replace( " ", "", $first_name . " " . $last_name ) );
			}

			// if full name exists
			$count = 1;
			while ( username_exists( $user_login ) ) {
				$user_login .= $count;
				$count++;
			}
		}

		if ( ! isset( $user_login ) && isset( $user_email ) && $user_email ) {
			$user_login = $user_email;
		}

		$unique_userID = UM()->query()->count_users() + 1;

		if ( ! isset( $user_login ) ||  strlen( $user_login ) > 30 && ! is_email( $user_login ) ) {
			$user_login = 'user' . $unique_userID;
		}

		if ( isset( $username ) && is_email( $username ) ) {
			$user_email = $username;
		}

		if ( ! isset( $user_password ) ) {
			$user_password = UM()->validation()->generate( 8 );
		}

		if ( ! isset( $user_email ) ) {
			$site_url = @$_SERVER['SERVER_NAME'];
			$user_email = 'nobody' . $unique_userID . '@' . $site_url;
			$user_email = apply_filters( 'um_user_register_submitted__email', $user_email );
		}

		$credentials = array(
			'user_login'	=> $user_login,
			'user_password'	=> $user_password,
			'user_email'	=> trim( $user_email ),
		);

		$args['submitted'] = array_merge( $args['submitted'], $credentials );
		$args = array_merge( $args, $credentials );

		$user_role = apply_filters( 'um_registration_user_role', UM()->form()->assigned_role( UM()->form()->form_id ), $args );

		$userdata = array(
			'user_login'	=> $user_login,
			'user_pass'		=> $user_password,
			'user_email'	=> $user_email,
			'role'			=> $user_role,
		);
		$user_id = wp_insert_user( $userdata );

		do_action( 'um_user_register', $user_id, $args );

		return $user_id;
	}
	add_action( 'um_submit_form_register', 'um_submit_form_register', 10 );


	/**
	 * Register user with predefined role in options
	 */
	add_action( 'um_after_register_fields', 'um_add_user_role' );
	function um_add_user_role( $args ) {

		if ( isset( $args['custom_fields']['role_select'] ) || isset( $args['custom_fields']['role_radio'] ) ) return;

        $use_custom_settings = get_post_meta( $args['form_id'], '_um_register_use_custom_settings', true );
		
		if ( ! empty( $args['role'] ) && $use_custom_settings ) {
			$role = $args['role'];
		} else if( ! $use_custom_settings ) {
			$role = get_option( 'default_role' );
		}

		if( empty( $role ) ) return;

		$role = apply_filters('um_register_hidden_role_field', $role );
		if( $role ){
			echo '<input type="hidden" name="role" id="role" value="' . $role . '" />';
		}

	}

	/**
	 * Show the submit button 
	 */
	add_action('um_after_register_fields', 'um_add_submit_button_to_register', 1000);
	function um_add_submit_button_to_register($args){
		// DO NOT add when reviewing user's details
		if ( isset( UM()->user()->preview ) && UM()->user()->preview == true && is_admin() ) return;

		$primary_btn_word = $args['primary_btn_word'];
		$primary_btn_word = apply_filters('um_register_form_button_one', $primary_btn_word, $args );

		$secondary_btn_word = $args['secondary_btn_word'];
		$secondary_btn_word = apply_filters('um_register_form_button_two', $secondary_btn_word, $args );

		$secondary_btn_url = ( isset( $args['secondary_btn_url'] ) && $args['secondary_btn_url'] ) ? $args['secondary_btn_url'] : um_get_core_page('login');
		$secondary_btn_url = apply_filters('um_register_form_button_two_url', $secondary_btn_url, $args );

		?>

		<div class="um-col-alt">

			<?php if ( isset($args['secondary_btn']) && $args['secondary_btn'] != 0 ) { ?>

			<div class="um-left um-half"><input type="submit" value="<?php echo __( $primary_btn_word,'ultimate-member'); ?>" class="um-button" id="um-submit-btn" /></div>
			<div class="um-right um-half"><a href="<?php echo $secondary_btn_url; ?>" class="um-button um-alt"><?php echo __( $secondary_btn_word,'ultimate-member'); ?></a></div>

			<?php } else { ?>

			<div class="um-center"><input type="submit" value="<?php echo __( $primary_btn_word,'ultimate-member'); ?>" class="um-button" id="um-submit-btn" /></div>

			<?php } ?>

			<div class="um-clear"></div>

		</div>

		<?php
	}

	/**
	 * Show Fields
	 */
	add_action('um_main_register_fields', 'um_add_register_fields', 100);
	function um_add_register_fields($args){
		echo UM()->fields()->display( 'register', $args );

	}
	
	
	/**
	 * Saving files to register a new user, if there are fields with files
	 */
	add_action( 'um_registration_set_extra_data', 'um_registration_save_files', 10, 2 );
	function um_registration_save_files( $user_id, $args ) {

		if ( empty( $args['custom_fields'] ) )
			return;

		$files = array();

		$fields = unserialize( $args['custom_fields'] );

		// loop through fields
		if ( isset( $fields ) && is_array( $fields ) ) {

			foreach ( $fields as $key => $array ) {

				if ( isset( $args['submitted'][$key] ) ) {

					if ( isset( $fields[$key]['type'] ) && in_array( $fields[$key]['type'], array( 'image', 'file' ) ) &&
						( um_is_temp_upload( $args['submitted'][$key] ) || $args['submitted'][$key] == 'empty_file' )
					) {

						$files[$key] = $args['submitted'][$key];

					}
				}
			}
		}

		$files = apply_filters( 'um_user_pre_updating_files_array', $files );

		if ( !empty( $files ) ) {
			do_action( 'um_before_user_upload', $user_id, $files );
			UM()->user()->update_files( $files );
			do_action( 'um_after_user_upload', $user_id, $files );
		}
	}

	/**
	 * Update user Full Name
	 *
	 * @profile name update
	 *
	 * @param $user_id
	 * @param $args
	 */
	function um_registration_set_profile_full_name( $user_id, $args ) {
		do_action( 'um_update_profile_full_name', $user_id, $args );
	}

	add_action( 'um_registration_set_extra_data', 'um_registration_set_profile_full_name', 10, 2 );