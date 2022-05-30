<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Enqueue
 *
 * @package umm\member_directory\includes\admin
 */
class Enqueue {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'load-edit.php', array( &$this, 'posts_page' ) );
		add_action( 'load-post-new.php', array( &$this, 'post_page' ) );
		add_action( 'load-post.php', array( &$this, 'post_page' ) );
		// after register block category
		add_action( 'enqueue_block_editor_assets', array( &$this, 'block_editor' ), 11 );
	}


	/**
	 * @since 3.0
	 */
	function posts_page() {
		if ( isset( $_GET['post_type'] ) && 'um_directory' === sanitize_key( $_GET['post_type'] ) ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'directories_page_scripts' ) );
		}
	}


	/**
	 * @since 3.0
	 */
	function post_page() {
		if ( ( isset( $_GET['post_type'] ) && 'um_directory' === sanitize_key( $_GET['post_type'] ) ) ||
		           ( isset( $_GET['post'] ) && 'um_directory' === get_post_type( absint( $_GET['post'] ) ) ) ) {
			add_action( 'admin_enqueue_scripts', array( &$this, 'directory_page_scripts' ) );
		}
	}


	/**
	 * @since 3.0
	 */
	function directories_page_scripts() {
		$data = UM()->modules()->get_data( 'member-directory' );

		wp_register_style( 'um_admin_directories-screen', $data['url'] . 'assets/css/admin-directories-screen' . UM()->admin()->enqueue()->suffix . '.css', array(), UM_VERSION );
		wp_enqueue_style( 'um_admin_directories-screen' );
	}


	/**
	 * @since 3.0
	 */
	function directory_page_scripts() {

	}


	/**
	 *
	 */
	function block_editor() {
		// Disable Gutenberg scripts to avoid the conflicts
		$disable_script = apply_filters( 'um_disable_blocks_script', false );
		if ( $disable_script ) {
			return;
		}

		$data = UM()->modules()->get_data( 'member-directory' );

		$enable_blocks = UM()->options()->get( 'enable_blocks' );
		if ( ! empty( $enable_blocks ) ) {
			wp_register_script( 'um_admin_blocks_member_directory_shortcode', $data['url'] . 'assets/js/blocks/blocks-shortcode' . UM()->admin()->enqueue()->suffix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components' ), UM_VERSION, true );
			wp_set_script_translations( 'um_admin_blocks_member_directory_shortcode', 'ultimate-member' );

			wp_enqueue_script( 'um_admin_blocks_member_directory_shortcode' );

			/**
			 * Create gutenberg blocks
			 */
			register_block_type( 'um-block/um-member-directories', array(
				'editor_script' => 'um_admin_blocks_member_directory_shortcode',
			) );
		}
	}
}
