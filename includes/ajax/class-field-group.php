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

//			add_action( 'wp_ajax_um_fields_groups_save_draft', array( &$this, 'save_draft' ) );
//			add_action( 'wp_ajax_um_fields_groups_check_draft', array( &$this, 'check_draft' ) );
//			add_action( 'wp_ajax_um_fields_groups_flush_draft', array( &$this, 'flush_draft' ) );
//			add_action( 'wp_ajax_um_fields_groups_add_field', array( &$this, 'add_field' ) );
		}

		public function add_reset_rules_button( $html, $field_data ) {
			if ( array_key_exists( 'id', $field_data ) && 'conditional_logic' === $field_data['id'] ) {
				$visibility = '';
				if ( empty( $field_data['value'] ) ) {
					$visibility = ' style="visibility:hidden;"';
				}
				$html = '<div style="display: flex;flex-direction: row;justify-content: space-between; align-items: center;flex-wrap: nowrap;">' . $html .'<input type="button" class="button um-field-groups-field-reset-all-conditions" value="' . __( 'Reset all rules', 'ultimate-member' ) . '"' . $visibility . '/></div>';
			}
			return $html;
		}

		public function get_field_settings_form() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator.', 'ultimate-member' ) );
			}

			if ( empty( $_POST['field_id'] ) || empty( $_POST['type'] ) ) {
				wp_send_json_error( __( 'Wrong data.', 'ultimate-member' ) );
			}

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

		private function sanitize( $data ) {
			$sanitize_map = array(
				'title'       => 'text',
				'description' => 'textarea',
			);

			foreach ( $data as $key => &$value ) {
				switch ( $sanitize_map[ $key ] ) {
					default:
						$value = apply_filters( 'um_groups_fields_sanitize_' . $key, $value );
						break;
					case 'int':
						$value = (int) $value;
						break;
					case 'empty_int':
						$value = ( '' !== $value ) ? (int) $value : '';
						break;
					case 'bool':
						$value = (bool) $value;
						break;
					case 'url':
						if ( is_array( $value ) ) {
							$value = array_map( 'esc_url_raw', $value );
						} else {
							$value = esc_url_raw( $value );
						}
						break;
					case 'text':
						$value = sanitize_text_field( $value );
						break;
					case 'textarea':
						$value = sanitize_textarea_field( $value );
						break;
					case 'wp_kses':
						$value = wp_kses_post( $value );
						break;
					case 'key':
						if ( is_array( $value ) ) {
							$value = array_map( 'sanitize_key', $value );
						} else {
							$value = sanitize_key( $value );
						}
						break;
					case 'absint':
						if ( is_array( $value ) ) {
							$value = array_map( 'absint', $value );
						} else {
							$value = absint( $value );
						}
						break;
					case 'empty_absint':
						if ( is_array( $value ) ) {
							$value = array_map( 'absint', $value );
						} else {
							$value = ( '' !== $value ) ? absint( $value ) : '';
						}
						break;
				}
			}

			return $data;
		}

		private function set_draft_data( $data ) {
			global $wpdb;

			if ( ! empty( $data['id'] ) ) {
				$draft_id = $data['id'];

				$args = array(
					'id' => $data['id'],
				);
				if ( array_key_exists( 'title', $data ) ) {
					$args['title'] = $data['title'];
				}
				if ( array_key_exists( 'description', $data ) ) {
					$args['description'] = $data['description'];
				}
				if ( array_key_exists( 'meta', $data ) ) {
					$args['meta'] = $data['meta'];
				}

				UM()->admin()->field_group()->update( $args );
			} else {
				$args = array(
					'title'       => '',
					'description' => '',
					'status'      => 'draft',
					'meta'        => array(),
				);

				if ( array_key_exists( 'base_id', $data ) ) {
					$base_data = UM()->admin()->field_group()->get_data( $data['base_id'] );
					if ( ! empty( $base_data['title'] ) ) {
						$args['title'] = $base_data['title'];
					}
					if ( ! empty( $base_data['description'] ) ) {
						$args['description'] = $base_data['description'];
					}

					$base_meta_rows = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT meta_key, meta_value
							FROM {$wpdb->prefix}um_field_groups_meta 
							WHERE group_id = %d",
							$data['base_id']
						),
						ARRAY_A
					);
					if ( ! empty( $base_meta_rows ) ) {
						$base_meta    = array_combine( array_column( $base_meta_rows, 'meta_key' ), array_column( $base_meta_rows, 'meta_value' ) );
						$args['meta'] = array_merge( $args['meta'], $base_meta );
					}
				}

				if ( array_key_exists( 'title', $data ) ) {
					$args['title'] = $data['title'];
				}

				if ( array_key_exists( 'description', $data ) ) {
					$args['description'] = $data['description'];
				}

				if ( array_key_exists( 'meta', $data ) ) {
					$args['meta'] = array_merge( $args['meta'], $data['meta'] );
				}

				$draft_id = UM()->admin()->field_group()->create( $args );

				if ( ! empty( $draft_id ) && array_key_exists( 'base_id', $data ) ) {
					$group_fields = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT *
							FROM {$wpdb->prefix}um_fields 
							WHERE group_id = %d",
							$data['base_id']
						),
						ARRAY_A
					);

					if ( ! empty( $group_fields ) ) {
						$new_meta_map = array();
						foreach ( $group_fields as $copy_field ) {
							$wpdb->insert(
								"{$wpdb->prefix}um_fields",
								array(
									'field_key'   => $copy_field['field_key'],
									'group_id'    => $draft_id,
									'title'       => $copy_field['title'],
									'description' => $copy_field['description'],
									'type'        => $copy_field['type'],
								),
								array(
									'%s',
									'%d',
									'%s',
									'%s',
									'%s',
								)
							);
							$new_meta_map[ $copy_field['id'] ] = $wpdb->insert_id;
						}

						$fields_ids        = array_column( $group_fields, 'id' );
						$group_fields_meta = $wpdb->get_results(
							"SELECT *
							FROM {$wpdb->prefix}um_fields_meta 
							WHERE field_id IN('" . implode( "','", $fields_ids ) . "')",
							ARRAY_A
						);

						foreach ( $group_fields_meta as $meta_row ) {
							if ( ! array_key_exists( $meta_row['field_id'], $new_meta_map ) ) {
								continue;
							}

							$wpdb->insert(
								"{$wpdb->prefix}um_fields_meta",
								array(
									'field_id'   => $new_meta_map[ $meta_row['field_id'] ],
									'meta_key'   => $meta_row['meta_key'],
									'meta_value' => $meta_row['meta_value'],
								),
								array(
									'%d',
									'%s',
									'%s',
								)
							);
						}
					}
				}
			}

			return $draft_id;
		}

		public function save_draft() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator.', 'ultimate-member' ) );
			}

			if ( ! empty( $_REQUEST['group_id'] ) ) {
				// edit group draft checking
				$group_id = absint( $_REQUEST['group_id'] );
				if ( empty( $group_id ) ) {
					wp_send_json_error( __( 'Empty group ID.', 'ultimate-member' ) );
				}

				$draft_id = UM()->admin()->field_group()->get_draft_by( $group_id, 'group' );
			} else {
				// add group draft checking
				$user_id  = get_current_user_id();
				$draft_id = UM()->admin()->field_group()->get_draft_by( $user_id, 'user' );
			}

			if ( ! empty( $_REQUEST['draft_id'] ) ) {
				if ( absint( $_REQUEST['draft_id'] ) !== $draft_id ) {
					wp_send_json_error( __( 'Wrong draft ID.', 'ultimate-member' ) );
				}
			}

			$data = $this->sanitize( $_REQUEST['changed_data'] );

			if ( isset( $user_id ) ) {
				$data['meta']['user_id'] = $user_id;
			}
			if ( isset( $group_id ) ) {
				$data['meta']['group_id'] = $group_id;
			}

			if ( false !== $draft_id ) {
				// update draft
				$data['id'] = $draft_id;
				$draft_id = $this->set_draft_data( $data );
				wp_send_json_success( array( 'draft_id' => $draft_id ) );
			} else {
				// create draft
				if ( isset( $group_id ) ) {
					// copy all data if doesn't exist on the first draft creation while edit fields group
					$data['base_id'] = $group_id;
				}

				$draft_id = $this->set_draft_data( $data );
				wp_send_json_success( array( 'draft_id' => $draft_id ) );
			}
		}

		public function check_draft() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator.', 'ultimate-member' ) );
			}

			if ( ! empty( $_REQUEST['group_id'] ) ) {
				// edit group draft checking
				$group_id = absint( $_REQUEST['group_id'] );
				if ( empty( $group_id ) ) {
					wp_send_json_error( __( 'Empty group ID.', 'ultimate-member' ) );
				}

				$draft_id = UM()->admin()->field_group()->get_draft_by( $group_id, 'group' );
			} else {
				// add group draft checking
				$user_id  = get_current_user_id();
				$draft_id = UM()->admin()->field_group()->get_draft_by( $user_id, 'user' );
			}

			if ( false !== $draft_id ) {
				wp_send_json_success( array( 'draft_id' => $draft_id ) );
			} else {
				wp_send_json_success( array( 'draft_id' => null ) );
			}
		}

		public function flush_draft() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator.', 'ultimate-member' ) );
			}

			if ( ! empty( $_REQUEST['group_id'] ) ) {
				// edit group draft checking
				$group_id = absint( $_REQUEST['group_id'] );
				if ( empty( $group_id ) ) {
					wp_send_json_error( __( 'Empty group ID.', 'ultimate-member' ) );
				}

				$draft_id = UM()->admin()->field_group()->get_draft_by( $group_id, 'group' );
			} else {
				// add group draft checking
				$user_id  = get_current_user_id();
				$draft_id = UM()->admin()->field_group()->get_draft_by( $user_id, 'user' );
			}

			if ( false === $draft_id ) {
				wp_send_json_error( __( 'Draft isn\'t exist.', 'ultimate-member' ) );
			}

			global $wpdb;
			$wpdb->delete(
				"{$wpdb->prefix}um_field_groups_meta",
				array(
					'group_id' => $draft_id,
				),
				array(
					'%d',
				)
			);

			$wpdb->delete(
				"{$wpdb->prefix}um_field_groups",
				array(
					'id' => $draft_id,
				),
				array(
					'%d',
				)
			);

			if ( ! empty( $group_id ) ) {
				$draft_id = UM()->admin()->field_group()->get_draft_by( $group_id, 'group' );
			} elseif ( ! empty( $user_id ) ) {
				$draft_id = UM()->admin()->field_group()->get_draft_by( $user_id, 'user' );
			}

			if ( false === $draft_id ) {
				wp_send_json_success();
			}

			wp_send_json_error( __( 'Something went wrong. Draft cannot be removed.', 'ultimate-member' ) );
		}

		public function add_field() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as Administrator.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['group_id'] ) ) {
				wp_send_json_error( __( 'Wrong fields group ID.', 'ultimate-member' ) );
			}

			$group_id   = absint( $_REQUEST['group_id'] );
			$group_data = UM()->admin()->field_group()->get_data( $group_id );
			if ( empty( $group_data ) ) {
				wp_send_json_error( __( 'Wrong fields group ID. Cannot get data by ID.', 'ultimate-member' ) );
			}

			global $wpdb;

			$wpdb->insert(
				"{$wpdb->prefix}um_fields",
				array(
					'field_key'   => md5( 'field' . '' . time() ),
					'group_id'    => $group_id,
					'title'       => '',
					'description' => '',
					'type'        => 'text',
				),
				array(
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
				)
			);
			$field_id = $wpdb->insert_id;

			wp_send_json_success( array( 'field_id' => $field_id ) );
		}
	}
}
