<?php
namespace umm\online\includes\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
		add_filter( 'um_account_privacy_fields_update', array( &$this, 'privacy_fields_update' ), 10, 1 );
	}


	/**
	 * Shows the online field in account page
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function add_privacy_field( $args ) {
		$user_id = get_current_user_id();

		$hide_online_status = 'yes';
		if ( get_user_meta( $user_id, '_hide_online_status', true ) ) {
			$hide_online_status_meta = get_user_meta( $user_id, '_hide_online_status', true );
			$hide_online_status      = $hide_online_status_meta[0];
		}

		$args['fields'][] = array(
			'type'    => 'radio',
			'label'   => __( 'Show my online status?', 'ultimate-member-pro' ),
			'helptip' => __( 'Do you want other people to see that you are online?', 'ultimate-member-pro' ),
			'id'      => '_hide_online_status',
			'value'   => $hide_online_status,
			'options' => array(
				'no'  => __( 'No', 'ultimate-member' ),
				'yes' => __( 'Yes', 'ultimate-member' ),
			),
		);

		return $args;
	}


	/**
	 * Extend privacy tab update fields
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function privacy_fields_update( $args ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here
		$args['_hide_online_status'] = array( sanitize_key( $_POST['_hide_online_status'] ) );

		return $args;
	}
}
