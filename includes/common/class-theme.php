<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Theme
 *
 * @package um\common
 */
class Theme {

	public function hooks() {
		add_action( 'after_switch_theme', array( &$this, 'check_outdated_templates' ) );
	}

	/**
	 * Find outdated UM templates and notify Administrator.
	 *
	 */
	public function check_outdated_templates() {
		$templates = UM()->admin_settings()->get_override_templates( true );
		$out_date  = false;

		foreach ( $templates as $template ) {
			if ( 0 === $template['status_code'] ) {
				$out_date = true;
				break;
			}
		}

		if ( false === $out_date ) {
			delete_option( 'um_override_templates_outdated' );
		}
	}
}
