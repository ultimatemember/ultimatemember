<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Forms' ) ) {

	/**
	 * Class Forms
	 *
	 * @package um\admin
	 */
	class Forms {

		/**
		 * @var bool|array
		 */
		public $form_data;

		/**
		 * Admin_Forms constructor.
		 * @param bool $form_data
		 */
		public function __construct( $form_data = false ) {
			if ( $form_data ) {
				$this->form_data = $form_data;
			}
		}

		/**
		 * Set Form Data
		 *
		 * @param $data
		 *
		 * @return $this
		 */
		public function set_data( $data ) {
			$this->form_data = $data;
			return $this;
		}

		/**
		 * Render form
		 *
		 *
		 * @param bool $echo
		 * @return string
		 */
		public function render_form( $echo = true ) {
			if ( empty( $this->form_data['fields'] ) ) {
				return '';
			}

			$class      = 'form-table um-form-table ' . ( ! empty( $this->form_data['class'] ) ? $this->form_data['class'] : '' );
			$class_attr = ' class="' . esc_attr( $class ) . '" ';

			if ( ! empty( $this->form_data['class'] ) ) {
				$class_attr .= 'data-extra-class="' . esc_attr( $this->form_data['class'] ) . '" ';
			}

			ob_start();

			foreach ( $this->form_data['fields'] as $field_data ) {
				if ( isset( $field_data['type'] ) && 'hidden' === $field_data['type'] ) {
					echo $this->render_form_row( $field_data );
				}
			}

			if ( empty( $this->form_data['without_wrapper'] ) ) {
				?>
				<table <?php echo $class_attr; ?>>
				<tbody>
				<?php
			}

			foreach ( $this->form_data['fields'] as $field_data ) {
				if ( isset( $field_data['type'] ) && 'hidden' !== $field_data['type'] ) {
					echo $this->render_form_row( $field_data );
				}
			}

			if ( empty( $this->form_data['without_wrapper'] ) ) {
				?>
				</tbody>
				</table>
				<?php
			}

			if ( $echo ) {
				ob_get_flush();
				return '';
			} else {
				return ob_get_clean();
			}
		}

		/**
		 * @param array $data
		 *
		 * @return string
		 */
		public function render_form_row( $data ) {
			if ( empty( $data['type'] ) ) {
				return '';
			}

			if ( ! empty( $data['value'] ) && 'email_template' !== $data['type'] && 'info_text' !== $data['type'] ) {
				$data['value'] = wp_unslash( $data['value'] );

				/*for multi_text*/
				if ( ! is_array( $data['value'] ) && ! in_array( $data['type'], array( 'info_text', 'wp_editor' ), true ) ) {
					$data['value'] = esc_attr( $data['value'] );
				}

				if ( in_array( $data['type'], array( 'info_text' ), true ) ) {
					$arr_kses      = array(
						'a'      => array(
							'href'   => array(),
							'title'  => array(),
							'target' => array(),
						),
						'br'     => array(),
						'em'     => array(),
						'strong' => array(),
					);
					$data['value'] = wp_kses( $data['value'], $arr_kses );
				}
			}

			$conditional = ! empty( $data['conditional'] ) ? 'data-conditional="' . esc_attr( wp_json_encode( $data['conditional'] ) ) . '"' : '';
			$prefix_attr = ! empty( $this->form_data['prefix_id'] ) ? ' data-prefix="' . esc_attr( sanitize_title( $this->form_data['prefix_id'] ) ) . '" ' : '';

			$field_id_attr = ! empty( $data['id'] ) ? ' data-field_id="' . esc_attr( $data['id'] ) . '" ' : '';

			$type_attr = ' data-field_type="' . esc_attr( $data['type'] ) . '" ';

			$html = '';
			if ( 'hidden' !== $data['type'] ) {

				if ( ! empty( $this->form_data['div_line'] ) ) {

					if ( strpos( $this->form_data['class'], 'um-top-label' ) !== false ) {

						$html .= '<div class="form-field um-forms-line" ' . $conditional . $prefix_attr . $field_id_attr . $type_attr . '>' . $this->render_field_label( $data );

						if ( method_exists( $this, 'render_' . $data['type'] ) ) {

							$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );

						} else {

							$html .= $this->render_field_by_hook( $data );

						}

						if ( ! empty( $data['description'] ) ) {
							$html .= '<p class="description">' . $data['description'] . '</p>';
						}

						$html .= '</div>';

					} else {

						if ( ! empty( $data['without_label'] ) ) {

							$html .= '<div class="form-field um-forms-line" ' . $conditional . $prefix_attr . $field_id_attr . $type_attr . '>';

							if ( method_exists( $this, 'render_' . $data['type'] ) ) {

								$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );

							} else {

								$html .= $this->render_field_by_hook( $data );

							}

							if ( ! empty( $data['description'] ) ) {
								$html .= '<p class="description">' . $data['description'] . '</p>';
							}

							$html .= '</div>';

						} else {

							$html .= '<div class="form-field um-forms-line" ' . $conditional . $prefix_attr . $field_id_attr . $type_attr . '>' . $this->render_field_label( $data );

							if ( method_exists( $this, 'render_' . $data['type'] ) ) {

								$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );

							} else {

								$html .= $this->render_field_by_hook( $data );

							}

							if ( ! empty( $data['description'] ) ) {
								$html .= '<p class="description">' . $data['description'] . '</p>';
							}

							$html .= '</div>';

						}
					}

				} else {
					if ( strpos( $this->form_data['class'], 'um-top-label' ) !== false ) {

						$html .= '<tr class="um-forms-line" ' . $conditional . $prefix_attr . $field_id_attr . $type_attr . '>
						<td>' . $this->render_field_label( $data );

						if ( method_exists( $this, 'render_' . $data['type'] ) ) {

							$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );

						} else {

							$html .= $this->render_field_by_hook( $data );

						}

						if ( ! empty( $data['description'] ) ) {
							if ( 'checkbox' !== $data['type'] ) {
								$html .= '<div class="um-admin-clear"></div><p class="description">' . $data['description'] . '</p>';
							}
						}

						$html .= '</td></tr>';

					} else {

						if ( ! empty( $data['without_label'] ) ) {

							$html .= '<tr class="um-forms-line" ' . $conditional . $prefix_attr . $field_id_attr . $type_attr . '>
							<td colspan="2">';

							if ( method_exists( $this, 'render_' . $data['type'] ) ) {

								$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );

							} else {

								$html .= $this->render_field_by_hook( $data );

							}

							if ( ! empty( $data['description'] ) ) {
								if ( 'checkbox' !== $data['type'] ) {
									$html .= '<div class="um-admin-clear"></div><p class="description">' . $data['description'] . '</p>';
								}
							}

							$html .= '</td></tr>';

						} else {

							$html .= '<tr class="um-forms-line" ' . $conditional . $prefix_attr . $field_id_attr . $type_attr . '>
							<th>' . $this->render_field_label( $data ) . '</th>
							<td>';

							if ( method_exists( $this, 'render_' . $data['type'] ) ) {

								$html .= call_user_func( array( &$this, 'render_' . $data['type'] ), $data );

							} else {

								$html .= $this->render_field_by_hook( $data );

							}

							if ( ! empty( $data['description'] ) ) {
								if ( 'checkbox' !== $data['type'] ) {
									$html .= '<div class="um-admin-clear"></div><p class="description">' . $data['description'] . '</p>';
								}
							}

							$html .= '</td></tr>';

						}
					}
				}

			} else {
				$html .= $this->render_hidden( $data );
			}

			return $html;
		}

		/**
		 * @param array $data
		 *
		 * @return string
		 */
		public function render_field_by_hook( $data ) {
			/**
			 * Filters the custom field-type content that can be rendered by 3rd-party callback.
			 * Render admin form field by hook.
			 *
			 * @since 2.0
			 * @hook um_render_field_type_{$field_type}
			 *
			 * @param {string} $content    Rendered content. Empty string by default
			 * @param {string} $field_data Field data.
			 * @param {array}  $form_data  Form data.
			 * @param {object} $form       Backend form class (\um\admin\Forms) instance.
			 *
			 * @return {string} Rendered content.
			 */
			return apply_filters( "um_render_field_type_{$data['type']}", '', $data, $this->form_data, $this );
		}

		/**
		 * @param $data
		 *
		 * @return bool|string
		 */
		public function render_field_label( $data ) {
			if ( empty( $data['type'] ) ) {
				return false;
			}

			if ( empty( $data['label'] ) ) {
				return false;
			}

			$id = ! empty( $data['id1'] ) ? $data['id1'] : $data['id'];
			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $id;
			$for_attr = ' for="' . esc_attr( $id ) . '" ';

			$label = $data['label'];
			if ( isset( $data['required'] ) && $data['required'] ) {
				$label = $label . '<span class="um-req" title="' . esc_attr__( 'Required', 'ultimate-member' ) . '">*</span>';
			}

			$tooltip = ! empty( $data['tooltip'] ) ? UM()->tooltip( $data['tooltip'], false, false ) : '';

			if ( 'checkbox' !== $data['type'] ) {
				return "<label $for_attr>$label $tooltip</label>";
			} else {
				return "$label $tooltip";
			}
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_hidden( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return '';
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . $value . '" ';

			$html = "<input type=\"hidden\" $id_attr $class_attr $name_attr $data_attr $value_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return mixed
		 */
		public function render_separator( $field_data ) {
			return $field_data['value'] . '<hr />';
		}

		/**
		 * Render text field
		 *
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_text( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ){
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = "<input type=\"text\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * Render text field
		 *
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_date( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ){
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = "<input type=\"date\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * Render text field
		 *
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_time( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ){
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = "<input type=\"time\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * Render text field
		 *
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_url( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ){
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = "<input type=\"url\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * Render text field
		 *
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_number( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ) {
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . esc_attr( $value ) . '" ';

			$html = "<input type=\"number\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_color( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? ' um-' . $field_data['size'] . '-field ' : ' um-long-field ';
			$class .= ' um-admin-colorpicker ';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . $value . '" ';

			$html = "<input type=\"text\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_icon( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			UM()->install()->set_icons_options();
			$um_icons_list = get_option( 'um_icons_list' );

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field um-icon-select-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			if ( ! empty( $field_data['label'] ) ) {
				$data['label'] = $field_data['label'];
			}

			if ( ! empty( $field_data['attr'] ) && is_array( $field_data['attr'] ) ) {
				$data = array_merge( $data, $field_data['attr'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= " data-{$key}=\"" . esc_attr( $value ) . '" ';
			}

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );

			$value_html = '';
			if ( array_key_exists( $value, $um_icons_list ) ) {
				$value_html = '<option value="' . esc_attr( $value ) . '" selected>' . esc_html( $um_icons_list[ $value ]['label'] ) . '</option>';
			}

			$placeholder = array_key_exists( 'placeholder', $data ) ? $data['placeholder'] : '';

			$html = "<select $id_attr $name_attr $class_attr $data_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . "><option value=\"\">" . esc_html( $placeholder ) . "</option>$value_html</select>";
			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_users_dropdown( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$multiple = ! empty( $field_data['multi'] ) ? 'multiple' : '';

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field um-user-select-field' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
				'avatar'   => ! empty( $field_data['avatar'] ) ? 1 : 0,
			);

			if ( ! empty( $field_data['data'] ) && is_array( $field_data['data'] ) ) {
				$data = array_merge( $data, $field_data['data'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			$hidden_name_attr = ' name="' . $name . '" ';
			$name = $name . ( ! empty( $field_data['multi'] ) ? '[]' : '' );
			$name_attr = ' name="' . $name . '" ';

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
					if ( ! empty( $field_data['avatar'] ) ) {
						$url      = get_avatar_url( $user->ID, 'size=20' );
						$options .= '<option data-img="' . esc_url( $url ) . '" value="' . esc_attr( $user->ID ) . '" selected>' . esc_html( $user->user_login . ' (#' . $user->ID . ')' ) . '</option>';
					} else {
						$options .= '<option value="' . esc_attr( $user->ID ) . '" selected>' . esc_html( $user->user_login . ' (#' . $user->ID . ')' ) . '</option>';
					}
				}
			}

			$placeholder = ! empty( $field_data['placeholder'] ) ? $field_data['placeholder'] : __( 'Select Users', 'ultimate-member' );

			$hidden = '';
			if ( ! empty( $multiple ) ) {
				$hidden = "<input type=\"hidden\" $hidden_name_attr value=\"\" />";
			}
			$html = "$hidden<select $multiple $id_attr $name_attr $class_attr $data_attr data-placeholder=\"" . esc_attr( $placeholder ) . "\" placeholder=\"" . esc_attr( $placeholder ) . "\"><option value=\"\">" . esc_html( $placeholder ) . "</option>$options</select>";

			return $html;
		}

		/**
		 * @param $field_data
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

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$size = ! empty( $field_data['size'] ) ? ' um-' . $field_data['size'] . '-field ' : ' um-long-field';

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			$name_attr = ' name="' . $name . '" ';

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . $value . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $val ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $val ) . '" ';
			}

			$html = '<input class="um-sortable-items-value" type="hidden" ' . $name_attr . ' ' . $id_attr . ' ' . $value_attr . ' ' . $data_attr . ' />';
			$html .= '<ul class="um-sortable-items-field' . esc_attr( $size ) . '">';

			if ( ! empty( $value ) ) {
				$value_array = explode( ',', $value );
				uksort( $field_data['items'], function( $a, $b ) use ( $value_array ) {

					$arr_flip = array_flip( $value_array );

					if ( ! isset( $arr_flip[ $b ] ) ) {
						return 1;
					}

					if ( ! isset( $arr_flip[ $a ] ) ) {
						return -1;
					}

					if ( $arr_flip[ $a ] == $arr_flip[ $b ] ) {
						return 0;
					}

					return ( $arr_flip[ $a ] < $arr_flip[ $b ] ) ? -1 : 1;
				} );
			}

			foreach ( $field_data['items'] as $tab_id => $tab_name ) {
				$content = apply_filters( 'um_render_sortable_items_item_html', $tab_name, $tab_id, $field_data );
				$html .= '<li data-tab-id="' . esc_attr( $tab_id ) . '" class="um-sortable-item"><span class="um-field-icon"><i class="fas fa-sort"></i></span>' . $content . '</li>';
			}

			$html .= '</ul>';

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_datepicker( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . esc_attr( $field_data['placeholder'] ) . '"' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . $value . '" ';

			$html = "<input type=\"date\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_inline_texts( $field_data ) {
			if ( empty( $field_data['id1'] ) ) {
				return false;
			}

			$i = 1;
			$fields = array();
			while( ! empty( $field_data['id' . $i] ) ) {
				$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'. $i];
				$id_attr = ' id="' . $id . '" ';

				$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
				$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
				$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

				$data = array(
					'field_id' => $field_data[ 'id'. $i ]
				);

				$data_attr = '';
				foreach ( $data as $key => $value ) {
					$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
				}

				$placeholder_attr = ! empty( $field_data['placeholder'] ) ? ' placeholder="' . $field_data['placeholder'] . '"' : '';

				$name = $field_data[ 'id'. $i ];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . $name . '" ';

				$value = $this->get_field_value( $field_data, $i );

				$value_attr = ' value="' . $value . '" ';

				$fields[$i] = "<input type=\"text\" $id_attr $class_attr $name_attr $data_attr $value_attr $placeholder_attr style=\"display:inline;\"/>";

				$i++;
			}

			$html = vsprintf( $field_data['mask'], $fields );

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_textarea( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$rows = ! empty( $field_data['args']['textarea_rows'] ) ? ' rows="' . $field_data['args']['textarea_rows'] . '" ' : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );

			$html = "<textarea $id_attr $class_attr $name_attr $data_attr $rows " . disabled( ! empty( $field_data['disabled'] ), true, false ) . ">$value</textarea>";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_wp_editor( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			if ( ! empty( $field_data['name'] ) ) {
				$name = $field_data['name'];
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			}

			$value = $this->get_field_value( $field_data );

			$rows = ! empty( $field_data['args']['textarea_rows'] ) ? $field_data['args']['textarea_rows'] : 8;

			ob_start();

			if ( UM()->is_request( 'ajax' ) ) {
				?>
				<div class="um-admin-editor"></div>
				<div class="um-admin-editor-content" data-editor_id="<?php echo esc_attr( $id ); ?>" data-editor_name="<?php echo esc_attr( $name ); ?>"><?php echo wp_kses( $value, UM()->get_allowed_html( 'templates' ) ); ?></div>
				<?php
			} else {
				wp_editor( $value,
					$id,
					array(
						'textarea_name' => $name,
						'textarea_rows' => $rows,
//						'editor_height' => 425,
						'wpautop'       => false,
						'media_buttons' => false,
						'editor_class'  => $class,
					)
				);
			}

			$html = ob_get_clean();
			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_checkbox( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr        = ' id="' . esc_attr( $id ) . '" ';
			$id_attr_hidden = ' id="' . esc_attr( $id ) . '_hidden" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			if ( ! empty( $field_data['data'] ) ) {
				$data = array_merge( $data, $field_data['data'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			if ( ! empty( $field_data['name'] ) ) {
				$name_attr = ' name="' . esc_attr( $field_data['name'] ) . '" ';
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
				$name_attr = ' name="' . esc_attr( $name ) . '" ';
			}

			$value = $this->get_field_value( $field_data );

			$description = ! empty( $field_data['description'] ) ? $field_data['description'] : '';

			$field_html = "<input type=\"checkbox\" $id_attr $class_attr $name_attr $data_attr " . checked( $value, true, false ) . " value=\"1\" " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";
			if ( '' !== $description ) {
				$field_html = "<label>$field_html $description</label>";
			}
			$html = "<input type=\"hidden\" $id_attr_hidden $name_attr value=\"0\" " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />{$field_html}";

			$html = apply_filters( 'um_admin_render_checkbox_field_html', $html, $field_data );
			return $html;
		}

		public function render_sub_fields( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$value = $this->get_field_value( $field_data );

			if ( ! empty( $field_data['name'] ) ) {
				$name = $field_data['name'];
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			}

			ob_start();

			UM()->admin()->field_group()->field_row_template();
			?>

			<div class="um-fields-column um-sub-fields-builder">
				<div class="um-fields-column-header<?php if ( empty( $value ) ) { ?> hidden<?php } ?>">
					<div class="um-fields-column-header-order"><?php esc_html_e( '#', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-name"><?php esc_html_e( 'Name', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-metakey"><?php esc_html_e( 'Metakey', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-type"><?php esc_html_e( 'Type', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-actions">&nbsp;</div>
				</div>
				<div class="um-fields-column-content<?php if ( empty( $value ) ) { ?> hidden<?php } ?>" data-uniqid="<?php echo esc_attr( uniqid() ); ?>">
					<?php
					if ( ! empty( $value ) ) {
						$i = 1;
						foreach ( $value as $k => $field ) {
							// text-type field is default field type for the builder
							$field_settings_tabs     = UM()->admin()->field_group()->get_field_tabs( $field['type'] );
							$field_settings_settings = UM()->admin()->field_group()->get_field_settings( $field['type'], $field['id'] );

							$row_key = ! empty( $field['id'] ) ? $field['id'] : $k;

							$type     = UM()->admin()->field_group()->get_field_type( $field );
							$meta_key = UM()->admin()->field_group()->get_field_metakey( $field );
							$meta_key = ( empty( $meta_key ) && ! empty( $field['meta_key'] ) ) ? $field['meta_key'] : $meta_key;
							?>
							<div class="um-field-row" data-field="<?php echo esc_attr( $row_key ); ?>">
								<input type="hidden" class="um-field-row-id" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $row_key ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" />
								<input type="hidden" class="um-field-row-parent-id" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $row_key ); ?>][parent_id]" value="<?php echo esc_attr( $field['parent_id'] ); ?>" />
								<input type="hidden" class="um-field-row-order" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $row_key ); ?>][order]" value="<?php echo esc_attr( $i ); ?>" />
								<div class="um-field-row-header um-field-row-toggle-edit">
									<span class="um-field-row-move-link">
										<?php echo esc_html( $i ); ?>
									</span>
									<span class="um-field-row-title um-field-row-toggle-edit">
										<?php
										if ( ! empty( $field['title'] ) ) {
											echo esc_html( $field['title'] );
										} else {
											esc_html_e( '(no title)', 'ultimate-member' );
										}
										?>
									</span>
									<span class="um-field-row-metakey um-field-row-toggle-edit"><?php echo ! empty( $meta_key ) ? esc_html( $meta_key ) : esc_html__( '(no metakey)', 'ultimate-member' ); ?></span>
									<span class="um-field-row-type um-field-row-toggle-edit"><?php echo esc_html( $type ); ?></span>
									<span class="um-field-row-actions um-field-row-toggle-edit">
										<a href="javascript:void(0);" class="um-field-row-action-edit"><?php esc_html_e( 'Edit', 'ultimate-member' ); ?></a>
										<a href="javascript:void(0);" class="um-field-row-action-duplicate"><?php esc_html_e( 'Duplicate', 'ultimate-member' ); ?></a>
										<a href="javascript:void(0);" class="um-field-row-action-delete"><?php esc_html_e( 'Delete', 'ultimate-member' ); ?></a>
									</span>
								</div>
								<div class="um-field-row-content">
									<div class="um-field-row-tabs">
										<?php
										foreach ( $field_settings_tabs as $tab_key => $tab_title ) {
											if ( empty( $field_settings_settings[ $tab_key ] ) ) {
												continue;
											}
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
										foreach ( $field_settings_settings as $tab_key => $settings_fields ) {
											if ( empty( $settings_fields ) ) {
												continue;
											}
											$classes = array();
											if ( 'general' === $tab_key ) {
												// General tab is selected by default for the new field.
												$classes[] = 'current';
											}
											?>
											<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
												<?php
												echo UM()->admin()->field_group()->get_tab_fields_html( $tab_key, array( 'type' => UM()->admin()->field_group()->get_field_type( $field, true ), 'index' => $row_key ) );
												?>
											</div>
											<?php
										}
										?>
									</div>
								</div>
							</div>
							<?php
							$i++;
						}
					}
					?>
				</div>
				<div class="um-fields-column-empty-content<?php if ( ! empty( $value ) ) { ?> hidden<?php } ?>">
					<strong><?php esc_html_e( 'There aren\'t any fields yet. Add them below.', 'ultimate-member' ); ?></strong>
				</div>
				<div class="um-fields-column-footer">
					<input type="button" class="um-add-field-to-column button button-primary" value="<?php esc_attr_e( 'Add new field', 'ultimate-member' ); ?>" />
				</div>
			</div>

			<?php
			return ob_get_clean();
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_conditional_rules( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$value = $this->get_field_value( $field_data );

//			$id = ( ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] : '' ) . '_' . $field_data['id'];
//			$id_attr        = ' id="' . esc_attr( $id ) . '" ';
//			$id_attr_hidden = ' id="' . esc_attr( $id ) . '_hidden" ';
//
//			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
//			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';
//			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';
//
//			$data = array(
//				'field_id' => $field_data['id']
//			);
//
//			if ( ! empty( $field_data['data'] ) ) {
//				$data = array_merge( $data, $field_data['data'] );
//			}
//
//			$data_attr = '';
//			foreach ( $data as $key => $value ) {
//				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
//			}
//
//			$name = $field_data['id'];
//			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
//			$name_attr = ' name="' . $name . '" ';
//

//
//			$description = ! empty( $field_data['description'] ) ? $field_data['description'] : '';
//
//			$field_html = "<input type=\"checkbox\" $id_attr $class_attr $name_attr $data_attr " . checked( $value, true, false ) . " value=\"1\" />";

//			$html = "<input type=\"hidden\" $id_attr_hidden $name_attr value=\"0\" />{$field_html}";

			$description = ! empty( $field_data['description'] ) ? $field_data['description'] : '';

			if ( ! empty( $field_data['name'] ) ) {
				$name = $field_data['name'];
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			}

			$field_types = UM()->config()->get( 'field_types' );
			$field_conditional_rules = UM()->config()->get( 'field_conditional_rules' );
			asort( $field_conditional_rules );
			ob_start();
			?>

			<?php if ( '' !== $description ) { ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php } ?>
			<div class="um-conditional-rules-group-template">
				<div class="um-conditional-rule-row">
					<div class="um-conditional-rules-connect"><?php esc_html_e( 'and', 'ultimate-member' ); ?></div>
					<div class="um-conditional-rule-fields">
						<div class="um-conditional-rule-field-col">
							<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][field]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][0][field]' ); ?>" disabled>
								<option value=""><?php esc_html_e( '(Select Field)', 'ultimate-member' ); ?></option>
							</select>
						</div>
						<div class="um-conditional-rule-condition-col">
							<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][condition]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][0][condition]' ); ?>" disabled>
								<option value=""><?php esc_html_e( '(Select Condition)', 'ultimate-member' ); ?></option>
							</select>
						</div>
						<div class="um-conditional-rule-value-col">
							<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][0][value]' ); ?>" disabled>
								<option value=""><?php esc_html_e( '(Select Value)', 'ultimate-member' ); ?></option>
							</select>
							<input class="um-conditional-rule-setting um-force-disabled" type="text" disabled value="" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][0][value]' ); ?>" placeholder="<?php esc_attr_e( 'Field value', 'ultimate-member' ); ?>" />
						</div>
						<div class="um-conditional-rule-actions-col">
							<input type="button" class="um-conditional-add-rule button" value="<?php esc_attr_e( '+', 'ultimate-member' ); ?>" />
							<input type="button" class="um-conditional-remove-rule button" value="<?php esc_attr_e( '-', 'ultimate-member' ); ?>" />
						</div>
					</div>
				</div>
				<div class="um-conditional-rules-groups-connect"><?php esc_html_e( 'or', 'ultimate-member' ); ?></div>
			</div>
			<div class="um-conditional-rule-row-template">
				<div class="um-conditional-rules-connect"><?php esc_html_e( 'and', 'ultimate-member' ); ?></div>
				<div class="um-conditional-rule-fields">
					<div class="um-conditional-rule-field-col">
						<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][field]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][field]' ); ?>" disabled>
							<option value=""><?php esc_html_e( '(Select Field)', 'ultimate-member' ); ?></option>
						</select>
					</div>
					<div class="um-conditional-rule-condition-col">
						<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][condition]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][condition]' ); ?>" disabled>
							<option value=""><?php esc_html_e( '(Select Condition)', 'ultimate-member' ); ?></option>
						</select>
					</div>
					<div class="um-conditional-rule-value-col">
						<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" disabled>
							<option value=""><?php esc_html_e( '(Select Value)', 'ultimate-member' ); ?></option>
						</select>
						<input class="um-conditional-rule-setting um-force-disabled" type="text" value="" disabled data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" placeholder="<?php esc_attr_e( 'Field value', 'ultimate-member' ); ?>" />
					</div>
					<div class="um-conditional-rule-actions-col">
						<input type="button" class="um-conditional-add-rule button" value="<?php esc_attr_e( '+', 'ultimate-member' ); ?>" />
						<input type="button" class="um-conditional-remove-rule button" value="<?php esc_attr_e( '-', 'ultimate-member' ); ?>" />
					</div>
				</div>
			</div>

			<div class="um-conditional-rules-wrapper">
				<?php if ( empty( $value ) ) { ?>
					<div class="um-conditional-rules-group" data-group-index="0">
						<div class="um-conditional-rule-row">
							<div class="um-conditional-rules-connect"><?php esc_html_e( 'and', 'ultimate-member' ); ?></div>
							<div class="um-conditional-rule-fields">
								<div class="um-conditional-rule-field-col">
									<select class="um-conditional-rule-setting" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][field]' ); ?>" name="<?php echo esc_attr( $name . '[0][0][field]' ); ?>">
										<option value=""><?php esc_html_e( '(Select Field)', 'ultimate-member' ); ?></option>
									</select>
								</div>
								<div class="um-conditional-rule-condition-col">
									<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][condition]' ); ?>" name="<?php echo esc_attr( $name . '[0][0][condition]' ); ?>" disabled>
										<option value=""><?php esc_html_e( '(Select Condition)', 'ultimate-member' ); ?></option>
									</select>
								</div>
								<div class="um-conditional-rule-value-col">
									<select class="um-conditional-rule-setting um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" name="<?php echo esc_attr( $name . '[0][0][value]' ); ?>" disabled>
										<option value=""><?php esc_html_e( '(Select Value)', 'ultimate-member' ); ?></option>
									</select>
									<input class="um-conditional-rule-setting um-force-disabled" type="text" value="" disabled data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" name="<?php echo esc_attr( $name . '[0][0][value]' ); ?>" placeholder="<?php esc_attr_e( 'Field value', 'ultimate-member' ); ?>" />
								</div>
								<div class="um-conditional-rule-actions-col">
									<input type="button" class="um-conditional-add-rule button" value="<?php esc_attr_e( '+', 'ultimate-member' ); ?>" />
									<input type="button" class="um-conditional-remove-rule button" value="<?php esc_attr_e( '-', 'ultimate-member' ); ?>" />
								</div>
							</div>
						</div>
						<div class="um-conditional-rules-groups-connect"><?php esc_html_e( 'or', 'ultimate-member' ); ?></div>
					</div>
					<?php
				} else {
					foreach ( $value as $cond_group_k => $cond_group ) {
						?>
						<div class="um-conditional-rules-group" data-group-index="<?php echo esc_attr( $cond_group_k ); ?>">
							<?php
							foreach ( $cond_group as $cond_row_k => $cond_row ) {
								$field_data = UM()->admin()->field_group()->get_field_data( $cond_row['field'], true );

								$options = array();
								if ( isset( $field_types[ $field_data['type'] ]['category'] ) && 'choice' === $field_types[ $field_data['type'] ]['category'] ) {
									if ( 'bool' === $field_data['type'] ) {
										$options = array(
											'0' => __( 'False', 'ultimate-member' ),
											'1' => __( 'True', 'ultimate-member' ),
										);
									} else {
										$options = array_combine( $field_data['options']['keys'], $field_data['options']['values'] );
									}
								}
								?>
								<div class="um-conditional-rule-row">
									<div class="um-conditional-rules-connect"><?php esc_html_e( 'and', 'ultimate-member' ); ?></div>
									<div class="um-conditional-rule-fields">
										<div class="um-conditional-rule-field-col">
											<select class="um-conditional-rule-setting" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][field]' ); ?>" name="<?php echo esc_attr( $name . '[' . $cond_group_k . '][' . $cond_row_k . '][field]' ); ?>">
												<option value=""><?php esc_html_e( '(Select Field)', 'ultimate-member' ); ?></option>
												<option value="<?php echo esc_attr( $cond_row['field'] ); ?>" selected><?php echo esc_html( $field_data['title'] ); ?></option>
											</select>
										</div>
										<div class="um-conditional-rule-condition-col">
											<select class="um-conditional-rule-setting" data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][condition]' ); ?>" name="<?php echo esc_attr( $name . '[' . $cond_group_k . '][' . $cond_row_k . '][condition]' ); ?>">
												<option value=""><?php esc_html_e( '(Select Condition)', 'ultimate-member' ); ?></option>
												<?php
												foreach ( $field_conditional_rules as $cond_k => $cond_title ) {
													if ( ! in_array( $cond_k, $field_types[ $field_data['type'] ]['conditional_rules'], true ) ) {
														continue;
													}
													?>
													<option value="<?php echo esc_attr( $cond_k ); ?>" <?php selected( $cond_row['condition'], $cond_k ); ?>><?php echo esc_html( $cond_title ); ?></option>
													<?php
												}
												?>
											</select>
										</div>
										<div class="um-conditional-rule-value-col">
											<select class="um-conditional-rule-setting" <?php disabled( in_array( $cond_row['condition'], array( '', '!=empty', '==empty' ), true ) || empty( $options ) ); ?> data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" name="<?php echo esc_attr( $name . '[' . $cond_group_k . '][' . $cond_row_k . '][value]' ); ?>">
												<option value=""><?php esc_html_e( '(Select Value)', 'ultimate-member' ); ?></option>
												<?php
												if ( ! empty( $options ) ) {
													foreach ( $options as $option_k => $option_v ) {
														?>
														<option value="<?php echo esc_attr( $option_k ); ?>" <?php selected( isset( $cond_row['value'] ) && $option_k === (string) $cond_row['value'] ); ?>><?php echo esc_html( $option_v ); ?></option>
														<?php
													}
												}
												?>
											</select>
											<input class="um-conditional-rule-setting" <?php disabled( in_array( $cond_row['condition'], array( '', '!=empty', '==empty' ), true ) || ! empty( $options ) ); ?> data-base-name="<?php echo esc_attr( $name . '[{group_key}][{row_key}][value]' ); ?>" type="text" value="<?php echo isset( $cond_row['value'] ) ? esc_attr( $cond_row['value'] ) : ''; ?>" name="<?php echo esc_attr( $name . '[' . $cond_group_k . '][' . $cond_row_k . '][value]' ); ?>" placeholder="<?php esc_attr_e( 'Field value', 'ultimate-member' ); ?>" />
										</div>
										<div class="um-conditional-rule-actions-col">
											<input type="button" class="um-conditional-add-rule button" value="<?php esc_attr_e( '+', 'ultimate-member' ); ?>" />
											<input type="button" class="um-conditional-remove-rule button" value="<?php esc_attr_e( '-', 'ultimate-member' ); ?>" />
										</div>
									</div>
								</div>
								<?php
							}
							?>
							<div class="um-conditional-rules-groups-connect"><?php esc_html_e( 'or', 'ultimate-member' ); ?></div>
						</div>
						<?php
					}
				}
				?>
				<div class="um-conditional-rules-wrapper-bottom">
					<input type="button" class="um-conditional-add-rules-group button" value="<?php esc_attr_e( 'Add rule group', 'ultimate-member' ); ?>" />
				</div>
			</div>
			<?php
			$html = ob_get_clean();
			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_choices( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$multiple = false;
			if ( ! empty( $field_data['multiple'] ) ) {
				$multiple = $field_data['multiple']; // true - only checkbox | "both" - for both types (e.g. select with trigger multiple <-> single)
			}

			$value = $this->get_field_value( $field_data );

			if ( ! empty( $field_data['name'] ) ) {
				$name = $field_data['name'];
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			}

			$bulk_options = array(
				'countries' => __( 'Countries', 'ultimate-member' ),
				'languages' => __( 'Languages', 'ultimate-member' ),
				'months'    => __( 'Months', 'ultimate-member' ),
				'days'      => __( 'Days', 'ultimate-member' ),
			);
			$bulk_options = apply_filters( 'um_admin_forms_choices_bulk_options', $bulk_options, $field_data );

			// todo $field_data['optgroup'] = true means the selectbox field ability to set the optgroup tags using

			ob_start();
			?>
			<?php if ( ! empty( $field_data['optgroup'] ) ) { ?>
				<span class="um-admin-option-group-row-placeholder" data-option_group_index="{{group_index}}" style="display: none;">
					<span class="um-admin-option-group-rows">
						<span class="um-admin-option-group-move-link"></span>
						<span class="um-admin-option-group-label-wrapper">
							<input class="um-admin-option-group-label um-force-disabled" type="text" disabled data-base-name="<?php echo esc_attr( $name . '[{{group_index}}]' ); ?>" name="<?php echo esc_attr( $name . '[[{{group_index}}]][label]' ); ?>" value="" placeholder="<?php esc_attr_e( 'Option group label', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option group label', 'ultimate-member' ); ?>" />
						</span>
						<span class="um-admin-option-group-row-actions">
							<input type="button" class="um-admin-option-group-row-add button" value="+">
							<input type="button" class="um-admin-option-group-row-remove button" value="-">
						</span>
					</span>
					<span class="um-admin-option-group-rows">
						<span class="um-admin-option-row" data-option_index="{{index}}">
							<input class="um-admin-option-group-options um-force-disabled" type="hidden" disabled data-base-name="<?php echo esc_attr( $name . '[{{group_index}}][options][]' ); ?>" name="<?php echo esc_attr( $name . '[[{{group_index}}]][options][]' ); ?>" value="{{index}}" />
							<span class="um-admin-option-move-link"></span>
							<span class="um-admin-option-row-defaults">
								<?php if ( false === $multiple || 'both' === $multiple ) { ?>
									<input class="um-admin-option-default um-force-disabled" disabled type="radio" name="<?php echo esc_attr( $name . '[default_value]' ); ?>" value="{{index}}" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
								<?php } ?>
								<?php if ( true === $multiple || 'both' === $multiple ) { ?>
									<input class="um-admin-option-default-multi um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[default_value]' ); ?>" disabled type="checkbox" name="<?php echo esc_attr( $name . '[default_value][{{index}}]' ); ?>" value="1" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
								<?php } ?>
							</span>
							<span class="um-admin-option-key-wrapper">
								<input class="um-admin-option-key um-force-disabled" type="text" disabled data-base-name="<?php echo esc_attr( $name . '[keys]' ); ?>" name="<?php echo esc_attr( $name . '[keys][{{index}}]' ); ?>" value="" placeholder="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" />
							</span>
							<span class="um-admin-option-val-wrapper">
								<input class="um-admin-option-val um-force-disabled" type="text" disabled data-base-name="<?php echo esc_attr( $name . '[values]' ); ?>" name="<?php echo esc_attr( $name . '[values][{{index}}]' ); ?>" value="" placeholder="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" />
							</span>
							<span class="um-admin-option-row-actions">
								<input type="button" class="um-admin-option-row-add button" value="+">
								<input type="button" class="um-admin-option-row-remove button" value="-">
							</span>
						</span>
					</span>
				</span>
			<?php } ?>
			<span class="um-admin-option-row-placeholder" data-option_index="{{index}}" style="display: none;">
				<span class="um-admin-option-move-link"></span>
				<span class="um-admin-option-row-defaults">
					<?php if ( false === $multiple || 'both' === $multiple ) { ?>
						<input class="um-admin-option-default um-force-disabled" disabled type="radio" name="<?php echo esc_attr( $name . '[default_value]' ); ?>" value="{{index}}" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
					<?php } ?>
					<?php if ( true === $multiple || 'both' === $multiple ) { ?>
						<input class="um-admin-option-default-multi um-force-disabled" data-base-name="<?php echo esc_attr( $name . '[default_value]' ); ?>" disabled type="checkbox" name="<?php echo esc_attr( $name . '[default_value][{{index}}]' ); ?>" value="1" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
					<?php } ?>
				</span>
				<span class="um-admin-option-key-wrapper">
					<input class="um-admin-option-key um-force-disabled" type="text" disabled data-base-name="<?php echo esc_attr( $name . '[keys]' ); ?>" name="<?php echo esc_attr( $name . '[keys][{{index}}]' ); ?>" value="" placeholder="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" />
				</span>
				<span class="um-admin-option-val-wrapper">
					<input class="um-admin-option-val um-force-disabled" type="text" disabled data-base-name="<?php echo esc_attr( $name . '[values]' ); ?>" name="<?php echo esc_attr( $name . '[values][{{index}}]' ); ?>" value="" placeholder="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" />
				</span>
				<span class="um-admin-option-row-actions">
					<input type="button" class="um-admin-option-row-add button" value="+">
					<input type="button" class="um-admin-option-row-remove button" value="-">
				</span>
			</span>
			<span class="um-admin-option-row-skeleton-placeholder">
				<span class="um-skeleton-box" style="width:52px;"></span>
				<span class="um-skeleton-box" style="width:30%;"></span>
				<span class="um-skeleton-box" style="width:calc( 70% - 142px);"></span>
				<span class="um-skeleton-box" style="width:77px;"></span>
			</span>
			<span class="um-admin-option-bulk-add-wrapper<?php if ( ! empty( $field_data['optgroup'] ) ) { ?> um-admin-option-bulk-enable-optgroup-wrapper<?php } ?>">
				<?php if ( ! empty( $field_data['optgroup'] ) ) { ?>
					<label><input type="checkbox" class="um-admin-option-enable-optgroup" name="<?php echo esc_attr( $name . '[has_groups]' ); ?>" <?php checked( ( ! empty( $value ) && array_key_exists( 'has_groups', $value ) && ! empty( $value['has_groups'] ) ) ); ?> value="1"><?php esc_html_e( 'Enable option groups', 'ultimate-member' ); ?></label>
				<?php } ?>
				<a href="javascript:void(0);" class="um-admin-option-bulk-toggle" data-show-label="<?php esc_attr_e( 'Show presets', 'ultimate-member' ); ?>" data-hide-label="<?php esc_attr_e( 'Hide presets', 'ultimate-member' ); ?>"><?php esc_html_e( 'Show presets', 'ultimate-member' ); ?></a>
			</span>
			<ul class="um-admin-option-bulk-add-list">
				<?php foreach ( $bulk_options as $bulk_action => $bulk_title ) { ?>
					<li>
						<a href="javascript:void(0);" class="um-admin-option-bulk-add" data-bulk_value="<?php echo esc_attr( $bulk_action ); ?>">
							<?php echo esc_html( $bulk_title ); ?>
						</a>
					</li>
				<?php } ?>
			</ul>

			<?php if ( ! empty( $field_data['optgroup'] ) && ! empty( $value ) && array_key_exists( 'has_groups', $value ) && ! empty( $value['has_groups'] ) ) { ?>
				<span class="um-admin-option-rows" data-multiple="<?php echo esc_attr( (string) $multiple ); ?>">
					<?php foreach ( $value['groups'] as $index => $group_data ) { ?>
						<span class="um-admin-option-group-row" data-option_group_index="<?php echo esc_attr( $index ); ?>">
							<span class="um-admin-option-group-rows">
								<span class="um-admin-option-group-move-link"></span>
								<span class="um-admin-option-group-label-wrapper">
									<input class="um-admin-option-group-label" type="text" data-base-name="<?php echo esc_attr( $name . '[{{group_index}}]' ); ?>" name="<?php echo esc_attr( $name . '[[{{group_index}}]][label]' ); ?>" value="<?php echo esc_attr( $group_data['label'] ); ?>" placeholder="<?php esc_attr_e( 'Option group label', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option group label', 'ultimate-member' ); ?>" />
								</span>
								<span class="um-admin-option-group-row-actions">
									<input type="button" class="um-admin-option-group-row-add button" value="+">
									<input type="button" class="um-admin-option-group-row-remove button" value="-">
								</span>
							</span>
							<span class="um-admin-option-group-rows">
								<?php foreach ( $group_data['options'] as $index => $key ) { ?>
									<span class="um-admin-option-row" data-option_index="<?php echo esc_attr( $index ); ?>">
										<span class="um-admin-option-move-link"></span>
										<span class="um-admin-option-row-defaults">
											<?php
											$checked = false;
											if ( array_key_exists( 'default_value', $value ) ) {
												if ( is_array( $value['default_value'] ) ) {
													$checked = in_array( absint( $index ), array_map( 'absint', $value['default_value'] ), true );
												} else {
													$checked = absint( $index ) === absint( $value['default_value'] );
												}
											}
											if ( false === $multiple || 'both' === $multiple ) {
												?>
												<input class="um-admin-option-default" type="radio" <?php checked( $checked ); ?> name="<?php echo esc_attr( $name . '[default_value]' ); ?>" value="<?php echo esc_attr( $index ); ?>" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
												<?php
											}
											if ( true === $multiple || 'both' === $multiple ) {
												?>
												<input class="um-admin-option-default-multi" <?php checked( $checked ); ?> data-base-name="<?php echo esc_attr( $name . '[default_value]' ); ?>" type="checkbox" name="<?php echo esc_attr( $name . '[default_value][' . $index . ']' ); ?>" value="1" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
												<?php
											}
											?>
										</span>
										<span class="um-admin-option-key-wrapper">
											<input class="um-admin-option-key" type="text" data-base-name="<?php echo esc_attr( $name . '[keys]' ); ?>" name="<?php echo esc_attr( $name . '[keys][' . $index . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" />
										</span>
										<span class="um-admin-option-val-wrapper">
											<input class="um-admin-option-val" type="text" data-base-name="<?php echo esc_attr( $name . '[values]' ); ?>" name="<?php echo esc_attr( $name . '[values][' . $index . ']' ); ?>" value="<?php echo esc_attr( $value['values'][ $index ] ); ?>" placeholder="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" />
										</span>
										<span class="um-admin-option-row-actions">
											<input type="button" class="um-admin-option-row-add button" value="+">
											<input type="button" class="um-admin-option-row-remove button" value="-">
										</span>
									</span>
								<?php } ?>
							</span>
						</span>
					<?php } ?>
				</span>
			<?php } else { ?>
				<span class="um-admin-option-rows" data-multiple="<?php echo esc_attr( (string) $multiple ); ?>">
					<?php
					if ( empty( $value ) ) {
						?>
						<span class="um-admin-option-row" data-option_index="0">
							<span class="um-admin-option-move-link"></span>
							<span class="um-admin-option-row-defaults">
								<?php if ( false === $multiple || 'both' === $multiple ) { ?>
									<input class="um-admin-option-default" type="radio" name="<?php echo esc_attr( $name . '[default_value]' ); ?>" value="0" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
								<?php } ?>
								<?php if ( true === $multiple || 'both' === $multiple ) { ?>
									<input class="um-admin-option-default-multi" data-base-name="<?php echo esc_attr( $name . '[default_value]' ); ?>" type="checkbox" name="<?php echo esc_attr( $name . '[default_value][0]' ); ?>" value="1" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
								<?php } ?>
							</span>
							<span class="um-admin-option-key-wrapper">
								<input class="um-admin-option-key" type="text" data-base-name="<?php echo esc_attr( $name . '[keys]' ); ?>" name="<?php echo esc_attr( $name . '[keys][0]' ); ?>" value="" placeholder="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" />
							</span>
							<span class="um-admin-option-val-wrapper">
								<input class="um-admin-option-val" type="text" data-base-name="<?php echo esc_attr( $name . '[values]' ); ?>" name="<?php echo esc_attr( $name . '[values][0]' ); ?>" value="" placeholder="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" />
							</span>
							<span class="um-admin-option-row-actions">
								<input type="button" class="um-admin-option-row-add button" value="+">
								<input type="button" class="um-admin-option-row-remove button" value="-">
							</span>
						</span>
						<?php
					} else {
						foreach ( $value['keys'] as $index => $key ) {
							?>
							<span class="um-admin-option-row" data-option_index="<?php echo esc_attr( $index ); ?>">
								<span class="um-admin-option-move-link"></span>
								<span class="um-admin-option-row-defaults">
									<?php
									$checked = false;
									if ( array_key_exists( 'default_value', $value ) ) {
										if ( is_array( $value['default_value'] ) ) {
											$checked = in_array( absint( $index ), array_map( 'absint', $value['default_value'] ), true );
										} else {
											$checked = absint( $index ) === absint( $value['default_value'] );
										}
									}
									if ( false === $multiple || 'both' === $multiple ) {
										?>
										<input class="um-admin-option-default" type="radio" <?php checked( $checked ); ?> name="<?php echo esc_attr( $name . '[default_value]' ); ?>" value="<?php echo esc_attr( $index ); ?>" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
										<?php
									}
									if ( true === $multiple || 'both' === $multiple ) {
										?>
										<input class="um-admin-option-default-multi" <?php checked( $checked ); ?> data-base-name="<?php echo esc_attr( $name . '[default_value]' ); ?>" type="checkbox" name="<?php echo esc_attr( $name . '[default_value][' . $index . ']' ); ?>" value="1" aria-label="<?php esc_attr_e( 'Does option is default?', 'ultimate-member' ); ?>" />
										<?php
									}
									?>
								</span>
								<span class="um-admin-option-key-wrapper">
									<input class="um-admin-option-key" type="text" data-base-name="<?php echo esc_attr( $name . '[keys]' ); ?>" name="<?php echo esc_attr( $name . '[keys][' . $index . ']' ); ?>" value="<?php echo esc_attr( $key ); ?>" placeholder="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option key', 'ultimate-member' ); ?>" />
								</span>
								<span class="um-admin-option-val-wrapper">
									<input class="um-admin-option-val" type="text" data-base-name="<?php echo esc_attr( $name . '[values]' ); ?>" name="<?php echo esc_attr( $name . '[values][' . $index . ']' ); ?>" value="<?php echo esc_attr( $value['values'][ $index ] ); ?>" placeholder="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" aria-label="<?php esc_attr_e( 'Option label', 'ultimate-member' ); ?>" />
								</span>
								<span class="um-admin-option-row-actions">
									<input type="button" class="um-admin-option-row-add button" value="+">
									<input type="button" class="um-admin-option-row-remove button" value="-">
								</span>
							</span>
							<?php
						}
					}
					?>
				</span>
			<?php } ?>

			<?php if ( ! empty( $field_data['optgroup'] ) ) { ?>
				<span class="um-admin-option-group-row-add-wrapper">
					<input type="button" class="um-admin-option-group-row-add button" value="<?php esc_attr_e( 'Add option group', 'ultimate-member' ); ?>">
				</span>
			<?php } ?>

			<?php
			return ob_get_clean();
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_same_page_update( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';
			$id_attr_hidden = ' id="' . esc_attr( $id ) . '_hidden" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id'              => $field_data['id'],
				'successfully_redirect' => ! empty( $field_data['successfully_redirect'] ) ? $field_data['successfully_redirect'] : '',
			);

			if ( ! empty( $field_data['data'] ) ) {
				$data = array_merge( $data, $field_data['data'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			if ( ! empty( $field_data['upgrade_cb'] ) ) {
				$data_attr .= ' data-log-object="' . esc_attr( $field_data['upgrade_cb'] ) . '" ';
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			$name_attr = ' name="' . $name . '" ';

			$value = $this->get_field_value( $field_data );

			$html = "<input type=\"hidden\" $id_attr_hidden $name_attr value=\"0\" />
			<input type=\"checkbox\" $id_attr $class_attr $name_attr $data_attr " . checked( $value, true, false ) . " value=\"1\" />";

			if ( ! empty( $field_data['upgrade_cb'] ) ) {
				$html .= '<div class="um-same-page-update-wrapper um-same-page-update-' . esc_attr( $field_data['upgrade_cb'] ) . '"><div class="um-same-page-update-description">' . $field_data['upgrade_description'] . '</div><input type="button" data-upgrade_cb="' . $field_data['upgrade_cb'] . '" class="button button-primary um-admin-form-same-page-update" value="' . esc_attr__( 'Run', 'ultimate-member' ) . '"/>
					<div class="upgrade_log"></div></div>';
			}

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_select( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$multiple = ! empty( $field_data['multi'] ) ? 'multiple' : '';

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			if ( ! empty( $field_data['name'] ) ) {
				$name = $field_data['name'];
			} else {
				$name = $field_data['id'];
				$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			}

			$hidden_name_attr = ' name="' . esc_attr( $name ) . '" ';
			$name = $name . ( ! empty( $field_data['multi'] ) ? '[]' : '' );
			$name_attr = ' name="' . esc_attr( $name ) . '" ';

			$value = $this->get_field_value( $field_data );

			$options = '';
			$disabled_options = ! empty( $field_data['disabled_options'] ) ? $field_data['disabled_options'] : array();

			if ( ! empty( $field_data['options'] ) ) {
				foreach ( $field_data['options'] as $key => $option ) {
					if ( is_array( $option ) && ! empty( $option['options'] ) ) {
						// means that it's option group
						if ( empty( $option['options'] ) ) {
							continue;
						}
						$options .= '<optgroup label="' . esc_attr( $option['title'] ) . '">';
						foreach ( $option['options'] as $sub_key => $sub_option ) {
							if ( ! empty( $field_data['multi'] ) ) {
								if ( ! is_array( $value ) || empty( $value ) ) {
									$value = array();
								}

								$options .= '<option value="' . $sub_key . '" ' . selected( in_array( $sub_key, $value ), true, false ) . ' ' . disabled( in_array( $sub_key, $disabled_options, true ), true, false ) . '>' . esc_html( $sub_option ) . '</option>';
							} else {
								$options .= '<option value="' . $sub_key . '" ' . selected( (string)$sub_key == $value, true, false ) . ' ' . disabled( in_array( $sub_key, $disabled_options, true ), true, false ) . '>' . esc_html( $sub_option ) . '</option>';
							}
						}
						$options .= '</optgroup>';
					} else {
						if ( ! empty( $field_data['multi'] ) ) {
							if ( ! is_array( $value ) || empty( $value ) ) {
								$value = array();
							}

							$options .= '<option value="' . $key . '" ' . selected( in_array( $key, $value ), true, false ) . ' ' . disabled( in_array( $key, $disabled_options, true ), true, false ) . '>' . esc_html( $option ) . '</option>';
						} else {
							$options .= '<option value="' . $key . '" ' . selected( (string)$key == $value, true, false ) . ' ' . disabled( in_array( $key, $disabled_options, true ), true, false ) . '>' . esc_html( $option ) . '</option>';
						}
					}
				}
			}

			$hidden = '';
			if ( ! empty( $multiple ) ) {
				$hidden = "<input type=\"hidden\" $hidden_name_attr value=\"\" " . disabled( ! empty( $field_data['disabled'] ), true, false ) . " />";
			}
			$html = "$hidden<select $multiple $id_attr $name_attr $class_attr $data_attr " . disabled( ! empty( $field_data['disabled'] ), true, false ) . ">$options</select>";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @since 3.0
		 *
		 * @return bool|string
		 */
		public function render_page_select( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$multiple = ! empty( $field_data['multi'] ) ? 'multiple' : '';

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] . ' ' : ' ';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';
			$class_attr = ' class="um-forms-field um-pages-select2 ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id'],
			);

			if ( ! empty( $field_data['placeholder'] ) ) {
				$data['placeholder'] = $field_data['placeholder'];
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			$hidden_name_attr = ' name="' . $name . '" ';
			$name = $name . ( ! empty( $field_data['multi'] ) ? '[]' : '' );
			$name_attr = ' name="' . $name . '" ';

			$value = $this->get_field_value( $field_data );

			$options = '<option value="">' . esc_html( $data['placeholder'] ) . '</option>';
			if ( ! empty( $field_data['options'] ) ) {
				foreach ( $field_data['options'] as $key => $option ) {
					if ( ! empty( $field_data['multi'] ) ) {

						if ( ! is_array( $value ) || empty( $value ) ) {
							$value = array();
						}

						$options .= '<option value="' . $key . '" ' . selected( in_array( $key, $value ), true, false ) . '>' . esc_html( $option ) . '</option>';
					} else {
						$options .= '<option value="' . $key . '" ' . selected( (string)$key == $value, true, false ) . '>' . esc_html( $option ) . '</option>';
					}
				}
			}

			$hidden = '';
			if ( ! empty( $multiple ) ) {
				$hidden = "<input type=\"hidden\" $hidden_name_attr value=\"\" />";
			}

			$button = '';
			$slug = str_replace( 'core_', '', $field_data['id'] );
			if ( ! um_get_predefined_page_id( $slug ) || 'publish' !== get_post_status( um_get_predefined_page_id( $slug ) ) ) {
				$button = '&nbsp;<a href="' . esc_url( add_query_arg( array( 'um_adm_action' => 'install_predefined_page', 'um_page_slug' => $slug ) ) ) . '" class="button button-primary">' . esc_html__( 'Create Default', 'ultimate-member' ) . '</a>';
			}

			$html = "$hidden<select $multiple $id_attr $name_attr $class_attr $data_attr>$options</select>$button";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_multi_selects( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];

			$sorting = ! empty( $field_data['sorting'] ) ? $field_data['sorting'] : false;

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';
			$class .= ! empty( $sorting ) ? 'um-sorting-enabled' : '';
			$class_attr = ' class="um-forms-field ' . $class . '" ';

			$data = array(
				'field_id' => $field_data['id'],
				'id_attr' => $id
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			$name = "{$name}[]";
			$name_attr = ' name="' . $name . '" ';

			$values = $this->get_field_value( $field_data );

			$options = '';
			foreach ( $field_data['options'] as $key => $option ) {
				$options .= '<option value="' . $key . '">' . $option . '</option>';
			}

			$html = "<select class=\"um-hidden-multi-selects\" $data_attr>$options</select>";
			$html .= "<ul class=\"um-multi-selects-list" . ( ! empty( $sorting ) ? ' um-sortable-multi-selects' : '' ) . "\" $data_attr>";

			if ( $sorting && is_array( $values ) ) {
				ksort( $values );
			}

			if ( ! empty( $values ) && is_array( $values ) ) {
				foreach ( $values as $k => $value ) {

					if ( ! in_array( $value, array_keys( $field_data['options'] ) ) ) {
						continue ;
					}

					$id_attr = ' id="' . esc_attr( $id . '-' . $k ) . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						$options .= '<option value="' . $key . '" ' . selected( $key == $value, true, false ) . '>' . $option . '</option>';
					}

					$html .= '<li class="um-multi-selects-option-line' . ( ! empty( $sorting ) ? ' um-admin-drag-fld' : '' ) . '">';
					if ( $sorting ) {
						$html .= '<span class="um-field-icon"><i class="fas fa-sort"></i></span>';
					}
					$html .= "<span class=\"um-field-wrapper\">
						<select $id_attr $name_attr $class_attr $data_attr>$options</select></span>
						<span class=\"um-field-control\"><a href=\"javascript:void(0);\" class=\"um-select-delete\">" . __( 'Remove', 'ultimate-member' ) . "</a></span></li>";
				}
			} elseif ( ! empty( $field_data['show_default_number'] ) && is_numeric( $field_data['show_default_number'] ) && $field_data['show_default_number'] > 0 ) {
				$i = 0;
				while ( $i < $field_data['show_default_number'] ) {
					$id_attr = ' id="' . $id . '-' . $i . '" ';

					$options = '';
					foreach ( $field_data['options'] as $key => $option ) {
						$options .= '<option value="' . $key . '">' . $option . '</option>';
					}

					$html .= '<li class="um-multi-selects-option-line' . ( ! empty( $sorting ) ? ' um-admin-drag-fld' : '' ) . '">';
					if ( $sorting ) {
						$html .= '<span class="um-field-icon"><i class="fas fa-sort"></i></span>';
					}

					$html .= "<span class=\"um-field-wrapper\">
						<select $id_attr $name_attr $class_attr $data_attr>$options</select></span>
						<span class=\"um-field-control\"><a href=\"javascript:void(0);\" class=\"um-select-delete\">" . __( 'Remove', 'ultimate-member' ) . "</a></span></li>";

					$i++;
				}
			}

			$html .= "</ul><a href=\"javascript:void(0);\" class=\"button button-primary um-multi-selects-add-option\" data-name=\"$name\">{$field_data['add_text']}</a>";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_multi_checkbox( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;

			$values = $this->get_field_value( $field_data );
			if ( empty( $values ) ) {
				$values = array();
			}

			$i = 0;
			$html = '';

			$columns = ( ! empty( $field_data['columns'] ) && is_numeric( $field_data['columns'] ) ) ? $field_data['columns'] : 1;
			while ( $i < $columns ) {
				$per_page = ceil( count( $field_data['options'] ) / $columns );
				$section_fields_per_page = array_slice( $field_data['options'], $i*$per_page, $per_page, true );
				$html .= '<span class="um-form-fields-section" style="width:' . floor( 100 / $columns ) . '% !important;">';

				foreach ( $section_fields_per_page as $k => $title ) {
					$id_attr = ' id="' . esc_attr( $id . '_' . $k ) . '" ';
					$for_attr = ' for="' . esc_attr( $id . '_' . $k ) . '" ';
					$name_attr = ' name="' . $name . '[' . $k . ']" ';

					$data = array(
						'field_id' => $field_data['id'] . '_' . $k,
					);

					if ( ! empty( $field_data['data'] ) ) {
						$data = array_merge( $data, $field_data['data'] );
					}

					$data_attr = '';
					foreach ( $data as $key => $value ) {
						if ( $value == 'checkbox_key' ) {
							$value = $k;
						}
						$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
					}

					$html .= "<label $for_attr>
						<input type=\"checkbox\" " . checked( in_array( $k, $values ), true, false ) . "$id_attr $name_attr $data_attr value=\"1\" $class_attr>
						<span>$title</span>
					</label>";
				}

				$html .= '</span>';
				$i++;
			}

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_multi_text( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];

			$size = ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class_attr = ' class="um-forms-field ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id'   => $field_data['id'],
				'id_attr'    => $id,
				'item_class' => "um-multi-text-option-line {$size}",
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			$name = "{$name}[]";
			$name_attr = ' name="' . $name . '" ';

			$values = $this->get_field_value( $field_data );

			$html = "<input type=\"text\" class=\"um-hidden-multi-text\" $data_attr />";
			$html .= "<ul class=\"um-multi-text-list\" $data_attr>";

			if ( ! empty( $values ) ) {
				foreach ( $values as $k => $value ) {
					$value = esc_attr( $value );
					$id_attr = ' id="' . esc_attr( $id . '-' . $k ) . '" ';

					$html .= "<li class=\"um-multi-text-option-line {$size}\"><span class=\"um-field-wrapper\">
						<input type=\"text\" $id_attr $name_attr $class_attr $data_attr value=\"$value\" /></span>
						<span class=\"um-field-control\"><a href=\"javascript:void(0);\" class=\"um-text-delete\">" . __( 'Remove', 'ultimate-member' ) . "</a></span></li>";
				}
			} elseif ( ! empty( $field_data['show_default_number'] ) && is_numeric( $field_data['show_default_number'] ) && $field_data['show_default_number'] > 0 ) {
				$i = 0;
				while( $i < $field_data['show_default_number'] ) {
					$id_attr = ' id="' . esc_attr( $id . '-' . $i ) . '" ';

					$html .= "<li class=\"um-multi-text-option-line {$size}\"><span class=\"um-field-wrapper\">
						 <input type=\"text\" $id_attr $name_attr $class_attr $data_attr value=\"\" /></span>
						<span class=\"um-field-control\"><a href=\"javascript:void(0);\" class=\"um-text-delete\">" . __( 'Remove', 'ultimate-member' ) . "</a></span></li>";

					$i++;
				}
			}

			$html .= "</ul><a href=\"javascript:void(0);\" class=\"button button-primary um-multi-text-add-option\" data-name=\"$name\">{$field_data['add_text']}</a>";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_media( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];

			$class  = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? 'um-' . $field_data['size'] . '-field' : 'um-long-field';

			$class_attr_hidden = ' class="um-forms-field um-media-upload-data-url ' . $class . '"';
			$class_attr        = ' class="um-forms-field um-media-upload-url ' . $class . '"';

			$data = array(
				'field_id' => $field_data['id'] . '_url',
			);

			if ( ! empty( $field_data['default']['url'] ) ) {
				$data['default'] = esc_attr( $field_data['default']['url'] );
			}

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= " data-{$key}=\"{$value}\" ";
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;

			$value = $this->get_field_value( $field_data );

			$upload_frame_title = ! empty( $field_data['upload_frame_title'] ) ? $field_data['upload_frame_title'] : __( 'Select media', 'ultimate-member' );

			$image_id = ! empty( $value['id'] ) ? $value['id'] : '';
			$image_width = ! empty( $value['width'] ) ? $value['width'] : '';
			$image_height = ! empty( $value['height'] ) ? $value['height'] : '';
			$image_thumbnail = ! empty( $value['thumbnail'] ) ? $value['thumbnail'] : '';
			$image_url = ! empty( $value['url'] ) ? $value['url'] : '';

			$html = "<div class=\"um-media-upload\">" .
					"<input type=\"hidden\" class=\"um-media-upload-data-id\" name=\"{$name}[id]\" id=\"{$id}_id\" value=\"$image_id\">" .
					"<input type=\"hidden\" class=\"um-media-upload-data-width\" name=\"{$name}[width]\" id=\"{$id}_width\" value=\"$image_width\">" .
					"<input type=\"hidden\" class=\"um-media-upload-data-height\" name=\"{$name}[height]\" id=\"{$id}_height\" value=\"$image_height\">" .
					"<input type=\"hidden\" class=\"um-media-upload-data-thumbnail\" name=\"{$name}[thumbnail]\" id=\"{$id}_thumbnail\" value=\"$image_thumbnail\">" .
					"<input type=\"hidden\" $class_attr_hidden name=\"{$name}[url]\" id=\"{$id}_url\" value=\"$image_url\" $data_attr>";

			if ( ! isset( $field_data['preview'] ) || $field_data['preview'] !== false ) {
				$html .= '<img src="' . $image_url . '" alt="" class="icon_preview"><div style="clear:both;"></div>';
			}

			if ( ! empty( $field_data['url'] ) ) {
				$html .= '<input type="text" ' . $class_attr . ' readonly value="' . $image_url . '" /><div style="clear:both;"></div>';
			}

			$html .= '<input type="button" class="um-set-image button button-primary" value="' . esc_attr__( 'Select', 'ultimate-member' ) . '" data-upload_frame="' . $upload_frame_title . '" />
					<input type="button" class="um-clear-image button" value="' . esc_attr__( 'Clear', 'ultimate-member' ) . '" /></div>';

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_email_template( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class .= ! empty( $field_data['size'] ) ? $field_data['size'] : 'um-long-field';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;

			$value = $this->get_field_value( $field_data );

			ob_start(); ?>

			<div class="email_template_wrapper" data-key="<?php echo $field_data['id'] ?>" style="position: relative;">
				<?php
				wp_editor( $value,
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
			</div>

			<?php $html = ob_get_clean();

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return bool|string
		 */
		public function render_ajax_button( $field_data ) {
			if ( empty( $field_data['id'] ) ) {
				return false;
			}

			$id = ( ! empty( $this->form_data['prefix_id'] ) ? sanitize_title( $this->form_data['prefix_id'] ) : '' ) . '_' . $field_data['id'];
			$id_attr = ' id="' . esc_attr( $id ) . '" ';

			$class = ! empty( $field_data['class'] ) ? $field_data['class'] : '';
			$class_attr = ' class="um-forms-field button ' . esc_attr( $class ) . '" ';

			$data = array(
				'field_id' => $field_data['id']
			);

			$data_attr = '';
			foreach ( $data as $key => $value ) {
				$data_attr .= ' data-' . $key . '="' . esc_attr( $value ) . '" ';
			}

			$name = $field_data['id'];
			$name = ! empty( $this->form_data['prefix_id'] ) ? $this->form_data['prefix_id'] . '[' . $name . ']' : $name;
			$name_attr = ' name="' . $name . '" ';

			$value = $this->get_field_value( $field_data );
			$value_attr = ' value="' . $value . '" ';

			$html = "<input type=\"button\" $id_attr $class_attr $name_attr $data_attr $value_attr /><div class='clear'></div><div class='um-setting_ajax_button_response'></div>";

			return $html;
		}

		/**
		 * @param $field_data
		 *
		 * @return mixed
		 */
		public function render_info_text( $field_data ) {
			return $field_data['value'];
		}

		/**
		 * Get field value
		 *
		 * @param array $field_data
		 * @param string $i
		 * @return string|array
		 */
		public function get_field_value( $field_data, $i = '' ) {
			$default = '';
			if ( $field_data['type'] === 'multi_checkbox' ) {
				$default = array();
				if ( isset( $field_data['default'] ) ) {
					$default = is_array( $field_data['default'] ) ? $field_data['default'] : array( $field_data['default'] );
				}
			}
			if ( isset( $field_data[ 'default' . $i ] ) ) {
				$default = $field_data[ 'default' . $i ];
			}

			if ( $field_data['type'] == 'checkbox' || $field_data['type'] == 'multi_checkbox' ) {
				$value = ( isset( $field_data[ 'value' . $i ] ) && '' !== $field_data[ 'value' . $i ] ) ? $field_data[ 'value' . $i ] : $default;
			} else {
				$value = isset( $field_data[ 'value' . $i ] ) ? $field_data[ 'value' . $i ] : $default;
			}

			$value = is_string( $value ) ? stripslashes( $value ) : $value;

			return $value;
		}
	}
}
