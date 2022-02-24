<?php
namespace umm\member_directory\includes;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Common
 *
 * @package umm\member_directory\includes
 */
class Common {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'um_after_form_fields', array( &$this, 'display_option' ) );
		add_action( 'um_submit_form_register', array( &$this, 'agreement_validation' ), 9 );

		add_filter( 'um_before_save_filter_submitted', array( &$this, 'add_agreement_date' ), 10, 2 );
		add_filter( 'um_user_submitted_registration_formatted_before_fields', array( &$this, 'add_user_registration_data' ), 10, 3 );
	}


	/**
	 * @param $args
	 */
	function display_option( $args ) {
		if ( isset( $args['use_terms_conditions'] ) && $args['use_terms_conditions'] == 1 ) {
			$args['args'] = $args;
			um_get_template( 'form-fields.php', $args, 'terms-conditions' );
		}
	}


	/**
	 * @param $args
	 */
	function agreement_validation( $args ) {
		$terms_conditions = get_post_meta( $args['form_id'], '_um_register_use_terms_conditions', true );

		if ( $terms_conditions && ! isset( $args['submitted']['use_terms_conditions_agreement'] ) ) {
			$error_text = isset( $args['use_terms_conditions_error_text'] ) ? $args['use_terms_conditions_error_text'] : __( 'You must agree to our terms & conditions', 'ultimate-member' );
			UM()->form()->add_error('use_terms_conditions_agreement', $error_text );
		}
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
