<?php
namespace um\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Multisite' ) ) {

	/**
	 * Class Multisite
	 * @package um\core
	 */
	class Multisite {

		/**
		 * Multisite constructor.
		 */
		function __construct() {

			add_action( 'wpmu_new_blog', array( &$this, 'create_new_blog_old_wp' ) );
			add_action( 'wp_insert_site', array( &$this, 'create_new_blog' ) );

		}


		/**
		 * @param $blog_id
		 */
		function create_new_blog_old_wp ( $blog_id ) {

			switch_to_blog( $blog_id );
			UM()->single_site_activation();
			restore_current_blog();

		}

		/**
		 * @param $blog
		 */
		function create_new_blog ( $blog ) {

			switch_to_blog( $blog->blog_id );
			UM()->single_site_activation();
			restore_current_blog();

		}


	}

}