<?php
namespace umm\forumwp;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Install
 *
 * @package umm\forumwp
 */
class Install {


	/**
	 * @var array Default module settings
	 */
	var $settings_defaults;


	/**
	 * Install constructor.
	 */
	function __construct() {
		//settings defaults
		$this->settings_defaults = array(
			'profile_tab_forumwp'         => 1,
			'profile_tab_forumwp_privacy' => 0,
		);

		$notification_types_templates = array(
			'fmwp_mention'   => __( '<strong>{member}</strong> just mentioned you <a href="{post_url}" target="_blank">here</a>.', 'ultimate-member' ),
			'fmwp_new_reply' => __( '<strong>{member}</strong> has <strong><a href="{post_url}" target="_blank">replied</a></strong> to a topic or forum on which you are subscribed.', 'ultimate-member' ),
			'fmwp_new_topic' => __( '<strong>{member}</strong> has <strong>created a new <a href="{post_url}" target="_blank">topic</a></strong> in a forum on which you are subscribed.', 'ultimate-member' ),
		);

		foreach ( $notification_types_templates as $k => $template ) {
			$this->settings_defaults[ 'log_' . $k ] = 1;
			$this->settings_defaults[ 'log_' . $k . '_template' ] = $template;
		}
	}


	/**
	 *
	 */
	function start() {
		UM()->options()->set_defaults( $this->settings_defaults );
	}
}
