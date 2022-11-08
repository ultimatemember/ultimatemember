<?php
namespace umm\member_directory\includes\common;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Pages
 *
 * @package umm\member_directory\includes\common
 */
class Pages {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_filter( 'um_predefined_pages', array( &$this, 'add_predefined_pages' ), 10, 1 );
	}


	/**
	 * @param array $pages
	 *
	 * @return array
	 */
	function add_predefined_pages( $pages ) {
		$core_directories = get_option( 'um_core_directories', array() );

		$setup_shortcodes = array_merge(
			array(
				'members' => '',
			),
			$core_directories
		);

		$pages['members'] = array(
			'title'   => __( 'Members', 'ultimate-member' ),
			'content' => ! empty( $setup_shortcodes['members'] ) ? '[ultimatemember_directory id="' . $setup_shortcodes['members'] . '"]' : '',
		);

		return $pages;
	}
}
