<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Menu
 * @package umm\member_directory\includes\admin
 */
class Menu {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'um_extend_admin_menu', array( &$this, 'add_submenu' ) );
		add_filter( 'um_admin_footer_text_pages', array( &$this, 'add_footer_text_page' ), 10, 1 );
	}


	/**
	 *
	 */
	function add_submenu() {
		add_submenu_page( UM()->admin()->menu()->slug, __( 'Member Directories', 'ultimate-member' ), __( 'Member Directories', 'ultimate-member' ), 'manage_options', 'edit.php?post_type=um_directory', '' );
	}


	/**
	 * @param array $um_pages
	 *
	 * @return array
	 */
	function add_footer_text_page( $um_pages ) {
		$um_pages[] = 'edit-um_directory';
		return $um_pages;
	}
}
