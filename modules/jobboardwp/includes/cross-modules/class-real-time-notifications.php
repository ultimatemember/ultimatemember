<?php
namespace umm\jobboardwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Real_Time_Notifications
 *
 * @package umm\jobboardwp\includes\cross_modules
 */
class Real_Time_Notifications {


	/**
	 * Real_Time_Notifications constructor.
	 */
	public function __construct() {
		add_filter( 'um_notifications_core_log_types', array( &$this, 'add_notifications' ), 300, 1 );
		add_action( 'jb_job_is_approved', array( &$this, 'after_job_is_approved' ), 10, 2 );
		add_action( 'jb_job_is_expired', array( &$this, 'after_job_is_expired' ), 10, 1 );
	}


	/**
	 * Adds a notification type
	 *
	 * @param array $logs
	 *
	 * @return array
	 */
	public function add_notifications( $logs ) {
		$logs['jb_job_approved'] = array(
			'title'        => __( 'Your job is approved', 'ultimate-member' ),
			'account_desc' => __( 'When your job gets approved status', 'ultimate-member' ),
			'content'      => __( 'Your <a href="{job_uri}">job</a> is now approved.', 'ultimate-member' ),
			'placeholders' => array( 'job_uri' ),
		);
		$logs['jb_job_expired'] = array(
			'title'        => __( 'Your job is expired', 'ultimate-member' ),
			'account_desc' => __( 'When your job gets expired status', 'ultimate-member' ),
			'content'      => __( 'Your <a href="{job_uri}">job</a> is now expired.', 'ultimate-member' ),
			'placeholders' => array( 'job_uri' ),
		);

		return $logs;
	}


	/**
	 * Send a web notification after user's job is approved
	 *
	 * @param int $job_id
	 * @param \WP_Post $job
	 */
	public function after_job_is_approved( $job_id, $job ) {
		$user_id = $job->post_author;
		um_fetch_user( $user_id );

		$vars['photo'] = um_get_avatar_url( get_avatar( $user_id, 40 ) );
		$vars['member'] = um_user( 'display_name' );
		$url = um_user_profile_url();
		$vars['notification_uri'] = $url;
		$vars['job_uri'] = get_permalink( $job );

		UM()->Notifications_API()->api()->store_notification( $user_id, 'jb_job_approved', $vars );
	}


	/**
	 * Send a web notification after user's job is expired
	 *
	 * @param int $job_id
	 */
	public function after_job_is_expired( $job_id ) {
		$job = get_post( $job_id );

		if ( ! empty( $job ) && ! is_wp_error( $job ) ) {
			$user_id = $job->post_author;
			um_fetch_user( $user_id );

			$vars['photo'] = um_get_avatar_url( get_avatar( $user_id, 40 ) );
			$vars['member'] = um_user( 'display_name' );
			$url = um_user_profile_url();
			$vars['notification_uri'] = $url;
			$vars['job_uri'] = get_permalink( $job );

			UM()->Notifications_API()->api()->store_notification( $user_id, 'jb_job_expired', $vars );
		}
	}
}
