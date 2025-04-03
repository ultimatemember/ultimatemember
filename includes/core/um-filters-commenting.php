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

	if ( ! isset( $comment->comment_type ) || 'comment' !== $comment->comment_type ) {
		return $return;
	}

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


function um_comment_author_to_profile( $author, $comment_id ) {
	$comment = ( isset( $GLOBALS['comment'] ) && is_a( $GLOBALS['comment'], 'WP_Comment' ) ) ? $GLOBALS['comment'] : get_comment( $comment_id );
	if ( ! isset( $comment->comment_type ) || 'comment' !== $comment->comment_type ) {
		return $author;
	}

	$return = $author;
	if ( isset( $comment->user_id ) && ! empty( $comment->user_id ) ) {
		if ( isset( UM()->user()->cached_user[ $comment->user_id ] ) && UM()->user()->cached_user[ $comment->user_id ] ) {
			$return = '<a href="' . UM()->user()->cached_user[ $comment->user_id ]['url'] . '">' . UM()->user()->cached_user[ $comment->user_id ]['name'] . '</a>';
		} else {
			um_fetch_user( $comment->user_id );
			UM()->user()->cached_user[ $comment->user_id ] = array(
				'url'  => um_user_profile_url(),
				'name' => um_user( 'display_name' ),
			);

			$return = '<a href="' . UM()->user()->cached_user[ $comment->user_id ]['url'] . '">' . UM()->user()->cached_user[ $comment->user_id ]['name'] . '</a>';
			um_reset_user();
		}
	}

	return $return;
}
add_filter( 'comment_author', 'um_comment_author_to_profile', 100, 2 );
