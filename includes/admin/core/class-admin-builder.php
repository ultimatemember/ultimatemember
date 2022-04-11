<?php
namespace um\admin\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\core\Admin_Builder' ) ) {


	/**
	 * Class Admin_Builder
	 * @package um\admin\core
	 */
	class Admin_Builder {


		/**
		 * @var
		 */
		var $form_id;


		/**
		 * Admin_Builder constructor.
		 */
		function __construct() {
			add_action( 'um_admin_field_modal_footer', array( &$this, 'add_conditional_support' ), 10, 4 );
			add_filter( 'um_admin_builder_skip_field_validation', array( &$this, 'skip_field_validation' ), 10, 3 );
			add_filter( 'um_admin_pre_save_field_to_form', array( &$this, 'um_admin_pre_save_field_to_form' ), 1 );
			add_filter( 'um_admin_pre_save_fields_hook', array( &$this, 'um_admin_pre_save_fields_hook' ), 1 );
			add_filter( 'um_admin_field_update_error_handling', array( &$this, 'um_admin_field_update_error_handling' ), 1, 2 );
		}


		/**
		 * Apply a filter to handle errors for field updating in backend
		 *
		 * @param $errors
		 * @param $array
		 *
		 * @return mixed
		 */
		function um_admin_field_update_error_handling( $errors, $array ) {
			/**
			 * @var $field_type
			 */
			extract( $array );

			$field_attr = UM()->builtin()->get_core_field_attrs( $field_type );

			if ( isset( $field_attr['validate'] ) ) {

				$validate = $field_attr['validate'];
				foreach ( $validate as $post_input => $arr ) {

					$skip = apply_filters( 'um_admin_builder_skip_field_validation', false, $post_input, $array );
					if ( $skip ) {
						continue;
					}

					$mode = $arr['mode'];

					switch ( $mode ) {

						case 'numeric':
							if ( ! empty( $array['post'][ $post_input ] ) && ! is_numeric( $array['post'][ $post_input ] ) ){
								$errors[ $post_input ] = $validate[ $post_input ]['error'];
							}
							break;

						case 'unique':
							if ( ! isset( $array['post']['edit_mode'] ) ) {
								if ( UM()->builtin()->unique_field_err( $array['post'][ $post_input ] ) ) {
									$errors[ $post_input ] = UM()->builtin()->unique_field_err( $array['post'][ $post_input ] );
								}
							}
							break;

						case 'required':
							if ( $array['post'][ $post_input ] == '' ) {
								$errors[ $post_input ] = $validate[ $post_input ]['error'];
							}
							break;

						case 'range-start':
							if ( UM()->builtin()->date_range_start_err( $array['post'][ $post_input ] ) && $array['post']['_range'] == 'date_range' ) {
								$errors[ $post_input ] = UM()->builtin()->date_range_start_err( $array['post'][ $post_input ] );
							}
							break;

						case 'range-end':
							if ( UM()->builtin()->date_range_end_err( $array['post'][ $post_input ], $array['post']['_range_start'] ) && $array['post']['_range'] == 'date_range' ) {
								$errors[ $post_input ] = UM()->builtin()->date_range_end_err( $array['post'][ $post_input ], $array['post']['_range_start'] );
							}
							break;

					}

				}

			}

			return $errors;

		}


		/**
		 * Some fields may require extra fields before saving
		 *
		 * @param $array
		 *
		 * @return mixed
		 */
		function um_admin_pre_save_fields_hook( $array ) {
			/**
			 * @var $form_id
			 * @var $field_type
			 */
			extract( $array );

			$fields_without_metakey = UM()->builtin()->get_fields_without_metakey();

			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			$count = 1;
			if ( ! empty( $fields ) ) {
				$count = count( $fields ) + 1;
			}

			// set unique meta key
			if ( in_array( $field_type, $fields_without_metakey ) && ! isset( $array['post']['_metakey'] ) ) {
				$array['post']['_metakey'] = "um_{$field_type}_{$form_id}_{$count}";
			}

			// set position
			if ( ! isset( $array['post']['_position'] ) ) {
				$array['post']['_position'] = $count;
			}

			return $array;
		}


		/**
		 * Modify field args just before it is saved into form
		 *
		 * @param $array
		 *
		 * @return mixed
		 */
		function um_admin_pre_save_field_to_form( $array ){
			unset( $array['conditions'] );
			if ( isset($array['conditional_field']) && ! empty( $array['conditional_action'] ) && ! empty( $array['conditional_operator'] ) ) {
				$array['conditional_value'] = isset( $array['conditional_value'] ) ? $array['conditional_value'] : '';
				$array['conditions'][] = array( $array['conditional_action'], $array['conditional_field'], $array['conditional_operator'], $array['conditional_value'] );
			}

			if ( isset( $array['conditional_field1'] ) && ! empty( $array['conditional_action1'] ) && ! empty( $array['conditional_operator1'] ) ) {
				$array['conditional_value1'] = isset( $array['conditional_value1'] ) ? $array['conditional_value1'] : '';
				$array['conditions'][] = array( $array['conditional_action1'], $array['conditional_field1'], $array['conditional_operator1'], $array['conditional_value1'] );
			}

			if ( isset( $array['conditional_field2'] ) && ! empty( $array['conditional_action2'] ) && ! empty( $array['conditional_operator2'] ) ) {
				$array['conditional_value2'] = isset( $array['conditional_value2'] ) ? $array['conditional_value2'] : '';
				$array['conditions'][] = array( $array['conditional_action2'], $array['conditional_field2'], $array['conditional_operator2'], $array['conditional_value2'] );
			}

			if ( isset( $array['conditional_field3'] ) && ! empty( $array['conditional_action3'] ) && ! empty( $array['conditional_operator3'] ) ) {
				$array['conditional_value3'] = isset( $array['conditional_value3'] ) ? $array['conditional_value3'] : '';
				$array['conditions'][] = array( $array['conditional_action3'], $array['conditional_field3'], $array['conditional_operator3'], $array['conditional_value3'] );
			}

			if ( isset( $array['conditional_field4'] ) && ! empty( $array['conditional_action4'] ) && ! empty( $array['conditional_operator4'] ) ) {
				$array['conditional_value4'] = isset( $array['conditional_value4'] ) ? $array['conditional_value4'] : '';
				$array['conditions'][] = array( $array['conditional_action4'], $array['conditional_field4'], $array['conditional_operator4'], $array['conditional_value4'] );
			}

			return $array;
		}


		/**
		 * Footer of modal
		 *
		 * @param $form_id
		 * @param $field_args
		 * @param $in_edit
		 * @param $edit_array
		 */
		function add_conditional_support( $form_id, $field_args, $in_edit, $edit_array ) {
			$metabox = UM()->admin()->metabox();

			if ( isset( $field_args['conditional_support'] ) && $field_args['conditional_support'] == 0 ) {
				return;
			} ?>

			<div class="um-admin-btn-toggle">

				<?php if ( $in_edit ) { $metabox->in_edit = true;  $metabox->edit_array = $edit_array; ?>
					<a href="javascript:void(0);"><i class="fas fa-plus"></i><?php _e( 'Manage conditional fields support' ); ?></a> <?php UM()->tooltip( __( 'Here you can setup conditional logic to show/hide this field based on specific fields value or conditions', 'ultimate-member' ) ); ?>
				<?php } else { ?>
					<a href="javascript:void(0);"><i class="fas fa-plus"></i><?php _e( 'Add conditional fields support' ); ?></a> <?php UM()->tooltip( __( 'Here you can setup conditional logic to show/hide this field based on specific fields value or conditions', 'ultimate-member' ) ); ?>
				<?php } ?>

				<div class="um-admin-btn-content">
					<div class="um-admin-cur-condition-template">

						<?php $metabox->field_input( '_conditional_action', $form_id ); ?>
						<?php $metabox->field_input( '_conditional_field', $form_id ); ?>
						<?php $metabox->field_input( '_conditional_operator', $form_id ); ?>
						<?php $metabox->field_input( '_conditional_value', $form_id ); ?>

						<span class="dashicons dashicons-no-alt"></span>

						<p><a href="javascript:void(0);" class="um-admin-remove-condition button um-admin-tipsy-n" title="<?php esc_attr_e( 'Remove condition', 'ultimate-member' ); ?>"><i class="fas fa-times"></i></a></p>

						<div class="um-admin-clear"></div>
					</div>
					<p class="um-admin-conditions-notice">
						<small>
							<?php _e( 'Use the condition operator `equals to` or `not equals` if the parent field has a single option.', 'ultimate-member' ); ?>
							<br><?php _e( 'Use the condition operator `greater than` or `less than` if the parent field is a number.', 'ultimate-member' ); ?>
							<br><?php _e( 'Use the condition operator `contains` if the parent field has multiple options.', 'ultimate-member' ); ?>
						</small>
					</p>
					<p><a href="javascript:void(0);" class="um-admin-new-condition button button-primary um-admin-tipsy-n" title="Add new condition"><?php _e( 'Add new rule', 'ultimate-member' ); ?></a></p>
					<p class="um-admin-reset-conditions"><a href="javascript:void(0);" class="button"><?php _e( 'Reset all rules', 'ultimate-member' ); ?></a></p>

					<div class="um-admin-clear"></div>

					<?php if ( isset( $edit_array['conditions'] ) && count( $edit_array['conditions'] ) != 0 ) {

						foreach ( $edit_array['conditions'] as $k => $arr ) {

							if ( $k == 0 ) $k = ''; ?>

							<div class="um-admin-cur-condition">

								<?php $metabox->field_input( '_conditional_action' . $k, $form_id ); ?>
								<?php $metabox->field_input( '_conditional_field' . $k , $form_id ); ?>
								<?php $metabox->field_input( '_conditional_operator' . $k, $form_id ); ?>
								<?php $metabox->field_input( '_conditional_value' . $k, $form_id ); ?>

								<p><a href="javascript:void(0);" class="um-admin-remove-condition button um-admin-tipsy-n" title="<?php esc_attr_e( 'Remove condition', 'ultimate-member' ); ?>"><i class="fas fa-times"></i></a></p>

								<div class="um-admin-clear"></div>
							</div>

							<?php
						}

					} else { ?>

						<div class="um-admin-cur-condition">

							<?php $metabox->field_input( '_conditional_action', $form_id ); ?>
							<?php $metabox->field_input( '_conditional_field', $form_id ); ?>
							<?php $metabox->field_input( '_conditional_operator', $form_id ); ?>
							<?php $metabox->field_input( '_conditional_value', $form_id ); ?>

							<p><a href="javascript:void(0);" class="um-admin-remove-condition button um-admin-tipsy-n" title="<?php esc_attr_e( 'Remove condition', 'ultimate-member' ); ?>"><i class="fas fa-times"></i></a></p>

							<div class="um-admin-clear"></div>
						</div>

					<?php } ?>
				</div>
			</div>

			<?php
		}


		/**
		 * Sort array function
		 *
		 * @param array $arr
		 * @param string $col
		 * @param int $dir
		 *
		 * @return array
		 */
		function array_sort_by_column( $arr, $col, $dir = SORT_ASC ) {
			$sort_col = array();

			foreach ( $arr as $key => $row ) {
				if ( ! empty( $row[ $col ] ) ) {
					$sort_col[ $key ] = $row[ $col ];
				}
			}

			if ( ! empty( $sort_col ) ) {
				array_multisort( $sort_col, $dir, $arr );
			}

			return $arr;
		}


		/**
		 * Get fields in row
		 *
		 * @param $row_id
		 *
		 * @return string
		 */
		function get_fields_by_row( $row_id ) {

			if ( empty( $this->global_fields ) || ! is_array( $this->global_fields ) ) {
				$this->global_fields = array();
			}

			foreach ( $this->global_fields as $key => $array ) {
				if ( ! isset( $array['in_row'] ) || ( isset( $array['in_row'] ) && $array['in_row'] == $row_id ) ) {
					$results[ $key ] = $array;
					unset( $this->global_fields[ $key ] );
				}
			}

			return ( isset ( $results ) ) ? $results : '';
		}


		/**
		 * Get fields by sub row
		 *
		 * @param $row_fields
		 * @param $subrow_id
		 *
		 * @return string
		 */
		function get_fields_in_subrow( $row_fields, $subrow_id ) {
			if ( ! is_array( $row_fields ) ) {
				return '';
			}

			foreach( $row_fields as $key => $array ) {
				if ( ! isset( $array['in_sub_row'] ) || ( isset( $array['in_sub_row'] ) && $array['in_sub_row'] == $subrow_id ) ) {
					$results[ $key ] = $array;
					unset( $this->global_fields[ $key ] );
				}
			}

			return ( isset ( $results ) ) ? $results : '';
		}


		/**
		 * Display the builder
		 */
		function show_builder() {

			$fields = UM()->query()->get_attr( 'custom_fields', $this->form_id );

			if ( empty( $fields ) ) { ?>

				<div class="um-admin-drag-row">

					<!-- Master Row Actions -->
					<div class="um-admin-drag-row-icons">
						<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="fas fa-plus"></i></a>
						<a href="javascript:void(0);" class="um-admin-drag-row-edit um_admin_edit_field_popup um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member' ); ?>" data-arg1="row" data-arg2="<?php echo esc_attr( $this->form_id ); ?>" data-arg3="_um_row_1" data-field_type="row" data-form_id="<?php echo esc_attr( $this->form_id ); ?>" data-field_key="_um_row_1"><i class="fas fa-pencil-alt"></i></a>
						<span class="um-admin-drag-row-start"><i class="fas fa-arrows-alt"></i></span>
					</div>
					<div class="um-admin-clear"></div>

					<div class="um-admin-drag-rowsubs">
						<div class="um-admin-drag-rowsub">

							<!-- Column Layout -->
							<div class="um-admin-drag-ctrls columns">
								<a href="javascript:void(0);" class="active" data-cols="1"></a>
								<a href="javascript:void(0);" data-cols="2"></a>
								<a href="javascript:void(0);" data-cols="3"></a>
							</div>

							<!-- Sub Row Actions -->
							<div class="um-admin-drag-rowsub-icons">
								<span class="um-admin-drag-rowsub-start"><i class="fas fa-arrows-alt"></i></span>
							</div><div class="um-admin-clear"></div>

							<!-- Columns -->
							<div class="um-admin-drag-col">

							</div>

							<div class="um-admin-drag-col-dynamic"></div>

							<div class="um-admin-clear"></div>

						</div>
					</div>

				</div>

				<?php

			} else {

				$this->global_fields = is_array( $fields ) ? $fields : [];

				foreach ( $this->global_fields as $key => $array ) {
					if ( $array['type'] == 'row' ) {
						$rows[ $key ] = $array;
						unset( $this->global_fields[ $key ] ); // not needed now
					}
				}

				if ( ! isset( $rows ) ) {
					$rows = array(
						'_um_row_1' => array(
							'type'      => 'row',
							'id'        => '_um_row_1',
							'sub_rows'  => 1,
							'cols'      => 1
						),
					);
				}

				foreach ( $rows as $row_id => $array ) { ?>

					<div class="um-admin-drag-row" data-original="<?php echo esc_attr( $row_id ); ?>">

						<!-- Master Row Actions -->
						<div class="um-admin-drag-row-icons">
							<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="fas fa-plus"></i></a>
							<a href="javascript:void(0);" class="um-admin-drag-row-edit um_admin_edit_field_popup um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member'); ?>" data-arg1="row" data-arg2="<?php echo esc_attr( $this->form_id ); ?>" data-arg3="<?php echo esc_attr( $row_id ); ?>" data-field_type="row" data-form_id="<?php echo esc_attr( $this->form_id ); ?>" data-field_key="<?php echo esc_attr( $row_id ); ?>"><i class="fas fa-pencil-alt"></i></a>
							<span class="um-admin-drag-row-start"><i class="fas fa-arrows-alt"></i></span>
							<?php if ( $row_id != '_um_row_1' ) {?>
								<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-row"><i class="far fa-trash-alt"></i></a>
							<?php } ?>
						</div><div class="um-admin-clear"></div>

						<div class="um-admin-drag-rowsubs">

							<?php $row_fields = $this->get_fields_by_row( $row_id );

							$sub_rows = ( isset( $array['sub_rows'] ) ) ? $array['sub_rows'] : 1;
							for ( $c = 0; $c < $sub_rows; $c++  ) {

								$subrow_fields = $this->get_fields_in_subrow( $row_fields, $c );

								?>

								<div class="um-admin-drag-rowsub">

									<!-- Column Layout -->
									<div class="um-admin-drag-ctrls columns">

										<?php

										if ( ! isset( $array['cols'] ) ) {
											$col_num = 1;
										} elseif ( is_numeric( $array['cols'] ) ) {
											$col_num = (int) $array['cols'];
										} else {
											$col_split = explode( ':', $array['cols'] );
											$col_num = $col_split[ $c ];
										}

										for ( $i = 1; $i <= 3; $i++ ) {
											echo '<a href="javascript:void(0);" data-cols="'.$i.'" ';
											if ( $col_num == $i ) echo 'class="active"';
											echo '></a>';
										}

										?>

									</div>

									<!-- Sub Row Actions -->
									<div class="um-admin-drag-rowsub-icons">
										<span class="um-admin-drag-rowsub-start"><i class="fas fa-arrows-alt"></i></span>
										<?php if ( $c > 0 ) { ?><a href="javascript:void(0);" class="um-admin-tipsy-n" title="Delete Row" data-remove_element="um-admin-drag-rowsub"><i class="far fa-trash-alt"></i></a><?php } ?>
									</div>
									<div class="um-admin-clear"></div>

									<!-- Columns -->
									<div class="um-admin-drag-col">

										<?php

										if ( is_array( $subrow_fields ) ) {

											$subrow_fields = $this->array_sort_by_column( $subrow_fields, 'position');

											foreach( $subrow_fields as $key => $keyarray ) {
												/**
												 * @var $type
												 * @var $title
												 */
												extract( $keyarray );

												?>

												<div class="um-admin-drag-fld um-admin-delete-area um-field-type-<?php echo $type; ?> <?php echo $key; ?>" data-group="<?php echo (isset($keyarray['in_group'])) ? $keyarray['in_group'] : ''; ?>" data-key="<?php echo $key; ?>" data-column="<?php echo ( isset($keyarray['in_column']) ) ? $keyarray['in_column'] : 1; ?>">

													<div class="um-admin-drag-fld-title um-field-type-<?php echo $type; ?>">
														<?php if ( $type === 'group' ) { ?>
															<i class="fas fa-plus"></i>
														<?php } else if ( isset($keyarray['icon']) && !empty( $keyarray['icon'] ) ) { ?>
															<i class="<?php echo $keyarray['icon']; ?>"></i>
														<?php } ?><?php echo ! empty( $keyarray['title'] ) ? $keyarray['title'] : __( '(no title)', 'ultimate-member' ); ?></div>
													<?php $field_name = isset( UM()->builtin()->core_fields[$type]['name'] ) ? UM()->builtin()->core_fields[$type]['name'] : ''; ?>
													<div class="um-admin-drag-fld-type um-field-type-<?php echo $type; ?>"><?php echo $field_name; ?></div>
													<div class="um-admin-drag-fld-icons um-field-type-<?php echo $type; ?>">

														<a href="javascript:void(0);" class="um_admin_edit_field_popup um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit', 'ultimate-member' ) ?>" data-arg1="<?php echo $type; ?>" data-arg2="<?php echo $this->form_id; ?>" data-arg3="<?php echo $key; ?>" data-field_type="<?php echo esc_attr( $type ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>" data-field_key="<?php echo esc_attr( $key ); ?>"><i class="fas fa-pencil-alt"></i></a>

														<a href="javascript:void(0);" class="um_admin_duplicate_field um-admin-tipsy-n" title="<?php esc_attr_e( 'Duplicate', 'ultimate-member' ) ?>" data-field_key="<?php echo esc_attr( $key ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>"><i class="far fa-copy"></i></a>

														<?php if ( $type == 'group' ) { ?>
															<a href="javascript:void(0);" class="um_admin_remove_field um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Group', 'ultimate-member' ) ?>" data-remove_element="um-admin-drag-fld.um-field-type-group" data-field_key="<?php echo esc_attr( $key ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>"><i class="far fa-trash-alt"></i></a>
														<?php } else { ?>
															<a href="javascript:void(0);" class="um_admin_remove_field um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete', 'ultimate-member' ) ?>" data-field_key="<?php echo esc_attr( $key ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>"><i class="far fa-trash-alt"></i></a>
														<?php } ?>

													</div><div class="um-admin-clear"></div>

													<?php if ( $type == 'group' ) { ?>
														<div class="um-admin-drag-group">

														</div>
													<?php } ?>

												</div>

												<?php

											} // end foreach

										} // end if

										?>

									</div>

									<div class="um-admin-drag-col-dynamic"></div>

									<div class="um-admin-clear"></div>

								</div>

							<?php } ?>

						</div>

					</div>

					<?php

				} // rows loop

			} // if fields exist

		}


		/**
		 *
		 */
		function modal_header() {
			?>

			<div class="um-admin-error-block"></div>
			<div class="um-admin-success-block"></div>

			<?php

			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_field_modal_header
			 * @description Modal Window Header
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_field_modal_header', 'function_name', 10 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_field_modal_header', 'my_admin_field_modal_header', 10 );
			 * function my_admin_field_modal_header() {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_field_modal_header' );
		}


		/**
		 * Modal Footer loading
		 *
		 * @param $arg2
		 * @param $args
		 * @param $metabox
		 */
		function modal_footer( $arg2, $args, $metabox ) {
			/**
			 * UM hook
			 *
			 * @type action
			 * @title um_admin_field_modal_footer
			 * @description Modal Window Footer
			 * @input_vars
			 * [{"var":"$arg2","type":"string","desc":"Ajax Action"},
			 * {"var":"$args","type":"array","desc":"Modal window arguments"},
			 * {"var":"$in_edit","type":"bool","desc":"Is edit mode?"},
			 * {"var":"$edit_array","type":"array","desc":"Edit Array"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_action( 'um_admin_field_modal_footer', 'function_name', 10, 4 );
			 * @example
			 * <?php
			 * add_action( 'um_admin_field_modal_footer', 'my_admin_field_modal_footer', 10, 4 );
			 * function my_admin_field_modal_footer( $arg2, $args, $in_edit, $edit_array ) {
			 *     // your code here
			 * }
			 * ?>
			 */
			do_action( 'um_admin_field_modal_footer', $arg2, $args, $metabox->in_edit, ( isset( $metabox->edit_array ) ) ? $metabox->edit_array : '' );
		}


		/**
		 * Skip field validation for:
		 *  - '_options' if Choices Callback specified
		 *
		 * @param boolean $skip
		 * @param string $post_input
		 * @param array $array
		 * @return boolean
		 */
		public function skip_field_validation( $skip, $post_input, $array ) {
			if ( $post_input === '_options' && isset( $array['post']['_custom_dropdown_options_source'] ) ) {
				$skip = function_exists( $array['post']['_custom_dropdown_options_source'] );
			}

			return $skip;
		}





		/**
		 * @deprecated 3.0
		 */
		function update_builder() {
			_deprecated_function( __METHOD__, '3.0', 'UM()->ajax()->builder()->update_builder()' );
			UM()->ajax()->builder()->update_builder();
		}


		/**
		 * @deprecated 3.0
		 */
		function update_field() {
			_deprecated_function( __METHOD__, '3.0', 'UM()->ajax()->builder()->update_field()' );
			UM()->ajax()->builder()->update_field();
		}

	}
}
