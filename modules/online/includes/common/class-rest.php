<?php
namespace umm\online\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class REST
 *
 * @package umm\online\includes\common
 */
class REST {


	/**
	 * REST constructor.
	 */
	public function __construct() {
		add_filter( 'um_rest_api_get_stats', array( &$this, 'rest_api_get_stats' ), 10, 1 );
	}


	/**
	 * Get online users count via REST API
	 *
	 * @param array $response
	 *
	 * @return array
	 */
	function rest_api_get_stats( $response ) {
		$response['stats']['total_online'] = UM()->module( 'online' )->get_users( 'count' );
		return $response;
	}
}
