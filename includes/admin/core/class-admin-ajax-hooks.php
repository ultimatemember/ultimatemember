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
			add_action( 'wp_ajax_um_update_order', array( UM()->dragdrop(), 'update_order' ) );
			add_action( 'wp_ajax_um_update_field', array( UM()->builder(), 'update_field' ) );
			add_action( 'wp_ajax_um_dynamic_modal_content', array( UM()->builder(), 'dynamic_modal_content' ) );
			add_action( 'wp_ajax_um_populate_dropdown_options', array( UM()->builder(), 'populate_dropdown_options' ) );
			add_action( 'wp_ajax_um_rated', array( UM()->admin_menu(), 'ultimatemember_rated' ) );
			add_action( 'wp_ajax_um_member_directory_default_filter_settings', array( UM()->member_directory(), 'default_filter_settings' ) );

			add_action( 'wp_ajax_um_same_page_update', array( UM()->admin_settings(), 'same_page_update_ajax' ) );
		}

	}
}
