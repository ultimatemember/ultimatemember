<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Restrictions
 *
 * @package um\ajax
 */
class Restrictions {

	public function __construct() {
		add_action( 'wp_ajax_um_restriction_rules_order', array( $this, 'um_restriction_rules_order' ) );
	}

	/**
	 * Update order of restrictions rules.
	 */
	public function um_restriction_rules_order() {
		check_ajax_referer( 'um_restriction_rules_order' );
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! empty( $_POST['indexes'] ) ) {
			$indexes = array_map(
				function ( $value ) {
					return absint( $value );
				},
				$_POST['indexes']
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification

		$um_rules = get_option( 'um_restriction_rules', array() );
		foreach ( $um_rules as $key => $rule ) {
			$um_rules[ $key ]['_um_priority'] = $indexes[ $key ];
		}

		update_option( 'um_restriction_rules', $um_rules );

		wp_send_json_success( 'success' );
	}
}
