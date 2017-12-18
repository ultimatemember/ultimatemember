<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


	/***
	***	@sync with WP role
	***/
	add_action('um_after_user_role_is_updated','um_setup_synced_wp_role', 50, 2);
	function um_setup_synced_wp_role( $user_id, $role ) {
		$meta = UM()->roles()->role_data( $role );
		$meta = apply_filters('um_user_permissions_filter', $meta, $user_id );
		$wp_user_object = new WP_User( $user_id );
		
		if ( isset( $meta['synced_role'] ) && $meta['synced_role'] ) {
			$wp_user_object->add_role( $meta['synced_role'] );
		}elseif( ! $wp_user_object->roles ) { // Fallback user default role if nothing set
			$wp_user_object->add_role( 'subscriber' );
		}
	}

	/***
	*** @remove previously synced WP role
	***/
	add_action('um_when_role_is_set', 'um_remove_prev_synced_wp_role');
	function um_remove_prev_synced_wp_role( $user_id ) {
		um_fetch_user( $user_id );
		$meta = UM()->roles()->role_data( um_user( 'role' ) );
		if ( isset( $meta['synced_role'] ) && $meta['synced_role'] ) {
			$wp_user_object = new WP_User( $user_id );
			$wp_user_object->remove_role( $meta['synced_role'] );
		}
	}


	/**
	 * Clean user temp uploads
	 *
	 * @param $user_id
	 * @param $post_array
	 */
	function um_remove_unused_uploads( $user_id, $post_array ) {
		um_fetch_user( $user_id );

		$user_meta_keys = UM()->user()->profile;

		$_array = array();
		foreach ( UM()->builtin()->custom_fields as $_field ) {
			if ( $_field['type'] == 'file' && ! empty( $user_meta_keys[ $_field['metakey'] ] ) )
				$_array[] = $user_meta_keys[ $_field['metakey'] ];
		}
		$_array = array_merge( $_array, $post_array );


		$files = glob( um_user_uploads_dir() . '*', GLOB_BRACE );
		$error = array();
		if ( file_exists( um_user_uploads_dir() ) && $files && isset( $_array ) && is_array( $_array ) ) {
			foreach ( $files as $file ) {
				$str = basename( $file );

				if ( ! strstr( $str, 'profile_photo' ) && ! strstr( $str, 'cover_photo' ) &&
					 ! strstr( $str, 'stream_photo' ) && ! preg_grep( '/' . $str . '/', $_array ) ) {
					$error[] = $str;
					unlink( $file );
				}
			}
		}
	}
	add_action( 'um_after_user_upload','um_remove_unused_uploads', 10, 2 );


	/***
	***	@adds main links to a logout widget
	***/
	add_action('um_logout_user_links', 'um_logout_user_links', 100 );
	function um_logout_user_links( $args ) {
	?>

		<li><a href="<?php echo um_get_core_page('account'); ?>"><?php _e('Your account','ultimate-member'); ?></a></li>
		<li><a href="<?php echo esc_url( add_query_arg('redirect_to', UM()->permalinks()->get_current_url(true), um_get_core_page('logout') ) ); ?>"><?php _e('Logout','ultimate-member'); ?></a></li>

	<?php

	}
