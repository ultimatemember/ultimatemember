<?php
namespace umm\forumwp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Install
 *
 * @package umm\forumwp
 */
class Install {

	/**
	 * Default module settings.
	 *
	 * @var array
	 */
	public $settings_defaults = array();

	/**
	 * @var array
	 */
	public $roles_meta = array();

	/**
	 * Install constructor.
	 */
	public function __construct() {
		$this->settings_defaults = array(
			// Activity cross-modules install/flush-data
			'activity-new-forumwp-topic' => 1,
			'activity-new-forumwp-reply' => 1,
		);

		// Real-time Notifications cross-modules install/flush-data
		$notification_types_templates = array(
			'fmwp_mention'   => __( '<strong>{member}</strong> just mentioned you <a href="{post_url}" target="_blank">here</a>.', 'ultimate-member' ),
			'fmwp_new_reply' => __( '<strong>{member}</strong> has <strong><a href="{post_url}" target="_blank">replied</a></strong> to a topic or forum on which you are subscribed.', 'ultimate-member' ),
			'fmwp_new_topic' => __( '<strong>{member}</strong> has <strong>created a new <a href="{post_url}" target="_blank">topic</a></strong> in a forum on which you are subscribed.', 'ultimate-member' ),
		);

		foreach ( $notification_types_templates as $k => $template ) {
			$this->settings_defaults[ 'log_' . $k ] = 1;
			$this->settings_defaults[ 'log_' . $k . '_template' ] = $template;
		}

		$this->roles_meta = array(
			'fmwp_spectator'   => array(
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
			'fmwp_participant' => array(
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
			'fmwp_moderator'   => array(
				'_um_can_access_wpadmin'         => 1,
				'_um_can_not_see_adminbar'       => 0,
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
			'fmwp_manager'     => array(
				'_um_can_access_wpadmin'         => 1,
				'_um_can_not_see_adminbar'       => 0,
				'_um_can_edit_everyone'          => 0,
				'_um_can_delete_everyone'        => 0,
				'_um_can_edit_profile'           => 1,
				'_um_can_delete_profile'         => 1,
				'_um_default_homepage'           => 1,
				'_um_after_login'                => 'redirect_admin',
				'_um_after_logout'               => 'redirect_home',
				'_um_can_view_all'               => 0,
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
