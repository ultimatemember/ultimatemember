<?php
namespace umm\forumwp\includes\cross_modules;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Profile_Completeness
 *
 * @package umm\forumwp\includes\cross_modules
 */
class Profile_Completeness {


	/**
	 * Profile_Completeness constructor.
	 */
	function __construct() {
		add_filter( 'um_profile_completeness_roles_metabox_fields', array( &$this, 'role_completeness_fields' ), 10, 2 );
		add_filter( 'um_profile_completeness_get_progress_result', array( &$this, 'profile_completeness_get_progress_result' ), 10, 2 );
		add_filter( 'um_profile_completeness_profile_progress_defaults', array( &$this, 'profile_completeness_profile_progress_defaults' ), 10, 1 );
	}

	/**
	 * Adds a ForumWP profile completeness role settings
	 *
	 * @param $fields
	 * @param $role
	 *
	 * @return array
	 */
	function role_completeness_fields( $fields, $role ) {
		$fields[] = array(
			'id'          => '_um_profilec_prevent_forumwp',
			'type'        => 'select',
			'label'       => __( 'Require profile to be complete to create new ForumWP topics/replies?', 'ultimate-member' ),
			'description'     => __( 'Prevent user from adding participating in forum If their profile completion is below the completion threshold set up above?', 'ultimate-member' ),
			'value'       => ! empty( $role['_um_profilec_prevent_forumwp'] ) ? $role['_um_profilec_prevent_forumwp'] : 0,
			'conditional' => array( '_um_profilec', '=', '1' ),
			'options'     => array(
				0 => __( 'No', 'ultimate-member' ),
				1 => __( 'Yes', 'ultimate-member' ),
			),
		);

		return $fields;
	}


	/**
	 * Extends get progress results
	 *
	 * @param array $result
	 * @param array $role_data
	 *
	 * @return array
	 */
	function profile_completeness_get_progress_result( $result, $role_data ) {
		$result['prevent_forumwp'] = ! empty( $role_data['profilec_prevent_forumwp'] ) ? $role_data['profilec_prevent_forumwp'] : 0;
		return $result;
	}


	/**
	 * Extends get progress defaults
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	function profile_completeness_profile_progress_defaults( $defaults ) {
		$defaults['prevent_forumwp'] = 0;
		return $defaults;
	}
}
