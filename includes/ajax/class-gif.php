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
		$api_key = UM()->options()->get( 'tenor_api_key' );
		if ( empty( $api_key ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'um_get_gif_list', 'nonce' );

		$images  = array();
		$keyword = 'Hello';
		if ( isset( $_POST['gif_search'] ) ) {
			$keyword = sanitize_text_field( $_POST['gif_search'] );
		}
		$apiUrl       = 'https://tenor.googleapis.com/v2/search?key=' . $api_key . '&q=' . $keyword;
		$response     = wp_remote_get( $apiUrl );

		var_dump( $response );
		exit;

		$responseBody = wp_remote_retrieve_body( $response );
		$result       = json_decode( $responseBody, true );
		foreach ( $result['results'] as $image ) {
			$images[] = array(
				'preview' => $image['media_formats']['nanogif']['url'],
				'image'   => $image['media_formats']['gif']['url'],
			);
		}

		ob_start();
		echo '<div class="imges">';
		foreach ( $images as $im ) {
			echo '<img data-um_gif_img data-image="' . $im['image'] . '" src="' . $im['preview'] . '"/>';
		}
		echo '</div>';

		$contents = ob_get_contents();

		wp_send_json_success( UM()->ajax()->esc_html_spaces( $contents ) );
	}
}
