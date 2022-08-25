<?php
namespace umm\recaptcha\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'umm\recaptcha\includes\admin\Site_Health' ) ) {


	/**
	 * Class Site_Health
	 *
	 * @package um\admin
	 */
	class Site_Health {


		/**
		 * Site_Health constructor.
		 */
		public function __construct() {
			add_action( 'debug_information', array( &$this, 'debug_information' ), 30, 1 );
			add_action( 'um_debug_information_register_form', array( &$this, 'um_debug_information_register_form' ), 10, 2 );
			add_action( 'um_debug_information_login_form', array( &$this, 'um_debug_information_login_form' ), 10, 2 );
		}


		/**
		 * Add our data to Site Health information.
		 *
		 * @since 3.0
		 *
		 * @param array $info The Site Health information.
		 *
		 * @return array The updated Site Health information.
		 */
		public function debug_information( $info ) {
			$labels = array(
				'yes'     => __( 'Yes', 'ultimate-member' ),
				'no'      => __( 'No', 'ultimate-member' ),
				'all'     => __( 'All', 'ultimate-member' ),
				'default' => __( 'Default', 'ultimate-member' ),
				'no-dir'  => __( 'No directories', 'ultimate-member' ),
			);

			$info['ultimate-member-recaptcha'] = array(
				'label'       => __( 'Ultimate Member reCAPTCHA', 'ultimate-member' ),
				'description' => __( 'This debug information about Ultimate Member Online module.', 'ultimate-member' ),
				'fields'      => array(
					'um-g_recaptcha_status' => array(
						'label' => __( 'Enable Google reCAPTCHA', 'ultimate-member' ),
						'value' => UM()->options()->get( 'g_recaptcha_status' ) ? $labels['yes'] : $labels['no'],
					),
					'um-g_recaptcha_password_reset' => array(
						'label' => __( 'Enable Google reCAPTCHA on the UM password reset form', 'ultimate-member' ),
						'value' => UM()->options()->get( 'g_recaptcha_password_reset' ) ? $labels['yes'] : $labels['no'],
					),
					'um-g_recaptcha_wp_lostpasswordform' => array(
						'label' => __( 'Enable Google reCAPTCHA on wp-login.php lost password form', 'ultimate-member' ),
						'value' => UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ? $labels['yes'] : $labels['no'],
					),
					'um-g_recaptcha_wp_login_form' => array(
						'label' => __( 'Enable Google reCAPTCHA on wp-login.php form', 'ultimate-member' ),
						'value' => UM()->options()->get( 'g_recaptcha_wp_login_form' ) ? $labels['yes'] : $labels['no'],
					),
					'um-g_recaptcha_wp_login_form_widget' => array(
						'label' => __( 'Enable Google reCAPTCHA on login form through `wp_login_form()`', 'ultimate-member' ),
						'value' => UM()->options()->get( 'g_recaptcha_wp_login_form_widget' ) ? $labels['yes'] : $labels['no'],
					),
					'um-g_recaptcha_wp_register_form' => array(
						'label' => __( 'Enable Google reCAPTCHA on wp-login.php registration form', 'ultimate-member' ),
						'value' => UM()->options()->get( 'g_recaptcha_wp_register_form' ) ? $labels['yes'] : $labels['no'],
					),
				),
			);

			if ( 1 === (int) UM()->options()->get( 'g_recaptcha_status' ) || 1 === (int) UM()->options()->get( 'g_recaptcha_password_reset' ) || 1 === (int) UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) || 1 === (int) UM()->options()->get( 'g_recaptcha_wp_login_form' ) || 1 === (int) UM()->options()->get( 'g_recaptcha_wp_login_form_widget' ) || 1 === (int) UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) {
				$info['ultimate-member-recaptcha']['fields'] = array_merge(
					$info['ultimate-member-recaptcha']['fields'],
					array(
						'um-g_recaptcha_version' => array(
							'label' => __( 'reCAPTCHA type', 'ultimate-member' ),
							'value' => __( 'reCAPTCHA ', 'ultimate-member' ) . UM()->options()->get( 'g_recaptcha_version' ),
						),
						'um-g_recaptcha_sitekey' => array(
							'label' => __( 'Site Key', 'ultimate-member' ),
							'value' => UM()->options()->get( 'g_recaptcha_sitekey' ) ? $labels['yes'] : $labels['no'],
						),
						'um-g_recaptcha_secretkey' => array(
							'label' => __( 'Secret Key', 'ultimate-member' ),
							'value' => UM()->options()->get( 'g_recaptcha_secretkey' ) ? $labels['yes'] : $labels['no'],
						),
						'um-g_recaptcha_type' => array(
							'label' => __( 'Type', 'ultimate-member' ),
							'value' => UM()->options()->get( 'g_recaptcha_type' ),
						),
						'um-g_recaptcha_language_code' => array(
							'label' => __( 'Language', 'ultimate-member' ),
							'value' => UM()->options()->get( 'g_recaptcha_language_code' ),
						),
						'um-g_recaptcha_size' => array(
							'label' => __( 'Size', 'ultimate-member' ),
							'value' => UM()->options()->get( 'g_recaptcha_size' ),
						),
					)
				);

				if ( 'invisible' !== UM()->options()->get( 'g_recaptcha_size' ) ) {
					$info['ultimate-member-recaptcha']['fields'] = array_merge(
						$info['ultimate-member-recaptcha']['fields'],
						array(
							'um-g_recaptcha_theme' => array(
								'label' => __( 'Theme', 'ultimate-member' ),
								'value' => UM()->options()->get( 'g_recaptcha_theme' ),
							),
						)
					);
				}
			}

			return $info;
		}


		/**
		 * Extend register form info.
		 *
		 * @since 3.0
		 *
		 * @param array $info
		 * @param int $key
		 *
		 * @return array
		 */
		public function um_debug_information_register_form( $info, $key ) {
			$info['ultimate-member-' . $key ]['fields'] = array_merge(
				$info['ultimate-member-' . $key ]['fields'],
				array(
					'um-register_g_recaptcha_status' => array(
						'label' => __( 'Google reCAPTCHA', 'ultimate-member' ),
						'value' => get_post_meta( $key, '_um_register_g_recaptcha_status', true ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				)
			);

			return $info;
		}


		/**
		 * Extend login form info.
		 *
		 * @since 3.0
		 *
		 * @param array $info
		 * @param int $key
		 *
		 * @return array
		 */
		public function um_debug_information_login_form( $info, $key ) {
			$info['ultimate-member-' . $key ]['fields'] = array_merge(
				$info['ultimate-member-' . $key ]['fields'],
				array(
					'um-login_g_recaptcha_status' => array(
						'label' => __( 'Google reCAPTCHA', 'ultimate-member' ),
						'value' => get_post_meta( $key, '_um_login_g_recaptcha_status', true ) ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
					),
				)
			);

			return $info;
		}
	}
}
