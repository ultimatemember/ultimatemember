<?php namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Shortcodes' ) ) {


	/**
	 * Class Shortcodes
	 *
	 * @package um\common
	 */
	class Shortcodes {

		/**
		 * Marker for the displaying resetpass form instead of the lostpassword
		 *
		 * @var bool
		 */
		public $is_resetpass = false;

		/**
		 * Shortcodes constructor.
		 */
		public function __construct() {
			add_shortcode( 'ultimatemember_password', array( &$this, 'reset_password_form' ) );
			add_shortcode( 'ultimatemember_login', array( &$this, 'login_form' ) );
			add_shortcode( 'ultimatemember', array( &$this, 'common_forms' ) );
		}

		/**
		 * Shortcode for the displaying reset password form
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function reset_password_form( $args = array() ) {
			if ( ! um_is_predefined_page( 'password-reset' ) ) {
				return '';
			}

			/** There is possible to use 'shortcode_atts_ultimatemember_password' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
			$args = shortcode_atts(
				array(
					'max_width' => '450px',
					'align'     => 'center',
				),
				$args,
				'ultimatemember_password'
			);

			if ( ! empty( $this->is_resetpass ) ) {
				// then COOKIE are valid then get data from them and populate hidden fields for the password change form
				$args['template'] = 'reset-password.php';

				$login     = '';
				$rp_key    = '';
				$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
				if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
					list( $login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
				}

				$resetpass_form = UM()->frontend()->form(
					array(
						'id' => 'um-resetpass',
					)
				);

				$resetpass_form->add_notice(
					__( 'Enter your new password below and confirm it.', 'ultimate-member' ),
					'resetpass-info'
				);

				$resetpass_form_args = array(
					'id'        => 'um-resetpass',
					'class'     => 'um-top-label um-single-button um-center-always',
					'prefix_id' => '',
					'fields'    => array(
						array(
							'type'        => 'password',
							'label'       => __( 'New Password', 'ultimate-member' ),
							'id'          => 'user_password',
							'required'    => true,
							'value'       => '',
							'placeholder' => __( 'Enter new Password', 'ultimate-member' ),
						),
						array(
							'type'        => 'password',
							'label'       => __( 'Confirm Password', 'ultimate-member' ),
							'id'          => 'confirm_user_password',
							'required'    => true,
							'value'       => '',
							'placeholder' => __( 'Confirm Password', 'ultimate-member' ),
						)
					),
					'hiddens'   => array(
						'um-action' => 'password-reset',
						'rp_key'    => $rp_key,
						'login'     => $login,
						'nonce'     => wp_create_nonce( 'um-resetpass' ),
					),
					'buttons'   => array(
						'save-password' => array(
							'type'  => 'submit',
							'label' => __( 'Save Password', 'ultimate-member' ),
						),
					),
				);
				/**
				 * Filters arguments for the Reset Password form in the Password Reset shortcode content.
				 *
				 * Note: Use this hook for adding custom fields, hiddens or buttons to your Reset Password form.
				 *
				 * @since 3.0.0
				 * @hook um_resetpass_form_args
				 *
				 * @param {array} $resetpass_form_args Reset Password form arguments.
				 */
				$resetpass_form_args = apply_filters( 'um_resetpass_form_args', $resetpass_form_args );

				$resetpass_form->set_data( $resetpass_form_args );

				$t_args = array(
					'resetpass_form' => $resetpass_form,
				);
			} else {
				// show lostpassword form by default
				$lostpassword_form = null;
				$args['template']  = 'lostpassword.php';

				if ( ( ! isset( $_GET['checkemail'] ) || 'confirm' !== sanitize_key( $_GET['checkemail'] ) ) && ( ! isset( $_GET['checklogin'] ) || 'password_changed' !== sanitize_key( $_GET['checklogin'] ) ) ) {
					$lostpassword_form = UM()->frontend()->form(
						array(
							'id' => 'um-lostpassword',
						)
					);

					if ( ! empty( $_GET['error'] ) ) {
						if ( 'expiredkey' === sanitize_key( $_GET['error'] ) ) {
							$lostpassword_form->add_notice(
								__( 'Your password reset link has expired. Please request a new link below.', 'ultimate-member' ),
								'expiredkey-error'
							);
						} elseif ( 'invalidkey' === sanitize_key( $_GET['error'] ) ) {
							$lostpassword_form->add_notice(
								__( 'Your password reset link appears to be invalid. Please request a new link below.', 'ultimate-member' ),
								'invalidkey-error'
							);
						}
					} else {
						$lostpassword_form->add_notice(
							__( 'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.', 'ultimate-member' ),
							'lostpassword-info'
						);
					}

					$lostpassword_form_args = array(
						'id'        => 'um-lostpassword',
						'class'     => 'um-top-label um-single-button um-center-always',
						'prefix_id' => '',
						'fields'    => array(
							array(
								'type'        => 'text',
								'label'       => __( 'Username or Email Address', 'ultimate-member' ),
								'id'          => 'user_login',
								'required'    => true,
								'value'       => '',
								'placeholder' => __( 'Enter Username or Email Address', 'ultimate-member' ),
								'validation'  => 'user_login',
							)
						),
						'hiddens'   => array(
							'um-action' => 'password-reset-request',
							'nonce'     => wp_create_nonce( 'um-lostpassword' ),
						),
						'buttons'   => array(
							'new-password' => array(
								'type'  => 'submit',
								'label' => __( 'Get New Password', 'ultimate-member' ),
							),
						),
					);
					/**
					 * Filters arguments for the Lost Password form in the Password Reset shortcode content.
					 *
					 * Note: Use this hook for adding custom fields, hiddens or buttons to your Lost Password form.
					 *
					 * @since 3.0.0
					 * @hook um_lostpassword_form_args
					 *
					 * @param {array} $lostpassword_form_args Lost Password form arguments.
					 */
					$lostpassword_form_args = apply_filters( 'um_lostpassword_form_args', $lostpassword_form_args );

					$lostpassword_form->set_data( $lostpassword_form_args );
				}

				$t_args = array(
					'lostpassword_form' => $lostpassword_form,
				);
			}

			/**
			 * Fires before Password Reset form loading inside shortcode callback.
			 *
			 * Note: Use this hook for adding some custom content before the password reset form or enqueue scripts when password reset form shortcode loading.
			 * Legacy v2.x hooks: 'um_before_password_form_is_loaded', 'um_before_form_is_loaded'
			 *
			 * @since 3.0.0
			 * @hook um_pre_password_shortcode
			 *
			 * @param {array} $args Password reset form shortcode arguments.
			 */
			do_action( 'um_pre_password_shortcode', $args );

			$template = $args['template'];
			unset( $args['template'] );
			$t_args = array_merge( $t_args, $args );

			wp_enqueue_script('um-password-reset' );

			$form_styling = UM()->options()->get( 'form_styling' );
			switch ( $form_styling ) {
				case 'none':
					break;
				case 'layout_only':
					wp_enqueue_style( 'um-password-reset-base' );
					break;
				default:
					wp_enqueue_style( 'um-password-reset-full' );
					break;
			}

			return um_get_template_html( $template, $t_args );
		}

		/**
		 * Shortcode for the displaying login form
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function login_form( $args = array() ) {
			global $error;

			if ( is_user_logged_in() ) {
				return '';
			}

			/** There is possible to use 'shortcode_atts_ultimatemember_login' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
			$args = shortcode_atts(
				array(
//					'max_width'      => '450px',
//					'align'          => 'center',
					'login_button'       => __( 'Log In', 'ultimate-member' ),
					'show_remember'      => true,
					'show_forgot'        => true,
					'login_redirect'     => '',
					'login_redirect_url' => '',
					'form_id'            => '', // backward compatibility argument
				),
				$args,
				'ultimatemember_login'
			);

			$form_styling = UM()->options()->get( 'form_styling' );
			switch ( $form_styling ) {
				case 'none':
					break;
				case 'layout_only':
					wp_enqueue_script('um-login' );
					wp_enqueue_style( 'um-login-base' );
					break;
				default:
					wp_enqueue_script('um-login' );
					wp_enqueue_style( 'um-login-full' );
					break;
			}

			$login_args = array(
				'form_id'           => 'um-loginform',
				'um_login_form'     => true,
				'um_login_form_id'  => absint( $args['form_id'] ),
				'um_login_redirect' => in_array( $args['login_redirect'], array_keys( UM()->config()->get( 'login_redirect_options' ) ), true ) ? $args['login_redirect'] : '', // if empty then get default from role
				'um_show_forgot'    => (bool) $args['show_forgot'],
				'echo'              => true,
				'remember'          => (bool) $args['show_remember'],
				'label_username'    => __( 'Username or Email Address', 'ultimate-member' ),
				'label_password'    => __( 'Password', 'ultimate-member' ),
				'label_remember'    => __( 'Remember Me', 'ultimate-member' ),
				'label_log_in'      => ! empty( $args['login_button'] ) ? $args['login_button'] : __( 'Log In', 'ultimate-member' ),
			);

			if ( ! isset( $_GET['redirect_to'] ) || empty( $_GET['redirect_to'] ) ) {
				$redirect = '';
				if ( in_array( $args['login_redirect'], array_keys( UM()->config()->get( 'login_redirect_options' ) ), true ) ) {
					switch ( $args['login_redirect'] ) {
						case 'redirect_profile':
							$redirect = um_get_predefined_page_url( 'user' );
							break;
						case 'redirect_url':
							$redirect = esc_url_raw( $args['login_redirect_url'] );
							break;
						case 'redirect_admin':
							$redirect = get_admin_url();
							break;
						case 'refresh':
							$redirect = '';
							break;
						default:
							$redirect = apply_filters( "um_login_form_custom_redirect_{$args['login_redirect']}", '', $args );
							break;
					}
				}

				$login_args['redirect'] = $redirect;
			}

			/**
			 * Filters Ultimate Member Login Form arguments for init it through `wp_login_form()`.
			 *
			 * @since 3.0
			 * @hook um_login_form_args
			 *
			 * @param {array} $login_args Attributes for `wp_login_form()` function init.
			 * @param {array} $args       Login form shortcode attributes.
			 *
			 * @return {array} Attributes for `wp_login_form()` function.
			 */
			$login_args = apply_filters( 'um_login_form_args', $login_args, $args );

			$wp_error = new \WP_Error();

			$error_codes = array(
				'empty'                                  => sprintf( __( '%s and %s are required.', 'ultimate-member' ), $login_args['label_username'], $login_args['label_password'] ),
				'failed'                                 => __( 'Invalid username, email address or incorrect password.', 'ultimate-member' ),
				'um_blocked_email'                       => __( 'This email address has been blocked.', 'ultimate-member' ),
				'um_blocked_domain'                      => __( 'This email address domain has been blocked.', 'ultimate-member' ),
				'um_account_inactive'                    => __( 'Your account has been disabled.', 'ultimate-member' ),
				'um_account_awaiting_admin_review'       => __( 'Your account has not been approved yet.', 'ultimate-member' ),
				'um_account_awaiting_email_confirmation' => __( 'Your account is awaiting e-mail verification.', 'ultimate-member' ),
				'um_account_rejected'                    => __( 'Your membership request has been rejected.', 'ultimate-member' ),
			);
			/**
			 * Filters Ultimate Member Login Form errors and their codes.
			 *
			 * @since 3.0
			 * @hook um_login_form_error_codes
			 *
			 * @param {array} $error_codes Error codes in format $error_code => $error_message.
			 * @param {array} $args        Login form attributes.
			 *
			 * @return {array} Error codes.
			 */
			$error_codes = apply_filters( 'um_login_form_error_codes', $error_codes, $args, $login_args );

			if ( ! empty( $args['show_forgot'] ) ) {
				// Change lostpassword URL only when using Ultimate Member Login Form
				add_filter( 'lostpassword_url', array( &$this, 'change_lostpassword_url' ), 10, 1 );
			}

			ob_start();
			?>

			<div class="um um-login">
				<?php echo do_action( 'um_before_form', array() ); ?>

				<?php
				// In case a plugin uses $error rather than the $wp_errors object.
				if ( ! empty( $error ) ) {
					$wp_error->add( 'error', $error );
					unset( $error );
				}

				if ( ! empty( $_GET['login'] ) ) {
					$error_code = sanitize_key( $_GET['login'] );

					if ( array_key_exists( $error_code, $error_codes ) ) {
						$wp_error->add( $error_code, $error_codes[ $error_code ] );
					}
				}

				if ( $wp_error->has_errors() ) {
					$errors   = '';
					$messages = '';

					foreach ( $wp_error->get_error_codes() as $code ) {
						$severity = $wp_error->get_error_data( $code );
						foreach ( $wp_error->get_error_messages( $code ) as $error_message ) {
							if ( 'message' === $severity ) {
								$messages .= '	' . $error_message . "<br />\n";
							} else {
								$errors .= '	' . $error_message . "<br />\n";
							}
						}
					}

					if ( ! empty( $errors ) ) {
						/** This filter is documented in wp-login.php */
						echo '<p class="um-frontend-form-error">' . apply_filters( 'login_errors', $errors ) . "</p>\n";
					}

					if ( ! empty( $messages ) ) {
						/** This filter is documented in wp-login.php */
						echo '<p class="um-frontend-form-notice">' . apply_filters( 'login_messages', $messages ) . "</p>\n";
					}
				}
				?>

				<?php wp_login_form( $login_args ); ?>

				<p class="login-sign-up">
					<span><?php esc_html_e( 'Don\'t have an account?', 'ultimate-member' ); ?></span>
					<a class="um-link" href="<?php echo esc_url( um_get_predefined_page_url( 'register' ) ); ?>">
						<?php esc_html_e( 'Sign up', 'ultimate-member' ); ?>
					</a>
				</p>
			</div>

			<?php
			$content = ob_get_clean();

			if ( ! empty( $args['show_forgot'] ) ) {
				remove_filter( 'lostpassword_url', array( &$this, 'change_lostpassword_url' ), 10 );
			}

			return $content;
		}

		/**
		 * Shortcode for the displaying Ultimate Member forms
		 *
		 * @param array $args
		 *
		 * @return string
		 */
		public function common_forms( $args = array() ) {
			/** There is possible to use 'shortcode_atts_ultimatemember' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
			$args = shortcode_atts(
				array(
					'form_id' => false,
				),
				$args,
				'ultimatemember'
			);

			if ( empty( $args['form_id'] ) || ! is_numeric( $args['form_id'] ) ) {
				return '';
			}

			$form = get_post( $args['form_id'] );
			if ( empty( $form ) ) {
				return '';
			}

			if ( 'publish' !== $form->post_status ) {
				return '';
			}

			// backward compatibility for login forms and using new login form shortcode
			$mode = get_post_meta( $args['form_id'],  '_um_mode', true );
			if ( 'login' === $mode ) {
				$login_primary_btn_word = get_post_meta( $args['form_id'], '_um_login_primary_btn_word', true );
				$show_remember          = get_post_meta( $args['form_id'], '_um_login_show_rememberme', true );
				$show_forgot            = get_post_meta( $args['form_id'], '_um_login_forgot_pass_link', true );
				$login_redirect         = get_post_meta( $args['form_id'], '_um_login_after_login', true );

				$login_args = array(
					'login_button'   => ! empty( $login_primary_btn_word ) ? $login_primary_btn_word : __( 'Log In', 'ultimate-member' ),
					'show_remember'  => (bool) $show_remember,
					'show_forgot'    => (bool) $show_forgot,
					'form_id'        => absint( $args['form_id'] ),
					'login_redirect' => $login_redirect,
				);
				/**
				 * Filters Ultimate Member Login Form legacy arguments for ultimatemember_login shortcode.
				 *
				 * @since 3.0
				 * @hook um_login_form_legacy_args
				 *
				 * @param {array} $login_args Attributes for `ultimatemember_login` shortcode.
				 * @param {array} $args       Form data.
				 *
				 * @return {array} Attributes for `wp_login_form()` function.
				 */
				$login_args = apply_filters( 'um_login_form_legacy_args', $login_args, $args );

				return $this->login_form( $login_args );
			}

			return '';
		}

		/**
		 * Change lost password URL in UM Login form
		 *
		 * @param  string $lostpassword_url
		 * @return string
		 */
		public function change_lostpassword_url( $lostpassword_url ) {
			$url = um_get_predefined_page_url( 'password-reset' );
			if ( false !== $url ) {
				$lostpassword_url = $url;
			}

			return $lostpassword_url;
		}
	}
}
