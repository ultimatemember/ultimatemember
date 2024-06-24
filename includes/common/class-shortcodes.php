<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Shortcodes
 * @package um\common
 */
class Shortcodes {

	/**
	 * @var array
	 */
	public $forms_exist = array();

	/**
	 * @var string
	 */
	public $profile_role = '';

	/**
	 * @var bool
	 */
	public $message_mode = false;

	/**
	 * @var string
	 */
	public $custom_message = '';

	/**
	 * @var array
	 */
	public $loop = array();

	/**
	 * @var array
	 */
	private static $emoji = array();

	/**
	 * @var null|int
	 */
	public $form_id = null;

	/**
	 * @var null|string
	 */
	public $form_status = null;

	/**
	 * @var array
	 */
	public $set_args = array();

	/**
	 * Shortcodes constructor.
	 */
	public function __construct() {
		self::init_legacy_emoji();

		// Content restrictions shortcodes.
		add_shortcode( 'um_loggedin', array( &$this, 'um_loggedin' ) );
		add_shortcode( 'um_loggedout', array( &$this, 'um_loggedout' ) );
		add_shortcode( 'um_show_content', array( &$this, 'um_shortcode_show_content_for_role' ) );
		// Helpers shortcodes.
		add_shortcode( 'um_author_profile_link', array( &$this, 'author_profile_link' ) );

		add_shortcode( 'ultimatemember', array( &$this, 'ultimatemember' ) );
		add_shortcode( 'ultimatemember_searchform', array( &$this, 'ultimatemember_searchform' ) );
		add_shortcode( 'ultimatemember_directory', array( &$this, 'ultimatemember_directory' ) );

//		add_shortcode( 'ultimatemember_password', array( &$this, 'reset_password_form' ) );

		add_filter( 'um_shortcode_args_filter', array( &$this, 'display_logout_form' ), 99 );
		add_filter( 'um_shortcode_args_filter', array( &$this, 'parse_shortcode_args' ), 99 );

		// Probably shortcodes to remove.
		add_shortcode( 'ultimatemember_login', array( &$this, 'ultimatemember_login' ) );
		add_shortcode( 'ultimatemember_register', array( &$this, 'ultimatemember_register' ) );
		add_shortcode( 'ultimatemember_profile', array( &$this, 'ultimatemember_profile' ) );
		//add_shortcode( 'ultimatemember_directory', array( &$this, 'ultimatemember_directory' ) );

		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
			add_shortcode( 'ultimatemember_design_scheme', array( &$this, 'design_scheme' ) );
			add_shortcode( 'ultimatemember_profile', array( &$this, 'new_profile' ) );
		}
	}

	/**
	 * @todo deprecate as soon as transfer DB script will be ready.
	 */
	private static function init_legacy_emoji() {
		$base_uri  = apply_filters( 'um_emoji_base_uri', 'https://s.w.org/images/core/emoji/' );
		$base_uri .= '72x72/';

		$emoji = array(
			':)'                             => '1f604.png',
			':smiley:'                       => '1f603.png',
			':D'                             => '1f600.png',
			':$'                             => '1f60a.png',
			':relaxed:'                      => '263a.png',
			';)'                             => '1f609.png',
			':heart_eyes:'                   => '1f60d.png',
			':kissing_heart:'                => '1f618.png',
			':kissing_closed_eyes:'          => '1f61a.png',
			':kissing:'                      => '1f617.png',
			':kissing_smiling_eyes:'         => '1f619.png',
			';P'                             => '1f61c.png',
			':P'                             => '1f61b.png',
			':stuck_out_tongue_closed_eyes:' => '1f61d.png',
			':flushed:'                      => '1f633.png',
			':grin:'                         => '1f601.png',
			':pensive:'                      => '1f614.png',
			':relieved:'                     => '1f60c.png',
			':unamused'                      => '1f612.png',
			':('                             => '1f61e.png',
			':persevere:'                    => '1f623.png',
			":'("                            => '1f622.png',
			':joy:'                          => '1f602.png',
			':sob:'                          => '1f62d.png',
			':sleepy:'                       => '1f62a.png',
			':disappointed_relieved:'        => '1f625.png',
			':cold_sweat:'                   => '1f630.png',
			':sweat_smile:'                  => '1f605.png',
			':sweat:'                        => '1f613.png',
			':weary:'                        => '1f629.png',
			':tired_face:'                   => '1f62b.png',
			':fearful:'                      => '1f628.png',
			':scream:'                       => '1f631.png',
			':angry:'                        => '1f620.png',
			':rage:'                         => '1f621.png',
			':triumph'                       => '1f624.png',
			':confounded:'                   => '1f616.png',
			':laughing:'                     => '1f606.png',
			':yum:'                          => '1f60b.png',
			':mask:'                         => '1f637.png',
			':cool:'                         => '1f60e.png',
			':sleeping:'                     => '1f634.png',
			':dizzy_face:'                   => '1f635.png',
			':astonished:'                   => '1f632.png',
			':worried:'                      => '1f61f.png',
			':frowning:'                     => '1f626.png',
			':anguished:'                    => '1f627.png',
			':smiling_imp:'                  => '1f608.png',
			':imp:'                          => '1f47f.png',
			':open_mouth:'                   => '1f62e.png',
			':grimacing:'                    => '1f62c.png',
			':neutral_face:'                 => '1f610.png',
			':confused:'                     => '1f615.png',
			':hushed:'                       => '1f62f.png',
			':no_mouth:'                     => '1f636.png',
			':innocent:'                     => '1f607.png',
			':smirk:'                        => '1f60f.png',
			':expressionless:'               => '1f611.png',
		);
		array_walk(
			$emoji,
			function ( &$item1, $key, $prefix ) {
				$item1 = $prefix . $item1;
			},
			$base_uri
		);

		self::$emoji = $emoji;
	}

	/**
	 * Logged-in only content
	 *
	 * @param array $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function um_loggedin( $args = array(), $content = "" ) {
		ob_start();

		$args = shortcode_atts(
			array(
				'lock_text' => __( 'This content has been restricted to logged in users only. Please <a href="{login_referrer}">login</a> to view this content.', 'ultimate-member' ),
				'show_lock' => 'yes',
			),
			$args,
			'um_loggedin'
		);

		if ( ! is_user_logged_in() ) {
			if ( 'no' === $args['show_lock'] ) {
				echo '';
			} else {
				$args['lock_text'] = $this->convert_locker_tags( $args['lock_text'] );
				UM()->get_template( 'login-to-view.php', '', $args, true );
			}
		} else {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( $this->convert_locker_tags( wpautop( $content ) ) );
			} else {
				echo apply_shortcodes( $this->convert_locker_tags( wpautop( $content ) ) );
			}
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
		ob_start();

		// Hide for logged in users
		if ( is_user_logged_in() ) {
			echo '';
		} else {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode( wpautop( $content ) );
			} else {
				echo apply_shortcodes( wpautop( $content ) );
			}
		}

		$output = ob_get_clean();
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
			return;
		}

		$a = shortcode_atts( array(
			'roles' => '',
			'not' => '',
			'is_profile' => false,
		), $atts );

		if ( $a['is_profile'] ) {
			um_fetch_user( um_profile_id() );
		} else {
			um_fetch_user( $user_ID );
		}

		$current_user_roles = um_user( 'roles' );

		if ( ! empty( $a['not'] ) && ! empty( $a['roles'] ) ) {
			if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				return do_shortcode( $this->convert_locker_tags( $content ) );
			} else {
				return apply_shortcodes( $this->convert_locker_tags( $content ) );
			}
		}

		if ( ! empty( $a['not'] ) ) {
			$not_in_roles = explode( ",", $a['not'] );

			if ( is_array( $not_in_roles ) && ( empty( $current_user_roles ) || count( array_intersect( $current_user_roles, $not_in_roles ) ) <= 0 ) ) {
				if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
					return do_shortcode( $this->convert_locker_tags( $content ) );
				} else {
					return apply_shortcodes( $this->convert_locker_tags( $content ) );
				}
			}
		} else {
			$roles = explode( ",", $a['roles'] );

			if ( ! empty( $current_user_roles ) && is_array( $roles ) && count( array_intersect( $current_user_roles, $roles ) ) > 0 ) {
				if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
					return do_shortcode( $this->convert_locker_tags( $content ) );
				} else {
					return apply_shortcodes( $this->convert_locker_tags( $content ) );
				}
			}
		}

		return '';
	}

	/**
	 * Display post author's link to UM User Profile.
	 *
	 * @since 2.8.2
	 *
	 * Example 1: [um_author_profile_link] current post author User Profile URL
	 * Example 2: [um_author_profile_link title="User profile" user_id="29"]
	 * Example 3: [um_author_profile_link title="User profile" user_id="29"]Visit Author Profile[/um_author_profile_link]
	 * Example 4: [um_author_profile_link raw="1"] for result like http://localhost:8000/user/janedoe/
	 *
	 * @param array  $attr {
	 *     Attributes of the shortcode.
	 *
	 *     @type string $class   A link class.
	 *     @type string $title   A link text.
	 *     @type int    $user_id User ID. Author ID if empty.
	 *     @type bool   $raw     Get raw URL or link layout. `false` by default.
	 * }
	 * @param string $content
	 * @return string Profile link HTML or profile link URL if the link text is empty.
	 */
	public function author_profile_link( $attr = array(), $content = '' ) {
		$default_user_id = 0;
		if ( is_singular() ) {
			$default_user_id = get_post()->post_author;
		} elseif ( is_author() ) {
			$default_user_id = get_the_author_meta( 'ID' );
		}

		$defaults_atts = array(
			'class'   => 'um-link um-profile-link',
			'title'   => __( 'Go to profile', 'ultimate-member' ),
			'user_id' => $default_user_id,
			'raw'     => false,
		);

		$atts = shortcode_atts( $defaults_atts, $attr, 'um_author_profile_link' );

		if ( empty( $atts['user_id'] ) ) {
			return '';
		}

		$user_id = absint( $atts['user_id'] );
		$url     = um_user_profile_url( $user_id );
		if ( empty( $url ) ) {
			return '';
		}

		if ( ! empty( $atts['raw'] ) ) {
			return $url;
		}

		$title     = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Go to profile', 'ultimate-member' );
		$link_html = empty( $content ) ? $title : $content;

		return '<a class="' . esc_attr( $atts['class'] ) . '" href="' . esc_url( $url ) . '" title="' . esc_attr( $title ) . '">' . wp_kses_post( $link_html ) . '</a>';
	}

	/**
	 * Shortcode for the displaying reset password form.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
//	public function reset_password_form( $args = array() ) {
//		if ( ! um_is_predefined_page( 'password-reset' ) ) {
//			return '';
//		}
//
//		/** There is possible to use 'shortcode_atts_ultimatemember_password' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
//		$args = shortcode_atts(
//			array(
//				'max_width' => '450px',
//				'align'     => 'center',
//			),
//			$args,
//			'ultimatemember_password'
//		);
//
//		if ( ! empty( $this->is_resetpass ) ) {
//			// then COOKIE are valid then get data from them and populate hidden fields for the password change form
//			$args['template'] = 'reset-password.php';
//
//			$login     = '';
//			$rp_key    = '';
//			$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
//			if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
//				list( $login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
//			}
//
//			$resetpass_form = UM()->frontend()->form(
//				array(
//					'id' => 'um-resetpass',
//				)
//			);
//
//			$resetpass_form->add_notice(
//				__( 'Enter your new password below and confirm it.', 'ultimate-member' ),
//				'resetpass-info'
//			);
//
//			$resetpass_form_args = array(
//				'id'        => 'um-resetpass',
//				'class'     => 'um-top-label um-single-button um-center-always',
//				'prefix_id' => '',
//				'fields'    => array(
//					array(
//						'type'        => 'password',
//						'label'       => __( 'New Password', 'ultimate-member' ),
//						'id'          => 'user_password',
//						'required'    => true,
//						'value'       => '',
//						'placeholder' => __( 'Enter new Password', 'ultimate-member' ),
//					),
//					array(
//						'type'        => 'password',
//						'label'       => __( 'Confirm Password', 'ultimate-member' ),
//						'id'          => 'confirm_user_password',
//						'required'    => true,
//						'value'       => '',
//						'placeholder' => __( 'Confirm Password', 'ultimate-member' ),
//					),
//				),
//				'hiddens'   => array(
//					'um-action' => 'password-reset',
//					'rp_key'    => $rp_key,
//					'login'     => $login,
//					'nonce'     => wp_create_nonce( 'um-resetpass' ),
//				),
//				'buttons'   => array(
//					'save-password' => array(
//						'type'  => 'submit',
//						'label' => __( 'Save Password', 'ultimate-member' ),
//						'class' => array(
//							'um-button-primary',
//						),
//					),
//				),
//			);
//			/**
//			 * Filters arguments for the Reset Password form in the Password Reset shortcode content.
//			 *
//			 * Note: Use this hook for adding custom fields, hiddens or buttons to your Reset Password form.
//			 *
//			 * @since 3.0.0
//			 * @hook um_resetpass_form_args
//			 *
//			 * @param {array} $resetpass_form_args Reset Password form arguments.
//			 */
//			$resetpass_form_args = apply_filters( 'um_resetpass_form_args', $resetpass_form_args );
//
//			$resetpass_form->set_data( $resetpass_form_args );
//
//			$t_args = array(
//				'resetpass_form' => $resetpass_form,
//			);
//		} else {
//			// show lostpassword form by default
//			$lostpassword_form = null;
//			$args['template']  = 'lostpassword.php';
//
//			if ( ( ! isset( $_GET['checkemail'] ) || 'confirm' !== sanitize_key( $_GET['checkemail'] ) ) && ( ! isset( $_GET['checklogin'] ) || 'password_changed' !== sanitize_key( $_GET['checklogin'] ) ) ) {
//				$lostpassword_form = UM()->frontend()->form(
//					array(
//						'id' => 'um-lostpassword',
//					)
//				);
//
//				if ( ! empty( $_GET['error'] ) ) {
//					if ( 'expiredkey' === sanitize_key( $_GET['error'] ) ) {
//						$lostpassword_form->add_notice(
//							__( 'Your password reset link has expired. Please request a new link below.', 'ultimate-member' ),
//							'expiredkey-error'
//						);
//					} elseif ( 'invalidkey' === sanitize_key( $_GET['error'] ) ) {
//						$lostpassword_form->add_notice(
//							__( 'Your password reset link appears to be invalid. Please request a new link below.', 'ultimate-member' ),
//							'invalidkey-error'
//						);
//					}
//				} else {
//					$lostpassword_form->add_notice(
//						__( 'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.', 'ultimate-member' ),
//						'lostpassword-info'
//					);
//				}
//
//				$lostpassword_form_args = array(
//					'id'        => 'um-lostpassword',
//					'class'     => 'um-top-label um-single-button um-center-always',
//					'prefix_id' => '',
//					'fields'    => array(
//						array(
//							'type'        => 'text',
//							'label'       => __( 'Username or Email Address', 'ultimate-member' ),
//							'id'          => 'user_login',
//							'required'    => true,
//							'value'       => '',
//							'placeholder' => __( 'Enter Username or Email Address', 'ultimate-member' ),
//							'validation'  => 'user_login',
//						),
//					),
//					'hiddens'   => array(
//						'um-action' => 'password-reset-request',
//						'nonce'     => wp_create_nonce( 'um-lostpassword' ),
//					),
//					'buttons'   => array(
//						'new-password' => array(
//							'type'  => 'submit',
//							'label' => __( 'Get New Password', 'ultimate-member' ),
//							'class' => array(
//								'um-button-primary',
//							),
//						),
//					),
//				);
//				/**
//				 * Filters arguments for the Lost Password form in the Password Reset shortcode content.
//				 *
//				 * Note: Use this hook for adding custom fields, hiddens or buttons to your Lost Password form.
//				 *
//				 * @since 3.0.0
//				 * @hook um_lostpassword_form_args
//				 *
//				 * @param {array} $lostpassword_form_args Lost Password form arguments.
//				 */
//				$lostpassword_form_args = apply_filters( 'um_lostpassword_form_args', $lostpassword_form_args );
//
//				$lostpassword_form->set_data( $lostpassword_form_args );
//			}
//
//			$t_args = array(
//				'lostpassword_form' => $lostpassword_form,
//			);
//		}
//
//		/**
//		 * Fires before Password Reset form loading inside shortcode callback.
//		 *
//		 * Note: Use this hook for adding some custom content before the password reset form or enqueue scripts when password reset form shortcode loading.
//		 * Legacy v2.x hooks: 'um_before_password_form_is_loaded', 'um_before_form_is_loaded'
//		 *
//		 * @since 3.0.0
//		 * @hook um_pre_password_shortcode
//		 *
//		 * @param {array} $args Password reset form shortcode arguments.
//		 */
//		do_action( 'um_pre_password_shortcode', $args );
//
//		$template = $args['template'];
//		unset( $args['template'] );
//		$t_args = array_merge( $t_args, $args );
//
//		wp_enqueue_script( 'um-password-reset' );
//
//		$styling = UM()->options()->get( 'styling' );
//		switch ( $styling ) {
//			case 'none':
//				break;
//			case 'layout_only':
//				wp_enqueue_style( 'um-password-reset-base' );
//				break;
//			default:
//				wp_enqueue_style( 'um-password-reset-full' );
//				break;
//		}
//
//		return um_get_template_html( $template, $t_args );
//	}

	public function design_scheme( $args ) {
		wp_enqueue_style( 'um_new_design' );
		wp_enqueue_script( 'um_new_design' );

		$palette = UM()->common()::color()->generate_palette( UM()->options()->get( 'primary_color' ) );

		ob_start();
		?>
		<div class="um">

			<h3>Range</h3>
			<div>
				<?php
				echo wp_kses(
					UM()->frontend()::layouts()::date_range(
						array(
							'id'    => 'test1',
							'name'  => 'test1',
							'label' => 'Date range 1',
							'value' => 30,
							'min'   => 10,
							'max'   => 50,
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses(
					UM()->frontend()::layouts()::date_range(
						array(
							'id'    => 'test2',
							'name'  => 'test2',
							'label' => 'Date range 2',
							'value' => array( '2024-11-22', '2024-11-15' ),
							'min'   => 10,
							'max'   => 50,
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses(
					UM()->frontend()::layouts()::time_range(
						array(
							'id'  => 'test3',
							'name'  => 'test3',
							'label'  => 'Time range 1',
							'value' => 30,
							'min'   => 10,
							'max'   => 50,
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses(
					UM()->frontend()::layouts()::time_range(
						array(
							'id'  => 'test4',
							'name'  => 'test4',
							'label'  => 'Time range 2',
							'value' => array( '10:00', '15:00' ),
							'min'   => 10,
							'max'   => 50,
						)
					),
					UM()->get_allowed_html( 'templates' )
				);

				echo wp_kses(
					UM()->frontend()::layouts()::range(
						array(
							'label'       => 'Test 5',
							'name'        => 'test1',
							'value'       => 30,
							'min'         => 10,
							'max'         => 50,
							'placeholder' => array(
								'single' => '{{{label}}}: {{{value}}}',
								'plural' => '{{{label}}}: {{{value_from}}} - {{{value_to}}}',
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses(
					UM()->frontend()::layouts()::range(
						array(
							'label' => 'Test 5.2',
							'name'  => 'test1',
							'value' => 30,
							'min'   => 10,
							'max'   => 50,
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				?>

				<?php
				echo wp_kses(
					UM()->frontend()::layouts()::range(
						array(
							'label'  => 'Test 6',
							'name'  => 'test2',
							'value' => array( 5, 50 ),
							'min'   => 0,
							'max'   => 100,
							'placeholder' => array(
								'single' => '{{{label}}}: {{{value}}}',
								'plural' => '{{{label}}}: {{{value_from}}} - {{{value_to}}}',
							),
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				echo wp_kses(
					UM()->frontend()::layouts()::range(
						array(
							'label'  => 'Test 7',
							'name'  => 'test2',
							'value' => array( 5, 50 ),
							'min'   => 0,
							'max'   => 100,
						)
					),
					UM()->get_allowed_html( 'templates' )
				);
				?>
			</div>
			<h3>File extensions</h3>
			<div style="display:flex;flex-direction:row;justify-content:flex-start;flex-wrap: nowrap;align-items:center; gap: 8px;">
				<?php echo UM()->frontend()::layouts()::get_file_extension_icon( 'jpg' ); ?>
				<?php echo UM()->frontend()::layouts()::get_file_extension_icon( 'png' ); ?>
				<?php echo UM()->frontend()::layouts()::get_file_extension_icon( 'svg' ); ?>
				<?php echo UM()->frontend()::layouts()::get_file_extension_icon( 'docx' ); ?>
				<?php echo UM()->frontend()::layouts()::get_file_extension_icon( 'xml' ); ?>
			</div>
			<h3>Progress bar</h3>
			<div>
				<?php echo UM()->frontend()::layouts()::progress_bar(); ?>
				<?php echo UM()->frontend()::layouts()::progress_bar( array( 'label' => 'bottom' ) ); ?>
				<?php echo UM()->frontend()::layouts()::progress_bar( array( 'label' => 'right' ) ); ?>
				<?php echo UM()->frontend()::layouts()::progress_bar( array( 'value' => 50 ) ); ?>
				<?php echo UM()->frontend()::layouts()::progress_bar( array( 'label' => 'bottom', 'value' => 80 ) ); ?>
				<?php echo UM()->frontend()::layouts()::progress_bar( array( 'label' => 'right', 'value' => 30 ) ); ?>
				<?php echo UM()->frontend()::layouts()::progress_bar( array( 'value' => 100 ) ); ?>
			</div>

			<h3>Avatar uploader</h3>

			<?php echo UM()->frontend()::layouts()::avatar_uploader(); ?>

			<h3>Uploader</h3>
			<div>
				<p>Common upload</p>
				<?php echo UM()->frontend()::layouts()::uploader( array( 'handler' => 'common-upload' ) ); ?>
				<p>Common upload but custom text in dropzone</p>
				<?php echo UM()->frontend()::layouts()::uploader( array( 'handler' => 'common-upload', 'max_upload_size' => 1024 * 1024, 'dropzone_inner' => 'lorem ipsum' ) ); ?>
				<p>Common upload but limited by size</p>
				<?php echo UM()->frontend()::layouts()::uploader( array( 'handler' => 'common-upload', 'max_upload_size' => 1024 * 1024 ) ); ?>
				<p>Image upload</p>
				<?php
				echo UM()->frontend()::layouts()::uploader(
					array(
						'handler' => 'upload-image',
						'types'   => array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'webp', 'avif' ),
					)
				);
				?>
				<p>Image upload but custom text in dropzone</p>
				<?php
				echo UM()->frontend()::layouts()::uploader(
					array(
						'handler' => 'upload-image',
						'types'   => array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'webp', 'avif' ),
						'dropzone_inner' => 'lorem ipsum'
					)
				);
				?>
				<p>Image upload but limited by size</p>
				<?php
				echo UM()->frontend()::layouts()::uploader(
					array(
						'handler' => 'upload-image',
						'types'   => array( 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tif', 'tiff', 'ico', 'heic', 'webp', 'avif' ),
						'max_upload_size' => 1024 * 1024
					)
				);
				?>
<!--				<p>Should work for not logged in</p>-->
				<?php /*echo UM()->frontend()::layouts()::uploader( array( 'handler' => 'nopriv-upload' ) );*/ ?>
			</div>
			<h3>Buttons</h3>
			<script>
				jQuery(document).ready( function($) {
					$(document.body).on('click', '#get_button_snippet', function(){
						let request = UM.common.form.vanillaSerialize( 'um-get-button' );
						wp.ajax.send(
							'um_get_button_snippet',
							{
								data: request,
								success: function(response) {
									$('#um-button-preview').html( response );
								},
								error: function(response) {

								},
							}
						)
					});
				});
			</script>
			<form id="um-get-button" style="display: flex; gap:8px;flex-direction:column;align-items:flex-start;justify-content:flex-start;flex-wrap:nowrap;width:100%;">
				<label>Type
				<select name="type">
					<option value="submit">Submit</option>
					<option value="button">Button</option>
					<option value="reset">Reset</option>
				</select>
				</label>
				<label>Size
				<select name="size">
					<option value="s">S</option>
					<option value="m">M</option>
					<option value="l">L</option>
					<option value="xl">XL</option>
				</select>
				</label>
				<label>Design
				<select name="design">
					<option value="primary">primary</option>
					<option value="secondary-gray">secondary-gray</option>
					<option value="secondary-color">secondary-color</option>
					<option value="tertiary-gray">tertiary-gray</option>
					<option value="tertiary-color">tertiary-color</option>
					<option value="link-gray">link-gray</option>
					<option value="link-color">link-color</option>
					<option value="primary-destructive">primary-destructive</option>
					<option value="secondary-destructive">secondary-destructive</option>
					<option value="tertiary-destructive">tertiary-destructive</option>
					<option value="link-destructive">link-destructive</option>
				</select>
				</label>
				<label>Content
				<input type="text" name="content" value="Button"/>
				</label>
				<label>Icon position
				<select name="icon_position">
					<option value="">None</option>
					<option value="trailing">Trailing</option>
					<option value="leading">Leading</option>
					<option value="content">Content</option>
				</select>
				</label>
				<label>Icon SVG HTML
				<input type="text" name="icon" value="" />
				</label>
				<p class="description">You can get icons <a href="https://tabler.io/icons" target="_blank">here</a>. Size = 20px. Stroke = 1.5</p>
				<label>Disabled
				<input type="checkbox" name="disabled" value="1" />
				</label>
				<label>Full-width
				<input type="checkbox" name="width" value="full" />
				</label>
				<button type="button" id="get_button_snippet">Get snippet + preview</button>
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'get_button' ) ); ?>">
			</form>
			<div class="um-box">
				<h4>Preview</h4>
				<div id="um-button-preview" style="display: flex; gap:8px;flex-direction:column;align-items:flex-start;justify-content:flex-start;flex-wrap:nowrap;width:100%;"></div>
			</div>

			<h3>Links</h3>
			<script>
				jQuery(document).ready( function($) {
					$(document.body).on('click', '#get_link_snippet', function(){
						let request = UM.common.form.vanillaSerialize( 'um-get-link' );
						wp.ajax.send(
							'um_get_link_snippet',
							{
								data: request,
								success: function(response) {
									$('#um-link-preview').html( response );
								},
								error: function(response) {

								},
							}
						)
					});
				});
			</script>
			<form id="um-get-link" style="display: flex; gap:8px;flex-direction:column;align-items:flex-start;justify-content:flex-start;flex-wrap:nowrap;width:100%;">
				<label>Type
					<select name="type">
						<option value="raw">Raw</option>
						<option value="button">Button</option>
					</select>
				</label>
				<label>Size
					<select name="size">
						<option value="s">S</option>
						<option value="m">M</option>
						<option value="l">L</option>
						<option value="xl">XL</option>
					</select>
				</label>
				<label>Design
					<select name="design">
						<option value="primary">primary</option>
						<option value="secondary-gray">secondary-gray</option>
						<option value="secondary-color">secondary-color</option>
						<option value="tertiary-gray">tertiary-gray</option>
						<option value="tertiary-color">tertiary-color</option>
						<option value="link-gray">link-gray</option>
						<option value="link-color">link-color</option>
						<option value="primary-destructive">primary-destructive</option>
						<option value="secondary-destructive">secondary-destructive</option>
						<option value="tertiary-destructive">tertiary-destructive</option>
						<option value="link-destructive">link-destructive</option>
					</select>
				</label>
				<label>Content
					<input type="text" name="content" value="Link"/>
				</label>
				<label>Icon position
					<select name="icon_position">
						<option value="">None</option>
						<option value="trailing">Trailing</option>
						<option value="leading">Leading</option>
						<option value="content">Content</option>
					</select>
				</label>
				<label>Icon SVG HTML
					<input type="text" name="icon" value="" />
				</label>
				<p class="description">You can get icons <a href="https://tabler.io/icons" target="_blank">here</a>. Size = 20px. Stroke = 1.5</p>
				<label>Disabled
					<input type="checkbox" name="disabled" value="1" />
				</label>
				<label>Full-width
					<input type="checkbox" name="width" value="full" />
				</label>
				<button type="button" id="get_link_snippet">Get snippet + preview</button>
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'get_link' ) ); ?>">
			</form>
			<div class="um-box">
				<h4>Preview</h4>
				<div id="um-link-preview" style="display: flex; gap:8px;flex-direction:column;align-items:flex-start;justify-content:flex-start;flex-wrap:nowrap;width:100%;"></div>
			</div>

			<h3>User data</h3>
			<div style="display:flex;flex-direction:column;justify-content:flex-start;flex-wrap: nowrap;align-items:stretch">
				<p>Default current user</p>
				<?php echo UM()->frontend()::layouts()::small_data(); ?>
				<p>User by ID</p>
				<?php echo UM()->frontend()::layouts()::small_data( 88 ); ?>
				<p>User by ID + supporting text</p>
				<?php echo UM()->frontend()::layouts()::small_data( 88, array( 'supporting' => 'Some text' ) ); ?>
			</div>
			<h3>Badges</h3>
			<div style="display:flex;flex-direction:row;justify-content:flex-start;flex-wrap: wrap;align-items:center">
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label' ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-outline' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-outline' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-outline' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-color' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-color' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-color' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'color' => 'brand' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'color' => 'brand' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'color' => 'brand' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-outline', 'color' => 'brand' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-outline', 'color' => 'brand' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-outline', 'color' => 'brand' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-color', 'color' => 'brand' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-color', 'color' => 'brand' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-color', 'color' => 'brand' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'color' => 'error' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'color' => 'error' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'color' => 'error' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-outline', 'color' => 'error' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-outline', 'color' => 'error' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-outline', 'color' => 'error' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-color', 'color' => 'error' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-color', 'color' => 'error' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-color', 'color' => 'error' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'color' => 'warning' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'color' => 'warning' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'color' => 'warning' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-outline', 'color' => 'warning' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-outline', 'color' => 'warning' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-outline', 'color' => 'warning' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-color', 'color' => 'warning' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-color', 'color' => 'warning' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-color', 'color' => 'warning' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'color' => 'success' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'color' => 'success' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'color' => 'success' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-outline', 'color' => 'success' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-outline', 'color' => 'success' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-outline', 'color' => 'success' ) ); ?>

			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 's', 'type'  => 'pill-color', 'color' => 'success' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array( 'type'  => 'pill-color', 'color' => 'success' ) ); ?>
			<?php echo UM()->frontend()::layouts()::badge( 'Label', array('size'  => 'l', 'type'  => 'pill-color', 'color' => 'success' ) ); ?>
			</div>
			<h3>Pagination</h3>

			<?php echo UM()->frontend()::layouts()::pagination( array( 'page' => 1, 'total' => 0, 'per_page' => 5 ) ); ?>
			<?php echo UM()->frontend()::layouts()::pagination( array( 'total' => 0, 'per_page' => 5, 'page' => 1, ) ); ?>
			<?php echo UM()->frontend()::layouts()::pagination( array( 'page' => 2, 'total' => 10, 'per_page' => 2 ) ); ?>
			<?php echo UM()->frontend()::layouts()::pagination( array( 'page' => 1, 'total' => 5, 'per_page' => 5 ) ); ?>
			<?php echo UM()->frontend()::layouts()::pagination( array( 'page' => 8, 'total' => 500, 'per_page' => 20 ) ); ?>

			<h3>Avatars</h3>
			<p>Clickable</p>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 'l', 'clickable' => true ) ); ?>
			<p>s</p>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 's' ) ); ?>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 's', 'type' => 'square' ) ); ?>
			<p>m</p>
			<?php echo UM()->frontend()::layouts()::single_avatar(); ?>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 'm', 'type' => 'square' ) ); ?>
			<p>l</p>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 'l' ) ); ?>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 'l', 'type' => 'square' ) ); ?>
			<p>xl</p>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 'xl' ) ); ?>
			<?php echo UM()->frontend()::layouts()::single_avatar( get_current_user_id(), array( 'size' => 'xl', 'type' => 'square' ) ); ?>

			<h3>Figma palette</h3>
			<div style="display:flex;justify-content: flex-start;align-items:baseline; flex-wrap: wrap;margin-bottom:20px;">
				<?php foreach ( $palette as $title => $colors ) { ?>
					<div style="width: 50px; height: 50px; background-color: <?php echo esc_attr( $colors['bg'] );?>; color: <?php echo esc_attr( $colors['fg'] ); ?>"><?php echo $title; ?></div>
				<?php } ?>
			</div>

			<h3>AJAX loaders</h3>
			<p>m</p>
			<?php echo UM()->frontend()::layouts()::ajax_loader( 'm' ); ?>
			<p>l</p>
			<?php echo UM()->frontend()::layouts()::ajax_loader(); ?>
			<p>xl</p>
			<?php echo UM()->frontend()::layouts()::ajax_loader( 'xl' ); ?>

			<h3>Form</h3>
			<?php echo UM()->frontend()::layouts()::form(); ?>

			<h3>Dropdown</h3>
			<div style="display: flex; justify-content: flex-start; flex-wrap: wrap; align-items: start; gap: 24px;">
				<?php
				$itemsh = array(
					'<a href="#" class="um-manual-trigger">' . __( 'Action 1', 'ultimate-member' ) . '</a>',
					'<a href="#" class="um-reset-profile-photo">' . __( 'Action 2', 'ultimate-member' ) . '</a>',
				);
				echo UM()->frontend()::layouts()::dropdown_menu( 'um-dropdown-toggle-hover', $itemsh, array( 'event' => 'mouseover' ) );

				$items = array(
					'<a href="javascript:void(0);" class="um-manual-trigger" data-parent=".um-profile-photo" data-child=".um-btn-auto-width">' . __( 'Change photo', 'ultimate-member' ) . '</a>',
					'<a href="javascript:void(0);" class="um-reset-profile-photo" data-user_id="' . esc_attr( um_profile_id() ) . '" data-default_src="' . esc_url( um_get_default_avatar_uri() ) . '">' . __( 'Remove photo', 'ultimate-member' ) . '</a>',
				);
				echo UM()->frontend()::layouts()::dropdown_menu( 'um-dropdown-toggle-test', $items );

				$items2 = array(
					array(
						'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 1', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 2', 'ultimate-member' ) . '</a>',
					),
					array(
						'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 3', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 4', 'ultimate-member' ) . '</a>',
					),
					array(
						'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 5', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 6', 'ultimate-member' ) . '</a>',
					),
				);
				echo UM()->frontend()::layouts()::dropdown_menu( 'um-dropdown-toggle-test2', $items2 );

				$items3 = array(
					array(
						'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 1', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 2', 'ultimate-member' ) . '</a>',
					),
					array(
						'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 3', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 4', 'ultimate-member' ) . '</a>',
					),
					array(
						'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 5', 'ultimate-member' ) . '</a>',
						'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 6', 'ultimate-member' ) . '</a>',
					),
				);
				echo UM()->frontend()::layouts()::dropdown_menu( 'um-dropdown-toggle-test3', $items3, array( 'header' => 'Dropdown header text', 'width' => 230 ) );

				$items4 = array(
					'<a href="javascript:void(0);" class="um-manual-trigger">' . __( 'Action 1', 'ultimate-member' ) . '</a>',
					'<a href="javascript:void(0);" class="um-reset-profile-photo">' . __( 'Action 2', 'ultimate-member' ) . '</a>',
				);
				echo UM()->frontend()::layouts()::dropdown_menu( 'um-dropdown-toggle-test4', $items4, array( 'header' => 'Dropdown header text', 'width' => 300 ) );
				?>
			</div>
			<h3>Card component</h3>
			<div style="display: flex; justify-content: flex-start; flex-wrap: wrap; align-items: start; gap: 24px;">
				<?php echo UM()->frontend()::layouts()::box( 'CONTENT', array( 'title' => 'Card title' ) ); ?>
				<?php echo UM()->frontend()::layouts()::box( 'CONTENT2', array( 'title' => 'Card title2', 'footer' => '<a class="um-link" href="#">View</a>' ) ); ?>
				<?php echo UM()->frontend()::layouts()::box( 'CONTENT3CONTENT3CONTENT3CONTENT3CONTENT3CONTENT3 CONTENT3CONTENT3CONTENT3CONTENT3CONTENT3', array( 'title' => 'Card title3', 'actions' => $items, 'footer' => '<a class="um-link" href="#">View</a>' ) ); ?>
				<?php echo UM()->frontend()::layouts()::box( 'CONTENT4CONTENT4CONTENT4CONTENT4CONTENT4CONTENT4 CONTENT4CONTENT4CONTENT4CONTENT4CONTENT4', array( 'actions' => $items, 'footer' => '<a class="um-link" href="#">View</a>' ) ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function new_profile( $args = array() ) {
		$args = shortcode_atts(
			array(
				'form_id' => '',
			),
			$args,
			'ultimatemember_profile'
		);

		$form_id = absint( $args['form_id'] );

		$user       = get_userdata( um_user( 'ID' ) );
		$user_roles = (array) $user->roles;

		wp_enqueue_style( 'um_new_profile' );

		$content = '';
		if ( um_is_on_edit_profile() ) {
			$content = UM()->get_template( 'v3/profile/edit.php', '', array( 'form_id' => $form_id, 'roles' => $user_roles ) );
		} else {
			$content = UM()->get_template( 'v3/profile/base.php', '', array( 'form_id' => $form_id, 'roles' => $user_roles ) );
		}
		return $content;
	}

	/**
	 * Conditional logout form
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function display_logout_form( $args ) {
		if ( is_user_logged_in() && isset( $args['mode'] ) && $args['mode'] == 'login' ) {

			if ( isset( UM()->user()->preview ) && UM()->user()->preview ) {
				return $args;
			}

			if ( get_current_user_id() != um_user( 'ID' ) ) {
				um_fetch_user( get_current_user_id() );
			}

			$args['template'] = 'logout';
		}

		return $args;
	}


	/**
	 * Filter shortcode args
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function parse_shortcode_args( $args ) {
		if ( $this->message_mode == true ) {
			if ( ! empty( $_REQUEST['um_role'] ) ) {
				$args['template'] = 'message';
				$roleID = sanitize_key( $_REQUEST['um_role'] );
				$role = UM()->roles()->role_data( $roleID );

				if ( ! empty( $role ) && ! empty( $role['status'] ) ) {
					$message_key = $role['status'] . '_message';
					$this->custom_message = ! empty( $role[ $message_key ] ) ? $this->convert_user_tags( stripslashes( $role[ $message_key ] ) ) : '';
				}
			}
		}

		foreach ( $args as $k => $v ) {
			$args[ $k ] = maybe_unserialize( $args[ $k ] );
		}

		return $args;
	}


	/**
	 * Emoji support
	 *
	 * @todo Maybe deprecate soon because there is native `wp_staticize_emoji()`
	 *
	 * @param $content
	 *
	 * @return mixed|string
	 */
	public function emotize( $content ) {
		$content = stripslashes( $content );
		foreach ( self::$emoji as $code => $val ) {
			$regex = str_replace(array('(', ')'), array("\\" . '(', "\\" . ')'), $code);
			$content = preg_replace('/(' . $regex . ')(\s|$)/', '<img src="' . $val . '" alt="' . $code . '" title="' . $code . '" class="emoji" />$2', $content);
		}
		return $content;
	}


	/**
	 * Remove wpautop filter for post content if it's UM core page
	 */
	function is_um_page() {
		if ( is_ultimatemember() ) {
			remove_filter( 'the_content', 'wpautop' );
		}
	}


	/**
	 * Retrieve core login form
	 *
	 * @return int
	 */
	function core_login_form() {
		$forms = get_posts(array('post_type' => 'um_form', 'posts_per_page' => 1, 'meta_key' => '_um_core', 'meta_value' => 'login'));
		$form_id = isset( $forms[0]->ID ) ? $forms[0]->ID: 0;

		return $form_id;
	}


	/**
	 * Load a compatible template
	 *
	 * @param $tpl
	 */
	function load_template( $tpl ) {
		$loop = ( $this->loop ) ? $this->loop : array();

		$args = array();
		if ( isset( $this->set_args ) && is_array( $this->set_args ) ) {
			$args = $this->set_args;

			unset( $args['file'], $args['theme_file'], $args['tpl'] );

			$args = apply_filters( 'um_template_load_args', $args, $tpl );

			/**
			 * This use of extract() cannot be removed. There are many possible ways that
			 * templates could depend on variables that it creates existing, and no way to
			 * detect and deprecate it.
			 *
			 * Passing the EXTR_SKIP flag is the safest option, ensuring globals and
			 * function variables cannot be overwritten.
			 *
			 * @var array $search_filters
			 */
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $args, EXTR_SKIP );
		}

		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
			if ( 'members' === $tpl ) {
				$tpl = 'v3/directory/wrapper';
			} else {
				$tpl = 'v3/' . $tpl;
			}
		}

		$file       = UM_PATH . "templates/{$tpl}.php";
		$theme_file = get_stylesheet_directory() . "/ultimate-member/templates/{$tpl}.php";
		if ( file_exists( $theme_file ) ) {
			$file = $theme_file;
		}

		if ( file_exists( $file ) ) {
			// Avoid Directory Traversal vulnerability by the checking the realpath.
			// Templates can be situated only in the get_stylesheet_directory() or plugindir templates.
			$real_file = wp_normalize_path( realpath( $file ) );
			if ( 0 === strpos( $real_file, wp_normalize_path( UM_PATH . "templates" . DIRECTORY_SEPARATOR ) ) || 0 === strpos( $real_file, wp_normalize_path( get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'ultimate-member' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR ) ) ) {
				include $file;
			}
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
	function get_class($mode, $args = array()) {

		$classes = 'um-' . $mode;

		if (is_admin()) {
			$classes .= ' um-in-admin';
		}

		if (isset(UM()->form()->errors) && UM()->form()->errors) {
			$classes .= ' um-err';
		}

		if ( true === UM()->fields()->editing ) {
			$classes .= ' um-editing';
		}

		if ( true === UM()->fields()->viewing ) {
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
	 * @param array $args
	 *
	 * @return string
	 */
	function ultimatemember_login( $args = array() ) {
		global $wpdb;

		$args = ! empty( $args ) ? $args : array();

		$default_login = $wpdb->get_var(
			"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'login' AND
					  pm2.meta_value = '1'"
		);

		$args['form_id'] = $default_login;
		$shortcode_attrs = '';
		foreach ( $args as $key => $value ) {
			$shortcode_attrs .= " {$key}=\"{$value}\"";
		}

		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
		} else {
			return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
		}
	}


	/**
	 * @param array $args
	 *
	 * @return string
	 */
	function ultimatemember_register( $args = array() ) {
		global $wpdb;

		$args = ! empty( $args ) ? $args : array();

		$default_register = $wpdb->get_var(
			"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'register' AND
					  pm2.meta_value = '1'"
		);

		$args['form_id'] = $default_register;
		$shortcode_attrs = '';
		foreach ( $args as $key => $value ) {
			$shortcode_attrs .= " {$key}=\"{$value}\"";
		}

		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
		} else {
			return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
		}
	}


	/**
	 * @param array $args
	 *
	 * @return string
	 */
	function ultimatemember_profile( $args = array() ) {
		global $wpdb;

		$args = ! empty( $args ) ? $args : array();

		$default_profile = $wpdb->get_var(
			"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->postmeta} pm2 ON( pm.post_id = pm2.post_id AND pm2.meta_key = '_um_is_default' )
				WHERE pm.meta_key = '_um_mode' AND
					  pm.meta_value = 'profile' AND
					  pm2.meta_value = '1'"
		);

		$args['form_id'] = $default_profile;

		$shortcode_attrs = '';
		foreach ( $args as $key => $value ) {
			$shortcode_attrs .= " {$key}=\"{$value}\"";
		}

		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			return do_shortcode( "[ultimatemember {$shortcode_attrs} /]" );
		} else {
			return apply_shortcodes( "[ultimatemember {$shortcode_attrs} /]" );
		}
	}


	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function ultimatemember_directory( $args = array() ) {
		/** There is possible to use 'shortcode_atts_ultimatemember' filter for getting customized $atts. This filter is documented in wp-includes/shortcodes.php "shortcode_atts_{$shortcode}" */
		$args = shortcode_atts(
			array(
				'id' => false,
			),
			$args,
			'ultimatemember_directory'
		);

		if ( empty( $args['id'] ) || ! is_numeric( $args['id'] ) ) {
			return '';
		}

		$directory = get_post( $args['id'] );
		if ( empty( $directory ) ) {
			return '';
		}

		if ( 'publish' !== $directory->post_status ) {
			return '';
		}

		$post_data = UM()->query()->post_data( $args['id'] );
		$args      = array_merge( $args, $post_data );

		do_action( 'um_pre_directory_shortcode', $args );

		$template = 'members';
		if ( $this->template_exists( $args['template'] ) ) {
			$template = $args['template'];
		}

		ob_start();

		$this->template_load( $template, $args );

		$file = UM_PATH . 'assets/dynamic_css/dynamic-directory.php';
		if ( file_exists( $file ) ) {
			include_once $file;
		}

		do_action( 'um_after_everything_output' );

		return ob_get_clean();
	}

	/**
	 * Shortcode
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function ultimatemember( $args = array() ) {
		// There is possible to use 'shortcode_atts_ultimatemember' filter for getting customized `$args`.
		$args = shortcode_atts(
			array(
				'form_id'  => '',
				'is_block' => false,
			),
			$args,
			'ultimatemember'
		);

		// Sanitize shortcode arguments.
		$args['form_id']  = ! empty( $args['form_id'] ) ? absint( $args['form_id'] ) : '';
		$args['is_block'] = (bool) $args['is_block'];

		/**
		 * Filters variable for enable singleton shortcode loading on the same page.
		 * Note: Set it to `false` if you don't need to render the same form twice or more on the same page.
		 *
		 * @since 2.6.8
		 * @since 2.6.9 $disable argument set to `true` by default
		 *
		 * @hook  um_ultimatemember_shortcode_disable_singleton
		 *
		 * @param {bool}  $disable Disabled singleton. By default, it's `true`.
		 * @param {array} $args    Shortcode arguments.
		 *
		 * @return {bool} Disabled singleton or not.
		 *
		 * @example <caption>Turn off ability to use ultimatemember shortcode twice.</caption>
		 * add_filter( 'um_ultimatemember_shortcode_disable_singleton', '__return_false' );
		 */
		$disable_singleton_shortcode = apply_filters( 'um_ultimatemember_shortcode_disable_singleton', true, $args );
		if ( false === $disable_singleton_shortcode ) {
			if ( isset( $args['form_id'] ) ) {
				$id = $args['form_id'];
				if ( isset( $this->forms_exist[ $id ] ) && true === $this->forms_exist[ $id ] ) {
					return '';
				}
				$this->forms_exist[ $id ] = true;
			}
		}

		return $this->load( $args );
	}

	/**
	 * Load a module with global function
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function load( $args ) {
		$defaults = array();
		$args     = wp_parse_args( $args, $defaults );

		// When to not continue.
		if ( ! array_key_exists( 'form_id', $args ) ) {
			return '';
		}

		$this->form_id = $args['form_id'];
		if ( empty( $this->form_id ) ) {
			return '';
		}

		$this->form_status = get_post_status( $this->form_id );
		if ( 'publish' !== $this->form_status ) {
			return '';
		}

		UM()->fields()->set_id = absint( $this->form_id );

		// get data into one global array
		$post_data = UM()->query()->post_data( $this->form_id );
		$args      = array_merge( $args, $post_data );

		if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
			wp_enqueue_style( 'um_new_design' );
			wp_enqueue_script( 'um_new_design' );

			if ( 'directory' === $post_data['mode'] ) {
				wp_enqueue_style( 'um_directory' );
				wp_enqueue_script( 'um_directory' );
			}
		}

		ob_start();

		/**
		 * Filters arguments for loading Ultimate Member shortcodes.
		 *
		 * @since 1.3.x
		 * @hook  um_pre_args_setup
		 *
		 * @param {array} $args Data for loading shortcode.
		 *
		 * @return {array} Data for loading shortcode.
		 *
		 * @example <caption>Change arguments on load shortcode.</caption>
		 * function my_pre_args_setup( $args ) {
		 *     // your code here
		 *     return $args;
		 * }
		 * add_filter( 'um_pre_args_setup', 'my_pre_args_setup' );
		 */
		$args = apply_filters( 'um_pre_args_setup', $args );

		if ( ! isset( $args['template'] ) ) {
			$args['template'] = '';
		}

		if ( isset( $post_data['template'] ) && $post_data['template'] !== $args['template'] ) {
			$args['template'] = $post_data['template'];
		}

		if ( ! $this->template_exists( $args['template'] ) ) {
			$args['template'] = $post_data['mode'];
		}

		if ( ! isset( $post_data['template'] ) ) {
			$post_data['template'] = $post_data['mode'];
		}

		if ( 'directory' === $args['mode'] ) {
			if ( defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {

			} else {
				wp_enqueue_script( 'um_members' );
				wp_enqueue_style( 'um_members' );
			}
		} elseif ( 'register' === $args['mode'] ) {
			wp_enqueue_script( 'um-gdpr' );
		}

		if ( 'directory' !== $args['mode'] ) {
			$args = array_merge( $post_data, $args );

			if ( empty( $args['use_custom_settings'] ) ) {
				$args = array_merge( $args, $this->get_css_args( $args ) );
			} else {
				$args = array_merge( $this->get_css_args( $args ), $args );
			}
		}

		/**
		 * Filters change arguments on load shortcode.
		 *
		 * @since 1.3.x
		 * @hook  um_shortcode_args_filter
		 *
		 * @param {array} $args Shortcode arguments.
		 *
		 * @return {array} Shortcode arguments.
		 *
		 * @example <caption>Change arguments on load shortcode.</caption>
		 * function my_shortcode_args( $args ) {
		 *     // your code here
		 *     return $args;
		 * }
		 * add_filter( 'um_shortcode_args_filter', 'my_shortcode_args' );
		 */
		$args = apply_filters( 'um_shortcode_args_filter', $args );

		if ( ! array_key_exists( 'mode', $args ) || ! array_key_exists( 'template', $args ) ) {
			ob_get_clean();
			return '';
		}
		$mode = $args['mode'];

		// Not display on admin preview.
		if ( empty( $_POST['act_id'] ) || 'um_admin_preview_form' !== sanitize_key( $_POST['act_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			/**
			 * Filters the ability to show registration form for the logged-in users.
			 * Set it to true for displaying registration form for the logged-in users.
			 *
			 * @since 2.1.20
			 * @hook um_registration_for_loggedin_users
			 *
			 * @param {bool}  $show Show registration form for the logged-in users. By default, it's false
			 * @param {array} $args Shortcode arguments.
			 *
			 * @return {bool} Show registration form for the logged-in users.
			 *
			 * @example <caption>Show registration form for the logged-in users for all UM registration forms on your website.</caption>
			 * add_filter( 'um_registration_for_loggedin_users', '__return_true' );
			 */
			$enable_loggedin_registration = apply_filters( 'um_registration_for_loggedin_users', false, $args );

			if ( ! $enable_loggedin_registration && 'register' === $mode && is_user_logged_in() ) {
				ob_get_clean();
				return __( 'You are already registered.', 'ultimate-member' );
			}
		}

		if ( 'profile' === $mode && ! empty( $args['is_block'] ) && ! is_user_logged_in() ) {
			ob_get_clean();
			return '';
		}

		// For profiles only.
		if ( 'profile' === $mode && um_profile_id() ) {
			// Set requested user if it's not setup from permalinks (for not profile page in edit mode).
			if ( ! um_get_requested_user() ) {
				um_set_requested_user( um_profile_id() );
			}

			if ( ! empty( $args['use_custom_settings'] ) && ! empty( $args['role'] ) ) {
				// Option "Apply custom settings to this form". Option "Make this profile form role-specific".
				// Show the first Profile Form with role selected, don't show profile forms below the page with other role-specific setting.
				if ( empty( $this->profile_role ) ) {
					$current_user_roles = UM()->roles()->get_all_user_roles( um_profile_id() );

					if ( empty( $current_user_roles ) ) {
						ob_get_clean();
						return '';
					}
					if ( is_array( $args['role'] ) ) {
						if ( ! count( array_intersect( $args['role'], $current_user_roles ) ) ) {
							ob_get_clean();
							return '';
						}
					} elseif ( ! in_array( $args['role'], $current_user_roles, true ) ) {
						ob_get_clean();
						return '';
					}

					$this->profile_role = $args['role'];
				} elseif ( $this->profile_role !== $args['role'] ) {
					ob_get_clean();
					return '';
				}
			}
		}

		$content = apply_filters( 'um_force_shortcode_render', false, $args );
		if ( false !== $content ) {
			ob_get_clean();
			return $content;
		}

		/**
		 * Fires before loading form shortcode.
		 *
		 * Note: $mode can be 'profile', 'login', 'register', 'account'.
		 *
		 * @since 1.3.x
		 * @hook  um_pre_{$mode}_shortcode
		 *
		 * @param {array} $args Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action before loading a registration form shortcode.</caption>
		 * function my_pre_register_shortcode( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_pre_register_shortcode', 'my_pre_register_shortcode' );
		 * @example <caption>Make any custom action before loading a login form shortcode.</caption>
		 * function my_pre_login_shortcode( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_pre_login_shortcode', 'my_pre_login_shortcode' );
		 * @example <caption>Make any custom action before loading a password reset form shortcode.</caption>
		 * function my_pre_password_shortcode( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_pre_password_shortcode', 'my_pre_password_shortcode' );
		 * @example <caption>Make any custom action before loading a profile form shortcode.</caption>
		 * function my_pre_profile_shortcode( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_pre_profile_shortcode', 'my_pre_profile_shortcode' );
		 * @example <caption>Make any custom action before loading an account form shortcode.</caption>
		 * function my_pre_account_shortcode( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_pre_account_shortcode', 'my_pre_account_shortcode' );
		 */
		do_action( "um_pre_{$mode}_shortcode", $args );
		/**
		 * Fires before loading form shortcode.
		 *
		 * @since 1.3.x
		 * @hook  um_before_form_is_loaded
		 *
		 * @param {array} $args Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action before loading UM form shortcode.</caption>
		 * function my_pre_shortcode( $args ) {
		 *     // your code here
		 * }
		 * add_action( 'um_before_form_is_loaded', 'my_pre_shortcode', 10, 1 );
		 */
		do_action( 'um_before_form_is_loaded', $args );
		/**
		 * Fires before loading a form shortcode.
		 *
		 * @since 1.3.x
		 * @todo Deprecate since 2.7.0. Use `um_pre_{$mode}_shortcode` or `um_before_form_is_loaded` instead.
		 * @hook  um_before_{$mode}_form_is_loaded
		 *
		 * @param {array} $args Form shortcode arguments.
		 */
		do_action( "um_before_{$mode}_form_is_loaded", $args );

		if ( 'directory' === $mode && defined( 'UM_DEV_MODE' ) && UM_DEV_MODE && UM()->options()->get( 'enable_new_ui' ) ) {
			// Get default and real arguments
			$config_args = array();
			foreach ( UM()->config()->core_directory_meta['members'] as $config_k => $config_v ) {
				$config_args[ str_replace( '_um_', '', $config_k ) ] = $config_v;
			}

			$args = wp_parse_args( $args, $config_args );

			$unique_hash         = substr( md5( $args['form_id'] ), 10, 5 );
			$args['unique_hash'] = $unique_hash;

			global $post;
			$args['post_id'] = ! empty( $post->ID ) ? $post->ID : '';

			// Current user priority role.
			$priority_user_role = false;
			if ( is_user_logged_in() ) {
				$priority_user_role = UM()->roles()->get_priority_user_role( um_user( 'ID' ) );
			}

			$args = apply_filters( 'um_member_directory_arguments_on_load', $args );

			// Views.
			$single_view = false;

			if ( ! empty( $args['view_types'] ) && is_array( $args['view_types'] ) ) {
				$args['view_types'] = array_filter(
					$args['view_types'],
					function ( $item ) {
						return array_key_exists( $item, UM()->member_directory()->view_types );
					}
				);
			}

			if ( empty( $args['view_types'] ) || ! is_array( $args['view_types'] ) ) {
				$args['view_types'] = array( 'grid', 'list' );
			}

			if ( 1 === count( $args['view_types'] ) ) {
				$single_view  = true;
				$current_view = $args['view_types'][0];
				$default_view = $current_view;
			} else {
				$args['default_view'] = ! empty( $args['default_view'] ) ? $args['default_view'] : $args['view_types'][0];
				$default_view         = $args['default_view'];
				$current_view         = ( ! empty( $_GET[ 'view_type_' . $unique_hash ] ) && in_array( $_GET[ 'view_type_' . $unique_hash ], $args['view_types'], true ) ) ? sanitize_text_field( $_GET[ 'view_type_' . $unique_hash ] ) : $args['default_view'];
			}

			$args['default_view'] = $default_view;
			$args['single_view']  = $single_view;
			$args['current_view'] = $current_view;

			// Pagination.
			$args['current_page'] = ( ! empty( $_GET[ 'page_' . $unique_hash ] ) && is_numeric( $_GET[ 'page_' . $unique_hash ] ) ) ? absint( $_GET[ 'page_' . $unique_hash ] ) : 1;

			// Sorting.
			$default_sorting = ! empty( $args['sortby'] ) ? $args['sortby'] : 'user_registered_desc';
			if ( 'other' === $default_sorting && ! empty( $args['sortby_custom'] ) ) {
				$default_sorting = $args['sortby_custom'];
			}
			$args['default_sorting'] = $default_sorting;

			$sort_from_url   = '';
			$sorting_options = array();
			if ( ! empty( $args['enable_sorting'] ) ) {
				$custom_sorting_titles = array();
				$sorting_options       = empty( $args['sorting_fields'] ) ? array() : $args['sorting_fields'];

				$sorting_options_prepared = array();
				if ( ! empty( $sorting_options ) ) {
					foreach ( $sorting_options as $option ) {
						if ( is_array( $option ) ) {
							$option_keys                = array_keys( $option );
							$sorting_options_prepared[] = $option_keys[0];

							$custom_sorting_titles[ $option_keys[0] ] = ! empty( $option['label'] ) ? $option['label'] : $option[ $option_keys[0] ];
						} else {
							$sorting_options_prepared[] = $option;
						}
					}
				}

				$all_sorting_options = UM()->member_directory()->sort_fields;

				if ( ! in_array( $default_sorting, $sorting_options_prepared, true ) ) {
					$sorting_options_prepared[] = $default_sorting;

					$label = $default_sorting;
					if ( ! empty( $args['sortby_custom_label'] ) && 'other' === $args['sortby'] ) {
						$label = $args['sortby_custom_label'];
					} elseif ( ! empty( $all_sorting_options[ $default_sorting ] ) ) {
						$label = $all_sorting_options[ $default_sorting ];
					}

					$label = ( 'random' === $label ) ? __( 'Random', 'ultimate-member' ) : $label;

					$custom_sorting_titles[ $default_sorting ] = $label;
				}

				if ( ! empty( $sorting_options_prepared ) ) {
					$sorting_options = array_intersect_key( array_merge( $all_sorting_options, $custom_sorting_titles ), array_flip( $sorting_options_prepared ) );
				}

				$sorting_options = apply_filters( 'um_member_directory_pre_display_sorting', $sorting_options, $args );
				$sort_from_url   = ( ! empty( $_GET[ 'sort_' . $unique_hash ] ) && in_array( sanitize_text_field( $_GET[ 'sort_' . $unique_hash ] ), array_keys( $sorting_options ), true ) ) ? sanitize_text_field( $_GET[ 'sort_' . $unique_hash ] ) : $default_sorting;
			}

			$args['sort_from_url']   = $sort_from_url;
			$args['sorting_options'] = $sorting_options;

			// Search.
			$search          = isset( $args['search'] ) ? $args['search'] : false;
			$search_from_url = '';
			$show_search     = false;
			if ( ! empty( $search ) ) {
				$show_search = empty( $args['roles_can_search'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $args['roles_can_search'], true ) );
				if ( $show_search ) {
					$search_from_url = ! empty( $_GET[ 'search_' . $unique_hash ] ) ? stripslashes( sanitize_text_field( $_GET[ 'search_' . $unique_hash ] ) ) : '';
				}
			}

			$args['search']          = $search;
			$args['show_search']     = $show_search;
			$args['has_search']      = $search && $show_search;
			$args['search_from_url'] = $search_from_url;

			// Filters.
			$filters        = isset( $args['filters'] ) ? $args['filters'] : false;
			$show_filters   = false;
			$search_filters = array();
			if ( ! empty( $filters ) ) {
				$show_filters = empty( $args['roles_can_filter'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $args['roles_can_filter'], true ) );

				if ( $show_filters ) {
					if ( isset( $args['search_fields'] ) ) {
						$search_filters = apply_filters( 'um_frontend_member_search_filters', array_unique( array_filter( $args['search_fields'] ) ) );
					}

					if ( ! empty( $search_filters ) ) {
						$search_filters = array_filter(
							$search_filters,
							function( $item ) {
								return array_key_exists( $item, UM()->member_directory()->filter_fields );
							}
						);

						$search_filters = array_values( $search_filters );
					}

					$temp_search_filters = array();
					// Hide filter fields based on the field visibility.
					foreach ( $search_filters as $filter ) {
						$filter_data = UM()->fields()->get_field( $filter );
						if ( ! um_can_view_field( $filter_data ) ) {
							continue;
						}

						$filter_content = UM()->frontend()->directory()->show_filter( $filter, $args );
						if ( empty( $filter_content ) ) {
							continue;
						}

						$temp_search_filters[ $filter ] = array(
							'type'    => UM()->member_directory()->filter_types[ $filter ],
							'content' => $filter_content,
						);
					}
					$search_filters = $temp_search_filters;

					if ( empty( $search_filters ) ) {
						$filters      = false;
						$show_filters = false;
					}
				}
			}

			$args['filters']        = $filters;
			$args['show_filters']   = $show_filters;
			$args['search_filters'] = $search_filters;
			$args['has_filters']    = $filters && $show_filters && ! empty( $search_filters );

			// Classes
			$classes = '';
			if ( $args['has_search'] ) {
				$classes .= ' um-member-with-search';
			}

			if ( $args['has_filters'] ) {
				$classes .= ' um-member-with-filters';
			}

			if ( ! $single_view ) {
				$classes .= ' um-member-with-view';
			}

			if ( ! empty( $args['enable_sorting'] ) && ! empty( $sorting_options ) && count( $sorting_options ) > 1 ) {
				$classes .= ' um-member-with-sorting';
			}

			$args['classes'] = $classes;

			$filters_collapsible = true;
			$filters_expanded    = ! empty( $args['filters_expanded'] );
			if ( $filters_expanded ) {
				$filters_collapsible = ! empty( $args['filters_is_collapsible'] );
			}

			$not_searched = null;
			$not_filtered = null;
			if ( $args['has_search'] || $args['has_filters'] ) {
				$not_searched = true;
				if ( $args['has_search'] && ! empty( $search_from_url ) ) {
					$not_searched = false;
				}
				if ( $args['has_filters'] ) {
					$not_filtered = true;
					foreach ( $search_filters as $filter => $filter_data ) {
						// getting value from GET line
						switch ( UM()->frontend()->directory()->filter_types[ $filter ] ) {
							default:
								$not_searched = apply_filters( 'um_member_directory_filter_value_from_url', $not_searched, $filter );
								if ( ! $not_searched ) {
									$filters_expanded = true;
									$not_filtered     = false;
								}
								break;

							case 'select':
								// getting value from GET line
								$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ) : array();
								if ( ! empty( $filter_from_url ) ) {
									$filters_expanded = true;
									$not_searched     = false;
									$not_filtered     = false;
								}
								break;

							case 'slider':
								// getting value from GET line
								$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : '';
								if ( ! empty( $filter_from_url ) ) {
									$filters_expanded = true;
									$not_searched     = false;
									$not_filtered     = false;
								}
								break;

							case 'datepicker':
							case 'timepicker':
								// getting value from GET line
								$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $unique_hash ] ) : '';
								if ( ! empty( $filter_from_url ) ) {
									$filters_expanded = true;
									$not_searched     = false;
									$not_filtered     = false;
								}
								break;
						}
					}
				}
			}

			$args['must_search']  = ! empty( $args['must_search'] ) && ( $args['has_search'] || $args['has_filters'] );
			$args['not_searched'] = $not_searched;
			$args['not_filtered'] = $not_filtered;

			$args['filters_collapsible'] = $filters_collapsible;
			$args['filters_expanded']    = $filters_expanded;

			// send $args variable to the templates
			$args['t_args'] = $args;
		}

		$this->template_load( $args['template'], $args );

		$this->dynamic_css( $args );

		if ( 'logout' === $mode || um_get_requested_user() ) {
			um_reset_user();
		}

		/**
		 * Fires after load shortcode content.
		 *
		 * @since 2.0
		 * @hook  um_after_everything_output
		 *
		 * @param {array} $args Form shortcode arguments.
		 *
		 * @example <caption>Make any custom action after load shortcode content.</caption>
		 * function my_pre_shortcode() {
		 *     // your code here
		 * }
		 * add_action( 'um_after_everything_output', 'my_pre_shortcode', 10 );
		 */
		do_action( 'um_after_everything_output' );

		return ob_get_clean();
	}

	/**
	 * Get dynamic CSS args
	 *
	 * @param $args
	 * @return array
	 */
	public function get_css_args( $args ) {
		$arr = um_styling_defaults( $args['mode'] );
		$arr = array_merge(
			$arr,
			array(
				'form_id' => $args['form_id'],
				'mode'    => $args['mode'],
			)
		);
		return $arr;
	}

	/**
	 * Load dynamic CSS.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function dynamic_css( $args = array() ) {
		/**
		 * Filters for disable global dynamic CSS. It's false by default, set it to true to disable.
		 *
		 * @since 2.0
		 * @hook  um_disable_dynamic_global_css
		 *
		 * @param {bool} $disable Disable global CSS.
		 *
		 * @return {bool} Disable global CSS.
		 *
		 * @example <caption>Turn off enqueue of global dynamic CSS.</caption>
		 * add_filter( 'um_disable_dynamic_global_css', '__return_true' );
		 */
		$disable_css = apply_filters( 'um_disable_dynamic_global_css', false );
		if ( $disable_css ) {
			return '';
		}

		if ( empty( $args['form_id'] ) ) {
			return '';
		}

		include_once UM_PATH . 'assets/dynamic_css/dynamic-global.php';

		if ( array_key_exists( 'mode', $args ) && in_array( $args['mode'], array( 'profile', 'directory' ), true ) ) {
			$file = UM_PATH . 'assets/dynamic_css/dynamic-' . $args['mode'] . '.php';

			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}

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
	 * Checks if a template file exists
	 *
	 * @param $template
	 *
	 * @return bool
	 */
	function template_exists($template) {

		$file = UM_PATH . 'templates/' . $template . '.php';
		$theme_file = get_stylesheet_directory() . '/ultimate-member/templates/' . $template . '.php';

		if (file_exists($theme_file) || file_exists($file)) {
			return true;
		}

		return false;
	}


	/**
	 * Get File Name without path and extension
	 *
	 * @param $file
	 *
	 * @return mixed|string
	 */
	function get_template_name( $file ) {
		$file = basename( $file );
		$file = preg_replace( '/\\.[^.\\s]{3,4}$/', '', $file );
		return $file;
	}


	/**
	 * Get Templates
	 *
	 * @param null $excluded
	 *
	 * @return mixed
	 */
	function get_templates( $excluded = null ) {

		if ( $excluded ) {
			$array[ $excluded ] = __( 'Default Template', 'ultimate-member' );
		}

		$paths[] = glob( UM_PATH . 'templates/' . '*.php' );

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
	 * Get Shortcode for given form ID
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	function get_shortcode( $post_id ) {
		$shortcode = '[ultimatemember form_id="' . $post_id . '"]';
		return $shortcode;
	}


	/**
	 * Get Shortcode for given form ID
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	function get_default_shortcode( $post_id ) {
		$mode = UM()->query()->get_attr( 'mode', $post_id );

		switch ( $mode ) {
			case 'login':
				$shortcode = '[ultimatemember_login]';
				break;
			case 'profile':
				$shortcode = '[ultimatemember_profile]';
				break;
			case 'register':
				$shortcode = '[ultimatemember_register]';
				break;
			case 'directory':
				$shortcode = '[ultimatemember_directory]';
				break;
		}

		return $shortcode;
	}


	/**
	 * Convert access lock tags
	 *
	 * @param $str
	 *
	 * @return mixed|string
	 */
	function convert_locker_tags( $str ) {
		add_filter( 'um_template_tags_patterns_hook', array( &$this, 'add_placeholder' ), 10, 1 );
		add_filter( 'um_template_tags_replaces_hook', array( &$this, 'add_replace_placeholder' ), 10, 1 );
		return um_convert_tags( $str, array(), false );
	}

	/**
	 * Convert user tags in a string
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public function convert_user_tags( $str ) {
		$pattern_array = array(
			'{first_name}',
			'{last_name}',
			'{display_name}',
			'{user_avatar_small}',
			'{username}',
			'{nickname}',
			'{user_email}',
		);
		/**
		 * Filters the user placeholders patterns.
		 *
		 * @since 1.3.x
		 * @hook  um_allowed_user_tags_patterns
		 *
		 * @param {array} $patterns User Placeholders.
		 *
		 * @return {array} User Placeholders.
		 *
		 * @example <caption>Add the `{user_description}` placeholder.</caption>
		 * function my_allowed_user_tags( $patterns ) {
		 *     $patterns[] = '{user_description}';
		 *     return $patterns;
		 * }
		 * add_filter( 'um_allowed_user_tags_patterns', 'my_allowed_user_tags' );
		 */
		$pattern_array = apply_filters( 'um_allowed_user_tags_patterns', $pattern_array );
		foreach ( $pattern_array as $pattern ) {
			if ( preg_match( $pattern, $str ) ) {

				$value    = '';
				$usermeta = str_replace( array( '{', '}' ), '', $pattern );
				if ( is_user_logged_in() ) {
					if ( 'user_avatar_small' === $usermeta ) {
						$value = get_avatar( um_user( 'ID' ), 40 );
					} elseif ( um_user( $usermeta ) ) {
						$value = um_user( $usermeta );
					}

					if ( 'username' === $usermeta ) {
						$value = um_user( 'user_login' );
					}

					if ( 'nickname' === $usermeta ) {
						$value = um_profile( 'nickname' );
					}

					if ( 'user_email' === $usermeta ) {
						$value = um_user( 'user_email' );
					}

					/**
					 * Filters the user placeholders value of pattern for logged-in user.
					 *
					 * @since 1.3.x
					 * @hook  um_profile_tag_hook__{$usermeta}
					 *
					 * @param {string} $value User meta field value.
					 * @param {int}    $id    User ID.
					 *
					 * @return {string} User meta field value.
					 *
					 * @example <caption>Add the replacement value for `{user_description}` placeholder.</caption>
					 * function my_user_description( $value, $user_id ) {
					 *     $value = get_user_meta( $user_id, 'user_description', true );
					 *     return $value;
					 * }
					 * add_filter( 'um_profile_tag_hook__user_description', 'my_user_description', 10, 2 );
					 */
					$value = apply_filters( "um_profile_tag_hook__{$usermeta}", $value, um_user( 'ID' ) );
				} else {
					/**
					 * Filters the user placeholders value of pattern for not logged-in user.
					 *
					 * @since 2.6.11
					 * @hook  um_profile_nopriv_tag_hook__{$usermeta}
					 *
					 * @param {string} $value User meta field value.
					 *
					 * @return {string} User meta field value.
					 *
					 * @example <caption>Add the replacement value for `{user_description}` placeholder for not logged-in user.</caption>
					 * function my_nopriv_user_description( $value ) {
					 *     $value = ! empty( $_GET['user_description'] ) ? sanitize_text_field( $_GET['user_description'] ) : '';
					 *     return $value;
					 * }
					 * add_filter( 'um_profile_nopriv_tag_hook__user_description', 'my_nopriv_user_description' );
					 */
					$value = apply_filters( "um_profile_nopriv_tag_hook__{$usermeta}", $value );
				}

				$str = preg_replace( '/' . $pattern . '/', $value, $str );
			}
		}

		return $str;
	}

	/**
	 * @param array $args
	 * @param string $content
	 *
	 * @return string
	 */
	public function ultimatemember_searchform( $args = array(), $content = '' ) {
		if ( ! UM()->options()->get( 'members_page' ) ) {
			return '';
		}

		$member_directory_ids = array();

		$page_id = UM()->config()->permalinks['members'];
		if ( ! empty( $page_id ) ) {
			$member_directory_ids = UM()->member_directory()->get_member_directory_id( $page_id );
		}

		if ( empty( $member_directory_ids ) ) {
			return '';
		}

		//current user priority role
		$priority_user_role = false;
		if ( is_user_logged_in() ) {
			$priority_user_role = UM()->roles()->get_priority_user_role( get_current_user_id() );
		}

		$query = array();
		foreach ( $member_directory_ids as $directory_id ) {
			$directory_data = UM()->query()->post_data( $directory_id );

			if ( isset( $directory_data['roles_can_search'] ) ) {
				$directory_data['roles_can_search'] = maybe_unserialize( $directory_data['roles_can_search'] );
			}

			$show_search = empty( $directory_data['roles_can_search'] ) || ( ! empty( $priority_user_role ) && in_array( $priority_user_role, $directory_data['roles_can_search'] ) );
			if ( empty( $directory_data['search'] ) || ! $show_search ) {
				continue;
			}

			$hash = UM()->member_directory()->get_directory_hash( $directory_id );

			$query[ 'search_' . $hash ] = ! empty( $_GET[ 'search_' . $hash ] ) ? sanitize_text_field( $_GET[ 'search_' . $hash ] ) : '';
		}

		if ( empty( $query ) ) {
			return '';
		}

		$search_value = array_values( $query );

		$template = UM()->get_template( 'searchform.php', '', array( 'query' => $query, 'search_value' => $search_value[0], 'members_page' => um_get_core_page( 'members' ) ) );

		return $template;
	}


	/**
	 * UM Placeholders for login referrer
	 *
	 * @param $placeholders
	 *
	 * @return array
	 */
	function add_placeholder( $placeholders ) {
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
	function add_replace_placeholder( $replace_placeholders ) {
		$replace_placeholders[] = um_dynamic_login_page_redirect();
		return $replace_placeholders;
	}

}
