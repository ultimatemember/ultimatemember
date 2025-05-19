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
			add_filter( 'um_action_scheduler_is_hook_enabled', array( $this, 'is_enabled' ), 10, 2 );
			add_action( 'um_dispatch_email', array( $this, 'send' ), 10, 3 );
		}

		public function is_enabled( $is_enabled, $hook ) {
			if ( 'um_dispatch_email' === $hook ) {
				$is_enabled = UM()->options()->get( 'enable_as_email_sending' );
			}
			return $is_enabled;
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

			// @todo Workaround for now. Maybe we need to put base $user_id everytime
			if ( array_key_exists( 'fetch_user_id', $args ) ) {
				// When Action Scheduler is enabled, email sending script is located out of basic functionality, so we need to fetch the user for replace placeholders.
				if ( UM()->maybe_action_scheduler()->is_hook_enabled( 'um_dispatch_email' ) ) {
					um_fetch_user( $args['fetch_user_id'] );
				}
				unset( $args['fetch_user_id'] );
			}

			UM()->mail()->send( $user_email, $template, $args );
		}
	}
}
