<?php namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * Emoji list
	 * @var array
	 */
	public $emoji = array();

	/**
	 * Check if there is a global form message.
	 * @var bool
	 */
	public $message_mode = false;

	/**
	 * @var array
	 */
	public $loop = array();

	/**
	 * @var null|array
	 */
	public $set_args = null;

	/**
	 * Shortcodes constructor.
	 */
	public function __construct() {
		add_shortcode( 'um_loggedin', array( &$this, 'um_loggedin' ) );
		add_shortcode( 'um_loggedout', array( &$this, 'um_loggedout' ) );
		add_shortcode( 'um_show_content', array( &$this, 'um_shortcode_show_content_for_role' ) );

		add_shortcode( 'ultimatemember_password', array( &$this, 'reset_password_form' ) );
		add_shortcode( 'ultimatemember_login', array( &$this, 'login_form' ) );
		add_shortcode( 'ultimatemember', array( &$this, 'common_forms' ) );

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_emoji_base_uri
		 * @description Change Emoji base URL
		 * @input_vars
		 * [{"var":"$url","type":"string","desc":"Base URL"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_emoji_base_uri', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_emoji_base_uri', 'my_emoji_base_uri', 10, 1 );
		 * function my_emoji_base_uri( $url ) {
		 *     // your code here
		 *     return $url;
		 * }
		 * ?>
		 */
		$base_uri = apply_filters( 'um_emoji_base_uri', 'https://s.w.org/images/core/emoji/' );

		$this->emoji[':)'] = $base_uri . '72x72/1f604.png';
		$this->emoji[':smiley:'] = $base_uri . '72x72/1f603.png';
		$this->emoji[':D'] = $base_uri . '72x72/1f600.png';
		$this->emoji[':$'] = $base_uri . '72x72/1f60a.png';
		$this->emoji[':relaxed:'] = $base_uri . '72x72/263a.png';
		$this->emoji[';)'] = $base_uri . '72x72/1f609.png';
		$this->emoji[':heart_eyes:'] = $base_uri . '72x72/1f60d.png';
		$this->emoji[':kissing_heart:'] = $base_uri . '72x72/1f618.png';
		$this->emoji[':kissing_closed_eyes:'] = $base_uri . '72x72/1f61a.png';
		$this->emoji[':kissing:'] = $base_uri . '72x72/1f617.png';
		$this->emoji[':kissing_smiling_eyes:'] = $base_uri . '72x72/1f619.png';
		$this->emoji[';P'] = $base_uri . '72x72/1f61c.png';
		$this->emoji[':P'] = $base_uri . '72x72/1f61b.png';
		$this->emoji[':stuck_out_tongue_closed_eyes:'] = $base_uri . '72x72/1f61d.png';
		$this->emoji[':flushed:'] = $base_uri . '72x72/1f633.png';
		$this->emoji[':grin:'] = $base_uri . '72x72/1f601.png';
		$this->emoji[':pensive:'] = $base_uri . '72x72/1f614.png';
		$this->emoji[':relieved:'] = $base_uri . '72x72/1f60c.png';
		$this->emoji[':unamused'] = $base_uri . '72x72/1f612.png';
		$this->emoji[':('] = $base_uri . '72x72/1f61e.png';
		$this->emoji[':persevere:'] = $base_uri . '72x72/1f623.png';
		$this->emoji[":'("] = $base_uri . '72x72/1f622.png';
		$this->emoji[':joy:'] = $base_uri . '72x72/1f602.png';
		$this->emoji[':sob:'] = $base_uri . '72x72/1f62d.png';
		$this->emoji[':sleepy:'] = $base_uri . '72x72/1f62a.png';
		$this->emoji[':disappointed_relieved:'] = $base_uri . '72x72/1f625.png';
		$this->emoji[':cold_sweat:'] = $base_uri . '72x72/1f630.png';
		$this->emoji[':sweat_smile:'] = $base_uri . '72x72/1f605.png';
		$this->emoji[':sweat:'] = $base_uri . '72x72/1f613.png';
		$this->emoji[':weary:'] = $base_uri . '72x72/1f629.png';
		$this->emoji[':tired_face:'] = $base_uri . '72x72/1f62b.png';
		$this->emoji[':fearful:'] = $base_uri . '72x72/1f628.png';
		$this->emoji[':scream:'] = $base_uri . '72x72/1f631.png';
		$this->emoji[':angry:'] = $base_uri . '72x72/1f620.png';
		$this->emoji[':rage:'] = $base_uri . '72x72/1f621.png';
		$this->emoji[':triumph'] = $base_uri . '72x72/1f624.png';
		$this->emoji[':confounded:'] = $base_uri . '72x72/1f616.png';
		$this->emoji[':laughing:'] = $base_uri . '72x72/1f606.png';
		$this->emoji[':yum:'] = $base_uri . '72x72/1f60b.png';
		$this->emoji[':mask:'] = $base_uri . '72x72/1f637.png';
		$this->emoji[':cool:'] = $base_uri . '72x72/1f60e.png';
		$this->emoji[':sleeping:'] = $base_uri . '72x72/1f634.png';
		$this->emoji[':dizzy_face:'] = $base_uri . '72x72/1f635.png';
		$this->emoji[':astonished:'] = $base_uri . '72x72/1f632.png';
		$this->emoji[':worried:'] = $base_uri . '72x72/1f61f.png';
		$this->emoji[':frowning:'] = $base_uri . '72x72/1f626.png';
		$this->emoji[':anguished:'] = $base_uri . '72x72/1f627.png';
		$this->emoji[':smiling_imp:'] = $base_uri . '72x72/1f608.png';
		$this->emoji[':imp:'] = $base_uri . '72x72/1f47f.png';
		$this->emoji[':open_mouth:'] = $base_uri . '72x72/1f62e.png';
		$this->emoji[':grimacing:'] = $base_uri . '72x72/1f62c.png';
		$this->emoji[':neutral_face:'] = $base_uri . '72x72/1f610.png';
		$this->emoji[':confused:'] = $base_uri . '72x72/1f615.png';
		$this->emoji[':hushed:'] = $base_uri . '72x72/1f62f.png';
		$this->emoji[':no_mouth:'] = $base_uri . '72x72/1f636.png';
		$this->emoji[':innocent:'] = $base_uri . '72x72/1f607.png';
		$this->emoji[':smirk:'] = $base_uri . '72x72/1f60f.png';
		$this->emoji[':expressionless:'] = $base_uri . '72x72/1f611.png';
	}

	/**
	 * Logged-in only content
	 *
	 * @param array $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function um_loggedin( $args = array(), $content = '' ) {
		$args = shortcode_atts(
			array(
				'lock_text' => __( 'This content has been restricted to logged in users only. Please <a href="{login_referrer}">login</a> to view this content.', 'ultimate-member' ),
				'show_lock' => 'yes',
			),
			$args,
			'um_loggedin'
		);

		ob_start();

		if ( ! is_user_logged_in() ) {
			if ( 'no' === $args['show_lock'] ) {
				echo '';
			} else {
				$args['lock_text'] = $this->convert_locker_tags( $args['lock_text'] );
				um_get_template( 'login-to-view.php', $args );
			}
		} else {
			echo apply_shortcodes( $this->convert_locker_tags( wpautop( $content ) ) );
		}

		$output = ob_get_clean();

		return htmlspecialchars_decode( $output, ENT_NOQUOTES );
	}

	/**
	 * Logged-out only content
	 *
	 * @param array $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function um_loggedout( $args = array(), $content = '' ) {
		if ( is_user_logged_in() ) {
			return ''; // Hide for logged in users
		}

		$output = apply_shortcodes( wpautop( $content ) );
		return $output;
	}

	/**
	 * Shortcode: Show custom content to specific role
	 *
	 * Show content to specific roles
	 * [um_show_content roles='member'] <!-- insert content here -->  [/um_show_content]
	 * You can add multiple target roles, just use ',' e.g.  [um_show_content roles='member,candidates,pets']
	 *
	 * Hide content from specific roles
	 * [um_show_content not='contributors'] <!-- insert content here -->  [/um_show_content]
	 * You can add multiple target roles, just use ',' e.g.  [um_show_content roles='member,candidates,pets']
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @return string
	 */
	public function um_shortcode_show_content_for_role( $atts = array() , $content = '' ) {
		global $user_ID;

		if ( ! is_user_logged_in() ) {
			return '';
		}

		$a = shortcode_atts(
			array(
				'roles' => '',
				'not' => '',
				'is_profile' => false,
			),
			$atts,
			'um_show_content'
		);

		if ( $a['is_profile'] ) {
			um_fetch_user( um_profile_id() );
		} else {
			um_fetch_user( $user_ID );
		}

		$current_user_roles = um_user( 'roles' );

		if ( ! empty( $a['not'] ) && ! empty( $a['roles'] ) ) {
			return apply_shortcodes( $this->convert_locker_tags( $content ) );
		}

		if ( ! empty( $a['not'] ) ) {
			$not_in_roles = explode( ",", $a['not'] );

			if ( is_array( $not_in_roles ) && ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, $not_in_roles ) ) <= 0 ) ) {
				return apply_shortcodes( $this->convert_locker_tags( $content ) );
			}
		} else {
			$roles = explode( ",", $a['roles'] );

			if ( ! empty( $current_user_roles ) && is_array( $roles ) && count( array_intersect( $current_user_roles, $roles ) ) > 0 ) {
				return apply_shortcodes( $this->convert_locker_tags( $content ) );
			}
		}

		return '';
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
						'class' => array(
							'um-button-primary',
						),
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
							'class' => array(
								'um-button-primary',
							),
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

		$styling = UM()->options()->get( 'styling' );
		switch ( $styling ) {
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

		$styling = UM()->options()->get( 'styling' );
		switch ( $styling ) {
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
				<a class="um-link um-link-always-active" href="<?php echo esc_url( um_get_predefined_page_url( 'register' ) ); ?>">
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

		/**
		 * Fires just before the Ultimate Member form is inited.
		 *
		 * Note: $mode = login||register||profile and when you paste `add_action` please use
		 * um_pre_login_shortcode - for login
		 * um_pre_register_shortcode - for register
		 * um_pre_profile_shortcode - for profile
		 *
		 * @since 1.0.0
		 * @hook um_pre_{$mode}_shortcode
		 *
		 * @param {array} $args Ultimate Member form arguments.
		 */
		do_action( "um_pre_{$mode}_shortcode", $args );

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
		} elseif ( 'register' === $mode ) {
			$fields = get_post_meta( $args['form_id'], '_um_custom_fields', true );
			if ( empty( $fields ) ) {
				return '';
			}

			wp_enqueue_script('um-register' );

			$styling = UM()->options()->get( 'styling' );
			switch ( $styling ) {
				case 'none':
					break;
				case 'layout_only':
					wp_enqueue_style( 'um-register-base' );
					break;
				default:
					wp_enqueue_style( 'um-register-full' );
					break;
			}

			$register_primary_btn_word = get_post_meta( $args['form_id'], '_um_register_primary_btn_word', true );

			$register_form = UM()->frontend()->form(
				array(
					'id' => 'um-register',
				)
			);

			$register_form_args = array(
				'id'        => 'um-register',
				'class'     => 'um-top-label um-single-button um-center-always',
				'prefix_id' => '',
				'fields'    => array(),
				'hiddens'   => array(
					'um-action' => 'register',
					'nonce'     => wp_create_nonce( 'um-register' ),
				),
				'buttons'   => array(
					'register' => array(
						'type'  => 'submit',
						'label' => $register_primary_btn_word,
						'class' => array(
							'um-button-primary',
						),
					),
				),
			);

			$temp_fields = $fields;
			foreach ( $fields as $key => $field_data ) {
				if ( ! array_key_exists( 'type', $field_data ) ) {
					continue;
				}

				// extends form fields with confirm password if needed
				if ( 'password' === $field_data['type'] ) {
					if ( ! empty( $field_data['force_confirm_pass'] ) ) {
						$confirm_pass_data = $field_data;
						unset( $confirm_pass_data['force_confirm_pass'] );
						$confirm_pass_data['label']       = ! empty( $field_data['label_confirm_pass'] ) ? $field_data['label_confirm_pass'] : '';
						$confirm_pass_data['description'] = ! empty( $field_data['description_confirm_pass'] ) ? $field_data['description_confirm_pass'] : '';
						$confirm_pass_data['placeholder'] = ! empty( $field_data['placeholder_confirm_pass'] ) ? $field_data['placeholder_confirm_pass'] : '';
						$confirm_pass_data['metakey']     = 'confirm_' . $key;
						$temp_fields = UM()->array_insert_after( $temp_fields, $key, array( 'confirm_' . $key => $confirm_pass_data ) );
					}
				}
			}
			$fields = $temp_fields;

			foreach ( $fields as $key => $field_data ) {
				if ( ! array_key_exists( 'type', $field_data ) ) {
					continue;
				}

				$label = '';
				if ( array_key_exists( 'label', $field_data ) && ! empty( $field_data['label'] ) ) {
					$label = $field_data['label'];
				}/* elseif ( array_key_exists( 'title', $field_data ) && ! empty( $field_data['title'] ) ) {
					$label = $field_data['title'];
				}*/

				$field_args = array(
					'type'        => $field_data['type'],
					'label'       => $label,
					'id'          => ! empty( $field_data['metakey'] ) ? $field_data['metakey'] : $key,
					'required'    => array_key_exists( 'required', $field_data ) ? (bool) $field_data['required'] : false,
					'value'       => '',
					'placeholder' => array_key_exists( 'placeholder', $field_data ) ? $field_data['placeholder'] : '',
					'validation'  => array_key_exists( 'validate', $field_data ) ? $field_data['validate'] : '',
					'description' => array_key_exists( 'description', $field_data ) ? $field_data['description'] : '',
				);

				if ( 'time' === $field_args['type'] ) {
					unset( $field_args['placeholder'] );

					if ( ! empty( $field_data['min'] ) ) {
						$field_args['min'] = $field_data['min'];
					}

					if ( ! empty( $field_data['max'] ) ) {
						$field_args['max'] = $field_data['max'];
					}

					if ( ! empty( $field_data['step'] ) && is_numeric( $field_data['step'] ) ) {
						$field_args['step'] = $field_data['step'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'date' === $field_args['type'] ) {
					unset( $field_args['placeholder'] );

					if ( ! empty( $field_data['min'] ) ) {
						$field_args['min'] = $field_data['min'];
					}

					if ( ! empty( $field_data['max'] ) ) {
						$field_args['max'] = $field_data['max'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'number' === $field_args['type'] ) {
					if ( array_key_exists( 'min', $field_data ) && is_numeric( $field_data['min'] ) ) {
						$field_args['min'] = $field_data['min'];
					}

					if ( array_key_exists( 'max', $field_data ) && is_numeric( $field_data['max'] ) ) {
						$field_args['max'] = $field_data['max'];
					}

					if ( ! empty( $field_data['step'] ) && is_numeric( $field_data['step'] ) ) {
						$field_args['step'] = $field_data['step'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'password' === $field_args['type'] ) {
					if ( ! empty( $field_data['pattern'] ) ) {
						$field_args['pattern'] = $field_data['pattern'];
					}

					if ( array_key_exists( 'min_chars', $field_data ) && is_numeric( $field_data['min_chars'] ) ) {
						$field_args['minlength'] = $field_data['min_chars'];
					}

					if ( array_key_exists( 'max_chars', $field_data ) && is_numeric( $field_data['max_chars'] ) ) {
						$field_args['maxlength'] = $field_data['max_chars'];
					}
				}

				if ( 'email' === $field_args['type'] ) {
					if ( ! empty( $field_data['pattern'] ) ) {
						$field_args['pattern'] = $field_data['pattern'];
					}

					if ( array_key_exists( 'min_chars', $field_data ) && is_numeric( $field_data['min_chars'] ) ) {
						$field_args['minlength'] = $field_data['min_chars'];
					}

					if ( array_key_exists( 'max_chars', $field_data ) && is_numeric( $field_data['max_chars'] ) ) {
						$field_args['maxlength'] = $field_data['max_chars'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'url' === $field_args['type'] ) {
					if ( ! empty( $field_data['pattern'] ) ) {
						$field_args['pattern'] = $field_data['pattern'];
					}

					if ( array_key_exists( 'min_chars', $field_data ) && is_numeric( $field_data['min_chars'] ) ) {
						$field_args['minlength'] = $field_data['min_chars'];
					}

					if ( array_key_exists( 'max_chars', $field_data ) && is_numeric( $field_data['max_chars'] ) ) {
						$field_args['maxlength'] = $field_data['max_chars'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'tel' === $field_args['type'] ) {
					if ( ! empty( $field_data['pattern'] ) ) {
						$field_args['pattern'] = $field_data['pattern'];
					}

					if ( array_key_exists( 'min_chars', $field_data ) && is_numeric( $field_data['min_chars'] ) ) {
						$field_args['minlength'] = $field_data['min_chars'];
					}

					if ( array_key_exists( 'max_chars', $field_data ) && is_numeric( $field_data['max_chars'] ) ) {
						$field_args['maxlength'] = $field_data['max_chars'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'text' === $field_args['type'] ) {
					if ( ! empty( $field_data['pattern'] ) ) {
						$field_args['pattern'] = $field_data['pattern'];
					}

					if ( array_key_exists( 'min_chars', $field_data ) && is_numeric( $field_data['min_chars'] ) ) {
						$field_args['minlength'] = $field_data['min_chars'];
					}

					if ( array_key_exists( 'max_chars', $field_data ) && is_numeric( $field_data['max_chars'] ) ) {
						$field_args['maxlength'] = $field_data['max_chars'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'select' === $field_args['type'] ) {
					$default = array_column( $field_data['options'], 'default' );
					$default_keys = array_column( $field_data['options'], 'key' );

					$default = array_combine( $default_keys, $default );
					$default = array_keys( array_filter( $default ) );

					$options = array_combine( array_column( $field_data['options'], 'key' ), array_column( $field_data['options'], 'value' ) );

					if ( ! empty( $default ) ) {
						$field_args['value'] = $default;
					}
					$field_args['options'] = $options;

					if ( ! empty( $field_data['is_multi'] ) ) {
						$field_args['multi'] = true;
					}
				}

				if ( 'radio' === $field_args['type'] || 'checkbox' === $field_args['type'] ) {
					$default = array_column( $field_data['options'], 'default' );
					$default_keys = array_column( $field_data['options'], 'key' );

					$default = array_combine( $default_keys, $default );
					$default = array_keys( array_filter( $default ) );

					if ( ! empty( $default ) && 'radio' === $field_args['type'] ) {
						$default = $default[0];
					}

					$columns_layout = ! empty( $field_data['choices_layout'] ) ? 'um-' . $field_data['choices_layout'] : 'um-col-1';
					$field_args['columns_layout'] = $columns_layout;

					$options = array_combine( array_column( $field_data['options'], 'key' ), array_column( $field_data['options'], 'value' ) );
					if ( ! empty( $default ) ) {
						$field_args['value'] = $default;
					}
					$field_args['options'] = $options;
				}

				if ( 'bool' === $field_args['type'] ) {
					$field_args['type'] = 'checkbox';
					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
					$field_args['options'] = array( '1' => $field_data['label'] );

					$field_args['hide_label'] = true;
				}

				if ( 'textarea' === $field_args['type'] ) {
					if ( ! empty( $field_data['html'] ) ) {
						$field_args['type'] = 'wp_editor';
					} else {
						$field_args['type'] = 'textarea';
					}

					if ( array_key_exists( 'min_chars', $field_data ) && is_numeric( $field_data['min_chars'] ) ) {
						$field_args['minlength'] = $field_data['min_chars'];
					}

					if ( array_key_exists( 'max_chars', $field_data ) && is_numeric( $field_data['max_chars'] ) ) {
						$field_args['maxlength'] = $field_data['max_chars'];
					}

					if ( array_key_exists( 'rows', $field_data ) && is_numeric( $field_data['rows'] ) ) {
						$field_args['rows'] = $field_data['rows'];
					}

					if ( array_key_exists( 'default', $field_data ) ) {
						$field_args['value'] = $field_data['default'];
					}
				}

				if ( 'block' === $field_args['type'] || 'shortcode' === $field_args['type'] ) {
					$field_args['content'] = $field_data['content'];
				}

				if ( 'spacing' === $field_args['type'] ) {
					$field_args['size'] = $field_data['spacing'];
				}

				if ( 'divider' === $field_args['type'] ) {
					$field_args['divider_text'] = ! empty( $field_data['divider_text'] ) ? $field_data['divider_text'] : '';

					$field_args['style'] = ! empty( $field_data['style'] ) ? $field_data['style'] : 'solid';
					$field_args['color'] = ! empty( $field_data['color'] ) ? $field_data['color'] : '#475467'; // grey-600 if empty
					$field_args['width'] = ! empty( $field_data['width'] ) ? $field_data['width'] : 1; // 1px if empty
				}

				$register_form_args['fields'][] = $field_args;
			}

			//var_dump( $register_form_args['fields'] );
//			var_dump( array_unique( array_column( $register_form_args['fields'], 'type' ) ) );

			/**
			 * Filters arguments for the Registration form in the Ultimate Member form shortcode content.
			 *
			 * Note: Use this hook for adding custom fields, hiddens or buttons to your Registration form.
			 *
			 * @since 3.0.0
			 * @hook um_register_form_args
			 *
			 * @param {array} $register_form_args Register form arguments.
			 */
			$register_form_args = apply_filters( 'um_register_form_args', $register_form_args );

			$register_form->set_data( $register_form_args );

			$args['register_form'] = $register_form;

			return um_get_template_html( 'register.php', $args );
		} elseif ( 'profile' === $mode ) {
			wp_enqueue_script('um-profile' );
			wp_enqueue_style('um-profile-full' );
			return um_get_template_html( 'profile.php', $args );
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

	/**
	 * Emoji support
	 *
	 * @param $content
	 *
	 * @return mixed|string
	 */
	public function emotize( $content ) {
		$content = stripslashes( $content );
		foreach ( $this->emoji as $code => $val ) {
			$regex = str_replace(array('(', ')'), array("\\" . '(', "\\" . ')'), $code);
			$content = preg_replace('/(' . $regex . ')(\s|$)/', '<img src="' . $val . '" alt="' . $code . '" title="' . $code . '" class="emoji" />$2', $content);
		}
		return $content;
	}

	/**
	 * Get Shortcode for given form ID
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public function get_shortcode( $post_id ) {
		$shortcode = '[ultimatemember form_id="' . $post_id . '"]';
		return $shortcode;
	}

	/**
	 * Get Templates
	 *
	 * @param null $excluded
	 *
	 * @return mixed
	 */
	public function get_templates( $excluded = null ) {

		if ( $excluded ) {
			$array[ $excluded ] = __( 'Default Template', 'ultimate-member' );
		}

		$paths[] = glob( um_path . 'templates/' . '*.php' );

		if ( file_exists( get_stylesheet_directory() . '/ultimate-member/templates/' ) ) {
			$paths[] = glob( get_stylesheet_directory() . '/ultimate-member/templates/' . '*.php' );
		}

		if ( isset( $paths ) && ! empty( $paths ) ) {

			foreach ( $paths as $k => $files ) {

				if ( isset( $files ) && ! empty( $files ) ) {

					foreach ( $files as $file ) {

						$clean_filename = $this->get_template_name( $file );

						if ( 0 === strpos( $clean_filename, $excluded ) ) {

							$source = file_get_contents( $file );
							$tokens = @\token_get_all( $source );
							$comment = array(
								T_COMMENT, // All comments since PHP5
								T_DOC_COMMENT, // PHPDoc comments
							);
							foreach ( $tokens as $token ) {
								if ( in_array( $token[0], $comment ) && strstr( $token[1], '/* Template:' ) && $clean_filename != $excluded ) {
									$txt = $token[1];
									$txt = str_replace( '/* Template: ', '', $txt );
									$txt = str_replace( ' */', '', $txt );
									$array[ $clean_filename ] = $txt;
								}
							}

						}

					}

				}

			}

		}

		return $array;
	}

	/**
	 * Get File Name without path and extension
	 *
	 * @param $file
	 *
	 * @return mixed|string
	 */
	private function get_template_name( $file ) {
		$file = basename( $file );
		$file = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $file );
		return $file;
	}

	/**
	 * Convert user tags in a string
	 *
	 * @param $str
	 *
	 * @return mixed
	 */
	public function convert_user_tags( $str ) {
		$pattern_array = array(
			'{first_name}',
			'{last_name}',
			'{display_name}',
			'{user_avatar_small}',
			'{username}',
			'{nickname}',
		);

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_allowed_user_tags_patterns
		 * @description Extend user placeholders patterns
		 * @input_vars
		 * [{"var":"$patterns","type":"array","desc":"Placeholders"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_allowed_user_tags_patterns', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_allowed_user_tags_patterns', 'my_allowed_user_tags', 10, 1 );
		 * function my_allowed_user_tags( $patterns ) {
		 *     // your code here
		 *     return $patterns;
		 * }
		 * ?>
		 */
		$pattern_array = apply_filters( 'um_allowed_user_tags_patterns', $pattern_array );

		//$matches = false;
		foreach ( $pattern_array as $pattern ) {

			if ( preg_match( $pattern, $str ) ) {

				$value = '';
				if ( is_user_logged_in() ) {
					$usermeta = str_replace( '{', '', $pattern );
					$usermeta = str_replace( '}', '', $usermeta );

					if ( $usermeta == 'user_avatar_small' ) {
						$value = get_avatar( um_user( 'ID' ), 40 );
					} elseif ( um_user( $usermeta ) ) {
						$value = um_user( $usermeta );
					}

					if ( $usermeta == 'username' ) {
						$value = um_user( 'user_login' );
					}

					if ( $usermeta == 'nickname' ) {
						$value = um_profile( 'nickname' );
					}

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_profile_tag_hook__{$usermeta}
					 * @description Change usermeta field value
					 * @input_vars
					 * [{"var":"$value","type":"array","desc":"Meta field value"},
					 * {"var":"$user_id","type":"array","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage
					 * <?php add_filter( 'um_profile_tag_hook__{$usermeta}', 'function_name', 10, 2 ); ?>
					 * @example
					 * <?php
					 * add_filter( 'um_profile_tag_hook__{$usermeta}', 'my_profile_tag', 10, 2 );
					 * function my_profile_tag( $value, $user_id ) {
					 *     // your code here
					 *     return $value;
					 * }
					 * ?>
					 */
					$value = apply_filters( "um_profile_tag_hook__{$usermeta}", $value, um_user( 'ID' ) );
				}

				$str = preg_replace( '/' . $pattern . '/', $value, $str );
			}

		}

		return $str;
	}

	/**
	 * Retrieve core login form
	 *
	 * @return int
	 */
	public function core_login_form() {
		$forms = get_posts(array('post_type' => 'um_form', 'posts_per_page' => 1, 'meta_key' => '_um_core', 'meta_value' => 'login'));
		$form_id = isset( $forms[0]->ID ) ? $forms[0]->ID: 0;

		return $form_id;
	}

	/**
	 * Load dynamic css
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function dynamic_css( $args = array() ) {
		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_disable_dynamic_global_css
		 * @description Turn on for disable global dynamic CSS for fix the issue #306
		 * @input_vars
		 * [{"var":"$disable","type":"bool","desc":"Disable global CSS"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_disable_dynamic_global_css', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_disable_dynamic_global_css', 'my_disable_dynamic_global_css', 10, 1 );
		 * function my_disable_dynamic_global_css( $disable ) {
		 *     // your code here
		 *     return $disable;
		 * }
		 * ?>
		 */
		$disable_css = apply_filters( 'um_disable_dynamic_global_css', false );
		if ( $disable_css ) {
			return '';
		}

		/**
		 * @var $mode
		 */
		extract( $args );

		include_once um_path . 'assets/dynamic_css/dynamic_global.php';

		if ( isset( $mode ) && in_array( $mode, array( 'profile' ) ) ) {
			$file = um_path . 'assets/dynamic_css/dynamic_' . $mode . '.php';

			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}

		do_action( 'um_form_shortcode_dynamic_css_include', $args );

		return '';
	}

	/**
	 * Loads a template file
	 *
	 * @param $template
	 * @param array $args
	 */
	public function template_load( $template, $args = array() ) {
		if ( is_array( $args ) ) {
			$this->set_args = $args;
		}
		$this->load_template( $template );
	}

	/**
	 * Load a compatible template
	 *
	 * @param $tpl
	 */
	public function load_template( $tpl ) {
		$loop = ( $this->loop ) ? $this->loop : array();

		if ( isset( $this->set_args ) && is_array( $this->set_args ) ) {
			$args = $this->set_args;

			unset( $args['file'] );
			unset( $args['theme_file'] );
			unset( $args['tpl'] );

			$args = apply_filters( 'um_template_load_args', $args, $tpl );

			extract( $args );
		}

		$file = um_path . "templates/{$tpl}.php";
		$theme_file = get_stylesheet_directory() . "/ultimate-member/templates/{$tpl}.php";
		if ( file_exists( $theme_file ) ) {
			$file = $theme_file;
		}

		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	/**
	 * Add class based on shortcode
	 *
	 * @param $mode
	 * @param array $args
	 *
	 * @return mixed|string|void
	 */
	public function get_class($mode, $args = array()) {

		$classes = 'um-' . $mode;

		if (is_admin()) {
			$classes .= ' um-in-admin';
		}

		if (isset(UM()->form()->errors) && UM()->form()->errors) {
			$classes .= ' um-err';
		}

		if (UM()->fields()->editing == true) {
			$classes .= ' um-editing';
		}

		if (UM()->fields()->viewing == true) {
			$classes .= ' um-viewing';
		}

		if (isset($args['template']) && $args['template'] != $args['mode']) {
			$classes .= ' um-' . $args['template'];
		}

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_form_official_classes__hook
		 * @description Change official form classes
		 * @input_vars
		 * [{"var":"$classes","type":"string","desc":"Classes string"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_form_official_classes__hook', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_form_official_classes__hook', 'my_form_official_classes', 10, 1 );
		 * function my_form_official_classes( $classes ) {
		 *     // your code here
		 *     return $classes;
		 * }
		 * ?>
		 */
		$classes = apply_filters( 'um_form_official_classes__hook', $classes );
		return $classes;
	}

	/**
	 * Convert access lock tags
	 *
	 * @param $str
	 *
	 * @return mixed|string
	 */
	private function convert_locker_tags( $str ) {
		add_filter( 'um_template_tags_patterns_hook', array( &$this, 'add_placeholder' ), 10, 1 );
		add_filter( 'um_template_tags_replaces_hook', array( &$this, 'add_replace_placeholder' ), 10, 1 );
		return um_convert_tags( $str, array(), false );
	}

	/**
	 * UM Placeholders for login referrer
	 *
	 * @param $placeholders
	 *
	 * @return array
	 */
	public function add_placeholder( $placeholders ) {
		$placeholders[] = '{login_referrer}';
		return $placeholders;
	}

	/**
	 * UM Replace Placeholders for login referrer
	 *
	 * @param $replace_placeholders
	 *
	 * @return array
	 */
	public function add_replace_placeholder( $replace_placeholders ) {
		$replace_placeholders[] = um_dynamic_login_page_redirect();
		return $replace_placeholders;
	}
}
