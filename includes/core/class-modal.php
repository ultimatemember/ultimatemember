<?php
namespace um\core;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Modal' ) ) {


	/**
	 * Class Modal
	 *
	 * @package um\core
	 */
	class Modal {


		/**
		 * Modal constructor.
		 */
		function __construct() {
			add_action( 'wp_footer', array( &$this, 'load_modal_content' ), $this->get_priority() );
		}


		/**
		 * @return int
		 */
		function get_priority() {
			return apply_filters( 'um_core_includes_modals_priority', 9 );
		}


		/**
		 * Load modal content
		 */
		function load_modal_content() {

			if ( ! is_admin() ) {
				$modal_templates = glob( UM_PATH . 'templates/modal/*.php' );

				if ( ! empty( $modal_templates ) ) {
					foreach ( $modal_templates as $modal_content ) {
						include_once $modal_content;
					}
				}
			}

		}

	}
}
