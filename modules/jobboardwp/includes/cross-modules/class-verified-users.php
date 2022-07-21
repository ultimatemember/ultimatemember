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
	public function __construct() {
		add_action( 'jb_job_submission_after_create_account', array( &$this, 'maybe_verify' ), 11, 1 );
		add_filter( 'um_verified_users_settings_fields', array( &$this, 'add_verified_users_settings' ), 10, 1 );
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );
		add_filter( 'jb_can_applied_job', array( &$this, 'lock_for_unverified' ), 10, 1 );
	}


	/**
	 * Maybe auto-verify user after registration on posting job
	 * based on UM role settings
	 *
	 * @param $user_id
	 */
	public function maybe_verify( $user_id ) {
		UM()->module( 'verified-users' )->common()->user()->maybe_verify_after_registration( $user_id );
	}


	/**
	 * @param array $settings_fields
	 *
	 * @return array
	 */
	public function add_verified_users_settings( $settings_fields ) {
		$settings_fields[] = array(
			'id'          => 'job_apply_only_verified',
			'type'        => 'checkbox',
			'label'       => __( 'Only verified users can apply for jobs', 'ultimate-member' ),
			'description' => __( 'Unverified users cannot apply the jobs.', 'ultimate-member' ),
		);

		return $settings_fields;
	}


	/**
	 * @param array $settings_map
	 *
	 * @return array
	 */
	public function add_settings_sanitize( $settings_map ) {
		$settings_map = array_merge(
			$settings_map,
			array(
				'job_apply_only_verified' => array(
					'sanitize' => 'bool',
				),
			)
		);

		return $settings_map;
	}


	/**
	 * @param bool $can_applied
	 *
	 * @return bool
	 */
	public function lock_for_unverified( $can_applied ) {
		if ( ! UM()->options()->get( 'job_apply_only_verified' ) ) {
			return $can_applied;
		}

		if ( ! is_user_logged_in() || ( is_user_logged_in() && ! UM()->module( 'verified-users' )->common()->user()->is_verified( get_current_user_id() ) ) ) {
			$can_applied = false;
		}

		return $can_applied;
	}
}
