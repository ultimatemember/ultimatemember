<?php
namespace umm\recaptcha\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class WP_Login_PHP
 *
 * @package umm\recaptcha\includes\common
 */
class WP_Login_PHP {


	/**
	 * WP_Login_PHP constructor.
	 */
	public function __construct() {
		add_filter( 'login_body_class', array( &$this, 'add_login_form_classes' ), 10, 2 );
		add_action( 'login_enqueue_scripts', array( &$this, 'add_wp_login_form_scripts' ) );

		add_action( 'login_form', array( &$this, 'add_wp_login_form_recaptcha' ) );

		add_action( 'register_form', array( &$this, 'add_wp_register_form_recaptcha' ) );
		add_filter( 'registration_errors', array( &$this, 'validate_wp_register_form' ), 10, 1 );

		add_action( 'lostpassword_form', array( &$this, 'add_wp_lostpassword_form_recaptcha' ) );
		add_filter( 'lostpassword_errors', array( &$this, 'validate_wp_lostpassword_form' ), 10, 1 );

		add_filter( 'wp_login_errors', array( &$this, 'extends_wp_login_errors' ), 10, 1 );
		add_action( 'wp_authenticate', array( &$this, 'validate_authentication' ), 2, 2 );
	}


	/**
	 * @return bool
	 */
	private function is_api_request() {
		$is_api_request = ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) );
		$is_api_request = apply_filters( 'um_is_api_request', $is_api_request );

		return $is_api_request;
	}


	/**
	 * Add classes on wp-login.php page
	 *
	 * @param array $classes
	 * @param string $action
	 *
	 * @return array
	 */
	public function add_login_form_classes( $classes, $action ) {
		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $classes;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $classes;
		}

		if ( ( 'login' === $action && UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) || ( ( 'lostpassword' === $action || 'retrievepassword' === $action ) && UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ) || ( 'register' === $action && UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) ) {
			$version = UM()->options()->get( 'g_recaptcha_version' );
			if ( 'v3' === $version ) {
				return $classes;
			}

			$type = UM()->options()->get( 'g_recaptcha_size' );
			if ( 'invisible' === $type ) {
				return $classes;
			}

			$classes[] = ( 'normal' === $type ) ? 'has-normal-um-recaptcha' : 'has-compact-um-recaptcha';
		}

		return $classes;
	}


	/**
	 * Enqueue assets on wp-login.php page
	 */
	public function add_wp_login_form_scripts() {
		$module_data = UM()->modules()->get_data( 'recaptcha' );
		if ( ! $module_data ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		if ( ! ( UM()->options()->get( 'g_recaptcha_wp_login_form' ) || UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) || UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) ) {
			return;
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
				break;
			case 'v2':
			default:
				$language_code = UM()->options()->get( 'g_recaptcha_language_code' );
				$language_code = apply_filters( 'um_recaptcha_language_code', $language_code );

				wp_register_script( 'google-recaptcha-api-v2', "https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit&hl=$language_code", array( 'jquery' ), '2.0', false );
				wp_enqueue_script( 'google-recaptcha-api-v2' );
				break;
		}
	}


	/**
	 * Add reCAPTCHA block to the wp-login.php page
	 */
	public function add_wp_login_form_recaptcha() {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				um_get_template( 'wp-captcha-v3.php', array(), 'recaptcha' );
				break;
			case 'v2':
			default:
				um_get_template(
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
	}


	/**
	 * Add reCAPTCHA block to the wp-login.php page Register mode
	 */
	public function add_wp_register_form_recaptcha() {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				um_get_template( 'wp-captcha-v3.php', array(), 'recaptcha' );
				break;
			case 'v2':
			default:
				um_get_template(
					'wp-captcha.php',
					array(
						'mode'    => 'register',
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
	 * @param \WP_Error $errors
	 *
	 * @return mixed
	 */
	public function validate_wp_register_form( $errors ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php
		if ( $this->is_api_request() ) {
			return $errors;
		}

		if ( ! UM()->options()->get( 'g_recaptcha_wp_register_form' ) ) {
			return $errors;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $errors;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $errors;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'ultimate-member' ) );
					return $errors;
				} else {
					$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
				}

				$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
				$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

				if ( is_array( $response ) ) {
					$result = json_decode( $response['body'] );

					$score = UM()->options()->get( 'g_reCAPTCHA_score' );
					if ( empty( $score ) ) {
						// set default 0.6 because Google recommend by default set 0.5 score
						// https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
						$score = 0.6;
					}
					// available to change score based on form $args
					$validate_score = apply_filters( 'um_recaptcha_score_validation', $score );

					if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
						$errors->add( 'um-recaptcha-score', __( '<strong>Error</strong>: It is very likely a bot.', 'ultimate-member' ) );
						return $errors;
					} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
							$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );
							return $errors;
						}
					}
				}
				break;
			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'ultimate-member' ) );
					return $errors;
				} else {
					$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
				}

				$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
				$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

				if ( is_array( $response ) ) {
					$result = json_decode( $response['body'] );

					if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
							$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );
							return $errors;
						}
					}
				}
				break;
		}

		return $errors;
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php
	}


	/**
	 * Add reCAPTCHA block to the wp-login.php page Lost Password mode
	 */
	public function add_wp_lostpassword_form_recaptcha() {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				um_get_template( 'wp-captcha-v3.php', array(), 'recaptcha' );
				break;
			case 'v2':
			default:
				um_get_template(
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
	}


	/**
	 * @param \WP_Error $errors
	 *
	 * @return mixed
	 */
	public function validate_wp_lostpassword_form( $errors ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php
		if ( $this->is_api_request() ) {
			return $errors;
		}

		if ( ! UM()->options()->get( 'g_recaptcha_wp_lostpasswordform' ) ) {
			return $errors;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return $errors;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $errors;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'ultimate-member' ) );
					return $errors;
				} else {
					$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
				}

				$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
				$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

				if ( is_array( $response ) ) {
					$result = json_decode( $response['body'] );

					$score = UM()->options()->get( 'g_reCAPTCHA_score' );
					if ( empty( $score ) ) {
						// set default 0.6 because Google recommend by default set 0.5 score
						// https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
						$score = 0.6;
					}
					// available to change score based on form $args
					$validate_score = apply_filters( 'um_recaptcha_score_validation', $score );

					if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
						$errors->add( 'um-recaptcha-score', __( '<strong>Error</strong>: It is very likely a bot.', 'ultimate-member' ) );
						return $errors;
					} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
							$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );
							return $errors;
						}
					}
				}
				break;
			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					$errors->add( 'um-recaptcha-empty', __( '<strong>Error</strong>: Please confirm you are not a robot.', 'ultimate-member' ) );
					return $errors;
				} else {
					$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
				}

				$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
				$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

				if ( is_array( $response ) ) {
					$result = json_decode( $response['body'] );

					if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
							$errors->add( 'um-recaptcha-' . $code, $error_codes[ $code ] );
							return $errors;
						}
					}
				}
				break;
		}

		return $errors;
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php
	}


	/**
	 * @param \WP_Error $errors
	 *
	 * @return \WP_Error
	 */
	public function extends_wp_login_errors( $errors ) {
		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
			return $errors;
		}

		if ( $this->is_api_request() ) {
			return $errors;
		}

		// phpcs:disable WordPress.Security.NonceVerification -- getting value from GET line
		if ( isset( $_GET['um-recaptcha-error'] ) ) {
			$code = ! empty( $_GET['um-recaptcha-error'] ) ? sanitize_key( $_GET['um-recaptcha-error'] ) : 'undefined';

			switch ( $code ) {
				case 'empty':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: Please confirm you are not a robot.', 'ultimate-member' ) );
					break;
				case 'score':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: It is very likely a bot.', 'ultimate-member' ) );
					break;
				case 'missing-input-secret':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The secret parameter is missing.', 'ultimate-member' ) );
					break;
				case 'invalid-input-secret':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The secret parameter is invalid or malformed.', 'ultimate-member' ) );
					break;
				case 'missing-input-response':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The response parameter is missing.', 'ultimate-member' ) );
					break;
				case 'invalid-input-response':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The response parameter is invalid or malformed.', 'ultimate-member' ) );
					break;
				case 'bad-request':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The request is invalid or malformed.', 'ultimate-member' ) );
					break;
				case 'timeout-or-duplicate':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: The response is no longer valid: either is too old or has been used previously.', 'ultimate-member' ) );
					break;
				case 'undefined':
					$errors->add( 'recaptcha_' . $code, __( '<strong>Error</strong>: Undefined reCAPTCHA error.', 'ultimate-member' ) );
					break;
				default:
					// translators: %s: Google reCAPTCHA error code
					$errors->add( 'recaptcha_' . $code, sprintf( __( '<strong>Error</strong>: reCAPTCHA Code: %s', 'ultimate-member' ), $code ) );
					break;
			}
		}
		return $errors;
		// phpcs:enable WordPress.Security.NonceVerification -- getting value from GET line
	}


	/**
	 * Run before the authenticate process of the user via wp-login.php form
	 *
	 * @param $username
	 * @param $password
	 */
	public function validate_authentication( $username, $password ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
		if ( ! UM()->options()->get( 'g_recaptcha_wp_login_form' ) ) {
			return;
		}

		// UM Login form has their own authentication validator
		if ( ! empty( $_REQUEST['um_login_form'] ) ) {
			return;
		}

		if ( $this->is_api_request() ) {
			return;
		}

		if ( empty( $username ) || empty( $password ) ) {
			return;
		}

		$recaptcha = UM()->options()->get( 'g_recaptcha_status' );
		if ( ! $recaptcha ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		$redirect     = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : '';
		$force_reauth = isset( $_GET['reauth'] ) ? (bool) $_GET['reauth'] : false;

		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'empty' ), wp_login_url( $redirect, $force_reauth ) ) );
					exit;
				} else {
					$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
				}

				$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
				$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

				if ( is_array( $response ) ) {
					$result = json_decode( $response['body'] );

					$score = UM()->options()->get( 'g_reCAPTCHA_score' );
					if ( empty( $score ) ) {
						// set default 0.6 because Google recommend by default set 0.5 score
						// https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
						$score = 0.6;
					}
					// available to change score based on form $args
					$validate_score = apply_filters( 'um_recaptcha_score_validation', $score );

					if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
						wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'score' ), wp_login_url( $redirect, $force_reauth ) ) );
						exit;
					} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';

							wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => $code ), wp_login_url( $redirect, $force_reauth ) ) );
							exit;
						}
					}
				}

				break;
			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => 'empty' ), wp_login_url( $redirect, $force_reauth ) ) );
					exit;
				} else {
					$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
				}

				$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
				$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$your_secret&response=$client_captcha_response&remoteip=$user_ip" );

				if ( is_array( $response ) ) {
					$result = json_decode( $response['body'] );

					if ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';

							wp_safe_redirect( add_query_arg( array( 'um-recaptcha-error' => $code ), wp_login_url( $redirect, $force_reauth ) ) );
							exit;
						}
					}
				}
				break;
		}
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
	}
}
