<?php
namespace umm\recaptcha\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Init
 *
 * @package umm\recaptcha\includes\admin
 */
class Init {


	/**
	 * Init constructor.
	 */
	public function __construct() {
		add_action( 'um_admin_create_notices', array( &$this, 'add_admin_notice' ) );
		add_action( 'um_admin_add_form_metabox', array( &$this, 'add_form_metaboxes' ), 10 );
		add_filter( 'um_form_meta_map', array( &$this, 'add_form_meta_sanitize' ), 10, 1 );
		add_filter( 'um_settings_map', array( &$this, 'settings_map' ), 10, 1 );
		add_filter( 'um_settings_structure', array( &$this, 'add_settings' ), 10, 1 );
	}


	/**
	 * @param array $meta_map
	 *
	 * @return array
	 */
	public function add_form_meta_sanitize( $meta_map ) {
		$meta_map = array_merge(
			$meta_map,
			array(
				'_um_login_g_recaptcha_status'    => array(
					'sanitize' => 'bool',
				),
				'_um_login_g_recaptcha_score'     => array(
					'sanitize' => 'text',
				),
				'_um_register_g_recaptcha_status' => array(
					'sanitize' => 'bool',
				),
				'_um_register_g_recaptcha_score'  => array(
					'sanitize' => 'text',
				),
			)
		);
		return $meta_map;
	}


