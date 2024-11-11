<?php
namespace um\common\actions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\actions\Emails' ) ) {

	/**
	 * Class Emails
	 *
	 * @since 2.9.0
	 *
	 * @package um\common\actions
	 */
	class Emails {

		public function __construct() {
			add_action( 'um_dispatch_email', array( $this, 'send' ), 10, 3 );
		}

		/**
		 * Send an email
		 *
		 * @param string $user_email User email.
		 * @param string $template   Template name.
		 * @param array  $args       Email additional arguments.
		 */
		public function send( $user_email, $template, $args = array() ) {
			if ( empty( $user_email ) && empty( $template ) ) {
				return;
			}

			UM()->mail()->send( $user_email, $template, $args );
		}
	}
}
