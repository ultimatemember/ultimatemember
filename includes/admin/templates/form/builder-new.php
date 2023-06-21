<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( UM()->builder()->form_id ) ) {
	UM()->builder()->form_id = $this->form_id;
} ?>

<div class="um-form-section-template">
	<!-- Single section here -->
	<div class="um-form-section-header um-form-section-toggle-edit">
		<div class="um-form-section-header-title-wrapper">
			<span class="um-form-section-move-link" title="<?php esc_attr_e( 'Move section', 'ultimate-member' ); ?>"></span>
			<span class="um-form-section-header-title um-form-section-toggle-edit"><?php esc_html_e( '(no title)', 'ultimate-member' ); ?></span>
		</div>
		<div class="um-form-section-header-actions um-form-section-toggle-edit">
			<a href="javascript:void(0);" class="um-form-section-action-edit"><?php esc_html_e( 'Section Settings', 'ultimate-member' ); ?></a>
			<a href="javascript:void(0);" class="um-form-section-action-delete"><?php esc_html_e( 'Delete Section', 'ultimate-member' ); ?></a>
		</div>
	</div>
	<div class="um-form-section-settings">
		<?php
		$section_settings = UM()->config()->get( 'builder_section_settings' );
		$section_tabs     = array_keys( $section_settings );
		$tab_labels       = UM()->config()->get( 'field_settings_tabs' );
		?>
		<div class="um-form-section-settings-tabs">
			<?php
			foreach ( $section_tabs as $tab_key ) {
				$classes = array();
				if ( 'general' === $tab_key ) {
					$classes[] = 'current';
				}
				?>

				<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
					<?php echo esc_html( $tab_labels[ $tab_key ] ); ?>
				</div>

				<?php
			}
			?>
		</div>
		<div class="um-form-section-settings-tabs-content">
			<?php
			foreach ( $section_settings as $tab_key => $settings_fields ) {
				$classes = array();
				if ( 'general' === $tab_key ) {
					$classes[] = 'current';
				}
				?>
				<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
					<?php
					UM()->admin()->forms(
						array(
							'class'     => 'form_fields_section' . $tab_key,
							'prefix_id' => 'form[fields][section][' . $tab_key . ']',
							'fields'    => $settings_fields,
						)
					)->render_form();
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<div class="um-form-section-content">
		<div class="um-form-rows">
			<div class="um-form-row">
				<!-- Single row here -->
				<div class="um-form-row-header um-form-row-toggle-edit">
					<div class="um-form-row-header-title-wrapper">
						<span class="um-form-row-move-link" title="<?php esc_attr_e( 'Move row', 'ultimate-member' ); ?>"></span>
						<span class="um-form-row-header-title um-form-row-toggle-edit"><?php esc_html_e( '(no title)', 'ultimate-member' ); ?></span>
					</div>
					<div class="um-form-row-header-actions um-form-row-toggle-edit">
						<a href="javascript:void(0);" class="um-form-row-action-edit"><?php esc_html_e( 'Row Settings', 'ultimate-member' ); ?></a>
						<a href="javascript:void(0);" class="um-form-row-action-delete"><?php esc_html_e( 'Delete Row', 'ultimate-member' ); ?></a>
					</div>
				</div>
				<div class="um-form-row-settings">
					<?php
					$row_settings = UM()->config()->get( 'builder_row_settings' );
					$row_tabs     = array_keys( $row_settings );
					$tab_labels   = UM()->config()->get( 'field_settings_tabs' );
					?>
					<div class="um-form-row-settings-tabs">
						<?php
						foreach ( $row_tabs as $tab_key ) {
							$classes = array();
							if ( 'general' === $tab_key ) {
								// General tab is selected by default for the new field.
								$classes[] = 'current';
							}
							?>

							<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
								<?php echo esc_html( $tab_labels[ $tab_key ] ); ?>
							</div>

							<?php
						}
						?>
					</div>
					<div class="um-form-row-settings-tabs-content">
						<?php
						foreach ( $row_settings as $tab_key => $settings_fields ) {
							$classes = array();
							if ( 'general' === $tab_key ) {
								// General tab is selected by default for the new field.
								$classes[] = 'current';
							}
							?>
							<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
								<?php
								UM()->admin()->forms(
									array(
										'class'     => 'form_fields_row' . $tab_key,
										'prefix_id' => 'form[fields][row][' . $tab_key . ']',
										'fields'    => $settings_fields,
									)
								)->render_form();
								?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<div class="um-form-row-content">
					<div class="um-form-row-columns">
						<div class="um-form-row-column">
							<!-- Single column here -->
							<div class="um-fields-column-header<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>">
								<div class="um-fields-column-header-order"><?php esc_html_e( '#', 'ultimate-member' ); ?></div>
								<div class="um-fields-column-header-name"><?php esc_html_e( 'Name', 'ultimate-member' ); ?></div>
								<div class="um-fields-column-header-metakey"><?php esc_html_e( 'Metakey', 'ultimate-member' ); ?></div>
								<div class="um-fields-column-header-type"><?php esc_html_e( 'Type', 'ultimate-member' ); ?></div>
								<div class="um-fields-column-header-actions">&nbsp;</div>
							</div>
							<div class="um-fields-column-content<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>" data-uniqid="<?php echo esc_attr( uniqid() ); ?>">
								<?php
								if ( ! empty( $fields ) ) {
									$i = 1;
									foreach ( $fields as $k => $field ) {
										// text-type field is the default field type for the builder
										$field_settings_tabs     = UM()->admin()->field_group()->get_field_tabs( $field['type'] );
										$field_settings_settings = UM()->admin()->field_group()->get_field_settings( $field['type'], $field['id'] );

										$row_key = ! empty( $field['id'] ) ? $field['id'] : $k;

										$type     = UM()->admin()->field_group()->get_field_type( $field );
										$meta_key = UM()->admin()->field_group()->get_field_metakey( $field );
										$meta_key = ( empty( $meta_key ) && ! empty( $field['meta_key'] ) ) ? $field['meta_key'] : $meta_key;
										?>
										<div class="um-field-row" data-field="<?php echo esc_attr( $row_key ); ?>">
											<input type="hidden" class="um-field-row-id" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" />
											<input type="hidden" class="um-field-row-parent-id" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][parent_id]" value="<?php echo esc_attr( $field['parent_id'] ); ?>" />
											<input type="hidden" class="um-field-row-order" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][order]" value="<?php echo esc_attr( $i ); ?>" />
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
							<div class="um-fields-column-footer">
								<input type="button" class="um-add-field-group-to-column button button-primary" value="<?php esc_attr_e( 'Add field group', 'ultimate-member' ); ?>" />
								<input type="button" class="um-add-field-to-column button" value="<?php esc_attr_e( 'Add individual field', 'ultimate-member' ); ?>" />
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="um-fields-footer">
			<a href="javascript:void(0);" class="um-add-field-row"><?php esc_html_e( 'Add row', 'ultimate-member' ); ?></a>
		</div>
	</div>
</div>

<div class="um-form-row-template">
	<!-- Single row here -->
	<div class="um-form-row-header um-form-row-toggle-edit">
		<div class="um-form-row-header-title-wrapper">
			<span class="um-form-row-move-link" title="<?php esc_attr_e( 'Move row', 'ultimate-member' ); ?>"></span>
			<span class="um-form-row-header-title um-form-row-toggle-edit"><?php esc_html_e( '(no title)', 'ultimate-member' ); ?></span>
		</div>
		<div class="um-form-row-header-actions um-form-row-toggle-edit">
			<a href="javascript:void(0);" class="um-form-row-action-edit"><?php esc_html_e( 'Row Settings', 'ultimate-member' ); ?></a>
			<a href="javascript:void(0);" class="um-form-row-action-delete"><?php esc_html_e( 'Delete Row', 'ultimate-member' ); ?></a>
		</div>
	</div>
	<div class="um-form-row-settings">
		<?php
		$row_settings = UM()->config()->get( 'builder_row_settings' );
		$row_tabs     = array_keys( $row_settings );
		$tab_labels   = UM()->config()->get( 'field_settings_tabs' );
		?>
		<div class="um-form-row-settings-tabs">
			<?php
			foreach ( $row_tabs as $tab_key ) {
				$classes = array();
				if ( 'general' === $tab_key ) {
					// General tab is selected by default for the new field.
					$classes[] = 'current';
				}
				?>

				<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
					<?php echo esc_html( $tab_labels[ $tab_key ] ); ?>
				</div>

				<?php
			}
			?>
		</div>
		<div class="um-form-row-settings-tabs-content">
			<?php
			foreach ( $row_settings as $tab_key => $settings_fields ) {
				$classes = array();
				if ( 'general' === $tab_key ) {
					// General tab is selected by default for the new field.
					$classes[] = 'current';
				}
				?>
				<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
					<?php
					UM()->admin()->forms(
						array(
							'class'     => 'form_fields_row' . $tab_key,
							'prefix_id' => 'form[fields][row][' . $tab_key . ']',
							'fields'    => $settings_fields,
						)
					)->render_form();
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<div class="um-form-row-content">
		<div class="um-form-row-columns">
			<div class="um-form-row-column">
				<!-- Single column here -->
				<div class="um-fields-column-header<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>">
					<div class="um-fields-column-header-order"><?php esc_html_e( '#', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-name"><?php esc_html_e( 'Name', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-metakey"><?php esc_html_e( 'Metakey', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-type"><?php esc_html_e( 'Type', 'ultimate-member' ); ?></div>
					<div class="um-fields-column-header-actions">&nbsp;</div>
				</div>
				<div class="um-fields-column-content<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>" data-uniqid="<?php echo esc_attr( uniqid() ); ?>">
					<?php
					if ( ! empty( $fields ) ) {
						$i = 1;
						foreach ( $fields as $k => $field ) {
							// text-type field is the default field type for the builder
							$field_settings_tabs     = UM()->admin()->field_group()->get_field_tabs( $field['type'] );
							$field_settings_settings = UM()->admin()->field_group()->get_field_settings( $field['type'], $field['id'] );

							$row_key = ! empty( $field['id'] ) ? $field['id'] : $k;

							$type     = UM()->admin()->field_group()->get_field_type( $field );
							$meta_key = UM()->admin()->field_group()->get_field_metakey( $field );
							$meta_key = ( empty( $meta_key ) && ! empty( $field['meta_key'] ) ) ? $field['meta_key'] : $meta_key;
							?>
							<div class="um-field-row" data-field="<?php echo esc_attr( $row_key ); ?>">
								<input type="hidden" class="um-field-row-id" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" />
								<input type="hidden" class="um-field-row-parent-id" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][parent_id]" value="<?php echo esc_attr( $field['parent_id'] ); ?>" />
								<input type="hidden" class="um-field-row-order" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][order]" value="<?php echo esc_attr( $i ); ?>" />
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
				<div class="um-fields-column-footer">
					<input type="button" class="um-add-field-group-to-column button button-primary" value="<?php esc_attr_e( 'Add field group', 'ultimate-member' ); ?>" />
					<input type="button" class="um-add-field-to-column button" value="<?php esc_attr_e( 'Add individual field', 'ultimate-member' ); ?>" />
				</div>
			</div>
		</div>
	</div>
</div>

<div class="um-form-builder-new" data-form_id="<?php echo esc_attr( UM()->builder()->form_id ); ?>">

<!--	<div class="um-admin-drag-ctrls-demo um-admin-drag-ctrls">-->
<!---->
<!--		<a href="javascript:void(0);" class="active" data-modal="UM_preview_form" data-modal-size="smaller"-->
<!--		   data-dynamic-content="um_admin_preview_form" data-arg1="--><?php //echo esc_attr( get_the_ID() ); ?><!--" data-arg2="">-->
<!--			--><?php //esc_html_e( 'Live Preview', 'ultimate-member' ); ?>
<!--		</a>-->
<!---->
<!--	</div>-->
<!---->
<!--	<div class="um-admin-clear"></div>-->
<!---->
<!--	<div class="um-admin-drag">-->
<!---->
<!--		<div class="um-admin-drag-ajax" data-form_id="--><?php //echo esc_attr( UM()->builder()->form_id ); ?><!--">-->
<!--			--><?php //UM()->builder()->show_builder(); ?>
<!--		</div>-->
<!---->
<!--		<div class="um-admin-drag-addrow um-admin-tipsy-n" title="--><?php //esc_attr_e( 'Add Master Row', 'ultimate-member' ); ?><!--"-->
<!--		     data-row_action="add_row">-->
<!--			<i class="um-icon-plus"></i>-->
<!--		</div>-->
<!---->
<!--	</div>-->

	<?php

	$fields = array();
//	if ( isset( $_GET['action'] ) && 'edit' === sanitize_key( $_GET['tab'] ) ) {
//		$field_group_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
//	}
//
//	if ( ! is_null( UM()->admin()->actions_listener()->field_group_submission ) &&
//		is_array( UM()->admin()->actions_listener()->field_group_submission ) &&
//		array_key_exists( 'fields', UM()->admin()->actions_listener()->field_group_submission ) ) {
//
//		$fields = wp_parse_args(
//			UM()->admin()->actions_listener()->field_group_submission['fields'],
//			$fields
//		);
//
//		foreach ( $fields as $k => $field ) {
//			if ( array_key_exists( 'parent_id', $field ) && '0' !== (string) $field['parent_id'] ) {
//				unset( $fields[ $k ] );
//			}
//		}
//	} else {
//		if ( ! empty( $field_group_id ) ) {
//			// Get only 1st level fields with parent_id = 0. parent_id > 0 fields are from the Repeater type field.
//			$fields = UM()->admin()->field_group()->get_fields( $field_group_id, 0 );
//		}
//	}
//
//	UM()->admin()->field_group()->field_row_template();

	$i = 1;
	?>
	<div class="um-form-sections">
		<div class="um-form-section">
			<!-- Single section here -->
			<div class="um-form-section-header um-form-section-toggle-edit">
				<div class="um-form-section-header-title-wrapper">
					<span class="um-form-section-move-link" title="<?php esc_attr_e( 'Move section', 'ultimate-member' ); ?>">
						<?php echo esc_html( $i ); ?>
					</span>
					<span class="um-form-section-header-title um-form-section-toggle-edit">Section Title</span>
				</div>
				<div class="um-form-section-header-actions um-form-section-toggle-edit">
					<a href="javascript:void(0);" class="um-form-section-action-edit"><?php esc_html_e( 'Section Settings', 'ultimate-member' ); ?></a>
					<a href="javascript:void(0);" class="um-form-section-action-delete"><?php esc_html_e( 'Delete Section', 'ultimate-member' ); ?></a>
				</div>
			</div>
			<div class="um-form-section-settings">
				<?php
				$section_settings = UM()->config()->get( 'builder_section_settings' );
				$section_tabs     = array_keys( $section_settings );
				$tab_labels       = UM()->config()->get( 'field_settings_tabs' );
				?>
				<div class="um-form-section-settings-tabs">
					<?php
					foreach ( $section_tabs as $tab_key ) {
						$classes = array();
						if ( 'general' === $tab_key ) {
							$classes[] = 'current';
						}
						?>

						<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
							<?php echo esc_html( $tab_labels[ $tab_key ] ); ?>
						</div>

						<?php
					}
					?>
				</div>
				<div class="um-form-section-settings-tabs-content">
					<?php
					foreach ( $section_settings as $tab_key => $settings_fields ) {
						$classes = array();
						if ( 'general' === $tab_key ) {
							$classes[] = 'current';
						}
						?>
						<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
							<?php
							UM()->admin()->forms(
								array(
									'class'     => 'form_fields_section' . $tab_key,
									'prefix_id' => 'form[fields][section][' . $tab_key . ']',
									'fields'    => $settings_fields,
								)
							)->render_form();
							?>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<div class="um-form-section-content">
				<div class="um-form-rows">
					<div class="um-form-row">
						<!-- Single row here -->
						<div class="um-form-row-header um-form-row-toggle-edit">
							<div class="um-form-row-header-title-wrapper">
								<span class="um-form-row-move-link" title="<?php esc_attr_e( 'Move row', 'ultimate-member' ); ?>">
									<?php echo esc_html( $i ); ?>
								</span>
								<span class="um-form-row-header-title um-form-row-toggle-edit">Row Title</span>
							</div>
							<div class="um-form-row-header-actions um-form-row-toggle-edit">
								<a href="javascript:void(0);" class="um-form-row-action-edit"><?php esc_html_e( 'Row Settings', 'ultimate-member' ); ?></a>
								<a href="javascript:void(0);" class="um-form-row-action-delete"><?php esc_html_e( 'Delete Row', 'ultimate-member' ); ?></a>
							</div>
						</div>
						<div class="um-form-row-settings">
							<?php
							$row_settings = UM()->config()->get( 'builder_row_settings' );
							$row_tabs     = array_keys( $row_settings );
							$tab_labels   = UM()->config()->get( 'field_settings_tabs' );
							?>
							<div class="um-form-row-settings-tabs">
								<?php
								foreach ( $row_tabs as $tab_key ) {
									$classes = array();
									if ( 'general' === $tab_key ) {
										// General tab is selected by default for the new field.
										$classes[] = 'current';
									}
									?>

									<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
										<?php echo esc_html( $tab_labels[ $tab_key ] ); ?>
									</div>

									<?php
								}
								?>
							</div>
							<div class="um-form-row-settings-tabs-content">
								<?php
								foreach ( $row_settings as $tab_key => $settings_fields ) {
									$classes = array();
									if ( 'general' === $tab_key ) {
										// General tab is selected by default for the new field.
										$classes[] = 'current';
									}
									?>
									<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
										<?php
										UM()->admin()->forms(
											array(
												'class'     => 'form_fields_row' . $tab_key,
												'prefix_id' => 'form[fields][row][' . $tab_key . ']',
												'fields'    => $settings_fields,
											)
										)->render_form();
										?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
						<div class="um-form-row-content">
							<div class="um-form-row-columns">
								<div class="um-form-row-column">
									<!-- Single column here -->
									<div class="um-fields-column-header<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>">
										<div class="um-fields-column-header-order"><?php esc_html_e( '#', 'ultimate-member' ); ?></div>
										<div class="um-fields-column-header-name"><?php esc_html_e( 'Name', 'ultimate-member' ); ?></div>
										<div class="um-fields-column-header-metakey"><?php esc_html_e( 'Metakey', 'ultimate-member' ); ?></div>
										<div class="um-fields-column-header-type"><?php esc_html_e( 'Type', 'ultimate-member' ); ?></div>
										<div class="um-fields-column-header-actions">&nbsp;</div>
									</div>
									<div class="um-fields-column-content<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>" data-uniqid="<?php echo esc_attr( uniqid() ); ?>">
										<?php
										if ( ! empty( $fields ) ) {
											$i = 1;
											foreach ( $fields as $k => $field ) {
												// text-type field is the default field type for the builder
												$field_settings_tabs     = UM()->admin()->field_group()->get_field_tabs( $field['type'] );
												$field_settings_settings = UM()->admin()->field_group()->get_field_settings( $field['type'], $field['id'] );

												$row_key = ! empty( $field['id'] ) ? $field['id'] : $k;

												$type     = UM()->admin()->field_group()->get_field_type( $field );
												$meta_key = UM()->admin()->field_group()->get_field_metakey( $field );
												$meta_key = ( empty( $meta_key ) && ! empty( $field['meta_key'] ) ) ? $field['meta_key'] : $meta_key;
												?>
												<div class="um-field-row" data-field="<?php echo esc_attr( $row_key ); ?>">
													<input type="hidden" class="um-field-row-id" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" />
													<input type="hidden" class="um-field-row-parent-id" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][parent_id]" value="<?php echo esc_attr( $field['parent_id'] ); ?>" />
													<input type="hidden" class="um-field-row-order" name="field_group[fields][<?php echo esc_attr( $row_key ); ?>][order]" value="<?php echo esc_attr( $i ); ?>" />
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
									<div class="um-fields-column-footer">
										<input type="button" class="um-add-field-group-to-column button button-primary" value="<?php esc_attr_e( 'Add field group', 'ultimate-member' ); ?>" />
										<input type="button" class="um-add-field-to-column button" value="<?php esc_attr_e( 'Add individual field', 'ultimate-member' ); ?>" />
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="um-fields-footer">
					<a href="javascript:void(0);" class="um-add-field-row"><?php esc_html_e( 'Add row', 'ultimate-member' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<div class="um-builder-footer">
		<a href="javascript:void(0);" class="um-add-field-row"><?php esc_html_e( 'Add section', 'ultimate-member' ); ?></a>
	</div>
</div>
