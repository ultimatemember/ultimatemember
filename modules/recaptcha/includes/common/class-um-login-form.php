<?php
namespace umm\recaptcha\includes\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UM_Login_Form
 *
 * @package umm\recaptcha\includes\common
 */
class UM_Login_Form {

	/**
	 * UM_Login_Form constructor.
	 */
	public function __construct() {
		add_filter( 'um_login_form_legacy_args', array( &$this, 'add_legacy_form_atts' ), 10, 2 );
		add_filter( 'shortcode_atts_ultimatemember_login', array( &$this, 'add_recaptcha_atts' ), 10, 3 );
		add_filter( 'um_login_form_args', array( &$this, 'add_login_form_args' ), 10, 2 );

		add_filter( 'login_form_middle', array( &$this, 'add_recaptcha_login_form' ), 10, 2 );
		add_action( 'um_login_form_wp_authenticate', array( &$this, 'validate_authentication' ), 10, 1 );
		add_filter( 'um_login_form_error_codes', array( &$this, 'extends_um_login_form_errors' ), 10, 3 );
	}

	/**
	 * @param array $login_args Attributes for `ultimatemember_login` shortcode.
	 * @param array $args       Form data.
	 *
	 * @return array
	 */
	public function add_legacy_form_atts( $login_args, $args ) {
		if ( ! array_key_exists( 'form_id', $args ) ) {
			return $login_args;
		}

		$meta = get_post_meta( $args['form_id'], '_um_login_g_recaptcha_status', true );
		$meta = '' !== $meta ? (bool) $meta : (bool) UM()->options()->get( 'g_recaptcha_status' );

		$login_args['recaptcha_enabled'] = $meta;
		return $login_args;
	}

