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
			add_action( 'wp_ajax_um_fields_groups_get_options_preset', array( &$this, 'get_options_preset' ) );
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

		public function get_options_preset() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator.', 'ultimate-member' ) );
			}

			if ( empty( $_POST['preset'] ) ) {
				wp_send_json_error( __( 'Wrong data.', 'ultimate-member' ) );
			}

			$preset = sanitize_key( $_POST['preset'] );

			$options = array();
			switch ( $preset ) {
				case 'countries':
					$options = apply_filters( 'um_countries', include wp_normalize_path( UM_PATH . 'i18n/countries.php' ) );
					break;
				case 'languages':
					$options = apply_filters( 'um_languages', include wp_normalize_path( UM_PATH . 'i18n/languages.php' ) );
					break;
				case 'states':
					$options = apply_filters( 'um_states', include wp_normalize_path( UM_PATH . 'i18n/states.php' ) );
					break;
				case 'months':
					$options = array(
						'january'   => __( 'January', 'ultimate-member' ),
						'february'  => __( 'February', 'ultimate-member' ),
						'march'     => __( 'March', 'ultimate-member' ),
						'april'     => __( 'April', 'ultimate-member' ),
						'may'       => __( 'May', 'ultimate-member' ),
						'june'      => __( 'June', 'ultimate-member' ),
						'july'      => __( 'July', 'ultimate-member' ),
						'august'    => __( 'August', 'ultimate-member' ),
						'september' => __( 'September', 'ultimate-member' ),
						'october'   => __( 'October', 'ultimate-member' ),
						'november'  => __( 'November', 'ultimate-member' ),
						'december'  => __( 'December', 'ultimate-member' ),
					);
					$options = apply_filters( 'um_months', $options );
					break;
				case 'days':
					$options = array(
						'sunday'    => __( 'Sunday', 'ultimate-member' ),
						'monday'    => __( 'Monday', 'ultimate-member' ),
						'tuesday'   => __( 'Tuesday', 'ultimate-member' ),
						'wednesday' => __( 'Wednesday', 'ultimate-member' ),
						'thursday'  => __( 'Thursday', 'ultimate-member' ),
						'friday'    => __( 'Friday', 'ultimate-member' ),
						'saturday'  => __( 'Saturday', 'ultimate-member' ),
					);
					$options = apply_filters( 'um_days', $options );
					break;
				default:
					$options = apply_filters( "um_{$preset}", $options );
					break;
			}

			if ( empty( $options ) ) {
				wp_send_json_error( __( 'Wrong preset. There aren\'t any options.', 'ultimate-member' ) );
			}

			wp_send_json_success( array( 'options' => $options ) );
		}
	}
}