	/**
	 * @param array $settings_map
	 *
	 * @return array
	 */
	public function settings_map( $settings_map ) {
		$settings_map = array_merge(
			$settings_map,
			array(
				'g_recaptcha_status'               => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_version'              => array(
					'sanitize' => 'text',
				),
				'g_reCAPTCHA_site_key'             => array(
					'sanitize' => 'text',
				),
				'g_reCAPTCHA_secret_key'           => array(
					'sanitize' => 'text',
				),
				'g_reCAPTCHA_score'                => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_sitekey'              => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_secretkey'            => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_type'                 => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_language_code'        => array(
					'sanitize' => 'text',
				),
				'g_recaptcha_size'                 => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_theme'                => array(
					'sanitize' => 'key',
				),
				'g_recaptcha_password_reset'       => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_lostpasswordform'  => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_login_form'        => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_login_form_widget' => array(
					'sanitize' => 'bool',
				),
				'g_recaptcha_wp_register_form'     => array(
					'sanitize' => 'bool',
				),
			)
		);
		return $settings_map;
	}


	/**
	 *
	 */
	public function add_admin_notice() {
		$status    = UM()->options()->get( 'g_recaptcha_status' );
		$sitekey   = UM()->options()->get( 'g_recaptcha_sitekey' ) || UM()->options()->get( 'g_reCAPTCHA_site_key' );
		$secretkey = UM()->options()->get( 'g_recaptcha_secretkey' ) || UM()->options()->get( 'g_reCAPTCHA_secret_key' );

		if ( ! $status || ( $sitekey && $secretkey ) ) {
			return;
		}

		$allowed_html = array(
			'strong' => array(),
		);

		ob_start(); ?>

		<p><?php echo wp_kses( __( 'Google reCAPTCHA is active on your site. However you need to fill in both your <strong>site key and secret key</strong> to start protecting your site against spam.', 'ultimate-member' ), $allowed_html ); ?></p>

		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=um_options&tab=modules&section=recaptcha' ) ); ?>" class="button button-primary"><?php esc_html_e( 'I already have the keys', 'ultimate-member' ); ?></a>&nbsp;
			<a href="http://google.com/recaptcha" class="button-secondary" target="_blank"><?php esc_html_e( 'Generate your site and secret key', 'ultimate-member' ); ?></a>
		</p>

		<?php
		$message = ob_get_clean();

		UM()->admin()->notices()->add_notice(
			'um_recaptcha_notice',
			array(
				'class'       => 'updated',
				'message'     => $message,
				'dismissible' => true,
			),
			10
		);
	}


	/**
	 *
	 */
	public function add_form_metaboxes() {
		$module_data = UM()->modules()->get_data( 'recaptcha' );
		if ( ! $module_data ) {
			return;
		}

		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}


		add_meta_box(
			'um-admin-form-register-recaptcha{' . $module_data['path'] . '}',
			__( 'Google reCAPTCHA', 'ultimate-member' ),
			array( UM()->admin()->metabox(), 'load_metabox_form' ),
			'um_form',
			'side',
			'default'
		);

		add_meta_box(
			"um-admin-form-login-recaptcha{" . $module_data['path'] . "}",
			__( 'Google reCAPTCHA', 'ultimate-member' ),
			array( UM()->admin()->metabox(), 'load_metabox_form' ),
			'um_form',
			'side',
			'default'
		);
	}


	/**
	 * Extend settings
	 *
	 * @param array $settings
	 * @return array
	 */
	public function add_settings( $settings ) {
		$settings['modules']['sections']['recaptcha'] = array(
			'title'  => __( 'Google reCAPTCHA', 'ultimate-member' ),
			'fields' => array(
				array(
					'id'          => 'g_recaptcha_status',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA', 'ultimate-member' ),
					'description' => __( 'Turn on or off your Google reCAPTCHA on your site registration and login forms by default.', 'ultimate-member' ),
				),
				array(
					'id'          => 'g_recaptcha_version',
					'type'        => 'select',
					'label'       => __( 'reCAPTCHA type', 'ultimate-member' ),
					'description' => __( 'Choose the type of reCAPTCHA for this site key. A site key only works with a single reCAPTCHA site type. See <a href="https://g.co/recaptcha/sitetypes" target="_blank">Site Types</a> for more details.', 'ultimate-member' ),
					'options'     => array(
						'v2' => __( 'reCAPTCHA v2', 'ultimate-member' ),
						'v3' => __( 'reCAPTCHA v3', 'ultimate-member' ),
					),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				/* reCAPTCHA v3 */
				array(
					'id'          => 'g_reCAPTCHA_site_key',
					'type'        => 'text',
					'label'       => __( 'Site Key', 'ultimate-member' ),
					'description' => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'ultimate-member' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				array(
					'id'          => 'g_reCAPTCHA_secret_key',
					'type'        => 'text',
					'label'       => __( 'Secret Key', 'ultimate-member' ),
					'description' => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'ultimate-member' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				array(
					'id'          => 'g_reCAPTCHA_score',
					'type'        => 'text',
					'label'       => __( 'reCAPTCHA Score', 'ultimate-member' ),
					'description' => __( 'Consider answers with a score >= to the specified as safe. Set the score in the 0 to 1 range. E.g. 0.5', 'ultimate-member' ),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v3' ),
				),
				/* reCAPTCHA v2 */
				array(
					'id'          => 'g_recaptcha_sitekey',
					'type'        => 'text',
					'label'       => __( 'Site Key', 'ultimate-member' ),
					'description' => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'ultimate-member' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_secretkey',
					'type'        => 'text',
					'label'       => __( 'Secret Key', 'ultimate-member' ),
					'description' => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'ultimate-member' ),
					'size'        => 'medium',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_type',
					'type'        => 'select',
					'label'       => __( 'Type', 'ultimate-member' ),
					'description' => __( 'The type of reCAPTCHA to serve.', 'ultimate-member' ),
					'options'     => array(
						'audio' => __( 'Audio', 'ultimate-member' ),
						'image' => __( 'Image', 'ultimate-member' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_language_code',
					'type'        => 'select',
					'label'       => __( 'Language', 'ultimate-member' ),
					'description' => __( 'Select the language to be used in your reCAPTCHA.', 'ultimate-member' ),
					'options'     => array(
						'ar'     => __( 'Arabic', 'ultimate-member' ),
						'af'     => __( 'Afrikaans', 'ultimate-member' ),
						'am'     => __( 'Amharic', 'ultimate-member' ),
						'hy'     => __( 'Armenian', 'ultimate-member' ),
						'az'     => __( 'Azerbaijani', 'ultimate-member' ),
						'eu'     => __( 'Basque', 'ultimate-member' ),
						'bn'     => __( 'Bengali', 'ultimate-member' ),
						'bg'     => __( 'Bulgarian', 'ultimate-member' ),
						'ca'     => __( 'Catalan', 'ultimate-member' ),
						'zh-HK'  => __( 'Chinese (Hong Kong)', 'ultimate-member' ),
						'zh-CN'  => __( 'Chinese (Simplified)', 'ultimate-member' ),
						'zh-TW'  => __( 'Chinese (Traditional)', 'ultimate-member' ),
						'hr'     => __( 'Croatian', 'ultimate-member' ),
						'cs'     => __( 'Czech', 'ultimate-member' ),
						'da'     => __( 'Danish', 'ultimate-member' ),
						'nl'     => __( 'Dutch', 'ultimate-member' ),
						'en-GB'  => __( 'English (UK)', 'ultimate-member' ),
						'en'     => __( 'English (US)', 'ultimate-member' ),
						'et'     => __( 'Estonian', 'ultimate-member' ),
						'fil'    => __( 'Filipino', 'ultimate-member' ),
						'fi'     => __( 'Finnish', 'ultimate-member' ),
						'fr'     => __( 'French', 'ultimate-member' ),
						'fr-CA'  => __( 'French (Canadian)', 'ultimate-member' ),
						'gl'     => __( 'Galician', 'ultimate-member' ),
						'ka'     => __( 'Kartuli', 'ultimate-member' ),
						'de'     => __( 'German', 'ultimate-member' ),
						'de-AT'  => __( 'German (Austria)', 'ultimate-member' ),
						'de-CH'  => __( 'German (Switzerland)', 'ultimate-member' ),
						'el'     => __( 'Greek', 'ultimate-member' ),
						'gu'     => __( 'Gujarati', 'ultimate-member' ),
						'iw'     => __( 'Hebrew', 'ultimate-member' ),
						'hi'     => __( 'Hindi', 'ultimate-member' ),
						'hu'     => __( 'Hungarain', 'ultimate-member' ),
						'is'     => __( 'Icelandic', 'ultimate-member' ),
						'id'     => __( 'Indonesian', 'ultimate-member' ),
						'it'     => __( 'Italian', 'ultimate-member' ),
						'ja'     => __( 'Japanese', 'ultimate-member' ),
						'kn'     => __( 'Kannada', 'ultimate-member' ),
						'ko'     => __( 'Korean', 'ultimate-member' ),
						'lo'     => __( 'Laothian', 'ultimate-member' ),
						'lv'     => __( 'Latvian', 'ultimate-member' ),
						'lt'     => __( 'Lithuanian', 'ultimate-member' ),
						'ms'     => __( 'Malay', 'ultimate-member' ),
						'ml'     => __( 'Malayalam', 'ultimate-member' ),
						'mr'     => __( 'Marathi', 'ultimate-member' ),
						'mn'     => __( 'Mongolian', 'ultimate-member' ),
						'no'     => __( 'Norwegian', 'ultimate-member' ),
						'fa'     => __( 'Persian', 'ultimate-member' ),
						'pl'     => __( 'Polish', 'ultimate-member' ),
						'pt'     => __( 'Portuguese', 'ultimate-member' ),
						'pt-BR'  => __( 'Portuguese (Brazil)', 'ultimate-member' ),
						'pt-PT'  => __( 'Portuguese (Portugal)', 'ultimate-member' ),
						'ro'     => __( 'Romanian', 'ultimate-member' ),
						'ru'     => __( 'Russian', 'ultimate-member' ),
						'sr'     => __( 'Serbian', 'ultimate-member' ),
						'si'     => __( 'Sinhalese', 'ultimate-member' ),
						'sk'     => __( 'Slovak', 'ultimate-member' ),
						'sl'     => __( 'Slovenian', 'ultimate-member' ),
						'es'     => __( 'Spanish', 'ultimate-member' ),
						'es-419' => __( 'Spanish (Latin America)', 'ultimate-member' ),
						'sw'     => __( 'Swahili', 'ultimate-member' ),
						'sv'     => __( 'Swedish', 'ultimate-member' ),
						'ta'     => __( 'Tamil', 'ultimate-member' ),
						'te'     => __( 'Telugu', 'ultimate-member' ),
						'th'     => __( 'Thai', 'ultimate-member' ),
						'tr'     => __( 'Turkish', 'ultimate-member' ),
						'uk'     => __( 'Ukrainian', 'ultimate-member' ),
						'ur'     => __( 'Urdu', 'ultimate-member' ),
						'vi'     => __( 'Vietnamese', 'ultimate-member' ),
						'zu'     => __( 'Zulu', 'ultimate-member' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_size',
					'type'        => 'select',
					'label'       => __( 'Size', 'ultimate-member' ),
					'description' => __( 'The type of reCAPTCHA to serve.', 'ultimate-member' ),
					'options'     => array(
						'compact'   => __( 'Compact', 'ultimate-member' ),
						'normal'    => __( 'Normal', 'ultimate-member' ),
						'invisible' => __( 'Invisible', 'ultimate-member' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_version', '=', 'v2' ),
				),
				array(
					'id'          => 'g_recaptcha_theme',
					'type'        => 'select',
					'label'       => __( 'Theme', 'ultimate-member' ),
					'description' => __( 'Select a color theme of the widget.', 'ultimate-member' ),
					'options'     => array(
						'dark'  => __( 'Dark', 'ultimate-member' ),
						'light' => __( 'Light', 'ultimate-member' ),
					),
					'size'        => 'small',
					'conditional' => array( 'g_recaptcha_size', '!=', 'invisible' ),
				),
				/* Forms */
				array(
					'id'          => 'g_recaptcha_password_reset',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on the UM password reset form', 'ultimate-member' ),
					'description' => __( 'Display the google Google reCAPTCHA on the Ultimate Member password reset form.', 'ultimate-member' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_lostpasswordform',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on wp-login.php lost password form', 'ultimate-member' ),
					'description' => __( 'Display the google Google reCAPTCHA on wp-login.php lost password form.', 'ultimate-member' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_login_form',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on wp-login.php form', 'ultimate-member' ),
					'description' => __( 'Display the google Google reCAPTCHA on wp-login.php form.', 'ultimate-member' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_login_form_widget',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on login form through `wp_login_form()`', 'ultimate-member' ),
					'description' => __( 'Display the google Google reCAPTCHA on login form through `wp_login_form()`.', 'ultimate-member' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
				array(
					'id'          => 'g_recaptcha_wp_register_form',
					'type'        => 'checkbox',
					'label'       => __( 'Enable Google reCAPTCHA on wp-login.php registration form', 'ultimate-member' ),
					'description' => __( 'Display the google Google reCAPTCHA on wp-login.php registration form`.', 'ultimate-member' ),
					'conditional' => array( 'g_recaptcha_status', '=', 1 ),
				),
			),
		);

		return $settings;
	}
}