	/**
	 * @param array $out   The output array of shortcode attributes.
	 * @param array $pairs The supported attributes and their defaults.
	 * @param array $atts  The user defined shortcode attributes.
	 *
	 * @return array
	 */
	public function add_recaptcha_atts( $out, $pairs, $atts ) {
		$recaptcha_pairs = array(
			'recaptcha_enabled' => (bool) UM()->options()->get( 'g_recaptcha_status' ),
		);
		foreach ( $recaptcha_pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) ) {
				$out[ $name ] = $atts[ $name ];
			} else {
				$out[ $name ] = $default;
			}
		}
		return $out;
	}

	/**
	 * @param array $login_args Attributes for `wp_login_form()` function init.
	 * @param array $args       Login form shortcode attributes.
	 *
	 * @return array
	 */
	public function add_login_form_args( $login_args, $args ) {
		$recaptcha_enabled = UM()->options()->get( 'g_recaptcha_status' );
		if ( array_key_exists( 'recaptcha_enabled', $args ) ) {
			$recaptcha_enabled = (bool) $args['recaptcha_enabled'];
		}
		$login_args['um_login_recaptcha_enabled'] = $recaptcha_enabled;
		return $login_args;
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

		if ( ! ( array_key_exists( 'um_login_recaptcha_enabled', $args ) && true === $args['um_login_recaptcha_enabled'] ) ) {
			return $content;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return $content;
		}

		$version = UM()->options()->get( 'g_recaptcha_version' );

		switch ( $version ) {
			case 'v3':
				$content .= um_get_template_html(
					'wp-captcha-v3.php',
					array(),
					'recaptcha'
				);
				break;
			case 'v2':
			default:
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
	 * @param array $error_codes
	 * @param array $args
	 * @param array $login_args
	 *
	 * @return array
	 */
	public function extends_um_login_form_errors( $error_codes, $args, $login_args ) {
		if ( ! array_key_exists( 'um_login_form', $login_args ) || true !== $login_args['um_login_form'] ) {
			return $error_codes;
		}

		if ( array_key_exists( 'recaptcha_enabled', $args ) && true === $args['recaptcha_enabled'] ) {
			if ( array_key_exists( 'form_id', $args ) ) {
				$form_id = absint( $args['form_id'] );
				$meta = get_post_meta( $form_id, '_um_login_g_recaptcha_status', true );
				$meta = '' !== $meta ? (bool) $meta : (bool) UM()->options()->get( 'g_recaptcha_status' );
			} else {
				$meta = (bool) UM()->options()->get( 'g_recaptcha_wp_login_form_widget' );
			}
		} else {
			$meta = (bool) UM()->options()->get( 'g_recaptcha_wp_login_form_widget' );
		}

		if ( ! $meta ) {
			return $error_codes;
		}

		$error_codes = array_merge(
			$error_codes,
			array(
				'um_recaptcha_empty'                  => __( 'Please confirm you are not a robot.', 'ultimate-member' ),
				'um_recaptcha_score'                  => __( 'It is very likely a bot.', 'ultimate-member' ),
				'um_recaptcha_missing-input-secret'   => __( 'The secret parameter is missing.', 'ultimate-member' ),
				'um_recaptcha_invalid-input-secret'   => __( 'The secret parameter is invalid or malformed.', 'ultimate-member' ),
				'um_recaptcha_missing-input-response' => __( 'The response parameter is missing.', 'ultimate-member' ),
				'um_recaptcha_invalid-input-response' => __( 'The response parameter is invalid or malformed.', 'ultimate-member' ),
				'um_recaptcha_bad-request'            => __( 'The request is invalid or malformed.', 'ultimate-member' ),
				'um_recaptcha_timeout-or-duplicate'   => __( 'The response is no longer valid: either is too old or has been used previously.', 'ultimate-member' ),
				'um_recaptcha_undefined'              => __( 'Undefined reCAPTCHA error.', 'ultimate-member' ),
			)
		);

		return $error_codes;
		// phpcs:enable WordPress.Security.NonceVerification -- getting value from GET line
	}

	/**
	 * Run before the authenticate process of the user via `wp_authenticate()` on Ultimate Member - Login form.
	 * reCAPTCHA must be triggered until 'authenticate' filter to avoid security issues.
	 *
	 * @param null|int $form_id Login Form ID if passed
	 */
	public function validate_authentication( $form_id ) {
		// phpcs:disable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
		if ( is_null( $form_id ) ) {
			// if form is undefined then check the global setting for the `wp_login_form()` widget
			$meta = (bool) UM()->options()->get( 'g_recaptcha_wp_login_form_widget' );
		} else {
			$meta = get_post_meta( $form_id, '_um_login_g_recaptcha_status', true );
			$meta = '' !== $meta ? (bool) $meta : (bool) UM()->options()->get( 'g_recaptcha_status' );
		}

		if ( ! $meta ) {
			return;
		}

		$your_sitekey = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$your_secret  = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $your_sitekey || ! $your_secret ) {
			return;
		}

		$current_url = esc_url_raw( $_REQUEST['um_current_login_url'] );

		$version = UM()->options()->get( 'g_recaptcha_version' );
		switch ( $version ) {
			case 'v3':
				$your_secret = trim( UM()->options()->get( 'g_reCAPTCHA_secret_key' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'login' => 'um_recaptcha_empty' ), $current_url ) );
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
						wp_safe_redirect( add_query_arg( array( 'login' => 'um_recaptcha_score' ), $current_url ) );
						exit;
					} elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
						$error_codes = UM()->module( 'recaptcha' )->config()->get( 'error_codes' );

						foreach ( $result->{'error-codes'} as $key => $error_code ) {
							$code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';

							wp_safe_redirect( add_query_arg( array( 'login' => 'um_recaptcha_' . $code ), $current_url ) );
							exit;
						}
					}
				}

				break;
			case 'v2':
			default:
				$your_secret = trim( UM()->options()->get( 'g_recaptcha_secretkey' ) );

				if ( empty( $_POST['g-recaptcha-response'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'login' => 'um_recaptcha_empty' ), $current_url ) );
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

							wp_safe_redirect( add_query_arg( array( 'login' => 'um_recaptcha_' . $code ), $current_url ) );
							exit;
						}
					}
				}
				break;
		}
		// phpcs:enable WordPress.Security.NonceVerification -- already verified here via wp-login.php or wp_login_form()
	}
}
