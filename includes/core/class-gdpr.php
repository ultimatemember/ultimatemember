<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\GDPR' ) ) {

	/**
	 * Class Admin_GDPR
	 * @package um\core
	 */
	class GDPR {

		/**
		 * Admin_GDPR constructor.
		 */
		public function __construct() {
			add_action( 'um_submit_form_register', array( &$this, 'agreement_validation' ), 9, 2 );

			add_filter( 'um_whitelisted_metakeys', array( &$this, 'extend_whitelisted' ), 10, 2 );

			add_filter( 'um_before_save_filter_submitted', array( &$this, 'add_agreement_date' ) );
			add_action( 'um_after_form_fields', array( &$this, 'display_option' ) );
		}

		/**
		 * @param $args
		 */
		public function display_option( $args ) {
			if ( ! empty( $args['use_gdpr'] ) ) {
				if ( UM()->is_new_ui() ) {
					UM()->get_template( 'v3/gdpr-register.php', '', array( 'args' => $args ), true );
				} else {
					UM()->get_template( 'gdpr-register.php', '', array( 'args' => $args ), true );
				}
			}
		}

		/**
		 * @param array $submitted_data
		 * @param array $form_data
		 */
		public function agreement_validation( $submitted_data, $form_data ) {
			$gdpr_enabled        = get_post_meta( $form_data['form_id'], '_um_register_use_gdpr', true );
			$use_gdpr_error_text = get_post_meta( $form_data['form_id'], '_um_register_use_gdpr_error_text', true );
			$use_gdpr_error_text = ! empty( $use_gdpr_error_text ) ? $use_gdpr_error_text : __( 'Please agree privacy policy.', 'ultimate-member' );

			if ( $gdpr_enabled && empty( $submitted_data['submitted']['use_gdpr_agreement'] ) ) {
				UM()->form()->add_error( 'use_gdpr_agreement', $use_gdpr_error_text );
			}
		}

		/**
		 * @param array $metakeys
		 * @param array $form_data
		 */
		public function extend_whitelisted( $metakeys, $form_data ) {
			$gdpr_enabled = get_post_meta( $form_data['form_id'], '_um_register_use_gdpr', true );
			if ( ! empty( $gdpr_enabled ) ) {
				$metakeys[] = 'use_gdpr_agreement';
			}
			return $metakeys;
		}

		/**
		 * @param $submitted
		 *
		 * @return mixed
		 */
		public function add_agreement_date( $submitted ) {
			if ( isset( $submitted['use_gdpr_agreement'] ) ) {
				$submitted['use_gdpr_agreement'] = current_time( 'mysql', true );
			}

			return $submitted;
		}
	}
}
