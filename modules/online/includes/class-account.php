<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Account
 *
 * @package umm\online\includes
 */
class Account {


	/**
	 * Account constructor.
	 */
	function __construct() {
		add_filter( 'um_account_tab_privacy_fields', array( &$this, 'add_privacy_field' ), 10, 2 );
	}


	/**
	 * Shows the online field in account page
	 *
	 * @param string $args
	 * @param array $shortcode_args
	 *
	 * @return string
	 */
	function add_privacy_field( $args, $shortcode_args ) {
		return $args . ',_hide_online_status';
	}
}
