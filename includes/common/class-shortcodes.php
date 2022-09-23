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
					'class'     => 'um-top-label um-center-always',
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
						'class'     => 'um-top-label um-center-always',
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
			wp_enqueue_style( 'um-password-reset' );

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
			if ( is_user_logged_in() ) {
				return '';
			}

			/** There is possible to use 'shortcode_atts_ultimatemember_login' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
			$args = shortcode_atts(
				array(
					'max_width'     => '450px',
					'align'         => 'center',
					'login_button'  => __( 'Log In', 'ultimate-member' ),
					'show_remember' => true,
					'show_forgot'   => true,
				),
				$args,
				'ultimatemember_login'
			);

			wp_enqueue_script('um-login' );
			wp_enqueue_style( 'um-login' );

			$login_args = array(
				'form_id'        => 'loginform',
				'um_login_form'  => true,
				'echo'           => true,
				'remember'       => (bool) $args['show_remember'],
				'label_username' => __( 'Username or Email Address', 'ultimate-member' ),
				'label_password' => __( 'Password', 'ultimate-member' ),
				'label_remember' => __( 'Remember Me', 'ultimate-member' ),
				'label_log_in'   => ! empty( $args['login_button'] ) ? $args['login_button'] : __( 'Log In', 'ultimate-member' ),
			);

			/** This filter is documented in ultimate-member/includes/frontend/class-form.php */
			$disable_star = apply_filters( 'um_frontend_forms_required_star_disabled', false );
			if ( ! $disable_star ) {
				$login_args['label_username'] = $login_args['label_username'] . ' *';
				$login_args['label_password'] = $login_args['label_password'] . ' *';
			}

			$errors = new \WP_Error();

			/**
			 * Filters the login page errors.
			 *
			 * @since 3.6.0
			 *
			 * @param \WP_Error $errors      WP Error object.
			 * @param string   $redirect_to Redirect destination URL.
			 */
			$errors = apply_filters( 'um_wp_login_errors', $errors, '' );

			ob_start();
			?>

			<div class="um um-login">
				<?php echo do_action( 'um_before_form', array() ); ?>

				<?php if ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) { ?>
					<p class="um-notice err um-error-code-authentication_failed">
						<?php _e( 'Invalid username, email address or incorrect password.', 'ultimate-member' ); ?>
					</p>
				<?php } ?>

				<?php wp_login_form( $login_args ); ?>

				<?php if ( ! empty( $args['show_forgot'] ) ) { ?>
					<p id="nav">
						<a href="<?php echo esc_url( um_get_predefined_page_url( 'password-reset' ) ); ?>">
							<?php esc_html_e( 'Forgot your password?', 'ultimate-member' ); ?>
						</a>
					</p>
				<?php } ?>
			</div>
			<?php
			return ob_get_clean();
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

				$login_args = array(
					'login_button'  => ! empty( $login_primary_btn_word ) ? $login_primary_btn_word : __( 'Log In', 'ultimate-member' ),
					'show_remember' => (bool) $show_remember,
					'show_forgot'   => (bool) $show_forgot,
				);
				return $this->login_form( $login_args );
			}

			return '';
		}
	}
}
