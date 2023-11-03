<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$_um_sorting_fields = get_post_meta( $post_id, '_um_sorting_fields', true );
$_um_sorting_fields = empty( $_um_sorting_fields ) ? array() : $_um_sorting_fields;

$fields = array(
	array(
		'id'      => '_um_sortby',
		'type'    => 'select',
		'label'   => __( 'Default sort users by', 'ultimate-member' ),
		'tooltip' => __( 'Default sorting users by a specific parameter in the directory', 'ultimate-member' ),
		'options' => UM()->member_directory()->default_sorting,
		'value'   => UM()->query()->get_meta_value( '_um_sortby' ),
	),
	array(
		'id'          => '_um_sortby_custom',
		'type'        => 'text',
		'label'       => __( 'Meta key', 'ultimate-member' ),
		'tooltip'     => __( 'To sort by a custom field, enter the meta key of field here', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_sortby_custom', null, 'na' ),
		'conditional' => array( '_um_sortby', '=', 'other' ),
	),
	array(
		'id'          => '_um_sortby_custom_type',
		'type'        => 'select',
		'label'       => __( 'Data type', 'ultimate-member' ),
		'tooltip'     => __( 'To correct sort by a custom field, choose a data type', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_sortby_custom_type', null, 'CHAR' ),
		'options'     => UM()->member_directory()->sort_data_types,
		'conditional' => array( '_um_sortby', '=', 'other' ),
	),
	array(
		'id'          => '_um_sortby_custom_order',
		'type'        => 'select',
		'label'       => __( 'Order', 'ultimate-member' ),
		'tooltip'     => __( 'To correct sort by a custom field, choose an order', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_sortby_custom_order', null, 'ASC' ),
		'options'     => array(
			'ASC'  => __( 'ASC', 'ultimate-member' ),
			'DESC' => __( 'DESC', 'ultimate-member' ),
		),
		'conditional' => array( '_um_sortby', '=', 'other' ),
	),
	array(
		'id'          => '_um_sortby_custom_label',
		'type'        => 'text',
		'label'       => __( 'Label of custom sort', 'ultimate-member' ),
		'tooltip'     => __( 'To sort by a custom field, enter the label of sorting here', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_sortby_custom_label', null, 'na' ),
		'conditional' => array( '_um_sortby', '=', 'other' ),
	),
	array(
		'id'      => '_um_enable_sorting',
		'type'    => 'checkbox',
		'label'   => __( 'Enable custom sorting', 'ultimate-member' ),
		'tooltip' => __( 'Whether to provide an ability to change the sorting on the directory page', 'ultimate-member' ),
		'value'   => (bool) get_post_meta( $post_id, '_um_enable_sorting', true ),
	),
	array(
		'id'                  => '_um_sorting_fields',
		'type'                => 'md_sorting_fields',
		'label'               => __( 'Choose field(s) to enable in sorting', 'ultimate-member' ),
		'value'               => $_um_sorting_fields,
		'options'             => array_merge( UM()->member_directory()->sort_fields, array( 'other' => __( 'Other (Custom Field)', 'ultimate-member' ) ) ),
		'add_text'            => __( 'Add New Field', 'ultimate-member' ),
		'show_default_number' => 0,
		'conditional'         => array( '_um_enable_sorting', '=', 1 ),
	),
); ?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-member-directory-sorting um-half-column',
			'prefix_id' => 'um_metadata',
			'fields'    => $fields,
		)
	)->render_form();
	?>
	<div class="clear"></div>
</div>
