<?php
namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\admin\core\Admin_Ajax_Hooks' ) ) {


	/**
	 * Class Admin_Ajax_Hooks
	 * @package um\admin\core
	 */
	class Admin_Ajax_Hooks {


		/**
		 * Admin_Columns constructor.
		 */
		function __construct() {
			add_action( 'wp_ajax_um_do_ajax_action', array( UM()->fields(), 'do_ajax_action' ) );
			add_action( 'wp_ajax_um_update_builder', array( UM()->builder(), 'update_builder' ) );
		}

	}
}