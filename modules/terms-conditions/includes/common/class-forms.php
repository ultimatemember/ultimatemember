<?php
namespace umm\terms_conditions\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Forms
 * @package umm\terms_conditions\includes\common
 */
class Forms {


	/**
	 * Forms constructor.
	 */
	public function __construct() {
		add_action( 'um_after_form_fields', array( &$this, 'display_option' ) );
		add_action( 'um_submit_form_register', array( &$this, 'agreement_validation' ), 9 );
	}


	/**
	 * @param array $args
	 */
	function display_option( $args ) {
		if ( isset( $args['use_terms_conditions'] ) && $args['use_terms_conditions'] == 1 ) {
			$args['args'] = $args;
			um_get_template( 'form-fields.php', $args, 'terms-conditions' );
		}
	}


	/**
	 * @param array $args
	 */
	function agreement_validation( $args ) {
		$terms_conditions = get_post_meta( $args['form_id'], '_um_register_use_terms_conditions', true );

		if ( $terms_conditions && ! isset( $args['submitted']['use_terms_conditions_agreement'] ) ) {
			$error_text = isset( $args['use_terms_conditions_error_text'] ) ? $args['use_terms_conditions_error_text'] : __( 'You must agree to our terms & conditions', 'ultimate-member' );
			UM()->form()->add_error('use_terms_conditions_agreement', $error_text );
		}
	}
}
