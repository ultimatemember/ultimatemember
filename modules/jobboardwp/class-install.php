<?php
namespace umm\jobboardwp;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Install
 *
 * @package umm\jobboardwp
 */
class Install {


	/**
	 * @var array Default module settings
	 */
	public $settings_defaults;


	/**
	 * Install constructor.
	 */
	public function __construct() {
		//settings defaults
		$this->settings_defaults = array(
			'profile_tab_jobboardwp'         => 1,
			'profile_tab_jobboardwp_privacy' => 0,
			'account_tab_jobboardwp'         => 1,
			'job_apply_only_verified'        => 0,
			'job_show_pm_button'             => 0,
		);

		$notification_types_templates = array(
			'jb_job_approved' => __( 'Your <a href="{job_uri}">job</a> is now approved.', 'ultimate-member' ),
			'jb_job_expired'  => __( 'Your <a href="{job_uri}">job</a> is now expired.', 'ultimate-member' ),
		);

		foreach ( $notification_types_templates as $k => $template ) {
			$this->settings_defaults[ 'log_' . $k ] = 1;
			$this->settings_defaults[ 'log_' . $k . '_template' ] = $template;
		}
	}


	/**
	 *
	 */
	public function start() {
		UM()->options()->set_defaults( $this->settings_defaults );
	}
}
