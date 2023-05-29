<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields = array();
if ( isset( $_GET['tab'] ) && 'edit' === sanitize_key( $_GET['tab'] ) ) {
	$field_group_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
}

if ( ! is_null( UM()->admin()->actions_listener()->field_group_submission ) &&
     is_array( UM()->admin()->actions_listener()->field_group_submission ) &&
     array_key_exists( 'fields', UM()->admin()->actions_listener()->field_group_submission ) ) {

	$fields = wp_parse_args(
		UM()->admin()->actions_listener()->field_group_submission['fields'],
		$fields
	);

	foreach ( $fields as $k => $field ) {
		if ( array_key_exists( 'parent_id', $field ) && '0' !== (string) $field['parent_id'] ) {
			unset( $fields[ $k ] );
		}
	}
} else {
	if ( ! empty( $field_group_id ) ) {
		// Get only 1st level fields with parent_id = 0. parent_id > 0 fields are from the Repeater type field.
		$fields = UM()->admin()->field_group()->get_fields( $field_group_id, 0 );
	}
}

UM()->admin()->field_group()->field_row_template();
?>

<div class="um-fields-column um-field-group-builder">
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
	<div class="um-fields-column-empty-content<?php if ( ! empty( $fields ) ) { ?> hidden<?php } ?>">
		<strong><?php esc_html_e( 'There aren\'t any fields yet. Add them below.', 'ultimate-member' ); ?></strong>
	</div>
	<div class="um-fields-column-footer">
		<input type="button" class="um-add-field-to-column button button-primary" value="<?php esc_attr_e( 'Add new field', 'ultimate-member' ); ?>" />
	</div>
</div>
