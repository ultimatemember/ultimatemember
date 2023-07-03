<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Secure' ) ) {

	/**
	 * Class Secure
	 *
	 * @package um\core
	 *
	 * @since 2.6.8
	 */
	class Secure {

		/**
		 * Login constructor.
		 * @since 2.6.8
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			add_action( 'um_before_login_fields', array( $this, 'reset_password_notice' ) );

			add_action( 'um_before_login_fields', array( $this, 'under_maintanance_notice' ) );

			add_filter( 'um_settings_structure', array( $this, 'add_settings' ) );

			add_action( 'um_submit_form_register', array( $this, 'block_register_forms' ) );

			add_action( 'um_user_login', array( $this, 'login_validate_expired_pass' ), 1 );

			add_action( 'validate_password_reset', array( $this, 'avoid_old_password' ), 1, 2 );

			add_action( 'um_after_save_registration_details', array( $this, 'secure_user_capabilities' ), 10, 3 );
		}

		/**
		 * Admin Init
		 *
		 * @since 2.6.8
		 */
		public function admin_init() {
			if ( isset( $_REQUEST['um_secure_expire_all_sessions'] ) ) {
				if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'um-secure-expire-session-nonce' ) ) {
					// This nonce is not valid.
					wp_die( esc_html_e( 'Security check', 'ultimate-member' ) );
				}

				// Get an instance of WP_User_Meta_Session_Tokens
				$sessions_manager = \WP_Session_Tokens::get_instance( null );
				// Remove all the session data for all users.
				$sessions_manager->drop_sessions();
				wp_safe_redirect( admin_url() );
				exit;
			}

		}

		/**
		 * Add Login notice for Reset Password
		 *
		 * @param array $args
		 * @since 2.6.8
		 */
		public function reset_password_notice( $args ) {

			if ( ! UM()->options()->get( 'display_login_form_notice' ) ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['notice'] ) || 'expired_password' !== $_REQUEST['notice'] ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			echo "<p class='um-notice warning'>";
			echo wp_kses(
				sprintf(
					// translators: One-time change requires you to reset your password
					__( '<strong>Important:</strong> Your password has expired. This (one-time) change requires you to reset your password. Please <a href="%s">click here</a> to reset your password via Email.', 'ultimate-member' ),
					um_get_core_page( 'password-reset' )
				),
				array(
					'strong' => array(),
					'a'      => array(
						'href' => array(),
					),
				)
			);
			echo '</p>';
		}

		/**
		 * Add Login notice for Under Maintance
		 *
		 * @param array $args
		 * @since 2.6.8
		 */
		public function under_maintanance_notice( $args ) {

			if ( ! UM()->options()->get( 'lock_register_forms' ) ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['notice'] ) || 'maintanance' !== $_REQUEST['notice'] ) {
				return;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			echo "<p class='um-notice warning'>";
			echo wp_kses(
				sprintf(
					// translators: One-time change requires you to reset your password
					__( '<strong>Important:</strong> This site is currently under maintenance. Please check back soon.', 'ultimate-member' ),
					um_get_core_page( 'password-reset' )
				),
				array(
					'strong' => array(),
					'a'      => array(
						'href' => array(),
					),
				)
			);
			echo '</p>';
		}

		/**
		 * Register Secure Settings
		 *
		 * @param array $settings
		 * @since 2.6.8
		 */
		public function add_settings( $settings ) {
			$nonce                          = wp_create_nonce( 'um-secure-expire-session-nonce' );
			$count_users                    = count_users();
			$settings['secure']['title']    = __( 'Secure', 'ultimate-member' );
			$settings['secure']['sections'] =
			array(
				'' =>
				array(
					'title'  => __( 'Secure Ultimate Member', 'ultimate-member' ),
					'fields' => array(
						array(
							'id'          => 'lock_register_forms',
							'type'        => 'checkbox',
							'label'       => __( 'Lock All Register Forms', 'ultimate-member' ),
							'description' => __( 'This prevents all users from registering with Ultimate Member on your site temporarily.', 'ultimate-member' ),
						),
						array(
							'id'          => 'display_login_form_notice',
							'type'        => 'checkbox',
							'label'       => __( 'Display Login form notice to reset their passwords', 'ultimate-member' ),
							'description' => __( 'Enforces users to reset their passwords( one-time ) and prevent from entering old password.', 'ultimate-member' ),
						),
						array(
							'id'          => 'force_reset_passwords',
							'type'        => 'info_text',
							'label'       => __( 'Expire All Users Sessions', 'ultimate-member' ),
							'value'       => '<a href="' . admin_url( '?um_secure_expire_all_sessions=1&_wpnonce=' . esc_attr( $nonce ) ) . '" class="button">Logout Users(' . esc_attr( $count_users['total_users'] ) . ') </a>',
							'description' => __( 'This will logout all users on your site and forces them to reset passwords when <strong>"Display Login form notice to reset their passwords" is enabled.</strong>', 'ultimate-member' ),
						),
					),
				),
			);

			return $settings;
		}

		/**
		 * Block all UM Register form submissions.
		 *
		 * @param array $args Form settings.
		 * @since 2.6.8
		 */
		public function block_register_forms( $args ) {
			if ( UM()->options()->get( 'lock_register_forms' ) ) {
				$login_url = add_query_arg( 'notice', 'maintanance', um_get_core_page( 'login' ) );
				nocache_headers();
				wp_safe_redirect( $login_url );
				exit;
			}
		}

		/**
		 * Validate when user has expired password
		 *
		 * @param array $submitted_data
		 * @since 2.6.8
		 */
		public function login_validate_expired_pass( $submitted_data ) {

			if ( UM()->options()->get( 'display_login_form_notice' ) ) {
				$has_expired = get_user_meta( um_user( 'ID' ), 'um_secure_has_reset_password', true );
				if ( ! $has_expired ) {
					$login_url = add_query_arg( 'notice', 'expired_password', um_get_core_page( 'login' ) );
					wp_safe_redirect( $login_url );
					exit;
				}
			}
		}

		/**
		 * Prevent users from using Old Passwords on Password Reset form
		 *
		 * @param object $errors
		 * @param object $user
		 * @since 2.6.8
		 */
		public function avoid_old_password( $errors, $user ) {
			$wp_hasher = new \PasswordHash( 8, true );

			if ( isset( $_REQUEST['user_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$new_user_pass = $_REQUEST['user_password']; // phpcs:ignore WordPress.Security.NonceVerification
				if ( $wp_hasher->CheckPassword( $new_user_pass, $user->data->user_pass ) ) {
					UM()->form()->add_error( 'user_password', __( 'Your new password cannot be same as old password.', 'ultimate-member' ) );
					$errors->add( 'block_old_password', __( 'Your new password cannot be same as old password.', 'ultimate-member' ) );
				} else {
					update_user_meta( $user->data->ID, 'um_secure_has_reset_password', true );
					update_user_meta( $user->data->ID, 'um_secure_has_reset_password__timestamp', current_time( 'mysql' ) );
				}
			}
		}

		/**
		 * Secure user capabilities and revoke administrative ones
		 */
		public function secure_user_capabilities( $user_id, $submitted_data, $form_data ) {
			global $wpdb;
			// Fetch the WP_User object of our user.
			um_fetch_user( $user_id );
			$user             = new \WP_User( $user_id );
			$has_admin_cap    = false;
			$disallowed_roles = array( 'administrator' );
			foreach ( $disallowed_roles as $role ) {
				$admin_caps = array_keys( get_role( $role )->capabilities );
				foreach ( $admin_caps as $i => $cap ) {
					/**
					 * When there's at least one administrator cap added to the user,
					 * immediately revoke caps and mark as rejected.
					 */
					if ( $user->has_cap( $cap ) ) {
						$has_admin_cap = true;
						$this->revoke_caps( $user );
						break;
					}
				}
			}

			/**
			 * Double-check if *_user_level has been modified with the highest level
			 * when user has no administrator capabilities.
			 */
			$user_level = um_user( $wpdb->get_blog_prefix() . 'user_level' );
			if ( ! empty( $user_level ) ) {
				$arr_levels = array( 'level_10' );
				foreach ( $arr_levels as $level ) {
					if ( $level === $user_level ) {
						$this->revoke_caps( $user );
						$has_admin_cap = true;
						break;
					}
				}
			}

			if ( $has_admin_cap ) {
				wp_die( esc_html__( 'Security Check!', 'ultimate-member' ) );
			}
		}

		/**
		 * Revoke Caps
		 *
		 * @param string $cap Capability slug
		 * @param object $user \WP_User
		 */
		public function revoke_caps( $user ) {

			$user->remove_all_caps();
			$user->set_role( 'rejected' ); // Set role to rejected
			UM()->user()->set_status( 'rejected' ); // Set UM role to rejected
		}

	}


}