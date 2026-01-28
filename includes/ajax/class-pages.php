<?php
namespace um\ajax;

use WP_Query;

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

		add_action( 'wp_ajax_um_search_widget_request', array( $this, 'search_widget_request' ) );
		add_action( 'wp_ajax_nopriv_um_search_widget_request', array( $this, 'search_widget_request' ) );
	}

	/**
	 * AJAX callback for getting the pages list
	 */
	public function get_pages_list() {
		check_ajax_referer( 'um-admin-nonce', 'nonce' );

		// we will pass post IDs and titles to this array
		$return = array();

		$pre_result = apply_filters( 'um_admin_settings_get_pages_list', false );

		if ( false === $pre_result ) {
			$query_args = array(
				'post_type'           => 'page',
				'post_status'         => 'publish', // if you don't want drafts to be returned
				'ignore_sticky_posts' => 1,
				'posts_per_page'      => 10, // how much to show at once
				'paged'               => ! empty( $_GET['page'] ) ? absint( $_GET['page'] ) : 1,
				'orderby'             => 'title',
				'order'               => 'asc',
			);

			if ( ! empty( $_GET['search'] ) ) {
				$query_args['s'] = sanitize_text_field( $_GET['search'] ); // the search query
			}

			$field_id = ! empty( $_GET['field_id'] ) ? sanitize_text_field( $_GET['field_id'] ) : null;
			if ( 'form__um_register_use_gdpr_content_id' === $field_id ) {
				$predefined_ids   = array();
				$predefined_pages = array_keys( UM()->config()->get( 'predefined_pages' ) );
				foreach ( $predefined_pages as $slug ) {
					$p_id = um_get_predefined_page_id( $slug );
					if ( empty( $p_id ) ) {
						continue;
					}
					$predefined_ids[] = $p_id;
				}
				$predefined_ids = array_unique( $predefined_ids );
				if ( ! empty( $predefined_ids ) ) {
					$query_args['post__not_in'] = $predefined_ids;
				}
			}

			/**
			 * Filters WP_Query arguments for getting pages visible in the dropdown fields in UM Settings.
			 *
			 * @since 2.10.6
			 * @hook  um_admin_settings_get_pages_list_args
			 *
			 * @param {array}  $query_args Get pages WP_Query arguments.
			 * @param {string} $field_id   Dropdown field ID.
			 *
			 * @return {array} Get pages WP_Query arguments.
			 */
			$query_args = apply_filters( 'um_admin_settings_get_pages_list_args', $query_args, $field_id );

			$search_results = new WP_Query( $query_args );

			if ( $search_results->have_posts() ) {
				while ( $search_results->have_posts() ) {
					$search_results->the_post();

					// shorten the title a little
					$title    = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
					$title    = sprintf( __( '%s (ID: %s)', 'ultimate-member' ), $title, $search_results->post->ID );
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

	/**
	 * AJAX callback for getting search widget redirect to a proper member directory page.
	 */
	public function search_widget_request() {
		check_ajax_referer( 'um_search_widget_request' );

		if ( ! UM()->options()->get( 'members_page' ) ) {
			wp_send_json_error( __( 'No members page enabled', 'ultimate-member' ) );
		}

		$member_directory_ids = array();

		$page_id = UM()->config()->permalinks['members'];
		if ( ! empty( $page_id ) ) {
			$member_directory_ids = UM()->member_directory()->get_member_directory_id( $page_id );
		}

		if ( empty( $member_directory_ids ) ) {
			wp_send_json_error( __( 'No members page enabled', 'ultimate-member' ) );
		}

		$url = um_get_predefined_page_url( 'members' );

		$search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		if ( empty( $search ) ) {
			wp_send_json_success( array( 'url' => $url ) );
		}

		// Current user priority role
		$priority_user_role = false;
		if ( is_user_logged_in() ) {
			$priority_user_role = UM()->roles()->get_priority_user_role( get_current_user_id() );
		}

		foreach ( $member_directory_ids as $directory_id ) {
			$directory_data = UM()->query()->post_data( $directory_id );

			if ( isset( $directory_data['roles_can_search'] ) ) {
				$directory_data['roles_can_search'] = maybe_unserialize( $directory_data['roles_can_search'] );
			}

			$show_search = empty( $directory_data['roles_can_search'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $directory_data['roles_can_search'], true ) );
			if ( empty( $directory_data['search'] ) || ! $show_search ) {
				continue;
			}

			$hash = UM()->member_directory()->get_directory_hash( $directory_id );

			$url = add_query_arg( array( 'search_' . $hash => $search ), $url );
		}

		wp_send_json_success( array( 'url' => $url ) );
	}
}
