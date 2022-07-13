<?php
namespace umm\online\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Enqueue
 *
 * @package umm\online\includes\admin
 */
class Enqueue {


	/**
	 * Enqueue constructor.
	 */
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( &$this, 'block_editor' ), 11 );
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

		$enable_blocks = UM()->options()->get( 'enable_blocks' );
		if ( empty( $enable_blocks ) ) {
			return;
		}

		$module_data = UM()->modules()->get_data( 'online' );
		if ( ! $module_data ) {
			return;
		}

		wp_register_script( 'um_admin_blocks_online_shortcode', $module_data['url'] . 'assets/js/admin/blocks' . UM()->admin()->enqueue()->suffix . '.js', array( 'wp-i18n', 'wp-blocks', 'wp-components' ), UM_VERSION, true );
		wp_set_script_translations( 'um_admin_blocks_online_shortcode', 'ultimate-member' );

		$roles  = UM()->roles()->get_roles();

		wp_localize_script( 'um_admin_blocks_online_shortcode', 'um_online_roles', $roles );

		wp_enqueue_script( 'um_admin_blocks_online_shortcode' );

		/**
		 * Create gutenberg blocks
		 */
		register_block_type(
			'um-block/um-online',
			array(
				'editor_script' => 'um_admin_blocks_online_shortcode',
			)
		);
	}
}
