<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_Builder' ) ) {


	/**
	 * Class Admin_Builder
	 * @package um\admin\core
	 */
	class Admin_Builder {

		/**
		 * @var int
		 */
		public $form_id;

		/**
		 * @var array
		 */
		public $global_fields = array();

		/**
		 * Admin_Builder constructor.
		 */
		public function __construct() {
			add_action( 'um_admin_field_modal_header', array( &$this, 'add_message_handlers' ) );
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
		 * Put status handler in modal
		 */
		function add_message_handlers() {
			?>
			<div class="um-admin-error-block"></div>
			<div class="um-admin-success-block"></div>
			<?php
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
			$metabox = UM()->metabox();

			if ( isset( $field_args['conditional_support'] ) && $field_args['conditional_support'] == 0 ) {
				return;
			} ?>

			<div class="um-admin-btn-toggle">

				<?php if ( $in_edit ) { $metabox->in_edit = true;  $metabox->edit_array = $edit_array; ?>
					<a href="javascript:void(0);"><i class="um-icon-plus"></i><?php _e( 'Manage conditional fields support' ); ?></a> <?php UM()->tooltip( __( 'Here you can setup conditional logic to show/hide this field based on specific fields value or conditions', 'ultimate-member' ) ); ?>
				<?php } else { ?>
					<a href="javascript:void(0);"><i class="um-icon-plus"></i><?php _e( 'Add conditional fields support' ); ?></a> <?php UM()->tooltip( __( 'Here you can setup conditional logic to show/hide this field based on specific fields value or conditions', 'ultimate-member' ) ); ?>
				<?php } ?>

				<div class="um-admin-btn-content">
					<div class="um-admin-cur-condition-template">

						<?php $metabox->field_input( '_conditional_action', $form_id ); ?>
						<?php $metabox->field_input( '_conditional_field', $form_id ); ?>
						<?php $metabox->field_input( '_conditional_operator', $form_id ); ?>
						<?php $metabox->field_input( '_conditional_value', $form_id ); ?>

						<p><a href="javascript:void(0);" class="um-admin-remove-condition button um-admin-tipsy-n" title="Remove condition"><i class="um-icon-close" style="margin-right:0!important"></i></a></p>

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

								<p><a href="#" class="um-admin-remove-condition button um-admin-tipsy-n" title="Remove condition"><i class="um-icon-close" style="margin-right:0!important"></i></a></p>

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

							<p><a href="#" class="um-admin-remove-condition button um-admin-tipsy-n" title="Remove condition"><i class="um-icon-close" style="margin-right:0!important"></i></a></p>

							<div class="um-admin-clear"></div>
						</div>

					<?php } ?>
				</div>
			</div>

			<?php
		}


		/**
		 * Update the builder area
		 */
		function update_builder() {
			UM()->admin()->check_ajax_nonce();

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			ob_start();

			$this->form_id = absint( $_POST['form_id'] );

			$this->show_builder();

			$output = ob_get_clean();

			if ( is_array( $output ) ) {
				print_r( $output );
			} else {
				echo $output;
			}
			die;
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

			if ( !isset( $fields ) || empty( $fields ) ) { ?>

				<div class="um-admin-drag-row">

					<!-- Master Row Actions -->
					<div class="um-admin-drag-row-icons">
						<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="um-icon-plus"></i></a>
						<a href="javascript:void(0);" class="um-admin-drag-row-edit um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member' ); ?>" data-modal="UM_edit_row" data-modal-size="normal" data-dynamic-content="um_admin_edit_field_popup" data-arg1="row" data-arg2="<?php echo esc_attr( $this->form_id ); ?>" data-arg3="_um_row_1"><i class="um-faicon-pencil"></i></a>
						<span class="um-admin-drag-row-start"><i class="um-icon-arrow-move"></i></span>
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
								<span class="um-admin-drag-rowsub-start"><i class="um-icon-arrow-move"></i></span>
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

				if ( empty( $fields ) || ! is_array( $fields ) ) {
					$this->global_fields = array();
				} else {
					$this->global_fields = $fields;
				}

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
							<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="um-icon-plus"></i></a>
							<a href="javascript:void(0);" class="um-admin-drag-row-edit um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member'); ?>" data-modal="UM_edit_row" data-modal-size="normal" data-dynamic-content="um_admin_edit_field_popup" data-arg1="row" data-arg2="<?php echo esc_attr( $this->form_id ); ?>" data-arg3="<?php echo esc_attr( $row_id ); ?>"><i class="um-faicon-pencil"></i></a>
							<span class="um-admin-drag-row-start"><i class="um-icon-arrow-move"></i></span>
							<?php if ( $row_id != '_um_row_1' ) {?>
								<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-row"><i class="um-faicon-trash-o"></i></a>
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
										<span class="um-admin-drag-rowsub-start"><i class="um-icon-arrow-move"></i></span>
										<?php if ( $c > 0 ) { ?><a href="javascript:void(0);" class="um-admin-tipsy-n" title="Delete Row" data-remove_element="um-admin-drag-rowsub"><i class="um-faicon-trash-o"></i></a><?php } ?>
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
														<?php if ( $type == 'group' ) { ?>
															<i class="um-icon-plus"></i>
														<?php } else if ( isset($keyarray['icon']) && !empty( $keyarray['icon'] ) ) { ?>
															<i class="<?php echo $keyarray['icon']; ?>"></i>
														<?php } ?><?php echo ! empty( $keyarray['title'] ) ? $keyarray['title'] : __( '(no title)', 'ultimate-member' ); ?></div>
													<?php $field_name = isset( UM()->builtin()->core_fields[$type]['name'] ) ? UM()->builtin()->core_fields[$type]['name'] : ''; ?>
													<div class="um-admin-drag-fld-type um-field-type-<?php echo $type; ?>"><?php echo $field_name; ?></div>
													<div class="um-admin-drag-fld-icons um-field-type-<?php echo $type; ?>">

														<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit', 'ultimate-member' ) ?>" data-modal="UM_edit_field" data-modal-size="normal" data-dynamic-content="um_admin_edit_field_popup" data-arg1="<?php echo $type; ?>" data-arg2="<?php echo $this->form_id; ?>" data-arg3="<?php echo $key; ?>"><i class="um-faicon-pencil"></i></a>

														<a href="javascript:void(0);" class="um-admin-tipsy-n um_admin_duplicate_field" title="<?php esc_attr_e( 'Duplicate', 'ultimate-member' ) ?>" data-silent_action="um_admin_duplicate_field" data-arg1="<?php echo $key; ?>" data-arg2="<?php echo $this->form_id; ?>"><i class="um-faicon-files-o"></i></a>

														<?php if ( $type == 'group' ) { ?>

															<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Group', 'ultimate-member' ) ?>" data-remove_element="um-admin-drag-fld.um-field-type-group" data-silent_action="um_admin_remove_field" data-arg1="<?php echo $key; ?>" data-arg2="<?php echo $this->form_id; ?>"><i class="um-faicon-trash-o"></i></a>
														<?php } else { ?>

															<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete', 'ultimate-member' ) ?>" data-silent_action="um_admin_remove_field" data-arg1="<?php echo $key; ?>" data-arg2="<?php echo $this->form_id; ?>"><i class="um-faicon-trash-o"></i></a>

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
		function update_field() {
			UM()->admin()->check_ajax_nonce();

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			$output['error'] = null;

			$array = array(
				'field_type' => sanitize_key( $_POST['_type'] ),
				'form_id'    => absint( $_POST['post_id'] ),
				'args'       => UM()->builtin()->get_core_field_attrs( sanitize_key( $_POST['_type'] ) ),
				'post'       => UM()->admin()->sanitize_builder_field_meta( $_POST ),
			);

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_pre_save_fields_hook
			 * @description Filter field data before save
			 * @input_vars
			 * [{"var":"$array","type":"array","desc":"Save Field data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_pre_save_fields_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_pre_save_fields_hook', 'my_admin_pre_save_fields', 10, 1 );
			 * function my_admin_pre_save_fields( $array ) {
			 *     // your code here
			 *     return $array;
			 * }
			 * ?>
			 */
			$array = apply_filters( 'um_admin_pre_save_fields_hook', $array );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_field_update_error_handling
			 * @description Change error string on save field
			 * @input_vars
			 * [{"var":"$error","type":"string","desc":"Error String"},
			 * {"var":"$array","type":"array","desc":"Save Field data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_field_update_error_handling', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_field_update_error_handling', 'my_admin_field_update_error', 10, 2 );
			 * function my_admin_field_update_error( $error, $array ) {
			 *     // your code here
			 *     return $error;
			 * }
			 * ?>
			 */
			$output['error'] = apply_filters( 'um_admin_field_update_error_handling', $output['error'], $array );

			/**
			 * @var $_metakey
			 * @var $post_id
			 */
			extract( $array['post'] );

			if ( empty( $output['error'] ) ) {

				$save = array();
				$save[ $_metakey ] = null;
				foreach ( $array['post'] as $key => $val ) {

					if ( substr( $key, 0, 1 ) === '_' && $val !== '' ) { // field attribute
						$new_key = ltrim ( $key, '_' );

						if ( $new_key == 'options' ) {
							//$save[ $_metakey ][$new_key] = explode(PHP_EOL, $val);
							$save[ $_metakey ][ $new_key ] = preg_split( '/[\r\n]+/', wp_unslash( $val ), -1, PREG_SPLIT_NO_EMPTY );
						} else {
							$save[ $_metakey ][ $new_key ] = $val;
						}

					} elseif ( strstr( $key, 'um_editor' ) ) {

						if ( 'block' === $array['post']['_type'] ) {
							$save[ $_metakey ]['content'] = wp_kses_post( $val );
						} else {
							$save[ $_metakey ]['content'] = sanitize_textarea_field( $val );
						}
					}

				}

				$field_ID = $_metakey;
				$field_args = $save[ $_metakey ];

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_admin_pre_save_field_to_form
				 * @description Change field options before save to form
				 * @input_vars
				 * [{"var":"$field_args","type":"array","desc":"Field Options"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_admin_pre_save_field_to_form', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_admin_pre_save_field_to_form', 'my_admin_pre_save_field_to_form', 10, 1 );
				 * function my_admin_pre_save_field_to_form( $field_args ) {
				 *     // your code here
				 *     return $field_args;
				 * }
				 * ?>
				 */
				$field_args = apply_filters( 'um_admin_pre_save_field_to_form', $field_args );

				UM()->fields()->update_field( $field_ID, $field_args, $post_id );

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_admin_pre_save_field_to_db
				 * @description Change field options before save to DB
				 * @input_vars
				 * [{"var":"$field_args","type":"array","desc":"Field Options"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_admin_pre_save_field_to_db', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_admin_pre_save_field_to_db', 'my_admin_pre_save_field_to_db', 10, 1 );
				 * function my_admin_pre_save_field_to_form( $field_args ) {
				 *     // your code here
				 *     return $field_args;
				 * }
				 * ?>
				 */
				$field_args = apply_filters( 'um_admin_pre_save_field_to_db', $field_args );

				if ( ! isset( $array['args']['form_only'] ) ) {
					if ( ! isset( UM()->builtin()->predefined_fields[ $field_ID ] ) ) {
						UM()->fields()->globally_update_field( $field_ID, $field_args );
					}
				}

			}

			$output = json_encode( $output );
			if ( is_array( $output ) ) {
				print_r( $output );
			} else {
				echo $output;
			}
			die;
		}


		/**
		 *
		 */
		function dynamic_modal_content() {
			UM()->admin()->check_ajax_nonce();

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			$metabox = UM()->metabox();

			/**
			 * @var $act_id
			 * @var $arg1
			 * @var $arg2
			 * @var $arg3
			 */
			extract( $_POST );

			if ( isset( $arg1 ) ) {
				$arg1 = sanitize_text_field( $arg1 );
			}

			if ( isset( $arg2 ) ) {
				$arg2 = sanitize_text_field( $arg2 );
			}

			if ( isset( $arg3 ) ) {
				$arg3 = sanitize_text_field( $arg3 );
			}

			switch ( sanitize_key( $act_id ) ) {

				default:

					ob_start();

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_ajax_modal_content__hook
					 * @description Integration hook on ajax popup admin builder modal content
					 * @input_vars
					 * [{"var":"$act_id","type":"string","desc":"Ajax Action"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_ajax_modal_content__hook', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_ajax_modal_content__hook', 'my_admin_custom_hook', 10, 1 );
					 * function um_admin_ajax_modal_content__hook( $act_id ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_admin_ajax_modal_content__hook', sanitize_key( $act_id ) );
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_ajax_modal_content__hook_{$act_id}
					 * @description Integration hook on ajax popup admin builder modal content
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_ajax_modal_content__hook_{$act_id}', 'function_name', 10 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_ajax_modal_content__hook_{$act_id}', 'my_admin_ajax_modal_content', 10 );
					 * function my_admin_ajax_modal_content() {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_admin_ajax_modal_content__hook_" . sanitize_key( $act_id ) );

					$output = ob_get_clean();
					break;

				case 'um_admin_fonticon_selector':

					ob_start(); ?>

					<div class="um-admin-metabox">
						<p class="_icon_search"><input type="text" name="_icon_search" id="_icon_search" value="" placeholder="<?php esc_attr_e('Search Icons...', 'ultimate-member' ); ?>" /></p>
					</div>

					<div class="um-admin-icons">
						<?php foreach( UM()->fonticons()->all as $icon ) { ?>
							<span data-code="<?php echo esc_attr( $icon ); ?>" title="<?php echo esc_attr( $icon ); ?>" class="um-admin-tipsy-n"><i class="<?php echo $icon; ?>"></i></span>
						<?php } ?>
					</div><div class="um-admin-clear"></div>

					<?php $output = ob_get_clean();
					break;

				case 'um_admin_show_fields':

					ob_start();
					$form_fields = UM()->query()->get_attr( 'custom_fields', $arg2 );
					$form_fields = array_values( array_filter( array_keys( $form_fields ) ) );
					//$form_fields = array_keys( $form_fields );
					?>

					<h4><?php _e('Setup New Field','ultimate-member'); ?></h4>
					<div class="um-admin-btns">

						<?php if ( UM()->builtin()->core_fields ) {
							foreach ( UM()->builtin()->core_fields as $field_type => $array ) {

								if ( isset( $array['in_fields'] ) && $array['in_fields'] == false ) {
									continue;
								} ?>

								<a href="javascript:void(0);" class="button" data-modal="UM_add_field" data-modal-size="normal" data-dynamic-content="um_admin_new_field_popup" data-arg1="<?php echo esc_attr( $field_type ); ?>" data-arg2="<?php echo esc_attr( $arg2 ) ?>"><?php echo esc_html( $array['name'] ); ?></a>

							<?php }
						} ?>

					</div>

					<h4><?php _e('Predefined Fields','ultimate-member'); ?></h4>
					<div class="um-admin-btns">

						<?php if ( UM()->builtin()->predefined_fields ) {
							foreach ( UM()->builtin()->predefined_fields as $field_key => $array ) {
								if ( ! isset( $array['account_only'] ) && ! isset( $array['private_use'] ) ) { ?>

									<a href="javascript:void(0);" class="button" <?php disabled( in_array( $field_key, $form_fields, true ) ) ?> data-silent_action="um_admin_add_field_from_predefined" data-arg1="<?php echo esc_attr( $field_key ); ?>" data-arg2="<?php echo esc_attr( $arg2 ); ?>"><?php echo um_trim_string( stripslashes( $array['title'] ), 20 ); ?></a>

								<?php }
							}
						} else {
							echo '<p>' . __( 'None', 'ultimate-member' ) . '</p>';
						} ?>

					</div>

					<h4><?php _e( 'Custom Fields', 'ultimate-member' ); ?></h4>
					<div class="um-admin-btns">

						<?php
						if ( UM()->builtin()->custom_fields ) {
							foreach ( UM()->builtin()->custom_fields as $field_key => $array ) {
								if ( empty( $array['title'] ) || empty( $array['type'] ) ) {
									continue;
								} ?>

								<a href="javascript:void(0);" class="button with-icon" <?php disabled( in_array( $field_key, $form_fields, true ) ) ?> data-silent_action="um_admin_add_field_from_list" data-arg1="<?php echo esc_attr( $field_key ); ?>" data-arg2="<?php echo esc_attr( $arg2 ); ?>" title="<?php echo __( 'Meta Key', 'ultimate-member' ) . ' - ' . esc_attr( $field_key ); ?>"><?php echo um_trim_string( stripslashes( $array['title'] ), 20 ); ?> <small>(<?php echo ucfirst( $array['type'] ); ?>)</small><span class="remove"></span></a>

							<?php }
						} else {
							echo '<p>' . __( 'You did not create any custom fields', 'ultimate-member' ) . '</p>';
						} ?>

					</div>

					<?php $output = ob_get_clean();
					break;

				case 'um_admin_edit_field_popup':

					ob_start();

					$args = UM()->builtin()->get_core_field_attrs( $arg1 );

					$form_fields = UM()->query()->get_attr( 'custom_fields', $arg2 );

					$metabox->set_field_type = $arg1;
					$metabox->in_edit = true;
					$metabox->edit_array = $form_fields[ $arg3 ];

					if ( !isset( $metabox->edit_array['metakey'] ) ){
						$metabox->edit_array['metakey'] = $metabox->edit_array['id'];
					}

					if ( !isset( $metabox->edit_array['position'] ) ){
						$metabox->edit_array['position'] = $metabox->edit_array['id'];
					}

					extract( $args );

					if ( ! isset( $col1 ) ) {

						echo '<p>'. __( 'This field type is not setup correcty.', 'ultimate-member' ) . '</p>';

					} else {

						?>

						<?php if ( isset( $metabox->edit_array['in_group'] ) ) { ?>
							<input type="hidden" name="_in_row" id="_in_row" value="<?php echo $metabox->edit_array['in_row']; ?>" />
							<input type="hidden" name="_in_sub_row" id="_in_sub_row" value="<?php echo $metabox->edit_array['in_sub_row']; ?>" />
							<input type="hidden" name="_in_column" id="_in_column" value="<?php echo $metabox->edit_array['in_column']; ?>" />
							<input type="hidden" name="_in_group" id="_in_group" value="<?php echo $metabox->edit_array['in_group']; ?>" />
						<?php } ?>

						<input type="hidden" name="_type" id="_type" value="<?php echo $arg1; ?>" />

						<input type="hidden" name="post_id" id="post_id" value="<?php echo $arg2; ?>" />

						<input type="hidden" name="edit_mode" id="edit_mode" value="true" />

						<input type="hidden" name="_metakey" id="_metakey" value="<?php echo $metabox->edit_array['metakey']; ?>" />

						<input type="hidden" name="_position" id="_position" value="<?php echo $metabox->edit_array['position']; ?>" />

						<?php if ( isset( $args['mce_content'] ) ) { ?>
							<div class="dynamic-mce-content"><?php echo ! empty( $metabox->edit_array['content'] ) ? $metabox->edit_array['content'] : ''; ?></div>
						<?php } ?>

						<?php $this->modal_header(); ?>

						<div class="um-admin-half">

							<?php if ( isset( $col1 ) ) {  foreach( $col1 as $opt ) $metabox->field_input ( $opt, $arg2, $metabox->edit_array ); } ?>

						</div>

						<div class="um-admin-half um-admin-right">

							<?php if ( isset( $col2 ) ) {  foreach( $col2 as $opt ) $metabox->field_input ( $opt, $arg2, $metabox->edit_array ); } ?>

						</div><div class="um-admin-clear"></div>

						<?php if ( isset( $col3 ) ) { foreach( $col3 as $opt ) $metabox->field_input ( $opt, $arg2, $metabox->edit_array ); } ?>

						<div class="um-admin-clear"></div>

						<?php if ( isset( $col_full ) ) {foreach( $col_full as $opt ) $metabox->field_input ( $opt, $arg2, $metabox->edit_array ); } ?>

						<?php $this->modal_footer( $arg2, $args, $metabox ); ?>

						<?php

					}

					$output = ob_get_clean();
					break;

				case 'um_admin_new_field_popup':

					ob_start();

					$args = UM()->builtin()->get_core_field_attrs( $arg1 );

					$metabox->set_field_type = $arg1;

					/**
					 * @var $in_row
					 * @var $in_sub_row
					 * @var $in_column
					 * @var $in_group
					 */
					extract( $args );

					if ( ! isset( $col1 ) ) {

						echo '<p>'. __( 'This field type is not setup correcty.', 'ultimate-member' ) . '</p>';

					} else {

						if ( $in_column ) { ?>
							<input type="hidden" name="_in_row" id="_in_row" value="_um_row_<?php echo $in_row + 1; ?>" />
							<input type="hidden" name="_in_sub_row" id="_in_sub_row" value="<?php echo $in_sub_row; ?>" />
							<input type="hidden" name="_in_column" id="_in_column" value="<?php echo $in_column; ?>" />
							<input type="hidden" name="_in_group" id="_in_group" value="<?php echo $in_group; ?>" />
						<?php } ?>

						<input type="hidden" name="_type" id="_type" value="<?php echo $arg1; ?>" />

						<input type="hidden" name="post_id" id="post_id" value="<?php echo $arg2; ?>" />

						<?php $this->modal_header(); ?>

						<div class="um-admin-half">

							<?php if ( isset( $col1 ) ) { foreach( $col1 as $opt ) $metabox->field_input ( $opt ); } ?>

						</div>

						<div class="um-admin-half um-admin-right">

							<?php if ( isset( $col2 ) ) { foreach( $col2 as $opt ) $metabox->field_input ( $opt ); } ?>

						</div><div class="um-admin-clear"></div>

						<?php if ( isset( $col3 ) ) { foreach( $col3 as $opt ) $metabox->field_input ( $opt ); } ?>

						<div class="um-admin-clear"></div>

						<?php if ( isset( $col_full ) ) { foreach( $col_full as $opt ) $metabox->field_input ( $opt ); } ?>

						<?php $this->modal_footer( $arg2, $args, $metabox ); ?>

						<?php

					}

					$output = ob_get_clean();
					break;

				case 'um_admin_preview_form':

					UM()->user()->preview = true;

					$mode = UM()->query()->get_attr('mode', $arg1 );

					if ( $mode == 'profile' ) {
						UM()->fields()->editing = true;
					}

					$output = '<div class="um-admin-preview-overlay"></div>';

					if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
						$output .= do_shortcode('[ultimatemember form_id="' . $arg1 . '" /]');
					} else {
						$output .= apply_shortcodes('[ultimatemember form_id="' . $arg1 . '" /]');
					}

					break;

				case 'um_admin_review_registration':
					//$user_id = $arg1;

					if ( ! current_user_can( 'administrator' ) ) {
						if ( ! um_can_view_profile( $arg1 ) ) {
							$output = '';
							break;
						}
					}

					um_fetch_user( $arg1 );

					UM()->user()->preview = true;

					$output = um_user_submitted_registration_formatted( true );

					um_reset_user();

					break;

			}

			if ( is_array( $output ) ) {
				print_r( $output );
			} else {
				echo $output;
			}
			die;
		}


		/**
		 *
		 */
		function modal_header() {
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
				$skip = function_exists( wp_unslash( $array['post']['_custom_dropdown_options_source'] ) );
			}

			return $skip;
		}


		/**
		 *  Retrieves dropdown/multi-select options from a callback function
		 */
		function populate_dropdown_options() {
			UM()->admin()->check_ajax_nonce();

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
			}

			$arr_options = array();

			// we can not use `sanitize_key()` because it removes backslash needed for namespace and uppercase symbols
			$um_callback_func = sanitize_text_field( $_POST['um_option_callback'] );
			// removed added by sanitize slashes for the namespaces
			$um_callback_func = wp_unslash( $um_callback_func );

			if ( empty( $um_callback_func ) ) {
				$arr_options['status'] = 'empty';
				$arr_options['function_name'] = $um_callback_func;
				$arr_options['function_exists'] = function_exists( $um_callback_func );
			}

			if ( UM()->fields()->is_source_blacklisted( $um_callback_func ) ) {
				wp_send_json_error( __( 'This is not possible for security reasons. Don\'t use internal PHP functions.', 'ultimate-member' ) );
			}

			$arr_options['data'] = array();
			if ( function_exists( $um_callback_func ) ) {
				$arr_options['data'] = call_user_func( $um_callback_func );
			}

			wp_send_json( $arr_options );
		}

	}
}
