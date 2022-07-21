<?php
namespace umm\online\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Account
 *
 * @package umm\online\includes\frontend
 */
class Account {


	/**
	 * Account constructor.
	 */
	public function __construct() {
		add_filter( 'um_account_tab_privacy_fields', array( &$this, 'add_privacy_field' ), 10, 1 );
	}


	/**
	 * Shows the online field in account page
	 *
	 * @param string $args
	 *
	 * @return string
	 */
	public function add_privacy_field( $args ) {
		return $args . ',_hide_online_status';
	}
}
