<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Shortcode
 *
 * @package umm\online\includes
 */
class Shortcode {


	/**
	 * Shortcode constructor.
	 */
	function __construct() {
		add_shortcode( 'ultimatemember_online', array( &$this, 'ultimatemember_online' ) );
	}


	/**
	 * Online users list shortcode
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	function ultimatemember_online( $args = array() ) {
		$defaults = array(
			'max'   => 11,
			'roles' => 'all',
		);
		$args = wp_parse_args( $args, $defaults );

		$args['online'] = UM()->module( 'online' )->get_users();
		$template = ( $args['online'] && count( $args['online'] ) > 0 ) ? 'online' : 'nobody';

		return um_get_template_html( "{$template}.php", $args, 'online' );
	}
}
