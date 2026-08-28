<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GIF
 *
 * @package um\ajax
 */
class GIF {

	/**
	 * GIF constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_get_gif_images', array( $this, 'get_gif_list' ) );
	}

	public function get_gif_list() {
		$api_key = UM()->options()->get( 'giphy_api_key' );
		if ( empty( $api_key ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'um_get_gif_list', 'nonce' );

		$images = array();

		$keyword = 'hello';
		if ( ! empty( $_POST['search'] ) ) {
			$keyword = sanitize_text_field( $_POST['search'] );
		}

		$per_page = 20;
		if ( ! empty( $_POST['per_page'] ) ) {
			$per_page = absint( $_POST['per_page'] );
		}

		$query_args = array(
			'api_key' => $api_key,
			'q'       => $keyword,
			'limit'   => $per_page,
			'offset'  => 0,
		);

		if ( isset( $_POST['next'] ) ) {
			$query_args['offset'] = absint( $_POST['next'] );
		}

		$url = add_query_arg( $query_args, 'https://api.giphy.com/v1/gifs/search' );

		$args = array(
			'headers' => array(
				'Referer' => get_site_url(),
			),
		);

		$response = wp_remote_get( $url, $args );
		$result   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_wp_error( $response ) && ! empty( $result['data'] ) && ! empty( $result['meta']['status'] ) && 200 === absint( $result['meta']['status'] ) ) {
			foreach ( $result['data'] as $image ) {
				$preview = '';
				if ( ! empty( $image['images']['fixed_width_small']['url'] ) ) {
					$preview = $image['images']['fixed_width_small']['url'];
				} elseif ( ! empty( $image['images']['preview_gif']['url'] ) ) {
					$preview = $image['images']['preview_gif']['url'];
				}

				$full = '';
				if ( ! empty( $image['images']['original']['url'] ) ) {
					$full = $image['images']['original']['url'];
				} elseif ( ! empty( $image['images']['downsized']['url'] ) ) {
					$full = $image['images']['downsized']['url'];
				}

				if ( empty( $preview ) || empty( $full ) ) {
					continue;
				}

				$images[] = array(
					'preview' => $preview,
					'image'   => $full,
				);
			}
		}

		ob_start();
		foreach ( $images as $im ) {
			echo '<img class="um-gif-img" data-um_gif_img data-image="' . esc_attr( $im['image'] ) . '" src="' . esc_url( $im['preview'] ) . '" />';
		}

		$contents = ob_get_clean();

		$next = '';
		if ( ! empty( $result['data'] ) ) {
			$count       = count( $result['data'] );
			$offset      = ! empty( $query_args['offset'] ) ? absint( $query_args['offset'] ) : 0;
			$next_offset = $offset + $per_page;
			$next        = $count >= $per_page ? $next_offset : '';
		}

		wp_send_json_success(
			array(
				'html' => UM()->ajax()->esc_html_spaces( $contents ),
				'next' => $next,
			)
		);
	}
}
