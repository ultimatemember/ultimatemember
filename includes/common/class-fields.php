<?php
namespace um\common;

use DateTimeZone;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Fields' ) ) {

	/**
	 * Class Fields
	 *
	 * @since 3.0.0
	 *
	 * @package um\common
	 */
	class Fields extends \um\core\Fields {

		/**
		 * Standard checkbox field
		 *
		 * @since 3.0.0
		 *
		 * @param  int    $id
		 * @param  string $title
		 * @param  bool   $checked
		 */
		public function checkbox( $id, $title, $checked = true ) {
			/**
			 * Set value on form submission
			 */
			$checked = isset( $_REQUEST[ $id ] ) ? (bool) $_REQUEST[ $id ] : $checked;
			?>
			<div id="um_field_0_<?php echo esc_attr( $id ); ?>" class="um-field um-field-bool um-field-<?php echo esc_attr( $id ); ?> um-field-bool um-field-type_bool" data-key="<?php echo esc_attr( $id ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $id ); ?>" value="0" />
				<div class="um-field-checkbox-area">
					<label class="um-checkbox-label um-size-md"><input name="<?php echo esc_attr( $id ); ?>" type="checkbox" value="1" <?php checked( $checked ); ?>><?php echo esc_html( $title ); ?></label>
				</div>
			</div>
			<?php
		}

		/**
		 * Print field error.
		 *
		 * @since 3.0.0
		 *
		 * @param string $text
		 * @param string $input_id
		 * @param bool   $force_show
		 *
		 * @return string
		 */
		public function field_error( $text, $input_id, $force_show = false ) {
			if ( empty( $text ) ) {
				return '';
			}

			$error_id = 'um-error-for-' . $input_id;

			if ( $force_show ) {
				return '<p class="um-field-hint um-field-error" id="' . esc_attr( $error_id ) . '">' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</p>';
			}

			if ( isset( $this->set_id ) && UM()->form()->processing === $this->set_id ) {
				$output = '<p class="um-field-hint um-field-error" id="' . esc_attr( $error_id ) . '">' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</p>';
			} else {
				$output = '';
			}

			if ( ! UM()->form()->processing ) {
				$output = '<p class="um-field-hint um-field-error" id="' . esc_attr( $error_id ) . '">' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</p>';
			}

			return $output;
		}

		/**
		 * Print field notice.
		 *
		 * @since 3.0.0
		 *
		 * @param string $text
		 * @param string $input_id
		 * @param bool   $force_show
		 *
		 * @return string
		 */
		public function field_notice( $text, $input_id, $force_show = false ) {
			if ( empty( $text ) ) {
				return '';
			}

			$notice_id = 'um-notice-for-' . $input_id;

			if ( $force_show ) {
				return '<p class="um-field-hint um-field-notice"  id="' . esc_attr( $notice_id ) . '">' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</p>';
			}

			if ( isset( $this->set_id ) && UM()->form()->processing === $this->set_id ) {
				$output = '<p class="um-field-hint um-field-notice"  id="' . esc_attr( $notice_id ) . '">' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</p>';
			} else {
				$output = '';
			}

			if ( ! UM()->form()->processing ) {
				$output = '<p class="um-field-hint um-field-notice"  id="' . esc_attr( $notice_id ) . '">' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</p>';
			}

			return $output;
		}

		/**
		 * Display field label.
		 *
		 * @param string $label Field label.
		 * @param string $key   Field key.
		 * @param array  $data  Field data.
		 *
		 * @return string
		 */
		public function field_label( $label, $key, $data ) {
			$output = '';
			$label  = $this->prepare_label( $label, $key, $data );

			$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();
			$for_attr               = '';
			if ( ! in_array( $data['type'], $fields_without_metakey, true ) ) {
				$for_attr = ' for="' . esc_attr( $key . UM()->form()->form_suffix ) . '"';
			}

			$output .= '<label' . $for_attr . '>' . wp_kses_post( $label ) . '</label>';

			return $output;
		}

		/**
		 * Gets selected option value from a callback function. View field mode.
		 *
		 * @param  string|array $value
		 * @param  array        $data
		 * @param  string       $type
		 *
		 * @return string
		 */
		public function get_option_value_from_callback( $value, $data, $type ) {
			if ( ! in_array( $type, array( 'select', 'multiselect' ), true ) ) {
				return $value;
			}

			$key         = $data['metakey'];
			$arr_options = array();
			$choices_callback = $this->get_custom_dropdown_options_source( $key, $data );
			if ( empty( $choices_callback ) ) {
				return $value;
			}
			// @todo check `um_has_dropdown_options_source__$key` and `um_get_field__$key` hooks using here.
			/**
			 * Filters a marker for enable way to populate field options via the filter hook `um_get_field__$key`.
			 *
			 * @param {bool} $has_custom_source Marker for using the hook. Default `false`.
			 *
			 * @return {bool} Populate via hook marker.
			 *
			 * @since 2.0.50
			 * @hook um_has_dropdown_options_source__$key
			 *
			 * @example <caption>Marker for populate options for the field with key `my_key` via hook `um_get_field__my_key`.</caption>
			 * add_filter( 'um_has_dropdown_options_source__my_key', '__return_true' );
			 */
			$has_custom_source = apply_filters( "um_has_dropdown_options_source__$key", false );
			if ( $has_custom_source ) {

				/** This filter is documented in includes/core/class-fields.php */
				$opts        = apply_filters( "um_get_field__$key", array() );
				$arr_options = array_key_exists( 'options', $opts ) ? $opts['options'] : array();

			} elseif ( ! empty( $data['parent_dropdown_relationship'] ) ) {
				$parent_dropdown_relationship = $data['parent_dropdown_relationship'];

				$parent_options = array();
				if ( isset( UM()->form()->post_form[ $parent_dropdown_relationship ] ) ) {
					if ( ! is_array( UM()->form()->post_form[ $parent_dropdown_relationship ] ) ) {
						$parent_options = array( UM()->form()->post_form[ $parent_dropdown_relationship ] );
					} else {
						$parent_options = UM()->form()->post_form[ $parent_dropdown_relationship ];
					}
				} elseif ( um_user( $parent_dropdown_relationship ) ) {
					if ( ! is_array( um_user( $parent_dropdown_relationship ) ) ) {
						$parent_options = array( um_user( $parent_dropdown_relationship ) );
					} else {
						$parent_options = um_user( $parent_dropdown_relationship );
					}
				}

				$arr_options = $choices_callback( $parent_options, $data['parent_dropdown_relationship'] );
			} else {
				$arr_options = $choices_callback();
			}

			if ( empty( $arr_options ) ) {
				return $value;
			}

			if ( 'select' === $type ) {
				if ( ! empty( $arr_options[ $value ] ) ) {
					return $arr_options[ $value ];
				}

				if ( ! empty( $data['default'] ) && isset( $arr_options[ $data['default'] ] ) ) {
					return $arr_options[ $data['default'] ];
				}

				return '';
			}

			// `multiselect` part is here.
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

		/**
		 * Display fields
		 *
		 * @param string $mode
		 * @param array $args
		 *
		 * @return string|null
		 * @throws Exception
		 */
		public function display( $mode, $args ) {
			$output = null;

			$this->global_args = $args;

			UM()->form()->form_suffix = '-' . $this->global_args['form_id'];

			$this->set_mode = $mode;

			if ( 'profile' === $mode ) {
				UM()->form()->nonce = wp_create_nonce( 'um-profile-nonce' . UM()->user()->target_id );
			}

			$this->set_id = absint( $this->global_args['form_id'] );

			$this->field_icons = array_key_exists( 'icons', $this->global_args ) ? $this->global_args['icons'] : 'label';

			// start output here
			$this->get_fields = $this->get_fields();

			if ( empty( $this->get_fields ) ) {
				return $output;
			}

			// find rows
			foreach ( $this->get_fields as $key => $array ) {
				if ( isset( $array['type'] ) && 'row' === $array['type'] ) {
					$this->rows[ $key ] = $array;
					unset( $this->get_fields[ $key ] ); // not needed anymore
				}
			}

			if ( empty( $this->get_fields ) ) {
				return $output;
			}

			// rows fallback
			if ( ! isset( $this->rows ) ) {
				$this->rows = array(
					'_um_row_1' => array(
						'type'     => 'row',
						'id'       => '_um_row_1',
						'sub_rows' => 1,
						'cols'     => 1,
					),
				);
			}

			// Master rows
			foreach ( $this->rows as $row_id => $row_array ) {

				$row_fields = $this->get_fields_by_row( $row_id );
				if ( $row_fields ) {

					$output .= $this->new_row_output( $row_id, $row_array );

					$sub_rows = ( isset( $row_array['sub_rows'] ) ) ? $row_array['sub_rows'] : 1;
					for ( $c = 0; $c < $sub_rows; $c++ ) {
						$output .= '<div class="um-form-row">';
						// cols
						$cols = isset( $row_array['cols'] ) ? $row_array['cols'] : 1;
						if ( is_numeric( $cols ) ) {
							$cols_num = (int) $cols;
						} else {
							if ( strstr( $cols, ':' ) ) {
								$col_split = explode( ':', $cols );
							} else {
								$col_split = array( $cols );
							}
							$cols_num = $col_split[ $c ];
						}

						// sub row fields
						$subrow_fields = $this->get_fields_in_subrow( $row_fields, $c );

						if ( is_array( $subrow_fields ) ) {

							$subrow_fields = $this->array_sort_by_column( $subrow_fields, 'position' );

							$output .= '<div class="um-form-cols um-form-cols-' . esc_attr( $cols_num ) . '">';

							if ( $cols_num == 1 ) {

								$output .= '<div class="um-form-col um-form-col-1">';
								$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
								if ( $col1_fields ) {
									foreach ( $col1_fields as $key => $data ) {
										if ( ! empty( $args['is_block'] ) ) {
											$data['is_block'] = true;
										}
										$output .= $this->edit_field( $key, $data );
									}
								}
								$output .= '</div>';

							} else if ($cols_num == 2) {

								$output .= '<div class="um-form-col um-form-col-1">';

								$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
								if ( $col1_fields ) {
									foreach ( $col1_fields as $key => $data ) {
										if ( ! empty( $args['is_block'] ) ) {
											$data['is_block'] = true;
										}
										$output .= $this->edit_field( $key, $data );
									}
								}
								$output .= '</div>';

								$output .= '<div class="um-form-col um-form-col-2">';

								$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
								if ( $col2_fields ) {
									foreach ( $col2_fields as $key => $data ) {
										if ( ! empty( $args['is_block'] ) ) {
											$data['is_block'] = true;
										}
										$output .= $this->edit_field( $key, $data );
									}
								}
								$output .= '</div>';
							} else {
								$output .= '<div class="um-form-col um-form-col-1">';

								$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
								if ( $col1_fields ) {
									foreach ( $col1_fields as $key => $data ) {
										$output .= $this->edit_field( $key, $data );
									}
								}
								$output .= '</div>';
								$output .= '<div class="um-form-col um-form-col-2">';
								$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
								if ( $col2_fields ) {
									foreach ( $col2_fields as $key => $data ) {
										$output .= $this->edit_field( $key, $data );
									}
								}
								$output .= '</div>';
								$output .= '<div class="um-form-col um-form-col-3">';
								$col3_fields = $this->get_fields_in_column( $subrow_fields, 3 );
								if ( $col3_fields ) {
									foreach ( $col3_fields as $key => $data ) {
										$output .= $this->edit_field( $key, $data );
									}
								}
								$output .= '</div>';
							}

							$output .= '</div>';
						}

						$output .= '</div>';
					}

					$output .= '</div>';
				}
			}

			return $output;
		}

		/**
		 * Display fields ( view mode )
		 *
		 * @param string $mode
		 * @param array $args
		 *
		 * @return string|null
		 * @throws Exception
		 */
		public function display_view( $mode, $args ) {
			$output = null;

			$this->global_args = $args;

			UM()->form()->form_suffix = '-' . $this->global_args['form_id'];

			$this->set_mode = $mode;
			$this->set_id   = absint( $this->global_args['form_id'] );

			$this->field_icons = array_key_exists( 'icons', $this->global_args ) ? $this->global_args['icons'] : 'label';

			// start output here
			$this->get_fields = $this->get_fields();

			if ( empty( $this->get_fields ) || ! is_array( $this->get_fields ) ) {
				return $output;
			}

			// Find rows
			foreach ( $this->get_fields as $key => $array ) {
				if ( isset( $array['type'] ) && 'row' === $array['type'] ) {
					$this->rows[ $key ] = $array;
					unset( $this->get_fields[ $key ] ); // not needed anymore
				}
			}

			if ( empty( $this->get_fields ) ) {
				return $output;
			}

			// Rows fallback
			if ( ! isset( $this->rows ) ) {
				$this->rows = array(
					'_um_row_1' => array(
						'type'     => 'row',
						'id'       => '_um_row_1',
						'sub_rows' => 1,
						'cols'     => 1,
					),
				);
			}

			// Master rows
			foreach ( $this->rows as $row_id => $row_array ) {
				$row_fields = $this->get_fields_by_row( $row_id );
				if ( empty( $row_fields ) ) {
					continue;
				}

				$output .= $this->new_row_output( $row_id, $row_array );

				$sub_rows = array_key_exists( 'sub_rows', $row_array ) ? $row_array['sub_rows'] : 1;

				for ( $c = 0; $c < $sub_rows; $c++ ) {
					$output .= '<div class="um-profile-row">';
					// cols
					$cols = isset( $row_array['cols'] ) ? $row_array['cols'] : 1;
					if ( is_numeric( $cols ) ) {
						$cols_num = (int) $cols;
					} else {
						if ( false !== strpos( $cols, ':' ) ) {
							$col_split = explode( ':', $cols );
						} else {
							$col_split = array( $cols );
						}
						$cols_num = (int) $col_split[ $c ];
					}

					// sub row fields
					$subrow_fields = $this->get_fields_in_subrow( $row_fields, $c );

					if ( is_array( $subrow_fields ) ) {
						$subrow_fields = $this->array_sort_by_column( $subrow_fields, 'position' );

						$output .= '<div class="um-profile-cols um-profile-cols-' . esc_attr( $cols_num ) . '">';

						if ( 1 === $cols_num ) {

							$output .= '<div class="um-profile-col um-profile-col-1">';
							$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
							if ( $col1_fields ) {
								foreach ( $col1_fields as $key => $data ) {
									$data    = $this->view_field_output( $data );
									$output .= $this->view_field( $key, $data );
								}
							}
							$output .= '</div>';

						} elseif ( 2 === $cols_num ) {

							$output .= '<div class="um-profile-col um-profile-col-1">';

							$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
							if ( $col1_fields ) {
								foreach ( $col1_fields as $key => $data ) {
									$data    = $this->view_field_output( $data );
									$output .= $this->view_field( $key, $data );
								}
							}
							$output .= '</div>';

							$output .= '<div class="um-profile-col um-profile-col-2">';

							$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
							if ( $col2_fields ) {
								foreach ( $col2_fields as $key => $data ) {
									$data    = $this->view_field_output( $data );
									$output .= $this->view_field( $key, $data );
								}
							}
							$output .= '</div>';
						} else {
							$output .= '<div class="um-profile-col um-profile-col-1">';

							$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
							if ( $col1_fields ) {
								foreach ( $col1_fields as $key => $data ) {
									$data    = $this->view_field_output( $data );
									$output .= $this->view_field( $key, $data );
								}
							}
							$output .= '</div>';

							$output .= '<div class="um-profile-col um-profile-col-2">';
							$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
							if ( $col2_fields ) {
								foreach ( $col2_fields as $key => $data ) {
									$data    = $this->view_field_output( $data );
									$output .= $this->view_field( $key, $data );
								}
							}
							$output .= '</div>';

							$output .= '<div class="um-profile-col um-profile-col-3">';
							$col3_fields = $this->get_fields_in_column( $subrow_fields, 3 );
							if ( $col3_fields ) {
								foreach ( $col3_fields as $key => $data ) {
									$data    = $this->view_field_output( $data );
									$output .= $this->view_field( $key, $data );
								}
							}
							$output .= '</div>';
						}

						$output .= '</div>';
					}

					$output .= '</div>';
				}

				$output .= '</div>';
			}

			return $output;
		}

		/**
		 * Get new row in form.
		 *
		 * @param string $row_id
		 * @param array  $row_array
		 *
		 * @return string
		 */
		public function new_row_output( $row_id, $row_array ) {
			$output = '';

			$background   = array_key_exists( 'background', $row_array ) ? $row_array['background'] : '';
			$text_color   = array_key_exists( 'text_color', $row_array ) ? $row_array['text_color'] : '';
			$padding      = array_key_exists( 'padding', $row_array ) ? $row_array['padding'] : '';
			$margin       = array_key_exists( 'margin', $row_array ) ? $row_array['margin'] : '';
			$border       = array_key_exists( 'border', $row_array ) ? $row_array['border'] : '';
			$borderradius = array_key_exists( 'borderradius', $row_array ) ? $row_array['borderradius'] : '';
			$borderstyle  = array_key_exists( 'borderstyle', $row_array ) ? $row_array['borderstyle'] : '';
			$bordercolor  = array_key_exists( 'bordercolor', $row_array ) ? $row_array['bordercolor'] : '';
			$heading      = ! empty( $row_array['heading'] );
			$css_class    = array_key_exists( 'css_class', $row_array ) ? $row_array['css_class'] : '';

			$css_borderradius = '';

			// Row CSS rules.
			$css_background = '';
			if ( ! empty( $background ) ) {
				$css_background = 'background-color: ' . esc_attr( $background ) . ';';
			}

			$css_text_color = '';
			if ( ! empty( $text_color ) ) {
				$css_text_color = 'color: ' . esc_attr( $text_color ) . ' !important;';
				$css_class     .= ' um-customized-row';
			}

			$css_padding = '';
			if ( ! empty( $padding ) ) {
				$css_padding = 'padding: ' . esc_attr( $padding ) . ';';
			}

			$css_margin = 'margin: 0 0 30px 0;';
			if ( ! empty( $margin ) ) {
				$css_margin = 'margin: ' . esc_attr( $margin ) . ';';
			}

			$css_border = '';
			if ( ! empty( $border ) ) {
				$css_border = 'border-width: ' . esc_attr( $border ) . ';';
			}

			$css_borderstyle = '';
			if ( ! empty( $borderstyle ) ) {
				$css_borderstyle = 'border-style: ' . esc_attr( $borderstyle ) . ';';
			}

			$css_bordercolor = '';
			if ( ! empty( $bordercolor ) ) {
				$css_bordercolor = 'border-color: ' . esc_attr( $bordercolor ) . ';';
			}

			$header = '';
			// Show the heading.
			if ( $heading ) {
				if ( ! empty( $borderradius ) ) {
					$css_borderradius = 'border-radius: 0px 0px ' . esc_attr( $borderradius ) . ' ' . esc_attr( $borderradius ) . ';';
				}

				$css_heading_background_color = '';
				$css_heading_padding          = '';
				if ( ! empty( $row_array['heading_background_color'] ) ) {
					$css_heading_background_color = 'background-color: ' . $row_array['heading_background_color'] . ';';
					$css_heading_padding          = 'padding: 10px 15px;';
				}

				$css_heading_borderradius = ! empty( $borderradius ) ? 'border-radius: ' . esc_attr( $borderradius ) . ' ' . esc_attr( $borderradius ) . ' 0px 0px;' : '';
				$css_heading_border       = $css_border . $css_borderstyle . $css_bordercolor . $css_heading_borderradius . 'border-bottom-width: 0px;';
				$css_heading_margin       = $css_margin . 'margin-bottom: 0px;';
				$css_heading_text_color   = ! empty( $row_array['heading_text_color'] ) ? 'color: ' . esc_attr( $row_array['heading_text_color'] ) . ';' : '';

				$header .= '<div class="um-row-heading" style="' . esc_attr( $css_heading_margin . $css_heading_padding . $css_heading_border . $css_heading_background_color . $css_heading_text_color ) . '">';

				if ( ! empty( $row_array['icon'] ) ) {
					$css_icon_color = ! empty( $row_array['icon_color'] ) ? 'color: ' . esc_attr( $row_array['icon_color'] ) . ';' : '';
					$header        .= '<span class="um-row-heading-icon" style="' . esc_attr( $css_icon_color ) . '"><i class="' . esc_attr( $row_array['icon'] ) . '"></i></span>';
				}

				if ( ! empty( $row_array['heading_text'] ) ) {
					$header .= esc_html( $row_array['heading_text'] );
				}

				$header .= '</div>';

				$css_border .= 'border-top-width: 0px;';
				$css_margin .= 'margin-top: 0px;';
			} elseif ( ! empty( $borderradius ) ) {
				// No heading.
				$css_borderradius = 'border-radius: ' . esc_attr( $borderradius ) . ';';
			}

			if ( true === $this->viewing ) {
				$output .= '<div class="um-profile-rows ' . esc_attr( $row_id . ' ' . $css_class ) . '" style="' . esc_attr( $css_padding . $css_background . $css_margin . $css_border . $css_borderstyle . $css_bordercolor . $css_borderradius . $css_text_color ) . '">' . $header;
			} else {
				$output .= '<div class="um-form-rows ' . esc_attr( $row_id . ' ' . $css_class ) . '" style="' . esc_attr( $css_padding . $css_background . $css_margin . $css_border . $css_borderstyle . $css_bordercolor . $css_borderradius . $css_text_color ) . '">' . $header;
			}

			return $output;
		}

		/**
		 * Gets a field in 'input mode'
		 *
		 * @param string $key
		 * @param array  $data
		 * @param bool   $rule
		 * @param array  $args
		 *
		 * @return string|null
		 * @throws Exception
		 */
		public function edit_field( $key, $data, $rule = false, $args = array() ) {
			global $_um_profile_id;

			if ( ! empty( $data['is_block'] ) ) {
				$form_suffix = '';
			} else {
				$form_suffix = UM()->form()->form_suffix;
			}

			$output = '';

			if ( empty( $_um_profile_id ) ) {
				$_um_profile_id = um_user( 'ID' );
			}

			if ( ! empty( $data['is_block'] ) && ! is_user_logged_in() ) {
				$_um_profile_id = 0;
			}

			// Get whole field data.
			if ( isset( $data ) && is_array( $data ) ) {
				$origin_data = $this->get_field( $key );
				if ( is_array( $origin_data ) ) {
					// Merge data passed with original field data.
					$data = array_merge( $origin_data, $data );
				}
			}

			if ( ! isset( $data['type'] ) ) {
				return '';
			}
			$type = $data['type'];

			$disabled = '';
			if ( isset( $data['disabled'] ) ) {
				$disabled = $data['disabled'];
			}

			if ( isset( $data['in_group'] ) && '' !== $data['in_group'] && 'group' !== $rule ) {
				return '';
			}

			// forbidden in edit mode? 'edit_forbidden' - it's field attribute predefined in the field data in code
			if ( isset( $data['edit_forbidden'] ) ) {
				return '';
			}

			// required option? 'required_opt' - it's field attribute predefined in the field data in code
			if ( isset( $data['required_opt'] ) ) {
				$opt = $data['required_opt'];
				if ( (bool) UM()->options()->get( $opt[0] ) !== (bool) $opt[1] ) {
					return '';
				}
			}

			// required user permission 'required_perm' - it's field attribute predefined in the field data in code
			if ( isset( $data['required_perm'] ) && ! UM()->roles()->um_user_can( $data['required_perm'] ) ) {
				return '';
			}

			// fields that need to be disabled in edit mode (profile) (email, username, etc.)
			$arr_restricted_fields = $this->get_restricted_fields_for_edit( $_um_profile_id );
			if ( true === $this->editing && 'profile' === $this->set_mode && in_array( $key, $arr_restricted_fields, true ) ) {
				return '';
			}

			if ( 'register' !== $this->set_mode && array_key_exists( 'visibility', $data ) && 'view' === $data['visibility'] ) {
				return '';
			}

			if ( ! um_can_view_field( $data ) ) {
				return '';
			}

			um_fetch_user( $_um_profile_id );

			// Stop return empty values build field attributes:

			if ( 'register' === $this->set_mode && array_key_exists( 'visibility', $data ) && 'view' === $data['visibility'] ) {
				um_fetch_user( get_current_user_id() );
				if ( ! um_user( 'can_edit_everyone' ) ) {
					$disabled = ' disabled="disabled" ';
				}

				um_fetch_user( $_um_profile_id );
				if ( isset( $data['public'] ) && '-2' === $data['public'] && $data['roles'] ) {
					$current_user_roles = um_user( 'roles' );
					if ( ! empty( $current_user_roles ) && count( array_intersect( $current_user_roles, $data['roles'] ) ) > 0 ) {
						$disabled = '';
					}
				}
			}

			if ( true === $this->editing && 'profile' === $this->set_mode ) {
				if ( ! UM()->roles()->um_user_can( 'can_edit_everyone' ) ) {
					// It's for a legacy case `array_key_exists( 'editable', $data )`.
					if ( array_key_exists( 'editable', $data ) && empty( $data['editable'] ) ) {
						$disabled = ' disabled="disabled" ';
					}
				}
			}

			/**
			 * Filters a field disabled attribute.
			 *
			 * @since 2.0
			 * @hook  um_is_field_disabled
			 *
			 * @param {string} $disabled Disable global CSS.
			 * @param {array}  $data     Field data.
			 *
			 * @return {string} Set string to ' disabled="disabled" ' to make a field disabled.
			 *
			 * @example <caption>Make a field disabled on the edit mode.</caption>
			 * function my_is_field_disabled( $disabled, $data ) {
			 *     $disabled = ' disabled="disabled" ';
			 *     return $disabled;
			 * }
			 * add_filter( 'um_is_field_disabled', 'my_is_field_disabled', 10, 2 );
			 */
			$disabled = apply_filters( 'um_is_field_disabled', $disabled, $data );

			$autocomplete = array_key_exists( 'autocomplete', $data ) ? $data['autocomplete'] : 'off';

			$classes = '';
			if ( array_key_exists( 'classes', $data ) ) {
				$classes = explode( ' ', $data['classes'] );
			}

			um_fetch_user( $_um_profile_id );

			$input       = array_key_exists( 'input', $data ) ? $data['input'] : 'text';
			$default     = array_key_exists( 'default', $data ) ? $data['default'] : false;
			$validate    = array_key_exists( 'validate', $data ) ? $data['validate'] : '';
			$placeholder = array_key_exists( 'placeholder', $data ) ? $data['placeholder'] : '';

			$conditional = '';
			if ( ! empty( $data['conditional'] ) ) {
				$conditional = $data['conditional'];
			}

			/**
			 * Filters a field type on the edit mode.
			 *
			 * @since 1.3.x
			 * @hook  um_hook_for_field_{$type}
			 *
			 * @param {string} $type Field Type.
			 *
			 * @return {string} Field Type.
			 *
			 * @example <caption>Change a field type.</caption>
			 * function my_field_type( $type ) {
			 *     // your code here
			 *     return $type;
			 * }
			 * add_filter( 'um_hook_for_field_{$type}', 'my_field_type', 10, 1 );
			 */
			$type = apply_filters( "um_hook_for_field_{$type}", $type );
			switch ( $type ) {
				case 'textarea':
				case 'multiselect':
					$field_id    = $key;
					$field_name  = $key;
					$field_value = $this->field_value( $key, $default, $data );
					break;
				case 'select':
				case 'radio':
					$form_key = str_replace( array( 'role_select', 'role_radio' ), 'role', $key );
					$field_id = $form_key;
					break;
				default:
					$field_id = '';
					break;
			}

			/**
			 * Filters change core id not allowed duplicate.
			 *
			 * @since 2.0.13
			 * @hook  um_completeness_field_id
			 *
			 * @param {string} $field_id Field id.
			 * @param {array}  $data     Field Data.
			 * @param {array}  $args     Optional field arguments.
			 *
			 * @return {string} Field id.
			 *
			 * @example <caption>Change field core id.</caption>
			 * function function_name( $field_id, $data, $args ) {
			 *     // your code here
			 *     return $field_id;
			 * }
			 * add_filter( 'um_completeness_field_id', 'function_name', 10, 3 );
			 */
			$field_id = apply_filters( 'um_completeness_field_id', $field_id, $data, $args );

			/* Begin by field type */
			switch ( $type ) {
				// Default case for integration.
				default:
					$mode = isset( $this->set_mode ) ? $this->set_mode : 'no_mode';

					/**
					 * Filters change field html by $mode and field $type
					 *
					 * @since 1.3.x
					 * @hook  um_edit_field_{$mode}_{$type}
					 *
					 * @param {string} $output Field HTML.
					 * @param {array}  $data   Field Data.
					 *
					 * @return {string} Field HTML.
					 *
					 * @example <caption>Change field html by $mode and field $type.</caption>
					 * function my_edit_field_html( $output, $data ) {
					 *     // your code here
					 *     return $output;
					 * }
					 * add_filter( 'um_edit_field_{$mode}_{$type}', 'my_edit_field_html', 10, 2 );
					 */
					$output .= apply_filters( "um_edit_field_{$mode}_{$type}", $output, $data );
					break;
				/* Other fields with new UI.*/
				case 'googlemap':
				case 'youtube_video':
				case 'vimeo_video':
				case 'spotify':
				case 'soundcloud_track':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					$output .= '<input ' . $disabled . ' class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Text with new UI. */
				case 'text':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					$output .= '<input ' . $disabled . ' autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Phone with new UI.*/
				case 'tel':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<input ' . $disabled . ' autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Number with new UI. */
				case 'number':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$number_limit = '';
					if ( isset( $data['min'] ) ) {
						$number_limit .= ' min="' . esc_attr( $data['min'] ) . '" ';
					}
					if ( isset( $data['max'] ) ) {
						$number_limit .= ' max="' . esc_attr( $data['max'] ) . '" ';
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<input ' . $disabled . ' autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="number" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $number_limit . ' ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Password with new UI. */
				case 'password':
					$original_key = $key;

					if ( 'single_user_password' === $key ) {
						$key = $original_key;

						$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

						if ( isset( $data['label'] ) ) {
							$output .= $this->field_label( $data['label'], $key, $data );
						}

						$field_name  = $key . $form_suffix;
						$field_value = $this->field_value( $key, $default, $data );

						if ( UM()->options()->get( 'toggle_password' ) ) {
							$output .= '<div class="um-field-area-password">
									<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>
									<span class="um-toggle-password um-icon-eye"></span>
								</div>';
						} else {
							$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
						}

						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $field_name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
						} elseif ( ! empty( $data['help'] ) ) {
							$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
						}

						$output .= '</div>';
					} else {
						if ( ( 'account' === $this->set_mode || um_is_core_page( 'account' ) ) && UM()->account()->current_password_is_required( 'password' ) ) {

							$key     = 'current_' . $original_key;
							$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

							if ( isset( $data['label'] ) ) {
								$output .= $this->field_label( __( 'Current Password', 'ultimate-member' ), $key, $data );
							}

							$field_name  = $key . $form_suffix;
							$field_value = $this->field_value( $key, $default, $data );

							if ( UM()->options()->get( 'toggle_password' ) ) {
								$output .= '<div class="um-field-area-password">
										<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>
										<span class="um-toggle-password um-icon-eye"></span>
									</div>';
							} else {
								$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
							}

							if ( $this->is_error( $key ) ) {
								$output .= $this->field_error( $this->show_error( $key ), $field_name );
							} elseif ( $this->is_notice( $key ) ) {
								$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
							} elseif ( ! empty( $data['help'] ) ) {
								$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
							}

							$output .= '</div>';
						}

						$key = $original_key;

						$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

						if ( ( 'account' === $this->set_mode && um_is_core_page( 'account' ) ) || ( 'password' === $this->set_mode && um_is_core_page( 'password-reset' ) ) ) {

							$output .= $this->field_label( __( 'New Password', 'ultimate-member' ), $key, $data );

						} elseif ( isset( $data['label'] ) ) {

							$output .= $this->field_label( $data['label'], $key, $data );

						}

						$name = $key . $form_suffix;
						if ( 'password' === $this->set_mode && um_is_core_page( 'password-reset' ) ) {
							$name = $key;
						}

						$field_value = $this->field_value( $key, $default, $data );
						if ( UM()->options()->get( 'toggle_password' ) ) {
							$output .= '<div class="um-field-area-password">
									<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/>
									<span class="um-toggle-password um-icon-eye"></span>
								</div>';
						} else {
							$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/>';
						}

						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $name );
						} elseif ( ! empty( $data['help'] ) ) {
							$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
						}

						$output .= '</div>';

						if ( 'login' !== $this->set_mode && ! empty( $data['force_confirm_pass'] ) ) {

							$key     = 'confirm_' . $original_key;
							$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

							if ( ! empty( $data['label_confirm_pass'] ) ) {
								$output .= $this->field_label( $data['label_confirm_pass'], $key, $data );
							} elseif ( isset( $data['label'] ) ) {
								// translators: %s: label.
								$output .= $this->field_label( sprintf( __( 'Confirm %s', 'ultimate-member' ), $data['label'] ), $key, $data );
							}

							$name = $key . $form_suffix;
							if ( 'password' === $this->set_mode && um_is_core_page( 'password-reset' ) ) {
								$name = $key;
							}

							if ( ! empty( $data['label_confirm_pass'] ) ) {
								$placeholder = $data['label_confirm_pass'];
							} elseif ( ! empty( $placeholder ) && ! isset( $data['label'] ) ) {
								// translators: %s: placeholder.
								$placeholder = sprintf( __( 'Confirm %s', 'ultimate-member' ), $placeholder );
							} elseif ( isset( $data['label'] ) ) {
								// translators: %s: label.
								$placeholder = sprintf( __( 'Confirm %s', 'ultimate-member' ), $data['label'] );
							}

							if ( UM()->options()->get( 'toggle_password' ) ) {
								$output .= '<div class="um-field-area-password"><input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $this->field_value( $key, $default, $data ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/><span class="um-toggle-password um-icon-eye"></span></div>';
							} else {
								$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $this->field_value( $key, $default, $data ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/>';
							}

							if ( $this->is_error( $key ) ) {
								$output .= $this->field_error( $this->show_error( $key ), $name );
							} elseif ( $this->is_notice( $key ) ) {
								$output .= $this->field_notice( $this->show_notice( $key ), $name );
							} elseif ( ! empty( $data['help'] ) ) {
								$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
							}

							$output .= '</div>';
						}
					}
					break;

				/* URL with new UI. */
				case 'oembed':
				case 'url':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<input ' . $disabled . ' autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="url" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Date with new UI. */
				case 'date':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					if ( ! empty( $field_value ) && false === strpos( $field_value, '-' ) ) {
						$field_value = wp_date( 'Y-m-d', strtotime( $field_value ), new DateTimeZone( 'UTC' ) );
					}

					$output .= '<input ' . $disabled . ' class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="date" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Time with new UI. */
				case 'time':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<input ' . $disabled . ' class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="time" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Row with new UI. It's empty echo. */
				case 'row':
					$output .= '';
					break;
				/* Textarea with new UI.  */
				case 'textarea':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$field_id    = $key;
					$field_name  = $key;
					$field_value = $this->field_value( $key, $default, $data );

					$bio_key = UM()->profile()->get_show_bio_key( $this->global_args );

					if ( ! empty( $data['html'] ) && $bio_key !== $key ) {
						$textarea_settings = array(
							'media_buttons' => false,
							'wpautop'       => false,
							'editor_class'  => $this->get_class( $key, $data ),
							'editor_height' => absint( $data['height'] ),
							'tinymce'       => array(
								'toolbar1' => 'formatselect,bullist,numlist,bold,italic,underline,forecolor,blockquote,hr,removeformat,link,unlink,undo,redo',
								'toolbar2' => '',
							),
						);

						if ( ! empty( $disabled ) ) {
							$textarea_settings['tinymce']['readonly'] = true;
						}

						/**
						 * Filters WP Editor options for textarea init.
						 *
						 * @since 1.3.x
						 * @hook  um_form_fields_textarea_settings
						 *
						 * @param {array} $textarea_settings WP Editor settings.
						 * @param {array} $data              Field data. Since 2.6.5
						 *
						 * @return {array} WP Editor settings.
						 *
						 * @example <caption>Change WP Editor options.</caption>
						 * function function_name( $textarea_settings, $data ) {
						 *     // your code here
						 *     return $textarea_settings;
						 * }
						 * add_filter( 'um_form_fields_textarea_settings', 'function_name', 10, 2 );
						 */
						$textarea_settings = apply_filters( 'um_form_fields_textarea_settings', $textarea_settings, $data );

						$field_value = empty( $field_value ) ? '' : $field_value;

						$placeholder_function = static function ( $output ) use ( $placeholder ) {
							return str_replace( '<textarea ', '<textarea placeholder="' . esc_attr( $placeholder ) . '" ', $output );
						};

						add_filter( 'the_editor', $placeholder_function );

						// turn on the output buffer
						ob_start();

						// echo the editor to the buffer
						wp_editor( $field_value, $key, $textarea_settings );

						// Add the contents of the buffer to the output variable.
						$output .= ob_get_clean();

						remove_filter( 'the_editor', $placeholder_function );
					} else {
						// User 'description' field uses `<textarea>` block everytime.
						$textarea_field_value = '';
						if ( ! empty( $field_value ) ) {
							$show_bio       = false;
							$bio_html       = false;
							$global_setting = UM()->options()->get( 'profile_show_html_bio' );
							if ( isset( $this->global_args['mode'] ) && 'profile' === $this->global_args['mode'] ) {
								if ( ! empty( $this->global_args['use_custom_settings'] ) ) {
									if ( ! empty( $this->global_args['show_bio'] ) ) {
										$show_bio = true;
										$bio_html = ! empty( $global_setting );
									}
								} else {
									$global_show_bio = UM()->options()->get( 'profile_show_bio' );
									if ( ! empty( $global_show_bio ) ) {
										$show_bio = true;
										$bio_html = ! empty( $global_setting );
									}
								}
							}

							if ( $show_bio ) {
								if ( true === $bio_html && ! empty( $data['html'] ) ) {
									$textarea_field_value = $field_value;
								} else {
									$textarea_field_value = wp_strip_all_tags( $field_value );
								}
							} else {
								if ( ! empty( $data['html'] ) ) {
									$textarea_field_value = $field_value;
								} else {
									$textarea_field_value = wp_strip_all_tags( $field_value );
								}
							}
						}
						$output .= '<textarea  ' . $disabled . '  style="height: ' . esc_attr( $data['height'] ) . ';" class="' . esc_attr( $this->get_class( $key, $data ) ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_id ) . '" placeholder="' . esc_attr( $placeholder ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '>' . esc_textarea( $textarea_field_value ) . '</textarea>';
					}

					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Rating */
				case 'rating':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					if ( ! empty( $disabled ) ) {
						$output .= '<div class="um-rating-readonly um-raty ' . esc_attr( $this->get_class( $key, $data ) ) . '" id="' . esc_attr( $field_name ) . '" data-key="' . esc_attr( $key ) . '" data-number="' . esc_attr( $data['number'] ) . '" data-score="' . esc_attr( $field_value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '></div>';
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					} else {
						$output .= '<div class="um-rating um-raty ' . esc_attr( $this->get_class( $key, $data ) ) . '" id="' . esc_attr( $field_name ) . '" data-key="' . esc_attr( $key ) . '" data-number="' . esc_attr( $data['number'] ) . '" data-score="' . esc_attr( $field_value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '></div>';
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';

					break;
				/* Gap/Space with new UI.*/
				case 'spacing':
					$field_style = array();
					if ( array_key_exists( 'spacing', $data ) ) {
						$field_style = array( 'height' => $data['spacing'] );
					}
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data, $field_style ) . '></div>';
					break;
				/* A line divider with new UI.*/
				case 'divider':
					$border_style = '';
					if ( array_key_exists( 'borderwidth', $data ) ) {
						$border_style .= $data['borderwidth'] . 'px';
					}
					if ( array_key_exists( 'borderstyle', $data ) ) {
						$border_style .= ' ' . $data['borderstyle'];
					}
					if ( array_key_exists( 'bordercolor', $data ) ) {
						$border_style .= ' ' . $data['bordercolor'];
					}
					$field_style = array();
					if ( ! empty( $border_style ) ) {
						$field_style = array( 'border-bottom' => $border_style );
					}
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data, $field_style ) . '>';
					if ( ! empty( $data['divider_text'] ) ) {
						$output .= '<div class="um-field-divider-text"><span>' . esc_html( $data['divider_text'] ) . '</span></div>';
					}
					$output .= '</div>';
					break;
				/* Single Image Upload */
				case 'image':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . ' data-mode="' . esc_attr( $this->set_mode ) . '" data-upload-label="' . ( ! empty( $data['button_text'] ) ? esc_attr( $data['button_text'] ) : esc_attr__( 'Upload', 'ultimate-member' ) ) . '">';

					$field_name = $key . $form_suffix;
					if ( in_array( $key, array( 'profile_photo', 'cover_photo' ), true ) ) {
						$field_value = '';
					} else {
						$field_value = $this->field_value( $key, $default, $data );
					}

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					if ( UM()->is_new_ui() ) {
						if ( ! isset( $data['allowed_types'] ) ) {
							$allowed_types = UM()->common()->filesystem()::image_mimes();
						} elseif ( ! is_array( $data['allowed_types'] ) ) {
							$allowed_types = explode( ',', $data['allowed_types'] );
						} else {
							$allowed_types = $data['allowed_types'];
						}

						$uploader_args = array(
							'async'    => false,
							'handler'  => 'field-image',
							'multiple' => false,
							'types'    => $allowed_types,
							'name'     => $field_name,
//							'value'    => $field_value,
							'field_id' => $field_name,
							'button'   => array(
								'id'            => $field_name . '_uploader_button',
								'size'          => 's',
								'icon_position' => 'content',
							),
							'data'     => array(
								'crop' => $data['crop_data'],
							),
						);

						if ( ! empty( $data['max_size'] ) ) {
							$uploader_args['max_upload_size'] = $data['max_size'];
						}

						$uploader_wrapper_classes = array( 'um-field-uploader-wrapper', 'um-field-image-uploader-wrapper' );
						if ( 'profile' === $this->set_mode && ! empty( $field_value ) && 'empty_file' !== $field_value ) {
							$output .= '<div class="um-field-value">';

							$uri   = UM()->files()->get_download_link( UM()->fields()->set_id, $data['metakey'], um_user( 'ID' ) );
							$title = isset( $data['title'] ) ? $data['title'] : __( 'Untitled photo', 'ultimate-member' );

							$removed = false;
							if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $field_value ) ) {
								if ( is_multisite() ) {
									//multisite fix for old customers
									$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $field_value );
									if ( ! file_exists( $file_path ) ) {
										$removed = true;
									}
								} else {
									$removed = true;
								}
							}

							// if value is an image tag
							if ( preg_match( '/\<img.*src=\"([^"]+).*/', $field_value, $matches ) ) {
								$uri = $matches[1];
								// translators: %s is the field name
								$output .= '<a href="#" class="um-photo-modal" data-src="' . esc_url( $uri ) . '" title="' . sprintf( esc_attr__( 'Preview %s', 'ultimate-member' ), esc_attr( $title ) ) . '"><img class="um-photo-modal-img" src="' . esc_url( $uri ) . '" alt="' . esc_attr( $title ) . '" /></a>';
							} elseif ( ! $removed ) {
								// translators: %s is the field name
								$output .= '<a href="#" class="um-photo-modal" data-src="' . esc_url( $uri ) . '" title="' . sprintf( esc_attr__( 'Preview %s', 'ultimate-member' ), esc_attr( $title ) ) . '"><img class="um-photo-modal-img" src="' . esc_url( $uri ) . '" alt="' . esc_attr( $title ) . '" /></a>';
							} else {
								$output .= '';
							}

//							$output .= '<div class="um-field-value-control">';
//							$output .= UM()->frontend()::layouts()::button(
//								__( 'Change', 'ultimate-member' ),
//								array(
//									'size'    => 's',
//									'classes' => array( 'um-field-image-change' ),
//									'design'  => 'secondary-color',
//								)
//							);
//							$output .= UM()->frontend()::layouts()::button(
//								__( 'Remove', 'ultimate-member' ),
//								array(
//									'size'    => 's',
//									'classes' => array( 'um-field-image-remove' ),
//									'design'  => 'tertiary-destructive',
//								)
//							);
//							$output .= '</div></div>';
							$output .= '</div>';

							$uploader_wrapper_classes[] = 'um-display-none';
						}

						$output .= '<div class="' . implode( ' ', $uploader_wrapper_classes ) . '">';

						$output .= UM()->frontend()::layouts()::uploader( $uploader_args );

						$control_classes = array( 'um-field-image-controls' );
						if ( empty( $field_value ) || 'empty_file' === $field_value ) {
							$control_classes[] = 'um-display-none';
						}

						$output .= '<div class="' . implode( ' ', $control_classes ) . '">';
						$output .= UM()->frontend()::layouts()::button(
							__( 'Change', 'ultimate-member' ),
							array(
								'size'    => 's',
								'classes' => array( 'um-field-image-change' ),
								'design'  => 'secondary-color',
							)
						);
						if ( ! empty( $data['crop_data'] ) ) {
							$output .= UM()->frontend()::layouts()::button(
								__( 'Resize', 'ultimate-member' ),
								array(
									'size'    => 's',
									'classes' => array( 'um-field-image-resize' ),
									'design'  => 'secondary-color',
								)
							);
						}
						$output .= UM()->frontend()::layouts()::button(
							__( 'Remove', 'ultimate-member' ),
							array(
								'size'    => 's',
								'classes' => array( 'um-field-image-remove' ),
								'design'  => 'tertiary-destructive',
							)
						);
						$output .= '</div>';

//						if ( 'profile' === $this->set_mode ) {
//							$output .= UM()->frontend()::layouts()::button(
//								__( 'Cancel', 'ultimate-member' ),
//								array(
//									'size'    => 's',
//									'classes' => $cancel_button_classes,
//									'design'  => 'tertiary-gray',
//								)
//							);
//						}

						$output .= '</div>';

						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $field_name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
						} elseif ( ! empty( $data['help'] ) ) {
							$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
						}
					} else {
						$output .= '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';

						$modal_label = array_key_exists( 'label', $data ) ? $data['label'] : __( 'Upload Photo', 'ultimate-member' );
						$output     .= '<div class="um-field-area" style="text-align: center;">';

						if ( ! empty( $field_value ) && 'empty_file' !== $field_value ) {
							if ( ! in_array( $key, array( 'profile_photo', 'cover_photo' ), true ) ) {
								if ( ( isset( $this->set_mode ) && 'register' === $this->set_mode ) || file_exists( UM()->uploader()->get_core_temp_dir() . DIRECTORY_SEPARATOR . $field_value ) ) {
									$img_value = UM()->uploader()->get_core_temp_url() . '/' . $this->field_value( $key, $default, $data );
								} else {
									$img_value = UM()->files()->get_download_link( $this->set_id, $key, um_user( 'ID' ) );
								}
								$img = '<img class="fusion-lazyload-ignore" src="' . esc_attr( $img_value ) . '" alt="" />';
							} else {
								$img = '';
							}
							$output .= '<div class="um-single-image-preview show ' . esc_attr( $data['crop_class'] ) . '" data-crop="' . esc_attr( $data['crop_data'] ) . '" data-key="' . esc_attr( $key ) . '">';
							if ( empty( $disabled ) ) {
								$output .= '<a href="javascript:void(0);" class="cancel"><i class="um-icon-close"></i></a>';
							}
							$output .= $img;
							$output .= '</div>';
							if ( empty( $disabled ) ) {
								$output .= '<a href="javascript:void(0);" data-modal="um_upload_single" data-modal-size="' . esc_attr( $data['modal_size'] ) . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . esc_html__( 'Change photo', 'ultimate-member' ) . '</a>';
							}
						} else {
							$output .= '<div class="um-single-image-preview ' . esc_attr( $data['crop_class'] ) . '" data-crop="' . esc_attr( $data['crop_data'] ) . '" data-key="' . esc_attr( $key ) . '">';
							if ( empty( $disabled ) ) {
								$output .= '<a href="javascript:void(0);" class="cancel"><i class="um-icon-close"></i></a>';
							}
							$output .= '<img class="fusion-lazyload-ignore" src="" alt="" /><div class="um-clear"></div></div>';
							if ( empty( $disabled ) ) {
								$output .= '<a href="javascript:void(0);" data-modal="um_upload_single" data-modal-size="' . esc_attr( $data['modal_size'] ) . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . esc_html( $data['button_text'] ) . '</a>';
							}
						}
						$output .= '</div>';
						/* modal hidden */
						if ( empty( $disabled ) ) {
							if ( ! isset( $data['allowed_types'] ) ) {
								$allowed_types = 'gif,jpg,jpeg,png';
							} elseif ( is_array( $data['allowed_types'] ) ) {
								$allowed_types = implode( ',', $data['allowed_types'] );
							} else {
								$allowed_types = $data['allowed_types'];
							}

							$output .= '<div class="um-modal-hidden-content">';
							$output .= '<div class="um-modal-header"> ' . esc_html( $modal_label ) . '</div>';
							$output .= '<div class="um-modal-body">';
							if ( isset( $this->set_id ) ) {
								$set_id   = $this->set_id;
								$set_mode = $this->set_mode;
							} else {
								$set_id   = 0;
								$set_mode = '';
							}

							$data_icon = '';
							if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
								$data_icon = ' data-icon="' . esc_attr( $data['icon'] ) . '"';
							}

							$nonce   = wp_create_nonce( 'um_upload_nonce-' . $this->timestamp );
							$output .= '<div class="um-single-image-preview ' . esc_attr( $data['crop_class'] ) . '"  data-crop="' . esc_attr( $data['crop_data'] ) . '" data-ratio="' . esc_attr( $data['ratio'] ) . '" data-min_width="' . esc_attr( $data['min_width'] ) . '" data-min_height="' . esc_attr( $data['min_height'] ) . '" data-coord=""><a href="javascript:void(0);" class="cancel"><i class="um-icon-close"></i></a><img class="fusion-lazyload-ignore" src="" alt="" /><div class="um-clear"></div></div><div class="um-clear"></div>';
							$output .= '<div class="um-single-image-upload" data-user_id="' . esc_attr( $_um_profile_id ) . '" data-nonce="' . esc_attr( $nonce ) . '" data-timestamp="' . esc_attr( $this->timestamp ) . '" ' . $data_icon . ' data-set_id="' . esc_attr( $set_id ) . '" data-set_mode="' . esc_attr( $set_mode ) . '" data-type="' . esc_attr( $type ) . '" data-key="' . esc_attr( $key ) . '" data-max_size="' . esc_attr( $data['max_size'] ) . '" data-max_size_error="' . esc_attr( $data['max_size_error'] ) . '" data-min_size_error="' . esc_attr( $data['min_size_error'] ) . '" data-extension_error="' . esc_attr( $data['extension_error'] ) . '"  data-allowed_types="' . esc_attr( $allowed_types ) . '" data-upload_text="' . esc_attr( $data['upload_text'] ) . '" data-max_files_error="' . esc_attr( $data['max_files_error'] ) . '" data-upload_help_text="' . esc_attr( $data['upload_help_text'] ) . '">' . esc_html( $data['button_text'] ) . '</div>';
							$output .= '<div class="um-modal-footer">
									<div class="um-modal-right">
										<a href="javascript:void(0);" class="um-modal-btn um-finish-upload image disabled" data-key="' . esc_attr( $key ) . '" data-change="' . esc_attr__( 'Change photo', 'ultimate-member' ) . '" data-processing="' . esc_attr__( 'Processing...', 'ultimate-member' ) . '">' . esc_html__( 'Apply', 'ultimate-member' ) . '</a>
										<a href="javascript:void(0);" class="um-modal-btn alt" data-action="um_remove_modal"> ' . esc_html__( 'Cancel', 'ultimate-member' ) . '</a>
									</div>
									<div class="um-clear"></div>
								</div>';
							$output .= '</div>';
							$output .= '</div>';
						}
						/* end */
						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $field_name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
						}
					}

					$output .= '</div>';
					break;

				/* Single File Upload */
				case 'file':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . ' data-mode="' . esc_attr( $this->set_mode ) . '" data-upload-label="' . ( ! empty( $data['button_text'] ) ? esc_attr( $data['button_text'] ) : esc_attr__( 'Upload', 'ultimate-member' ) ) . '">';

					$field_name       = $key . $form_suffix;
					$file_field_value = $this->field_value( $key, $default, $data );

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					if ( UM()->is_new_ui() ) {
						if ( ! isset( $data['allowed_types'] ) ) {
							$allowed_types = UM()->common()->filesystem()::file_mimes();
						} elseif ( ! is_array( $data['allowed_types'] ) ) {
							$allowed_types = explode( ',', $data['allowed_types'] );
						} else {
							$allowed_types = $data['allowed_types'];
						}

						$uploader_args = array(
							'async'    => false,
							'handler'  => 'field-file',
							'multiple' => false,
							'types'    => $allowed_types,
							'name'     => $field_name,
//							'value'    => $file_field_value,
							'field_id' => $field_name,
							'button'   => array(
								'id'            => $field_name . '_uploader_button',
								'size'          => 's',
								'icon_position' => 'content',
							),
						);

						if ( ! empty( $data['max_size'] ) ) {
							$uploader_args['max_upload_size'] = $data['max_size'];
						}

						$uploader_wrapper_classes = array( 'um-field-uploader-wrapper', 'um-field-file-uploader-wrapper' );
						// $cancel_button_classes    = array( 'um-field-file-uploader-cancel' );
						if ( 'profile' === $this->set_mode && ! empty( $file_field_value ) && 'empty_file' !== $file_field_value ) {
							$output .= '<div class="um-field-value">';

							$file_type = wp_check_filetype( $file_field_value );
							$uri       = UM()->files()->get_download_link( UM()->fields()->set_id, $data['metakey'], um_user( 'ID' ) );

							$removed = false;
							if ( ! file_exists( UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $file_field_value ) ) {
								if ( is_multisite() ) {
									//multisite fix for old customers
									$file_path = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $file_field_value );
									if ( ! file_exists( $file_path ) ) {
										$removed = true;
									}
								} else {
									$removed = true;
								}
							}

							if ( $removed ) {
								$output .= __( 'This file has been removed.', 'ultimate-member' );
							} else {
								$file_info = um_user( $data['metakey'] . '_metadata' );
								if ( ! empty( $file_info['original_name'] ) ) {
									$file_field_value = $file_info['original_name'];
								}

								$icon    = UM()->frontend()::layouts()::get_file_extension_icon( $file_type['ext'] );
								$output .= '<div class="um-field-single-file">
									' . $icon . '
									<div class="um-field-file-info">
										<span class="um-field-file-filename">' . esc_attr( $file_field_value ) . '</span>
										<a class="um-link um-link-secondary um-link-underline um-field-file-download-link" href="' . esc_url( $uri ) . '" target="_blank" title="' . esc_html__( 'Download', 'ultimate-member' ) . '">' .
										   esc_html__( 'Download', 'ultimate-member' ) .
										   '</a>
									</div>
								</div>';
							}

							$uploader_wrapper_classes[] = 'um-display-none';

//							$output .= '<div class="um-field-value-control">';
//
//							$output .= UM()->frontend()::layouts()::button(
//								__( 'Change', 'ultimate-member' ),
//								array(
//									'size'    => 's',
//									'classes' => array( 'um-field-file-change' ),
//									'design'  => 'tertiary-color',
//								)
//							);
//							$output .= UM()->frontend()::layouts()::button(
//								__( 'Remove', 'ultimate-member' ),
//								array(
//									'size'    => 's',
//									'classes' => array( 'um-field-file-remove' ),
//									'design'  => 'tertiary-destructive',
//								)
//							);
//
//							$output .= '</div></div>';
							$output .= '</div>';
						}/* else {
							$cancel_button_classes[] = 'um-display-none';
						}*/

						$output .= '<div class="' . implode( ' ', $uploader_wrapper_classes ) . '">';

						$output .= UM()->frontend()::layouts()::uploader( $uploader_args );

						$control_classes = array( 'um-field-file-controls' );
						if ( empty( $field_value ) || 'empty_file' === $field_value ) {
							$control_classes[] = 'um-display-none';
						}

						$output .= '<div class="' . implode( ' ', $control_classes ) . '">';
						$output .= UM()->frontend()::layouts()::button(
							__( 'Change', 'ultimate-member' ),
							array(
								'size'    => 's',
								'classes' => array( 'um-field-file-change' ),
								'design'  => 'secondary-color',
							)
						);
						$output .= UM()->frontend()::layouts()::button(
							__( 'Remove', 'ultimate-member' ),
							array(
								'size'    => 's',
								'classes' => array( 'um-field-file-remove' ),
								'design'  => 'tertiary-destructive',
							)
						);
						$output .= '</div>';

