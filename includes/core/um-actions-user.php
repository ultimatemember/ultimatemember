<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


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


/**
 * Adds main links to a logout widget
 *
 * @param $args
 */
function um_logout_user_links( $args ) {
	?>

	<li><a href="<?php echo um_get_core_page( 'account' ); ?>"><?php _e( 'Your account', 'ultimate-member' ); ?></a></li>
	<li><a href="<?php echo esc_url( add_query_arg( 'redirect_to', UM()->permalinks()->get_current_url( true ), um_get_core_page( 'logout' ) ) ); ?>"><?php _e('Logout','ultimate-member'); ?></a></li>

	<?php
}
add_action( 'um_logout_user_links', 'um_logout_user_links', 100 );