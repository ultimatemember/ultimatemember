<?php
namespace umm\jobboardwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Verified_Users
 *
 * @package umm\jobboardwp\includes\cross_modules
 */
class Verified_Users {


	/**
	 * Verified_Users constructor.
	 */
	function __construct() {
		add_action( 'jb_job_submission_after_create_account', array( &$this, 'maybe_verify' ), 11, 1 );
		add_filter( 'um_verified_users_settings_fields', array( &$this, 'add_verified_users_settings' ), 10, 1 );
		add_filter( 'jb_can_applied_job', array( &$this, 'lock_for_unverified' ), 10, 1 );
	}

	/**
	 * Maybe auto-verify user after registration on posting job
	 * based on UM role settings
	 *
	 * @param $user_id
	 */
	function maybe_verify( $user_id ) {
		if ( function_exists( 'um_verified_registration_complete' ) ) {
			um_verified_registration_complete( $user_id );
		}
	}


	/**
	 * @param array $settings_fields
	 *
	 * @return array
	 */
	function add_verified_users_settings( $settings_fields ) {
		$settings_fields[] = array(
			'id'          => 'job_apply_only_verified',
			'type'        => 'checkbox',
			'label'       => __( 'Only verified users can apply for jobs', 'ultimate-member' ),
			'description' => __( 'Unverified users cannot apply the jobs.', 'ultimate-member' ),
		);

		return $settings_fields;
	}


	/**
	 * @param bool $can_applied
	 *
	 * @return bool
	 */
	function lock_for_unverified( $can_applied ) {
		if ( ! UM()->options()->get( 'job_apply_only_verified' ) ) {
			return $can_applied;
		}

		if ( ! is_user_logged_in() || ( is_user_logged_in() && ! UM()->Verified_Users_API()->api()->is_verified( get_current_user_id() ) ) ) {
			$can_applied = false;
		}

		return $can_applied;
	}
}
