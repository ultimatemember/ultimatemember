<?php
namespace um\core;


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\core\Multisite' ) ) {


	/**
	 * Class Multisite
	 *
	 * @package um\core
	 */
	class Multisite {


		/**
		 * Multisite constructor.
		 */
		function __construct() {
			add_action( 'wpmu_new_blog', array( &$this, 'create_new_blog_old_wp' ), 10, 1 );
			add_action( 'wp_insert_site', array( &$this, 'create_new_blog' ), 10, 1 );
		}


		/**
		 * Make default UM installation at the new blog if UM is active for network
		 *
		 * is deprecated in WP 5.1
		 *
		 * @param $blog_id
		 */
		function create_new_blog_old_wp( $blog_id ) {

			switch_to_blog( $blog_id );
			UM()->single_site_activation();
			restore_current_blog();

		}


		/**
		 * Make default UM installation at the new blog if UM is active for network
		 * works since 5.1 WP version
		 *
		 * @param \WP_Site $blog
		 */
		function create_new_blog( $blog ) {

			switch_to_blog( $blog->blog_id );
			UM()->single_site_activation();
			restore_current_blog();

		}


	}

}