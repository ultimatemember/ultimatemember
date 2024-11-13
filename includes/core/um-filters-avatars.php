<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Do not apply to backend default avatars
 *
 * @param $avatar_defaults
 *
 * @return mixed
 */
function um_avatar_defaults( $avatar_defaults ) {
	remove_filter( 'get_avatar', 'um_get_avatar', 99999 );
	return $avatar_defaults;
}
add_filter( 'avatar_defaults', 'um_avatar_defaults', 99999 );

/**
 * Get user UM avatars.
 * @param  string $avatar
 * @param  string $id_or_email
 * @param  string $size
 * @param  string $avatar_class
 * @param  string $default
 * @param  string $alt
 * @return string returns avatar in image html elements
 */
function um_get_avatar( $avatar = '', $id_or_email='', $size = '96', $avatar_class = '', $default = '', $alt = '' ) {
	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_string( $id_or_email ) && ( $user = get_user_by( 'email', $id_or_email ) ) ) {
		$user_id = $user->ID;
	} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
		$user_id = (int) $id_or_email->user_id;
	}

	if ( empty( $user_id ) ) {
		return $avatar;
	}

	if ( $user_id !== um_user( 'ID' ) ) {
		$temp_id = um_user( 'ID' );
		um_fetch_user( $user_id );
	}

	$avatar = um_user( 'profile_photo', $size );

	if ( ! empty( $temp_id ) ) {
		um_fetch_user( $temp_id );
	}

	return $avatar;
}
add_filter( 'get_avatar', 'um_get_avatar', 99999, 5 );

if ( ! function_exists( 'um_filter_get_avatar_url' ) ) {

	/**
	 * Replace Gravatar image URL to Ultimate member profile image URL if setting "Use Gravatars?" disabled
	 *
	 * @param string $url
	 * @param int $id_or_email
	 * @param array $args
	 * @return string
	 */
	function um_filter_get_avatar_url( $url, $id_or_email, $args ) {

		if ( is_numeric( $id_or_email ) && ! UM()->options()->get( 'use_gravatars' ) && preg_match( '/gravatar/i', $url ) ) {
			$data = um_get_user_avatar_data( $id_or_email, $args['size'] );
			if ( ! empty( $data['url'] ) ) {
				$url = $data['url'];
			}
		}

		return $url;
	}

	// hooked in the get_avatar_data function
	add_filter( 'get_avatar_url', 'um_filter_get_avatar_url', 20, 3 );
}
