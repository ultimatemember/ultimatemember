<?php
namespace umm\forumwp\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Profile
 *
 * @package umm\forumwp\includes\common
 */
class Profile {


	/**
	 * Profile constructor.
	 */
	public function __construct() {
		add_filter( 'um_profile_tabs', array( $this, 'add_profile_tab' ), 802 );
	}


	/**
	 * Add profile tab
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_profile_tab( $tabs ) {
		$tabs['forumwp'] = array(
			'name' => __( 'Forums', 'ultimate-member' ),
			'icon' => 'fas fa-comments',
		);

		return $tabs;
	}
}
