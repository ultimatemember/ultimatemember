<?php
namespace umm\recaptcha\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Notices
 *
 * @package umm\recaptcha\includes\admin
 */
class Notices {


	/**
	 * Notices constructor.
	 */
	public function __construct() {
		add_action( 'um_admin_create_notices', array( &$this, 'add_admin_notice' ) );
	}


	/**
	 * Adding admin notices about inactive reCAPTCHA when keys are empty
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
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ultimatemember&tab=modules&section=recaptcha' ) ); ?>" class="button button-primary"><?php esc_html_e( 'I already have the keys', 'ultimate-member' ); ?></a>&nbsp;
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
}
