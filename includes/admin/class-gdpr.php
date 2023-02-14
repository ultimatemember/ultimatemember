<?php
namespace um\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\GDPR' ) ) {


	/**
	 * Class GDPR
	 * @package um\admin
	 */
	class GDPR {


		/**
		 * GDPR constructor.
		 */
		function __construct() {
			add_action( 'admin_init', array( &$this, 'plugin_add_suggested_privacy_content' ), 20 );
		}


		/**
		 * Return the default suggested privacy policy content.
		 *
		 * @return string The default policy content.
		 */
		private function plugin_get_default_privacy_content() {
			ob_start();

			include UM()->admin()->templates_path . 'gdpr.php';

			return ob_get_clean();
		}


		/**
		 * Add the suggested privacy policy text to the policy postbox.
		 */
		function plugin_add_suggested_privacy_content() {
			$content = $this->plugin_get_default_privacy_content();
			wp_add_privacy_policy_content( UM_PLUGIN_NAME, $content );
		}
	}
}
