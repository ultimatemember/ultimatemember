<?php
namespace um\common;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Validation' ) ) {

	/**
	 * Class Validation.
	 *
	 * @package um\common
	 */
	class Validation {

		/**
		 * Validation constructor.
		 */
		public function __construct() {
		}

		/**
		 * @param string $email Email to check.
		 *
		 * @return array
		 */
		public function email_is_blocked( $email ) {
			$emails = UM()->options()->get( 'blocked_emails' );
			if ( empty( $emails ) ) {
				return array( false, 'empty' );
			}

			if ( ! is_email( $email ) ) {
				return array( false, 'invalid' );
			}

			$emails = strtolower( $emails );
			$emails = array_map( 'rtrim', explode( "\n", $emails ) );

			if ( in_array( strtolower( $email ), $emails ) ) {
				return array( true, 'email' );
			}

			$domain       = explode( '@', $email );
			$check_domain = str_replace( $domain[0], '*', $email );

			if ( in_array( strtolower( $check_domain ), $emails ) ) {
				return array( true, 'domain' );
			}

			return array( false, 'valid' );
		}
	}
}
