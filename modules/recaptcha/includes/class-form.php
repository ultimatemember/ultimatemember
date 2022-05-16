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
		add_filter( 'login_body_class', array( &$this, 'add_login_form_classes' ), 10, 2 );
		add_action( 'login_enqueue_scripts', array( &$this, 'add_wp_login_form_scripts' ) );
		add_action( 'login_form', array( &$this, 'add_wp_login_form_recaptcha' ) );

		add_action( 'register_form', array( &$this, 'add_wp_register_form_recaptcha' ) );
		add_filter( 'registration_errors', array( &$this, 'validate_wp_register_form' ), 10, 1 );

		add_action( 'lostpassword_form', array( &$this, 'add_wp_lostpassword_form_recaptcha' ) );
		add_filter( 'lostpassword_errors', array( &$this, 'validate_wp_lostpassword_form' ), 10, 1 );

		add_filter( 'login_form_middle', array( &$this, 'add_wp_login_form_widget_recaptcha' ), 10, 2 );

		add_filter( 'wp_login_errors', array( &$this, 'extends_wp_login_errors' ), 10, 1 );
		add_action( 'wp_authenticate', array( &$this, 'validate_authentication' ), 2, 2 );

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
	 * @return bool
	 */
	private function is_api_request() {
		$is_api_request = ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) );
		$is_api_request = apply_filters( 'um_is_api_request', $is_api_request );

		return $is_api_request;
	}


	/**
	 * @return array
	 */
	private function error_codes_list() {
		$error_codes = array(
			'missing-input-secret'   => __( '<strong>Error</strong>: The secret parameter is missing.', 'ultimate-member' ),
			'invalid-input-secret'   => __( '<strong>Error</strong>: The secret parameter is invalid or malformed.', 'ultimate-member' ),
			'missing-input-response' => __( '<strong>Error</strong>: The response parameter is missing.', 'ultimate-member' ),
			'invalid-input-response' => __( '<strong>Error</strong>: The response parameter is invalid or malformed.', 'ultimate-member' ),
			'bad-request'            => __( '<strong>Error</strong>: The request is invalid or malformed.', 'ultimate-member' ),
			'timeout-or-duplicate'   => __( '<strong>Error</strong>: The response is no longer valid: either is too old or has been used previously.', 'ultimate-member' ),
			'undefined'              => __( '<strong>Error</strong>: Undefined reCAPTCHA error.', 'ultimate-member' ),
		);

		return $error_codes;
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
						$error_codes = $this->error_codes_list();

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
						$error_codes = $this->error_codes_list();

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
						$error_codes = $this->error_codes_list();

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
						$error_codes = $this->error_codes_list();

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
	 * Handle reCAPTCHA via `wp_login_form()`
	 *
	 * @param $content
	 * @param $args
	 *
	 * @return string
	 */
	public function add_wp_login_form_widget_recaptcha( $content, $args ) {
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

		// for the wp_login_form() function login widget
		// $redirect URL in this case will use the widget current URL from where was request to wp-login.php
		if ( ! empty( $_REQUEST['um_login_form'] ) && ! empty( $redirect ) ) {
			$query = wp_parse_url( $redirect, PHP_URL_QUERY );
			parse_str( $query, $query_args );

			if ( array_key_exists( 'redirect_to', $query_args ) ) {
				$redirect = $query_args['redirect_to'];
			}

			if ( array_key_exists( 'reauth', $query_args ) ) {
				$force_reauth = $query_args['reauth'];
			}
		}

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
						$error_codes = $this->error_codes_list();

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
						$error_codes = $this->error_codes_list();

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
	 * form error handling
	 *
	 * @link https://developers.google.com/recaptcha/docs/verify#api_request
	 * @link https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
	 *
	 * @param $args
	 */
	function um_recaptcha_validate( $args ) {
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
				$error_codes = $this->error_codes_list();

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
