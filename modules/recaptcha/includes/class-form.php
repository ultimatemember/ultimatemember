<?php
namespace umm\recaptcha\includes;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Form
 *
 * @package umm\recaptcha\includes
 */
class Form {


	/**
	 * Form constructor.
	 */
	function __construct() {
		add_action( 'um_after_register_fields', array( &$this, 'um_recaptcha_add_captcha' ), 500 );
		add_action( 'um_after_login_fields', array( &$this, 'um_recaptcha_add_captcha' ), 500 );
		add_action( 'um_after_password_reset_fields', array( &$this, 'um_recaptcha_add_captcha' ), 500 );

		add_action( 'um_pre_register_shortcode', array( &$this, 'um_recaptcha_enqueue_scripts' ) );
		add_action( 'um_pre_login_shortcode', array( &$this, 'um_recaptcha_enqueue_scripts' ) );
		add_action( 'um_pre_password_shortcode', array( &$this, 'um_recaptcha_enqueue_scripts' ) );

		add_action( 'um_submit_form_errors_hook', array( &$this, 'um_recaptcha_validate' ), 20 );
		add_action( 'um_reset_password_errors_hook', array( &$this, 'um_recaptcha_validate' ), 20 );
		add_filter( 'login_errors', array( &$this, 'um_recaptcha_hide_errors' ), 10, 2 );
	}


	/**
	 * add recaptcha
	 *
	 * @param $args
	 */
	function um_recaptcha_add_captcha( $args ) {
		if ( ! $this->captcha_allowed( $args ) ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch( $version ) {
			case 'v3':
				$t_args = compact( 'args' );
				um_get_template( 'captcha_v3.php', $t_args, 'recaptcha' );
				break;
			case 'v2':
			default:
				$options = array(
					'data-type'    => UM()->options()->get( 'g_recaptcha_type' ),
					'data-size'    => UM()->options()->get( 'g_recaptcha_size' ),
					'data-theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
					'data-sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
				);

				$attrs = array();
				foreach ( $options as $att => $value ) {
					if ( $value ) {
						$att = esc_html( $att );
						$value = esc_attr( $value );
						$attrs[] = "{$att}=\"{$value}\"";
					}
				}

				if ( ! empty( $attrs ) ) {
					$attrs = implode( ' ', $attrs );
				} else {
					$attrs = '';
				}

				$t_args = compact( 'args', 'attrs', 'options' );
				um_get_template( 'captcha.php', $t_args, 'recaptcha' );
				break;
		}
	}


	/**
	 * form error handling
	 *
	 * @link https://developers.google.com/recaptcha/docs/verify#api_request
	 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
	 *
	 * @param $args
	 */
	function um_recaptcha_validate( $args ) {
		if ( isset( $args['mode'] ) && ! in_array( $args['mode'], array( 'login', 'register', 'password' ), true ) && ! isset( $args['_social_login_form'] ) ) {
			return;
		}

		if ( ! $this->captcha_allowed( $args ) ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );
				break;

			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );
				break;
		}

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			UM()->form()->add_error( 'recaptcha', __( 'Please confirm you are not a robot', 'ultimate-member' ) );
			return;
		} else {
			$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
		}

		$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

		if ( is_array( $response ) ) {
			$result = json_decode( $response['body'] );

			$score = UM()->options()->get( 'g_reCAPTCHA_score' );
			if ( ! empty( $args['g_recaptcha_score'] ) ) {
				// use form setting for score
				$score = $args['g_recaptcha_score'];
			}

			if ( empty( $score ) ) {
				// set default 0.6 because Google recommend by default set 0.5 score
				// https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
				$score = 0.6;
			}
			// available to change score based on form $args
			$validate_score = apply_filters( 'um_recaptcha_score_validation', $score, $args );

			if ( isset( $result->score ) && $result->score < $validate_score ) {
				UM()->form()->add_error( 'recaptcha', __( 'reCAPTCHA: it is very likely a bot.', 'ultimate-member' ) );
			} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
				$error_codes = array(
					'missing-input-secret'   => __( 'The secret parameter is missing.', 'ultimate-member' ),
					'invalid-input-secret'   => __( 'The secret parameter is invalid or malformed.', 'ultimate-member' ),
					'missing-input-response' => __( 'The response parameter is missing.', 'ultimate-member' ),
					'invalid-input-response' => __( 'The response parameter is invalid or malformed.', 'ultimate-member' ),
					'bad-request'            => __( 'The request is invalid or malformed.', 'ultimate-member' ),
					'timeout-or-duplicate'   => __( 'The response is no longer valid: either is too old or has been used previously.', 'ultimate-member' ),
				);

				foreach ( $result->{'error-codes'} as $key => $error_code ) {
					$error = array_key_exists( $error_code, $error_codes ) ? $error_codes[ $error_code ] : sprintf( __( 'Undefined error. Key: %s', 'ultimate-member' ), $error_code );
					UM()->form()->add_error( 'recaptcha', $error );
				}
			}

		}
	}


	/**
	 * Don't display reCAPTCHA error message twice on login
	 *
	 * @since 2.2.1
	 *
	 * @param string $error_message  Error message
	 * @param string $error_key      A key of the error
	 *
	 * @return string Filtered error message
	 */
	function um_recaptcha_hide_errors( $error_message, $error_key = null ) {
		if ( 'recaptcha' === $error_key ) {
			$error_message = '';
		}
		return $error_message;
	}


	/**
	 * reCAPTCHA scripts/styles enqueue in the page with a form
	 */
	function um_recaptcha_enqueue_scripts( $args ) {
		add_filter( 'um_modules_min_scripts_variables', function( $variables ) use ( $args ) {
			$variables['umRecaptchaData']['allowed'] = $this->captcha_allowed( $args );
			return $variables;
		}, 10, 1 );
	}


	/**
	 * Captcha allowed
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	function captcha_allowed( $args ) {
		$enable = false;

		$recaptcha    = UM()->options()->get( 'g_recaptcha_status' );
		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( $recaptcha ) {
			$enable = true;
		}

		if ( isset( $args['g_recaptcha_status'] ) && $args['g_recaptcha_status'] ) {
			$enable = true;
		}

		if ( isset( $args['g_recaptcha_status'] ) && ! $args['g_recaptcha_status'] ) {
			$enable = false;
		}

		if ( ! $your_sitekey || ! $your_secret ) {
			$enable = false;
		}

		if ( isset( $args['mode'] ) && 'password' === $args['mode'] && ! UM()->options()->get( 'g_recaptcha_password_reset' ) ) {
			$enable = false;
		}

		return false === $enable ? false : true;
	}
}
