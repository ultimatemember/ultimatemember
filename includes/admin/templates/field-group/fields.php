<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//$draft_id        = false;
$fields          = array();
$field_group_id = 0;
if ( isset( $_GET['tab'] ) && 'edit' === sanitize_key( $_GET['tab'] ) ) {
	$field_group_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( ! empty( $field_group_id ) ) {
//		$draft_id = UM()->admin()->field_group()->get_draft_by( $field_group_id, 'group' );
//		if ( ! empty( $draft_id ) ) {
//			$fields = UM()->admin()->field_group()->get_fields( $draft_id );
//			$field_group_id = $draft_id;
//		} else {
//			$fields = UM()->admin()->field_group()->get_fields( $field_group_id );
//		}
		$fields = UM()->admin()->field_group()->get_fields( $field_group_id );
	}
} /*else {
	$draft_id = UM()->admin()->field_group()->get_draft_by( get_current_user_id(), 'user' );
	if ( ! empty( $draft_id ) ) {
		$fields = UM()->admin()->field_group()->get_fields( $draft_id );
		$field_group_id = $draft_id;
	}
}*/

$field_types = UM()->config()->get( 'field_types' );

// text-type field is default field type for the builder
$template_type     = 'text';
$template_tabs     = UM()->admin()->field_group()->get_field_tabs( $template_type );
$template_settings = UM()->admin()->field_group()->get_field_settings( $template_type );

// text-type field is default field type for the builder
$template_settings['general']['type']['value'] = $template_type;

//$field_types_options = array();
//$categories          = UM()->config()->get( 'field_type_categories' );
//foreach ( $categories as $cat_key => $cat_title ) {
//	$field_types_options[ $cat_key ] = array(
//		'title'   => $cat_title,
//		'options' => array(),
//	);
//	foreach ( $field_types as $field_key => $field_data ) {
//		if ( $cat_key !== $field_data['category'] ) {
//			continue;
//		}
//		$field_types_options[ $cat_key ]['options'][ $field_key ] = $field_data['title'];
//	}
//}
?>

