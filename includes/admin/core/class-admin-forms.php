<?php
/**
 * Admin forms and fields
 *
 * @package um\admin\core
 */

namespace um\admin\core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\core\Admin_Forms' ) ) {


	/**
	 * Class Admin_Forms
	 */
	class Admin_Forms {


		/**
		 * Form fields and settings.
		 *
		 * @var array|bool
		 */
		public $form_data;


		/**
		 * Class constructor
		 *
		 * @param array|bool $form_data  Form fields and settings.
		 */
		public function __construct( $form_data = false ) {
			if ( $form_data ) {
				$this->form_data = $form_data;
			}
		}


		/**
		 * Set the form data
		 *
		 * @param  array $data  Form fields and settings.
		 *
		 * @return $this
		 */
		public function set_data( $data ) {
			$this->form_data = $data;
			return $this;
		}


		/**
		 * Render admin form
		 *
		 * @param  bool $echo  Return a form if TRUE.
		 *
		 * @return string
		 */
		public function render_form( $echo = true ) {
			if ( empty( $this->form_data['fields'] ) ) {
				return '';
			}

			$class      = 'form-table um-form-table ' . ( empty( $this->form_data['class'] ) ? '' : $this->form_data['class'] );
			$class_attr = ' class="' . esc_attr( $class ) . '" ';

			$html = '';

			foreach ( $this->form_data['fields'] as $field_data ) {
				if ( isset( $field_data['type'] ) && 'hidden' === $field_data['type'] ) {
					$html .= $this->render_form_row( $field_data );
				}
			}

			if ( empty( $this->form_data['without_wrapper'] ) ) {
				$html .= '<table ' . $class_attr . '><tbody>';
			}

			foreach ( $this->form_data['fields'] as $field_data ) {
				if ( isset( $field_data['type'] ) && 'hidden' !== $field_data['type'] ) {
					$html .= $this->render_form_row( $field_data );
				}
			}

			if ( empty( $this->form_data['without_wrapper'] ) ) {
				$html .= '</tbody></table>';
			}

			if ( $echo ) {
				echo $html;
			} else {
				return $html;
			}
		}


		/**
		 * Render admin form row
		 *
		 * @param  array $data  Field data.
		 *
		 * @return string
		 */
		public function render_form_row( $data ) {
			if ( empty( $data['type'] ) ) {
				return '';
			}

			if ( ! empty( $data['value'] ) && 'email_template' !== $data['type'] ) {
				$data['value'] = wp_unslash( $data['value'] );

				// for multi_text.
				if ( ! is_array( $data['value'] ) && 'wp_editor' !== $data['type'] ) {
					$data['value'] = esc_attr( $data['value'] );
				}
			}

			$conditional = ! empty( $data['conditional'] ) ? 'data-conditional="' . esc_attr( wp_json_encode( $data['conditional'] ) ) . '"' : '';
			$prefix_attr = ! empty( $this->form_data['prefix_id'] ) ? ' data-prefix="' . esc_attr( $this->form_data['prefix_id'] ) . '" ' : '';

			$type_attr = ' data-field_type="' . esc_attr( $data['type'] ) . '" ';

			$html = '';
			if ( 'hidden' !== $data['type'] ) {

				if ( ! empty( $this->form_data['div_line'] ) ) {

					if ( strpos( $this->form_data['class'], 'um-top-label' ) !== false ) {

						$html .= '<div class="form-field um-forms-line" ' . $conditional . $prefix_attr . $type_attr . '>' . $this->render_field_label( $data );

						if ( method_exists( $this, 'render_' . $data['type'] ) ) {
							$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );
						} else {
							$html .= $this->render_field_by_hook( $data );
						}

						if ( ! empty( $data['description'] ) ) {
							$html .= '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>';
						}

						$html .= '</div>';

					} elseif ( ! empty( $data['without_label'] ) ) {

						$html .= '<div class="form-field um-forms-line" ' . $conditional . $prefix_attr . $type_attr . '>';

						if ( method_exists( $this, 'render_' . $data['type'] ) ) {
							$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );
						} else {
							$html .= $this->render_field_by_hook( $data );
						}

						if ( ! empty( $data['description'] ) ) {
							$html .= '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>';
						}

						$html .= '</div>';

					} else {

						$html .= '<div class="form-field um-forms-line" ' . $conditional . $prefix_attr . $type_attr . '>' . $this->render_field_label( $data );

						if ( method_exists( $this, 'render_' . $data['type'] ) ) {
							$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );
						} else {
							$html .= $this->render_field_by_hook( $data );
						}

						if ( ! empty( $data['description'] ) ) {
							$html .= '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>';
						}

						$html .= '</div>';

					}
				} elseif ( strpos( $this->form_data['class'], 'um-top-label' ) !== false ) {

					$html .= '<tr class="um-forms-line" ' . $conditional . $prefix_attr . $type_attr . '>'
						. '<td>' . $this->render_field_label( $data );

					if ( method_exists( $this, 'render_' . $data['type'] ) ) {
						$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );
					} else {
						$html .= $this->render_field_by_hook( $data );
					}

					if ( ! empty( $data['description'] ) ) {
						$html .= '<div class="um-admin-clear"></div>'
							. '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>';
					}

					$html .= '</td>'
						. '</tr>';

				} elseif ( ! empty( $data['without_label'] ) ) {

					$html .= '<tr class="um-forms-line" ' . $conditional . $prefix_attr . $type_attr . '>'
						. '<td colspan="2">';

					if ( method_exists( $this, 'render_' . $data['type'] ) ) {
						$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );
					} else {
						$html .= $this->render_field_by_hook( $data );
					}

					if ( ! empty( $data['description'] ) ) {
						$html .= '<div class="um-admin-clear"></div>'
							. '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>';
					}

					$html .= '</td>'
						. '</tr>';

				} else {

					$html .= '<tr class="um-forms-line" ' . $conditional . $prefix_attr . $type_attr . '>'
						. '<th>' . $this->render_field_label( $data ) . '</th>'
						. '<td>';

					if ( method_exists( $this, 'render_' . $data['type'] ) ) {
						$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );
					} else {
						$html .= $this->render_field_by_hook( $data );
					}

					if ( ! empty( $data['description'] ) ) {
						$html .= '<div class="um-admin-clear"></div>'
							. '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>';
					}

					$html .= '</td>'
						. '</tr>';

				}
			} else {
				$html .= $this->render_hidden( $data );
			}

			return $html;
		}


		/**
		 * Render admin form field by hook
		 *
		 * @param  array $data  Field data.
		 *
		 * @return mixed|void
		 */
		public function render_field_by_hook( $data ) {
			$type = isset( $data['type'] ) ? (string) $data['type'] : '';

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_render_field_type_{$type}
			 * @description Render admin form field by hook
			 * @input_vars
			 * [{"var":"$html","type":"string","desc":"Field's HTML"},
			 * {"var":"$data","type":"array","desc":"Field's data"},
			 * {"var":"$form_data","type":"array","desc":"Form data"},
			 * {"var":"$admin_form","type":"object","desc":"Admin_Forms class object"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( "um_render_field_type_{$type}", 'function_name', 10, 4 );
			 * @example
			 * <?php
			 * add_filter( "um_render_field_type_{$type}", 'my_render_field_type', 10, 4 );
			 * function my_render_field_type( $html, $data, $form_data, $admin_form ) {
			 *     // your code here
			 *     return $html;
			 * }
			 * ?>
			 */
			return apply_filters( "um_render_field_type_{$type}", '', $data, $this->form_data, $this );
		}


		/**
		 * Render admin form field label
		 *
		 * @param  array $data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_field_label( $data ) {
			if ( empty( $data['label'] ) ) {
				return false;
			}

			$prefix   = empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'];
			$id       = $prefix . ( empty( $data['id1'] ) ? $data['id'] : $data['id1'] );
			$for_attr = ' for="' . esc_attr( $id ) . '" ';

			$label = $data['label'] . ' ';
			if ( isset( $data['required'] ) && $data['required'] ) {
				$label .= '<span class="um-req" title="' . esc_attr__( 'Required', 'ultimate-member' ) . '">*</span> ';
			}

			$tooltip = empty( $data['tooltip'] ) ? '' : UM()->tooltip( $data['tooltip'], false, false );

			return '<label ' . $for_attr . '>' . $label . $tooltip . '</label>';
		}


		/**
		 * Render hidden field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_hidden( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return '';
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class      = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = '<input type="hidden" ' . $id_attr . $class_attr . $name_attr . $data_attr . $value_attr . '/>';

			return $html;
		}


		/**
		 * Render text field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_text( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ) {
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$placeholder_attr = empty( $field_data['placeholder'] ) ? '' : ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '" ';

			$html = '<input type="text" ' . $id_attr . $class_attr . $name_attr . $data_attr . $value_attr . $placeholder_attr . '/>';

			return $html;
		}


		/**
		 * Render number field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_number( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ) {
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$placeholder_attr = empty( $field_data['placeholder'] ) ? '' : ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '" ';

			$html = '<input type="number" ' . $id_attr . $class_attr . $name_attr . $data_attr . $value_attr . $placeholder_attr . '/>';

			return $html;
		}


		/**
		 * Render color field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_color( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';
			$class .= ' um-admin-colorpicker';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$placeholder_attr = empty( $field_data['placeholder'] ) ? '' : ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '" ';

			$html = '<input type="text" ' . $id_attr . $class_attr . $name_attr . $data_attr . $value_attr . $placeholder_attr . '/>';

			return $html;
		}


		/**
		 * Render icon field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_icon( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = '<span class="um_admin_fonticon_wrapper">'
				. '<a href="javascript:void(0);" class="button" data-modal="UM_fonticons" data-modal-size="normal" data-dynamic-content="um_admin_fonticon_selector" data-arg1="" data-arg2="" data-back="">' . esc_html__( 'Choose Icon', 'ultimate-member' ) . '</a>'
				. '<span class="um-admin-icon-value">';

			if ( ! empty( $value ) ) {
				$html .= '<i class="' . esc_attr( $value ) . '"></i>';
			} else {
				$html .= esc_html__( 'No Icon', 'ultimate-member' );
			}

			$html .= '</span>'
				. '<input type="hidden" ' . $name_attr . $id_attr . $value_attr . ' />';

			if ( ! empty( $value ) ) {
				$html .= '<span class="um-admin-icon-clear show"><i class="um-icon-android-cancel"></i></span>';
			} else {
				$html .= '<span class="um-admin-icon-clear"><i class="um-icon-android-cancel"></i></span>';
			}

			$html .= '</span>';

			UM()->metabox()->init_icon = true;

			return $html;
		}


		/**
		 * Render users_dropdown field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_users_dropdown( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$multiple = ! empty( $field_data['multi'] ) ? ' multiple ' : '';

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field um-user-select-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name             = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$hidden_name_attr = ' name="' . esc_attr( $name ) . '" ';

			$name     .= empty( $field_data['multi'] ) ? '' : '[]';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value = $this->get_field_value( $field_data );

			$users = array();
			if ( ! empty( $value ) ) {
				$users = get_users(
					array(
						'include' => $value,
						'fields'  => array( 'ID', 'user_login' ),
					)
				);
			}

			$options = '';
			if ( ! empty( $users ) ) {
				foreach ( $users as $user ) {
					$options .= '<option value="' . esc_attr( $user->ID ) . '" selected>' . esc_html( $user->user_login . ' (#' . $user->ID . ')' ) . '</option>';
				}
			}

			$hidden = empty( $multiple ) ? '' : '<input type="hidden" ' . $hidden_name_attr . ' value="" />';

			$html = $hidden
				. '<select ' . $multiple . $id_attr . $name_attr . $class_attr . $data_attr . 'data-placeholder="' . esc_attr__( 'Select Users', 'ultimate-member' ) . '" placeholder="' . esc_attr__( 'Select Users', 'ultimate-member' ) . '">'
				. '<option>' . esc_html__( 'Select Users', 'ultimate-member' ) . '</option>'
				. $options
				. '</select>';

			return $html;
		}


		/**
		 * Render sortable_items field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_sortable_items( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			if ( empty( $field_data['items'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$size = empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $val ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $val ) . '" ';
			}

			$html = '<input class="um-sortable-items-value" type="hidden" ' . $name_attr . $id_attr . $value_attr . $data_attr . ' />'
				. '<ul class="um-sortable-items-field ' . esc_attr( $size ) . '">';

			if ( ! empty( $value ) ) {
				$value_array = explode( ',', $value );
				uksort(
					$field_data['items'],
					function( $a, $b ) use ( $value_array ) {

						$arr_flip = array_flip( $value_array );

						if ( ! isset( $arr_flip[ $b ] ) ) {
							return 1;
						}

						if ( ! isset( $arr_flip[ $a ] ) ) {
							return -1;
						}

						if ( $arr_flip[ $a ] === $arr_flip[ $b ] ) {
							return 0;
						}

						return ( $arr_flip[ $a ] < $arr_flip[ $b ] ) ? -1 : 1;
					}
				);
			}

			foreach ( $field_data['items'] as $tab_id => $tab_name ) {
				$content = apply_filters( 'um_render_sortable_items_item_html', $tab_name, $tab_id, $field_data );

				$html .= '<li data-tab-id="' . esc_attr( $tab_id ) . '" class="um-sortable-item">'
					. '<span class="um-field-icon"><i class="um-faicon-sort"></i></span>'
					. $content
					. '</li>';
			}

			$html .= '</ul>';

			return $html;
		}


		/**
		 * Render datepicker field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_datepicker( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$placeholder_attr = empty( $field_data['placeholder'] ) ? '' : ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '" ';

			$html = '<input type="date" ' . $id_attr . $class_attr . $name_attr . $data_attr . $value_attr . $placeholder_attr . '/>';

			return $html;
		}


		/**
		 * Render inline_texts field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_inline_texts( $field_data ) {
			if ( empty( $field_data['id1'] ) ) {
				return false;
			}

			$i = 1;

			$fields = array();
			while ( ! empty( $field_data[ 'id' . $i ] ) ) {
				$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data[ 'id' . $i ];
				$id_attr = ' id="' . esc_attr( $id ) . '" ';

				$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
				$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

				$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

				$data = array(
					'field_id' => $field_data[ 'id' . $i ],
				);

				$data_attr = '';
				foreach ( $data as $key => $value ) {
					$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
				}

				$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '" ' : '';

				$name      = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $field_data[ 'id' . $i ] . ']' : $field_data[ 'id' . $i ];
				$name_attr = ' name="' . esc_attr( $name ) . '" ';

				$value      = $this->get_field_value( $field_data, $i );
				$value_attr = ' value="' . esc_attr( $value ) . '" ';

				$fields[ $i ] = '<input type="text" ' . $id_attr . $class_attr . $name_attr . $data_attr . $value_attr . $placeholder_attr . 'style="display:inline;"/>';

				$i++;
			}

			$html = vsprintf( $field_data['mask'], $fields );

			return $html;
		}


		/**
		 * Render textarea field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_textarea( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$rows = empty( $field_data['args']['textarea_rows'] ) ? '' : ' rows="' . esc_attr( $field_data['args']['textarea_rows'] ) . '" ';

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value = $this->get_field_value( $field_data );

			$html = '<textarea ' . $id_attr . $class_attr . $name_attr . $data_attr . $rows . '>' . esc_textarea( $value ) . '</textarea>';

			return $html;
		}


		/**
		 * Render wp_editor field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_wp_editor( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';

			$value = $this->get_field_value( $field_data );

			ob_start();
			wp_editor(
				$value,
				$id,
				array(
					'textarea_name' => $name,
					'textarea_rows' => 20,
					'editor_height' => 425,
					'wpautop'       => false,
					'media_buttons' => false,
					'editor_class'  => $class,
				)
			);

			$html = ob_get_clean();
			return $html;
		}


		/**
		 * Render checkbox field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_checkbox( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id             = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr        = ' id="' . esc_attr( $id ) . '" ';
			$id_attr_hidden = ' id="' . esc_attr( $id ) . '_hidden" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			if ( ! empty( $field_data['data'] ) ) {
				$data = array_merge( $data, $field_data['data'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value = $this->get_field_value( $field_data );

			$html = '<input type="hidden" ' . $id_attr_hidden . $name_attr . ' value="0" />'
				. '<input type="checkbox" ' . $id_attr . $class_attr . $name_attr . $data_attr . checked( $value, true, false ) . ' value="1" />';

			return $html;
		}


		/**
		 * Render same_page_update field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_same_page_update( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id             = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr        = ' id="' . esc_attr( $id ) . '" ';
			$id_attr_hidden = ' id="' . esc_attr( $id ) . '_hidden" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			if ( ! empty( $field_data['data'] ) ) {
				$data = array_merge( $data, $field_data['data'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			if ( ! empty( $field_data['upgrade_cb'] ) ) {
				$data_attr .= ' data-log-object="' . esc_attr( $field_data['upgrade_cb'] ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value = $this->get_field_value( $field_data );

			$html = '<input type="hidden" ' . $id_attr_hidden . $name_attr . ' value="0" />'
				. '<input type="checkbox" ' . $id_attr . $class_attr . $name_attr . $data_attr . checked( $value, true, false ) . ' value="1" />';

			if ( ! empty( $field_data['upgrade_cb'] ) ) {
				$html .= '<div class="um-same-page-update-wrapper um-same-page-update-' . esc_attr( $field_data['upgrade_cb'] ) . '">'
					. '<div class="um-same-page-update-description">' . wp_kses_post( $field_data['upgrade_description'] ) . '</div>'
					. '<input type="button" data-upgrade_cb="' . esc_attr( $field_data['upgrade_cb'] ) . '" class="button button-primary um-admin-form-same-page-update" value="' . esc_attr__( 'Run', 'ultimate-member' ) . '"/>'
					. '<div class="upgrade_log"></div>'
					. '</div>';
			}

			return $html;
		}


		/**
		 * Render select field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_select( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$multiple = ! empty( $field_data['multi'] ) ? ' multiple ' : '';

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name             = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$hidden_name_attr = ' name="' . esc_attr( $name ) . '" ';

			$name     .= empty( $field_data['multi'] ) ? '' : '[]';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value = $this->get_field_value( $field_data );

			$options = '';
			if ( ! empty( $field_data['options'] ) ) {
				if ( ! empty( $field_data['multi'] ) ) {
					$value = is_array( $value ) ? array_map( 'strval', $value ) : array();
				} elseif ( is_numeric( $value ) ) {
					$value = (string) $value;
				}
				foreach ( $field_data['options'] as $key => $option ) {
					if ( is_numeric( $key ) ) {
						$key = (string) $key;
					}
					if ( ! empty( $field_data['multi'] ) ) {
						$options .= '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $value, true ), true, false ) . '>' . esc_html( $option ) . '</option>';
					} else {
						$options .= '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $value, true, false ) . '>' . esc_html( $option ) . '</option>';
					}
				}
			}

			$hidden = empty( $multiple ) ? '' : '<input type="hidden" ' . $hidden_name_attr . ' value="" />';

			$html = $hidden . '<select ' . $multiple . $id_attr . $name_attr . $class_attr . $data_attr . ' >' . $options . '</select>';

			return $html;
		}


		/**
		 * Render multi_selects field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_multi_selects( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$sorting = ! empty( $field_data['sorting'] ) ? $field_data['sorting'] : false;

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';
			$class .= empty( $sorting ) ? '' : ' um-sorting-enabled';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
				'id_attr'  => $id,
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = ( empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']' ) . '[]';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$values = $this->get_field_value( $field_data );

			$options = '';
			foreach ( $field_data['options'] as $key => $option ) {
				$options .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
			}

			$html = '<select class="um-hidden-multi-selects" ' . $data_attr . ' >' . $options . '</select>'
				. '<ul class="um-multi-selects-list ' . ( empty( $sorting ) ? '' : ' um-sortable-multi-selects ' ) . '" ' . $data_attr . ' >';

			if ( $sorting && is_array( $values ) ) {
				ksort( $values );
			}

			if ( ! empty( $values ) && is_array( $values ) ) {
				foreach ( $values as $k => $value ) {
					if ( ! in_array( $value, array_keys( $field_data['options'] ), true ) ) {
						continue;
					}

					$id_attr = ' id="' . esc_attr( $id . '-' . $k ) . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						if ( is_numeric( $key ) ) {
							$key   = (string) $key;
							$value = (string) $value;
						}
						$options .= '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $value, true, false ) . '>' . esc_html( $option ) . '</option>';
					}

					$html .= '<li class="um-multi-selects-option-line ' . ( empty( $sorting ) ? '' : ' um-admin-drag-fld ' ) . '">';
					if ( $sorting ) {
						$html .= '<span class="um-field-icon"><i class="um-faicon-sort"></i></span>';
					}
					$html .= '<span class="um-field-wrapper">'
						. '<select ' . $id_attr . $name_attr . $class_attr . $data_attr . '>' . $options . '</select>'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-select-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '</li>';

				}
			} elseif ( ! empty( $field_data['show_default_number'] ) && is_numeric( $field_data['show_default_number'] ) && $field_data['show_default_number'] > 0 ) {

				$i = 0;
				while ( $i < $field_data['show_default_number'] ) {
					$id_attr = ' id="' . esc_attr( $id . '-' . $i ) . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						$options .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
					}

					$html .= '<li class="um-multi-selects-option-line ' . ( empty( $sorting ) ? '' : ' um-admin-drag-fld ' ) . '">';
					if ( $sorting ) {
						$html .= '<span class="um-field-icon"><i class="um-faicon-sort"></i></span>';
					}
					$html .= '<span class="um-field-wrapper">'
						. '<select ' . $id_attr . $name_attr . $class_attr . $data_attr . '>' . $options . '</select>'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-select-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '</li>';

					$i++;
				}
			}

			$html .= '</ul>'
				. '<a href="javascript:void(0);" class="button button-primary um-multi-selects-add-option" data-name="' . esc_attr( $name ) . '">' . esc_html( $field_data['add_text'] ) . '</a>';

			return $html;
		}


		/**
		 * Render multi_checkbox field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_multi_checkbox( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$name = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';

			$values = $this->get_field_value( $field_data );
			if ( empty( $values ) ) {
				$values = array();
			} elseif ( is_array( $values ) ) {
				$values = array_map( 'strval', $values );
			}

			$i    = 0;
			$html = '';

			$columns = ( ! empty( $field_data['columns'] ) && is_numeric( $field_data['columns'] ) ) ? $field_data['columns'] : 1;
			while ( $i < $columns ) {
				$per_page                = ceil( count( $field_data['options'] ) / $columns );
				$section_fields_per_page = array_slice( $field_data['options'], $i * $per_page, $per_page, true );

				$html .= '<span class="um-form-fields-section" style="width:' . floor( 100 / $columns ) . '%;">';

				foreach ( $section_fields_per_page as $k => $title ) {
					if ( is_numeric( $k ) ) {
						$k = (string) $k;
					}
					$id_attr   = ' id="' . esc_attr( $id . '_' . $k ) . '" ';
					$for_attr  = ' for="' . esc_attr( $id . '_' . $k ) . '" ';
					$name_attr = ' name="' . esc_attr( $name . '[' . $k . ']' ) . '" ';

					$data = array(
						'field_id' => $field_data['id'] . '_' . $k,
					);

					if ( ! empty( $field_data['data'] ) ) {
						$data = array_merge( $data, $field_data['data'] );
					}

					$data_attr = '';
					foreach ( $data as $key => $value ) {
						if ( 'checkbox_key' === $value ) {
							$value = $k;
						}
						$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
					}

					$html .= '<label ' . $for_attr . '>'
						. '<input type="checkbox" ' . $name_attr . $id_attr . checked( in_array( $k, $values, true ), true, false ) . $data_attr . $class_attr . ' value="1">'
						. '<span>' . esc_html( $title ) . '</span>'
						. '</label>';
				}

				$html .= '</span>';

				$i++;
			}

			return $html;
		}


		/**
		 * Render multi_text field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_multi_text( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$size = empty( $field_data['size'] ) ? ' um-long-field ' : ' um-' . $field_data['size'] . '-field ';

			$class      = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id'   => $field_data['id'],
				'id_attr'    => $id,
				'item_class' => 'um-multi-text-option-line ' . esc_attr( $size ),
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = ( empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']' ) . '[]';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$values = $this->get_field_value( $field_data );

			$html = '<input type="text" class="um-hidden-multi-text" ' . esc_attr( $data_attr ) . ' />'
				. '<ul class="um-multi-text-list" ' . esc_attr( $data_attr ) . '>';

			if ( ! empty( $values ) ) {
				foreach ( $values as $k => $value ) {
					$id_attr = ' id="' . esc_attr( $id . '-' . $k ) . '" ';

					$html .= '<li class="um-multi-text-option-line ' . $size . '">'
						. '<span class="um-field-wrapper">'
						. '<input type="text" ' . $id_attr . $name_attr . $class_attr . $data_attr . ' value="' . esc_attr( $value ) . '" />'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-text-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '</li>';

				}
			} elseif ( ! empty( $field_data['show_default_number'] ) && is_numeric( $field_data['show_default_number'] ) && $field_data['show_default_number'] > 0 ) {

				$i = 0;
				while ( $i < $field_data['show_default_number'] ) {
					$id_attr = ' id="' . esc_attr( $id . '-' . $i ) . '" ';

					$html .= '<li class="um-multi-text-option-line ' . $size . '">'
						. '<span class="um-field-wrapper">'
						. '<input type="text" ' . $id_attr . $name_attr . $class_attr . $data_attr . ' value="" />'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-text-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '</li>';

					$i++;
				}
			}

			$html .= '</ul>'
				. '<a href="javascript:void(0);" class="button button-primary um-multi-text-add-option" data-name="' . esc_attr( $name ) . '">' . esc_html( $field_data['add_text'] ) . '</a>';

			return $html;
		}


		/**
		 * Render media field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_media( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field um-media-upload-data-url ' . esc_attr( $class ) . '"';

			$data = array(
				'field_id' => $field_data['id'] . '_url',
			);

			if ( ! empty( $field_data['default']['url'] ) ) {
				$data['default'] = esc_attr( $field_data['default']['url'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';

			$value = $this->get_field_value( $field_data );

			$upload_frame_title = ! empty( $field_data['upload_frame_title'] ) ? $field_data['upload_frame_title'] : esc_html__( 'Select media', 'ultimate-member' );

			$image_id        = ! empty( $value['id'] ) ? $value['id'] : '';
			$image_width     = ! empty( $value['width'] ) ? $value['width'] : '';
			$image_height    = ! empty( $value['height'] ) ? $value['height'] : '';
			$image_thumbnail = ! empty( $value['thumbnail'] ) ? $value['thumbnail'] : '';
			$image_url       = ! empty( $value['url'] ) ? $value['url'] : '';

			$html = '<div class="um-media-upload">'
				. '<input type="hidden" name="' . esc_attr( $name ) . '[id]" id="' . esc_attr( $id ) . '_id" value="' . esc_attr( $image_id ) . '" class="um-media-upload-data-id">'
				. '<input type="hidden" name="' . esc_attr( $name ) . '[width]" id="' . esc_attr( $id ) . '_width" value="' . esc_attr( $image_width ) . '" class="um-media-upload-data-width">'
				. '<input type="hidden" name="' . esc_attr( $name ) . '[height]" id="' . esc_attr( $id ) . '_height" value="' . esc_attr( $image_height ) . '" class="um-media-upload-data-height">'
				. '<input type="hidden" name="' . esc_attr( $name ) . '[thumbnail]" id="' . esc_attr( $id ) . '_thumbnail" value="' . esc_attr( $image_thumbnail ) . '" class="um-media-upload-data-thumbnail">'
				. '<input type="hidden" name="' . esc_attr( $name ) . '[url]" id="' . esc_attr( $id ) . '_url" value="' . esc_attr( $image_url ) . '" ' . $data_attr . $class_attr . '>';

			if ( ! isset( $field_data['preview'] ) || false !== $field_data['preview'] ) {
				$html .= '<img src="' . esc_url( $image_url ) . '" alt="" class="icon_preview"><div style="clear:both;"></div>';
			}

			if ( ! empty( $field_data['url'] ) ) {
				$html .= '<input type="text" class="um-media-upload-url" readonly value="' . esc_attr( $image_url ) . '" />'
					. '<div style="clear:both;"></div>';
			}

			$html .= '<input type="button" class="um-set-image button button-primary" value="' . esc_attr__( 'Select', 'ultimate-member' ) . '" data-upload_frame="' . esc_attr( $upload_frame_title ) . '" />'
				. '<input type="button" class="um-clear-image button" value="' . esc_attr__( 'Clear', 'ultimate-member' ) . '" />'
				. '</div>';

			return $html;
		}


		/**
		 * Render email_template field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_email_template( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name  = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$value = $this->get_field_value( $field_data );

			ob_start();
			?>

			<div class="email_template_wrapper <?php echo esc_attr( $field_data['in_theme'] ? 'in_theme' : '' ); ?>" data-key="<?php echo esc_attr( $field_data['id'] ); ?>" style="position: relative;">

				<?php
				wp_editor(
					$value,
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => 20,
						'editor_height' => 425,
						'wpautop'       => false,
						'media_buttons' => false,
						'editor_class'  => $class,
					)
				);
				?>

				<span class="description"><?php esc_html_e( 'For default text for plain-text emails please see this', 'ultimate-member' ); ?> <a href="https://docs.ultimatemember.com/article/1342-plain-text-email-default-templates#<?php echo esc_attr( $field_data['id'] ); ?>" target="_blank"><?php esc_html_e( 'doc', 'ultimate-member' ); ?></a></span>
			</div>

			<?php
			$html = ob_get_clean();

			return $html;
		}


		/**
		 * Render ajax_button field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return bool|string
		 */
		public function render_ajax_button( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id      = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class      = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class_attr = ' class="um-forms-field button ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value      = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = '<input type="button" ' . $id_attr . $class_attr . $name_attr . $data_attr . $value_attr . '/>'
				. '<div class="clear"></div>'
				. '<div class="um_setting_ajax_button_response"></div>';

			return $html;
		}


		/**
		 * Render info_text field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return mixed
		 */
		public function render_info_text( $field_data ) {
			return $field_data['value'];
		}


		/**
		 * Render md_default_filters field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return string
		 */
		public function render_md_default_filters( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			global $post;
			$data = array(
				'field_id'         => $field_data['id'],
				'id_attr'          => $id,
				'member_directory' => $post->ID,
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = ( empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']' ) . '[]';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$values = $this->get_field_value( $field_data );
			if ( is_array( $values ) ) {
				$filters = array_keys( $values );
			}

			$options = '';
			foreach ( $field_data['options'] as $key => $option ) {
				$options .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
			}

			$html = '<input type="hidden" name="um-gmt-offset" />'
				. '<select class="um-hidden-md-default-filters" ' . $data_attr . '>' . $options . '</select>'
				. '<ul class="um-md-default-filters-list" ' . $data_attr . '>';

			if ( ! empty( $filters ) && is_array( $filters ) ) {
				foreach ( $filters as $k => $value ) {
					if ( ! in_array( $value, array_keys( $field_data['options'] ), true ) ) {
						continue;
					}

					$id_attr = ' id="' . esc_attr( $id . '-' . $k ) . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						if ( is_numeric( $key ) ) {
							$key   = (string) $key;
							$value = (string) $value;
						}
						$options .= '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $value, true, false ) . '>' . esc_html( $option ) . '</option>';
					}

					$html .= '<li class="um-md-default-filters-option-line">'
						. '<span class="um-field-wrapper">'
						. '<select ' . $id_attr . $name_attr . $class_attr . $data_attr . '>' . $options . '</select>'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-select-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '<span class="um-field-wrapper2 um">' . UM()->member_directory()->show_filter( $value, array( 'form_id' => $post->ID ), $values[ $value ], true ) . '</span>'
						. '</li>';

				}
			} elseif ( ! empty( $field_data['show_default_number'] ) && is_numeric( $field_data['show_default_number'] ) && $field_data['show_default_number'] > 0 ) {

				$i = 0;
				while ( $i < $field_data['show_default_number'] ) {
					$id_attr = ' id="' . esc_attr( $id . '-' . $i ) . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						$options .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
					}

					$html .= '<li class="um-md-default-filters-option-line">'
						. '<span class="um-field-wrapper">'
						. '<select ' . $id_attr . $name_attr . $class_attr . $data_attr . '>' . $options . '</select>'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-select-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '</li>';

					$i++;
				}
			}

			$html .= '</ul>'
				. '<a href="javascript:void(0);" class="button button-primary um-md-default-filters-add-option" data-name="' . esc_attr( $name ) . '">' . esc_html( $field_data['add_text'] ) . '</a>';

			return $html;
		}


		/**
		 * Render md_sorting_fields field
		 *
		 * @param  array $field_data  Field data.
		 *
		 * @return string
		 */
		public function render_md_sorting_fields( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( empty( $this->form_data['prefix_id'] ) ? '' : $this->form_data['prefix_id'] ) . '_' . $field_data['id'];

			$sorting = ! empty( $field_data['sorting'] ) ? $field_data['sorting'] : false;

			$class  = empty( $field_data['class'] ) ? '' : $field_data['class'];
			$class .= empty( $field_data['size'] ) ? ' um-long-field' : ' um-' . $field_data['size'] . '-field';
			$class .= empty( $sorting ) ? '' : ' um-sorting-enabled';

			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
				'id_attr'  => $id,
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
			}

			$name      = ( empty( $this->form_data['prefix_id'] ) ? $field_data['id'] : $this->form_data['prefix_id'] . '[' . $field_data['id'] . ']' ) . '[]';
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$values = $this->get_field_value( $field_data );

			$options = '';
			foreach ( $field_data['options'] as $key => $option ) {
				$options .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
			}

			$html = '<select class="um-hidden-multi-selects" ' . $data_attr . '>' . $options . '</select>'
				. '<ul class="um-multi-selects-list ' . ( empty( $sorting ) ? '' : ' um-sortable-multi-selects ' ) . '" ' . $data_attr . '>';

			if ( $sorting && is_array( $values ) ) {
				ksort( $values );
			}

			if ( ! empty( $values ) && is_array( $values ) ) {
				foreach ( $values as $k => $value ) {

					$other_key   = '';
					$other_label = '';
					if ( is_array( $value ) ) {
						$keys      = array_keys( $value );
						$other_key = $keys[0];

						$labels      = array_values( $value );
						$other_label = $labels[0];
					} elseif ( ! in_array( $value, array_keys( $field_data['options'] ), true ) ) {
						continue;
					}

					$id_attr = ' id="' . esc_attr( $id . '-' . $k ) . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						if ( is_array( $value ) ) {
							$selected = selected( 'other' === $key, true, false );
						} else {
							if ( is_numeric( $key ) ) {
								$key   = (string) $key;
								$value = (string) $value;
							}
							$selected = selected( $value === $key, true, false );
						}

						$options .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $option ) . '</option>';
					}

					$html .= '<li class="um-multi-selects-option-line ' . ( empty( $sorting ) ? '' : ' um-admin-drag-fld ' ) . '">';
					if ( $sorting ) {
						$html .= '<span class="um-field-icon"><i class="um-faicon-sort"></i></span>';
					}
					$html .= '<span class="um-field-wrapper">'
						. '<select ' . $id_attr . $name_attr . $class_attr . $data_attr . '>' . $options . '</select>'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-select-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '<span class="um-field-wrapper um-custom-order-fields">'
						. '<label>' . esc_html__( 'Meta key', 'ultimate-member' ) . ':&nbsp;'
						. '<input type="text" name="um_metadata[_um_sorting_fields][other_data][' . esc_attr( $k ) . '][meta_key]" value="' . esc_attr( $other_key ) . '" />'
						. '</label>'
						. '</span>'
						. '<span class="um-field-wrapper um-custom-order-fields">'
						. '<label>' . esc_html__( 'Label', 'ultimate-member' ) . ':&nbsp;'
						. '<input type="text" name="um_metadata[_um_sorting_fields][other_data][' . esc_attr( $k ) . '][label]" value="' . esc_attr( $other_label ) . '" />'
						. '</label>'
						. '</span>'
						. '</li>';

				}
			} elseif ( ! empty( $field_data['show_default_number'] ) && is_numeric( $field_data['show_default_number'] ) && $field_data['show_default_number'] > 0 ) {

				$i = 0;
				while ( $i < $field_data['show_default_number'] ) {
					$id_attr = ' id="' . esc_attr( $id . '-' . $i ) . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						$options .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
					}

					$html .= '<li class="um-multi-selects-option-line ' . ( empty( $sorting ) ? '' : ' um-admin-drag-fld ' ) . '">';
					if ( $sorting ) {
						$html .= '<span class="um-field-icon"><i class="um-faicon-sort"></i></span>';
					}
					$html .= '<span class="um-field-wrapper">'
						. '<select ' . $id_attr . $name_attr . $class_attr . $data_attr . '>' . $options . '</select>'
						. '</span>'
						. '<span class="um-field-control">'
						. '<a href="javascript:void(0);" class="um-select-delete">' . esc_html__( 'Remove', 'ultimate-member' ) . '</a>'
						. '</span>'
						. '<span class="um-field-wrapper um-custom-order-fields">'
						. '<label>' . esc_html__( 'Meta key', 'ultimate-member' ) . ':&nbsp;'
						. '<input type="text" name="um_metadata[_um_sorting_fields][other_data][' . esc_attr( $i ) . '][meta_key]" value="" />'
						. '</label>'
						. '</span>'
						. '<span class="um-field-wrapper um-custom-order-fields">'
						. '<label>' . esc_html__( 'Label', 'ultimate-member' ) . ':&nbsp;'
						. '<input type="text" name="um_metadata[_um_sorting_fields][other_data][' . esc_attr( $i ) . '][label]" value="" />'
						. '</label>'
						. '</span>'
						. '</li>';

					$i++;
				}
			}

			$html .= '</ul>'
				. '<a href="javascript:void(0);" class="button button-primary um-multi-selects-add-option" data-name="' . esc_attr( $name ) . '">' . esc_html( $field_data['add_text'] ) . '</a>';

			return $html;
		}


		/**
		 * Get field value
		 *
		 * @param  array  $field_data  Field data and settings.
		 * @param  string $i           Field index.
		 *
		 * @return string|array
		 */
		public function get_field_value( $field_data, $i = '' ) {

			$default = '';
			if ( isset( $field_data[ 'default' . $i ] ) ) {
				$default = $field_data[ 'default' . $i ];
			} elseif ( 'multi_checkbox' === $field_data['type'] ) {
				$default = array();
				if ( isset( $field_data['default'] ) ) {
					$default = is_array( $field_data['default'] ) ? $field_data['default'] : array( $field_data['default'] );
				}
			}

			if ( 'checkbox' === $field_data['type'] || 'multi_checkbox' === $field_data['type'] ) {
				$value = ( isset( $field_data[ 'value' . $i ] ) && '' !== $field_data[ 'value' . $i ] ) ? $field_data[ 'value' . $i ] : $default;
			} else {
				$value = isset( $field_data[ 'value' . $i ] ) ? $field_data[ 'value' . $i ] : $default;
			}

			$value = is_string( $value ) ? stripslashes( $value ) : $value;

			return $value;
		}

	}
}
