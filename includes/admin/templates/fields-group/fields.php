<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields          = array();
$fields_group_id = 0;
if ( isset( $_GET['tab'] ) && 'edit' === sanitize_key( $_GET['tab'] ) ) {
	$fields_group_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( ! empty( $fields_group_id ) ) {
		$rows = UM()->admin()->fields_group()->get_fields( $fields_group_id, 'row' );
		$fields = UM()->admin()->fields_group()->get_fields( $fields_group_id );
	}
}

global $wpdb;

//$wpdb->insert(
//	"{$wpdb->prefix}um_fields_meta",
//	array(
//		'field_id' => 1,
//		'meta_key' => 'row',
//		'meta_value' => 7,
//	),
//	array(
//		'%d',
//		'%s',
//		'%d',
//	)
//);
//$wpdb->insert(
//	"{$wpdb->prefix}um_fields_meta",
//	array(
//		'field_id' => 2,
//		'meta_key' => 'row',
//		'meta_value' => 7,
//	),
//	array(
//		'%d',
//		'%s',
//		'%d',
//	)
//);

//var_dump( $rows );

//var_dump( $fields );
?>

<!-- data-in_row="" data-in_sub_row="" data-in_column="" data-in_group="" data is used for builder handlers when trying to insert field in the selected place -->
<div class="um-admin-builder" data-group_id="<?php echo esc_attr( $fields_group_id ); ?>" data-in_row="" data-in_sub_row="" data-in_column="" data-in_group="">

	<div class="um-admin-clear"></div>

	<div class="um-admin-drag">

		<div class="um-admin-drag-ajax" data-group_id="<?php echo esc_attr( $fields_group_id ); ?>">
			<?php if ( empty( $fields ) ) { ?>

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

				foreach ( $rows as $row ) {
					?>
					<div class="um-fields-group-row">
						<div class="um-fields-group-row-title">
							<?php echo esc_html( $row['title'] ); ?>
							<a href="javascript:void(0);"><?php esc_html_e( 'Delete Row', 'ultimate-member' ); ?></a>
							<span><?php esc_html_e( 'Move Row', 'ultimate-member' ); ?><i class="fas fa-arrows-alt"></i></span>
						</div>
						<?php
						$row_fields = UM()->admin()->fields_group()->get_rows_fields( $fields_group_id, $row['id'] );
						if ( ! empty( $row_fields ) ) {
//							var_dump( $row_fields );
							foreach ( $row_fields as $row_field ) {
								?>
								<div class="um-fields-group-row-field">
									<i class="fas fa-arrows-alt"></i>
									<div><?php echo esc_html( $row_field['title'] ); ?></div>
									<div><?php echo esc_html( $row_field['type'] ); ?></div>
									<a href="javascript:void(0);"><?php esc_html_e( 'Edit', 'ultimate-member' ); ?></a>
									<a href="javascript:void(0);"><?php esc_html_e( 'Duplicate', 'ultimate-member' ); ?></a>
									<a href="javascript:void(0);"><?php esc_html_e( 'Delete', 'ultimate-member' ); ?></a>
								</div>
								<?php
							}
						}
						?>
						<input type="button" class="button button-primary" value="<?php esc_attr_e( 'Add new field', 'ultimate-member' ); ?>" >
					</div>
					<?php
				}

				?>
				<a href="javascript:void(0);"><?php esc_html_e( 'Add Row', 'ultimate-member' ); ?></a>
				<?php

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
			?>
		</div>

		<div class="um-admin-drag-addrow um-admin-tipsy-n"
		     title="<?php esc_attr_e( 'Add Master Row', 'ultimate-member' ); ?>"
		     data-row_action="add_row">
			<i class="fas fa-plus"></i>
		</div>

	</div>

</div>

<div class="um-col-demon-row" style="display:none;">

	<div class="um-admin-drag-row-icons">
		<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="fas fa-plus"></i></a>
		<a href="javascript:void(0);" class="um-admin-drag-row-edit um_admin_edit_field_popup um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member' ); ?>" data-arg1="row" data-arg2="<?php echo esc_attr( get_the_ID() ); ?>" data-field_type="row" data-group_id="<?php echo esc_attr( $fields_group_id ); ?>"><i class="fas fa-pencil-alt"></i></a>
		<span class="um-admin-drag-row-start"><i class="fas fa-arrows-alt"></i></span>
		<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-row"><i class="far fa-trash-alt"></i></a>
	</div>
	<div class="um-admin-clear"></div>

	<div class="um-admin-drag-rowsubs">
		<div class="um-admin-drag-rowsub">

			<div class="um-admin-drag-rowsub-icons">
				<span class="um-admin-drag-rowsub-start"><i class="fas fa-arrows-alt"></i></span>
				<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-rowsub"><i class="far fa-trash-alt"></i></a>
			</div>

			<div class="um-admin-clear"></div>

			<div class="um-admin-drag-col"></div>

			<div class="um-admin-drag-col-dynamic"></div>

			<div class="um-admin-clear"></div>

		</div>
	</div>

</div>

<div class="um-col-demon-subrow" style="display:none;">

	<div class="um-admin-drag-rowsub-icons">
		<span class="um-admin-drag-rowsub-start"><i class="fas fa-arrows-alt"></i></span>
		<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-rowsub"><i class="far fa-trash-alt"></i></a>
	</div>

	<div class="um-admin-clear"></div>

	<div class="um-admin-drag-col"></div>

	<div class="um-admin-drag-col-dynamic"></div>

	<div class="um-admin-clear"></div>

</div>
