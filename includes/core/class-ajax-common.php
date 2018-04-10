<?php
namespace um\core;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\AJAX_Common' ) ) {


	/**
	 * Class AJAX_Common
	 * @package um\core
	 */
	class AJAX_Common {


		/**
		 * AJAX_Common constructor.
		 */
		function __construct() {
			// UM_EVENT => nopriv
			$ajax_actions = array(
				'router'   => false
			);

			foreach ( $ajax_actions as $action => $nopriv ) {

				add_action( 'wp_ajax_um_' . $action, array( $this, $action ) );

				if ( $nopriv )
					add_action( 'wp_ajax_nopriv_um_' . $action, array( $this, $action ) );

			}


			/**
			 * Fallback for ajax urls
			 * @uses action hooks: wp_head, admin_head
			 */
			//add_action( 'wp_head', array( $this, 'ultimatemember_ajax_urls' ) );
			//add_action( 'admin_head', array( $this, 'ultimatemember_ajax_urls' ) );

		}


		/**
		 * Router method
		 */
		function router() {
			$router = new Router();
			$router->backend_requests();
		}
	}
}