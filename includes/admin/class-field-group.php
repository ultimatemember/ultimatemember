<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Field_Group' ) ) {

	/**
	 * Class Field_Group
	 *
	 * @package um\admin
	 */
	class Field_Group {

		/**
		 * @var
		 */
		public $form_id;

		/**
		 * @var array
		 */
		private $global_fields = array();

		public $is_displayed = false;

		/**
		 * Field_Group constructor.
		 */
		public function __construct() {
		}

		/**
		 *
		 */
		public function hooks() {
			add_filter( 'um_admin_render_checkbox_field_html', array( &$this, 'add_reset_rules_button' ), 10, 2 );
			add_filter( 'um_fields_settings', array( &$this, 'change_hidden_settings' ), 10, 2 );
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

		public function get_field_settings( $field_type, $field_id = null ) {
			$static_settings = UM()->config()->get( 'static_field_settings' );
			$field_types     = UM()->config()->get( 'field_types' );

			$settings_by_type = array_merge_recursive( $static_settings, $field_types[ $field_type ]['settings'] );
			$settings_by_type = apply_filters( 'um_fields_settings', $settings_by_type, $field_type );

			foreach ( $settings_by_type as $tab_key => &$settings_data ) {
				foreach ( $settings_data as $setting_key => &$setting_data ) {
					if ( array_key_exists( $tab_key, $static_settings ) && array_key_exists( $setting_key, $static_settings[ $tab_key ] ) ) {
						$setting_data['static'] = true;
					}
				}
			}

			if ( ! empty( $field_id ) ) {
				$field_data = $this->get_field_data( $field_id );
				if ( empty( $field_data ) ) {
					return $settings_by_type;
				}

				foreach ( $settings_by_type as $tab_key => &$settings_data ) {
					foreach ( $settings_data as $setting_key => &$setting_data ) {
						if ( ! array_key_exists( $setting_key, $field_data ) ) {
							continue;
						}

						$setting_data['value'] = maybe_unserialize( $field_data[ $setting_key ] );

//						if ( ! is_null( UM()->admin()->actions_listener()->field_group_submission ) &&
//						     is_array( UM()->admin()->actions_listener()->field_group_submission ) &&
//						     array_key_exists( 'fields', UM()->admin()->actions_listener()->field_group_submission ) ) {
//							UM()->admin()->actions_listener()->field_group_submission['fields'];
//						} else {
//							$setting_data['value'] = maybe_unserialize( $field_data[ $setting_key ] );
//						}
//						if ( 'conditional_rules' === $setting_key || 'options' === $setting_key ) {
//							$setting_data['value'] = maybe_unserialize( $field_data[ $setting_key ] );
//						} else {
//							$setting_data['value'] = $field_data[ $setting_key ];
//						}
					}
				}
			}

			return $settings_by_type;
		}

		public function get_field_tabs( $field_type ) {
			$titles = UM()->config()->get( 'field_settings_tabs' );

			$field_tabs = array_keys( $this->get_field_settings( $field_type ) );
			$tabs       = array_intersect_key( $titles, array_flip( $field_tabs ) );
			return $tabs;
		}

		/**
		 * @param int $group_id
		 *
		 * @return array
		 */
		public function get_data( $group_id ) {
			global $wpdb;

			$group_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * 
					FROM {$wpdb->prefix}um_field_groups 
					WHERE id = %d
					LIMIT 1",
					$group_id
				),
				ARRAY_A
			);

			return $group_data;
		}

		/**
		 * @param int      $group_id
		 * @param null|int $parent_id
		 *
		 * @return array
		 */
		public function get_fields( $group_id, $parent_id = null ) {
			global $wpdb;

			if ( ! is_null( $parent_id ) ) {
				$query = $wpdb->prepare(
					"SELECT f.*
					FROM {$wpdb->prefix}um_fields f 
					LEFT JOIN {$wpdb->prefix}um_fields_meta fm ON fm.field_id = f.id AND fm.meta_key = 'order' 
					WHERE group_id = %d AND 
						  parent_id = %d
					ORDER BY fm.meta_value ASC",
					$group_id,
					$parent_id
				);
			} else {
				$query = $wpdb->prepare(
					"SELECT f.*
					FROM {$wpdb->prefix}um_fields f 
					LEFT JOIN {$wpdb->prefix}um_fields_meta fm ON fm.field_id = f.id AND fm.meta_key = 'order' 
					WHERE group_id = %d
					ORDER BY fm.meta_value ASC",
					$group_id
				);
			}

//			if ( 'fields_only' === $type ) {
//				$query = $wpdb->prepare(
//					"SELECT *
//					FROM {$wpdb->prefix}um_fields
//					WHERE group_id = %d AND
//					      type != 'row'",
//					$group_id
//				);
//			} elseif ( 'row' === $type ) {
//				$query = $wpdb->prepare(
//					"SELECT *
//					FROM {$wpdb->prefix}um_fields
//					WHERE group_id = %d AND
//					      type = 'row'",
//					$group_id
//				);
//			}

			$fields = $wpdb->get_results( $query, ARRAY_A );
			return $fields;
		}

		/**
		 * Create fields group
		 *
		 * @param array $data {
		 *     Fields Group data array.
		 *
		 *     @type string $title       The fields group title.
		 *     @type string $description Optional. The fields group description.
		 *     @type string $status      Optional. The fields group status ('active' || 'inactive'). 'active' by default.
		 *     @type array  $fields      Optional. The link label.
		 * }
		 *
		 * @return bool|int Fields Group ID or false on failure
		 */
		public function create( $data ) {
			global $wpdb;

			if ( ! array_key_exists( 'title', $data ) ) {
				return false;
			}

			$wpdb->insert(
				"{$wpdb->prefix}um_field_groups",
				array(
					'group_key'   => md5( 'group' . uniqid( $data['title'] ) . time() ),
					'title'       => $data['title'],
					'description' => array_key_exists( 'description', $data ) ? $data['description'] : '',
					'status'      => array_key_exists( 'status', $data ) ? $data['status'] : 'active',
				),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			$field_group_id = $wpdb->insert_id;

			if ( ! empty( $field_group_id ) && array_key_exists( 'meta', $data ) ) {
				foreach ( $data['meta'] as $meta_key => $meta_value ) {
					$this->update_meta( $field_group_id, $meta_key, $meta_value );
				}
			}

//			if ( ! empty( $field_group_id ) ) {
//				// if fields in data array aren't exist then set basic row only
//				if ( ! array_key_exists( 'fields', $data ) ) {
//					$wpdb->insert(
//						"{$wpdb->prefix}um_fields",
//						array(
//							'field_key'   => md5( 'field' . __( 'Row', 'ultimate-member' ) . time() ),
//							'group_id'    => $field_group_id,
//							'title'       => __( 'Row', 'ultimate-member' ),
//							'description' => __( 'Basic Row', 'ultimate-member' ),
//						),
//						array(
//							'%s',
//							'%d',
//							'%s',
//							'%s',
//						)
//					);
//				}
//			}

			return $field_group_id;
		}

		public function add_field( $data ) {
			global $wpdb;

			if ( empty( $data['group_id'] ) || empty( $data['type'] ) ) {
				return false;
			}

			$wpdb->insert(
				"{$wpdb->prefix}um_fields",
				array(
					'field_key' => md5( 'field' . uniqid( $data['type'] . $data['title'] . $data['group_id'] ) . time() ),
					'group_id'  => $data['group_id'],
					'title'     => $data['title'],
					'type'      => $data['type'],
					'parent_id' => isset( $data['parent_id'] ) ? $data['parent_id'] : 0,
				),
				array(
					'%s',
					'%d',
					'%s',
					'%s',
					'%d',
				)
			);

			$field_id = $wpdb->insert_id;

			if ( ! empty( $field_id ) && array_key_exists( 'meta', $data ) ) {
				foreach ( $data['meta'] as $meta_key => $meta_value ) {
					$this->update_field_meta( $field_id, $meta_key, $meta_value );
				}
			}

			return $field_id;
		}

		public function update_field( $data ) {
			global $wpdb;

			if ( ! array_key_exists( 'id', $data ) ) {
				return false;
			}

			$update_data   = array();
			$update_format = array();

			if ( array_key_exists( 'title', $data ) ) {
				$update_data['title'] = $data['title'];
				$update_format[] = '%s';
			}

			if ( array_key_exists( 'type', $data ) ) {
				$update_data['type'] = $data['type'];
				$update_format[] = '%s';
			}

			if ( array_key_exists( 'parent_id', $data ) ) {
				$update_data['parent_id'] = $data['parent_id'];
				$update_format[] = '%d';
			}

			$wpdb->update(
				"{$wpdb->prefix}um_fields",
				$update_data,
				array(
					'id' => $data['id'],
				),
				$update_format,
				array(
					'%d',
				)
			);

			if ( array_key_exists( 'meta', $data ) ) {
				foreach ( $data['meta'] as $meta_key => $meta_value ) {
					$this->update_field_meta( $data['id'], $meta_key, $meta_value );
				}
			}

			return $data['id'];
		}

		public function field_meta_exists( $field_id, $meta_key ) {
			global $wpdb;

			$meta_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id 
					FROM {$wpdb->prefix}um_fields_meta 
					WHERE field_id = %d AND 
						  meta_key = %s 
					LIMIT 1",
					$field_id,
					$meta_key
				)
			);

			return ! empty( $meta_id ) ? $meta_id : false;
		}

		public function meta_exists( $group_id, $meta_key ) {
			global $wpdb;

			$meta_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id 
					FROM {$wpdb->prefix}um_field_groups_meta 
					WHERE group_id = %d AND 
						  meta_key = %s 
					LIMIT 1",
					$group_id,
					$meta_key
				)
			);

			return ! empty( $meta_id ) ? $meta_id : false;
		}

		public function update_field_meta( $field_id, $meta_key, $meta_value ) {
			global $wpdb;

			// don't use predefined in `um_fields`. 'fields' = predefined meta for repeater field that uses internally
			if ( in_array( $meta_key, array( 'id', 'field_key', 'group_id', 'title', 'type', 'parent_id', 'fields' ) ) ) {
				return;
			}

			if ( ! is_string( $meta_value ) ) {
				$meta_value = maybe_serialize( $meta_value );
			}

			$meta_id = $this->field_meta_exists( $field_id, $meta_key );
			if ( false === $meta_id ) {
				$wpdb->insert(
					"{$wpdb->prefix}um_fields_meta",
					array(
						'field_id'   => $field_id,
						'meta_key'   => $meta_key,
						'meta_value' => $meta_value,
					),
					array(
						'%d',
						'%s',
						'%s',
					)
				);
			} else {
				$wpdb->update(
					"{$wpdb->prefix}um_fields_meta",
					array(
						'meta_value' => $meta_value,
					),
					array(
						'meta_id'   => $meta_id,
					),
					array(
						'%s',
					),
					array(
						'%d',
					)
				);
			}
		}

		public function update_meta( $group_id, $meta_key, $meta_value ) {
			global $wpdb;

			if ( ! is_string( $meta_value ) ) {
				$meta_value = maybe_serialize( $meta_value );
			}

			$meta_id = $this->meta_exists( $group_id, $meta_key );
			if ( false === $meta_id ) {
				$wpdb->insert(
					"{$wpdb->prefix}um_field_groups_meta",
					array(
						'group_id'   => $group_id,
						'meta_key'   => $meta_key,
						'meta_value' => $meta_value,
					),
					array(
						'%d',
						'%s',
						'%s',
					)
				);
			} else {
				$wpdb->update(
					"{$wpdb->prefix}um_field_groups_meta",
					array(
						'meta_value' => $meta_value,
					),
					array(
						'meta_id'   => $meta_id,
					),
					array(
						'%s',
					),
					array(
						'%d',
					)
				);
			}
		}

		/**
		 * Update fields group
		 *
		 * @param array $data {
		 *     Fields Group data array.
		 *
		 *     @type string $title       The fields group title.
		 *     @type string $description Optional. The fields group description.
		 *     @type string $status      Optional. The fields group status ('active' || 'inactive'). 'active' by default.
		 *     @type array  $fields      Optional. The link label.
		 * }
		 *
		 * @return bool|int Fields Group ID or false on failure
		 */
		public function update( $data ) {
			global $wpdb;

			if ( ! array_key_exists( 'id', $data ) ) {
				return false;
			}

			$update_data   = array();
			$update_format = array();

			if ( array_key_exists( 'title', $data ) ) {
				$update_data['title'] = $data['title'];
				$update_format[] = '%s';
			}

			if ( array_key_exists( 'description', $data ) ) {
				$update_data['description'] = $data['description'];
				$update_format[] = '%s';
			}

			$wpdb->update(
				"{$wpdb->prefix}um_field_groups",
				$update_data,
				array(
					'id' => $data['id'],
				),
				$update_format,
				array(
					'%d',
				)
			);

			if ( array_key_exists( 'meta', $data ) ) {
				foreach ( $data['meta'] as $meta_key => $meta_value ) {
					$this->update_meta( $data['id'], $meta_key, $meta_value );
				}
			}

			return $data['id'];
		}

		/**
		 * Get all fields of the row.
		 *
		 * @param int $group_id     Group ID.
		 * @param int $row_field_id Row's Field ID.
		 *
		 * @return array|bool Array of the row's fields or false on failure
		 */
		public function get_rows_fields( $group_id, $row_field_id ) {
			global $wpdb;

			$field_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id 
					FROM {$wpdb->prefix}um_fields 
					WHERE id = %d AND 
						  group_id = %d 
					LIMIT 1",
					$row_field_id,
					$group_id
				)
			);

			if ( empty( $field_id ) ) {
				return false;
			}

			$fields = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT f.* 
					FROM {$wpdb->prefix}um_fields AS f
					LEFT JOIN {$wpdb->prefix}um_fields_meta AS fm ON fm.field_id = f.id AND fm.meta_key = 'row'
					WHERE fm.meta_value = %d AND 
						  group_id = %d",
					$row_field_id,
					$group_id
				),
				ARRAY_A
			);

			return $fields;
		}

		/**
		 * @param array $field Field data array
		 * @param bool  $raw   Return raw or formatted field type
		 *
		 * @return bool|string Field's type or false on failure
		 */
		public function get_field_type( $field, $raw = false ) {
			if ( ! array_key_exists( 'type', $field ) ) {
				return false;
			}

			if ( false !== $raw ) {
				return $field['type'];
			}

			$field_types = UM()->config()->get( 'field_types' );

			$types_map = array_combine( array_keys( $field_types ), array_column( $field_types, 'title' ) );

			if ( ! array_key_exists( $field['type'], $types_map ) ) {
				return $field['type'];
			}

			return $types_map[ $field['type'] ];
		}

		/**
		 * @param array|int $field Field data array
		 *
		 * @return bool|string Field's metakey or false on failure
		 */
		public function get_field_metakey( $field ) {
			if ( empty( $field ) ) {
				return false;
			}

			if ( ! is_numeric( $field ) ) {
				if ( ! array_key_exists( 'id', $field ) ) {
					return false;
				}

				$field = $field['id'];
			}

			return $this->get_field_meta( $field, 'meta_key' );
		}

		/**
		 * @param array|int $field    Field data array
		 * @param string    $meta_key Meta key for associated meta value
		 * @param mixed     $default  Default meta value
		 *
		 * @return bool|string|array Field's meta or false on failure
		 */
		public function get_field_meta( $field, $meta_key, $default = '' ) {
			global $wpdb;

			if ( empty( $field ) ) {
				return false;
			}

			if ( ! is_numeric( $field ) ) {
				if ( ! array_key_exists( 'id', $field ) ) {
					return false;
				}

				$field = $field['id'];
			}

			$meta_value = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value 
					FROM {$wpdb->prefix}um_fields_meta 
					WHERE field_id = %d AND 
						  meta_key = %s
					LIMIT 1",
					$field,
					$meta_key
				)
			);

			if ( empty( $meta_value ) ) {
				$meta_value = $default;
			} else {
				$meta_value = maybe_unserialize( $meta_value );
			}

			return $meta_value;
		}

		public function get_field_data( $field ) {
			global $wpdb;

			if ( is_array( $field ) ) {
				if ( ! array_key_exists( 'id', $field ) ) {
					return false;
				}

				$field = $field['id'];
			}

			$field_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * 
					FROM {$wpdb->prefix}um_fields 
					WHERE id = %d
					LIMIT 1",
					$field
				),
				ARRAY_A
			);

			if ( empty( $field_data ) ) {
				return false;
			}

			unset( $field_data['id'] );

			$meta_values = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT meta_key, meta_value 
					FROM {$wpdb->prefix}um_fields_meta 
					WHERE field_id = %d",
					$field
				),
				ARRAY_A
			);

			if ( ! empty( $meta_values ) ) {
				$meta_values = array_combine( array_column( $meta_values, 'meta_key' ), array_column( $meta_values, 'meta_value' ) );
				$field_data = array_merge( $field_data, $meta_values );
			}

			// Get repeater fields
			if ( 'repeater' === $field_data['type'] ) {
				$field_data['fields']    = $this->get_fields( $field_data['group_id'], $field );
			}

			return $field_data;
		}

		public function get_tab_fields_html( $tab, $field ) {
			$template_settings = UM()->admin()->field_group()->get_field_settings( $field['type'], $field['index'] );
			// set field type from the function's input
			if ( isset( $template_settings['general']['type'] ) ) {
				$template_settings['general']['type']['value'] = $field['type'];
			}

			$settings_fields = $template_settings[ $tab ];
			foreach ( $settings_fields as &$setting_data ) {
				// predefine name here for making the proper names through all the group fields builder
				$setting_data['name'] = 'field_group[fields][' . $field['index'] . '][' . $setting_data['id'] . ']';

				// disable fields if predefined
				if ( ! empty( $field['disabled'] ) ) {
					$setting_data['disabled'] = true;
				}

				// static fields that are still the same when change the field type
				if ( ! empty( $setting_data['static'] ) ) {
					$setting_data['class'] = ! empty( $setting_data['class'] ) ? $setting_data['class'] . ' um-field-row-static-setting' : 'um-field-row-static-setting';
				}

				// Maybe fill the data from submission
				if ( ! is_null( UM()->admin()->actions_listener()->field_group_submission ) &&
				     is_array( UM()->admin()->actions_listener()->field_group_submission ) &&
				     array_key_exists( 'fields', UM()->admin()->actions_listener()->field_group_submission ) ) {

					if ( array_key_exists( $field['index'], UM()->admin()->actions_listener()->field_group_submission['fields'] ) ) {
						if ( array_key_exists( $setting_data['id'], UM()->admin()->actions_listener()->field_group_submission['fields'][ $field['index'] ] ) ) {
							$setting_data['value'] = UM()->admin()->actions_listener()->field_group_submission['fields'][ $field['index'] ][ $setting_data['id'] ];
						} elseif ( 'repeater' === $field['type'] && 'fields' === $setting_data['id'] ) {
							$sub_fields = array();
							foreach ( UM()->admin()->actions_listener()->field_group_submission['fields'] as $k => $f ) {
								if ( array_key_exists( 'parent_id', $f ) && $f['parent_id'] === $field['index'] ) {
									$sub_fields[ $k ] = $f;
								}
							}

							if ( ! empty( $sub_fields ) ) {
								$setting_data['value'] = $sub_fields;
							}
						}
					}
				}
			}

			$form_content = UM()->admin()->forms(
				array(
					'class'     => 'field_group_fields_' . $tab . '_' . $field['index'],
					'prefix_id' => 'field_group[fields][' . $field['index'] . '][' . $tab . ']',
					'fields'    => $settings_fields,
				)
			)->render_form( false );

			return $form_content;
		}

		public function delete_group( $group_id ) {
			global $wpdb;

			$result = $wpdb->delete(
				"{$wpdb->prefix}um_field_groups",
				array( 'id' => $group_id ),
				array( '%d' )
			);

			if ( false !== $result ) {
				$wpdb->delete(
					"{$wpdb->prefix}um_field_groups_meta",
					array( 'group_id' => $group_id ),
					array( '%d' )
				);

				$group_fields = $this->get_fields( $group_id );
				if ( ! empty( $group_fields ) ) {
					$field_ids = array_column( $group_fields, 'id' );
					if ( ! empty( $field_ids ) ) {
						$this->delete_field( $field_ids );
					}
				}
			}

			return ( false !== $result && $result > 0 );
		}

		public function delete_field( $field_ids ) {
			global $wpdb;

			if ( ! is_array( $field_ids ) ) {
				$field_ids = array( $field_ids );
			}

			$result = $wpdb->query( "DELETE FROM {$wpdb->prefix}um_fields WHERE id IN('" . implode( "','", $field_ids ) . "')" );
			$wpdb->query( "DELETE FROM {$wpdb->prefix}um_fields_meta WHERE field_id IN('" . implode( "','", $field_ids ) . "')" );

			return ( false !== $result && $result > 0 );
		}

		public function activate_group( $group_id ) {
			$result = $this->set_status( $group_id, 'active' );
			return false !== $result;
		}

		public function deactivate_group( $group_id ) {
			$result = $this->set_status( $group_id, 'inactive' );
			return false !== $result;
		}

		public function group_exists( $group_id ) {
			global $wpdb;

			$group_exists = $wpdb->get_var(
				$wpdb->prepare(
				"SELECT id 
					FROM {$wpdb->prefix}um_field_groups 
					WHERE id = %d",
					$group_id
				)
			);

			return ! empty( $group_exists );
		}

		public function set_status( $group_id, $status ) {
			global $wpdb;

			if ( ! in_array( $status, array( 'active', 'inactive', 'draft', 'invalid' ), true ) ) {
				return false;
			}

			if ( ! $this->group_exists( $group_id ) ) {
				return false;
			}

			$result = $wpdb->update(
				"{$wpdb->prefix}um_field_groups",
				array( 'status' => $status ),
				array( 'id' => $group_id ),
				array( '%s' ),
				array( '%d' )
			);

			return ! empty( $result );
		}

		public function field_row_template() {
			// Avoid duplicates for field row template.
			if ( true === $this->is_displayed ) {
				return;
			}

			$field_types = UM()->config()->get( 'field_types' );

			// text-type field is default field type for the builder
			$template_type     = 'text';
			$template_tabs     = UM()->admin()->field_group()->get_field_tabs( $template_type );
			$template_settings = UM()->admin()->field_group()->get_field_settings( $template_type );

			// text-type field is default field type for the builder
			$template_settings['general']['type']['value'] = $template_type;
			?>
			<div class="um-field-row-template um-field-row-edit-mode">
				<input type="hidden" class="um-field-row-id" name="field_group[fields][new_{index}][id]" value="" disabled />
				<input type="hidden" class="um-field-row-parent-id" name="field_group[fields][new_{index}][parent_id]" value="" disabled />
				<input type="hidden" class="um-field-row-order" name="field_group[fields][new_{index}][order]" value="" disabled />
				<div class="um-field-row-header um-field-row-toggle-edit">
					<span class="um-field-row-move-link"></span>
					<span class="um-field-row-title um-field-row-toggle-edit"><?php esc_html_e( '(no title)', 'ultimate-member' ); ?></span>
					<span class="um-field-row-metakey um-field-row-toggle-edit"><?php esc_html_e( '(no metakey)', 'ultimate-member' ); ?></span>
					<span class="um-field-row-type um-field-row-toggle-edit"><?php echo esc_html( $field_types[ $template_type ]['title'] ); ?></span>
					<span class="um-field-row-actions um-field-row-toggle-edit">
						<a href="javascript:void(0);" class="um-field-row-action-edit"><?php esc_html_e( 'Edit', 'ultimate-member' ); ?></a>
						<a href="javascript:void(0);" class="um-field-row-action-duplicate"><?php esc_html_e( 'Duplicate', 'ultimate-member' ); ?></a>
						<a href="javascript:void(0);" class="um-field-row-action-delete"><?php esc_html_e( 'Delete', 'ultimate-member' ); ?></a>
					</span>
				</div>
				<div class="um-field-row-content">
					<div class="um-field-row-tabs">
						<?php
						foreach ( $template_tabs as $tab_key => $tab_title ) {
							$classes = array();
							if ( 'general' === $tab_key ) {
								// General tab is selected by default for the new field.
								$classes[] = 'current';
							}
							?>

							<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
								<?php echo esc_html( $tab_title ); ?>
							</div>

							<?php
						}
						?>
					</div>
					<div class="um-field-row-tabs-content">
						<?php
						foreach ( $template_settings as $tab_key => $settings_fields ) {
							$classes = array();
							if ( 'general' === $tab_key ) {
								// General tab is selected by default for the new field.
								$classes[] = 'current';
							}
							?>
							<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
								<?php
								echo UM()->admin()->field_group()->get_tab_fields_html( $tab_key, array( 'type' => $template_type, 'index' => 'new_{index}', 'disabled' => true ) );
								?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>

			<?php
			$this->is_displayed = true;
		}
	}
}
