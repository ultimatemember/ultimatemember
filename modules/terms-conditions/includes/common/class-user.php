<?php
namespace umm\terms_conditions\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class User
 * @package umm\terms_conditions\includes\common
 */
class User {


	/**
	 * User constructor.
	 */
	public function __construct() {
		add_filter( 'um_before_save_filter_submitted', array( &$this, 'add_agreement_date' ), 10, 2 );
		add_filter( 'um_user_submitted_registration_formatted_before_fields', array( &$this, 'add_user_registration_data' ), 10, 3 );
	}


	/**
	 * @param $submitted
	 * @param $args
	 *
	 * @return mixed
	 */
	function add_agreement_date( $submitted, $args ) {
		if ( isset( $submitted['use_terms_conditions_agreement'] ) ) {
			$submitted['use_terms_conditions_agreement'] = time();
		}

		return $submitted;
	}


	/**
	 * @param string $output
	 * @param array $submitted_data
	 *
	 * @uses um_user_submitted_display()
	 *
	 * @return string
	 */
	function add_user_registration_data( $output, $submitted_data ) {
		if ( ! empty( $submitted['use_terms_conditions_agreement'] ) ) {
			$k = ! empty( $submitted['timestamp'] ) ? 'timestamp' : 'use_terms_conditions_agreement';

			$output .= um_user_submitted_display( $k, __( 'Terms&Conditions Applied', 'ultimate-member' ), $submitted_data );
		}

		return $output;
	}
}
