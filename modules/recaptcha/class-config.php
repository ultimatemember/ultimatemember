<?php
namespace umm\recaptcha;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Config
 *
 * @package umm\recaptcha
 */
class Config {


	/**
	 * @var array
	 */
	var $error_codes = array();


	/**
	 * Config constructor.
	 */
	public function __construct() {
	}


	/**
	 * Get variable from config
	 *
	 * @param string $key
	 *
	 * @return mixed
	 *
	 * @since 3.0
	 */
	public function get( $key ) {
		if ( empty( $this->$key ) ) {
			call_user_func( array( &$this, 'init_' . $key ) );
		}
		return apply_filters( 'um_recaptcha_config_get', $this->$key, $key );
	}


	/**
	 *
	 */
	public function init_error_codes() {
		$this->error_codes =  array(
			'missing-input-secret'   => __( '<strong>Error</strong>: The secret parameter is missing.', 'ultimate-member' ),
			'invalid-input-secret'   => __( '<strong>Error</strong>: The secret parameter is invalid or malformed.', 'ultimate-member' ),
			'missing-input-response' => __( '<strong>Error</strong>: The response parameter is missing.', 'ultimate-member' ),
			'invalid-input-response' => __( '<strong>Error</strong>: The response parameter is invalid or malformed.', 'ultimate-member' ),
			'bad-request'            => __( '<strong>Error</strong>: The request is invalid or malformed.', 'ultimate-member' ),
			'timeout-or-duplicate'   => __( '<strong>Error</strong>: The response is no longer valid: either is too old or has been used previously.', 'ultimate-member' ),
			'undefined'              => __( '<strong>Error</strong>: Undefined reCAPTCHA error.', 'ultimate-member' ),
		);
	}
}
