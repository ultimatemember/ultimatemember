<?php
namespace um\core;

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
		 * Blocks constructor.
		 */
		public function __construct() {
			add_action( 'init', array( &$this, 'block_editor_render' ), 11 );
			add_filter( 'block_type_metadata_settings', array( &$this, 'block_type_metadata_settings' ), 9999, 2 );
			add_filter( 'register_block_type_args', array( &$this, 'register_block_type_args' ), 9999, 2 );
		}

		/**
		 * Is the block content restriction enabled in the settings.
		 *
		 * @since 2.14.4
		 *
		 * @return bool
		 */
		public function is_restriction_enabled() {
			return ! empty( UM()->options()->get( 'restricted_blocks' ) );
		}

		/**
		 * Attributes used for the block content restriction.
		 *
		 * They have to be registered in the block type on the server too. The block renderer
		 * REST endpoint rejects unknown attributes as invalid parameters.
		 *
		 * @since 2.14.4
		 *
		 * @return array
		 */
		public function get_restriction_attributes() {
			return array(
				'um_is_restrict'     => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'um_who_access'      => array(
					'type'    => 'string',
					'default' => '0',
				),
				'um_roles_access'    => array(
					'type'    => 'array',
					'default' => array(),
					'items'   => array( 'type' => 'string' ),
				),
				'um_message_type'    => array(
					'type'    => 'string',
					'default' => '0',
				),
				'um_message_content' => array(
					'type'    => 'string',
					'default' => '',
				),
			);
		}

		/**
		 * Add the restriction attributes that are not defined in the block type yet.
		 *
		 * @since 2.14.4
		 *
		 * @param array $attributes Block type attributes.
		 *
		 * @return array
		 */
		public function add_restriction_attributes( $attributes ) {
			foreach ( $this->get_restriction_attributes() as $attribute_key => $attribute_data ) {
				if ( empty( $attributes[ $attribute_key ] ) ) {
					$attributes[ $attribute_key ] = $attribute_data;
				}
			}

			return $attributes;
		}

		/**
		 * Add attribute types if restricted blocks is active.
		 *
		 * @param array $settings Array of determined settings for registering a block type.
		 *
		 * @return array
		 */
		public function block_type_metadata_settings( $settings ) {
			if ( ! $this->is_restriction_enabled() ) {
				return $settings;
			}

			if ( empty( $settings['attributes'] ) || ! is_array( $settings['attributes'] ) ) {
				$settings['attributes'] = array();
			}

			$settings['attributes'] = $this->add_restriction_attributes( $settings['attributes'] );

			return $settings;
		}

		/**
		 * Add attribute types for blocks registered with plain arguments.
		 *
		 * Blocks registered via `register_block_type()` with an arguments array are not
		 * processed by `block_type_metadata_settings`. Their REST schema has to get the
		 * attributes here, otherwise the block renderer endpoint rejects them.
		 *
		 * @since 2.14.4
		 *
		 * @param array $args Array of arguments for registering a block type.
		 *
		 * @return array
		 */
		public function register_block_type_args( $args ) {
			if ( ! $this->is_restriction_enabled() ) {
				return $args;
			}

			if ( ! isset( $args['attributes'] ) || ! is_array( $args['attributes'] ) ) {
				$args['attributes'] = array();
			}

			$args['attributes'] = $this->add_restriction_attributes( $args['attributes'] );

			return $args;
		}

		/**
		 * Register UM Blocks.
		 *
		 * @uses register_block_type_from_metadata()
		 * @uses wp_register_block_metadata_collection()
		 */
		public function block_editor_render() {
			/**
			 * Filters the variable to disable adding UM Blocks to Gutenberg editor.
			 *
			 * Note: It's "false" by default. To disable Gutenberg scripts to avoid the conflicts set it to "true"
			 *
			 * @since 2.6.3
			 * @hook um_disable_blocks_script
			 *
			 * @param {bool} $disable_script Disabling block scripts variable.
			 *
			 * @return {bool} It's true for disabling block scripts.
			 *
			 * @example <caption>Disable block scripts.</caption>
			 * function my_custom_um_disable_blocks_script( $disable_script ) {
			 *     $disable_script = true;
			 *     return $disable_script;
			 * }
			 * add_filter( 'um_disable_blocks_script', 'my_custom_um_disable_blocks_script', 10, 1 );
			 */
			$disable_script = apply_filters( 'um_disable_blocks_script', false );
			if ( $disable_script ) {
				return;
			}

			$enable_blocks = UM()->options()->get( 'enable_blocks' );
			if ( empty( $enable_blocks ) ) {
				return;
			}

			if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
				wp_register_block_metadata_collection( UM_PATH . 'includes/blocks', UM_PATH . 'includes/blocks/blocks-manifest.php' );
			}

			$blocks = array(
				'um-block/um-member-directories' => array(
					'render_callback' => array( $this, 'member_directories_render' ),
					'attributes'      => array(
						'member_id' => array(
							'type' => 'string',
						),
					),
				),
				'um-block/um-forms'              => array(
					'render_callback' => array( $this, 'forms_render' ),
					'attributes'      => array(
						'form_id' => array(
							'type' => 'string',
						),
					),
				),
				'um-block/um-password-reset'     => array(
					'render_callback' => array( $this, 'password_reset_render' ),
				),
				'um-block/um-account'            => array(
					'render_callback' => array( $this, 'account_render' ),
					'attributes'      => array(
						'tab' => array(
							'type' => 'string',
						),
					),
				),
			);

			foreach ( $blocks as $k => $block_data ) {
				$block_type = str_replace( 'um-block/', '', $k );
				register_block_type_from_metadata( UM_PATH . 'includes/blocks/' . $block_type, $block_data );
			}
		}

		/**
		 * Renders member directory block.
		 *
		 * @param array $atts Block attributes.
		 *
		 * @return string
		 *
		 * @uses apply_shortcodes()
		 */
		public function member_directories_render( $atts ) {
			$shortcode = '[ultimatemember';

			if ( isset( $atts['member_id'] ) && '' !== $atts['member_id'] ) {
				$shortcode .= ' form_id="' . $atts['member_id'] . '"';
			}

			$shortcode .= ']';

			return apply_shortcodes( $shortcode );
		}

		/**
		 * Renders UM Form block.
		 *
		 * @param array $atts Block attributes.
		 *
		 * @return string
		 *
		 * @uses apply_shortcodes()
		 */
		public function forms_render( $atts ) {
			if ( isset( $atts['form_id'] ) && '' !== $atts['form_id'] ) {
				if ( um_is_core_page( 'account' ) ) {
					if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
						return '<div class="um-block-notice">' . esc_html__( 'This block cannot be used on this page', 'ultimate-member' ) . '</div>';
					} else {
						return '';
					}
				}
			}

			$shortcode = '[ultimatemember is_block="1"';

			if ( isset( $atts['form_id'] ) && '' !== $atts['form_id'] ) {
				$shortcode .= ' form_id="' . $atts['form_id'] . '"';
			}

			$shortcode .= ']';

			return apply_shortcodes( $shortcode );
		}

		/**
		 * Renders UM Reset Password form block.
		 *
		 * @return string
		 *
		 * @uses apply_shortcodes()
		 */
		public function password_reset_render() {
			$shortcode = '[ultimatemember_password]';

			return apply_shortcodes( $shortcode );
		}

		/**
		 * Renders UM Account block.
		 *
		 * @param array $atts Block attributes.
		 *
		 * @return string
		 *
		 * @uses apply_shortcodes()
		 */
		public function account_render( $atts ) {
			if ( um_is_core_page( 'user' ) ) {
				if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
					return '<div class="um-block-notice">' . esc_html__( 'This block cannot be used on this page', 'ultimate-member' ) . '</div>';
				} else {
					return '';
				}
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
