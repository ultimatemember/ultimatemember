<?php
namespace umm\jobboardwp\includes\frontend;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Profile
 *
 * @package umm\jobboardwp\includes\frontend
 */
class Profile {


	/**
	 * Profile constructor.
	 */
	function __construct() {
		add_filter( 'um_user_profile_tabs', array( $this, 'check_profile_tab_privacy' ), 1000, 1 );
		add_action( 'um_profile_content_jobboardwp_default', array( &$this, 'profile_tab_content' ), 10, 1 );
	}


	/**
	 * Add tabs based on user
	 *
	 * @param array $tabs
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	function check_profile_tab_privacy( $tabs ) {
		if ( empty( $tabs['jobboardwp'] ) ) {
			return $tabs;
		}

		$user_id = um_user( 'ID' );
		if ( ! $user_id ) {
			return $tabs;
		}

		if ( um_user( 'disable_jobs_tab' ) ) {
			unset( $tabs['jobboardwp'] );
			return $tabs;
		}

		return $tabs;
	}


	/**
	 * @param array $args
	 *
	 * @since 1.0
	 */
	function profile_tab_content( $args ) {
		echo apply_shortcodes( '[jb_jobs employer-id="' . um_profile_id() . '" hide-search="1" hide-location-search="1" hide-filters="1" hide-job-types="1" /]' );
	}
}
