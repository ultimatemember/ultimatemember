<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Router' ) ) {


	/**
	 * Class Router
	 * @package um\core
	 */
	class Router {


		/**
		 * Run backend process
		 */
		function backend_requests() {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
			$user_id = get_current_user_id();

			if ( empty( $_REQUEST['um_action'] ) )
				exit( __( 'Wrong action', 'ultimate-member' ) );

			if ( empty( $_REQUEST['um_resource'] ) )
				exit( __( 'Wrong resource', 'ultimate-member' ) );

			if ( $_REQUEST['um_action'] == 'route' )
				$verify = wp_verify_nonce( $_REQUEST['um_verify'], $ip . $user_id . $_REQUEST['um_resource'] . $_REQUEST['um_method'] );
			else
				$verify = wp_verify_nonce( $_REQUEST['um_verify'], $ip . $user_id . $_REQUEST['um_action'] . $_REQUEST['um_resource'] );

			if ( empty( $verify ) )
				exit( __( 'Wrong nonce', 'ultimate-member' ) );

			$this->request_process( array(
				'route'     => $_REQUEST['um_resource'],
				'method'    => $_REQUEST['um_method']
			) );

			/*if ($_REQUEST['um_action'] == 'download' || $_REQUEST['um_action'] == 'view') {
                WO()->downloader()->set_type( $_REQUEST['um_action'] )->process( array(
                    'id' => $_REQUEST['um_id'],
                    'resource' => $_REQUEST['um_resource'],
                    'action' => $_REQUEST['um_action']
                ) );
            } else if ($_REQUEST['um_action'] == 'route') {
                $this->request_process( array(
                    'route' => $_REQUEST['um_resource'],
                    'method' => $_REQUEST['um_method']
                ) );
            }*/
		}


		/**
		 * Request process
		 *
		 * @param $params array
		 * @return bool
		 */
		function request_process( $params ) {
			if ( empty( $params['route'] ) || empty( $params['method'] ) )
				return false;

			$route = str_replace( array( '!', '/' ), '\\', $params['route'] );

			if ( ! class_exists( $route ) )
				return false;

			if ( method_exists( $route, 'instance' ) )
				$object = $route::instance();
			else
				$object = new $route();

			if ( ! method_exists( $object, $params['method'] ) )
				return false;


			call_user_func( array( &$object, $params['method'] ) );
			return true;
		}


		/**
		 * Run frontend process
		 */
		function frontend_requests() {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
			$user_id = get_current_user_id();
			if ( ! get_query_var( 'um_action' ) )
				exit( __( 'Wrong action', 'ultimate-member' ) );

			if ( ! get_query_var( 'um_resource' ) )
				exit( __( 'Wrong resource', 'ultimate-member' ) );

			$verify = false;
			if ( get_query_var( 'um_action' ) == 'route' )
				$verify = wp_verify_nonce( get_query_var( 'um_verify' ), $ip . $user_id . get_query_var( 'um_resource' ) . get_query_var( 'um_method' ) );

			if ( $verify ) {
				if ( get_query_var( 'um_action' ) == 'route' ) {
					$this->request_process( array(
						'route' => get_query_var( 'um_resource' ),
						'method' => get_query_var( 'um_method' )
					) );
				}
			} else {
				exit( __( 'Wrong nonce', 'ultimate-member' ) );
			}
		}

	}
}