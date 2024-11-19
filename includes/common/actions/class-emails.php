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
			add_action( 'um_before_email_notification_sending', array( $this, 'before_email_send' ), 10, 2 );
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
				if ( UM()->maybe_action_scheduler()->is_enabled() ) {
					um_fetch_user( $args['fetch_user_id'] );
				}
				unset( $args['fetch_user_id'] );
			}

			UM()->mail()->send( $user_email, $template, $args );
		}

		/**
		 * Add some custom placeholders when sending via Action Scheduler.
		 *
		 * @todo Workaround for now. Maybe we need to handle email placeholders in email class where $user_id is fetched everytime
		 *
		 * @param string $email
		 * @param string $template
		 *
		 * @return void
		 */
		public function before_email_send( $email, $template ) {
			if ( ! UM()->maybe_action_scheduler()->is_enabled() ) {
				return;
			}

			if ( 'checkmail_email' === $template ) {
				add_filter( 'um_template_tags_patterns_hook', array( UM()->user(), 'add_activation_placeholder' ) );
				add_filter( 'um_template_tags_replaces_hook', array( UM()->user(), 'add_activation_replace_placeholder' ) );
			} elseif ( 'welcome_email' === $template || 'approved_email' === $template || 'resetpw_email' === $template ) {
				add_filter( 'um_template_tags_patterns_hook', array( UM()->password(), 'add_placeholder' ) );
				add_filter( 'um_template_tags_replaces_hook', array( UM()->password(), 'add_replace_placeholder' ) );
			}
		}
	}
}
