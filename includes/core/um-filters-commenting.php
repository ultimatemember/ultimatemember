<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Control comment author display.
 *
 * @param string $return     The HTML-formatted comment author link.
 * @param string $author     The comment author's username.
 * @param string $comment_id The comment ID as a numeric string.
 *
 * @return string
 */
function um_comment_link_to_profile( $return, $author, $comment_id ) {
	$comment = get_comment( $comment_id );

	if ( ! empty( $comment->user_id ) ) {
		if ( isset( UM()->user()->cached_user[ $comment->user_id ] ) && UM()->user()->cached_user[ $comment->user_id ] ) {
			$return = '<a href="' . esc_url( UM()->user()->cached_user[ $comment->user_id ]['url'] ) . '">' . UM()->user()->cached_user[ $comment->user_id ]['name'] . '</a>';
		} else {
			um_fetch_user( $comment->user_id );

			UM()->user()->cached_user[ $comment->user_id ] = array(
				'url'  => um_user_profile_url(),
				'name' => um_user( 'display_name' ),
			);

			$return = '<a href="' . esc_url( UM()->user()->cached_user[ $comment->user_id ]['url'] ) . '">' . UM()->user()->cached_user[ $comment->user_id ]['name'] . '</a>';

			um_reset_user();
		}
	}

	return $return;
}

add_filter( 'get_comment_author_link', 'um_comment_link_to_profile', 10000, 3 );
