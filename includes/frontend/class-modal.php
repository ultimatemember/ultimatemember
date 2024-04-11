<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Modal
 *
 * @package um\frontend
 */
class Modal {

	/**
	 * Modal constructor.
	 */
	public function __construct() {
		add_action( 'wp_footer', array( &$this, 'load_modal_content' ), $this->get_priority() );
	}

	/**
	 * @return int
	 */
	private function get_priority() {
		return apply_filters( 'um_core_includes_modals_priority', 9 );
	}

	/**
	 * Load modal content.
	 */
	public function load_modal_content() {
		$modal_templates = glob( UM_PATH . 'templates/modal/*.php' );
		$modal_templates = array_map( 'basename', $modal_templates );
		if ( ! empty( $modal_templates ) ) {
			foreach ( $modal_templates as $modal_content ) {
				UM()->get_template( 'modal/' . $modal_content, '', array(), true );
			}
		}
	}
}
