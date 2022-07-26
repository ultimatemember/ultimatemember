<?php
namespace umm\member_directory;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Install
 *
 * @package umm\member_directory
 */
class Install {


	/**
	 * @var array Default module settings
	 */
	var $settings_defaults;


	/**
	 * Install constructor.
	 */
	public function __construct() {
		//settings defaults
		$this->settings_defaults = array(
			'account_hide_in_directory'         => 1,
			'account_hide_in_directory_default' => 'No',
			'member_directory_own_table'        => 0,
		);
	}


	/**
	 * Create first install member directory
	 */
	public function create_member_directory() {
		$form = array(
			'post_type'   => 'um_directory',
			'post_title'  => __( 'Members', 'ultimate-member' ),
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'meta_input'  => UM()->module( 'member-directory' )->config()->get( 'default_member_directory_meta' ),
		);

		$form_id = wp_insert_post( $form );
		if ( is_wp_error( $form_id ) ) {
			return;
		}

		update_option( 'um_core_directories', array( $form_id ) );
	}


	/**
	 *
	 */
	function start() {
		UM()->options()->set_defaults( $this->settings_defaults );

		if ( ! UM()->modules()->is_first_installed( 'member-directory' ) ) {
			$this->create_member_directory();
		}
	}
}