<div class="um-field-groups-field-row-template um-field-row-edit-mode">
	<input type="hidden" class="um-field-groups-field-id" name="field_group[fields][new_{index}][id]" value="" disabled />
	<input type="hidden" class="um-field-groups-field-order" name="field_group[fields][new_{index}][order]" value="" disabled />
	<div class="um-field-groups-field-row-header um-field-groups-toggle-edit">
		<span class="um-field-groups-field-move-link"></span>
		<span class="um-field-groups-field-title um-field-groups-toggle-edit"><?php esc_html_e( '(no title)', 'ultimate-member' ); ?></span>
		<span class="um-field-groups-field-metakey um-field-groups-toggle-edit"><?php esc_html_e( '(no metakey)', 'ultimate-member' ); ?></span>
		<span class="um-field-groups-field-type um-field-groups-toggle-edit"><?php echo esc_html( $field_types[ $template_type ]['title'] ); ?></span>
		<span class="um-field-groups-field-actions um-field-groups-toggle-edit">
			<a href="javascript:void(0);" class="um-field-groups-field-edit"><?php esc_html_e( 'Edit', 'ultimate-member' ); ?></a>
			<a href="javascript:void(0);" class="um-field-groups-field-duplicate"><?php esc_html_e( 'Duplicate', 'ultimate-member' ); ?></a>
			<a href="javascript:void(0);" class="um-field-groups-field-delete"><?php esc_html_e( 'Delete', 'ultimate-member' ); ?></a>
		</span>
	</div>
	<div class="um-field-groups-field-row-edit-wrapper">
		<div class="um-edit-field-tabs">
			<?php
			foreach ( $template_tabs as $tab_key => $tab_title ) {
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
		<div class="um-edit-field-tabs-content">
			<?php
			foreach ( $template_settings as $tab_key => $settings_fields ) {
				$classes = array();
				if ( 'general' === $tab_key ) {
					// General tab is selected by default for the new field.
					$classes[] = 'current';
				}

				/*foreach ( $settings_fields as &$setting_data ) {
					$setting_data['name']     = 'field_group[fields][new_{index}][' . $setting_data['id'] . ']';
					$setting_data['disabled'] = true;
				}*/
				?>
				<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
					<?php
					echo UM()->admin()->field_group()->get_tab_fields_html( $tab_key, array( 'type' => $template_type, 'index' => 'new_{index}', 'disabled' => true ) );
					/*UM()->admin()->forms(
						array(
							'class'     => 'field_group_fields_' . $tab_key . '_new_{index}',
							'prefix_id' => 'field_group[fields][new_{index}][' . $tab_key . ']',
							'fields'    => $settings_fields,
						)
					)->render_form();*/
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>

<div class="um-field-group-builder" data-group_id="<?php echo esc_attr( $field_group_id ); ?>">
	<div class="um-field-groups-builder-header<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>">
		<div id="um-field-groups-builder-field-order"><?php esc_html_e( '#', 'ultimate-member' ); ?></div>
		<div id="um-field-groups-builder-field-name"><?php esc_html_e( 'Name', 'ultimate-member' ); ?></div>
		<div id="um-field-groups-builder-field-metakey"><?php esc_html_e( 'Metakey', 'ultimate-member' ); ?></div>
		<div id="um-field-groups-builder-field-type"><?php esc_html_e( 'Type', 'ultimate-member' ); ?></div>
		<div id="um-field-groups-builder-field-actions">&nbsp;</div>
	</div>
	<div class="um-field-groups-fields-wrapper<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>">
		<?php if ( ! empty( $fields ) ) { ?>
			<?php foreach ( $fields as $k => $field ) {
				// text-type field is default field type for the builder
				$field_settings_tabs     = UM()->admin()->field_group()->get_field_tabs( $field['type'] );
				$field_settings_settings = UM()->admin()->field_group()->get_field_settings( $field['type'], $field['id'] );

				$order    = $k + 1;
				$type     = UM()->admin()->field_group()->get_field_type( $field );
				$meta_key = UM()->admin()->field_group()->get_field_metakey( $field );
				?>
				<div class="um-field-groups-field-row" data-field="<?php echo esc_attr( $field['id'] ); ?>">
					<input type="hidden" class="um-field-groups-field-id" name="field_group[fields][<?php echo esc_attr( $field['id'] ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" />
					<input type="hidden" class="um-field-groups-field-order" name="field_group[fields][<?php echo esc_attr( $field['id'] ); ?>][order]" value="<?php echo esc_attr( $order ); ?>" />
					<div class="um-field-groups-field-row-header um-field-groups-toggle-edit">
						<span class="um-field-groups-field-move-link">
							<?php echo esc_html( $order ); ?>
						</span>
						<span class="um-field-groups-field-title um-field-groups-toggle-edit">
							<?php
							if ( ! empty( $field['title'] ) ) {
								echo esc_html( $field['title'] );
							} else {
								esc_html_e( '(no title)', 'ultimate-member' );
							}
							?>
						</span>
						<span class="um-field-groups-field-metakey um-field-groups-toggle-edit"><?php echo ! empty( $meta_key ) ? esc_html( $meta_key ) : esc_html__( '(no metakey)', 'ultimate-member' ); ?></span>
						<span class="um-field-groups-field-type um-field-groups-toggle-edit"><?php echo esc_html( $type ); ?></span>
						<span class="um-field-groups-field-actions um-field-groups-toggle-edit">
							<a href="javascript:void(0);" class="um-field-groups-field-edit"><?php esc_html_e( 'Edit', 'ultimate-member' ); ?></a>
							<a href="javascript:void(0);" class="um-field-groups-field-duplicate"><?php esc_html_e( 'Duplicate', 'ultimate-member' ); ?></a>
							<a href="javascript:void(0);" class="um-field-groups-field-delete"><?php esc_html_e( 'Delete', 'ultimate-member' ); ?></a>
						</span>
					</div>
					<div class="um-field-groups-field-row-edit-wrapper">
						<div class="um-edit-field-tabs">
							<?php
							foreach ( $field_settings_tabs as $tab_key => $tab_title ) {
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
						<div class="um-edit-field-tabs-content">
							<?php
							foreach ( $field_settings_settings as $tab_key => $settings_fields ) {
								$classes = array();
								if ( 'general' === $tab_key ) {
									// General tab is selected by default for the new field.
									$classes[] = 'current';
								}

								/*foreach ( $settings_fields as $setting_key => &$setting_data ) {
									$setting_data['name'] = 'field_group[fields][' . $field['id'] . '][' . $setting_key . ']';
								}*/
								?>
								<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-tab="<?php echo esc_attr( $tab_key ); ?>">
									<?php
									echo UM()->admin()->field_group()->get_tab_fields_html( $tab_key, array( 'type' => UM()->admin()->field_group()->get_field_type( $field, true ), 'index' => $field['id'] ) );
									/*
									UM()->admin()->forms(
										array(
											'class'     => 'field_group_fields_' . $tab_key . '_' . $field['id'],
											'prefix_id' => 'field_group[fields][' . $field['id'] . '][' . $tab_key . ']',
											'fields'    => $settings_fields,
										)
									)->render_form();*/
									?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
	<div class="um-field-groups-fields-wrapper-empty<?php if ( ! empty( $fields ) ) { ?> hidden<?php } ?>">
		<strong><?php esc_html_e( 'There aren\'t any fields yet. Add them below.', 'ultimate-member' ); ?></strong>
	</div>
	<div class="um-field-groups-row-bottom">
		<input type="button" class="um-add-field-groups-field button button-primary" value="<?php esc_attr_e( 'Add new field', 'ultimate-member' ); ?>" />
	</div>
</div>
