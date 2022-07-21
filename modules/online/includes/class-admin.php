<?php
namespace umm\online\includes;


if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class Admin
 *
 * @package umm\online\includes
 */
class Admin {


	/**
	 * Admin constructor.
	 */
	function __construct() {
		add_filter( 'um_settings_structure', array( $this, 'admin_settings' ), 10, 1 );
		add_filter( 'um_settings_map', array( &$this, 'add_settings_sanitize' ), 10, 1 );

		add_action( 'enqueue_block_editor_assets', array( &$this, 'block_editor' ), 11 );
		add_action( 'debug_information', array( &$this, 'debug_information' ), 25, 1 );
	}


	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	function admin_settings( $settings ) {
		$settings['modules']['sections']['online'] = array(
			'title'  => __( 'Online', 'ultimate-member' ),
			'fields' => array(
				array(
					'id'    => 'online_show_stats',
					'type'  => 'checkbox',
					'label' => __( 'Show online stats in member directory', 'ultimate-member' ),
				),
			),
		);

		return $settings;
	}


	/**
	 * @param array $settings_map
	 *
	 * @return array
	 */
	public function add_settings_sanitize( $settings_map ) {
		$settings_map = array_merge(
			$settings_map,
			array(
				'online_show_stats' => array(
					'sanitize' => 'bool',
				),
			)
		);

		return $settings_map;
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


	/**
	 * Add our data to Site Health information.
	 *
	 * @since 3.0
	 *
	 * @param array $info The Site Health information.
	 *
	 * @return array The updated Site Health information.
	 */
	public function debug_information( $info ) {
		$info['ultimate-member-online'] = array(
			'label'       => __( 'Ultimate Member Online', 'ultimate-member' ),
			'description' => __( 'This debug information about Ultimate Member Online module.', 'ultimate-member' ),
			'fields'      => array(
				'um-online_show_stats' => array(
					'label' => __( 'Show online stats in member directory', 'ultimate-member' ),
					'value' => UM()->options()->get('online_show_stats') ? __( 'Yes', 'ultimate-member' ) : __( 'No', 'ultimate-member' ),
				),
			),
		);

		return $info;
	}
}
