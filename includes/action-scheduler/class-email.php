<?php
namespace um\action_scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\action_scheduler\Email' ) ) {

	/**
	 * Class Email
	 *
	 * @package um\action_scheduler
	 */
	class Email {

		public function __construct() {
			add_action( 'um_send_deleted_user_email', array( $this, 'send_deleted_user_email' ), 10, 2 );
		}

		/**
		 * Send an email after user account was deleted.
		 *
		 * @param string $user_email User email.
		 * @param string $template   Template name.
		 */
		public function send_deleted_user_email( $user_email, $template ) {
			if ( empty( $user_email ) && empty( $template ) ) {
				return;
			}

			UM()->mail()->send( $user_email, $template );
		}
	}
}
