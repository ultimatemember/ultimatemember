<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\Fields_Group' ) ) {

	/**
	 * Class Fields_Group
	 *
	 * @package um\admin
	 */
	class Fields_Group {

		/**
		 * @var
		 */
		public $form_id;

		/**
		 * @var array
		 */
		private $global_fields = array();

		/**
		 * Fields_Group constructor.
		 */
		public function __construct() {
		}

		/**
		 *
		 */
		public function hooks() {
			add_filter( 'um_admin_render_checkbox_field_html', array( &$this, 'add_reset_rules_button' ), 10, 2 );
		}

		public function add_reset_rules_button( $html, $field_data ) {
			if ( array_key_exists( 'id', $field_data ) && 'conditional_logic' === $field_data['id'] ) {
				$visibility = '';
				if ( empty( $field_data['value'] ) ) {
					$visibility = ' style="visibility:hidden;"';
				}
				$html = '<div style="display: flex;flex-direction: row;justify-content: space-between; align-items: center;flex-wrap: nowrap;">' . $html .'<input type="button" class="button um-fields-groups-field-reset-all-conditions" value="' . __( 'Reset all rules', 'ultimate-member' ) . '"' . $visibility . '/></div>';
			}
			return $html;
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
					FROM {$wpdb->prefix}um_fields_groups 
					WHERE id = %d
					LIMIT 1",
					$group_id
				),
				ARRAY_A
			);

			return $group_data;
		}

		/**
		 * @param int    $group_id
		 * @param string $type
		 *
		 * @return array
		 */
		public function get_fields( $group_id, $type = 'all' ) {
			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT * 
				FROM {$wpdb->prefix}um_fields 
				WHERE group_id = %d",
				$group_id
			);

			if ( 'fields_only' === $type ) {
				$query = $wpdb->prepare(
					"SELECT * 
					FROM {$wpdb->prefix}um_fields 
					WHERE group_id = %d AND 
					      type != 'row'",
					$group_id
				);
			} elseif ( 'row' === $type ) {
				$query = $wpdb->prepare(
					"SELECT * 
					FROM {$wpdb->prefix}um_fields 
					WHERE group_id = %d AND 
					      type = 'row'",
					$group_id
				);
			}

			$fields = $wpdb->get_results( $query, ARRAY_A );
			return $fields;
		}

		/**
		 * Create fields group. With basic row
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
				"{$wpdb->prefix}um_fields_group",
				array(
					'group_key'   => md5( 'group' . $data['title'] . time() ),
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

			$fields_group_id = $wpdb->insert_id;

			if ( ! empty( $fields_group_id ) ) {
				// if fields in data array aren't exist then set basic row only
				if ( ! array_key_exists( 'fields', $data ) ) {
					$wpdb->insert(
						"{$wpdb->prefix}um_fields",
						array(
							'field_key'   => md5( 'field' . __( 'Row', 'ultimate-member' ) . time() ),
							'group_id'    => $fields_group_id,
							'title'       => __( 'Row', 'ultimate-member' ),
							'description' => __( 'Basic Row', 'ultimate-member' ),
						),
						array(
							'%s',
							'%d',
							'%s',
							'%s',
						)
					);
				}
			}

			return $fields_group_id;
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

			$types_map = array(
				'text' => __( 'Text', 'ultimate-member' ),
			);

			if ( ! array_key_exists( $field['type'], $types_map ) ) {
				return $field['type'];
			}

			return $types_map[ $field['type'] ];
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
					sanitize_key( $meta_key )
				),
				ARRAY_A
			);

			if ( empty( $meta_value ) ) {
				$meta_value = $default;
			} else {
				$meta_value = maybe_unserialize( $meta_value );
			}

			return $meta_value;
		}

		/**
		 * Display the builder
		 */
		public function show_builder() {
			$fields = get_post_meta( $this->form_id, '_um_custom_fields', true );
			$fields = ( ! empty( $fields ) && is_array( $fields ) ) ? $fields : array();

			if ( empty( $fields ) ) {
				?>

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
				$this->global_fields = $fields;

				foreach ( $this->global_fields as $key => $array ) {
					if ( 'row' === $array['type'] ) {
						$rows[ $key ] = $array;
						unset( $this->global_fields[ $key ] ); // not needed now
					}
				}

				if ( ! isset( $rows ) ) {
					$rows = array(
						'_um_row_1' => array(
							'type'     => 'row',
							'id'       => '_um_row_1',
							'sub_rows' => 1,
							'cols'     => 1,
						),
					);
				}

				foreach ( $rows as $row_id => $array ) {
					?>

					<div class="um-admin-drag-row" data-original="<?php echo esc_attr( $row_id ); ?>">
						<!-- Master Row Actions -->
						<div class="um-admin-drag-row-icons">
							<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="fas fa-plus"></i></a>
							<a href="javascript:void(0);" class="um-admin-drag-row-edit um_admin_edit_field_popup um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member'); ?>" data-arg1="row" data-arg2="<?php echo esc_attr( $this->form_id ); ?>" data-arg3="<?php echo esc_attr( $row_id ); ?>" data-field_type="row" data-form_id="<?php echo esc_attr( $this->form_id ); ?>" data-field_key="<?php echo esc_attr( $row_id ); ?>"><i class="fas fa-pencil-alt"></i></a>
							<span class="um-admin-drag-row-start"><i class="fas fa-arrows-alt"></i></span>
							<?php if ( $row_id !== '_um_row_1' ) { ?>
								<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-row"><i class="far fa-trash-alt"></i></a>
							<?php } ?>
						</div>
						<div class="um-admin-clear"></div>

						<div class="um-admin-drag-rowsubs">
							<?php
							$row_fields = $this->get_fields_by_row( $row_id );
							$sub_rows   = ( isset( $array['sub_rows'] ) ) ? $array['sub_rows'] : 1;

							for ( $c = 0; $c < $sub_rows; $c++  ) {
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
											$col_num   = (int) $col_split[ $c ];
										}

										for ( $i = 1; $i <= 3; $i++ ) {
											?>
											<a href="javascript:void(0);" class="<?php if ( $col_num === $i ) { ?>active<?php } ?>" data-cols="<?php echo esc_attr( $i ) ?>"></a>
											<?php
										}
										?>
									</div>

									<!-- Sub Row Actions -->
									<div class="um-admin-drag-rowsub-icons">
										<span class="um-admin-drag-rowsub-start"><i class="fas fa-arrows-alt"></i></span>
										<?php if ( $c > 0 ) { ?>
											<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-rowsub"><i class="far fa-trash-alt"></i></a>
										<?php } ?>
									</div>
									<div class="um-admin-clear"></div>

									<!-- Columns -->
									<div class="um-admin-drag-col">
										<?php
										$subrow_fields = $this->get_fields_in_subrow( $row_fields, $c );
										if ( is_array( $subrow_fields ) ) {
											$subrow_fields = $this->array_sort_by_column( $subrow_fields, 'position' );

											foreach ( $subrow_fields as $key => $keyarray ) {
												if ( ! array_key_exists( 'type', $keyarray ) ) {
													continue;
												}

												$type       = $keyarray['type'];
												$field_name = isset( UM()->builtin()->core_fields[ $type ]['name'] ) ? UM()->builtin()->core_fields[ $type ]['name'] : '';
												?>

												<div class="um-admin-drag-fld um-admin-delete-area um-field-type-<?php echo esc_attr( $type ); ?> <?php echo esc_attr( $key ); ?>" data-group="<?php echo isset( $keyarray['in_group'] ) ? esc_attr( $keyarray['in_group'] ) : ''; ?>" data-key="<?php echo esc_attr( $key ); ?>" data-column="<?php echo isset( $keyarray['in_column'] ) ? esc_attr( $keyarray['in_column'] ) : 1; ?>">
													<div class="um-admin-drag-fld-title um-field-type-<?php echo esc_attr( $type ); ?>">
														<?php if ( 'group' === $type ) { ?>
															<i class="fas fa-plus"></i>
														<?php } ?><?php echo ! empty( $keyarray['title'] ) ? esc_html( $keyarray['title'] ) : esc_html__( '(no title)', 'ultimate-member' ); ?>
													</div>
													<div class="um-admin-drag-fld-type um-field-type-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $field_name ); ?></div>
													<div class="um-admin-drag-fld-icons um-field-type-<?php echo esc_attr( $type ); ?>">
														<a href="javascript:void(0);" class="um_admin_edit_field_popup um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit', 'ultimate-member' ) ?>" data-arg1="<?php echo esc_attr( $type ); ?>" data-arg2="<?php echo esc_attr( $this->form_id ); ?>" data-arg3="<?php echo esc_attr( $key ); ?>" data-field_type="<?php echo esc_attr( $type ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>" data-field_key="<?php echo esc_attr( $key ); ?>"><i class="fas fa-pencil-alt"></i></a>

														<a href="javascript:void(0);" class="um_admin_duplicate_field um-admin-tipsy-n" title="<?php esc_attr_e( 'Duplicate', 'ultimate-member' ) ?>" data-field_key="<?php echo esc_attr( $key ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>"><i class="far fa-copy"></i></a>

														<?php if ( 'group' === $type ) { ?>
															<a href="javascript:void(0);" class="um_admin_remove_field um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Group', 'ultimate-member' ) ?>" data-remove_element="um-admin-drag-fld.um-field-type-group" data-field_key="<?php echo esc_attr( $key ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>"><i class="far fa-trash-alt"></i></a>
														<?php } else { ?>
															<a href="javascript:void(0);" class="um_admin_remove_field um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete', 'ultimate-member' ) ?>" data-field_key="<?php echo esc_attr( $key ); ?>" data-form_id="<?php echo esc_attr( $this->form_id ); ?>"><i class="far fa-trash-alt"></i></a>
														<?php } ?>
													</div>
													<div class="um-admin-clear"></div>

													<?php if ( 'group' === $type ) { ?>
														<div class="um-admin-drag-group"></div>
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

								<?php
							}
							?>
						</div>
					</div>

					<?php
				} // rows loop
			} // if fields exist
		}
	}
}
