<?php
namespace um\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\GDPR' ) ) {


	/**
	 * Class Admin_GDPR
	 * @package um\core
	 */
	class GDPR {


		/**
		 * Admin_GDPR constructor.
		 */
		function __construct() {
			add_action( 'um_submit_form_register', array( &$this, 'agreement_validation' ), 9 );

			add_filter( 'um_before_save_filter_submitted', array( &$this, 'add_agreement_date' ), 10, 2 );
			add_filter( 'um_email_registration_data', array( &$this, 'email_registration_data' ), 10, 1 );

			add_action( 'um_after_form_fields', array( &$this, 'display_option' ) );
		}


		/**
		 * @param $args
		 */
		function display_option( $args ) {

			if ( isset( $args['use_gdpr'] ) && $args['use_gdpr'] == 1 ) {
				
				$template_path = trailingslashit( get_stylesheet_directory() ). '/ultimate-member/templates/gdpr-register.php';

				if ( file_exists( $template_path ) ) {
		            require $template_path;
		        } else {
		            require um_path . 'templates/gdpr-register.php';
		        }
			}
			
		}


		/**
		 * @param $args
		 */
		function agreement_validation( $args ) {
			$gdpr_enabled = get_post_meta( $args['form_id'], '_um_register_use_gdpr', true );

			if ( $gdpr_enabled && ! isset( $args['submitted']['use_gdpr_agreement'] ) ) {
				UM()->form()->add_error( 'use_gdpr_agreement', isset( $args['use_gdpr_error_text'] ) ? $args['use_gdpr_error_text'] : '' );
			}
		}


		/**
		 * @param $submitted
		 * @param $args
		 *
		 * @return mixed
		 */
		function add_agreement_date( $submitted, $args ) {
			if ( isset( $submitted['use_gdpr_agreement'] ) ) {
				$submitted['use_gdpr_agreement'] = time();
			}

			return $submitted;
		}


		/**
		 * @param $submitted
		 *
		 * @return mixed
		 */
		function email_registration_data( $submitted ) {
			if ( ! empty( $submitted['use_gdpr_agreement'] ) ) {
				$timestamp = ! empty( $submitted['timestamp'] ) ? $submitted['timestamp'] : $submitted['use_gdpr_agreement'];

				$submitted['GDPR Applied'] = date( "d M Y H:i", $timestamp );
				unset( $submitted['use_gdpr_agreement'] );
			}

			return $submitted;
		}

	}

}