<?php
namespace umm\jobboardwp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Install
 *
 * @package umm\jobboardwp
 */
class Install {

	/**
	 * Default module settings.
	 *
	 * @var array
	 */
	public $settings_defaults;

	/**
	 * @var array
	 */
	public $roles_meta = array();

	/**
	 * Install constructor.
	 */
	public function __construct() {
		//settings defaults
		$this->settings_defaults = array(
			'account_tab_jobboardwp'         => 1,
			'activity-new-jobboardwp-job'    => 1, // Activity cross-modules install/flush-data
			'activity-jobboardwp-job-filled' => 1, // Activity cross-modules install/flush-data
			'job_show_pm_button'             => 0, // Private Messages cross-modules install/flush-data
			'job_apply_only_verified'        => 0, // Verified Users cross-module install/flush-data
		);

		// Real-time Notifications cross-modules install/flush-data
		$notification_types_templates = array(
			'jb_job_approved' => __( 'Your <a href="{job_uri}">job</a> is now approved.', 'ultimate-member' ),
			'jb_job_expired'  => __( 'Your <a href="{job_uri}">job</a> is now expired.', 'ultimate-member' ),
		);

		foreach ( $notification_types_templates as $k => $template ) {
			$this->settings_defaults[ 'log_' . $k ] = 1;
			$this->settings_defaults[ 'log_' . $k . '_template' ] = $template;
		}

		$this->roles_meta = array(
			'jb_employer' => array(
				'_um_can_access_wpadmin'         => 0,
				'_um_can_not_see_adminbar'       => 1,
				'_um_can_edit_everyone'          => 0,
				'_um_can_delete_everyone'        => 0,
				'_um_can_edit_profile'           => 1,
				'_um_can_delete_profile'         => 1,
				'_um_after_login'                => 'redirect_profile',
				'_um_after_logout'               => 'redirect_home',
				'_um_default_homepage'           => 1,
				'_um_can_view_all'               => 1,
				'_um_can_make_private_profile'   => 0,
				'_um_can_access_private_profile' => 0,
				'_um_status'                     => 'approved',
				'_um_auto_approve_act'           => 'redirect_profile',
			),
		);
	}

	/**
	 * Set default UM role settings for existed ForumWP roles
	 *
	 * @since 3.0
	 */
	public function set_default_roles_meta() {
		foreach ( $this->roles_meta as $role => $meta ) {
			add_option( "um_role_{$role}_meta", $meta );
		}
	}

	/**
	 *
	 */
	public function start() {
		UM()->options()->set_defaults( $this->settings_defaults );
		$this->set_default_roles_meta();
	}
}
