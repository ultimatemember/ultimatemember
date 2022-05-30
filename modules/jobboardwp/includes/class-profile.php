<?php
namespace umm\jobboardwp\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Profile
 *
 * @package umm\jobboardwp\includes
 */
class Profile {


	/**
	 * Profile constructor.
	 */
	function __construct() {
		add_filter( 'um_profile_tabs', array( $this, 'add_profile_tab' ), 802 );

		if ( UM()->is_request( 'frontend' ) ) {
			add_filter( 'um_user_profile_tabs', array( $this, 'check_profile_tab_privacy' ), 1000, 1 );
			add_action( 'um_profile_content_jobboardwp_default', array( &$this, 'profile_tab_content' ), 10, 1 );
		}
	}


	/**
	 * Add profile tab
	 *
	 * @param array $tabs
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	function add_profile_tab( $tabs ) {
		$tabs['jobboardwp'] = array(
			'name' => __( 'Jobs', 'ultimate-member' ),
			'icon' => 'far fa-list-alt',
		);

		return $tabs;
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
		if ( version_compare( get_bloginfo( 'version' ),'5.4', '<' ) ) {
			echo do_shortcode( '[jb_jobs employer-id="' . um_profile_id() . '" hide-search="1" hide-location-search="1" hide-filters="1" hide-job-types="1" /]' );
		} else {
			echo apply_shortcodes( '[jb_jobs employer-id="' . um_profile_id() . '" hide-search="1" hide-location-search="1" hide-filters="1" hide-job-types="1" /]' );
		}
	}
}
