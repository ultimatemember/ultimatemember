<?php
namespace umm\jobboardwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Notifications
 *
 * @package umm\jobboardwp\includes\cross_modules
 */
class Notifications {


	/**
	 * Notifications constructor.
	 */
	function __construct() {
		add_filter( 'um_notifications_core_log_types', array( &$this, 'add_notifications' ), 300, 1 );
	}


	/**
	 * Adds a notification type
	 *
	 * @param array $logs
	 *
	 * @return array
	 */
	function add_notifications( $logs ) {
		$logs['jb_job_approved'] = array(
			'title'        => __( 'Your job is approved', 'ultimate-member' ),
			'account_desc' => __( 'When your job gets approved status', 'ultimate-member' ),
		);
		$logs['jb_job_expired'] = array(
			'title'        => __( 'Your job is expired', 'ultimate-member' ),
			'account_desc' => __( 'When your job gets expired status', 'ultimate-member' ),
		);
		return $logs;
	}
}
