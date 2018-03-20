<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Support multisite
 *
 * @param $dir
 *
 * @return string
 */
function um_multisite_urls_support( $dir ) {

	if ( is_multisite() ) { // Need to the work

		if ( get_current_blog_id() == '1' ) {
			return $dir;
		}

		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_multisite_upload_sites_directory
		 * @description Change multisite uploads directory
		 * @input_vars
		 * [{"var":"$sites_dir","type":"string","desc":"Upload sites directory"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_multisite_upload_sites_directory', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_multisite_upload_sites_directory', 'my_multisite_upload_sites_directory', 10, 1 );
		 * function my_multisite_upload_sites_directory( $sites_dir ) {
		 *     // your code here
		 *     return $sites_dir;
		 * }
		 * ?>
		 */
		$sites_dir = apply_filters('um_multisite_upload_sites_directory', 'sites/' );
		$split = explode( $sites_dir, $dir );
		/**
		 * UM hook
		 *
		 * @type filter
		 * @title um_multisite_upload_directory
		 * @description Change multisite UM uploads directory
		 * @input_vars
		 * [{"var":"$um_dir","type":"string","desc":"Upload UM directory"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage
		 * <?php add_filter( 'um_multisite_upload_directory', 'function_name', 10, 1 ); ?>
		 * @example
		 * <?php
		 * add_filter( 'um_multisite_upload_directory', 'my_multisite_upload_directory', 10, 1 );
		 * function my_multisite_upload_directory( $um_dir ) {
		 *     // your code here
		 *     return $um_dir;
		 * }
		 * ?>
		 */
		$um_dir = apply_filters('um_multisite_upload_directory','ultimatemember/');
		$dir = $split[0] . $um_dir;

	}

	return $dir;
}
add_filter( 'um_upload_basedir_filter', 'um_multisite_urls_support', 99 );
add_filter( 'um_upload_baseurl_filter', 'um_multisite_urls_support', 99 );