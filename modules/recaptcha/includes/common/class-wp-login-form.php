<?php
namespace umm\recaptcha\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class WP_Login_Form
 *
 * @package umm\recaptcha\includes\common
 */
class WP_Login_Form {


	/**
	 * WP_Login_Form constructor.
	 */
	public function __construct() {
		add_filter( 'login_form_middle', array( &$this, 'add_recaptcha_login_form' ), 10, 2 );
	}


	/**
	 * Add reCAPTCHA to the forms handled by `wp_login_form()`
	 *
	 * @param string $content
	 * @param array  $args
	 *
	 * @return string
	 */
	public function add_recaptcha_login_form( $content, $args ) {
		$module_data = UM()->modules()->get_data( 'recaptcha' );
		if ( ! $module_data ) {
			return $content;
		}

		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form_widget' ) ) {
			return $content;
		}

		if ( ! ( array_key_exists( 'um_login_form', $args ) && true === $args['um_login_form'] ) ) {
			return $content;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $content;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $content;
		}

		wp_register_style( 'um-recaptcha', $module_data['url'] . 'assets/css/wp-recaptcha' . UM()->frontend()->enqueue()->suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'um-recaptcha' );

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				$site_key = UM()->options()->get( 'g_reCAPTCHA_site_key' );

				wp_register_script( 'google-recaptcha-api-v3', "https://www.google.com/recaptcha/api.js?render=$site_key", array(), '3.0', false );
				wp_register_script( 'um-recaptcha', $module_data['url'] . 'assets/js/wp-recaptcha' . UM()->frontend()->enqueue()->suffix . '.js', array( 'jquery', 'google-recaptcha-api-v3' ), UM_VERSION, true );

				wp_localize_script(
					'um-recaptcha',
					'umRecaptchaData',
					array(
						'site_key' => $site_key,
					)
				);

				wp_enqueue_script( 'um-recaptcha' );

				$content .= um_get_template_html(
					'wp-captcha-v3.php',
					array(),
					'recaptcha'
				);
				break;
			case 'v2':
			default:
				$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
				$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

				wp_register_script( 'google-recaptcha-api-v2', "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code", array( 'jquery' ), '2.0', false );
				wp_enqueue_script( 'google-recaptcha-api-v2' );

				$content .= um_get_template_html(
					'wp-captcha.php',
					array(
						'mode'    => 'login',
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					'recaptcha'
				);
				break;
		}

		return $content;
	}
}
