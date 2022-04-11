<?php
namespace um\ajax;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\ajax\Pages' ) ) {


	/**
	 * Class Pages
	 *
	 * @package um\ajax
	 */
	class Pages {


		/**
		 * Pages constructor.
		 */
		function __construct() {
			add_action( 'wp_ajax_um_get_pages_list', array( $this, 'get_pages_list' ) );
		}


		/**
		 * AJAX callback for getting the pages list
		 */
		function get_pages_list() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			// we will pass post IDs and titles to this array
			$return = array();

			$pre_result = apply_filters( 'um_admin_settings_get_pages_list', false );

			if ( false === $pre_result ) {
				// you can use WP_Query, query_posts() or get_posts() here - it doesn't matter
				$search_results = new \WP_Query( array(
					'post_type'           => 'page',
					's'                   => sanitize_text_field( $_GET['search'] ), // the search query
					'post_status'         => 'publish', // if you don't want drafts to be returned
					'ignore_sticky_posts' => 1,
					'posts_per_page'      => 10, // how much to show at once
					'paged'               => absint( $_GET['page'] ),
					'orderby'             => 'title',
					'order'               => 'asc',
				) );

				if ( $search_results->have_posts() ) {
					while ( $search_results->have_posts() ) {
						$search_results->the_post();

						// shorten the title a little
						$title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
						$title = sprintf( __( '%s (ID: %s)', 'ultimate-member' ), $title, $search_results->post->ID );
						$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
					}
				}

				$return['total_count'] = $search_results->found_posts;
			} else {
				// got already calculated posts array from 3rd-party integrations (e.g. WPML, Polylang)
				$return = $pre_result;
			}

			wp_send_json( $return );
		}
	}
}
