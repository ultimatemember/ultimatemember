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
			add_action( 'init', array( &$this, 'block_editor_render' ), 10 );
			add_filter( 'block_type_metadata_settings', array( &$this, 'block_type_metadata_settings' ), 10, 2 );
		}


		public function block_type_metadata_settings( $settings, $args ) {
			if ( empty( $settings['attributes']['um_is_restrict'] ) ) {
				$settings['attributes']['um_is_restrict'] = array(
					'type' => 'boolean',
				);
			}
			if ( empty( $settings['attributes']['um_who_access'] ) ) {
				$settings['attributes']['um_who_access'] = array(
					'type' => 'string',
				);
			}
			if ( empty( $settings['attributes']['um_roles_access'] ) ) {
				$settings['attributes']['um_roles_access'] = array(
					'type' => 'array',
				);
			}
			if ( empty( $settings['attributes']['um_message_type'] ) ) {
				$settings['attributes']['um_message_type'] = array(
					'type' => 'string',
				);
			}
			if ( empty( $settings['attributes']['um_message_content'] ) ) {
				$settings['attributes']['um_message_content'] = array(
					'type' => 'string',
				);
			}

			return $settings;
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
				'um-block/um-forms'              => array(
					'render_callback' => array( $this, 'um_forms_render' ),
					'attributes'      => array(
						'form_id' => array(
							'type' => 'string',
						),
					),
				),
				'um-block/um-password-reset'     => array(
					'render_callback' => array( $this, 'um_password_reset_render' ),
				),
				'um-block/um-account'            => array(
					'render_callback' => array( $this, 'um_account_render' ),
					'attributes'      => array(
						'tab' => array(
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


		public function um_forms_render( $atts ) {
			if ( isset( $atts['form_id'] ) && '' !== $atts['form_id'] ) {
				$mode = get_post_meta( $atts['form_id'], '_um_mode', true );
				if ( 'profile' === $mode && ( um_is_core_page( 'account' ) || um_is_core_page( 'user' ) ) ) {
					return '';
				}
			}
			$shortcode = '[ultimatemember is_block="1"';

			if ( isset( $atts['form_id'] ) && '' !== $atts['form_id'] ) {
				$shortcode .= ' form_id="' . $atts['form_id'] . '"';
			}

			$shortcode .= ']';

			return apply_shortcodes( $shortcode );
		}


		public function um_password_reset_render() {
			$shortcode = '[ultimatemember_password]';

			return apply_shortcodes( $shortcode );
		}


		public function um_account_render( $atts ) {
			if ( um_is_core_page( 'account' ) || um_is_core_page( 'user' ) ) {
				return '';
			}
			$shortcode = '[ultimatemember_account is_block="1"';

			if ( isset( $atts['tab'] ) && 'all' !== $atts['tab'] ) {
				$shortcode .= ' tab="' . $atts['tab'] . '"';
			}

			$shortcode .= ']';

			return apply_shortcodes( $shortcode );
		}
	}
}
