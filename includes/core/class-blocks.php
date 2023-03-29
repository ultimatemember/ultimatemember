<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Blocks' ) ) {


	/**
	 * Class Blocks
	 * @package um\core
	 */
	class Blocks {


		/**
		 * Access constructor.
		 */
		public function __construct() {
			add_action( 'init', array( &$this, 'block_editor_render' ), 11 );
		}


		public function block_editor_render() {
			//disable Gutenberg scripts to avoid the conflicts
			$disable_script = apply_filters( 'um_disable_blocks_script', false );
			if ( $disable_script ) {
				return;
			}

			$enable_blocks = UM()->options()->get( 'enable_blocks' );
			if ( empty( $enable_blocks ) ) {
				return;
			}

			$blocks = array(
				'um-block/um-member-directories' => array(
					'render_callback' => array( $this, 'um_member_directories_render' ),
					'attributes'      => array(
						'member_id' => array(
							'type' => 'string',
						),
					),
				),
			);

			foreach ( $blocks as $k => $block_data ) {
				$block_type = str_replace( 'um-block/', '', $k );
				register_block_type_from_metadata( um_path . 'includes/blocks/' . $block_type, $block_data );
			}
		}


		public function um_member_directories_render( $atts ) {
			$shortcode = '[ultimatemember';

			if ( isset( $atts['member_id'] ) && '' !== $atts['member_id'] ) {
				$shortcode .= ' form_id="' . $atts['member_id'] . '"';
			}

			$shortcode .= ']';

			return apply_shortcodes( $shortcode );
		}
	}
}
