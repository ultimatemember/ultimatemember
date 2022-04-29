<?php
namespace umm\jobboardwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Private_Messages
 *
 * @package umm\jobboardwp\includes\cross_modules
 */
class Private_Messages {


	/**
	 * Private_Messages constructor.
	 */
	function __construct() {
		add_filter( 'um_messaging_settings_fields', array( &$this, 'add_messaging_settings' ), 10, 1 );
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );
		add_action( 'jb_after_job_apply_block', array( &$this, 'add_private_message_button' ), 10, 1 );
	}


	/**
	 * @param array $settings_fields
	 *
	 * @return array
	 */
	function add_messaging_settings( $settings_fields ) {
		$settings_fields[] = array(
			'id'          => 'job_show_pm_button',
			'type'        => 'checkbox',
			'label'       => __( 'Show messages button in individual job post', 'ultimate-member' ),
			'description' => __( 'Start private messaging with a job author.', 'ultimate-member' ),
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
				'job_show_pm_button' => array(
					'sanitize' => 'bool',
				),
			)
		);

		return $settings_map;
	}


	/**
	 * @param int $job_id
	 */
	public function add_private_message_button( $job_id ) {
		if ( ! UM()->options()->get( 'job_show_pm_button' ) ) {
			return;
		}

		$job = get_post( $job_id );

		if ( empty( $job ) || is_wp_error( $job ) ) {
			return;
		}

		if ( is_user_logged_in() && get_current_user_id() === (int) $job->post_author ) {
			return;
		}

		if ( version_compare( get_bloginfo( 'version' ), '5.4', '<' ) ) {
			echo do_shortcode( '[ultimatemember_message_button user_id="' . $job->post_author . '"]' );
		} else {
			echo apply_shortcodes( '[ultimatemember_message_button user_id="' . $job->post_author . '"]' );
		}
	}
}
