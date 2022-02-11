<?php
namespace umm\jobboardwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Activity
 *
 * @package umm\jobboardwp\includes\cross_modules
 */
class Activity {


	/**
	 * Activity constructor.
	 */
	function __construct() {
		add_filter( 'um_activity_global_actions', array( &$this, 'social_activity_action' ), 10, 1 );
		add_action( 'jb_job_submission_after_create_account', array( &$this, 'social_activity_new_user' ), 10, 1 );
	}

	/**
	 * Add new activity action
	 *
	 * @param array $actions
	 *
	 * @return array
	 *
	 * @since 1.0
	 */
	function social_activity_action( $actions ) {
		$actions['new-jobboardwp-job']    = __( 'New job', 'ultimate-member' );
		$actions['jobboardwp-job-filled'] = __( 'Job is filled', 'ultimate-member' );
		return $actions;
	}


	/**
	 * Add new user activity post
	 *
	 * @param array $user_id
	 */
	function social_activity_new_user( $user_id ) {
		do_action( 'um_after_user_is_approved', $user_id );
	}
}
