<?php
namespace umm\recaptcha\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Forms
 *
 * @package umm\recaptcha\includes\frontend
 */
class Forms {


	/**
	 * Forms constructor.
	 */
	function __construct() {

	}


	/**
	 *
	 */
	function hooks() {
		add_action( 'um_after_register_fields', array( &$this, 'um_form_add_captcha' ), 500 );
		add_action( 'um_after_login_fields', array( &$this, 'um_form_add_captcha' ), 500 );

		//add_action( 'um_after_password_reset_fields', array( &$this, 'um_form_add_captcha' ), 500 );
		add_filter( 'um_form_field_types', array( &$this, 'add_recaptcha_field_type' ), 10, 1 );
		add_filter( 'um_lostpassword_form_args', array( &$this, 'lostpassword_add_recaptcha_field' ), 10, 1 );
		add_filter( 'um_form_field_recaptcha_rendered_html', array( &$this, 'lostpassword_captcha_field_html' ), 10, 3 );

		add_action( 'um_submit_form_errors_hook', array( &$this, 'um_recaptcha_validate' ), 20 );

		add_action( 'um_lostpassword_errors_hook', array( &$this, 'um_recaptcha_lostpassword_validate' ), 20 );

		add_filter( 'login_errors', array( &$this, 'um_recaptcha_hide_errors' ), 10, 2 );
	}


	/**
	 * add recaptcha
	 *
	 * @param $args
	 */
	public function um_form_add_captcha( $args ) {
		if ( ! $this->captcha_allowed( $args ) ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch( $version ) {
			case 'v3':
				um_get_template( 'captcha-v3.php', array( 'args' => $args ), 'recaptcha' );
				break;
			case 'v2':
			default:
				um_get_template(
					'captcha.php',
					array(
						'args'    => $args,
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					'recaptcha'
				);
				break;
		}
	}

	/**
	 * @param array $types
	 *
	 * @return array
	 */
	public function add_recaptcha_field_type( $types ) {
		$types[] = 'recaptcha';
		return $types;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function lostpassword_add_recaptcha_field( $args ) {
		if ( ! UM()->options()->get( 'g_recaptcha_password_reset' ) ) {
			return $args;
		}

		$recaptcha    = UM()->options()->get( 'g_recaptcha_status' );
		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );

		if ( ! $recaptcha ) {
			return $args;
		}

		if ( ! $your_sitekey ) {
			return $args;
		}

		$field_args = array(
			'type' => 'recaptcha',
			'id'   => 'recaptcha',
		);

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );
				$field_args['row'] = false; // to use the field's HTML without row's render
				break;
			case 'v2':
			default:
				if ( 'invisible' === UM()->options()->get( 'g_recaptcha_size' ) ) {
					$field_args['row'] = false; // to use the field's HTML without row's render
				}

				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );
				break;
		}

		$field_args['version'] = $version;

		if ( ! $your_secret ) {
			return $args;
		}

		$args['fields'][] = $field_args;
		return $args;
	}

	/**
	 * @param string $html
	 * @param array $field_data
	 * @param array $form_data
	 *
	 * @return string
	 */
	public function lostpassword_captcha_field_html( $html, $field_data, $form_data ) {
		if ( ! array_key_exists( 'version', $field_data ) ) {
			return $html;
		}

		switch( $field_data['version'] ) {
			case 'v3':
				$html .= um_get_template_html(
					'rp-captcha-v3.php',
					array(
						'args' => array(
							'mode'    => 'lostpassword',
							'form_id' => $form_data['id'],
						),
					),
					'recaptcha'
				);
				break;
			case 'v2':
			default:
				$html .= um_get_template_html(
					'rp-captcha.php',
					array(
						'args'    => array(
							'form_id' => $form_data['id'],
						),
						'type'    => UM()->options()->get( 'g_recaptcha_type' ),
						'size'    => UM()->options()->get( 'g_recaptcha_size' ),
						'theme'   => UM()->options()->get( 'g_recaptcha_theme' ),
						'sitekey' => UM()->options()->get( 'g_recaptcha_sitekey' ),
					),
					'recaptcha'
				);
				break;
		}

		return $html;
	}


	/**
	 * form error handling
	 *
	 * @link https://developers.google.com/recaptcha/docs/verify#api_request
	 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
	 *
	 * @param $args
	 */
	public function um_recaptcha_validate( $args ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
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
				$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

				foreach ( $result->{'error-codes'} as $key => $error_code ) {
					// translators: %s: Google reCAPTCHA error code
					$error = array_key_exists( $error_code, $error_codes ) ? $error_codes[ $error_code ] : sprintf( __( 'Undefined error. Key: %s', 'ultimate-member' ), $error_code );
					UM()->form()->add_error( 'recaptcha', $error );
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
	}

	/**
	 * Lost Password form error handling
	 *
	 * @link https://developers.google.com/recaptcha/docs/verify#api_request
	 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
	 *
	 * @param $lostpassword_form \um\frontend\Form
	 */
	public function um_recaptcha_lostpassword_validate( $lostpassword_form ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
		if ( ! UM()->options()->get( 'g_recaptcha_password_reset' ) ) {
			return;
		}

		$recaptcha    = UM()->options()->get( 'g_recaptcha_status' );
		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );

		if ( ! $recaptcha ) {
			return;
		}

		if ( ! $your_sitekey ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch( $version ) {
			case 'v3':
				$error_id    = 'global';
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );
				break;
			case 'v2':
			default:
				$error_id    = 'recaptcha';
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );
				break;
		}

		if ( ! $your_secret ) {
			return;
		}

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			$lostpassword_form->add_error( $error_id, __( 'Please confirm you are not a robot', 'ultimate-member' ) );
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
				$lostpassword_form->add_error( $error_id, __( 'reCAPTCHA: it is very likely a bot.', 'ultimate-member' ) );
			} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
				$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

				foreach ( $result->{'error-codes'} as $key => $error_code ) {
					// translators: %s: Google reCAPTCHA error code
					$error = array_key_exists( $error_code, $error_codes ) ? $error_codes[ $error_code ] : sprintf( __( 'Undefined error. Key: %s', 'ultimate-member' ), $error_code );
					$lostpassword_form->add_error( $error_id, $error );
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via UM Form nonce
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
	public function um_recaptcha_hide_errors( $error_message, $error_key = null ) {
		if ( 'recaptcha' === $error_key ) {
			$error_message = '';
		}
		return $error_message;
	}


	/**
	 * Captcha allowed
	 *
	 * @param $args
	 *
	 * @return bool
	 */
	public function captcha_allowed( $args ) {
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
