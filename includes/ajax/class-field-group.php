<?php
namespace um\ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\ajax\Field_Group' ) ) {

	/**
	 * Class Field_Group
	 *
	 * @package um\ajax
	 */
	class Field_Group {

		/**
		 * Field_Group constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_um_fields_groups_get_settings_form', array( &$this, 'get_field_settings_form' ) );
			add_filter( 'um_admin_render_checkbox_field_html', array( &$this, 'add_reset_rules_button' ), 10, 2 );
			add_filter( 'um_fields_settings', array( &$this, 'change_hidden_settings' ), 10, 2 );
		}

		public function add_reset_rules_button( $html, $field_data ) {
			if ( array_key_exists( 'id', $field_data ) && 'conditional_logic' === $field_data['id'] ) {
				$visibility = '';
				if ( empty( $field_data['value'] ) ) {
					$visibility = ' style="visibility:hidden;"';
				}
				$html = '<div style="display: flex;flex-direction: row;justify-content: space-between; align-items: center;flex-wrap: nowrap;">' . $html .'<input type="button" class="button um-field-row-reset-all-conditions" value="' . __( 'Reset all rules', 'ultimate-member' ) . '"' . $visibility . '/></div>';
			}
			return $html;
		}

		public function change_hidden_settings( $settings, $field_type ) {
			if ( 'hidden' === $field_type ) {
				$settings['conditional']['conditional_action']['options'] = array(
					'show' => __( 'Enable', 'ultimate-member' ),
					'hide' => __( 'Disable', 'ultimate-member' ),
				);
			}
			return $settings;
		}

		public function get_field_settings_form() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator.', 'ultimate-member' ) );
			}

			if ( empty( $_POST['field_id'] ) || empty( $_POST['type'] ) ) {
				wp_send_json_error( __( 'Wrong data.', 'ultimate-member' ) );
			}

			// Avoid duplicates for field row template.
			UM()->admin()->field_group()->is_displayed = true;

			$type = sanitize_key( $_POST['type'] );
			$field_id = sanitize_text_field( $_POST['field_id'] );

			$field_settings_settings = UM()->admin()->field_group()->get_field_settings( $type, $field_id );

			$fields = array();

			foreach ( $field_settings_settings as $tab_key => $settings_fields ) {
				$html = UM()->admin()->field_group()->get_tab_fields_html( $tab_key, array( 'type' => $type, 'index' => $field_id ) );
				if ( ! empty( $html ) ) {
					$fields[ $tab_key ] = $html;
				}
			}

			wp_send_json_success( array( 'fields' => $fields ) );
		}
	}
}
