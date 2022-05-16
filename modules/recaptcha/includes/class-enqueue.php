<?php
namespace umm\recaptcha\includes;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Enqueue
 *
 * @package umm\recaptcha\includes
 */
class Enqueue {


	/**
	 * Enqueue constructor.
	 */
	function __construct() {
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_filter( 'um_modules_min_scripts_dependencies', array( &$this, 'extends_scripts_dependencies' ), 10, 1 );
		add_filter( 'um_modules_min_scripts_variables', array( &$this, 'extends_scripts_variables' ), 10, 1 );
	}


	/**
	 * @param array $deps
	 *
	 * @return array
	 */
	function extends_scripts_dependencies( $deps = array() ) {
		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch( $version ) {
			case 'v3':
				$deps[] = 'google-recaptcha-api-v3';
				break;
			case 'v2':
			default:
				$deps[] = 'google-recaptcha-api-v2';
				break;
		}

		return $deps;
	}


	/**
	 * @param array $variables
	 *
	 * @return array
	 */
	function extends_scripts_variables( $variables = array() ) {
		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch( $version ) {
			case 'v3':
				$site_key = UM()->options()->get( 'g_reCAPTCHA_site_key' );
				break;
			case 'v2':
			default:
				$site_key = UM()->options()->get( 'g_recaptcha_sitekey' );
				break;
		}

		$variables['umRecaptchaData'] = array(
			'version'  => $version,
			'site_key' => $site_key,
		);

		return $variables;
	}


	/**
	 *
	 */
	function enqueue_scripts() {
		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch( $version ) {
			case 'v3':
				$site_key = UM()->options()->get( 'g_reCAPTCHA_site_key' );

				wp_register_script( 'google-recaptcha-api-v3', "https://www.google.com/recaptcha/api.js?render=$site_key", array(), '3.0', false );
				break;
			case 'v2':
			default:
				$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
				$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

				wp_register_script( 'google-recaptcha-api-v2', "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code", array(), '2.0', false );
				break;
		}
	}
}
