<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields = array(
	array(
		'id'          => '_um_privacy',
		'type'        => 'select',
		'label'       => __( 'Who can see this member directory', 'ultimate-member' ),
		'description' => __( 'Select which users can view this member directory. Minimum recommended visibility is `Members only`. Please pay attention that visible for guests (anyone) member directory can have sensitive information.', 'ultimate-member' ),
		'options'     => array(
			0 => __( 'Anyone', 'ultimate-member' ),
			1 => __( 'Guests only', 'ultimate-member' ),
			2 => __( 'Members only', 'ultimate-member' ),
			3 => __( 'Only specific roles', 'ultimate-member' ),
		),
		'value'       => UM()->query()->get_meta_value( '_um_privacy', null, 2 ),
	),
	array(
		'id'          => '_um_privacy_roles',
		'type'        => 'select',
		'multi'       => true,
		'label'       => __( 'Allowed roles', 'ultimate-member' ),
		'description' => __( 'Select the the user roles allowed to view this member directory.', 'ultimate-member' ),
		'options'     => UM()->roles()->get_roles(),
		'placeholder' => __( 'Choose user roles...', 'ultimate-member' ),
		'conditional' => array( '_um_privacy', '=', '3' ),
		'value'       => UM()->query()->get_meta_value( '_um_privacy_roles', null, 'na' ),
	),
);

$fields = apply_filters( 'um_admin_extend_directory_options_privacy', $fields );
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-member-directory-privacy um-half-column',
			'prefix_id' => 'um_metadata',
			'fields'    => $fields,
		)
	)->render_form();
	?>

	<div class="clear"></div>
</div>