//						if ( 'profile' === $this->set_mode ) {
//							$output .= UM()->frontend()::layouts()::button(
//								__( 'Cancel', 'ultimate-member' ),
//								array(
//									'size'    => 's',
//									'classes' => $cancel_button_classes,
//									'design'  => 'tertiary-gray',
//								)
//							);
//						}

						$output .= '</div>';

						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $field_name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
						} elseif ( ! empty( $data['help'] ) ) {
							$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
						}
					} else {
						$output .= '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $file_field_value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
						$modal_label = array_key_exists( 'label', $data ) ? $data['label'] : __( 'Upload File', 'ultimate-member' );
						$output     .= '<div class="um-field-area" style="text-align: center;">';

						if ( ! empty( $file_field_value ) && 'empty_file' !== $file_field_value ) {
							$file_type = wp_check_filetype( $file_field_value );

							if ( um_is_temp_file( $file_field_value ) ) {
								$file_info = get_transient( "um_{$file_field_value}" );
							} else {
								$file_info = um_user( $data['metakey'] . '_metadata' );
							}

							$file_field_name = $file_field_value;
							if ( ! empty( $file_info['original_name'] ) ) {
								$file_field_name = $file_info['original_name'];
							}

							if ( ( isset( $this->set_mode ) && 'register' === $this->set_mode ) || file_exists( UM()->uploader()->get_core_temp_dir() . DIRECTORY_SEPARATOR . $file_field_value ) ) {
								$file_url = UM()->uploader()->get_core_temp_url() . DIRECTORY_SEPARATOR . $file_field_value;
								$file_dir = UM()->uploader()->get_core_temp_dir() . DIRECTORY_SEPARATOR . $file_field_value;
							} else {
								$file_url = UM()->files()->get_download_link( $this->set_id, $key, um_user( 'ID' ) );
								$file_dir = UM()->uploader()->get_upload_base_dir() . um_user( 'ID' ) . DIRECTORY_SEPARATOR . $file_field_value;
							}

							// Multisite fix for old customers.
							if ( ! file_exists( $file_dir ) && is_multisite() ) {
								$file_dir = str_replace( DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $file_dir );
							}

							if ( file_exists( $file_dir ) ) {
								$output .= '<div class="um-single-file-preview show" data-key="' . esc_attr( $key ) . '">';
								if ( empty( $disabled ) ) {
									$output .= '<a href="#" class="cancel"><i class="um-icon-close"></i></a>';
								}

								$fonticon_bg = UM()->files()->get_fonticon_bg_by_ext( $file_type['ext'] );
								$fonticon    = UM()->files()->get_fonticon_by_ext( $file_type['ext'] );

								$output .= '<div class="um-single-fileinfo">';
								$output .= '<a href="' . esc_url( $file_url ) . '" target="_blank">';
								$output .= '<span class="icon" style="background:' . esc_attr( $fonticon_bg ) . '"><i class="' . esc_attr( $fonticon ) . '"></i></span>';
								$output .= '<span class="filename">' . esc_html( $file_field_name ) . '</span>';
								$output .= '</a></div></div>';
							} else {
								$output .= '<div class="um-single-file-preview show" data-key="' . esc_attr( $key ) . '">' . esc_html__( 'This file has been removed.', 'ultimate-member' ) . '</div>';
							}

							if ( empty( $disabled ) ) {
								$output .= '<a href="#" data-modal="um_upload_single" data-modal-size="' . esc_attr( $data['modal_size'] ) . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . esc_html__( 'Change file', 'ultimate-member' ) . '</a>';
							}
						} else {
							$output .= '<div class="um-single-file-preview" data-key="' . esc_attr( $key ) . '"></div>';
							if ( empty( $disabled ) ) {
								$output .= '<a href="#" data-modal="um_upload_single" data-modal-size="' . esc_attr( $data['modal_size'] ) . '" data-modal-copy="1" class="um-button um-btn-auto-width">' . esc_html( $data['button_text'] ) . '</a>';
							}
						}
						$output .= '</div>';
						/* modal hidden */
						if ( empty( $disabled ) ) {
							if ( ! isset( $data['allowed_types'] ) ) {
								$allowed_types = 'pdf,txt';
							} elseif ( is_array( $data['allowed_types'] ) ) {
								$allowed_types = implode( ',', $data['allowed_types'] );
							} else {
								$allowed_types = $data['allowed_types'];
							}

							$output .= '<div class="um-modal-hidden-content">';
							$output .= '<div class="um-modal-header"> ' . esc_html( $modal_label ) . '</div>';
							$output .= '<div class="um-modal-body">';
							if ( isset( $this->set_id ) ) {
								$set_id   = $this->set_id;
								$set_mode = $this->set_mode;
							} else {
								$set_id   = 0;
								$set_mode = '';
							}
							$output .= '<div class="um-single-file-preview">
											<a href="javascript:void(0);" class="cancel"><i class="um-icon-close"></i></a>
											<div class="um-single-fileinfo">
												<a href="" target="_blank">
													<span class="icon"><i></i></span>
													<span class="filename"></span>
												</a>
											</div>
									</div>';

							$data_icon = '';
							if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
								$data_icon = ' data-icon="' . esc_attr( $data['icon'] ) . '"';
							}

							$nonce   = wp_create_nonce( 'um_upload_nonce-' . $this->timestamp );
							$output .= '<div class="um-single-file-upload" data-user_id="' . esc_attr( $_um_profile_id ) . '" data-timestamp="' . esc_attr( $this->timestamp ) . '" data-nonce="' . esc_attr( $nonce ) . '" ' . $data_icon . ' data-set_id="' . esc_attr( $set_id ) . '" data-set_mode="' . esc_attr( $set_mode ) . '" data-type="' . esc_attr( $type ) . '" data-key="' . esc_attr( $key ) . '" data-max_size="' . esc_attr( $data['max_size'] ) . '" data-max_size_error="' . esc_attr( $data['max_size_error'] ) . '" data-min_size_error="' . esc_attr( $data['min_size_error'] ) . '" data-extension_error="' . esc_attr( $data['extension_error'] ) . '"  data-allowed_types="' . esc_attr( $allowed_types ) . '" data-upload_text="' . esc_attr( $data['upload_text'] ) . '" data-max_files_error="' . esc_attr( $data['max_files_error'] ) . '" data-upload_help_text="' . esc_attr( $data['upload_help_text'] ) . '">' . esc_html( $data['button_text'] ) . '</div>';
							$output .= '<div class="um-modal-footer">
										<div class="um-modal-right">
											<a href="javascript:void(0);" class="um-modal-btn um-finish-upload file disabled" data-key="' . esc_attr( $key ) . '" data-change="' . esc_attr__( 'Change file', 'ultimate-member' ) . '" data-processing="' . esc_attr__( 'Processing...', 'ultimate-member' ) . '"> ' . esc_html__( 'Save', 'ultimate-member' ) . '</a>
											<a href="javascript:void(0);" class="um-modal-btn alt" data-action="um_remove_modal"> ' . esc_html__( 'Cancel', 'ultimate-member' ) . '</a>
										</div>
										<div class="um-clear"></div>
									</div>';
							$output .= '</div>';
							$output .= '</div>';
						}
						/* end */
						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $field_name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
						}
					}
					$output .= '</div>';
					break;

				/* Select dropdown with new UI. */
				case 'select':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					// Hardcode here to change 'role_select' or 'role_radio' field key to 'role' field name on the form.
					$form_key = str_replace( array( 'role_select', 'role_radio' ), 'role', $key );
					$field_id = $form_key;

					/**
					 * Filters enable options pair by field $data.
					 *
					 * @since 1.3.x `um_multiselect_option_value`
					 * @since 2.0 renamed to `um_select_options_pair`
					 *
					 * @hook  um_select_options_pair
					 *
					 * @param {bool|null} $options_pair Enable pairs.
					 * @param {array}     $data         Field Data.
					 *
					 * @return {bool} Enable pairs. Set to `true` if a field requires text keys.
					 *
					 * @example <caption>Enable options pair.</caption>
					 * function my_um_select_options_pair( $options_pair, $data ) {
					 *     // your code here
					 *     return $options_pair;
					 * }
					 * add_filter( 'um_select_options_pair', 'my_um_select_options_pair', 10, 2 );
					 */
					$options_pair = apply_filters( 'um_select_options_pair', null, $data );

					$options = array();

					$disabled_by_parent_option = '';

					$atts_ajax        = '';
					$choices_callback = $this->get_custom_dropdown_options_source( $field_id, $data );
					if ( ! empty( $choices_callback ) ) {
						$options_pair = true; // Switch options pair for custom options from a callback function.
						$atts_ajax   .= ' data-um-ajax-source="' . esc_attr( $choices_callback ) . '" ';
						// @todo check on form preview
						if ( ! empty( $data['parent_dropdown_relationship'] ) && ! UM()->user()->preview ) {
							/**
							 * Filters parent dropdown relationship by $field_id.
							 *
							 * @since 1.3.x
							 * @hook  um_custom_dropdown_options_parent__{$field_id}
							 *
							 * @param {string}  $parent  Parent dropdown relationship.
							 * @param {array}   $data    Field Data.
							 *
							 * @return {string} Parent dropdown relationship.
							 *
							 * @example <caption>Change parent dropdown relationship.</caption>
							 * function function_name( $parent, $data ) {
							 *     // your code here
							 *     return $parent;
							 * }
							 * add_filter( 'um_custom_dropdown_options_parent__{$field_id}', 'function_name', 10, 2 );
							 */
							$parent_dropdown_relationship = apply_filters( "um_custom_dropdown_options_parent__$field_id", $data['parent_dropdown_relationship'], $data );
							$atts_ajax                   .= ' data-um-parent="' . esc_attr( $parent_dropdown_relationship ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'um_dropdown_parent_nonce' . $data['metakey'] ) ) . '" ';

							// Don't use double disabled if already disabled.
							if ( empty( $disabled ) ) {
								$disabled_by_parent_option = ' disabled="disabled" ';
							}

							if ( um_user( $parent_dropdown_relationship ) || isset( UM()->form()->post_form[ $form_key ] ) ) {
								// Get parent values from the form's $_POST data or userdata
								$parent_options = array();
								if ( isset( UM()->form()->post_form[ $parent_dropdown_relationship ] ) ) {
									if ( ! is_array( UM()->form()->post_form[ $parent_dropdown_relationship ] ) ) {
										$parent_options = array( UM()->form()->post_form[ $parent_dropdown_relationship ] );
									} else {
										$parent_options = UM()->form()->post_form[ $parent_dropdown_relationship ];
									}
								} elseif ( um_user( $parent_dropdown_relationship ) ) {
									if ( ! is_array( um_user( $parent_dropdown_relationship ) ) ) {
										$parent_options = array( um_user( $parent_dropdown_relationship ) );
									} else {
										$parent_options = um_user( $parent_dropdown_relationship );
									}
								}

								$options = $choices_callback( $parent_options, $parent_dropdown_relationship );

								if ( array_key_exists( '', $options ) ) {
									// There is native placeholder. Fallback if there is empty value.
									unset( $options[''] );
								}

								// Don't use double disabled if already disabled.
								if ( empty( $disabled ) && count( $options ) ) {
									$disabled_by_parent_option = '';
								}
							}
						} else {
							$options = $choices_callback();
						}
					} else {
						// Get options from field settings.
						if ( array_key_exists( 'options', $data ) ) {
							if ( is_array( $data['options'] ) ) {
								$options = $data['options'];
							} elseif ( 'builtin' === $data['options'] && array_key_exists( 'filter', $data ) ) {
								// @todo maybe remove this condition because options can have only `array` type.
								$options = UM()->builtin()->get( $data['filter'] );
							}
						}

						if ( ( 'country' === $key || 'languages' === $key ) && empty( $options ) ) {
							// Fallback for fields 'country' or 'languages' when options are empty.
							$options = UM()->builtin()->get( $key );
						}
					}

					/**
					 * Filters dropdown options.
					 *
					 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
					 * 10 - `um_woocommerce_selectbox_options()` UM:Woocommerce billing and shipping fields.
					 *
					 * @param {array}  $options Field options.
					 * @param {string} $key     Field metakey.
					 *
					 * @return {array} Field options.
					 *
					 * @since 2.0
					 * @hook  um_selectbox_options
					 *
					 * @example <caption>Extend dropdown options.</caption>
					 * function my_um_selectbox_options( $options, $key ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_selectbox_options', 'my_um_selectbox_options', 10, 2 );
					 */
					$options = apply_filters( 'um_selectbox_options', $options, $key );
					/**
					 * Filters dropdown dynamic options.
					 *
					 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
					 * 10 - `um_select_dropdown_dynamic_options_to_utf8()` Filter select dropdown to use UTF-8 encoding
					 * 10 - `um_select_dropdown_dynamic_callback_options()` Returns dropdown options from a callback function.
					 *
					 * @param {array} $options Dynamic options.
					 * @param {array} $data    Field Data.
					 *
					 * @return {array} Dynamic options.
					 *
					 * @since 1.3.x
					 * @hook  um_select_dropdown_dynamic_options
					 *
					 * @example <caption>Extend dropdown dynamic options.</caption>
					 * function my_select_dropdown_dynamic_options( $options, $data ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_select_dropdown_dynamic_options', 'my_select_dropdown_dynamic_options', 10, 2 );
					 */
					$options = apply_filters( 'um_select_dropdown_dynamic_options', $options, $data );
					/**
					 * Filters dropdown dynamic options by field $key.
					 *
					 * Internal Ultimate Member callbacks (Priority -> Callback name -> Excerpt):
					 * 20 - Static anonymous function for getting alphabetical order for states in UM:Woocommerce
					 *
					 * @param {array} $options Dynamic options.
					 *
					 * @return {array} Dynamic options.
					 *
					 * @since 1.3.x
					 * @hook  um_select_dropdown_dynamic_options_{$key}
					 *
					 * @example <caption>Extend dropdown dynamic options by field $key.</caption>
					 * function my_select_dropdown_dynamic_options( $options ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_select_dropdown_dynamic_options_{$key}', 'my_select_dropdown_dynamic_options', 10, 1 );
					 */
					$options = apply_filters( "um_select_dropdown_dynamic_options_{$key}", $options );

					// Filter roles here for getting only available in the options.
					if ( 'role' === $form_key ) {
						$options = $this->get_available_roles( $form_key, $options );
					}

					$field_value = ''; // required to disable hidden fields below.

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$class = 'js-choice um-no-search';

					$output .= '<select data-default="' . esc_attr( $default ) . '" ' . $disabled . ' ' . $disabled_by_parent_option . '  name="' . esc_attr( $form_key ) . '" id="' . esc_attr( $field_id ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" class="' . esc_attr( $this->get_class( $key, $data, $class ) ) . '" style="width: 100%" data-placeholder="' . esc_attr( $placeholder ) . '" ' . $atts_ajax . ' ' . $this->aria_valid_attributes( $this->is_error( $form_key ), $form_key ) . '>';
					if ( ! ( isset( $data['allowclear'] ) && 0 === $data['allowclear'] ) ) {
						$output .= '<option value="">' . esc_html__( 'None', 'ultimate-member' ) . '</option>';
					}

					// add options
					if ( ! empty( $options ) ) {
						foreach ( $options as $k => $v ) {
							$v = rtrim( $v );

							$option_value = $v;

							if ( ( ! is_numeric( $k ) && 'role' === $form_key ) || ( 'account' === $this->set_mode || um_is_core_page( 'account' ) ) ) {
								$option_value = $k;
							}

							if ( isset( $options_pair ) ) {
								$option_value = $k;
							}

							$option_value = $this->filter_field_non_utf8_value( $option_value );

							$output .= '<option value="' . esc_attr( $option_value ) . '" ';

							if ( $this->is_selected( $form_key, $option_value, $data ) ) {
								$output     .= 'selected';
								$field_value = $option_value;
							} elseif ( ! isset( $options_pair ) && $this->is_selected( $form_key, $v, $data ) ) {
								$output     .= 'selected';
								$field_value = $v;
							}

							$output .= '>' . esc_html( $v ) . '</option>';
						}
					}

					$output .= '</select>';

					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $form_key, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $form_key );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $form_key );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Multi-Select dropdown with new UI. */
				case 'multiselect':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$field_id   = $key;
					$field_name = $key;

					/** This filter is documented in includes/core/class-fields.php */
					$options_pair = apply_filters( 'um_select_options_pair', null, $data );

					// Selections count settings.
					$max_selections = isset( $data['max_selections'] ) ? absint( $data['max_selections'] ) : 0;
					$options        = array();
					// Selections count settings.
					$min_selections = isset( $data['min_selections'] ) ? absint( $data['min_selections'] ) : 0;

					$atts_ajax        = '';
					$choices_callback = $this->get_custom_dropdown_options_source( $field_id, $data );
					if ( ! empty( $choices_callback ) ) {
						$options_pair = true; // Switch options pair for custom options from a callback function.
						$atts_ajax .= ' data-um-ajax-source="' . esc_attr( $choices_callback ) . '" ';
						$options    = $choices_callback();
					} else {
						// Get options from field settings.
						if ( array_key_exists( 'options', $data ) ) {
							if ( is_array( $data['options'] ) ) {
								$options = $data['options'];
							} elseif ( 'builtin' === $data['options'] && array_key_exists( 'filter', $data ) ) {
								// @todo maybe remove this condition because options can have only `array` type.
								$options = UM()->builtin()->get( $data['filter'] );
							}
						}

						if ( ( 'country' === $key || 'languages' === $key ) && empty( $options ) ) {
							// Fallback for fields 'country' or 'languages' when options are empty.
							$options = UM()->builtin()->get( $key );
						}
					}

					/**
					 * Filters multiselect options.
					 *
					 * @since 1.3.x
					 * @hook  um_multiselect_options
					 *
					 * @param {array} $options Multiselect Options.
					 * @param {array} $data    Field Data.
					 *
					 * @return {array} Multiselect Options.
					 *
					 * @example <caption>Extend multiselect options.</caption>
					 * function my_multiselect_options( $options, $data ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_multiselect_options', 'my_multiselect_options', 10, 2 );
					 */
					$options = apply_filters( 'um_multiselect_options', $options, $data );
					/**
					 * Filters multiselect options by field $key.
					 *
					 * @since 1.3.x
					 * @hook  um_multiselect_options_{$key}
					 *
					 * @param {array}   $options  Multiselect Options.
					 *
					 * @return {array}  $options  Multiselect Options.
					 *
					 * @example <caption>Extend multiselect options.</caption>
					 * function my_multiselect_options( $options ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_multiselect_options_{$key}', 'my_multiselect_options', 10, 2 );
					 */
					$options = apply_filters( "um_multiselect_options_{$key}", $options );
					/**
					 * Filters multiselect options by field $type.
					 *
					 * @since 1.3.x
					 * @hook  um_multiselect_options_{$type}
					 *
					 * @param {array} $options Multiselect Options.
					 * @param {array} $data    Field Data.
					 *
					 * @return {array} Multiselect Options.
					 *
					 * @example <caption>Extend multiselect options.</caption>
					 * function my_multiselect_options( $options, $data ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_multiselect_options_{$type}', 'my_multiselect_options', 10, 2 );
					 */
					$options = apply_filters( "um_multiselect_options_{$type}", $options, $data );

					$arr_selected = array(); // required to disable hidden fields below.

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$class = 'js-choice';

					$output .= '<select multiple data-default="' . esc_attr( $default ) . '" ' . $disabled . '  name="' . esc_attr( $field_name ) . '[]" id="' . esc_attr( $field_id ) . '" data-validate="' . esc_attr( $validate ) . '" data-max_selections="' . esc_attr( $max_selections ) . '" data-min_selections="' . esc_attr( $min_selections ) . '" data-key="' . esc_attr( $key ) . '" class="' . esc_attr( $this->get_class( $key, $data, $class ) ) . '" style="width: 100%" data-placeholder="' . esc_attr( $placeholder ) . '" ' . $atts_ajax . ' ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '>';

					// add options
					if ( ! empty( $options ) && is_array( $options ) ) {
						foreach ( $options as $k => $v ) {

							$v = rtrim( $v );

							$um_field_checkbox_item_title = $v;
							$opt_value                    = $v;

							if ( $options_pair ) {
								$opt_value = $k;
							}

							$opt_value = $this->filter_field_non_utf8_value( $opt_value );

							$output .= '<option value="' . esc_attr( $opt_value ) . '" ';
							if ( $this->is_selected( $key, $opt_value, $data ) ) {
								$output                    .= 'selected';
								$arr_selected[ $opt_value ] = $opt_value;
							}

							$output .= '>' . esc_html( $um_field_checkbox_item_title ) . '</option>';
						}
					}

					$output .= '</select>';

					if ( ! empty( $disabled ) && ! empty( $arr_selected ) ) {
						foreach ( $arr_selected as $item ) {
							$output .= $this->disabled_hidden_field( $key . '[]', $item );
						}
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Radio with new UI. */
				case 'radio':
					$form_key = str_replace( array( 'role_select', 'role_radio' ), 'role', $key );

					$options = array();
					if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
						$options = $data['options'];
					}

					/**
					 * Filters radio field options.
					 *
					 * @since 1.3.x
					 * @hook  um_radio_field_options
					 *
					 * @param {array} $options Radio Field Options.
					 * @param {array} $data    Field Data.
					 *
					 * @return {array} Radio Field Options.
					 *
					 * @example <caption>Extend radio field options.</caption>
					 * function my_radio_field_options( $options, $data ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_radio_field_options', 'my_radio_field_options', 10, 2 );
					 */
					$options = apply_filters( 'um_radio_field_options', $options, $data );
					/**
					 * Filters radio field options by field $key.
					 *
					 * @since 1.3.x
					 * @hook  um_radio_field_options_{$key}
					 *
					 * @param {array} $options Radio Field Options.
					 *
					 * @return {array} Radio Field Options.
					 *
					 * @example <caption>Extend radio field options.</caption>
					 * function my_radio_field_options( $options ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_radio_field_options_{$key}', 'my_radio_field_options', 10, 1 );
					 */
					$options = apply_filters( "um_radio_field_options_{$key}", $options );
					$options = $this->get_available_roles( $form_key, $options );

					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . ' ' . $this->aria_valid_attributes( $this->is_error( $key ), $form_key ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					// Add options.
					$i           = 0;
					$field_value = array();

					/**
					 * Filters enable options pair by field $data.
					 *
					 * @since 2.0
					 * @hook  um_radio_options_pair__{$key}
					 *
					 * @param {bool}  $options_pair Enable pairs.
					 * @param {array} $data         Field Data.
					 *
					 * @return {bool} Enable pairs.
					 *
					 * @example <caption>Enable options pair.</caption>
					 * function my_radio_field_options( $options ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_radio_options_pair__{$key}', 'my_radio_field_options', 10, 2 );
					 */
					$options_pair = apply_filters( "um_radio_options_pair__{$key}", false, $data );

					$output .= '<div class="um-field-radio-area"><div class="um-field-radio-column">';

					if ( ! empty( $options ) ) {
						$column_separator = count( $options ) / 2;
						foreach ( $options as $k => $v ) {
							$v = rtrim( $v );

							$um_field_checkbox_item_title = $v;
							$option_value                 = $v;

							if ( ( ! is_numeric( $k ) && 'role' === $form_key ) || ( 'account' === $this->set_mode || um_is_core_page( 'account' ) ) ) {
								$option_value = $k;
							}

							if ( $options_pair ) {
								$option_value = $k;
							}

							$i++;
							if ( $i - 1 === $column_separator ) {
								$output .= '</div><div class="um-field-radio-column">';
							}

							$option_value = $this->filter_field_non_utf8_value( $option_value );

							if ( $this->is_radio_checked( $key, $option_value, $data ) ) {
								$field_value[ $key ] = $option_value;
							}

							// It's for a legacy case `array_key_exists( 'editable', $data )`.
							if ( array_key_exists( 'editable', $data ) && empty( $data['editable'] ) ) {
								$disabled = 'disabled';
							}

							$output .= '<label class="um-radio-label um-size-sm"><input ' . $disabled . ' name="' . ( ( 'role' === $form_key ) ? esc_attr( $form_key ) : esc_attr( $form_key ) . '[]' ) . '" type="radio" value="' . esc_attr( $option_value ) . '" ' . checked( $this->is_radio_checked( $key, $option_value, $data ), true, false ) . '/>' . esc_html( $um_field_checkbox_item_title ) . '</label>';
						}
					}

					if ( ! empty( $disabled ) ) {
						foreach ( $field_value as $item ) {
							$output .= $this->disabled_hidden_field( $form_key, $item );
						}
					}

					$output .= '</div></div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $form_key );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $form_key );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Checkbox with new UI. */
				case 'checkbox':
					$options = array();
					if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
						$options = $data['options'];
					}

					/**
					 * Filters checkbox options.
					 *
					 * @since 1.3.x
					 * @hook  um_checkbox_field_options
					 *
					 * @param {array} $options Checkbox Options.
					 * @param {array} $data    Field Data.
					 *
					 * @return {array} Checkbox Options.
					 *
					 * @example <caption>Extend checkbox options.</caption>
					 * function um_checkbox_field_options( $options, $data ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_checkbox_field_options', 'um_checkbox_field_options', 10, 2 );
					 */
					$options = apply_filters( 'um_checkbox_field_options', $options, $data );
					/**
					 * Filters checkbox options by field $key.
					 *
					 * @since 1.3.x
					 * @hook  um_checkbox_field_options_{$key}
					 *
					 * @param {array} $options Checkbox Options.
					 *
					 * @return {array} Checkbox Options.
					 *
					 * @example <caption>Extend checkbox options.</caption>
					 * function my_checkbox_options( $options ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_checkbox_field_options_{$key}', 'my_checkbox_options', 10, 1 );
					 */
					$options = apply_filters( "um_checkbox_field_options_{$key}", $options );

					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . ' ' . $this->aria_valid_attributes( $this->is_error( $key ), $key ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					// Add options.
					$i = 0;

					/**
					 * Filters enable options pair by field $data.
					 *
					 * @since 3.0.0
					 * @hook  um_checkbox_options_pair__{$key}
					 *
					 * @param {bool}  $options_pair Enable pairs.
					 * @param {array} $data         Field Data.
					 *
					 * @return {bool} Enable pairs.
					 *
					 * @example <caption>Enable options pair.</caption>
					 * function my_checkbox_field_options( $options ) {
					 *     // your code here
					 *     return $options;
					 * }
					 * add_filter( 'um_checkbox_options_pair__{$key}', 'my_checkbox_field_options', 10, 2 );
					 */
					$options_pair = apply_filters( "um_checkbox_options_pair__{$key}", false, $data );

					$output .= '<div class="um-field-checkbox-area"><div class="um-field-checkbox-column">';

					if ( ! empty( $options ) ) {
						$column_separator = count( $options ) / 2;
						foreach ( $options as $k => $v ) {
							$v = rtrim( $v );

							$um_field_checkbox_item_title = $v;
							/**
							 * Filters change Checkbox item title.
							 *
							 * @since 1.3.x
							 * @hook  um_field_checkbox_item_title
							 *
							 * @param {string} $um_field_checkbox_item_title Item Title.
							 * @param {string} $key                          Field Key.
							 * @param {string} $v                            Field Value.
							 * @param {array}  $data                         Field Data.
							 *
							 * @return {string} Item Title.
							 *
							 * @example <caption>Change Checkbox item title.</caption>
							 * function um_checkbox_field_options( $um_field_checkbox_item_title, $key, $v, $data ) {
							 *     // your code here
							 *     return $um_field_checkbox_item_title;
							 * }
							 * add_filter( 'um_field_checkbox_item_title', 'um_checkbox_field_options', 10, 4 );
							 */
							$um_field_checkbox_item_title = apply_filters( 'um_field_checkbox_item_title', $um_field_checkbox_item_title, $key, $v, $data );

							if ( $options_pair ) {
								$v = $k;
							}

							$v          = $this->filter_field_non_utf8_value( $v );
							$value_attr = ( ! empty( $v ) && is_string( $v ) ) ? wp_strip_all_tags( $v ) : $v;

							$i++;
							if ( $i - 1 === $column_separator ) {
								$output .= '</div><div class="um-field-checkbox-column">';
							}

							// It's for a legacy case `array_key_exists( 'editable', $data )`.
							if ( array_key_exists( 'editable', $data ) && empty( $data['editable'] ) ) {
								$disabled = 'disabled';
							}

							$output .= '<label class="um-checkbox-label um-size-sm"><input ' . $disabled . ' name="' . esc_attr( $key ) . '[]" type="checkbox" value="' . esc_attr( $value_attr ) . '" ' . checked( $this->is_selected( $key, $v, $data ), true, false ) . ' />' . esc_html( $um_field_checkbox_item_title ) . '</label>';

							if ( ! empty( $disabled ) && $this->is_selected( $key, $v, $data ) ) {
								$output .= $this->disabled_hidden_field( $key . '[]', $value_attr );
							}
						}
					}

					$output .= '</div></div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $key );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $key );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* Bool Checkbox with new UI.*/
				case 'bool':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . ' ' . $this->aria_valid_attributes( $this->is_error( $key ), $key ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-checkbox-area">';
					if ( ! empty( $data['checkbox_label_supported'] ) ) {
						$output .= '<label class="um-checkbox-label um-size-md um-supporting-text"><input type="hidden" ' . $disabled . ' name="' . esc_attr( $key ) . '" value="0" /><input ' . $disabled . ' name="' . esc_attr( $key ) . '" ' . checked( $this->is_selected( $key, true, $data ), true, false ) . ' type="checkbox" value="1" /><span class="um-checkbox-content"><span class="um-text">' . esc_html( $data['checkbox_label'] ) . '</span><br /><span class="um-supporting-text">' . esc_html( $data['checkbox_label_supported'] ) . '</span></span></label>';
					} else {
						$output .= '<label class="um-checkbox-label um-size-md"><input type="hidden" ' . $disabled . ' name="' . esc_attr( $key ) . '" value="0" /><input ' . $disabled . ' name="' . esc_attr( $key ) . '" ' . checked( $this->is_selected( $key, true, $data ), true, false ) . ' type="checkbox" value="1" />' . esc_html( $data['checkbox_label'] ) . '</label>';
					}
					$output .= '</div>';

					if ( ! empty( $disabled ) && $this->is_selected( $key, true, $data ) ) {
						$output .= $this->disabled_hidden_field( $key, 1 );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $key );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $key );
					} elseif ( ! empty( $data['help'] ) ) {
						$output .= '<p class="um-field-hint">' . esc_html( $data['help'] ) . '</p>';
					}

					$output .= '</div>';
					break;
				/* HTML with new UI. */
				case 'block':
					$content = array_key_exists( 'content', $data ) ? $data['content'] : '';
					// @todo WP_KSES for $content
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>' . $content . '</div>';
					break;
				/* Shortcode with new UI. */
				case 'shortcode':
					$content = array_key_exists( 'content', $data ) ? $data['content'] : '';
					$content = str_replace( '{profile_id}', um_profile_id(), $content );
					$content = apply_shortcodes( $content );
					// @todo WP_KSES for $content
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>' . $content . '</div>';
					break;
				/* Unlimited Group */
				case 'group':
					$fields = $this->get_fields_in_group( $key );
					if ( ! empty( $fields ) ) {

						$output .= '<div class="um-field-group" data-max_entries="' . esc_attr( $data['max_entries'] ) . '">
								<div class="um-field-group-head"><i class="um-icon-plus"></i>' . esc_html__( $data['label'], 'ultimate-member' ) . '</div>';
						$output .= '<div class="um-field-group-body"><a href="javascript:void(0);" class="um-field-group-cancel"><i class="um-icon-close"></i></a>';

						foreach ( $fields as $subkey => $subdata ) {
							$output .= $this->edit_field( $subkey, $subdata, 'group' );
						}

						$output .= '</div>';
						$output .= '</div>';

					}
					break;
			}

			// Custom filter for field output.
			if ( isset( $this->set_mode ) ) {
				/**
				 * Filters change field HTML on edit mode by field $key.
				 *
				 * @since 1.3.x
				 * @hook  um_{$key}_form_edit_field
				 *
				 * @param {string} $output Field HTML.
				 * @param {string} $mode   Field Mode.
				 *
				 * @return {string} Field HTML.
				 *
				 * @example <caption>Change field HTML.</caption>
				 * function um_checkbox_field_options( $output, $mode ) {
				 *     // your code here
				 *     return $output;
				 * }
				 * add_filter( 'um_{$key}_form_edit_field', 'my_form_edit_field', 10, 2 );
				 */
				$output = apply_filters( "um_{$key}_form_edit_field", $output, $this->set_mode );
			}

			return $output;
		}
	}
}
