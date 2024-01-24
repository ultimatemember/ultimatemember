<?php
/**
 * Ultimate Member Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @version 2.8.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $slug
 *
 * @return bool
 */
function um_predefined_page_slug_exists( $slug ) {
	$predefined_pages = UM()->config()->get( 'predefined_pages' );
	return array_key_exists( $slug, $predefined_pages );
}

/**
 * @param string $slug
 *
 * @return false|int
 */
function um_get_predefined_page_id( $slug ) {
	if ( ! um_predefined_page_slug_exists( $slug ) ) {
		return false;
	}

	$option_key = UM()->options()->get_predefined_page_option_key( $slug );
	return apply_filters( 'um_get_predefined_page_id', UM()->options()->get( $option_key ), $slug );
}

/**
 *
 * @param string $slug
 * @param null|int|\WP_Post $post
 *
 * @return bool
 */
function um_is_predefined_page( $slug, $post = null ) {
	// handle $post inside, just we need make $post as \WP_Post. Otherwise something is wrong and return false
	if ( ! $post ) {
		global $post;

		if ( empty( $post ) ) {
			return false;
		}
	} else {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );

			if ( empty( $post ) ) {
				return false;
			}
		}
	}

	if ( empty( $post->ID ) ) {
		return false;
	}

	return um_get_predefined_page_id( $slug ) === $post->ID;
}

/**
 * Get predefined page URL
 *
 * @param string $slug
 *
 * @return false|string
 */
function um_get_predefined_page_url( $slug ) {
	$url = false;

	if ( $page_id = um_get_predefined_page_id( $slug ) ) {
		$url = get_permalink( $page_id );
	}

	return $url;
}
