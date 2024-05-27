<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pages
 *
 * @package um\ajax
 */
class Pages {

	/**
	 * Pages constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_get_pages_list', array( $this, 'get_pages_list' ) );
		add_action( 'wp_ajax_um_get_tags_list', array( $this, 'get_tags_list' ) );
		add_action( 'wp_ajax_um_get_category_list', array( $this, 'get_category_list' ) );
	}

	/**
	 * AJAX callback for getting the pages list
	 */
	public function get_pages_list() {
		UM()->admin()->check_ajax_nonce();

		// we will pass post IDs and titles to this array
		$return = array();

		$pre_result = apply_filters( 'um_admin_settings_get_pages_list', false );

		// phpcs:disable WordPress.Security.NonceVerification
		if ( false === $pre_result ) {
			$query_args = array(
				'post_type'           => sanitize_key( $_GET['scope'] ),
				'post_status'         => 'publish', // if you don't want drafts to be returned
				'ignore_sticky_posts' => 1,
				'posts_per_page'      => 10, // how much to show at once
				'paged'               => absint( $_GET['page'] ),
				'orderby'             => 'title',
				'order'               => 'asc',
			);

			if ( ! empty( $_GET['search'] ) ) {
				$query_args['s'] = sanitize_text_field( $_GET['search'] ); // the search query
			}

			$search_results = new \WP_Query( $query_args );

			if ( $search_results->have_posts() ) {
				while ( $search_results->have_posts() ) {
					$search_results->the_post();

					// shorten the title a little
					$title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
					// translators: %1$s is the post title, %2$s is the post ID
					$title    = sprintf( __( '%1$s (ID: %2$s)', 'ultimate-member' ), $title, $search_results->post->ID );
					$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
				}
			}

			$return['total_count'] = $search_results->found_posts;
		} else {
			// got already calculated posts array from 3rd-party integrations (e.g. WPML, Polylang)
			$return = $pre_result;
		}
		// phpcs:enable WordPress.Security.NonceVerification

		wp_send_json( $return );
	}

	/**
	 * AJAX callback for getting the tags list
	 */
	public function get_tags_list() {
		UM()->admin()->check_ajax_nonce();

		// we will pass post IDs and titles to this array
		$return = array();

		$tags = get_tags(
			array(
				'hide_empty' => false,
			)
		);

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				// translators: %1$s is the tag title, %2$s is the tag ID
				$title    = sprintf( __( '%1$s (ID: %2$s)', 'ultimate-member' ), $tag->name, $tag->term_id );
				$return[] = array( $tag->term_id, $title ); // array( Post ID, Post Title )
			}
		}

		wp_send_json( $return );
	}

	/**
	 * AJAX callback for getting the tags list
	 */
	public function get_category_list() {
		UM()->admin()->check_ajax_nonce();

		// we will pass post IDs and titles to this array
		$return = array();

		$categories = get_categories(
			array(
				'hide_empty' => false,
				'parent'     => 0,
			)
		);
		if ( ! empty( $categories ) ) {
			foreach ( $categories as $category ) {
				// translators: %1$s is the category title, %2$s is the category ID
				$title            = sprintf( __( '%1$s (ID: %2$s)', 'ultimate-member' ), $category->name, $category->term_id );
				$return[]         = array( $category->term_id, $title ); // array( Post ID, Post Title )
				$child_categories = get_categories(
					array(
						'hide_empty' => false,
						'parent'     => $category->term_id,
					)
				);
				if ( ! empty( $child_categories ) ) {
					foreach ( $child_categories as $child_category ) {
						// translators: %1$s is the category title, %2$s is the category ID
						$child_category_title = sprintf( __( '%1$s (ID: %2$s)', 'ultimate-member' ), '-- ' . $child_category->name, $child_category->term_id );
						$return[]             = array( $child_category->term_id, $child_category_title ); // array( Post ID, Post Title )
					}
				}
			}
		}

		wp_send_json( $return );
	}
}
