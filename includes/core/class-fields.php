<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Fields' ) ) {

	/**
	 * Class Fields
	 * @package um\core
	 */
	class Fields {


		/**
		 * @var null|string
		 */
		public $set_mode = null;


		/**
		 * @var null|int form_id
		 */
		public $set_id = null;

		/**
		 * @var bool
		 */
		public $editing = false;

		/**
		 * @var bool
		 */
		public $viewing = false;

		/**
		 * @var int
		 */
		public $timestamp = null;

		/**
		 * @var array
		 */
		public $global_args = array();

		/**
		 * @var array
		 */
		public $field_icons = '';

		/**
		 * @var array
		 */
		public $get_fields = array();

		/**
		 * @var array
		 */
		public $rows = array();

		/**
		 * @var array
		 */
		public $fields = array();

		/**
		 * @var bool
		 */
		public $disable_tooltips = false;

		/**
		 * Fields constructor.
		 */
		public function __construct() {
			$this->timestamp = time();
		}

		/**
		 * Standard checkbox field
		 *
		 * @param  integer $id
		 * @param  string  $title
		 * @param  bool $checked
		 */
		function checkbox( $id, $title, $checked = true ) {

			/**
			 * Set value on form submission
			 */
			if ( isset( $_REQUEST[ $id ] ) ) {
				$checked = (bool) $_REQUEST[ $id ];
			}

			$class = $checked ? 'um-icon-android-checkbox-outline' : 'um-icon-android-checkbox-outline-blank';

			?>


			<div class="um-field um-field-c">
				<div class="um-field-area">
					<label class="um-field-checkbox<?php echo $checked ? ' active' : '' ?>">
						<input type="checkbox" name="<?php echo esc_attr( $id ); ?>" value="1" <?php checked( $checked ) ?> />
						<span class="um-field-checkbox-state"><i class="<?php echo esc_attr( $class ) ?>"></i></span>
						<span class="um-field-checkbox-option"> <?php echo esc_html( $title ); ?></span>
					</label>
				</div>
			</div>

			<?php
		}

		/**
		 * Shows social links.
		 */
		public function show_social_urls() {
			$social = array();

			$fields = UM()->builtin()->get_all_user_fields();
			foreach ( $fields as $field => $args ) {
				if ( array_key_exists( 'advanced', $args ) && 'social' === $args['advanced'] ) {
					$social[ $field ] = $args;
				}
			}

			foreach ( $social as $k => $arr ) {
				if ( um_profile( $k ) ) {
					if ( array_key_exists( 'match', $arr ) ) {
						$match = is_array( $arr['match'] ) ? $arr['match'][0] : $arr['match'];
					} else {
						$match = null;
					}
					$arr['url_target'] = isset( $arr['url_target'] ) ? $arr['url_target'] : '_blank';
					?>

					<a href="<?php echo esc_url( um_filtered_social_link( $k, $match ) ); ?>"
					style="background: <?php echo esc_attr( $arr['color'] ); ?>;" target="<?php echo esc_attr( $arr['url_target'] ); ?>" class="um-tip-n"
					title="<?php echo esc_attr( $arr['title'] ); ?>"><i class="<?php echo esc_attr( $arr['icon'] ); ?>"></i></a>

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

			foreach ( $fields as $key => $data ) {
				$output .= $this->edit_field( $key, $data );
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
			return '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"/>';
		}


		/**
		 * Updates a field globally
		 *
		 * @param  integer $id
		 * @param  array   $args
		 */
		function globally_update_field( $id, $args ) {
			$fields = UM()->builtin()->saved_fields;

			$fields[ $id ] = $args;

			if ( array_key_exists( 'custom_dropdown_options_source', $args ) ) {
				if ( function_exists( wp_unslash( $args['custom_dropdown_options_source'] ) ) ) {
					if ( ! $this->is_source_blacklisted( $args['custom_dropdown_options_source'] ) ) {
						$allowed_callbacks = UM()->options()->get( 'allowed_choice_callbacks' );
						if ( ! empty( $allowed_callbacks ) ) {
							$allowed_callbacks = array_map( 'rtrim', explode( "\n", $allowed_callbacks ) );
							$allowed_callbacks[] = $args['custom_dropdown_options_source'];
						} else {
							$allowed_callbacks = array( $args['custom_dropdown_options_source'] );
						}
						$allowed_callbacks = array_unique( $allowed_callbacks );
						$allowed_callbacks = implode( "\r\n", $allowed_callbacks );

						UM()->options()->update( 'allowed_choice_callbacks', $allowed_callbacks );
					}
				}
			}

			unset( $fields[ $id ]['in_row'] );
			unset( $fields[ $id ]['in_sub_row'] );
			unset( $fields[ $id ]['in_column'] );
			unset( $fields[ $id ]['in_group'] );
			unset( $fields[ $id ]['position'] );

			do_action( 'um_add_new_field', $id, $args );

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

			if ( $args['type'] == 'row' ) {
				if ( isset( $fields[ $id ] ) ) {
					$old_args = $fields[ $id ];
					foreach ( $old_args as $k => $v ) {
						if ( ! in_array( $k, array( 'sub_rows', 'cols' ) ) ) {
							unset( $old_args[ $k ] );
						}
					}
					$args = array_merge( $old_args, $args );
				}
			}

			// custom fields support
			if ( isset( UM()->builtin()->predefined_fields[ $id ] ) && isset( UM()->builtin()->predefined_fields[ $id ]['custom'] ) ) {
				$args = array_merge( UM()->builtin()->predefined_fields[ $id ], $args );
			}

			if ( array_key_exists( 'custom_dropdown_options_source', $args ) ) {
				if ( function_exists( wp_unslash( $args['custom_dropdown_options_source'] ) ) ) {
					if ( ! $this->is_source_blacklisted( $args['custom_dropdown_options_source'] ) ) {
						$allowed_callbacks = UM()->options()->get( 'allowed_choice_callbacks' );
						if ( ! empty( $allowed_callbacks ) ) {
							$allowed_callbacks = array_map( 'rtrim', explode( "\n", $allowed_callbacks ) );
							$allowed_callbacks[] = $args['custom_dropdown_options_source'];
						} else {
							$allowed_callbacks = array( $args['custom_dropdown_options_source'] );
						}
						$allowed_callbacks = array_unique( $allowed_callbacks );
						$allowed_callbacks = implode( "\r\n", $allowed_callbacks );

						UM()->options()->update( 'allowed_choice_callbacks', $allowed_callbacks );

						$args['custom_dropdown_options_source'] = wp_unslash( $args['custom_dropdown_options_source'] );
					}
				}
			}

			$fields[ $id ] = $args;

			// for group field only
			if ( $args['type'] == 'group' ) {
				$fields[ $id ]['in_group'] = '';
			}

			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
		}

		/**
		 * Deletes a field in form only
		 *
		 * @param string $id
		 * @param int    $form_id
		 */
		public function delete_field_from_form( $id, $form_id ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );

			if ( isset( $fields[ $id ] ) ) {
				$condition_fields = get_option( 'um_fields', array() );

				if( ! is_array( $condition_fields ) ) $condition_fields = array();

				foreach ( $condition_fields as $key => $value ) {
					$deleted_field = array_search( $id, $value );

					if ( $key != $id && $deleted_field != false ) {
						$deleted_field_id = str_replace( 'conditional_field', '', $deleted_field );

						if ( $deleted_field_id == '' ) {
							$arr_id = 0;
						} else {
							$arr_id = $deleted_field_id;
						}

						unset( $condition_fields[ $key ][ 'conditional_action' . $deleted_field_id ] );
						unset( $condition_fields[ $key ][ $deleted_field ] );
						unset( $condition_fields[ $key ][ 'conditional_operator' . $deleted_field_id ] );
						unset( $condition_fields[ $key ][ 'conditional_value' . $deleted_field_id ] );
						unset( $condition_fields[ $key ]['conditions'][ $arr_id ] );

						unset( $fields[ $key ][ 'conditional_action' . $deleted_field_id ] );
						unset( $fields[ $key ][ $deleted_field ] );
						unset( $fields[ $key ][ 'conditional_operator' . $deleted_field_id ] );
						unset( $fields[ $key ][ 'conditional_value' . $deleted_field_id ] );
						unset( $fields[ $key ]['conditions'][ $arr_id ] );
					}
				}

				update_option( 'um_fields' , $condition_fields );
				unset( $fields[ $id ] );
				UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			}
		}

		/**
		 * Deletes a field from custom fields.
		 *
		 * @param string $id
		 */
		public function delete_field_from_db( $id ) {
			$fields = UM()->builtin()->saved_fields;
			if ( isset( $fields[ $id ] ) ) {
				$args = $fields[ $id ];

				unset( $fields[ $id ] );

				do_action( 'um_delete_custom_field', $id, $args );

				update_option( 'um_fields', $fields );

				global $wpdb;
				$forms = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'um_form'" );
				foreach ( $forms as $form_id ) {
					$form_fields = get_post_meta( $form_id, '_um_custom_fields', true );
					unset( $form_fields[ $id ] );
					update_post_meta( $form_id, '_um_custom_fields', $form_fields );
				}

				$directories = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'um_directory'" );
				foreach ( $directories as $directory_id ) {
					// Frontend filters
					$directory_search_fields = get_post_meta( $directory_id, '_um_search_fields', true );
					$directory_search_fields = ! is_array( $directory_search_fields ) ? array() : $directory_search_fields;
					$directory_search_fields = array_values( array_diff( $directory_search_fields, array( $id ) ) );
					update_post_meta( $directory_id, '_um_search_fields', $directory_search_fields );

					// Admin filtering
					$directory_search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
					if ( isset( $directory_search_filters[ $id ] ) ) {
						unset( $directory_search_filters[ $id ] );
					}
					update_post_meta( $directory_id, '_um_search_filters', $directory_search_filters );

					// display in tagline
					$directory_reveal_fields = get_post_meta( $directory_id, '_um_reveal_fields', true );
					$directory_reveal_fields = ! is_array( $directory_reveal_fields ) ? array() : $directory_reveal_fields;
					$directory_reveal_fields = array_values( array_diff( $directory_reveal_fields, array( $id ) ) );
					update_post_meta( $directory_id, '_um_reveal_fields', $directory_reveal_fields );

					// extra user information section
					$directory_tagline_fields = get_post_meta( $directory_id, '_um_tagline_fields', true );
					$directory_tagline_fields = ! is_array( $directory_tagline_fields ) ? array() : $directory_tagline_fields;
					$directory_tagline_fields = array_values( array_diff( $directory_tagline_fields, array( $id ) ) );
					update_post_meta( $directory_id, '_um_tagline_fields', $directory_tagline_fields );

					// Custom fields selected in "Choose field(s) to enable in sorting"
					$directory_sorting_fields = get_post_meta( $directory_id, '_um_sorting_fields', true );
					$directory_sorting_fields = ! is_array( $directory_sorting_fields ) ? array() : $directory_sorting_fields;
					foreach ( $directory_sorting_fields as $key => $sorting_data ) {
						if ( is_array( $sorting_data ) && array_key_exists( $id, $sorting_data ) ) {
							unset( $directory_sorting_fields[ $key ] );
						}
					}
					$directory_sorting_fields = array_values( $directory_sorting_fields );
					update_post_meta( $directory_id, '_um_sorting_fields', $directory_sorting_fields );

					// If "Default sort users by" = "Other (Custom Field)" is selected when delete this custom field and set default sorting
					$directory_sortby_custom = get_post_meta( $directory_id, '_um_sortby_custom', true );
					if ( $directory_sortby_custom === $id ) {
						$directory_sortby = get_post_meta( $directory_id, '_um_sortby', true );
						if ( 'other' === $directory_sortby ) {
							update_post_meta( $directory_id, '_um_sortby', 'user_registered_desc' );
						}
						update_post_meta( $directory_id, '_um_sortby_custom', '' );
						update_post_meta( $directory_id, '_um_sortby_custom_label', '' );
						update_post_meta( $directory_id, '_um_sortby_custom_type', '' );
						update_post_meta( $directory_id, '_um_sortby_custom_order', '' );
					}
				}
			}
		}

		/**
		 * Quickly adds a field from custom fields.
		 *
		 * @param string $global_id
		 * @param int    $form_id
		 * @param array  $position
		 */
		private function add_field_from_list( $global_id, $form_id, $position = array() ) {
			$fields      = UM()->query()->get_attr( 'custom_fields', $form_id );
			$field_scope = UM()->builtin()->saved_fields;

			if ( ! isset( $fields[ $global_id ] ) ) {
				$count = 1;
				if ( ! empty( $fields ) ) {
					$count = count( $fields ) + 1;
				}

				$fields[ $global_id ]             = $field_scope[ $global_id ];
				$fields[ $global_id ]['position'] = $count;

				// Set position.
				if ( $position ) {
					foreach ( $position as $key => $val ) {
						$fields[ $global_id ][ $key ] = $val;
					}
				}

				// add field to form
				UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			}
		}

		/**
		 * Quickly adds a field from pre-defined fields.
		 *
		 * @param string $global_id
		 * @param int    $form_id
		 * @param array  $position
		 */
		private function add_field_from_predefined( $global_id, $form_id, $position = array() ) {
			$fields      = UM()->query()->get_attr( 'custom_fields', $form_id );
			$field_scope = UM()->builtin()->predefined_fields;

			if ( ! isset( $fields[ $global_id ] ) ) {
				$count = 1;
				if ( ! empty( $fields ) ) {
					$count = count( $fields ) + 1;
				}

				$fields[ $global_id ]             = $field_scope[ $global_id ];
				$fields[ $global_id ]['position'] = $count;

				// Set position.
				if ( $position ) {
					foreach ( $position as $key => $val ) {
						$fields[ $global_id ][ $key ] = $val;
					}
				}

				// add field to form
				UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			}
		}

		/**
		 * Duplicates a field by meta key.
		 *
		 * @param string $id
		 * @param int    $form_id
		 */
		private function duplicate_field( $id, $form_id ) {
			$fields     = UM()->query()->get_attr( 'custom_fields', $form_id );
			$all_fields = UM()->builtin()->saved_fields;

			$inc = count( $fields ) + 1;

			$duplicate = $fields[ $id ];

			$new_metakey  = $id . '_' . $inc;
			$new_title    = $fields[ $id ]['title'] . ' #' . $inc;
			$new_position = $inc;

			$duplicate['title']    = $new_title;
			$duplicate['metakey']  = $new_metakey;
			$duplicate['position'] = $new_position;

			$fields[ $new_metakey ]     = $duplicate;
			$all_fields[ $new_metakey ] = $duplicate;

			// Not global attributes.
			unset( $all_fields[ $new_metakey ]['in_row'], $all_fields[ $new_metakey ]['in_sub_row'], $all_fields[ $new_metakey ]['in_column'], $all_fields[ $new_metakey ]['in_group'], $all_fields[ $new_metakey ]['position'] );

			do_action( 'um_add_new_field', $new_metakey, $duplicate );

			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			update_option( 'um_fields', $all_fields );
		}

		/**
		 * Generate aria errors attributes.
		 *
		 * @param bool   $is_error
		 * @param string $field_id
		 *
		 * @return string
		 */
		public function aria_valid_attributes( $is_error, $field_id, $context = 'error' ) {
			//$context
			$attr = ' aria-invalid="false" ';
			if ( $is_error ) {
				if ( 'notice' === $context ) {
					$errormessage_id = 'um-notice-for-' . $field_id;
				} else {
					$errormessage_id = 'um-error-for-' . $field_id;
				}
				$attr = ' aria-invalid="true" aria-errormessage="' . esc_attr( $errormessage_id ) . '" ';
			}
			return $attr;
		}

		/**
		 * Print field error.
		 *
		 * @since 2.7.0 Added $input_id attribute.
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
				$output = '<div class="um-field-error" id="' . esc_attr( $error_id ) . '"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</div>';
				return $output;
			}

			if ( isset( $this->set_id ) && UM()->form()->processing === $this->set_id ) {
				$output = '<div class="um-field-error" id="' . esc_attr( $error_id ) . '"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</div>';
			} else {
				$output = '';
			}

			if ( ! UM()->form()->processing ) {
				$output = '<div class="um-field-error" id="' . esc_attr( $error_id ) . '"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</div>';
			}

			return $output;
		}

		/**
		 * Print field notice.
		 *
		 * @since 2.7.0 Added $input_id attribute.
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
				$output = '<div class="um-field-notice" id="' . esc_attr( $notice_id ) . '"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</div>';
				return $output;
			}

			if ( isset( $this->set_id ) && UM()->form()->processing === $this->set_id ) {
				$output = '<div class="um-field-notice" id="' . esc_attr( $notice_id ) . '"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</div>';
			} else {
				$output = '';
			}

			if ( ! UM()->form()->processing ) {
				$output = '<div class="um-field-notice" id="' . esc_attr( $notice_id ) . '"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>' . wp_kses( $text, UM()->get_allowed_html( 'templates' ) ) . '</div>';
			}

			return $output;
		}

		/**
		 * Checks if field has a server-side error
		 *
		 * @param string $key
		 *
		 * @return bool
		 */
		public function is_error( $key ) {
			return UM()->form()->has_error( $key );
		}

		/**
		 * Checks if field has a notice
		 *
		 * @param string $key
		 *
		 * @return bool
		 */
		public function is_notice( $key ) {
			return UM()->form()->has_notice( $key );
		}

		/**
		 * Returns field error
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		public function show_error( $key ) {
			if ( empty( UM()->form()->errors ) ) {
				return '';
			}
			return array_key_exists( $key, UM()->form()->errors ) ? UM()->form()->errors[ $key ] : '';
		}

		/**
		 * Returns field notices
		 *
		 * @param string $key
		 *
		 * @return string
		 */
		public function show_notice( $key ) {
			if ( empty( UM()->form()->notices ) ) {
				return '';
			}
			return array_key_exists( $key, UM()->form()->notices ) ? UM()->form()->notices[ $key ] : '';
		}

		/**
		 *  Display field label.
		 *
		 * @param string $label Field label.
		 * @param string $key   Field key.
		 * @param array  $data  Field data.
		 *
		 * @return string
		 */
		public function field_label( $label, $key, $data ) {
			if ( true === $this->viewing ) {
				/**
				 * Filters Ultimate Member field label on the Profile form: View mode.
				 * Note: $key it's field metakey.
				 *
				 * @since 2.0.0
				 * @since 2.8.0 Added $data attribute.
				 *
				 * @hook um_view_label_{$key}
				 *
				 * @param {string} $label Field label.
				 * @param {string} $data  Field data.
				 *
				 * @return {string} Field label.
				 *
				 * @example <caption>Change first name field label on the Profile form: view mode.</caption>
				 * function my_change_first_name_label( $label, $data ) {
				 *     $label = 'My label';
				 *     return $label;
				 * }
				 * add_filter( 'um_view_label_first_name', 'my_change_first_name_label', 10, 2 );
				 */
				$label = apply_filters( "um_view_label_{$key}", $label, $data );
			} else {
				/**
				 * Filters Ultimate Member field label on the Profile form: Edit mode.
				 * Note: $key it's field metakey.
				 *
				 * @since 2.0.0
				 * @since 2.8.0 Added $data attribute.
				 *
				 * @hook um_edit_label_{$key}
				 *
				 * @param {string} $label Field label.
				 * @param {string} $data  Field data.
				 *
				 * @return {string} Field label.
				 *
				 * @example <caption>Change first name field label on the Profile form: edit mode.</caption>
				 * function my_change_first_name_label( $label, $data ) {
				 *     $label = 'My label';
				 *     return $label;
				 * }
				 * add_filter( 'um_edit_label_first_name', 'my_change_first_name_label', 10, 2 );
				 */
				$label = apply_filters( "um_edit_label_{$key}", $label, $data );
				/**
				 * Filters Ultimate Member field label on the Profile form: Edit mode.
				 *
				 * @since 2.0.0
				 *
				 * @hook um_edit_label_all_fields
				 *
				 * @param {string} $label Field label.
				 * @param {string} $data  Field data.
				 *
				 * @return {string} Field label.
				 *
				 * @example <caption>Change first name field label on the Profile form: edit mode.</caption>
				 * function my_change_first_name_label( $label, $data ) {
				 *     if ( 'first_name' === $data['metakey'] ) {
				 *         $label = 'My label';
				 *     }
				 *     return $label;
				 * }
				 * add_filter( 'um_edit_label_all_fields', 'my_change_first_name_label', 10, 2 );
				 */
				$label = apply_filters( 'um_edit_label_all_fields', $label, $data );
			}

			$output  = null;
			$output .= '<div class="um-field-label">';

			if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'off' !== $this->field_icons && ( 'label' === $this->field_icons || true === $this->viewing ) ) {
				$output .= '<div class="um-field-label-icon"><i class="' . esc_attr( $data['icon'] ) . '" aria-label="' . esc_attr( $label ) . '"></i></div>';
			}

			$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();
			$for_attr               = '';
			if ( ! in_array( $data['type'], $fields_without_metakey, true ) ) {
				$for_attr = ' for="' . esc_attr( $key . UM()->form()->form_suffix ) . '"';
			}

			$output .= '<label' . $for_attr . '>' . esc_html__( $label, 'ultimate-member' );

			if ( ! $this->viewing && ! empty( $data['required'] ) && UM()->options()->get( 'form_asterisk' ) ) {
				$output .= '<span class="um-req" title="' . esc_attr__( 'Required', 'ultimate-member' ) . '">*</span>';
			}

			$output .= '</label>';

			if ( ! empty( $data['help'] ) && false === $this->viewing && false === strpos( $key, 'confirm_user_pass' ) ) {
				if ( ! UM()->mobile()->isMobile() ) {
					if ( false === $this->disable_tooltips ) {
						$output .= '<span class="um-tip um-tip-' . ( is_rtl() ? 'e' : 'w' ) . '" title="' . esc_attr__( $data['help'], 'ultimate-member' ) . '"><i class="um-icon-help-circled"></i></span>';
					}
				}

				if ( false !== $this->disable_tooltips || UM()->mobile()->isMobile() ) {
					$output .= '<span class="um-tip-text">' . __( $data['help'], 'ultimate-member' ) . '</span>';
				}
			}

			$output .= '<div class="um-clear"></div></div>';

			return $output;
		}

		/**
		 * Output field classes.
		 *
		 * @param  string $key
		 * @param  array  $data
		 * @param  string $add
		 *
		 * @return string
		 */
		public function get_class( $key, $data, $add = null ) {
			$classes = null;

			$classes .= 'um-form-field ';

			if ( $this->is_error( $key ) ) {
				$classes .= 'um-error ';
			} else {
				$classes .= 'valid ';
			}

			if ( ! isset( $data['required'] ) ) {
				$classes .= 'not-required ';
			}

			if ( $data['type'] == 'date' ) {
				$classes .= 'um-datepicker ';
			}

			if ( $data['type'] == 'time' ) {
				$classes .= 'um-timepicker ';
			}

			if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && $this->field_icons == 'field' ) {
				$classes .= 'um-iconed ';
			}

			if ( $add ) {
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
			// preview in backend
			if ( isset( UM()->user()->preview ) && UM()->user()->preview ) {
				if ( $this->set_mode == 'login' || $this->set_mode == 'register' ) {
					return '';
				} else {
					$val = um_user( $key );
					if ( ! empty( $val ) ) {
						return $val;
					} else {
						return '';
					}
				}
			}

			if ( isset( $_SESSION ) && isset( $_SESSION['um_social_profile'][ $key ] ) && isset( $this->set_mode ) && $this->set_mode == 'register' ) {
				return $_SESSION['um_social_profile'][ $key ];
			}

			$type = ( isset( $data['type'] ) ) ? $data['type'] : '';

			// normal state
			if ( isset( UM()->form()->post_form[ $key ] ) ) {
				// Show empty value for password fields.
				if ( 'password' !== $this->set_mode && false !== strpos( $key, 'user_pass' ) ) {
					return '';
				}

				if ( 'profile' === $this->set_mode ) {
					if ( ! isset( UM()->form()->post_form['profile_nonce'] ) || false === wp_verify_nonce( UM()->form()->post_form['profile_nonce'], 'um-profile-nonce' . UM()->user()->target_id ) ) {
						return '';
					}
				}

				return stripslashes_deep( UM()->form()->post_form[ $key ] );

			} elseif ( true === $this->editing && um_user( $key ) ) {

				// Show empty value for password fields.
				if ( 'password' === $type || false !== strpos( $key, 'user_pass' ) ) {
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

			} elseif ( true === $this->viewing && ( um_user( $key ) || isset( $data['show_anyway'] ) ) ) {

				return um_filtered_value( $key, $data );

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
				$value = maybe_unserialize( $value );

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
				$default = apply_filters( 'um_field_default_value', $default, $data, $type );
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

			}

			// Default Value for Registration Form and Profile Form editing
			if ( ! isset( $value ) && ( $this->set_mode == 'register' || true === $this->editing ) ) {

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
				$value = apply_filters( "um_edit_{$key}_field_value", $default, $key );

			} elseif ( isset( $value ) && is_array( $value ) && ! count( $value ) ) {
				$value = '';
			} elseif ( ! isset( $value ) ) {
				$value = '';
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_field_value
			 * @description Change field value
			 * @input_vars
			 * [{"var":"$value","type":"string","desc":"Field Value"},
			 * {"var":"$key","type":"string","desc":"Field Key"},,
			 * {"var":"$type","type":"string","desc":"Field Type"}
			 * {"var":"$default","type":"string","desc":"Field Default Value"},
			 * {"var":"$data","type":"array","desc":"Field Data"}]
			 * @usage add_filter( 'um_field_value', 'function_name', 10, 5 );
			 */
			return apply_filters( 'um_field_value', $value, $default, $key, $type, $data );
		}

		/**
		 * Checks if an option is selected.
		 *
		 * is used by Select, Multiselect and Checkbox fields
		 *
		 * @param string $key
		 * @param string $value
		 * @param array  $data
		 *
		 * @return boolean
		 */
		public function is_selected( $key, $value, $data ) {
			global $wpdb;

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

			if ( isset( UM()->form()->post_form[ $key ] ) ) {

				if ( is_array( UM()->form()->post_form[ $key ] ) ) {

					if ( in_array( $value, UM()->form()->post_form[ $key ] ) ) {
						return true;
					}

					$stripslashed = array_map( 'stripslashes', UM()->form()->post_form[ $key ] );
					if ( in_array( $value, $stripslashed ) ) {
						return true;
					}

					if ( in_array( html_entity_decode( $value ), UM()->form()->post_form[ $key ] ) ) {
						return true;
					}
				} else {

					if ( $value == UM()->form()->post_form[ $key ] ) {
						return true;
					}

				}

			} else {

				$field_value = um_user( $key );
				if ( ! $field_value ) {
					$field_value = 0;
				}

				if ( $field_value == 0 && $value == '0' ) {
					$value = (int) $value;
				}

				if ( strstr( $key, 'role_' ) || 'role' === $key ) {
					$role_keys = get_option( 'um_roles', array() );
					if ( ! empty( $role_keys ) ) {
						$field_value = UM()->roles()->get_editable_priority_user_role( um_user( 'ID' ) );
						if ( ! empty( $field_value ) ) {
							$field_value = strtolower( $field_value );
							if ( in_array( $field_value, $role_keys, true ) ) {
								$field_value = 'um_' . $field_value;
							}
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
				 * {"var":"$key","type":"string","desc":"Selected filter key"},
				 * {"var":"$value","type":"string","desc":"Selected filter value"}]
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
				$field_value = apply_filters( 'um_is_selected_filter_value', $field_value, $key, $value );

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

				if ( false === $this->editing || 'custom' === $this->set_mode ) {
					// show default on register screen if there is default
					if ( isset( $data['default'] ) ) {

						if ( ! is_array( $data['default'] ) && $data['default'] === $value ) {
							return true;
						}

						if ( is_array( $data['default'] ) && in_array( $value, $data['default'] ) ) {
							return true;
						}

						if ( is_array( $data['default'] ) && array_intersect( $data['options'], $data['default'] ) ) {
							return true;
						}

						// default value with comma
						if ( is_string( $data['default'] ) && strstr( $data['default'], ',' ) ) {
							$choices = array_map( 'trim', explode( ',', $data['default'] ) );
							if ( in_array( $value, $choices ) ) {
								return true;
							}
						}

					}
				} else {

					if ( $field_value && is_array( $field_value ) && ( in_array( $value, $field_value ) || in_array( html_entity_decode( $value ), $field_value ) ) ) {
						return true;
					}

					if ( $field_value == 0 && ! is_array( $field_value ) && $field_value === $value ) {
						return true;
					}

					if ( $field_value && ! is_array( $field_value ) && $field_value == $value ) {
						return true;
					}

					if ( $field_value && ! is_array( $field_value ) && html_entity_decode( $field_value ) == html_entity_decode( $value ) ) {
						return true;
					}

					// show default on edit screen if there isn't meta row in usermeta table
					$direct_db_value = $wpdb->get_var( $wpdb->prepare( "SELECT ISNULL( meta_value ) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s", um_user( 'ID' ), $key ) );
					if ( ! isset( $direct_db_value ) && isset( $data['default'] ) ) {
						if ( ! is_array(  $data['default'] ) && strstr( $data['default'], ', ' ) ) {
							$data['default'] = explode( ', ', $data['default'] );
						}

						if ( ! is_array( $data['default'] ) && $data['default'] === $value ) {
							return true;
						}

						if ( is_array( $data['default'] ) && in_array( $value, $data['default'] ) ) {
							return true;
						}
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
			global $wpdb;

			if ( isset( UM()->form()->post_form[ $key ] ) ) {
				if ( is_array( UM()->form()->post_form[ $key ] ) && in_array( $value, UM()->form()->post_form[ $key ] ) ) {
					return true;
				} elseif ( $value == UM()->form()->post_form[ $key ] ) {
					return true;
				}
			} else {

				if ( true === $this->editing && 'custom' !== $this->set_mode ) {
					if ( um_user( $key ) ) {

						$um_user_value = um_user( $key );

						if ( strstr( $key, 'role_' ) || $key == 'role' ) {
							$um_user_value = strtolower( UM()->roles()->get_editable_priority_user_role( um_user( 'ID' ) ) );

							$role_keys = get_option( 'um_roles', array() );

							if ( ! empty( $role_keys ) ) {
								if ( in_array( $um_user_value, $role_keys ) ) {
									$um_user_value = 'um_' . $um_user_value;
								}
							}
						}

						if ( $um_user_value == $value ) {
							return true;
						}

						if ( is_array( $um_user_value ) && in_array( $value, $um_user_value ) ) {
							return true;
						}

						if ( is_array( $um_user_value ) ) {
							foreach ( $um_user_value as $u ) {
								if ( $u == html_entity_decode( $value ) ) {
									return true;
								}
							}
						}
					} else {

						// show default on edit screen if there isn't meta row in usermeta table
						$direct_db_value = $wpdb->get_var( $wpdb->prepare( "SELECT ISNULL( meta_value ) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s", um_user( 'ID' ), $key ) );
						if ( ! isset( $direct_db_value ) && isset( $data['default'] ) && $data['default'] == $value ) {
							return true;
						}

					}
				} else {
					if ( isset( $data['default'] ) && $data['default'] == $value ) {
						return true;
					}
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
			if ( isset( $fields[ $key ]['icon'] ) ) {
				return $fields[ $key ]['icon'];
			}

			return '';
		}

		/**
		 * Getting the blacklist of the functions that cannot be used as callback.
		 * All internal PHP functions are insecure for using inside callback functions.
		 *
		 * @return array
		 */
		public function dropdown_options_source_blacklist() {
			$list      = get_defined_functions();
			$blacklist = ! empty( $list['internal'] ) ? $list['internal'] : array();
			$blacklist = apply_filters( 'um_dropdown_options_source_blacklist', $blacklist );
			return $blacklist;
		}

		/**
		 * Is the dropdown source callback function blacklisted?
		 *
		 * @param string $source Function name
		 *
		 * @return bool
		 */
		public function is_source_blacklisted( $source ) {
			// avoid using different letter case for bypass the blacklist e.g. phpInfo
			// avoid using root namespace for bypass the blacklist e.g. \phpinfo
			$source = trim( strtolower( $source ), '\\' );

			if ( in_array( $source, $this->dropdown_options_source_blacklist(), true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Gets selected option value from a callback function
		 *
		 * @param  string $value
		 * @param  array  $data
		 * @param  string $type
		 *
		 * @return string
		 */
		function get_option_value_from_callback( $value, $data, $type ) {

			if ( in_array( $type, array( 'select', 'multiselect' ) ) && ! empty( $data['custom_dropdown_options_source'] ) ) {

				if ( $this->is_source_blacklisted( $data['custom_dropdown_options_source'] ) ) {
					return $value;
				}

				$has_custom_source = apply_filters( "um_has_dropdown_options_source__{$data['metakey']}", false );

				if ( $has_custom_source ) {

					/** This filter is documented in includes/core/class-fields.php */
					$opts        = apply_filters( "um_get_field__{$data['metakey']}", array() );
					$arr_options = array_key_exists( 'options', $opts ) ? $opts['options'] : array();

				} elseif ( function_exists( $data['custom_dropdown_options_source'] ) ) {
					if ( isset( $data['parent_dropdown_relationship'] ) ) {
						$_POST['parent_option_name'] = $data['parent_dropdown_relationship'];
						$_POST['parent_option'] = um_user( $data['parent_dropdown_relationship'] );

						$arr_options = call_user_func( $data['custom_dropdown_options_source'], $data['parent_dropdown_relationship'] );
					} else {
						$arr_options = call_user_func( $data['custom_dropdown_options_source'] );
					}
				}

				if ( $has_custom_source || function_exists( $data['custom_dropdown_options_source'] ) ) {
					if ( $type == 'select' ) {
						if ( ! empty( $arr_options[ $value ] ) ) {
							return $arr_options[ $value ];
						} elseif ( ! empty( $data['default'] ) && empty( $arr_options[ $value ] ) ) {
							return $arr_options[ $data['default'] ];
						} else {
							return '';
						}
					} elseif ( $type == 'multiselect' ) {

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
		public function get_options_from_callback( $data, $type ) {
			$arr_options = array();

			if ( ! empty( $data['custom_dropdown_options_source'] ) && in_array( $type, array( 'select', 'multiselect' ), true ) ) {
				if ( $this->is_source_blacklisted( $data['custom_dropdown_options_source'] ) ) {
					return $arr_options;
				}

				if ( function_exists( $data['custom_dropdown_options_source'] ) ) {
					if ( isset( $data['parent_dropdown_relationship'] ) ) {
						$arr_options = call_user_func( $data['custom_dropdown_options_source'], $data['parent_dropdown_relationship'] );
					} else {
						$arr_options = call_user_func( $data['custom_dropdown_options_source'] );
					}
				}
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
			if ( isset( $fields[ $key ]['type'] ) ) {
				return $fields[ $key ]['type'];
			}

			return '';
		}

		/**
		 * Get field label
		 *
		 * @param string $key Field meta key
		 *
		 * @return string
		 */
		public function get_label( $key ) {
			$label      = '';
			$fields     = UM()->builtin()->all_user_fields;
			$field_data = array_key_exists( $key, $fields ) ? $fields[ $key ] : array();

			if ( array_key_exists( 'label', $field_data ) ) {
				$label = stripslashes( $field_data['label'] );
			}

			if ( empty( $label ) && array_key_exists( 'title', $field_data ) ) {
				$label = stripslashes( $field_data['title'] );
			}

			/**
			 * Filters Ultimate Member field label.
			 *
			 * @since 2.0.30
			 * @since 2.8.0 Added $data attribute.
			 *
			 * @hook um_change_field_label
			 *
			 * @param {string} $label Field label.
			 * @param {string} $key   Field key.
			 * @param {array}  $data  Field data.
			 *
			 * @return {string} Field label.
			 *
			 * @example <caption>Change first name field label.</caption>
			 * function my_change_field_label( $label, $key, $data ) {
			 *     if ( 'first_name' === $key ) {
			 *         $label = 'My label';
			 *     }
			 *     return $label;
			 * }
			 * add_filter( 'um_change_field_label', 'my_change_field_label', 10, 3 );
			 */
			$label = apply_filters( 'um_change_field_label', $label, $key, $field_data );

			return sprintf( __( '%s', 'ultimate-member' ), $label );
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
			if ( isset( $fields[ $key ]['title'] ) ) {
				return $fields[ $key ]['title'];
			}
			if ( isset( $fields[ $key ]['label'] ) ) {
				return $fields[ $key ]['label'];
			}

			return __( 'Custom Field', 'ultimate-member' );
		}

		/**
		 * Get form fields
		 *
		 * @var null|int $form_id
		 *
		 * @return array
		 */
		public function get_fields() {
			if ( empty( $this->set_id ) ) {
				return array();
			}

			if ( empty( $this->fields[ $this->set_id ] ) ) {
				/**
				 * Filters the form fields.
				 *
				 * @param {array} $fields  Form fields.
				 * @param {int}   $form_id Form ID. Since 2.6.11
				 *
				 * @return {array} Form fields.
				 *
				 * @since 1.3.x
				 * @since 2.6.11 Added Form ID attribute.
				 * @hook um_get_form_fields
				 *
				 * @example <caption>Extend form fields.</caption>
				 * function my_form_fields( $fields, $form_id ) {
				 *     // your code here
				 *     return $fields;
				 * }
				 * add_filter( 'um_get_form_fields', 'my_form_fields', 10, 2 );
				 */
				$this->fields[ $this->set_id ] = apply_filters( 'um_get_form_fields', array(), $this->set_id );
			}

			return $this->fields[ $this->set_id ];
		}

		/**
		 * Get specific field
		 *
		 * @param $key
		 *
		 * @return mixed
		 * @throws \Exception
		 */
		public function get_field( $key ) {
			$fields = $this->get_fields();

			if ( isset( $fields ) && is_array( $fields ) && isset( $fields[ $key ] ) ) {
				$array = $fields[ $key ];
			} else {
				if ( ! isset( UM()->builtin()->predefined_fields[ $key ] ) && ! isset( UM()->builtin()->all_user_fields[ $key ] ) ) {
					return '';
				}
				$array = ( isset( UM()->builtin()->predefined_fields[ $key ] ) ) ? UM()->builtin()->predefined_fields[ $key ] : UM()->builtin()->all_user_fields[ $key ];
			}

			if ( empty( $array['type'] ) ) {
				return '';
			}

			$array['classes'] = null;

			if ( ! isset( $array['placeholder'] ) ) {
				$array['placeholder'] = null;
			}
			if ( ! isset( $array['required'] ) ) {
				$array['required'] = null;
			}
			if ( ! isset( $array['validate'] ) ) {
				$array['validate'] = null;
			}
			if ( ! isset( $array['default'] ) ) {
				$array['default'] = null;
			}

			if ( isset( $array['conditions'] ) && is_array( $array['conditions'] ) && false === $this->viewing ) {
				$array['conditional'] = '';

				foreach ( $array['conditions'] as $cond_id => $cond ) {
					$array['conditional'] .= ' data-cond-' . $cond_id . '-action="' . esc_attr( $cond[0] ) . '" data-cond-' . $cond_id . '-field="' . esc_attr( $cond[1] ) . '" data-cond-' . $cond_id . '-operator="' . esc_attr( $cond[2] ) . '" data-cond-' . $cond_id . '-value="' . esc_attr( $cond[3] ) . '"';
				}

				$array['classes'] .= ' um-is-conditional';

			} else {
				$array['conditional'] = null;
			}

			$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();

			if ( ! in_array( $array['type'], $fields_without_metakey, true ) ) {
				$array['classes'] .= ' um-field-' . esc_attr( $key );
			}
			$array['classes'] .= ' um-field-' . esc_attr( $array['type'] );
			$array['classes'] .= ' um-field-type_' . esc_attr( $array['type'] );

			switch ( $array['type'] ) {

				case 'googlemap':
				case 'youtube_video':
				case 'vimeo_video':
				case 'soundcloud_track':
				case 'spotify':
					$array['disabled'] = '';
					$array['input'] = 'text';
					break;

				case 'text':

					$array['disabled'] = '';
					if ( 'user_login' === $key && 'account' === $this->set_mode ) {
						$array['disabled'] = ' disabled="disabled" ';
					}

					$array['input'] = 'text';

					break;

				case 'tel':

					$array['input'] = 'tel';

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

				case 'oembed':

					$array['input'] = 'url';

					break;

				case 'date':

					$array['input'] = 'text';

					if ( ! isset( $array['format'] ) ) {
						$array['format'] = 'j M Y';
					}

					switch ( $array['format'] ) {
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

					if ( ! isset( $array['range'] ) ) {
						$array['range'] = 'years';
					}
					if ( ! isset( $array['years'] ) ) {
						$array['years'] = 100;
					}
					if ( ! isset( $array['years_x'] ) ) {
						$array['years_x'] = 'past';
					}
					if ( ! isset( $array['disabled_weekdays'] ) ) {
						$array['disabled_weekdays'] = '';
					}

					if ( ! empty( $array['disabled_weekdays'] ) ) {
						$array['disabled_weekdays'] = '[' . implode( ',', $array['disabled_weekdays'] ) . ']';
					}

					// When date range is strictly defined
					if ( $array['range'] == 'date_range' ) {

						$array['date_min'] = str_replace( '/', ',', $array['range_start'] );
						$array['date_max'] = str_replace( '/', ',', $array['range_end'] );

					} else {

						if ( $array['years_x'] == 'past' ) {

							$date = new \DateTime( date( 'Y-n-d' ) );
							$past = $date->modify( '-' . $array['years'] . ' years' );
							$past = $date->format( 'Y,n,d' );

							$array['date_min'] = $past;
							$array['date_max'] = date( 'Y,n,d' );

						} elseif ( $array['years_x'] == 'future' ) {

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

					if ( ! isset( $array['format'] ) ) {
						$array['format'] = 'g:i a';
					}

					switch ( $array['format'] ) {
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

					if ( ! isset( $array['intervals'] ) ) {
						$array['intervals'] = 60;
					}

					break;

				case 'textarea':

					if ( ! isset( $array['height'] ) ) {
						$array['height'] = '100px';
					}

					break;

				case 'rating':

					if ( ! isset( $array['number'] ) ) {
						$array['number'] = 5;
					}

					break;

				case 'spacing':

					if ( ! isset( $array['spacing'] ) ) {
						$array['spacing'] = '20px';
					}

					break;

				case 'divider':

					if ( isset( $array['width'] ) ) {
						$array['borderwidth'] = $array['width'];
					} else {
						$array['borderwidth'] = 4;
					}

					if ( isset( $array['color'] ) ) {
						$array['bordercolor'] = $array['color'];
					} else {
						$array['bordercolor'] = '#eee';
					}

					if ( isset( $array['style'] ) ) {
						$array['borderstyle'] = $array['style'];
					} else {
						$array['borderstyle'] = 'solid';
					}

					if ( ! isset( $array['divider_text'] ) ) {
						$array['divider_text'] = '';
					}

					break;

				case 'image':

					if ( ! isset( $array['crop'] ) ) {
						$array['crop'] = 0;
					}

					if ( $array['crop'] == 0 ) {
						$array['crop_data'] = 0;
					} elseif ( $array['crop'] == 1 ) {
						$array['crop_data'] = 'square';
					} elseif ( $array['crop'] == 2 ) {
						$array['crop_data'] = 'cover';
					} else {
						$array['crop_data'] = 'user';
					}

					if ( ! isset( $array['modal_size'] ) ) {
						$array['modal_size'] = 'normal';
					}

					if ( $array['crop'] > 0 ) {
						$array['crop_class'] = 'crop';
					} else {
						$array['crop_class'] = '';
					}

					if ( ! isset( $array['ratio'] ) ) {
						$array['ratio'] = 1.0;
					}

					if ( ! isset( $array['min_width'] ) ) {
						$array['min_width'] = '';
					}
					if ( ! isset( $array['min_height'] ) ) {
						$array['min_height'] = '';
					}

					if ( $array['min_width'] == '' && $array['crop'] == 1 ) {
						$array['min_width'] = 600;
					}
					if ( $array['min_height'] == '' && $array['crop'] == 1 ) {
						$array['min_height'] = 600;
					}

					if ( $array['min_width'] == '' && $array['crop'] == 3 ) {
						$array['min_width'] = 600;
					}
					if ( $array['min_height'] == '' && $array['crop'] == 3 ) {
						$array['min_height'] = 600;
					}

					if ( ! isset( $array['invalid_image'] ) ) {
						$array['invalid_image'] = __( 'Please upload a valid image!', 'ultimate-member' );
					}
					if ( ! isset( $array['allowed_types'] ) ) {
						$array['allowed_types'] = 'gif,jpg,jpeg,png';
					} else {
						$array['allowed_types'] = implode( ',', $array['allowed_types'] );
					}
					if ( ! isset( $array['upload_text'] ) ) {
						$array['upload_text'] = '';
					}
					if ( ! isset( $array['button_text'] ) ) {
						$array['button_text'] = __( 'Upload', 'ultimate-member' );
					}
					if ( ! isset( $array['extension_error'] ) ) {
						$array['extension_error'] = __( 'Sorry this is not a valid image.', 'ultimate-member' );
					}
					if ( ! isset( $array['max_size_error'] ) ) {
						$array['max_size_error'] = __( 'This image is too large!', 'ultimate-member' );
					}
					if ( ! isset( $array['min_size_error'] ) ) {
						$array['min_size_error'] = __( 'This image is too small!', 'ultimate-member' );
					}
					if ( ! isset( $array['max_files_error'] ) ) {
						$array['max_files_error'] = __( 'You can only upload one image', 'ultimate-member' );
					}
					if ( empty( $array['max_size'] ) ) {
						$array['max_size'] = 999999999;
					}
					if ( ! isset( $array['upload_help_text'] ) ) {
						$array['upload_help_text'] = '';
					}
					if ( ! isset( $array['icon'] ) ) {
						$array['icon'] = '';
					}

					break;

				case 'file':

					if ( ! isset( $array['modal_size'] ) ) {
						$array['modal_size'] = 'normal';
					}

					if ( ! isset( $array['allowed_types'] ) ) {
						$array['allowed_types'] = 'pdf,txt';
					} else {
						$array['allowed_types'] = implode( ',', $array['allowed_types'] );
					}
					if ( ! isset( $array['upload_text'] ) ) {
						$array['upload_text'] = '';
					}
					if ( ! isset( $array['button_text'] ) ) {
						$array['button_text'] = __( 'Upload', 'ultimate-member' );
					}
					if ( ! isset( $array['extension_error'] ) ) {
						$array['extension_error'] = __( 'Sorry this is not a valid file.', 'ultimate-member' );
					}
					if ( ! isset( $array['max_size_error'] ) ) {
						$array['max_size_error'] = __( 'This file is too large!', 'ultimate-member' );
					}
					if ( ! isset( $array['min_size_error'] ) ) {
						$array['min_size_error'] = __( 'This file is too small!', 'ultimate-member' );
					}
					if ( ! isset( $array['max_files_error'] ) ) {
						$array['max_files_error'] = __( 'You can only upload one file', 'ultimate-member' );
					}
					if ( empty( $array['max_size'] ) ) {
						$array['max_size'] = 999999999;
					}
					if ( ! isset( $array['upload_help_text'] ) ) {
						$array['upload_help_text'] = '';
					}
					if ( ! isset( $array['icon'] ) ) {
						$array['icon'] = '';
					}

					break;

				case 'select':

					break;

				case 'multiselect':

					break;

				case 'group':

					if ( ! isset( $array['max_entries'] ) ) {
						$array['max_entries'] = 0;
					}

					break;

			}

			if ( ! isset( $array['visibility'] ) ) {
				$array['visibility'] = 'all';
			}

			/**
			 * Filters the field data by the field type. Where $type is the field's type.
			 *
			 * @param {array} $field_data Field data.
			 *
			 * @return {array} Field data.
			 *
			 * @since 2.0   First hook version applied only for the date type.
			 * @since 2.6.8 Added support for all field type.
			 *
			 * @hook um_get_field_{$type}
			 *
			 * @example <caption>Disable all date-type fields.</caption>
			 * function my_custom_get_field_date( $field_data ) {
			 *     $field_data['disabled'] = ' disabled="disabled" ';
			 *     return $field_data;
			 * }
			 * add_filter( 'um_get_field_date', 'my_custom_get_field_date' );
			 */
			$array = apply_filters( "um_get_field_{$array['type']}", $array );
			/**
			 * Filters the field data by the metakey. Where $key is the field's metakey.
			 *
			 * @param {array} $field_data Field data.
			 *
			 * @return {array} Field data.
			 *
			 * @since 1.3.x
			 * @hook um_get_field__{$key}
			 *
			 * @example <caption>Disable 'first_name' field.</caption>
			 * function my_custom_disable_first_name( $field_data ) {
			 *     $field_data['disabled'] = ' disabled="disabled" ';
			 *     return $field_data;
			 * }
			 * add_filter( 'um_get_field__first_name', 'my_custom_disable_first_name' );
			 */
			return apply_filters( "um_get_field__{$key}", $array );
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
		 * Getting the fields that need to be disabled in edit mode (profile)
		 *
		 * @param bool|int $_um_profile_id
		 *
		 * @return array
		 */
		public function get_restricted_fields_for_edit( $_um_profile_id = false ) {
			static $cache = array();

			$cache_key = absint( $_um_profile_id );
			if ( array_key_exists( $cache_key, $cache ) ) {
				return $cache[ $cache_key ];
			}

			// fields that need to be disabled in edit mode (profile)
			$arr_restricted_fields = array( 'user_email', 'username', 'user_login', 'user_password', '_um_last_login', 'user_registered' );
			/**
			 * Filters the form fields that need to be disabled in edit mode (profile).
			 *
			 * @param {array} $fields         Form fields.
			 * @param {int}   $_um_profile_id User Profile ID.
			 *
			 * @return {array} Form fields.
			 *
			 * @since 2.0
			 * @hook um_user_profile_restricted_edit_fields
			 *
			 * @example <caption>Make user email field editable on the Profile Page.</caption>
			 * function my_make_email_editable( $fields, $_um_profile_id ) {
			 *     $fields = array_flip( $fields );
			 *     unset( $fields['user_email'] );
			 *     $fields = array_keys( $fields );
			 *     return $fields;
			 * }
			 * add_filter( 'um_user_profile_restricted_edit_fields', 'my_make_email_editable', 10, 2 );
			 */
			$cache[ $cache_key ] = apply_filters( 'um_user_profile_restricted_edit_fields', $arr_restricted_fields, $_um_profile_id );

			return $cache[ $cache_key ];
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
		 * @throws \Exception
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
				if ( UM()->options()->get( $opt[0] ) !== $opt[1] ) {
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
				/* Other fields */
				case 'googlemap':
				case 'youtube_video':
				case 'vimeo_video':
				case 'spotify':
				case 'soundcloud_track':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					$output .= '<input ' . $disabled . ' class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

						</div>';

					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Text and Tel */
				case 'text':
				case 'tel':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					$output .= '<input ' . $disabled . ' autocomplete="' . esc_attr( $autocomplete ) . '" class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

						</div>';

					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Number */
				case 'number':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					$number_limit = '';
					if ( isset( $data['min'] ) ) {
						$number_limit .= ' min="' . esc_attr( $data['min'] ) . '" ';
					}
					if ( isset( $data['max'] ) ) {
						$number_limit .= ' max="' . esc_attr( $data['max'] ) . '" ';
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					$output .= '<input ' . $disabled . ' class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="number" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $number_limit . ' ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

						</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Password */
				case 'password':
					$original_key = $key;

					if ( 'single_user_password' === $key ) {
						$key = $original_key;

						$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

						if ( isset( $data['label'] ) ) {
							$output .= $this->field_label( $data['label'], $key, $data );
						}

						$output .= '<div class="um-field-area">';

						if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
							$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
						}

						$field_name  = $key . $form_suffix;
						$field_value = $this->field_value( $key, $default, $data );

						if ( UM()->options()->get( 'toggle_password' ) ) {
							$output .= '<div class="um-field-area-password">
									<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>
									<span class="um-toggle-password"><i class="um-icon-eye"></i></span>
								</div>
							</div>';
						} else {
							$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

							</div>';
						}

						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $field_name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
						}

						$output .= '</div>';
					} else {
						if ( ( 'account' === $this->set_mode || um_is_core_page( 'account' ) ) && UM()->account()->current_password_is_required( 'password' ) ) {

							$key     = 'current_' . $original_key;
							$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

							if ( isset( $data['label'] ) ) {
								$output .= $this->field_label( __( 'Current Password', 'ultimate-member' ), $key, $data );
							}

							$output .= '<div class="um-field-area">';

							if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
								$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
							}

							$field_name  = $key . $form_suffix;
							$field_value = $this->field_value( $key, $default, $data );

							if ( UM()->options()->get( 'toggle_password' ) ) {
								$output .= '<div class="um-field-area-password">
										<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>
										<span class="um-toggle-password"><i class="um-icon-eye"></i></span>
									</div>
								</div>';
							} else {
								$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

								</div>';
							}

							if ( $this->is_error( $key ) ) {
								$output .= $this->field_error( $this->show_error( $key ), $field_name );
							} elseif ( $this->is_notice( $key ) ) {
								$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
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

						$output .= '<div class="um-field-area">';

						if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
							$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
						}

						$name = $key . $form_suffix;
						if ( 'password' === $this->set_mode && um_is_core_page( 'password-reset' ) ) {
							$name = $key;
						}

						$field_value = $this->field_value( $key, $default, $data );
						if ( UM()->options()->get( 'toggle_password' ) ) {
							$output .= '<div class="um-field-area-password">
									<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/>
									<span class="um-toggle-password"><i class="um-icon-eye"></i></span>
								</div>
							</div>';
						} else {
							$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/>

							</div>';
						}

						if ( $this->is_error( $key ) ) {
							$output .= $this->field_error( $this->show_error( $key ), $name );
						} elseif ( $this->is_notice( $key ) ) {
							$output .= $this->field_notice( $this->show_notice( $key ), $name );
						}

						$output .= '</div>';

						if ( 'login' !== $this->set_mode && ! empty( $data['force_confirm_pass'] ) ) {

							$key     = 'confirm_' . $original_key;
							$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

							if ( ! empty( $data['label_confirm_pass'] ) ) {
								$label_confirm_pass = __( $data['label_confirm_pass'], 'ultimate-member' );
								$output            .= $this->field_label( $label_confirm_pass, $key, $data );
							} elseif ( isset( $data['label'] ) ) {
								$data['label'] = __( $data['label'], 'ultimate-member' );
								// translators: %s: label.
								$output .= $this->field_label( sprintf( __( 'Confirm %s', 'ultimate-member' ), $data['label'] ), $key, $data );
							}

							$output .= '<div class="um-field-area">';

							if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
								$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
							}

							$name = $key . $form_suffix;
							if ( 'password' === $this->set_mode && um_is_core_page( 'password-reset' ) ) {
								$name = $key;
							}

							if ( ! empty( $data['label_confirm_pass'] ) ) {
								$placeholder = __( $data['label_confirm_pass'], 'ultimate-member' );
							} elseif ( ! empty( $placeholder ) && ! isset( $data['label'] ) ) {
								// translators: %s: placeholder.
								$placeholder = sprintf( __( 'Confirm %s', 'ultimate-member' ), $placeholder );
							} elseif ( isset( $data['label'] ) ) {
								// translators: %s: label.
								$placeholder = sprintf( __( 'Confirm %s', 'ultimate-member' ), $data['label'] );
							}

							if ( UM()->options()->get( 'toggle_password' ) ) {
								$output .= '<div class="um-field-area-password"><input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $this->field_value( $key, $default, $data ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/><span class="um-toggle-password"><i class="um-icon-eye"></i></span></div>';
							} else {
								$output .= '<input class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $key . $form_suffix ) . '" value="' . esc_attr( $this->field_value( $key, $default, $data ) ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $name ) . '/>';
							}

							$output .= '</div>';

							if ( $this->is_error( $key ) ) {
								$output .= $this->field_error( $this->show_error( $key ), $name );
							} elseif ( $this->is_notice( $key ) ) {
								$output .= $this->field_notice( $this->show_notice( $key ), $name );
							}

							$output .= '</div>';
						}
					}
					break;
				/* URL */
				case 'oembed':
				case 'url':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					$output .= '<input  ' . $disabled . '  class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

						</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Date */
				case 'date':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					// Normalise date format.
					$value = $this->field_value( $key, $default, $data );
					if ( $value ) {
						// numeric (either unix or YYYYMMDD). ACF uses Ymd format of date inside the meta tables.
						if ( is_numeric( $value ) && strlen( $value ) !== 8 ) {
							$unixtimestamp = $value;
						} else {
							$unixtimestamp = strtotime( $value );
						}
						// Ultimate Member date field stores the date in metatable in the format Y/m/d. Convert to it before echo.
						$value = date( 'Y/m/d', $unixtimestamp );
					}

					$field_name = $key . $form_suffix;

					$disabled_weekdays = '';
					if ( isset( $data['disabled_weekdays'] ) && is_array( $data['disabled_weekdays'] ) ) {
						$disabled_weekdays = '[' . implode( ',', $data['disabled_weekdays'] ) . ']';
					}

					$output .= '<input ' . $disabled . '  class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" data-range="' . esc_attr( $data['range'] ) . '" data-years="' . esc_attr( $data['years'] ) . '" data-years_x="' . esc_attr( $data['years_x'] ) . '" data-disabled_weekdays="' . esc_attr( $disabled_weekdays ) . '" data-date_min="' . esc_attr( $data['date_min'] ) . '" data-date_max="' . esc_attr( $data['date_max'] ) . '" data-format="' . esc_attr( $data['js_format'] ) . '" data-value="' . esc_attr( $value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

						</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Time */
				case 'time':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-area">';

					if ( ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					$field_name  = $key . $form_suffix;
					$field_value = $this->field_value( $key, $default, $data );

					$output .= '<input  ' . $disabled . '  class="' . esc_attr( $this->get_class( $key, $data ) ) . '" type="' . esc_attr( $input ) . '" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" placeholder="' . esc_attr( $placeholder ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '"  data-format="' . esc_attr( $data['js_format'] ) . '" data-intervals="' . esc_attr( $data['intervals'] ) . '" data-value="' . esc_attr( $field_value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>

						</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Row */
				case 'row':
					$output .= '';
					break;
				/* Textarea */
				case 'textarea':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$field_id    = $key;
					$field_name  = $key;
					$field_value = $this->field_value( $key, $default, $data );

					$bio_key = UM()->profile()->get_show_bio_key( $this->global_args );

					$output .= '<div class="um-field-area">';

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

						// turn on the output buffer
						ob_start();

						// echo the editor to the buffer
						wp_editor( $field_value, $key, $textarea_settings );

						// Add the contents of the buffer to the output variable.
						$output .= ob_get_clean();
						$output .= '<br /><span class="description">' . esc_html( $placeholder ) . '</span>';
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

					$output .= '</div>';

					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $field_name, $field_value );
					}

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Rating */
				case 'rating':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$output .= '<div class="um-field-area">';

					$output .= '<div class="um-rating um-raty" id="' . esc_attr( $key ) . '" data-key="' . esc_attr( $key ) . '" data-number="' . esc_attr( $data['number'] ) . '" data-score="' . $this->field_value( $key, $default, $data ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $key ) . '></div>';
					$output .= '</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $key );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $key );
					}

					$output .= '</div>';

					break;
				/* Gap/Space */
				case 'spacing':
					$field_style = array();
					if ( array_key_exists( 'spacing', $data ) ) {
						$field_style = array( 'height' => $data['spacing'] );
					}
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data, $field_style ) . '></div>';
					break;
				/* A line divider */
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
					if ( in_array( $key, array( 'profile_photo', 'cover_photo' ), true ) ) {
						$field_value = '';
					} else {
						$field_value = $this->field_value( $key, $default, $data );
					}

					$field_name = $key . $form_suffix;

					$output .= '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}
					$modal_label = array_key_exists( 'label', $data ) ? $data['label'] : __( 'Upload Photo', 'ultimate-member' );
					$output     .= '<div class="um-field-area" style="text-align: center;">';

					if ( ! empty( $field_value ) && 'empty_file' !== $field_value ) {
						if ( ! in_array( $key, array( 'profile_photo', 'cover_photo' ), true ) ) {
//							if ( isset( $this->set_mode ) && 'register' === $this->set_mode ) {
//								$image_info = get_transient( "um_{$field_value}" );
//							} else {
//								$image_info = um_user( $data['metakey'] . '_metadata' );
//							}

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
					$output .= '</div>';

					break;
				/* Single File Upload */
				case 'file':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . ' data-mode="' . esc_attr( $this->set_mode ) . '" data-upload-label="' . ( ! empty( $data['button_text'] ) ? esc_attr( $data['button_text'] ) : esc_attr__( 'Upload', 'ultimate-member' ) ) . '">';

					$field_name       = $key . $form_suffix;
					$file_field_value = $this->field_value( $key, $default, $data );

					$output .= '<input type="hidden" name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $field_name ) . '" value="' . esc_attr( $file_field_value ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '/>';
					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}
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
					$output .= '</div>';

					break;
				/* Select dropdown */
				case 'select':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$form_key = str_replace( array( 'role_select', 'role_radio' ), 'role', $key );
					$field_id = $form_key;

					$class = 'um-s1';
					if ( isset( $data['allowclear'] ) && 0 === $data['allowclear'] ) {
						$class = 'um-s2';
					}

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$has_icon = ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons;

					$output .= '<div class="um-field-area ' . ( $has_icon ? 'um-field-area-has-icon' : '' ) . ' ">';
					if ( $has_icon ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					$options                      = array();
					$has_parent_option            = false;
					$disabled_by_parent_option    = '';
					$atts_ajax                    = '';
					$select_original_option_value = '';

					if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
						$options = $data['options'];
					}

					if ( ! empty( $data['parent_dropdown_relationship'] ) && ! UM()->user()->preview ) {
						$has_parent_option         = true;
						$disabled_by_parent_option = ' disabled="disabled" ';

						/**
						 * Filters parent dropdown relationship by $form_key.
						 *
						 * @since 1.3.x
						 * @hook  um_custom_dropdown_options_parent__{$form_key}
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
						 * add_filter( 'um_custom_dropdown_options_parent__{$form_key}', 'function_name', 10, 2 );
						 */
						$parent_dropdown_relationship = apply_filters( "um_custom_dropdown_options_parent__{$form_key}", $data['parent_dropdown_relationship'], $data );
						$atts_ajax                   .= ' data-um-parent="' . esc_attr( $parent_dropdown_relationship ) . '" ';

						if ( ! empty( $data['custom_dropdown_options_source'] ) && function_exists( $data['custom_dropdown_options_source'] ) && um_user( $data['parent_dropdown_relationship'] ) ) {
							if ( ! $this->is_source_blacklisted( $data['custom_dropdown_options_source'] ) ) {
								$options = call_user_func( $data['custom_dropdown_options_source'], $data['parent_dropdown_relationship'] );
							}

							$disabled_by_parent_option = '';
							if ( um_user( $form_key ) ) {
								$select_original_option_value = ' data-um-original-value="' . esc_attr( um_user( $form_key ) ) . '" ';
							}
						}
					}

					// Child dropdown option selected
					if ( isset( UM()->form()->post_form[ $form_key ] ) ) {
						$select_original_option_value = " data-um-original-value='" . esc_attr( UM()->form()->post_form[ $form_key ] ) . "' ";
					}

					// Child dropdown
					if ( $has_parent_option ) {
						if ( ! empty( $data['custom_dropdown_options_source'] ) && function_exists( $data['custom_dropdown_options_source'] ) && isset( UM()->form()->post_form[ $form_key ] ) ) {
							if ( ! $this->is_source_blacklisted( $data['custom_dropdown_options_source'] ) ) {
								$options = call_user_func( $data['custom_dropdown_options_source'], $data['parent_dropdown_relationship'] );
							}
						}
					}

					if ( ! empty( $data['custom_dropdown_options_source'] ) ) {
						/**
						 * Filters a custom dropdown options source by $form_key.
						 *
						 * @since 1.3.x
						 * @hook  um_custom_dropdown_options_source__{$form_key}
						 *
						 * @param {string} $source Dropdown options source.
						 * @param {array}  $data   Field Data.
						 *
						 * @return {string} Dropdown options source.
						 *
						 * @example <caption>Change custom dropdown options source.</caption>
						 * function function_name( $source, $data ) {
						 *     // your code here
						 *     return $source;
						 * }
						 * add_filter( 'um_custom_dropdown_options_source__{$form_key}', 'function_name', 10, 2 );
						 */
						$ajax_source = apply_filters( "um_custom_dropdown_options_source__{$form_key}", $data['custom_dropdown_options_source'], $data );
						$atts_ajax  .= ' data-um-ajax-source="' . esc_attr( $ajax_source ) . '" ';
					}

					if ( ! $has_parent_option ) {
						if ( isset( $options ) && 'builtin' === $options ) {
							$options = UM()->builtin()->get( $data['filter'] );
						}

						// 'country'
						if ( 'country' === $key && empty( $options ) ) {
							$options = UM()->builtin()->get( 'countries' );
						} elseif ( empty( $options ) && isset( $data['options'] ) ) {
							$options = $data['options'];
						}

						/**
						 * Filters dropdown options.
						 *
						 * @since 2.0
						 * @hook  um_selectbox_options
						 *
						 * @param {array}  $options Field options.
						 * @param {string} $key     Field metakey.
						 *
						 * @return {array} Field options.
						 *
						 * @example <caption>Extend dropdown options.</caption>
						 * function my_um_selectbox_options( $options, $key ) {
						 *     // your code here
						 *     return $options;
						 * }
						 * add_filter( 'um_selectbox_options', 'my_um_selectbox_options', 10, 2 );
						 */
						$options = apply_filters( 'um_selectbox_options', $options, $key );
						if ( isset( $options ) ) {
							/**
							 * Filters dropdown dynamic options.
							 *
							 * @since 1.3.x
							 * @hook  um_select_dropdown_dynamic_options
							 *
							 * @param {array} $options Dynamic options.
							 * @param {array} $data    Field Data.
							 *
							 * @return {array} Dynamic options.
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
							 * @since 1.3.x
							 * @hook  um_select_dropdown_dynamic_options_{$key}
							 *
							 * @param {array} $options Dynamic options.
							 *
							 * @return {array} Dynamic options.
							 *
							 * @example <caption>Extend dropdown dynamic options by field $key.</caption>
							 * function my_select_dropdown_dynamic_options( $options ) {
							 *     // your code here
							 *     return $options;
							 * }
							 * add_filter( 'um_select_dropdown_dynamic_options_{$key}', 'my_select_dropdown_dynamic_options', 10, 1 );
							 */
							$options = apply_filters( "um_select_dropdown_dynamic_options_{$key}", $options );
						}
					}

					if ( 'role' === $form_key ) {
						$options = $this->get_available_roles( $form_key, $options );
					}

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

					// Switch options pair for custom options from a callback function.
					if ( ! empty( $data['custom_dropdown_options_source'] ) ) {
						$options_pair = true;
					}

					$field_value = '';

					$output .= '<select data-default="' . esc_attr( $default ) . '" ' . $disabled . ' ' . $select_original_option_value . ' ' . $disabled_by_parent_option . '  name="' . esc_attr( $form_key ) . '" id="' . esc_attr( $field_id ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" class="' . esc_attr( $this->get_class( $key, $data, $class ) ) . '" style="width: 100%" data-placeholder="' . esc_attr( $placeholder ) . '" ' . $atts_ajax . ' ' . $this->aria_valid_attributes( $this->is_error( $form_key ), $form_key ) . '>';
					$output .= '<option value=""></option>';

					// add options
					if ( ! empty( $options ) ) {
						foreach ( $options as $k => $v ) {

							$v = rtrim( $v );

							$option_value                 = $v;
							$um_field_checkbox_item_title = $v;

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

							$output .= '>' . esc_html__( $um_field_checkbox_item_title, 'ultimate-member' ) . '</option>';
						}
					}

					if ( ! empty( $disabled ) ) {
						$output .= $this->disabled_hidden_field( $form_key, $field_value );
					}

					$output .= '</select>';

					$output .= '</div>';

					if ( $this->is_error( $form_key ) ) {
						$output .= $this->field_error( $this->show_error( $form_key ), $form_key );
					} elseif ( $this->is_notice( $form_key ) ) {
						$output .= $this->field_notice( $this->show_notice( $form_key ), $form_key );
					}

					$output .= '</div>';
					break;
				/* Multi-Select dropdown */
				case 'multiselect':
					$options = array();
					if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
						$options = $data['options'];
					}

					$max_selections = isset( $data['max_selections'] ) ? absint( $data['max_selections'] ) : 0;

					$field_id   = $key;
					$field_name = $key;

					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					$class = 'um-s1';
					if ( isset( $data['allowclear'] ) && 0 === $data['allowclear'] ) {
						$class = 'um-s2';
					}

					if ( isset( $data['label'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$has_icon = ! empty( $data['icon'] ) && isset( $this->field_icons ) && 'field' === $this->field_icons;

					$output .= '<div class="um-field-area ' . ( $has_icon ? 'um-field-area-has-icon' : '' ) . ' ">';
					if ( $has_icon ) {
						$output .= '<div class="um-field-icon"><i class="' . esc_attr( $data['icon'] ) . '"></i></div>';
					}

					$output .= '<select  ' . $disabled . ' multiple="multiple" name="' . esc_attr( $field_name ) . '[]" id="' . esc_attr( $field_id ) . '" data-maxsize="' . esc_attr( $max_selections ) . '" data-validate="' . esc_attr( $validate ) . '" data-key="' . esc_attr( $key ) . '" class="' . $this->get_class( $key, $data, $class ) . '" style="width: 100%" data-placeholder="' . esc_attr( $placeholder ) . '" ' . $this->aria_valid_attributes( $this->is_error( $key ), $field_name ) . '>';

					if ( isset( $options ) && 'builtin' === $options ) {
						$options = UM()->builtin()->get( $data['filter'] );
					}

					if ( ! isset( $options ) ) {
						$options = UM()->builtin()->get( 'countries' );
					}

					if ( isset( $options ) ) {
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
					}

					/** This filter is documented in includes/core/class-fields.php */
					$use_keyword = apply_filters( 'um_select_options_pair', null, $data );

					// Switch options pair for custom options from a callback function.
					if ( ! empty( $data['custom_dropdown_options_source'] ) ) {
						$use_keyword = true;
					}

					// Add an empty option!
					$output .= '<option value=""></option>';

					$arr_selected = array();
					// add options
					if ( ! empty( $options ) && is_array( $options ) ) {
						foreach ( $options as $k => $v ) {

							$v = rtrim( $v );

							$um_field_checkbox_item_title = $v;
							$opt_value                    = $v;

							if ( $use_keyword ) {
								$opt_value = $k;
							}

							$opt_value = $this->filter_field_non_utf8_value( $opt_value );

							$output .= '<option value="' . esc_attr( $opt_value ) . '" ';
							if ( $this->is_selected( $key, $opt_value, $data ) ) {

								$output                    .= 'selected';
								$arr_selected[ $opt_value ] = $opt_value;
							}

							$output .= '>' . esc_html__( $um_field_checkbox_item_title, 'ultimate-member' ) . '</option>';

						}
					}

					$output .= '</select>';

					if ( ! empty( $disabled ) && ! empty( $arr_selected ) ) {
						foreach ( $arr_selected as $item ) {
							$output .= $this->disabled_hidden_field( $key . '[]', $item );
						}
					}

					$output .= '</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $field_name );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $field_name );
					}

					$output .= '</div>';
					break;
				/* Radio */
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

					$output .= '<div class="um-field-area">';

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

					if ( ! empty( $options ) ) {
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
							if ( 0 === $i % 2 ) {
								$col_class = ' right ';
							} else {
								$col_class = '';
							}

							if ( $this->is_radio_checked( $key, $option_value, $data ) ) {
								$active = 'active';
								$class  = 'um-icon-android-radio-button-on';
							} else {
								$active = '';
								$class  = 'um-icon-android-radio-button-off';
							}

							// It's for a legacy case `array_key_exists( 'editable', $data )`.
							if ( array_key_exists( 'editable', $data ) && empty( $data['editable'] ) ) {
								$col_class .= ' um-field-radio-state-disabled';
							}

							$output .= '<label class="um-field-radio ' . esc_attr( $active ) . ' um-field-half ' . esc_attr( $col_class ) . '">';

							$option_value = $this->filter_field_non_utf8_value( $option_value );

							$output .= '<input ' . $disabled . ' type="radio" name="' . ( ( 'role' === $form_key ) ? esc_attr( $form_key ) : esc_attr( $form_key ) . '[]' ) . '" value="' . esc_attr( $option_value ) . '" ';

							if ( $this->is_radio_checked( $key, $option_value, $data ) ) {
								$output             .= 'checked';
								$field_value[ $key ] = $option_value;
							}

							$output .= ' />';

							$output .= '<span class="um-field-radio-state"><i class="' . esc_attr( $class ) . '"></i></span>';
							$output .= '<span class="um-field-radio-option">' . esc_html__( $um_field_checkbox_item_title, 'ultimate-member' ) . '</span>';
							$output .= '</label>';

							if ( 0 === $i % 2 ) {
								$output .= '<div class="um-clear"></div>';
							}
						}
					}

					if ( ! empty( $disabled ) ) {
						foreach ( $field_value as $item ) {
							$output .= $this->disabled_hidden_field( $form_key, $item );
						}
					}

					$output .= '<div class="um-clear"></div>';

					$output .= '</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $form_key );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $form_key );
					}

					$output .= '</div>';
					break;
				/* Checkbox */
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

					$output .= '<div class="um-field-area">';

					// Add options.
					$i = 0;
					foreach ( $options as $k => $v ) {

						$v = rtrim( $v );

						$i++;
						if ( 0 === $i % 2 ) {
							$col_class = ' right ';
						} else {
							$col_class = '';
						}

						if ( $this->is_selected( $key, $v, $data ) ) {
							$active = 'active';
							$class  = 'um-icon-android-checkbox-outline';
						} else {
							$active = '';
							$class  = 'um-icon-android-checkbox-outline-blank';
						}

						// It's for a legacy case `array_key_exists( 'editable', $data )`.
						if ( array_key_exists( 'editable', $data ) && empty( $data['editable'] ) ) {
							$col_class .= ' um-field-radio-state-disabled';
						}

						$output .= '<label class="um-field-checkbox ' . esc_attr( $active ) . ' um-field-half ' . esc_attr( $col_class ) . '">';

						$um_field_checkbox_item_title = $v;

						$v          = $this->filter_field_non_utf8_value( $v );
						$value_attr = ( ! empty( $v ) && is_string( $v ) ) ? wp_strip_all_tags( $v ) : $v;

						$output .= '<input  ' . $disabled . ' type="checkbox" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $value_attr ) . '" ';

						if ( $this->is_selected( $key, $v, $data ) ) {
							$output .= 'checked';
						}

						$output .= ' />';

						if ( ! empty( $disabled ) && $this->is_selected( $key, $v, $data ) ) {
							$output .= $this->disabled_hidden_field( $key . '[]', $value_attr );
						}

						$output .= '<span class="um-field-checkbox-state"><i class="' . esc_attr( $class ) . '"></i></span>';

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

						$output .= '<span class="um-field-checkbox-option">' . esc_html__( $um_field_checkbox_item_title, 'ultimate-member' ) . '</span>';
						$output .= '</label>';

						if ( 0 === $i % 2 ) {
							$output .= '<div class="um-clear"></div>';
						}
					}

					$output .= '<div class="um-clear"></div>';
					$output .= '</div>';

					if ( $this->is_error( $key ) ) {
						$output .= $this->field_error( $this->show_error( $key ), $key );
					} elseif ( $this->is_notice( $key ) ) {
						$output .= $this->field_notice( $this->show_notice( $key ), $key );
					}

					$output .= '</div>';
					break;
				/* HTML */
				case 'block':
					$content = array_key_exists( 'content', $data ) ? $data['content'] : '';
					// @todo WP_KSES for $content
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>' . $content . '</div>';
					break;
				/* Shortcode */
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

		/**
		 * Filter for user roles
		 *
		 * @param $form_key
		 * @param array $options
		 * @return array
		 */
		function get_available_roles( $form_key, $options = array() ) {
			if ( 'role' !== $form_key ) {
				return $options;
			}

			// role field
			global $wp_roles;

			$exclude_roles = array_diff( array_keys( $wp_roles->roles ), UM()->roles()->get_editable_user_roles() );
			$roles         = UM()->roles()->get_roles( false, $exclude_roles );

			if ( ! empty( $options ) ) {

				$roles = array_map( function( $item ) {
					return html_entity_decode( $item, ENT_QUOTES );
				}, $roles );

				//fix when customers change options for role (radio/dropdown) fields
				$intersected_options = array();
				foreach ( $options as $key => $title ) {
					if ( false !== $search_key = array_search( $title, $roles ) ) {
						$intersected_options[ $search_key ] = $title;
					} elseif ( isset( $roles[ $key ] ) ) {
						$intersected_options[ $key ] = $title;
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
			foreach ( $arr as $key => $row ) {
				if ( $key == 'form_id' ) {
					unset( $arr['form_id'] );
					continue;
				}

				if ( isset( $row[ $col ] ) ) {
					$sort_col[ $key ] = $row[ $col ];
				} else {
					unset( $arr[ $key ] );
				}
			}

			array_multisort( $sort_col, $dir, $arr );

			return $arr;
		}


		/**
		 * Get fields in row
		 *
		 * @param int $row_id
		 *
		 * @return string
		 */
		function get_fields_by_row( $row_id ) {
			if ( ! isset( $this->get_fields ) ) {
				return '';
			}

			foreach ( $this->get_fields as $key => $array ) {
				if ( ! isset( $array['in_row'] ) || ( isset( $array['in_row'] ) && $array['in_row'] == $row_id ) ) {
					$results[ $key ] = $array;
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
			if ( ! is_array( $row_fields ) ) {
				return '';
			}
			foreach ( $row_fields as $key => $array ) {
				if ( ! isset( $array['in_sub_row'] ) || ( isset( $array['in_sub_row'] ) && $array['in_sub_row'] == $subrow_id ) ) {
					$results[ $key ] = $array;
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
			foreach ( $this->get_fields as $key => $array ) {
				if ( isset( $array['in_group'] ) && $array['in_group'] == $group_id ) {
					$results[ $key ] = $array;
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
			foreach ( $fields as $key => $array ) {
				if ( isset( $array['in_column'] ) && $array['in_column'] == $col_number ) {
					$results[ $key ] = $array;
				}
			}

			return ( isset ( $results ) ) ? $results : '';
		}


		/**
		 * Display fields
		 *
		 * @param string $mode
		 * @param array $args
		 *
		 * @return string|null
		 * @throws \Exception
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

			$this->field_icons = ( isset( $this->global_args['icons'] ) ) ? $this->global_args['icons'] : 'label';

			// start output here
			$this->get_fields = $this->get_fields();

			if ( ! empty( $this->get_fields ) ) {

				// find rows
				foreach ( $this->get_fields as $key => $array ) {
					if ( isset( $array['type'] ) && 'row' === $array['type'] ) {
						$this->rows[ $key ] = $array;
						unset( $this->get_fields[ $key ] ); // not needed anymore
					}
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

				// master rows
				foreach ( $this->rows as $row_id => $row_array ) {

					$row_fields = $this->get_fields_by_row( $row_id );
					if ( $row_fields ) {

						$output .= $this->new_row_output( $row_id, $row_array );

						$sub_rows = ( isset( $row_array['sub_rows'] ) ) ? $row_array['sub_rows'] : 1;
						for ( $c = 0; $c < $sub_rows; $c++ ) {

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

								if ( $cols_num == 1 ) {

									$output .= '<div class="um-col-1">';
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

									$output .= '<div class="um-col-121">';
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

									$output .= '<div class="um-col-122">';
									$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
									if ( $col2_fields ) {
										foreach ( $col2_fields as $key => $data ) {
											if ( ! empty( $args['is_block'] ) ) {
												$data['is_block'] = true;
											}
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div><div class="um-clear"></div>';

								} else {

									$output .= '<div class="um-col-131">';
									$col1_fields = $this->get_fields_in_column( $subrow_fields, 1 );
									if ( $col1_fields ) {
										foreach ( $col1_fields as $key => $data ) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-132">';
									$col2_fields = $this->get_fields_in_column( $subrow_fields, 2 );
									if ( $col2_fields ) {
										foreach ( $col2_fields as $key => $data ) {
											$output .= $this->edit_field( $key, $data );
										}
									}
									$output .= '</div>';

									$output .= '<div class="um-col-133">';
									$col3_fields = $this->get_fields_in_column( $subrow_fields, 3 );
									if ( $col3_fields ) {
										foreach ( $col3_fields as $key => $data ) {
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
		 * @param string $key
		 * @param array $data
		 * @param bool $rule
		 *
		 * @return string|null
		 * @throws \Exception
		 */
		public function view_field( $key, $data, $rule = false ) {
			if ( '_um_last_login' === $key ) {
				$profile_id      = um_user( 'ID' );
				$show_last_login = get_user_meta( $profile_id, 'um_show_last_login', true );
				if ( ! empty( $show_last_login ) && 'no' === $show_last_login[0] ) {
					return '';
				}
			}

			$output = '';

			// Get whole field data.
			if ( is_array( $data ) ) {
				$data = $this->get_field( $key );
			}

			// Invalid field data.
			if ( ! is_array( $data ) ) {
				return '';
			}

			//hide if empty type
			if ( ! array_key_exists( 'type', $data ) || empty( $data['type'] ) ) {
				return '';
			}
			$type = $data['type'];

			if ( isset( $data['in_group'] ) && '' !== $data['in_group'] && 'group' !== $rule ) {
				return '';
			}

			// Invisible on profile page.
			if ( 'password' === $type || ( array_key_exists( 'visibility', $data ) && 'edit' === $data['visibility'] ) ) {
				return '';
			}

			// Disable these fields in profile view only.
			if ( 'user_password' === $key && 'profile' === $this->set_mode ) {
				return '';
			}

			$default = array_key_exists( 'default', $data ) ? $data['default'] : false;

			// Hide if empty.
			$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();
			if ( ! in_array( $type, $fields_without_metakey, true ) ) {
				$_field_value = $this->field_value( $key, $default, $data );

				if ( ! isset( $_field_value ) || '' === $_field_value ) {
					return '';
				}
			}

			if ( ! um_can_view_field( $data ) ) {
				return '';
			}

			if ( ! um_field_conditions_are_met( $data ) ) {
				return '';
			}

			$classes = '';
			if ( ! empty( $data['classes'] ) ) {
				$classes = explode( ' ', $data['classes'] );
			}

			$conditional = '';
			if ( ! empty( $data['conditional'] ) ) {
				$conditional = $data['conditional'];
			}

			switch ( $type ) {
				/* Default */
				default:
					$_field_value = $this->field_value( $key, $default, $data );

					if ( ( isset( $_field_value ) && '' !== $_field_value ) || in_array( $type, $fields_without_metakey, true ) ) {
						$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

						if ( isset( $data['label'] ) || ! empty( $data['icon'] ) ) {

							if ( ! isset( $data['label'] ) ) {
								$data['label'] = '';
							}

							$output .= $this->field_label( $data['label'], $key, $data );
						}

						$res = $_field_value;
						if ( ! empty( $res ) ) {
							$res = stripslashes( $res );
						}

						$bio_key = UM()->profile()->get_show_bio_key( $this->global_args );
						if ( $bio_key === $data['metakey'] ) {
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
									$res = wp_kses_post( make_clickable( wpautop( $res ) ) );
								} else {
									$res = esc_html( $res );
								}
							} else {
								if ( ! empty( $data['html'] ) ) {
									$res = wp_kses_post( make_clickable( wpautop( $res ) ) );
								} else {
									$res = esc_html( $res );
								}
							}

							$res = nl2br( $res );
						}

						$data['is_view_field'] = true;

						/**
						 * Filters the inner field HTML on view mode.
						 *
						 * @since 1.3.x
						 * @hook  um_view_field
						 *
						 * @param {string} $output  Field inner HTML.
						 * @param {array}  $data    Field Data.
						 * @param {string} $type    Field Type.
						 *
						 * @return {string} Field inner HTML.
						 *
						 * @example <caption>Change field's inner HTML on view mode.</caption>
						 * function my_view_field( $output, $data, $type ) {
						 *     // your code here
						 *     return $output;
						 * }
						 * add_filter( 'um_view_field', 'my_view_field', 10, 3 );
						 */
						$res = apply_filters( 'um_view_field', $res, $data, $type );
						/**
						 * Filters the inner field HTML on view mode by field type {$type}.
						 *
						 * @since 1.3.x
						 * @hook  um_view_field_value_{$type}
						 *
						 * @param {string} $output Field inner HTML.
						 * @param {array}  $data   Field Data.
						 *
						 * @return {string} Field inner HTML.
						 *
						 * @example <caption>Change field HTML on view mode by field type.</caption>
						 * function my_view_field( $output, $data ) {
						 *     // your code here
						 *     return $output;
						 * }
						 * add_filter( 'um_view_field_value_{$type}', 'my_view_field', 10, 2 );
						 */
						$res = apply_filters( "um_view_field_value_{$type}", $res, $data );

						$id_attr = '';
						if ( ! in_array( $type, $fields_without_metakey, true ) ) {
							$id_attr = ' id="' . esc_attr( $key . UM()->form()->form_suffix ) . '"';
						}

						if ( empty( $res ) && ! ( 'number' === $type && '' !== $res ) ) {
							$output = '';
						} else {
							$output .= '<div class="um-field-area">';
							$output .= '<div class="um-field-value"' . $id_attr . '>' . $res . '</div>';
							$output .= '</div>';

							$output .= '</div>';
						}
					}

					break;
					/* oEmbed */
				case 'oembed':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) || ! empty( $data['icon'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}
					$response = wp_oembed_get( $_field_value );
					if ( empty( $response ) ) {
						$response = $_field_value;
					}
					$output .= '<div class="um-field-area">';
					$output .= '<div class="um-field-value">' . $response . '</div>';
					$output .= '</div>';
					break;
					/* HTML */
				case 'block':
					$content = array_key_exists( 'content', $data ) ? $data['content'] : '';
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>' . $content . '</div>';
					break;
					/* Shortcode */
				case 'shortcode':
					$content = array_key_exists( 'content', $data ) ? $data['content'] : '';
					$content = str_replace( '{profile_id}', um_profile_id(), $content );
					$content = apply_shortcodes( $content );

					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>' . $content . '</div>';
					break;
					/* Gap/Space */
				case 'spacing':
					$field_style = array();
					if ( array_key_exists( 'spacing', $data ) ) {
						$field_style = array( 'height' => $data['spacing'] );
					}
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data, $field_style ) . '></div>';
					break;
					/* A line divider */
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
					/* Rating */
				case 'rating':
					$output .= '<div ' . $this->get_atts( $key, $classes, $conditional, $data ) . '>';

					if ( isset( $data['label'] ) || ! empty( $data['icon'] ) ) {
						$output .= $this->field_label( $data['label'], $key, $data );
					}

					$number = 5;
					if ( array_key_exists( 'number', $data ) && in_array( absint( $data['number'] ), array( 5, 10 ), true ) ) {
						$number = $data['number'];
					}
					ob_start();
					?>

					<div class="um-field-area">
						<div class="um-field-value">
							<div class="um-rating-readonly um-raty" id="<?php echo esc_attr( $key ); ?>"
								data-key="<?php echo esc_attr( $key ); ?>" data-number="<?php echo esc_attr( $number ); ?>"
								data-score="<?php echo esc_attr( $this->field_value( $key, $default, $data ) ); ?>"></div>
						</div>
					</div>

					<?php
					$output .= ob_get_clean();
					$output .= '</div>';
					break;
			}

			// Custom filter for field output.
			if ( isset( $this->set_mode ) ) {
				/**
				 * Filters outer field HTML by field $key.
				 *
				 * @since 1.3.x
				 * @hook  um_{$key}_form_show_field
				 *
				 * @param {string} $output Field outer HTML.
				 * @param {string} $mode   Field Mode.
				 *
				 * @return {string} Field outer HTML.
				 *
				 * @example <caption>Change field outer HTML by field $key.</caption>
				 * function my_form_show_field( $output, $mode ) {
				 *     // your code here
				 *     return $output;
				 * }
				 * add_filter( 'um_{$key}_form_show_field', 'my_form_show_field', 10, 2 );
				 */
				$output = apply_filters( "um_{$key}_form_show_field", $output, $this->set_mode );
				/**
				 * Filters outer field HTML by field $type.
				 *
				 * @since 1.3.x
				 * @hook  um_{$type}_form_show_field
				 *
				 * @param {string} $output Field outer HTML.
				 * @param {string} $mode   Field Mode.
				 *
				 * @return {string} Field outer HTML.
				 *
				 * @example <caption>Change field outer HTML by field $type.</caption>
				 * function my_form_show_field( $output, $mode ) {
				 *     // your code here
				 *     return $output;
				 * }
				 * add_filter( 'um_{$type}_form_show_field', 'my_form_show_field', 10, 2 );
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
		 * @param string $mode
		 * @param array $args
		 *
		 * @return string|null
		 * @throws \Exception
		 */
		public function display_view( $mode, $args ) {
			$output = null;

			$this->global_args = $args;

			UM()->form()->form_suffix = '-' . $this->global_args['form_id'];

			$this->set_mode = $mode;
			$this->set_id   = absint( $this->global_args['form_id'] );

			$this->field_icons = ( isset( $this->global_args['icons'] ) ) ? $this->global_args['icons'] : 'label';

			// start output here
			$this->get_fields = $this->get_fields();

			if ( UM()->options()->get( 'profile_empty_text' ) ) {

				$emo = UM()->options()->get( 'profile_empty_text_emo' );
				if ( $emo ) {
					$emo = '<i class="um-faicon-frown-o"></i>';
				} else {
					$emo = false;
				}

				if ( um_is_myprofile() ) {
					if ( isset( $_GET['profiletab'] ) && 'main' !== $_GET['profiletab'] ) {
						$tab         = sanitize_key( $_GET['profiletab'] );
						$edit_action = 'edit_' . $tab;
						$profile_url = um_user_profile_url( um_profile_id() );
						$edit_url    = add_query_arg( array( 'profiletab' => $tab, 'um_action' => $edit_action ), $profile_url );
					} else {
						$edit_url    = um_edit_profile_url();
					}
					// translators: %s: edit user link.
					$output .= '<p class="um-profile-note">' . $emo . '<span>' . sprintf( __( 'Your profile is looking a little empty. Why not <a href="%s">add</a> some information!', 'ultimate-member' ), esc_url( $edit_url ) ) . '</span></p>';
				} else {
					$output .= '<p class="um-profile-note">' . $emo . '<span>' . __( 'This user has not added any information to their profile yet.', 'ultimate-member' ) . '</span></p>';
				}
			}

			if ( ! empty( $this->get_fields ) ) {

				// find rows
				foreach ( $this->get_fields as $key => $array ) {
					if ( isset( $array['type'] ) && 'row' === $array['type'] ) {
						$this->rows[ $key ] = $array;
						unset( $this->get_fields[ $key ] ); // not needed anymore
					}
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

				// master rows
				foreach ( $this->rows as $row_id => $row_array ) {

					$row_fields = $this->get_fields_by_row( $row_id );

					if ( $row_fields ) {

						$output .= $this->new_row_output( $row_id, $row_array );

						$sub_rows = ( isset( $row_array['sub_rows'] ) ) ? $row_array['sub_rows'] : 1;
						for ( $c = 0; $c < $sub_rows; $c++ ) {

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

								if ( $cols_num == 1 ) {

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

				$output .= '<div class="um-row-heading" style="' . esc_attr( $css_heading_margin . $css_heading_padding . $css_heading_border . $css_heading_background_color . $css_heading_text_color ) . '">';

				if ( ! empty( $row_array['icon'] ) ) {
					$css_icon_color = ! empty( $row_array['icon_color'] ) ? 'color: ' . esc_attr( $row_array['icon_color'] ) . ';' : '';
					$output        .= '<span class="um-row-heading-icon" style="' . esc_attr( $css_icon_color ) . '"><i class="' . esc_attr( $row_array['icon'] ) . '"></i></span>';
				}

				if ( ! empty( $row_array['heading_text'] ) ) {
					$output .= esc_html( $row_array['heading_text'] );
				}

				$output .= '</div>';

				$css_border .= 'border-top-width: 0px;';
				$css_margin .= 'margin-top: 0px;';
			} else {
				// No heading.
				if ( ! empty( $borderradius ) ) {
					$css_borderradius = 'border-radius: ' . esc_attr( $borderradius ) . ';';
				}
			}

			$output .= '<div class="um-row ' . esc_attr( $row_id . ' ' . $css_class ) . '" style="' . esc_attr( $css_padding . $css_background . $css_margin . $css_border . $css_borderstyle . $css_bordercolor . $css_borderradius . $css_text_color ) . '">';
			return $output;
		}

		/**
		 * Admin Builder silent AJAX handler for actions with fields.
		 */
		public function do_ajax_action() {
			UM()->admin()->check_ajax_nonce();

			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator.', 'ultimate-member' ) );
			}

			if ( ! isset( $_POST['act_id'] ) ) {
				wp_send_json_error( __( 'Invalid action.', 'ultimate-member' ) );
			}

			$in_row   = isset( $_POST['in_row'] ) ? absint( $_POST['in_row'] ) : 0;
			$position = array(
				'in_row'     => '_um_row_' . ( $in_row + 1 ),
				'in_sub_row' => isset( $_POST['in_sub_row'] ) ? absint( $_POST['in_sub_row'] ) : '',
				'in_column'  => isset( $_POST['in_column'] ) ? absint( $_POST['in_column'] ) : '',
				'in_group'   => isset( $_POST['in_group'] ) ? absint( $_POST['in_group'] ) : '',
			);

			switch ( sanitize_key( $_POST['act_id'] ) ) {
				case 'um_admin_duplicate_field':
					// arg1 is a field metakey(id)
					// arg2 is a form ID.
					$this->duplicate_field( sanitize_text_field( $_POST['arg1'] ), absint( $_POST['arg2'] ) );
					break;
				case 'um_admin_remove_field_global':
					// arg1 is a field metakey(id)
					$this->delete_field_from_db( sanitize_text_field( $_POST['arg1'] ) );
					break;
				case 'um_admin_remove_field':
					// arg1 is a field metakey(id)
					// arg2 is a form ID.
					$this->delete_field_from_form( sanitize_text_field( $_POST['arg1'] ), absint( $_POST['arg2'] ) );
					break;
				case 'um_admin_add_field_from_predefined':
					// arg1 is a field metakey(id)
					// arg2 is a form ID.
					$this->add_field_from_predefined( sanitize_text_field( $_POST['arg1'] ), absint( $_POST['arg2'] ), $position );
					break;
				case 'um_admin_add_field_from_list':
					// arg1 is a field metakey(id)
					// arg2 is a form ID.
					$this->add_field_from_list( sanitize_text_field( $_POST['arg1'] ), absint( $_POST['arg2'] ), $position );
					break;
			}
			// phpcs:enable WordPress.Security.NonceVerification
			wp_send_json_success();
		}

		/**
		 * Get rendered field attributes
		 *
		 * @since  2.1.2
		 *
		 * @param  string $key
		 * @param  array $classes
		 * @param  string $conditional
		 * @param  array $data
		 * @param  array $field_style
		 *
		 * @return string/html
		 */
		function get_atts( $key, $classes, $conditional, $data, $field_style = array() ) {

			array_unshift( $classes, 'um-field-' . $data['type'] );
			array_unshift( $classes, 'um-field' );

			$field_atts = array(
				'id'        => array(
					"um_field_{$this->set_id}_{$key}",
				),
				'class'     => $classes,
				'data-key'  => array(
					esc_attr( $key )
				)
			);

			$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();

			if ( in_array( $data['type'], $fields_without_metakey, true ) ) {
				unset( $field_atts['id'] );

				if ( empty( $field_atts['data-key'] ) ) {
					unset( $field_atts['data-key'] );
				}
			}

			if ( ! empty( $field_style ) && is_array( $field_style ) ) {

				$arr_inline_style = '';
				foreach ( $field_style as $style_attr => $style_value ) {
					$arr_inline_style .= esc_attr( $style_attr ) . ':' . esc_attr( $style_value ) . ';';
				}
				$field_atts['style'] = array( $arr_inline_style );
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_field_extra_atts
			 * @description user for adding extra field attributes
			 * @input_vars
			 * [{"var":"$field_atts","type":"array","desc":"Field attributes"},
			 * [{"var":"$key","type":"string","desc":"Field id"},
			 * {"var":"$data","type":"array","desc":"Field Data"}]
			 * @change_log
			 * ["Since: 2.0.57"]
			 * @usage add_filter( 'um_field_extra_atts', 'function_name', 10, 3 );
			 * @example
			 * <?php
			 * add_filter( 'um_field_extra_atts', 'function_name', 10, 3 );
			 * function function_name( $field_atts, $key, $data ) {
			 *     // your code here
			 *     return $array_extra_atts;
			 * }
			 * ?>
			 */
			$field_atts = apply_filters( 'um_field_extra_atts', $field_atts, $key, $data );

			$html_atts = '';
			foreach ( $field_atts as $att_name => $att_values ) {
				$att_values = implode( " ", $att_values );
				$html_atts .= " {$att_name}=\"" . esc_attr( $att_values ) . "\"";
			}

			$html_atts .= $conditional;

			return $html_atts;
		}
	}
}
