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
	}


	/**
	 *
	 */
	function add_submenu() {
		add_submenu_page(
			UM()->admin()->menu()->slug,
			__( 'Member Directories', 'ultimate-member' ),
			__( 'Member Directories', 'ultimate-member' ),
			'manage_options',
			'edit.php?post_type=um_directory'
		);
	}
}
