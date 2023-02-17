<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields          = array();
$fields_group_id = 0;
if ( isset( $_GET['tab'] ) && 'edit' === sanitize_key( $_GET['tab'] ) ) {
	$fields_group_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	if ( ! empty( $fields_group_id ) ) {
		$fields = UM()->admin()->fields_group()->get_fields( $fields_group_id );
	}
}

$field_types_options = array();
$field_types         = UM()->config()->get( 'field_types' );
$categories          = UM()->config()->get( 'field_type_categories' );
foreach ( $categories as $cat_key => $cat_title ) {
	$field_types_options[ $cat_key ] = array(
		'title'   => $cat_title,
		'options' => array(),
	);
	foreach ( $field_types as $field_key => $field_data ) {
		if ( $cat_key !== $field_data['category'] ) {
			continue;
		}
		$field_types_options[ $cat_key ]['options'][ $field_key ] = $field_data['title'];
	}
}
?>

<div class="um-fields-groups-field-row-template um-field-row-edit-mode">
	<div class="um-fields-groups-field-row-header">
		<span class="um-fields-groups-field-data">
			<span class="um-fields-groups-field-move-link">
				<i class="fas fa-arrows-alt"></i>
			</span>
			<span class="um-fields-groups-field-title"></span>
			<span class="um-fields-groups-field-type"></span>
		</span>
		<span class="um-fields-groups-field-actions">
			<a href="javascript:void(0);" class="um-fields-groups-field-edit"><?php esc_html_e( 'Edit', 'ultimate-member' ); ?></a>
			<a href="javascript:void(0);" class="um-fields-groups-field-duplicate" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to duplicate this field?', 'ultimate-member' ) ); ?>');"><?php esc_html_e( 'Duplicate', 'ultimate-member' ); ?></a>
			<a href="javascript:void(0);" class="um-fields-groups-field-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this field?', 'ultimate-member' ) ); ?>');"><?php esc_html_e( 'Delete', 'ultimate-member' ); ?></a>
		</span>
	</div>
	<div class="um-fields-groups-field-row-edit-wrapper">

	</div>
</div>

<div class="um-fields-group-builder" data-group_id="<?php echo esc_attr( $fields_group_id ); ?>">
	<div class="um-fields-groups-fields-wrapper<?php if ( empty( $fields ) ) { ?> hidden<?php } ?>">
		<?php if ( ! empty( $fields ) ) { ?>
			<?php foreach ( $fields as $field ) { ?>
				<div class="um-fields-groups-field-row" data-field="<?php echo esc_attr( $field['id'] ); ?>">
					<div class="um-fields-groups-field-row-header">
						<span class="um-fields-groups-field-data">
							<span class="um-fields-groups-field-move-link">
								<i class="fas fa-arrows-alt"></i>
							</span>
							<span class="um-fields-groups-field-title"><?php echo esc_html( $field['title'] ); ?></span>
							<span class="um-fields-groups-field-type"><?php echo esc_html( UM()->admin()->fields_group()->get_field_type( $field ) ); ?></span>
						</span>
						<span class="um-fields-groups-field-actions">
							<a href="javascript:void(0);" class="um-fields-groups-field-edit"><?php esc_html_e( 'Edit', 'ultimate-member' ); ?></a>
							<a href="javascript:void(0);" class="um-fields-groups-field-duplicate" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to duplicate this field?', 'ultimate-member' ) ); ?>');"><?php esc_html_e( 'Duplicate', 'ultimate-member' ); ?></a>
							<a href="javascript:void(0);" class="um-fields-groups-field-delete" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this field?', 'ultimate-member' ) ); ?>');"><?php esc_html_e( 'Delete', 'ultimate-member' ); ?></a>
						</span>
					</div>
					<div class="um-fields-groups-field-row-edit-wrapper">
						<div class="um-edit-field-tabs">
							<div class="current" data-tab="general"><?php esc_html_e( 'General', 'ultimate-member' ); ?></div>
							<div data-tab="validation"><?php esc_html_e( 'Validation', 'ultimate-member' ); ?></div>
							<div data-tab="privacy"><?php esc_html_e( 'Privacy & Permissions', 'ultimate-member' ); ?></div>
							<div data-tab="conditional"><?php esc_html_e( 'Conditional Logic', 'ultimate-member' ); ?></div>
							<div data-tab="advanced"><?php esc_html_e( 'Advanced', 'ultimate-member' ); ?></div>
						</div>
						<div class="um-edit-field-tabs-content">
							<div class="current" data-tab="general">
								<?php
								UM()->admin()->forms(
									array(
										'class'     => 'fields_group_fields_general' . $field['id'],
										'prefix_id' => 'fields_group[fields][' . $field['id'] . '][general]',
										'fields'    => array(
											array(
												'id'      => 'type',
												'type'    => 'select',
												'label'   => __( 'Field type', 'ultimate-member' ),
												'value'   => UM()->admin()->fields_group()->get_field_type( $field, true ),
												'options' => $field_types_options,
											),
											array(
												'id'          => 'title',
												'type'        => 'text',
												'label'       => __( 'Field title', 'ultimate-member' ),
												'description' => __( 'Shown internally for administrator who set up fields group', 'ultimate-member' ),
												'value'       => $field['title'],
											),
										),
									)
								)->render_form();
								?>
							</div>
							<div data-tab="conditional">
								<?php
								UM()->admin()->forms(
									array(
										'class'     => 'fields_group_fields_conditional' . $field['id'],
										'prefix_id' => 'fields_group[fields][' . $field['id'] . '][conditional]',
										'fields'    => array(
											array(
												'id'      => 'conditional_logic',
												'type'    => 'checkbox',
												'label'   => __( 'Conditional Logic', 'ultimate-member' ),
												'value'   => UM()->admin()->fields_group()->get_field_meta( $field, 'conditional_logic', false ),
											),
											array(
												'id'          => 'conditional_action',
												'type'        => 'select',
												'label'       => __( 'Action', 'ultimate-member' ),
												'options'     => array(
													'show' => __( 'Show', 'ultimate-member' ),
													'hide' => __( 'Hide', 'ultimate-member' ),
												),
												'value'       => UM()->admin()->fields_group()->get_field_meta( $field, 'conditional_action', 'show' ),
//												'conditional' => array( 'conditional_logic', '=', 1 ),
											),
											array(
												'id'          => 'conditional_rules',
												'type'        => 'conditional_rules',
												'label'       => __( 'Rules', 'ultimate-member' ),
												'value'       => UM()->admin()->fields_group()->get_field_meta( $field, 'conditional_rules' ),
//												'conditional' => array( 'conditional_logic', '=', 1 ),
											),
										),
									)
								)->render_form();
								?>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
	<div class="um-fields-groups-fields-wrapper-empty<?php if ( ! empty( $fields ) ) { ?> hidden<?php } ?>">
		<strong><?php esc_html_e( 'There aren\'t any fields yet. Add them below.', 'ultimate-member' ); ?></strong>
	</div>
	<div class="um-fields-groups-row-bottom">
		<input type="button" class="um-add-fields-groups-field button button-primary" value="<?php esc_attr_e( 'Add new field', 'ultimate-member' ); ?>" />
	</div>
</div>
