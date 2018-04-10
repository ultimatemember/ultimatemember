<?php

namespace um\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\Fields' ) ) {


	/**
	 * Class Fields
	 * @package um\core
	 */
	class Fields {


		/**
		 * @var string
		 */
		var $set_mode = '';


		/**
		 * Fields constructor.
		 */
		function __construct() {
			$this->editing = false;
			$this->viewing = false;
			$this->timestamp = current_time( 'timestamp' );
		}


		/**
		 * Standard checkbox field
		 *
		 * @param  integer $id
		 * @param  string  $title
		 */
		function checkbox( $id, $title ) {
			?>

			<div class="um-field um-field-c">
				<div class="um-field-area">
					<label class="um-field-checkbox active">
						<input type="checkbox" name="<?php echo $id; ?>" value="1" checked/><span
							class="um-field-checkbox-state"><i
								class="um-icon-android-checkbox-outline"></i></span>
						<span class="um-field-checkbox-option"> <?php echo $title; ?></span>
					</label>
				</div>
			</div>

			<?php
		}


		/**
		 * Shows social links
		 */
		function show_social_urls() {
			$social = array();

			$fields = UM()->builtin()->all_user_fields;
			foreach ( $fields as $field => $args ) {
				if ( isset( $args['advanced'] ) && $args['advanced'] == 'social' ) {
					$social[ $field ] = $args;
				}
			}

			foreach ( $social as $k => $arr ) {
				if ( um_profile( $k ) ) { ?>

					<a href="<?php echo um_filtered_social_link( $k, $arr['match'] ); ?>"
					   style="background: <?php echo $arr['color']; ?>;" target="_blank" class="um-tip-n"
					   title="<?php echo $arr['title']; ?>"><i class="<?php echo $arr['icon']; ?>"></i></a>

					<?php
				}
			}
		}


		/**
		 * Hidden field inside a shortcode
		 *
		 * @param string $field
		 */
		function add_hidden_field( $field ) {
			echo '<div style="display: none !important;">';

			$fields = UM()->builtin()->get_specific_fields( $field );

			$output = null;

			foreach ($fields as $key => $data) {
				$output .= UM()->fields()->edit_field( $key, $data );
			}

			echo $output;

			echo '</div>';
		}


		/**
		 * Get hidden field
		 *
		 * @param  string $key
		 * @param  string $value
		 *
		 * @return string
		 */
		function disabled_hidden_field( $key, $value ) {
			return '<input type="hidden" name="' . $key . '" value="' . esc_attr( $value ) . '"/>';
		}


		/**
		 * Updates a field globally
		 *
		 * @param  integer $id
		 * @param  array   $args
		 */
		function globally_update_field( $id, $args ) {
			$fields = UM()->builtin()->saved_fields;

			$fields[$id] = $args;

			unset( $fields[$id]['in_row'] );
			unset( $fields[$id]['in_sub_row'] );
			unset( $fields[$id]['in_column'] );
			unset( $fields[$id]['in_group'] );
			unset( $fields[$id]['position'] );

			update_option( 'um_fields', $fields );
		}


		/**
		 * Updates a field in form only
		 *
		 * @param  integer $id
		 * @param  array   $args
		 * @param  integer $form_id
		 */
		function update_field( $id, $args, $form_id ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );

			if ($args['type'] == 'row') {
				if (isset( $fields[$id] )) {
					$old_args = $fields[$id];
					foreach ($old_args as $k => $v) {
						if (!in_array( $k, array( 'sub_rows', 'cols' ) )) {
							unset( $old_args[$k] );
						}
					}
					$args = array_merge( $old_args, $args );
				}
			}

			// custom fields support
			if (isset( UM()->builtin()->predefined_fields[$id] ) && isset( UM()->builtin()->predefined_fields[$id]['custom'] )) {
				$args = array_merge( UM()->builtin()->predefined_fields[$id], $args );
			}

			$fields[$id] = $args;

			// for group field only
			if ($args['type'] == 'group') {
				$fields[$id]['in_group'] = '';
			}

			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
		}


		/**
		 * Deletes a field in form only
		 *
		 * @param  integer $id
		 * @param  integer $form_id
		 */
		function delete_field_from_form( $id, $form_id ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			if (isset( $fields[$id] )) {
				unset( $fields[$id] );
				UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			}
		}


		/**
		 * Deletes a field from custom fields
		 *
		 * @param  integer $id
		 */
		function delete_field_from_db( $id ) {
			$fields = UM()->builtin()->saved_fields;
			if (isset( $fields[$id] )) {
				unset( $fields[$id] );
				update_option( 'um_fields', $fields );
			}
		}


		/**
		 * Quickly adds a field from custom fields
		 *
		 * @param integer $global_id
		 * @param integer $form_id
		 * @param array   $position
		 */
		function add_field_from_list( $global_id, $form_id, $position = array() ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			$field_scope = UM()->builtin()->saved_fields;

			if (!isset( $fields[$global_id] )) {

				$count = 1;
				if (isset( $fields ) && !empty( $fields )) $count = count( $fields ) + 1;

				$fields[$global_id] = $field_scope[$global_id];
				$fields[$global_id]['position'] = $count;

				// set position
				if ($position) {
					foreach ($position as $key => $val) {
						$fields[$global_id][$key] = $val;
					}
				}

				// add field to form
				UM()->query()->update_attr( 'custom_fields', $form_id, $fields );

			}
		}


		/**
		 * Quickly adds a field from pre-defined fields
		 *
		 * @param integer $global_id
		 * @param integer $form_id
		 * @param array   $position
		 */
		function add_field_from_predefined( $global_id, $form_id, $position = array() ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			$field_scope = UM()->builtin()->predefined_fields;

			if (!isset( $fields[$global_id] )) {

				$count = 1;
				if (isset( $fields ) && !empty( $fields )) $count = count( $fields ) + 1;

				$fields[$global_id] = $field_scope[$global_id];
				$fields[$global_id]['position'] = $count;

				// set position
				if ($position) {
					foreach ($position as $key => $val) {
						$fields[$global_id][$key] = $val;
					}
				}

				// add field to form
				UM()->query()->update_attr( 'custom_fields', $form_id, $fields );

				// add field to db
				//$this->globally_update_field( $global_id, $fields[$global_id] );

			}
		}


		/**
		 * Duplicates a frield by meta key
		 *
		 * @param  integer $id
		 * @param  integer $form_id
		 */
		function duplicate_field( $id, $form_id ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			$all_fields = UM()->builtin()->saved_fields;

			$inc = count( $fields ) + 1;

			$duplicate = $fields[$id];

			$new_metakey = $id . "_" . $inc;
			$new_title = $fields[$id]['title'] . " #" . $inc;
			$new_position = $inc;

			$duplicate['title'] = $new_title;
			$duplicate['metakey'] = $new_metakey;
			$duplicate['position'] = $new_position;

			$fields[$new_metakey] = $duplicate;
			$all_fields[$new_metakey] = $duplicate;

			// not global attributes
			unset( $all_fields[$new_metakey]['in_row'] );
			unset( $all_fields[$new_metakey]['in_sub_row'] );
			unset( $all_fields[$new_metakey]['in_column'] );
			unset( $all_fields[$new_metakey]['in_group'] );
			unset( $all_fields[$new_metakey]['position'] );

			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			update_option( 'um_fields', $all_fields );

		}


		/**
		 * Print field error
		 *
		 * @param string $text
		 * @param bool   $force_show
		 *
		 * @return string
		 */
		function field_error( $text, $force_show = false ) {
			if ($force_show) {
				$output = '<div class="um-field-error"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . $text . '</div>';

				return $output;
			}
			if (isset( $this->set_id ) && UM()->form()->processing == $this->set_id) {
				$output = '<div class="um-field-error"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . $text . '</div>';
			} else {
				$output = '';
			}

			if (!UM()->form()->processing) {
				$output = '<div class="um-field-error"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . $text . '</div>';
			}

			return $output;
		}


		/**
		 * Checks if field has a server-side error
		 *
		 * @param  string $key
		 *
		 * @return boolean
		 */
		function is_error( $key ) {
			return UM()->form()->has_error( $key );
		}


		/**
		 * Returns field error
		 *
		 * @param  string $key
		 *
		 * @return string
		 */
		function show_error( $key ) {
			return UM()->form()->errors[$key];
		}


		/**
		 *  Display field label
		 *
		 * @param  string $label
		 * @param  string $key
		 * @param  array $data
		 *
		 * @return  string
		 */
		function field_label( $label, $key, $data ) {
			$output = null;
			$output .= '<div class="um-field-label">';

			if (isset( $data['icon'] ) && $data['icon'] != '' && isset( $this->field_icons ) && $this->field_icons != 'off' && ( $this->field_icons == 'label' || $this->viewing == true )) {
				$output .= '<div class="um-field-label-icon"><i class="' . $data['icon'] . '"></i></div>';
			}

			if ( $this->viewing == true ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_view_label_{$key}
				 * @description Change field label on view by field $key
				 * @input_vars
				 * [{"var":"$label","type":"string","desc":"Field Label"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_view_label_{$key}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_view_label_{$key}', 'my_view_label', 10, 1 );
				 * function my_view_label( $label ) {
				 *     // your code here
				 *     return $label;
				 * }
				 * ?>
				 */
				$label = apply_filters( "um_view_label_{$key}", $label );
			} else {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_edit_label_{$key}
				 * @description Change field label on edit by field $key
				 * @input_vars
				 * [{"var":"$label","type":"string","desc":"Field Label"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_edit_label_{$key}', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_edit_label_{$key}', 'my_edit_label', 10, 1 );
				 * function my_edit_label( $label ) {
				 *     // your code here
				 *     return $label;
				 * }
				 * ?>
				 */
				$label = apply_filters( "um_edit_label_{$key}", $label );
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_edit_label_all_fields
				 * @description Change field label on view by field $key
				 * @input_vars
				 * [{"var":"$label","type":"string","desc":"Field Label"},
				 * {"var":"$data","type":"array","desc":"Field Data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_edit_label_all_fields', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_edit_label_all_fields', 'my_edit_label_all_fields', 10, 2 );
				 * function my_edit_label_all_fields( $label, $data ) {
				 *     // your code here
				 *     return $label;
				 * }
				 * ?>
				 */
				$label = apply_filters( 'um_edit_label_all_fields', $label, $data );
			}

			$output .= '<label for="' . $key . UM()->form()->form_suffix . '">' . __( $label, 'ultimate-member' ) . '</label>';

			if (isset( $data['help'] ) && !empty( $data['help'] ) && $this->viewing == false && !strstr( $key, 'confirm_user_pass' )) {

				if (!UM()->mobile()->isMobile()) {
					if (!isset( $this->disable_tooltips )) {
						$output .= '<span class="um-tip um-tip-w" title="' . __( $data['help'], 'ultimate-member' ) . '"><i class="um-icon-help-circled"></i></span>';
					}
				}

				if (UM()->mobile()->isMobile() || isset( $this->disable_tooltips )) {
					$output .= '<span class="um-tip-text">' . __( $data['help'], 'ultimate-member' ) . '</span>';
				}

			}

			$output .= '<div class="um-clear"></div></div>';

			return $output;
		}


		/**
		 * Output field classes
		 *
		 * @param  string $key
		 * @param  array  $data
		 * @param  string $add
		 *
		 * @return string
		 */
		function get_class( $key, $data, $add = null ) {
			$classes = null;

			$classes .= 'um-form-field ';

			if ($this->is_error( $key )) {
				$classes .= 'um-error ';
			} else {
				$classes .= 'valid ';
			}

			if (!isset( $data['required'] )) {
				$classes .= 'not-required ';
			}

			if ($data['type'] == 'date') {
				$classes .= 'um-datepicker ';
			}

			if ($data['type'] == 'time') {
				$classes .= 'um-timepicker ';
			}

			if (isset( $data['icon'] ) && $data['icon'] && isset( $this->field_icons ) && $this->field_icons == 'field') {
				$classes .= 'um-iconed ';
			}

			if ($add) {
				$classes .= $add . ' ';
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_extend_field_classes
			 * @description Change field classes
			 * @input_vars
			 * [{"var":"$classes","type":"string","desc":"Field Classes"},
			 * {"var":"$key","type":"string","desc":"Field Key"},
			 * {"var":"$data","type":"array","desc":"Field Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_extend_field_classes', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_extend_field_classes', 'my_extend_field_classes', 10, 3 );
			 * function my_extend_field_classes( $classes, $key, $data ) {
			 *     // your code here
			 *     return $classes;
			 * }
			 * ?>
			 */
			$classes = apply_filters( 'um_extend_field_classes', $classes, $key, $data );

			return $classes;
		}


		/**
		 * Gets field value
		 *
		 * @param  string  $key
		 * @param  boolean $default
		 * @param  array   $data
		 *
		 * @return mixed
		 */
		function field_value( $key, $default = false, $data = null ) {
			if (isset( $_SESSION ) && isset( $_SESSION['um_social_profile'][$key] ) && isset( $this->set_mode ) && $this->set_mode == 'register')
				return $_SESSION['um_social_profile'][$key];

			$type = ( isset( $data['type'] ) ) ? $data['type'] : '';

			// preview in backend
			if (isset( UM()->user()->preview ) && UM()->user()->preview) {
				$submitted = um_user( 'submitted' );
				if (isset( $submitted[$key] ) && !empty( $submitted[$key] )) {
					return $submitted[$key];
				} else {
					return 'Undefined';
				}
			}

			// normal state
			if ( isset( UM()->form()->post_form[ $key ] ) ) {

				if ( strstr( $key, 'user_pass' ) && $this->set_mode != 'password' ) {
					return '';
				}

				return stripslashes_deep( UM()->form()->post_form[$key] );

			} elseif ( um_user( $key ) && $this->editing == true ) {

				if ( strstr( $key, 'user_pass' ) ) {
					return '';
				}

				$value = um_user( $key );
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_edit_{$key}_field_value
				 * @description Change field value on edit by field $key
				 * @input_vars
				 * [{"var":"$value","type":"string","desc":"Field Value"},
				 * {"var":"$key","type":"string","desc":"Field Key"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_edit_{$key}_field_value', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_edit_{$key}_field_value', 'my_edit_field_value', 10, 2 );
				 * function my_edit_field_value( $value, $key ) {
				 *     // your code here
				 *     return $value;
				 * }
				 * ?>
				 */
				$value = apply_filters( "um_edit_{$key}_field_value", $value, $key );
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_edit_{$type}_field_value
				 * @description Change field value on edit by field $type
				 * @input_vars
				 * [{"var":"$value","type":"string","desc":"Field Value"},
				 * {"var":"$key","type":"string","desc":"Field Key"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_edit_{$type}_field_value', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_edit_{$type}_field_value', 'my_edit_field_value', 10, 2 );
				 * function my_edit_field_value( $value, $key ) {
				 *     // your code here
				 *     return $value;
				 * }
				 * ?>
				 */
				$value = apply_filters( "um_edit_{$type}_field_value", $value, $key );

				return $value;

			} elseif ( ( um_user( $key ) || isset( $data['show_anyway'] ) ) && $this->viewing == true ) {

				$value = um_filtered_value( $key, $data );

				return $value;

			} elseif ( isset( UM()->user()->profile[ $key ] ) ) {

				$value = UM()->user()->profile[ $key ];
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_edit_{$key}_field_value
				 * @description Change field value on edit by field $key
				 * @input_vars
				 * [{"var":"$value","type":"string","desc":"Field Value"},
				 * {"var":"$key","type":"string","desc":"Field Key"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_edit_{$key}_field_value', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_edit_{$key}_field_value', 'my_edit_field_value', 10, 2 );
				 * function my_edit_field_value( $value, $key ) {
				 *     // your code here
				 *     return $value;
				 * }
				 * ?>
				 */
				$value = apply_filters( "um_edit_{$key}_field_value", $value, $key );
				return $value;

			} elseif ( $default ) {

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_field_default_value
				 * @description Change field default value
				 * @input_vars
				 * [{"var":"$default","type":"string","desc":"Field Default Value"},
				 * {"var":"$data","type":"array","desc":"Field Data"},
				 * {"var":"$type","type":"string","desc":"Field Type"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_field_default_value', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_field_default_value', 'my_field_default_value', 10, 2 );
				 * function my_field_default_value( $default, $data, $type ) {
				 *     // your code here
				 *     return $default;
				 * }
				 * ?>
				 */
				$default = apply_filters( "um_field_default_value", $default, $data, $type );
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_field_{$key}_default_value
				 * @description Change field default value by $key
				 * @input_vars
				 * [{"var":"$default","type":"string","desc":"Field Default Value"},
				 * {"var":"$data","type":"array","desc":"Field Data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_field_{$key}_default_value', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_field_{$key}_default_value', 'my_field_default_value', 10, 2 );
				 * function my_field_default_value( $default, $data ) {
				 *     // your code here
				 *     return $default;
				 * }
				 * ?>
				 */
				$default = apply_filters( "um_field_{$key}_default_value", $default, $data );
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_field_{$type}_default_value
				 * @description Change field default value by $type
				 * @input_vars
				 * [{"var":"$default","type":"string","desc":"Field Default Value"},
				 * {"var":"$data","type":"array","desc":"Field Data"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_field_{$type}_default_value', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_field_{$type}_default_value', 'my_field_default_value', 10, 2 );
				 * function my_field_default_value( $default, $data ) {
				 *     // your code here
				 *     return $default;
				 * }
				 * ?>
				 */
				$default = apply_filters( "um_field_{$type}_default_value", $default, $data );
				return $default;

			} elseif ( $this->editing == true ) {

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_edit_{$key}_field_value
				 * @description Change field value on edit by field $key
				 * @input_vars
				 * [{"var":"$value","type":"string","desc":"Field Value"},
				 * {"var":"$key","type":"string","desc":"Field Key"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_edit_{$key}_field_value', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_edit_{$key}_field_value', 'my_edit_field_value', 10, 2 );
				 * function my_edit_field_value( $value, $key ) {
				 *     // your code here
				 *     return $value;
				 * }
				 * ?>
				 */
				return apply_filters( "um_edit_{$key}_field_value", '', $key );

			}

			return '';
		}


		/**
		 * Checks if an option is selected
		 *
		 * @param  string $key
		 * @param  string $value
		 * @param  array  $data
		 *
		 * @return boolean
		 */
		function is_selected( $key, $value, $data ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_is_selected_filter_key
			 * @description Change is selected filter key
			 * @input_vars
			 * [{"var":"$key","type":"string","desc":"Selected filter key"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_is_selected_filter_key', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_is_selected_filter_key', 'my_selected_filter_key', 10, 1 );
			 * function my_selected_filter_key( $key ) {
			 *     // your code here
			 *     return $key;
			 * }
			 * ?>
			 */
			$key = apply_filters( 'um_is_selected_filter_key', $key );

			if ( isset( UM()->form()->post_form[ $key ] ) && is_array( UM()->form()->post_form[ $key ] ) ) {

				if ( in_array( $value, UM()->form()->post_form[ $key ] ) ) {
					return true;
				}

				if ( in_array( html_entity_decode( $value ), UM()->form()->post_form[ $key ] ) ) {
					return true;
				}

			} else {

				if (!isset( UM()->form()->post_form[$key] )) {

					$field_value = um_user( $key );

					if ($key == 'role') {

						$role_keys = get_option( 'um_roles' );

						if (!empty( $role_keys )) {
							if (in_array( $field_value, $role_keys )) {
								$field_value = 'um_' . $field_value;
							}
						}

					}

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_is_selected_filter_value
					 * @description Change is selected filter value
					 * @input_vars
					 * [{"var":"$value","type":"string","desc":"Selected filter value"},
					 * {"var":"$key","type":"string","desc":"Selected filter key"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_is_selected_filter_value', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_is_selected_filter_value', 'my_selected_filter_value', 10, 2 );
					 * function my_selected_filter_value( $value, $key ) {
					 *     // your code here
					 *     return $field_value;
					 * }
					 * ?>
					 */
					$field_value = apply_filters( 'um_is_selected_filter_value', $field_value, $key );
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_is_selected_filter_data
					 * @description Change is selected filter data
					 * @input_vars
					 * [{"var":"$data","type":"array","desc":"Selected filter value"},
					 * {"var":"$key","type":"string","desc":"Selected filter key"},
					 * {"var":"$value","type":"string","desc":"Selected filter value"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_is_selected_filter_data', 'function_name', 10, 3 );
					 * @example
					 * <?php
					 * add_filter( 'um_is_selected_filter_data', 'my_selected_filter_data', 10, 3 );
					 * function my_selected_filter_data( $data, $key, $value ) {
					 *     // your code here
					 *     return $data;
					 * }
					 * ?>
					 */
					$data = apply_filters( 'um_is_selected_filter_data', $data, $key, $field_value );

					if ($field_value && $this->editing == true && is_array( $field_value ) && ( in_array( $value, $field_value ) || in_array( html_entity_decode( $value ), $field_value ) )) {
						return true;
					}

					if ($field_value && $this->editing == true && !is_array( $field_value ) && $field_value == $value) {
						return true;
					}

					if ($field_value && $this->editing == true && !is_array( $field_value ) && html_entity_decode( $field_value ) == html_entity_decode( $value )) {
						return true;
					}

					if (strstr( $data['default'], ', ' )) {
						$data['default'] = explode( ', ', $data['default'] );
					}

					if (isset( $data['default'] ) && !is_array( $data['default'] ) && $data['default'] == $value) {
						return true;
					}

					if (isset( $data['default'] ) && is_array( $data['default'] ) && in_array( $value, $data['default'] )) {
						return true;
					}

				} else {

					if ($value == UM()->form()->post_form[$key]) {
						return true;
					}


				}

			}

			return false;
		}


		/**
		 * Checks if a radio button is selected
		 *
		 * @param  string $key
		 * @param  string $value
		 * @param  array  $data
		 *
		 * @return boolean
		 */
		function is_radio_checked( $key, $value, $data ) {
			if (isset( UM()->form()->post_form[$key] )) {
				if (is_array( UM()->form()->post_form[$key] ) && in_array( $value, UM()->form()->post_form[$key] )) {
					return true;
				} else if ($value == UM()->form()->post_form[$key]) {
					return true;
				}
			} else {
				if (um_user( $key ) && $this->editing == true) {

					if (strstr( $key, 'role_' )) {
						$key = 'role';
					}

					$um_user_value = um_user( $key );

					if ($key == 'role') {
						$um_user_value = strtolower( $um_user_value );

						$role_keys = get_option( 'um_roles' );

						if (!empty( $role_keys )) {
							if (in_array( $um_user_value, $role_keys )) {
								$um_user_value = 'um_' . $um_user_value;
							}
						}
					}

					if ($um_user_value == $value) {
						return true;
					}

					if (is_array( $um_user_value ) && in_array( $value, $um_user_value )) {
						return true;
					}

					if (is_array( $um_user_value )) {
						foreach ($um_user_value as $u) {
							if ($u == html_entity_decode( $value )) {
								return true;
							}
						}
					}


				} else if (isset( $data['default'] ) && $data['default'] == $value) {
					return true;
				}
			}

			return false;
		}


		/**
		 * Get field icon
		 *
		 * @param  string $key
		 *
		 * @return string
		 */
		function get_field_icon( $key ) {
			$fields = UM()->builtin()->all_user_fields;
			if (isset( $fields[$key]['icon'] ))
				return $fields[$key]['icon'];

			return '';
		}


		/**
		 * Gets selected option value from a callback function
		 *
		 * @param  string $value
		 * @param  array  $data
		 * @param  string $type
		 *
		 * @return json
		 */
		function get_option_value_from_callback( $value, $data, $type ) {

			if ( in_array( $type, array( 'select', 'multiselect' ) ) && ! empty( $data['custom_dropdown_options_source'] ) ) {

				if ( function_exists( $data['custom_dropdown_options_source'] ) ) {

					$arr_options = call_user_func(
						$data['custom_dropdown_options_source'],
						( ! empty( $data['parent_dropdown_relationship'] ) ? $data['parent_dropdown_relationship'] : '' )
					);


					if ( $type == 'select' ) {
						if ( ! empty( $arr_options[ $value ] ) ) {
							return $arr_options[ $value ];
						} elseif ( ! empty( $data['default'] ) && empty( $arr_options[ $value ] ) ) {
							return $arr_options[ $data['default'] ];
						} else {
							return '';
						}
					}

					if ( $type == 'multiselect' ) {

						if ( is_array( $value ) ) {
							$values = $value;
						} else {
							$values = explode( ', ', $value );
						}

						$arr_paired_options = array();

						foreach ( $values as $option ) {
							if ( isset( $arr_options[ $option ] ) ) {
								$arr_paired_options[] = $arr_options[ $option ];
							}
						}

						return implode( ', ', $arr_paired_options );
					}

				}


			}

			return $value;
		}


		/**
		 * Get select options from a callback function
		 *
		 * @param  array  $data
		 * @param  string $type
		 *
		 * @return array $arr_options
		 */
		function get_options_from_callback( $data, $type ) {

			if ( in_array( $type, array( 'select', 'multiselect' ) ) && isset( $data['custom_dropdown_options_source'] ) && ! empty( $data['custom_dropdown_options_source'] ) ) {

				$arr_options = call_user_func( $data['custom_dropdown_options_source'], $data['parent_dropdown_relationship'] );


			}

			return $arr_options;
		}


		/**
		 * Get field type
		 *
		 * @param  string $key
		 *
		 * @return string
		 */
		function get_field_type( $key ) {
			$fields = UM()->builtin()->all_user_fields;
			if (isset( $fields[$key]['type'] ))
				return $fields[$key]['type'];

			return '';
		}


		/**
		 * Get field label
		 *
		 * @param  string $key
		 *
		 * @return string
		 */
		function get_label( $key ) {
			$fields = UM()->builtin()->all_user_fields;
			if (isset( $fields[$key]['label'] ))
				return $fields[$key]['label'];
			if (isset( $fields[$key]['title'] ))
				return $fields[$key]['title'];

			return '';
		}


		/**
		 * Get field title
		 *
		 * @param  string $key
		 *
		 * @return string
		 */
		function get_field_title( $key ) {
			$fields = UM()->builtin()->all_user_fields;
			if (isset( $fields[$key]['title'] ))
				return $fields[$key]['title'];
			if (isset( $fields[$key]['label'] ))
				return $fields[$key]['label'];

			return __( 'Custom Field', 'ultimate-member' );
		}


		/**
		 * Get form fields
		 *
		 * @return array
		 */
		function get_fields() {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_get_form_fields
			 * @description Extend form fields
			 * @input_vars
			 * [{"var":"$fields","type":"array","desc":"Selected filter value"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_get_form_fields', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_get_form_fields', 'my_form_fields', 10, 1 );
			 * function my_form_fields( $fields ) {
			 *     // your code here
			 *     return $fields;
			 * }
			 * ?>
			 */
			$this->fields = apply_filters( 'um_get_form_fields', array() );
			return $this->fields;
		}


		/**
		 * Get specific field
		 *
		 * @param  string $key
		 *
		 * @return array
		 */
		function get_field( $key ) {
			$fields = $this->get_fields();

			if (isset( $fields ) && is_array( $fields ) && isset( $fields[$key] )) {
				$array = $fields[$key];
			} else {
				if (!isset( UM()->builtin()->predefined_fields[$key] ) && !isset( UM()->builtin()->all_user_fields[$key] )) {
					return '';
				}
				$array = ( isset( UM()->builtin()->predefined_fields[$key] ) ) ? UM()->builtin()->predefined_fields[$key] : UM()->builtin()->all_user_fields[$key];
			}

			$array['classes'] = null;

			if (!isset( $array['placeholder'] )) $array['placeholder'] = null;
			if (!isset( $array['required'] )) $array['required'] = null;
			if (!isset( $array['validate'] )) $array['validate'] = null;
			if (!isset( $array['default'] )) $array['default'] = null;

			if (isset( $array['conditions'] ) && is_array( $array['conditions'] ) && !$this->viewing) {
				$array['conditional'] = '';

				foreach ($array['conditions'] as $cond_id => $cond) {
					$array['conditional'] .= ' data-cond-' . $cond_id . '-action="' . $cond[0] . '" data-cond-' . $cond_id . '-field="' . $cond[1] . '" data-cond-' . $cond_id . '-operator="' . $cond[2] . '" data-cond-' . $cond_id . '-value="' . $cond[3] . '"';
				}

				$array['classes'] .= ' um-is-conditional';

			} else {
				$array['conditional'] = null;
			}

			$array['classes'] .= ' um-field-' . $key;
			$array['classes'] .= ' um-field-' . $array['type'];
			$array['classes'] .= ' um-field-type_' . $array['type'];

			switch ($array['type']) {

				case 'googlemap':
				case 'youtube_video':
				case 'vimeo_video':
				case 'soundcloud_track':
					$array['disabled'] = '';
					$array['input'] = 'text';
					break;

				case 'text':

					$array['disabled'] = '';

					if ($key == 'user_login' && isset( $this->set_mode ) && $this->set_mode == 'account') {
						$array['disabled'] = 'disabled="disabled"';
					}

					$array['input'] = 'text';

					break;

				case 'password':

					$array['input'] = 'password';

					break;

				case 'number':

					$array['disabled'] = '';

					break;

				case 'url':

					$array['input'] = 'text';

					break;

				case 'date':

					$array['input'] = 'text';

					if (!isset( $array['format'] )) $array['format'] = 'j M Y';

					switch ($array['format']) {
						case 'j M Y':
							$js_format = 'd mmm yyyy';
							break;
						case 'j F Y':
							$js_format = 'd mmmm yyyy';
							break;
						case 'M j Y':
							$js_format = 'mmm d yyyy';
							break;
						case 'F j Y':
							$js_format = 'mmmm d yyyy';
							break;
					}

					$array['js_format'] = $js_format;

					if (!isset( $array['range'] )) $array['range'] = 'years';
					if (!isset( $array['years'] )) $array['years'] = 100;
					if (!isset( $array['years_x'] )) $array['years_x'] = 'past';
					if (!isset( $array['disabled_weekdays'] )) $array['disabled_weekdays'] = '';

					if (!empty( $array['disabled_weekdays'] )) {
						$array['disabled_weekdays'] = '[' . implode( ',', $array['disabled_weekdays'] ) . ']';
					}

					// When date range is strictly defined
					if ($array['range'] == 'date_range') {

						$array['date_min'] = str_replace( '/', ',', $array['range_start'] );
						$array['date_max'] = str_replace( '/', ',', $array['range_end'] );

					} else {

						if ($array['years_x'] == 'past') {

							$date = new \DateTime( date( 'Y-n-d' ) );
							$past = $date->modify( '-' . $array['years'] . ' years' );
							$past = $date->format( 'Y,n,d' );

							$array['date_min'] = $past;
							$array['date_max'] = date( 'Y,n,d' );

						} else if ($array['years_x'] == 'future') {

							$date = new \DateTime( date( 'Y-n-d' ) );
							$future = $date->modify( '+' . $array['years'] . ' years' );
							$future = $date->format( 'Y,n,d' );

							$array['date_min'] = date( 'Y,n,d' );
							$array['date_max'] = $future;

						} else {

							$date = new \DateTime( date( 'Y-n-d' ) );
							$date_f = new \DateTime( date( 'Y-n-d' ) );
							$past = $date->modify( '-' . ( $array['years'] / 2 ) . ' years' );
							$past = $date->format( 'Y,n,d' );
							$future = $date_f->modify( '+' . ( $array['years'] / 2 ) . ' years' );
							$future = $date_f->format( 'Y,n,d' );

							$array['date_min'] = $past;
							$array['date_max'] = $future;

						}

					}

					break;

				case 'time':

					$array['input'] = 'text';

					if (!isset( $array['format'] )) $array['format'] = 'g:i a';

					switch ($array['format']) {
						case 'g:i a':
							$js_format = 'h:i a';
							break;
						case 'g:i A':
							$js_format = 'h:i A';
							break;
						case 'H:i':
							$js_format = 'HH:i';
							break;
					}

					$array['js_format'] = $js_format;

					if (!isset( $array['intervals'] )) $array['intervals'] = 60;

					break;

				case 'textarea':

					if (!isset( $array['height'] )) $array['height'] = '100px';

					break;

				case 'rating':

					if (!isset( $array['number'] )) $array['number'] = 5;

					break;

				case 'spacing':

					if (!isset( $array['spacing'] )) {
						$array['spacing'] = '20px';
					}

					break;

				case 'divider':

					if (isset( $array['width'] )) {
						$array['borderwidth'] = $array['width'];
					} else {
						$array['borderwidth'] = 4;
					}

					if (isset( $array['color'] )) {
						$array['bordercolor'] = $array['color'];
					} else {
						$array['bordercolor'] = '#eee';
					}

					if (isset( $array['style'] )) {
						$array['borderstyle'] = $array['style'];
					} else {
						$array['borderstyle'] = 'solid';
					}

					if (!isset( $array['divider_text'] )) {
						$array['divider_text'] = '';
					}

					break;

				case 'image':

					if (!isset( $array['crop'] )) $array['crop'] = 0;

					if ($array['crop'] == 0) {
						$array['crop_data'] = 0;
					} else if ($array['crop'] == 1) {
						$array['crop_data'] = 'square';
					} else if ($array['crop'] == 2) {
						$array['crop_data'] = 'cover';
					} else {
						$array['crop_data'] = 'user';
					}

					if (!isset( $array['modal_size'] )) $array['modal_size'] = 'normal';

					if ($array['crop'] > 0) {
						$array['crop_class'] = 'crop';
					} else {
						$array['crop_class'] = '';
					}

					if (!isset( $array['ratio'] )) $array['ratio'] = 1.0;

					if (!isset( $array['min_width'] )) $array['min_width'] = '';
					if (!isset( $array['min_height'] )) $array['min_height'] = '';

					if ($array['min_width'] == '' && $array['crop'] == 1) $array['min_width'] = 600;
					if ($array['min_height'] == '' && $array['crop'] == 1) $array['min_height'] = 600;

					if ($array['min_width'] == '' && $array['crop'] == 3) $array['min_width'] = 600;
					if ($array['min_height'] == '' && $array['crop'] == 3) $array['min_height'] = 600;

					if (!isset( $array['invalid_image'] )) $array['invalid_image'] = __( "Please upload a valid image!", 'ultimate-member' );
					if (!isset( $array['allowed_types'] )) {
						$array['allowed_types'] = "gif,jpg,jpeg,png";
					} else {
						$array['allowed_types'] = implode( ',', $array['allowed_types'] );
					}
					if (!isset( $array['upload_text'] )) $array['upload_text'] = '';
					if (!isset( $array['button_text'] )) $array['button_text'] = __( 'Upload', 'ultimate-member' );
					if (!isset( $array['extension_error'] )) $array['extension_error'] = __( "Sorry this is not a valid image.", 'ultimate-member' );
					if (!isset( $array['max_size_error'] )) $array['max_size_error'] = __( "This image is too large!", 'ultimate-member' );
					if (!isset( $array['min_size_error'] )) $array['min_size_error'] = __( "This image is too small!", 'ultimate-member' );
					if (!isset( $array['max_files_error'] )) $array['max_files_error'] = __( "You can only upload one image", 'ultimate-member' );
					if (!isset( $array['max_size'] )) $array['max_size'] = 999999999;
					if (!isset( $array['upload_help_text'] )) $array['upload_help_text'] = '';
					if (!isset( $array['icon'] )) $array['icon'] = '';

					break;

				case 'file':

					if (!isset( $array['modal_size'] )) $array['modal_size'] = 'normal';

					if (!isset( $array['allowed_types'] )) {
						$array['allowed_types'] = "pdf,txt";
					} else {
						$array['allowed_types'] = implode( ',', $array['allowed_types'] );
					}
					if (!isset( $array['upload_text'] )) $array['upload_text'] = '';
					if (!isset( $array['button_text'] )) $array['button_text'] = __( 'Upload', 'ultimate-member' );
					if (!isset( $array['extension_error'] )) $array['extension_error'] = "Sorry this is not a valid file.";
					if (!isset( $array['max_size_error'] )) $array['max_size_error'] = "This file is too large!";
					if (!isset( $array['min_size_error'] )) $array['min_size_error'] = "This file is too small!";
					if (!isset( $array['max_files_error'] )) $array['max_files_error'] = "You can only upload one file";
					if (!isset( $array['max_size'] )) $array['max_size'] = 999999999;
					if (!isset( $array['upload_help_text'] )) $array['upload_help_text'] = '';
					if (!isset( $array['icon'] )) $array['icon'] = '';

					break;

				case 'select':

					break;

				case 'multiselect':

					break;

				case 'group':

					if (!isset( $array['max_entries'] )) $array['max_entries'] = 0;

					break;

			}

			if (!isset( $array['visibility'] )) $array['visibility'] = 'all';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_get_field__{$key}
			 * @description Extend field data by field $key
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"Field Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_get_field__{$key}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_get_field__{$key}', 'my_get_field', 10, 1 );
			 * function my_get_field( $data ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			$array = apply_filters( "um_get_field__{$key}", $array );

			return $array;
		}


		/**
		 * @param $option_value
		 *
		 * @return mixed|void
		 */
		function filter_field_non_utf8_value( $option_value ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_field_non_utf8_value
			 * @description Change dropdown option text
			 * @input_vars
			 * [{"var":"$value","type":"string","desc":"Option Value"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_field_non_utf8_value', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_field_non_utf8_value', 'my_field_non_utf8_value', 10, 1 );
			 * function my_field_non_utf8_value( $value ) {
			 *     // your code here
			 *     return $value;
			 * }
			 * ?>
			 */
			return apply_filters( 'um_field_non_utf8_value', $option_value );
		}


		/**
		 * Gets a field in 'input mode'
		 *
		 * @param  string  $key
		 * @param  array   $data
		 * @param  boolean $rule
		 *
		 * @return string
		 */
		function edit_field( $key, $data, $rule = false ) {
			global $_um_profile_id;
			$output = null;
			$disabled = '';
			if (empty( $_um_profile_id )) {
				$_um_profile_id = um_user( 'ID' );
			}


			// get whole field data
			if (isset( $data ) && is_array( $data )) {
				$data = $this->get_field( $key );
				if (is_array( $data )) {
					/**
					 * @var string      $in_row
					 * @var boolean     $in_sub_row
					 * @var boolean     $in_column
					 * @var string      $type
					 * @var string      $metakey
					 * @var int         $position
					 * @var string      $title
					 * @var string      $help
					 * @var array       $options
					 * @var string      $visibility
					 * @var string      $label
					 * @var string      $placeholder
					 * @var boolean     $public
					 * @var boolean     $editable
					 * @var string      $icon
					 * @var boolean     $in_group
					 * @var string      $classes
					 * @var boolean     $required
					 * @var string      $validate
					 * @var string      $default
                     * @var string      $conditional
					 */
					extract( $data );
				}
			}

			if (!isset( $data['type'] )) return;

			if (isset( $data['in_group'] ) && $data['in_group'] != '' && $rule != 'group') return;

			if ($visibility == 'view' && $this->set_mode != 'register') return;

			if (( $visibility == 'view' && $this->set_mode == 'register' ) ||
				( isset( $data['editable'] ) && $data['editable'] == 0 && $this->set_mode == 'profile' )
			) {

				um_fetch_user( get_current_user_id() );
				if ( ! um_user( 'can_edit_everyone' ) ) {
					$disabled = ' disabled="disabled" ';
				}

				um_fetch_user( $_um_profile_id );
				if ( isset( $data['public'] ) && $data['public'] == '-2' && $data['roles'] ) {
					$current_user_roles = um_user( 'roles' );
					if ( ! empty( $current_user_roles ) && count( array_intersect( $current_user_roles, $data['roles'] ) ) > 0 ) {
						$disabled = '';
					}
				}

			}

			if ( ! isset( $data['autocomplete'] ) ) {
				$autocomplete = 'off';
			}
			um_fetch_user( get_current_user_id() );
			if (!um_can_view_field( $data )) return;
			if (!um_can_edit_field( $data )) return;
			um_fetch_user( $_um_profile_id );

			// fields that need to be disabled in edit mode (profile)
			$arr_restricted_fields = array( 'user_email', 'username', 'user_login', 'user_password' );

			if ( UM()->options()->get( 'editable_primary_email_in_profile' ) == 1 ) {
				unset( $arr_restricted_fields[0] ); // remove user_email
			}

			if (in_array( $key, $arr_restricted_fields ) && $this->editing == true && $this->set_mode == 'profile') {
				return;
			}

			// forbidden in edit mode?
			if (isset( $data['edit_forbidden'] )) return;

			// required option
			if (isset( $data['required_opt'] )) {
				$opt = $data['required_opt'];
				if ( UM()->options()->get( $opt[0] ) != $opt[1]) {
					return;
				}
			}

			// required user permission
			if (isset( $data['required_perm'] )) {
				if (!um_user( $data['required_perm'] )) {
					return;
				}
			}

			// do not show passwords
			if (isset( UM()->user()->preview ) && UM()->user()->preview) {
				if ($data['type'] == 'password') {
					return;
				}
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_hook_for_field_{$type}
			 * @description Change field type
			 * @input_vars
			 * [{"var":"$type","type":"string","desc":"Field Type"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_hook_for_field_{$type}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_hook_for_field_{$type}', 'my_field_type', 10, 1 );
			 * function my_get_field( $type ) {
			 *     // your code here
			 *     return $type;
			 * }
			 * ?>
			 */
			$type = apply_filters( "um_hook_for_field_{$type}", $type );

			/* Begin by field type */
			switch ( $type ) {

				/* Default: Integration */
				default:
					$mode = ( isset( $this->set_mode ) ) ? $this->set_mode : 'no_mode';

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_edit_field_{$mode}_{$type}
					 * @description Change field html by $mode and field $type
					 * @input_vars
					 * [{"var":"$output","type":"string","desc":"Field HTML"},
					 * {"var":"$data","type":"array","desc":"Field Data"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_edit_field_{$mode}_{$type}', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_edit_field_{$mode}_{$type}', 'my_edit_field_html', 10, 2 );
					 * function my_edit_field_html( $output, $data ) {
					 *     // your code here
					 *     return $output;
					 * }
					 * ?>
					 */
					$output .= apply_filters( "um_edit_field_{$mode}_{$type}", $output, $data );
					break;

				/* Other fields */
				case 'googlemap':
				case 'youtube_video':
				case 'vimeo_video':
				case 'soundcloud_track':

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if (isset( $icon ) && $icon && isset( $this->field_icons ) && $this->field_icons == 'field') {

						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

					}

					$field_name = $key . UM()->form()->form_suffix;
					$field_value = htmlspecialchars( $this->field_value( $key, $default, $data ) );

					$output .= '<input ' . $disabled . ' class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $field_name . '" id="' . $field_name . '" value="' . $field_value . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" />

                        </div>';

				if (!empty( $disabled )) {
					$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Text */
				case 'text':

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if (isset( $icon ) && $icon && isset( $this->field_icons ) && $this->field_icons == 'field') {

						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

					}

					$field_name = $key . UM()->form()->form_suffix;
					$field_value = htmlspecialchars( $this->field_value( $key, $default, $data ) );

					$output .= '<input ' . $disabled . ' autocomplete="' . $autocomplete . '" class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $field_name . '" id="' . $field_name . '" value="' . $field_value . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" />

                        </div>';

					if (!empty( $disabled )) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Number */
				case 'number':

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if (isset( $icon ) && $icon && isset( $this->field_icons ) && $this->field_icons == 'field') {

						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

					}

					$number_limit = '';

					if (isset( $min )) {
						$number_limit .= " min=\"{$min}\" ";
					}

					if (isset( $max )) {
						$number_limit .= " max=\"{$max}\" ";
					}

					$output .= '<input ' . $disabled . ' class="' . $this->get_class( $key, $data ) . '" type="number" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . htmlspecialchars( $this->field_value( $key, $default, $data ) ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" '.$number_limit.' />

                        </div>';

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Password */
				case 'password':

					$original_key = $key;

					if ($key == 'single_user_password') {

						$key = $original_key;

						$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

						if (isset( $data['label'] )) {
							$output .= $this->field_label( $label, $key, $data );
						}

						$output .= '<div class="um-field-area">';

						if (isset( $icon ) && $icon && $this->field_icons == 'field') {

							$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

						}

						$output .= '<input class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" />

                            </div>';

						if ($this->is_error( $key )) {
							$output .= $this->field_error( $this->show_error( $key ) );
						}

						$output .= '</div>';

					} else {

						if ($this->set_mode == 'account' || um_is_core_page( 'account' )) {

							$key = 'current_' . $original_key;
							$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

							if (isset( $data['label'] )) {
								$output .= $this->field_label( __( 'Current Password', 'ultimate-member' ), $key, $data );
							}

							$output .= '<div class="um-field-area">';

							if (isset( $icon ) && $icon && $this->field_icons == 'field') {

								$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

							}

							$output .= '<input class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" />

                                </div>';

							if ($this->is_error( $key )) {
								$output .= $this->field_error( $this->show_error( $key ) );
							}

							$output .= '</div>';

						}

						$key = $original_key;

						$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

						if ($this->set_mode == 'account' && um_is_core_page( 'account' ) || $this->set_mode == 'password' && um_is_core_page( 'password-reset' )) {

							$output .= $this->field_label( __( 'New Password', 'ultimate-member' ), $key, $data );

						} else if (isset( $data['label'] )) {

							$output .= $this->field_label( $label, $key, $data );

						}

						$output .= '<div class="um-field-area">';

						if (isset( $icon ) && $icon && $this->field_icons == 'field') {

							$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

						}

						$output .= '<input class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" />

                        </div>';

						if ($this->is_error( $key )) {
							$output .= $this->field_error( $this->show_error( $key ) );
						}

						$output .= '</div>';

						if ($this->set_mode != 'login' && isset( $data['force_confirm_pass'] ) && $data['force_confirm_pass'] == 1) {

							$key = 'confirm_' . $original_key;
							$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

							if (isset( $data['label'] )) {
								$output .= $this->field_label( sprintf( __( 'Confirm %s', 'ultimate-member' ), $data['label'] ), $key, $data );
							}

							$output .= '<div class="um-field-area">';

							if (isset( $icon ) && $icon && $this->field_icons == 'field') {

								$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

							}

							$output .= '<input class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" />

                                </div>';

							if ($this->is_error( $key )) {
								$output .= $this->field_error( $this->show_error( $key ) );
							}

							$output .= '</div>';

						}

					}

					break;

				/* URL */
				case 'url':

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if (isset( $icon ) && $icon && isset( $this->field_icons ) && $this->field_icons == 'field') {

						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

					}

					$output .= '<input  ' . $disabled . '  class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '" />

                        </div>';

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Date */
				case 'date':

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if (isset( $icon ) && $icon && isset( $this->field_icons ) && $this->field_icons == 'field') {

						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

					}

					$output .= '<input  ' . $disabled . '  class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '"    data-range="' . $range . '" data-years="' . $years . '" data-years_x="' . $years_x . '" data-disabled_weekdays="' . $disabled_weekdays . '" data-date_min="' . $date_min . '" data-date_max="' . $date_max . '" data-format="' . $js_format . '" data-value="' . $this->field_value( $key, $default, $data ) . '" />

                        </div>';

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Time */
				case 'time':

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if (isset( $icon ) && $icon && $this->field_icons == 'field') {

						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';

					}

					$output .= '<input  ' . $disabled . '  class="' . $this->get_class( $key, $data ) . '" type="' . $input . '" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" placeholder="' . $placeholder . '" data-validate="' . $validate . '" data-key="' . $key . '"  data-format="' . $js_format . '" data-intervals="' . $intervals . '" data-value="' . $this->field_value( $key, $default, $data ) . '" />

                        </div>';

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Row */
				case 'row':
					$output .= '';
					break;

				/* Textarea */
				case 'textarea':
					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';
					$field_name = $key;
					$field_value = $this->field_value( $key, $default, $data );

					if (isset( $data['html'] ) && $data['html'] != 0 && $key != "description") {


						$textarea_settings = array(
							'media_buttons' => false,
							'wpautop'       => false,
							'editor_class'  => $this->get_class( $key, $data ),
							'editor_height' => $height,
							'tinymce'       => array(
								'toolbar1' => 'formatselect,bullist,numlist,bold,italic,underline,forecolor,blockquote,hr,removeformat,link,unlink,undo,redo',
								'toolbar2' => '',
							)
						);

						if (!empty( $disabled )) {
							$textarea_settings['tinymce']['readonly'] = true;
						}

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_form_fields_textarea_settings
						 * @description Change WP Editor options for textarea init
						 * @input_vars
						 * [{"var":"$textarea_settings","type":"array","desc":"WP Editor settings"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_form_fields_textarea_settings', 'function_name', 10, 1 );
						 * @example
						 * <?php
						 * add_filter( 'um_form_fields_textarea_settings', 'my_textarea_settings', 10, 1 );
						 * function my_edit_field_html( $textarea_settings ) {
						 *     // your code here
						 *     return $textarea_settings;
						 * }
						 * ?>
						 */
						$textarea_settings = apply_filters( 'um_form_fields_textarea_settings', $textarea_settings );

						// turn on the output buffer
						ob_start();

						// echo the editor to the buffer
						wp_editor( $field_value, $key, $textarea_settings );

						// add the contents of the buffer to the output variable
						$output .= ob_get_clean();

					} else $output .= '<textarea  ' . $disabled . '  style="height: ' . $height . ';" class="' . $this->get_class( $key, $data ) . '" name="' . $key . '" id="' . $key . '" placeholder="' . $placeholder . '">' . $field_value . '</textarea>';

					$output .= '
                        </div>';

					if (!empty( $disabled )) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Rating */
				case 'rating':
					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					$output .= '<div class="um-rating um-raty" id="' . $key . '" data-key="' . $key . '" data-number="' . $data['number'] . '" data-score="' . $this->field_value( $key, $default, $data ) . '"></div>';

					$output .= '</div>';

					$output .= '</div>';

					break;

				/* Gap/Space */
				case 'spacing':
					$output .= '<div class="um-field um-field-spacing' . $classes . '"' . $conditional . ' style="height: ' . $spacing . '"></div>';
					break;

				/* A line divider */
				case 'divider':
					$output .= '<div class="um-field um-field-divider' . $classes . '"' . $conditional . ' style="border-bottom: ' . $borderwidth . 'px ' . $borderstyle . ' ' . $bordercolor . '">';
					if ($divider_text) {
						$output .= '<div class="um-field-divider-text"><span>' . $divider_text . '</span></div>';
					}
					$output .= '</div>';
					break;

				/* Single Image Upload */
				case 'image':
					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (in_array( $key, array( 'profile_photo', 'cover_photo' ) )) {
						$field_value = '';
					} else {
						$field_value = $this->field_value( $key, $default, $data );
					}

					$output .= '<input type="hidden" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $field_value . '" />';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$modal_label = ( isset( $data['label'] ) ) ? $data['label'] : __( 'Upload Photo', 'ultimate-member' );

					$output .= '<div class="um-field-area" style="text-align: center">';

					if ($this->field_value( $key, $default, $data )) {

						if (!in_array( $key, array( 'profile_photo', 'cover_photo' ) )) {
							if (isset( $this->set_mode ) && $this->set_mode == 'register') {
								$imgValue = $this->field_value( $key, $default, $data );
							} else {
								$imgValue = um_user_uploads_uri() . $this->field_value( $key, $default, $data );
							}
							$img = '<img src="' . $imgValue . '" alt="" />';
						} else {
							$img = '';
						}

						$output .= '<div class="um-single-image-preview show ' . $crop_class . '" data-crop="' . $crop_data . '" data-key="' . $key . '">
                                <a href="#" class="cancel"><i class="um-icon-close"></i></a>' . $img . '
                            </div><a href="#" data-modal="um_upload_single" data-modal-size="' . $modal_size . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . __( 'Change photo', 'ultimate-member' ) . '</a>';

					} else {

						$output .= '<div class="um-single-image-preview ' . $crop_class . '" data-crop="' . $crop_data . '" data-key="' . $key . '">
                                <a href="#" class="cancel"><i class="um-icon-close"></i></a>
                                <img src="" alt="" />
                            <div class="um-clear"></div></div><a href="#" data-modal="um_upload_single" data-modal-size="' . $modal_size . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . $button_text . '</a>';

					}

					$output .= '</div>';

					/* modal hidden */
					$output .= '<div class="um-modal-hidden-content">';

					$output .= '<div class="um-modal-header"> ' . $modal_label . '</div>';

					$output .= '<div class="um-modal-body">';

					if (isset( $this->set_id )) {
						$set_id = $this->set_id;
						$set_mode = $this->set_mode;
					} else {
						$set_id = 0;
						$set_mode = '';
					}

					$nonce = wp_create_nonce( 'um_upload_nonce-' . $this->timestamp );

					$output .= '<div class="um-single-image-preview ' . $crop_class . '"  data-crop="' . $crop_data . '" data-ratio="' . $ratio . '" data-min_width="' . $min_width . '" data-min_height="' . $min_height . '" data-coord=""><a href="#" class="cancel"><i class="um-icon-close"></i></a><img src="" alt="" /><div class="um-clear"></div></div><div class="um-clear"></div>';
					$output .= '<div class="um-single-image-upload" data-nonce="' . $nonce . '" data-timestamp="' . esc_attr( $this->timestamp ) . '" data-icon="' . esc_attr( $icon ) . '" data-set_id="' . esc_attr( $set_id ) . '" data-set_mode="' . esc_attr( $set_mode ) . '" data-type="' . esc_attr( $type ) . '" data-key="' . esc_attr( $key ) . '" data-max_size="' . esc_attr( $max_size ) . '" data-max_size_error="' . esc_attr( $max_size_error ) . '" data-min_size_error="' . esc_attr( $min_size_error ) . '" data-extension_error="' . esc_attr( $extension_error ) . '"  data-allowed_types="' . esc_attr( $allowed_types ) . '" data-upload_text="' . esc_attr( $upload_text ) . '" data-max_files_error="' . esc_attr( $max_files_error ) . '" data-upload_help_text="' . esc_attr( $upload_help_text ) . '">' . $button_text . '</div>';

					$output .= '<div class="um-modal-footer">
                                    <div class="um-modal-right">
                                        <a href="#" class="um-modal-btn um-finish-upload image disabled" data-key="' . $key . '" data-change="' . __( 'Change photo', 'ultimate-member' ) . '" data-processing="' . __( 'Processing...', 'ultimate-member' ) . '"> ' . __( 'Apply', 'ultimate-member' ) . '</a>
                                        <a href="#" class="um-modal-btn alt" data-action="um_remove_modal"> ' . __( 'Cancel', 'ultimate-member' ) . '</a>
                                    </div>
                                    <div class="um-clear"></div>
                                </div>';

					$output .= '</div>';

					$output .= '</div>';

					/* end */

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';

					break;

				/* Single File Upload */
				case 'file':
					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					$output .= '<input type="hidden" name="' . $key . UM()->form()->form_suffix . '" id="' . $key . UM()->form()->form_suffix . '" value="' . $this->field_value( $key, $default, $data ) . '" />';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$modal_label = ( isset( $data['label'] ) ) ? $data['label'] : __( 'Upload Photo', 'ultimate-member' );

					$output .= '<div class="um-field-area" style="text-align: center">';

					if ($this->field_value( $key, $default, $data )) {

						$extension = pathinfo( $this->field_value( $key, $default, $data ), PATHINFO_EXTENSION );

						$output .= '<div class="um-single-file-preview show" data-key="' . $key . '">
                                        <a href="#" class="cancel"><i class="um-icon-close"></i></a>
                                        <div class="um-single-fileinfo">
                                            <a href="' . um_user_uploads_uri() . $this->field_value( $key, $default, $data ) . '" target="_blank">
                                                <span class="icon" style="background:' . UM()->files()->get_fonticon_bg_by_ext( $extension ) . '"><i class="' . UM()->files()->get_fonticon_by_ext( $extension ) . '"></i></span>
                                                <span class="filename">' . $this->field_value( $key, $default, $data ) . '</span>
                                            </a>
                                        </div>
                            </div><a href="#" data-modal="um_upload_single" data-modal-size="' . $modal_size . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . __( 'Change file', 'ultimate-member' ) . '</a>';

					} else {

						$output .= '<div class="um-single-file-preview" data-key="' . $key . '">
                            </div><a href="#" data-modal="um_upload_single" data-modal-size="' . $modal_size . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . $button_text . '</a>';

					}

					$output .= '</div>';

					/* modal hidden */
					$output .= '<div class="um-modal-hidden-content">';

					$output .= '<div class="um-modal-header"> ' . $modal_label . '</div>';

					$output .= '<div class="um-modal-body">';

					if (isset( $this->set_id )) {
						$set_id = $this->set_id;
						$set_mode = $this->set_mode;
					} else {
						$set_id = 0;
						$set_mode = '';
					}

					$output .= '<div class="um-single-file-preview">
                                        <a href="#" class="cancel"><i class="um-icon-close"></i></a>
                                        <div class="um-single-fileinfo">
                                            <a href="" target="_blank">
                                                <span class="icon"><i></i></span>
                                                <span class="filename"></span>
                                            </a>
                                        </div>
                                </div>';

					$nonce = wp_create_nonce( 'um_upload_nonce-' . $this->timestamp );

					$output .= '<div class="um-single-file-upload" data-timestamp="' . esc_attr( $this->timestamp ) . '" data-nonce="' . $nonce . '" data-icon="' . esc_attr( $icon ) . '" data-set_id="' . esc_attr( $set_id ) . '" data-set_mode="' . esc_attr( $set_mode ) . '" data-type="' . esc_attr( $type ) . '" data-key="' . esc_attr( $key ) . '" data-max_size="' . esc_attr( $max_size ) . '" data-max_size_error="' . esc_attr( $max_size_error ) . '" data-min_size_error="' . esc_attr( $min_size_error ) . '" data-extension_error="' . esc_attr( $extension_error ) . '"  data-allowed_types="' . esc_attr( $allowed_types ) . '" data-upload_text="' . esc_attr( $upload_text ) . '" data-max_files_error="' . esc_attr( $max_files_error ) . '" data-upload_help_text="' . esc_attr( $upload_help_text ) . '">' . $button_text . '</div>';

					$output .= '<div class="um-modal-footer">
                                    <div class="um-modal-right">
                                        <a href="#" class="um-modal-btn um-finish-upload file disabled" data-key="' . $key . '" data-change="' . __( 'Change file' ) . '" data-processing="' . __( 'Processing...', 'ultimate-member' ) . '"> ' . __( 'Save', 'ultimate-member' ) . '</a>
                                        <a href="#" class="um-modal-btn alt" data-action="um_remove_modal"> ' . __( 'Cancel', 'ultimate-member' ) . '</a>
                                    </div>
                                    <div class="um-clear"></div>
                                </div>';

					$output .= '</div>';

					$output .= '</div>';

					/* end */

					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';

					break;

				/* Select dropdown */
				case 'select':

					$form_key = str_replace( 'role_select', 'role', $key );

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';


					if (isset( $data['allowclear'] ) && $data['allowclear'] == 0) {
						$class = 'um-s2';
					} else {
						$class = 'um-s1';
					}

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area ' . ( isset( $this->field_icons ) && $this->field_icons == 'field' ? 'um-field-area-has-icon' : '' ) . ' ">';
					if (isset( $icon ) && $icon && isset( $this->field_icons ) && $this->field_icons == 'field') {
						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';
					}

					$has_parent_option = false;
					$disabled_by_parent_option = '';
					$atts_ajax = '';
					$select_original_option_value = '';

					if (isset( $data['parent_dropdown_relationship'] ) && !empty( $data['parent_dropdown_relationship'] ) && !UM()->user()->preview) {

						$disabled_by_parent_option = 'disabled = disabled';

						$has_parent_option = true;

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_custom_dropdown_options_parent__{$form_key}
						 * @description Change parent dropdown relationship by $form_key
						 * @input_vars
						 * [{"var":"$parent","type":"string","desc":"Parent dropdown relationship"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_custom_dropdown_options_parent__{$form_key}', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_filter( 'um_custom_dropdown_options_parent__{$form_key}', 'my_custom_dropdown_options_parent', 10, 2 );
						 * function my_custom_dropdown_options_parent( $parent, $data ) {
						 *     // your code here
						 *     return $parent;
						 * }
						 * ?>
						 */
						$parent_dropdown_relationship = apply_filters( "um_custom_dropdown_options_parent__{$form_key}", $data['parent_dropdown_relationship'], $data );
						$atts_ajax .= " data-um-parent='{$parent_dropdown_relationship}' ";

						if (isset( $data['custom_dropdown_options_source'] ) && !empty( $data['custom_dropdown_options_source'] ) &&
							$has_parent_option && function_exists( $data['custom_dropdown_options_source'] ) &&
							um_user( $data['parent_dropdown_relationship'] )
						) {
							$options = call_user_func( $data['custom_dropdown_options_source'], $data['parent_dropdown_relationship'] );
							$disabled_by_parent_option = '';
							if (um_user( $form_key )) {
								$select_original_option_value = " data-um-original-value='" . um_user( $form_key ) . "' ";
							}
						}

					}

					if (!empty( $data['custom_dropdown_options_source'] )) {

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_custom_dropdown_options_source__{$form_key}
						 * @description Change custom dropdown options source by $form_key
						 * @input_vars
						 * [{"var":"$source","type":"string","desc":"Dropdown options source"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_custom_dropdown_options_source__{$form_key}', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_filter( 'um_custom_dropdown_options_source__{$form_key}', 'my_custom_dropdown_options_source', 10, 2 );
						 * function my_custom_dropdown_options_source( $source, $data ) {
						 *     // your code here
						 *     return $source;
						 * }
						 * ?>
						 */
						$ajax_source = apply_filters( "um_custom_dropdown_options_source__{$form_key}", $data['custom_dropdown_options_source'], $data );
						$atts_ajax .= " data-um-ajax-source='{$ajax_source}' ";

						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_custom_dropdown_options_source_url__{$form_key}
						 * @description Change custom dropdown options source URL by $form_key
						 * @input_vars
						 * [{"var":"$url","type":"string","desc":"Dropdown options source URL"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_custom_dropdown_options_source_url__{$form_key}', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_filter( 'um_custom_dropdown_options_source_url__{$form_key}', 'my_custom_dropdown_options_source_url', 10, 2 );
						 * function my_custom_dropdown_options_source( $url, $data ) {
						 *     // your code here
						 *     return $url;
						 * }
						 * ?>
						 */
						$ajax_source_url = apply_filters( "um_custom_dropdown_options_source_url__{$form_key}", admin_url( 'admin-ajax.php' ), $data );
						$atts_ajax .= " data-um-ajax-url='{$ajax_source_url}' ";

					}

					if( ! empty( $placeholder ) ) {
					    $placeholder = strip_tags( $placeholder );
                    }

					$output .= '<select  ' . $disabled . ' ' . $select_original_option_value . ' ' . $disabled_by_parent_option . '  name="' . $form_key . '" id="' . $form_key . '" data-validate="' . $validate . '" data-key="' . $key . '" class="' . $this->get_class( $key, $data, $class ) . '" style="width: 100%" data-placeholder="' . $placeholder . '" ' . $atts_ajax . '>';

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_fields_options_enable_pairs__{$key}
					 * @description Enable options pairs by field $key
					 * @input_vars
					 * [{"var":"$options_pairs","type":"string","desc":"Enable pairs"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_fields_options_enable_pairs__{$key}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_filter( 'um_fields_options_enable_pairs__{$key}', 'my_fields_options_enable_pairs', 10, 1 );
					 * function my_fields_options_enable_pairs( $options_pairs ) {
					 *     // your code here
					 *     return $options_pairs;
					 * }
					 * ?>
					 */
					$enable_options_pair = apply_filters( "um_fields_options_enable_pairs__{$key}", false );

					if( ! $has_parent_option ) {
						if ( isset($options) && $options == 'builtin'){
							$options = UM()->builtin()->get ( $filter );
						}

						if ( ! isset( $options )) {
							$options = UM()->builtin()->get( 'countries' );
						}

						if ( isset( $options ) ) {
							/**
							 * UM hook
							 *
							 * @type filter
							 * @title um_select_dropdown_dynamic_options
							 * @description Extend dropdown dynamic options
							 * @input_vars
							 * [{"var":"$options","type":"array","desc":"Dynamic options"},
							 * {"var":"$data","type":"array","desc":"Field Data"}]
							 * @change_log
							 * ["Since: 2.0"]
							 * @usage add_filter( 'um_select_dropdown_dynamic_options', 'function_name', 10, 2 );
							 * @example
							 * <?php
							 * add_filter( 'um_select_dropdown_dynamic_options', 'my_select_dropdown_dynamic_options', 10, 2 );
							 * function my_select_dropdown_dynamic_options( $options, $data ) {
							 *     // your code here
							 *     return $options;
							 * }
							 * ?>
							 */
							$options = apply_filters( 'um_select_dropdown_dynamic_options', $options, $data );
							/**
							 * UM hook
							 *
							 * @type filter
							 * @title um_select_dropdown_dynamic_options_{$key}
							 * @description Extend dropdown dynamic options by field $key
							 * @input_vars
							 * [{"var":"$options","type":"array","desc":"Dynamic options"}]
							 * @change_log
							 * ["Since: 2.0"]
							 * @usage add_filter( 'um_select_dropdown_dynamic_options_{$key}', 'function_name', 10, 1 );
							 * @example
							 * <?php
							 * add_filter( 'um_select_dropdown_dynamic_options_{$key}', 'my_select_dropdown_dynamic_options', 10, 1 );
							 * function my_select_dropdown_dynamic_options( $options ) {
							 *     // your code here
							 *     return $options;
							 * }
							 * ?>
							 */
							$options = apply_filters( "um_select_dropdown_dynamic_options_{$key}", $options );
						}
					}

					$options = $this->get_available_roles( $form_key, $options );

					// add an empty option!
					$output .= '<option value=""></option>';

					$field_value = '';

					// switch options pair for custom options from a callback function
					if ( ! empty( $data['custom_dropdown_options_source'] ) ) {
						$options_pair = true;
					}

					// add options
					if ( ! empty( $options ) ) {
						foreach ( $options as $k => $v ) {

							$v = rtrim( $v );

							$option_value = $v;
							$um_field_checkbox_item_title = $v;


							if (!is_numeric( $k ) && in_array( $form_key, array( 'role' ) )) {
								$option_value = $k;
								$um_field_checkbox_item_title = $v;
							}

							if (isset( $options_pair )) {
								$option_value = $k;
								$um_field_checkbox_item_title = $v;
							}

							$option_value = $this->filter_field_non_utf8_value( $option_value );

							$output .= '<option value="' . $option_value . '" ';

							if ($this->is_selected( $form_key, $option_value, $data )) {
								$output .= 'selected';
								$field_value = $option_value;
							} else if (!isset( $options_pair ) && $this->is_selected( $form_key, $v, $data )) {
								$output .= 'selected';
								$field_value = $v;
							}

							$output .= '>' . __( $um_field_checkbox_item_title, 'ultimate-member' ) . '</option>';


						}
					}

					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $form_key, $field_value );
					}

					$output .= '</select>';

					$output .= '</div>';


					if ($this->is_error( $form_key )) {
						$output .= $this->field_error( $this->show_error( $form_key ) );
					}

					$output .= '</div>';
					break;

				/* Multi-Select dropdown */
				case 'multiselect':

					$max_selections = ( isset( $max_selections ) ) ? absint( $max_selections ) : 0;

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['allowclear'] ) && $data['allowclear'] == 0) {
						$class = 'um-s2';
					} else {
						$class = 'um-s1';
					}

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$field_icon = false;
					$field_icon_output = '';

					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_multiselect_option_value
					 * @description Change multiselect keyword data
					 * @input_vars
					 * [{"var":"$keyword","type":"int","desc":"Option Value"},
					 * {"var":"$type","type":"string","desc":"Field Type"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_multiselect_option_value', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_multiselect_option_value', 'my_multiselect_option_value', 10, 2 );
					 * function my_multiselect_option_value( $keyword, $type ) {
					 *     // your code here
					 *     return $keyword;
					 * }
					 * ?>
					 */
					$use_keyword = apply_filters( 'um_multiselect_option_value', 0, $data['type'] );

					$output .= '<div class="um-field-area ' . ( isset( $this->field_icons ) && $this->field_icons == 'field' ? 'um-field-area-has-icon' : '' ) . ' ">';
					if (isset( $icon ) && $icon && isset( $this->field_icons ) && $this->field_icons == 'field') {
						$output .= '<div class="um-field-icon"><i class="' . $icon . '"></i></div>';
					}

					$output .= '<select  ' . $disabled . ' multiple="multiple" name="' . $key . '[]" id="' . $key . '" data-maxsize="' . $max_selections . '" data-validate="' . $validate . '" data-key="' . $key . '" class="' . $this->get_class( $key, $data, $class ) . ' um-user-keyword_' . $use_keyword . '" style="width: 100%" data-placeholder="' . $placeholder . '">';


					if ( isset( $options ) && $options == 'builtin' ) {
						$options = UM()->builtin()->get( $filter );
					}

					if ( ! isset( $options ) ) {
						$options = UM()->builtin()->get( 'countries' );
					}

					if ( isset( $options ) ) {
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_multiselect_options
						 * @description Extend multiselect options
						 * @input_vars
						 * [{"var":"$options","type":"array","desc":"Multiselect Options"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_multiselect_options', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_filter( 'um_multiselect_options', 'my_multiselect_options', 10, 2 );
						 * function my_multiselect_options( $options, $data ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * ?>
						 */
						$options = apply_filters( 'um_multiselect_options', $options, $data );
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_multiselect_options_{$key}
						 * @description Extend multiselect options by field $key
						 * @input_vars
						 * [{"var":"$options","type":"array","desc":"Multiselect Options"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_multiselect_options_{$key}', 'function_name', 10, 1 );
						 * @example
						 * <?php
						 * add_filter( 'um_multiselect_options_{$key}', 'my_multiselect_options', 10, 1 );
						 * function my_multiselect_options( $options ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * ?>
						 */
						$options = apply_filters( "um_multiselect_options_{$key}", $options );
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_multiselect_options_{$type}
						 * @description Extend multiselect options by field $type
						 * @input_vars
						 * [{"var":"$options","type":"array","desc":"Multiselect Options"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_multiselect_options_{$type}', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_filter( 'um_multiselect_options_{$type}', 'my_multiselect_options', 10, 2 );
						 * function my_multiselect_option_value( $options, $data ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * ?>
						 */
						$options = apply_filters( "um_multiselect_options_{$data['type']}", $options, $data );
					}

					// switch options pair for custom options from a callback function
					if ( ! empty( $data['custom_dropdown_options_source'] ) ) {
						$use_keyword = true;
					}

					// add an empty option!
					$output .= '<option value=""></option>';

					$arr_selected = array();
					// add options
					if (!empty( $options ) && is_array( $options )) {
						foreach ($options as $k => $v) {

							$v = rtrim( $v );

							$um_field_checkbox_item_title = $v;
							$opt_value = $v;

							if ($use_keyword) {
								$um_field_checkbox_item_title = $v;
								$opt_value = $k;
							}

							$opt_value = $this->filter_field_non_utf8_value( $opt_value );

							$output .= '<option value="' . $opt_value . '" ';
							if ($this->is_selected( $key, $opt_value, $data )) {

								$output .= 'selected';
								$arr_selected[$opt_value] = $opt_value;
							}

							$output .= '>' . __( $um_field_checkbox_item_title, 'ultimate-member' ) . '</option>';

						}
					}

					$output .= '</select>';

					if (!empty( $disabled ) && !empty( $arr_selected )) {
						foreach ($arr_selected as $item) {
							$output .= $this->disabled_hidden_field( $key . '[]', $item );
						}
					}

					$output .= '</div>';


					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* Radio */
				case 'radio':

					$form_key = str_replace( 'role_radio', 'role', $key );

					if ( isset( $options ) ) {
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_radio_field_options
						 * @description Extend radio field options
						 * @input_vars
						 * [{"var":"$options","type":"array","desc":"Radio Field Options"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_radio_field_options', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_filter( 'um_radio_field_options', 'my_radio_field_options', 10, 2 );
						 * function my_radio_field_options( $options, $data ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * ?>
						 */
						$options = apply_filters( 'um_radio_field_options', $options, $data );
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_radio_field_options_{$key}
						 * @description Extend radio field options by field $key
						 * @input_vars
						 * [{"var":"$options","type":"array","desc":"Radio field Options"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_radio_field_options_{$key}', 'function_name', 10, 1 );
						 * @example
						 * <?php
						 * add_filter( 'um_radio_field_options_{$key}', 'my_radio_field_options', 10, 1 );
						 * function my_radio_field_options( $options ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * ?>
						 */
						$options = apply_filters( "um_radio_field_options_{$key}", $options );
					}

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					$options = $this->get_available_roles( $form_key, $options );

					// add options
					$i = 0;
					$field_value = array();

					if ( ! empty( $options ) ) {
						foreach ( $options as $k => $v ) {

							$v = rtrim( $v );

							$um_field_checkbox_item_title = $v;
							$option_value = $v;

							if (!is_numeric( $k ) && in_array( $form_key, array( 'role' ) )) {
								$um_field_checkbox_item_title = $v;
								$option_value = $k;
							}

							$i++;
							if ($i % 2 == 0) {
								$col_class = 'right';
							} else {
								$col_class = '';
							}

							if ($this->is_radio_checked( $key, $option_value, $data )) {
								$active = 'active';
								$class = "um-icon-android-radio-button-on";
							} else {
								$active = '';
								$class = "um-icon-android-radio-button-off";
							}

							if (isset( $data['editable'] ) && $data['editable'] == 0) {
								$col_class .= " um-field-radio-state-disabled";
							}

							$output .= '<label class="um-field-radio ' . $active . ' um-field-half ' . $col_class . '">';

							$option_value = $this->filter_field_non_utf8_value( $option_value );

							$output .= '<input  ' . $disabled . ' type="radio" name="' . ( ( $form_key == 'role' ) ? $form_key : $form_key . '[]' ) . '" value="' . $option_value . '" ';

							if ($this->is_radio_checked( $key, $option_value, $data )) {
								$output .= 'checked';
								$field_value[$key] = $option_value;
							}

							$output .= ' />';

							$output .= '<span class="um-field-radio-state"><i class="' . $class . '"></i></span>';
							$output .= '<span class="um-field-radio-option">' . __( $um_field_checkbox_item_title, 'ultimate-member' ) . '</span>';
							$output .= '</label>';

							if ($i % 2 == 0) {
								$output .= '<div class="um-clear"></div>';
							}

						}
					}

					if (!empty( $disabled )) {
						foreach ($field_value as $item) {
							$output .= $this->disabled_hidden_field( $form_key, $item );
						}
					}

					$output .= '<div class="um-clear"></div>';

					$output .= '</div>';

					if ($this->is_error( $form_key )) {
						$output .= $this->field_error( $this->show_error( $form_key ) );
					}

					$output .= '</div>';
					break;

				/* Checkbox */
				case 'checkbox':

					if ( isset( $options ) ) {
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_checkbox_field_options
						 * @description Extend checkbox options
						 * @input_vars
						 * [{"var":"$options","type":"array","desc":"Checkbox Options"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_checkbox_field_options', 'function_name', 10, 2 );
						 * @example
						 * <?php
						 * add_filter( 'um_checkbox_field_options', 'my_checkbox_options', 10, 2 );
						 * function my_checkbox_options( $options, $data ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * ?>
						 */
						$options = apply_filters( 'um_checkbox_field_options', $options, $data );
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_checkbox_field_options_{$key}
						 * @description Extend checkbox options by field $key
						 * @input_vars
						 * [{"var":"$options","type":"array","desc":"Checkbox Options"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_checkbox_field_options_{$key}', 'function_name', 10, 1 );
						 * @example
						 * <?php
						 * add_filter( 'um_checkbox_field_options_{$key}', 'my_checkbox_options', 10, 1 );
						 * function my_checkbox_options( $options ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * ?>
						 */
						$options = apply_filters( "um_checkbox_field_options_{$key}", $options );
					}

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';

					// add options
					$i = 0;

					foreach ($options as $k => $v) {

						$v = rtrim( $v );

						$i++;
						if ($i % 2 == 0) {
							$col_class = 'right';
						} else {
							$col_class = '';
						}

						if ($this->is_selected( $key, $v, $data )) {
							$active = 'active';
							$class = "um-icon-android-checkbox-outline";
						} else {
							$active = '';
							$class = "um-icon-android-checkbox-outline-blank";
						}

						if (isset( $data['editable'] ) && $data['editable'] == 0) {
							$col_class .= " um-field-radio-state-disabled";
						}

						$output .= '<label class="um-field-checkbox ' . $active . ' um-field-half ' . $col_class . '">';

						$um_field_checkbox_item_title = $v;

						$v = $this->filter_field_non_utf8_value( $v );

						$output .= '<input  ' . $disabled . ' type="checkbox" name="' . $key . '[]" value="' . strip_tags( $v ) . '" ';

						if ($this->is_selected( $key, $v, $data )) {
							$output .= 'checked';
						}

						$output .= ' />';

						if (!empty( $disabled ) && $this->is_selected( $key, $v, $data )) {
							$output .= $this->disabled_hidden_field( $key . '[]', strip_tags( $v ) );
						}


						$output .= '<span class="um-field-checkbox-state"><i class="' . $class . '"></i></span>';
						/**
						 * UM hook
						 *
						 * @type filter
						 * @title um_field_checkbox_item_title
						 * @description Change Checkbox item title
						 * @input_vars
						 * [{"var":"$checkbox_item_title","type":"array","desc":"Item Title"},
						 * {"var":"$key","type":"string","desc":"Field Key"},
						 * {"var":"$value","type":"string","desc":"Field Value"},
						 * {"var":"$data","type":"array","desc":"Field Data"}]
						 * @change_log
						 * ["Since: 2.0"]
						 * @usage add_filter( 'um_field_checkbox_item_title', 'function_name', 10, 4 );
						 * @example
						 * <?php
						 * add_filter( 'um_field_checkbox_item_title', 'my_checkbox_item_title', 10, 4 );
						 * function my_checkbox_item_title( $checkbox_item_title, $key, $value, $data ) {
						 *     // your code here
						 *     return $checkbox_item_title;
						 * }
						 * ?>
						 */
						$um_field_checkbox_item_title = apply_filters( "um_field_checkbox_item_title", $um_field_checkbox_item_title, $key, $v, $data );
						$output .= '<span class="um-field-checkbox-option">' . __( $um_field_checkbox_item_title, 'ultimate-member' ) . '</span>';
						$output .= '</label>';

						if ($i % 2 == 0) {
							$output .= '<div class="um-clear"></div>';
						}

					}

					$output .= '<div class="um-clear"></div>';

					$output .= '</div>';


					if ($this->is_error( $key )) {
						$output .= $this->field_error( $this->show_error( $key ) );
					}

					$output .= '</div>';
					break;

				/* HTML */
				case 'block':
					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">
                                <div class="um-field-block">' . $content . '</div>
                            </div>';
					break;

				/* Shortcode */
				case 'shortcode':

					$content = str_replace( '{profile_id}', um_profile_id(), $content );

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">
                                <div class="um-field-shortcode">' . do_shortcode( $content ) . '</div>
                            </div>';
					break;

				/* Unlimited Group */
				case 'group':

					$fields = $this->get_fields_in_group( $key );
					if (!empty( $fields )) {

						$output .= '<div class="um-field-group" data-max_entries="' . $max_entries . '">
                                <div class="um-field-group-head"><i class="um-icon-plus"></i>' . __( $label, 'ultimate-member' ) . '</div>';
						$output .= '<div class="um-field-group-body"><a href="#" class="um-field-group-cancel"><i class="um-icon-close"></i></a>';

						foreach ($fields as $subkey => $subdata) {
							$output .= $this->edit_field( $subkey, $subdata, 'group' );
						}

						$output .= '</div>';
						$output .= '</div>';

					}

					break;

			}

			// Custom filter for field output
			if ( isset( $this->set_mode ) ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_{$key}_form_edit_field
				 * @description Change field HTML on edit mode by field $key
				 * @input_vars
				 * [{"var":"$output","type":"string","desc":"Field HTML"},
				 * {"var":"$mode","type":"string","desc":"Fields Mode"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_{$key}_form_edit_field', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_{$key}_form_edit_field', 'my_form_edit_field', 10, 2 );
				 * function my_form_edit_field( $output, $mode ) {
				 *     // your code here
				 *     return $output;
				 * }
				 * ?>
				 */
				$output = apply_filters( "um_{$key}_form_edit_field", $output, $this->set_mode );
			}

			return $output;
		}


		/**
		 * Filter for user roles
		 *
		 * @param $form_key
		 * @param array $options
		 * @return array
		 */
		function get_available_roles( $form_key, $options = array() ) {
			if ( $form_key != 'role' ) {
				return $options;
			}

			// role field
			global $wp_roles;
			$role_keys = array_map( function( $item ) {
				return 'um_' . $item;
			}, get_option( 'um_roles', array() ) );
			$exclude_roles = array_diff( array_keys( $wp_roles->roles ), array_merge( $role_keys, array( 'subscriber' ) ) );

			$roles = UM()->roles()->get_roles( false, $exclude_roles );

			if ( ! empty( $options ) ) {
				//fix when customers change options for role (radio/dropdown) fields
				$intersected_options = array();
				foreach ( $options as $option ) {
					if ( false !== $search_key = array_search( $option, $roles ) ) {
						$intersected_options[ $search_key ] = $option;
					} else {
						$intersected_options[] = $option;
					}
				}

				$options = $intersected_options;
			} else {
				$options = $roles;
			}

			return $options;
		}


		/**
		 * Sorts columns array
		 *
		 * @param  array  $arr
		 * @param  string $col
		 * @param  string $dir
		 *
		 * @return array $arr
		 */
		function array_sort_by_column( $arr, $col, $dir = SORT_ASC ) {
			$sort_col = array();
			foreach ($arr as $key => $row) {
				if (isset( $row[$col] )) {
					$sort_col[$key] = $row[$col];
				}
			}

			array_multisort( $sort_col, $dir, $arr );

			return $arr;
		}


		/**
		 * Get fields in row
		 *
		 * @param  integer $row_id
		 *
		 * @return string
		 */
		function get_fields_by_row( $row_id ) {
			foreach ($this->get_fields as $key => $array) {
				if (!isset( $array['in_row'] ) || ( isset( $array['in_row'] ) && $array['in_row'] == $row_id )) {
					$results[$key] = $array;
				}
			}

			return ( isset ( $results ) ) ? $results : '';
		}


		/**
		 * Get fields by sub row
		 *
		 * @param  string  $row_fields
		 * @param  integer $subrow_id
		 *
		 * @return mixed
		 */
		function get_fields_in_subrow( $row_fields, $subrow_id ) {
			if (!is_array( $row_fields )) return '';
			foreach ($row_fields as $key => $array) {
				if (!isset( $array['in_sub_row'] ) || ( isset( $array['in_sub_row'] ) && $array['in_sub_row'] == $subrow_id )) {
					$results[$key] = $array;
				}
			}

			return ( isset ( $results ) ) ? $results : '';
		}


		/**
		 * Get fields in group
		 *
		 * @param  integer $group_id
		 *
		 * @return mixed
		 */
		function get_fields_in_group( $group_id ) {
			foreach ($this->get_fields as $key => $array) {
				if (isset( $array['in_group'] ) && $array['in_group'] == $group_id) {
					$results[$key] = $array;
				}
			}

			return ( isset ( $results ) ) ? $results : '';
		}


		/**
		 * Get fields in column
		 *
		 * @param  array   $fields
		 * @param  integer $col_number
		 *
		 * @return mixed
		 */
		function get_fields_in_column( $fields, $col_number ) {
			foreach ($fields as $key => $array) {
				if (isset( $array['in_column'] ) && $array['in_column'] == $col_number) {
					$results[$key] = $array;
				}
			}

			return ( isset ( $results ) ) ? $results : '';
		}


		/**
		 * Display fields
		 *
		 * @param  string $mode
		 * @param  array  $args
		 *
		 * @return string
		 */
		function display( $mode, $args ) {
			$output = null;

			$this->global_args = $args;

			UM()->form()->form_suffix = '-' . $this->global_args['form_id'];

			$this->set_mode = $mode;
			$this->set_id = $this->global_args['form_id'];

			$this->field_icons = ( isset( $this->global_args['icons'] ) ) ? $this->global_args['icons'] : 'label';

			// start output here
			$this->get_fields = $this->get_fields();

			if (!empty( $this->get_fields )) {

				// find rows
				foreach ($this->get_fields as $key => $array) {
					if ($array['type'] == 'row') {
						$this->rows[$key] = $array;
						unset( $this->get_fields[$key] ); // not needed anymore
					}
				}

				// rows fallback
				if (!isset( $this->rows )) {
					$this->rows = array( '_um_row_1' => array(
						'type'     => 'row',
						'id'       => '_um_row_1',
						'sub_rows' => 1,
						'cols'     => 1
					)
					);
				}

				// master rows
				foreach ($this->rows as $row_id => $row_array) {

					$row_fields = $this->get_fields_by_row( $row_id );
					if ($row_fields) {

						$output .= $this->new_row_output( $row_id, $row_array );

						$sub_rows = ( isset( $row_array['sub_rows'] ) ) ? $row_array['sub_rows'] : 1;
						for ($c = 0; $c < $sub_rows; $c++) {

							// cols
							$cols = ( isset( $row_array['cols'] ) ) ? $row_array['cols'] : 1;
							if (strstr( $cols, ':' )) {
								$col_split = explode( ':', $cols );
							} else {
								$col_split = array( $cols );
							}
							$cols_num = $col_split[$c];

							// sub row fields
							$subrow_fields = null;
							$subrow_fields = $this->get_fields_in_subrow( $row_fields, $c );

							if (is_array( $subrow_fields )) {

								$subrow_fields = $this->array_sort_by_column( $subrow_fields, 'position' );

								if ($cols_num == 1) {

									$output .= '<div class="um-col-1">';
									$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
									if ($col1_fields) {
										foreach ($col1_fields as $key => $data) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div>';

								} else if ($cols_num == 2) {

									$output .= '<div class="um-col-121">';
									$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
									if ($col1_fields) {
										foreach ($col1_fields as $key => $data) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-122">';
									$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
									if ($col2_fields) {
										foreach ($col2_fields as $key => $data) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div><div class="um-clear"></div>';

								} else {

									$output .= '<div class="um-col-131">';
									$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
									if ($col1_fields) {
										foreach ($col1_fields as $key => $data) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-132">';
									$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
									if ($col2_fields) {
										foreach ($col2_fields as $key => $data) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-133">';
									$col3_fields = $this->get_fields_in_column( $subrow_fields, 3 );
									if ($col3_fields) {
										foreach ($col3_fields as $key => $data) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div><div class="um-clear"></div>';

								}

							}

						}

						$output .= '</div>';

					}

				}

			}

			return $output;
		}


		/**
		 * Gets a field in `view mode`
		 *
		 * @param  string  $key
		 * @param  array   $data
		 * @param  boolean $rule
		 *
		 * @return string
		 */
		function view_field( $key, $data, $rule = false ) {
			$output = null;

			// get whole field data
			if (is_array( $data )) {
				$data = $this->get_field( $key );
				extract( $data );
			}

			if (!isset( $data['type'] )) return;

			if (isset( $data['in_group'] ) && $data['in_group'] != '' && $rule != 'group') return;

			if ($visibility == 'edit') return;

			if (in_array( $type, array( 'block', 'shortcode', 'spacing', 'divider', 'group' ) )) {

			} else {

				$_field_value = $this->field_value( $key, $default, $data );

				if ( ! isset($_field_value) || $_field_value == '') return;
			}

			if (!um_can_view_field( $data )) return;

			// disable these fields in profile view only
			if (in_array( $key, array( 'user_password' ) ) && $this->set_mode == 'profile') {
				return;
			}

			if (!um_field_conditions_are_met( $data )) return;

			switch ($type) {

				/* Default */
				default:

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] ) || isset( $data['icon'] ) && !empty( $data['icon'] )) {

						if (!isset( $data['label'] )) $data['label'] = '';

						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$res = $this->field_value( $key, $default, $data );

					if (!empty( $res )) {
						$res = stripslashes( $res );
					}

					$data['is_view_field'] = true;
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_view_field
					 * @description Change field HTML on view mode
					 * @input_vars
					 * [{"var":"$output","type":"string","desc":"Field HTML"},
					 * {"var":"$data","type":"string","desc":"Field Data"},
					 * {"var":"$type","type":"string","desc":"Field Type"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_view_field', 'function_name', 10, 3 );
					 * @example
					 * <?php
					 * add_filter( 'um_view_field', 'my_view_field', 10, 3 );
					 * function my_form_edit_field( $output, $data, $type ) {
					 *     // your code here
					 *     return $output;
					 * }
					 * ?>
					 */
					$res = apply_filters( "um_view_field", $res, $data, $type );
					/**
					 * UM hook
					 *
					 * @type filter
					 * @title um_view_field_value_{$type}
					 * @description Change field HTML on view mode by field type
					 * @input_vars
					 * [{"var":"$output","type":"string","desc":"Field HTML"},
					 * {"var":"$data","type":"string","desc":"Field Data"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_filter( 'um_view_field_value_{$type}', 'function_name', 10, 2 );
					 * @example
					 * <?php
					 * add_filter( 'um_view_field_value_{$type}', 'my_view_field', 10, 2 );
					 * function my_form_edit_field( $output, $data ) {
					 *     // your code here
					 *     return $output;
					 * }
					 * ?>
					 */
					$res = apply_filters( "um_view_field_value_{$type}", $res, $data );

					$output .= '<div class="um-field-area">';
					$output .= '<div class="um-field-value">' . $res . '</div>';
					$output .= '</div>';

					$output .= '</div>';

					break;

				/* HTML */
				case 'block':
					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">
                                <div class="um-field-block">' . $content . '</div>
                            </div>';
					break;

				/* Shortcode */
				case 'shortcode':

					$content = str_replace( '{profile_id}', um_profile_id(), $content );

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">
                                <div class="um-field-shortcode">' . do_shortcode( $content ) . '</div>
                            </div>';
					break;

				/* Gap/Space */
				case 'spacing':
					$output .= '<div class="um-field um-field-spacing' . $classes . '"' . $conditional . ' style="height: ' . $spacing . '"></div>';
					break;

				/* A line divider */
				case 'divider':
					$output .= '<div class="um-field um-field-divider' . $classes . '"' . $conditional . ' style="border-bottom: ' . $borderwidth . 'px ' . $borderstyle . ' ' . $bordercolor . '">';
					if ($divider_text) {
						$output .= '<div class="um-field-divider-text"><span>' . $divider_text . '</span></div>';
					}
					$output .= '</div>';
					break;

				/* Rating */
				case 'rating':

					$output .= '<div class="um-field' . $classes . '"' . $conditional . ' data-key="' . $key . '">';

					if (isset( $data['label'] ) || isset( $data['icon'] ) && !empty( $data['icon'] )) {
						$output .= $this->field_label( $label, $key, $data );
					}

					$output .= '<div class="um-field-area">';
					$output .= '<div class="um-field-value">
                                        <div class="um-rating-readonly um-raty" id="' . $key . '" data-key="' . $key . '" data-number="' . $data['number'] . '" data-score="' . $this->field_value( $key, $default, $data ) . '"></div>
                                    </div>';
					$output .= '</div>';

					$output .= '</div>';

					break;

			}

			// Custom filter for field output
			if ( isset( $this->set_mode ) ) {
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_{$key}_form_show_field
				 * @description Change field HTML by field $key
				 * @input_vars
				 * [{"var":"$output","type":"string","desc":"Field HTML"},
				 * {"var":"$mode","type":"string","desc":"Form Mode"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_{$key}_form_show_field', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_{$key}_form_show_field', 'my_form_show_field', 10, 2 );
				 * function my_form_show_field( $output, $mode ) {
				 *     // your code here
				 *     return $output;
				 * }
				 * ?>
				 */
				$output = apply_filters( "um_{$key}_form_show_field", $output, $this->set_mode );
				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_{$type}_form_show_field
				 * @description Change field HTML by field $type
				 * @input_vars
				 * [{"var":"$output","type":"string","desc":"Field HTML"},
				 * {"var":"$mode","type":"string","desc":"Form Mode"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_{$type}_form_show_field', 'function_name', 10, 2 );
				 * @example
				 * <?php
				 * add_filter( 'um_{$type}_form_show_field', 'my_form_show_field', 10, 2 );
				 * function my_form_show_field( $output, $mode ) {
				 *     // your code here
				 *     return $output;
				 * }
				 * ?>
				 */
				$output = apply_filters( "um_{$type}_form_show_field", $output, $this->set_mode );
			}

			return $output;
		}


		/**
		 * Filter field data
		 *
		 * @param array $data
		 *
		 * @return array
		 */
		function view_field_output( $data ) {
			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_view_field_output_{$type}
			 * @description Change field data output by $type
			 * @input_vars
			 * [{"var":"$data","type":"array","desc":"Field Data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_view_field_output_{$type}', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_view_field_output_{$type}', 'my_view_field_output', 10, 1 );
			 * function my_view_field_output( $data ) {
			 *     // your code here
			 *     return $data;
			 * }
			 * ?>
			 */
			return apply_filters( "um_view_field_output_" . $data['type'], $data );
		}


		/**
		 * Display fields ( view mode )
		 *
		 * @param  string $mode
		 * @param  array  $args
		 *
		 * @return string
		 */
		function display_view( $mode, $args ) {
			$output = null;

			$this->global_args = $args;

			UM()->form()->form_suffix = '-' . $this->global_args['form_id'];

			$this->set_mode = $mode;
			$this->set_id = $this->global_args['form_id'];

			$this->field_icons = ( isset( $this->global_args['icons'] ) ) ? $this->global_args['icons'] : 'label';

			// start output here
			$this->get_fields = $this->get_fields();

			if ( UM()->options()->get( 'profile_empty_text' ) ) {

				$emo = UM()->options()->get( 'profile_empty_text_emo' );
				if ($emo) {
					$emo = '<i class="um-faicon-frown-o"></i>';
				} else {
					$emo = false;
				}

				if (um_is_myprofile()) {
					$output .= '<p class="um-profile-note">' . $emo . '<span>' . sprintf( __( 'Your profile is looking a little empty. Why not <a href="%s">add</a> some information!', 'ultimate-member' ), um_edit_profile_url() ) . '</span></p>';
				} else {
					$output .= '<p class="um-profile-note">' . $emo . '<span>' . __( 'This user has not added any information to their profile yet.', 'ultimate-member' ) . '</span></p>';
				}
			}

			if (!empty( $this->get_fields )) {

				// find rows
				foreach ($this->get_fields as $key => $array) {
					if ($array['type'] == 'row') {
						$this->rows[$key] = $array;
						unset( $this->get_fields[$key] ); // not needed anymore
					}
				}

				// rows fallback
				if (!isset( $this->rows )) {
					$this->rows = array( '_um_row_1' => array(
						'type'     => 'row',
						'id'       => '_um_row_1',
						'sub_rows' => 1,
						'cols'     => 1
					)
					);
				}

				// master rows
				foreach ($this->rows as $row_id => $row_array) {

					$row_fields = $this->get_fields_by_row( $row_id );

					if ($row_fields) {

						$output .= $this->new_row_output( $row_id, $row_array );

						$sub_rows = ( isset( $row_array['sub_rows'] ) ) ? $row_array['sub_rows'] : 1;
						for ($c = 0; $c < $sub_rows; $c++) {

							// cols
							$cols = ( isset( $row_array['cols'] ) ) ? $row_array['cols'] : 1;
							if (strstr( $cols, ':' )) {
								$col_split = explode( ':', $cols );
							} else {
								$col_split = array( $cols );
							}
							$cols_num = $col_split[$c];

							// sub row fields
							$subrow_fields = null;
							$subrow_fields = $this->get_fields_in_subrow( $row_fields, $c );

							if (is_array( $subrow_fields )) {

								$subrow_fields = $this->array_sort_by_column( $subrow_fields, 'position' );

								if ($cols_num == 1) {

									$output .= '<div class="um-col-1">';
									$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
									if ( $col1_fields ) {
										foreach ( $col1_fields as $key => $data ) {

											$data = $this->view_field_output( $data );
											$output .= $this->view_field( $key, $data );

										}
									}
									$output .= '</div>';

								} elseif ( $cols_num == 2 ) {

									$output .= '<div class="um-col-121">';
									$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
									if ( $col1_fields ) {
										foreach ( $col1_fields as $key => $data ) {

											$data = $this->view_field_output( $data );
											$output .= $this->view_field( $key, $data );

										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-122">';
									$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
									if ( $col2_fields ) {
										foreach ( $col2_fields as $key => $data ) {

											$data = $this->view_field_output( $data );
											$output .= $this->view_field( $key, $data );

										}
									}
									$output .= '</div><div class="um-clear"></div>';

								} else {

									$output .= '<div class="um-col-131">';
									$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
									if ( $col1_fields ) {
										foreach ( $col1_fields as $key => $data ) {

											$data = $this->view_field_output( $data );
											$output .= $this->view_field( $key, $data );

										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-132">';
									$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
									if ( $col2_fields ) {
										foreach ( $col2_fields as $key => $data ) {

											$data = $this->view_field_output( $data );
											$output .= $this->view_field( $key, $data );

										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-133">';
									$col3_fields = $this->get_fields_in_column( $subrow_fields, 3 );
									if ( $col3_fields ) {
										foreach ( $col3_fields as $key => $data ) {

											$data = $this->view_field_output( $data );
											$output .= $this->view_field( $key, $data );

										}
									}
									$output .= '</div><div class="um-clear"></div>';

								}

							}

						}

						$output .= '</div>';

					}

				}

			}

			return $output;
		}


		/**
		 * Get new row in form
		 *
		 * @param  string $row_id
		 * @param  array  $row_array
		 *
		 * @return array
		 */
		function new_row_output( $row_id, $row_array ) {
			$output = null;
			extract( $row_array );

			$padding = ( isset( $padding ) ) ? $padding : '';
			$margin = ( isset( $margin ) ) ? $margin : '';
			$background = ( isset( $background ) ) ? $background : '';
			$text_color = ( isset( $text_color ) ) ? $text_color : '';
			$borderradius = ( isset( $borderradius ) ) ? $borderradius : '';
			$border = ( isset( $border ) ) ? $border : '';
			$bordercolor = ( isset( $bordercolor ) ) ? $bordercolor : '';
			$borderstyle = ( isset( $borderstyle ) ) ? $borderstyle : '';
			$heading = ( isset( $heading ) ) ? $heading : '';
			$css_class = ( isset( $css_class ) ) ? $css_class : '';

			$css_padding = '';
			$css_margin = '';
			$css_background = '';
			$css_borderradius = '';
			$css_border = '';
			$css_bordercolor = '';
			$css_borderstyle = '';
			$css_heading_background_color = '';
			$css_heading_padding = '';
			$css_heading_text_color = '';
			$css_heading_borderradius = '';
			$css_text_color = '';

			// row css rules
			if ($padding) $css_padding = 'padding: ' . $padding . ';';
			if ($margin) {
				$css_margin = 'margin: ' . $margin . ';';
			} else {
				$css_margin = 'margin: 0 0 30px 0;';
			}

			if ($background) $css_background = 'background-color: ' . $background . ';';
			if ($borderradius) $css_borderradius = 'border-radius: 0px 0px ' . $borderradius . ' ' . $borderradius . ';';
			if ($border) $css_border = 'border-width: ' . $border . ';';
			if ($bordercolor) $css_bordercolor = 'border-color: ' . $bordercolor . ';';
			if ($borderstyle) $css_borderstyle = 'border-style: ' . $borderstyle . ';';
			if ($text_color) $css_text_color = 'color: ' . $text_color . ' !important;';

			// show the heading
			if ($heading) {

				$heading_background_color = ( isset( $heading_background_color ) ) ? $heading_background_color : '';
				$heading_text_color = ( isset( $heading_text_color ) ) ? $heading_text_color : '';

				if ($heading_background_color) {
					$css_heading_background_color = 'background-color: ' . $heading_background_color . ';';
					$css_heading_padding = 'padding: 10px 15px;';
				}

				if ($heading_text_color) $css_heading_text_color = 'color: ' . $heading_text_color . ';';
				if ($borderradius) $css_heading_borderradius = 'border-radius: ' . $borderradius . ' ' . $borderradius . ' 0px 0px;';

				$output .= '<div class="um-row-heading" style="' . $css_heading_background_color . $css_heading_padding . $css_heading_text_color . $css_heading_borderradius . '">';

				if (isset( $icon )) {
					$output .= '<span class="um-row-heading-icon"><i class="' . $icon . '"></i></span>';
				}

				$output .= ( !empty( $heading_text ) ? $heading_text : '' ) . '</div>';

			} else {

				// no heading
				if ($borderradius) $css_borderradius = 'border-radius: ' . $borderradius . ';';

			}

			$output .= '<div class="um-row ' . $row_id . ' ' . $css_class . '" style="' . $css_padding . $css_background . $css_margin . $css_border . $css_borderstyle . $css_bordercolor . $css_borderradius . $css_text_color . '">';

			return $output;
		}


		/**
		 *
		 */
		function do_ajax_action() {
			if (!is_user_logged_in() || !current_user_can( 'manage_options' )) die( __( 'Please login as administrator', 'ultimate-member' ) );

			extract( $_POST );

			$output = null;

			$position = array();
			if (!empty( $in_column )) {
				$position['in_row'] = '_um_row_' . ( (int)$in_row + 1 );
				$position['in_sub_row'] = $in_sub_row;
				$position['in_column'] = $in_column;
				$position['in_group'] = $in_group;
			}

			switch ($act_id) {

				case 'um_admin_duplicate_field':
					UM()->fields()->duplicate_field( $arg1, $arg2 );
					break;

				case 'um_admin_remove_field_global':
					UM()->fields()->delete_field_from_db( $arg1 );
					break;

				case 'um_admin_remove_field':
					UM()->fields()->delete_field_from_form( $arg1, $arg2 );
					break;

				case 'um_admin_add_field_from_predefined':
					UM()->fields()->add_field_from_predefined( $arg1, $arg2, $position );
					break;

				case 'um_admin_add_field_from_list':
					UM()->fields()->add_field_from_list( $arg1, $arg2, $position );
					break;

			}

			if (is_array( $output )) {
				print_r( $output );
			} else {
				echo $output;
			}
			die;

		}
	}
}