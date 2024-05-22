<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Forms
 *
 * @package um\ajax
 */
class Forms {

	/**
	 * Forms constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_um_get_icons', array( $this, 'get_icons' ) );
	}

	/**
	 * Get the list of the icons.
	 */
	public function get_icons() {
		UM()->admin()->check_ajax_nonce();

		$search_request = ! empty( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
		$page           = ! empty( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1;
		$per_page       = 50;

		UM()->setup()->set_icons_options();

		$um_icons_list = get_option( 'um_icons_list' );
		if ( ! empty( $search_request ) ) {
			$um_icons_list = array_filter(
				$um_icons_list,
				function( $item ) use ( $search_request ) {
					$result = array_filter(
						$item['search'],
						function( $search_item ) use ( $search_request ) {
							return stripos( $search_item, $search_request ) !== false;
						}
					);
					return count( $result ) > 0;
				}
			);
		}

		$total_count = count( $um_icons_list );

		$um_icons_list = array_slice( $um_icons_list, $per_page * ( $page - 1 ), $per_page );

		wp_send_json_success(
			array(
				'icons'       => $um_icons_list,
				'total_count' => $total_count,
			)
		);
	}
}
