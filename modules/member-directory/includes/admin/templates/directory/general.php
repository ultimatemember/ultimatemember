<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$_um_roles_value = get_post_meta( $post_id, '_um_roles', true );
$_um_roles_value = empty( $_um_roles_value ) ? array() : $_um_roles_value;

$show_these_users = get_post_meta( get_the_ID(), '_um_show_these_users', true );
if ( $show_these_users ) {
	$show_these_users = implode( "\n", str_replace( "\r", '', $show_these_users ) );
}

$exclude_these_users = get_post_meta( get_the_ID(), '_um_exclude_these_users', true );
if ( $exclude_these_users ) {
	$exclude_these_users = implode( "\n", str_replace( "\r", '', $exclude_these_users ) );
}

$_um_view_type_value = get_post_meta( $post_id, '_um_view_type', true );
$_um_view_type_value = empty( $_um_view_type_value ) ? 'grid' : $_um_view_type_value;

$_um_grid_columns = get_post_meta( $post_id, '_um_grid_columns', true );
$_um_grid_columns = empty( $_um_grid_columns ) ? 3 : absint( $_um_grid_columns );

$fields = array(
	array(
		'id'    => '_um_mode',
		'type'  => 'hidden',
		'value' => 'directory',
	),
	array(
		'id'          => '_um_view_type',
		'type'        => 'select',
		'label'       => __( 'View type', 'ultimate-member' ),
		'description' => __( 'Select directory layout', 'ultimate-member' ),
		'options'     => UM()->module( 'member-directory' )->config()->get( 'view_types' ),
		'value'       => $_um_view_type_value,
	),
	array(
		'id'          => '_um_grid_columns',
		'type'        => 'select',
		'label'       => __( 'Grid Columns', 'ultimate-member' ),
		'description' => __( 'Select how many columns appear in this directory', 'ultimate-member' ),
		'options'     => array(
			2 => __( '2 Columns', 'ultimate-member' ),
			3 => __( '3 Columns', 'ultimate-member' ),
			4 => __( '4 Columns', 'ultimate-member' ),
		),
		'value'       => $_um_grid_columns,
		'conditional' => array( '_um_view_type', '=', 'grid' ),
	),
	array(
		'id'          => '_um_roles',
		'type'        => 'multi_checkbox',
		'label'       => __( 'User Roles to Display', 'ultimate-member' ),
		'description' => __( 'If you do not want to show all members, select only user roles to appear in this directory', 'ultimate-member' ),
		'options'     => UM()->roles()->get_roles(),
		'columns'     => 3,
		'value'       => $_um_roles_value,
	),
	array(
		'id'    => '_um_show_these_users',
		'type'  => 'textarea',
		'label' => __( 'Only show specific users (Enter one username per line)', 'ultimate-member' ),
		'value' => $show_these_users,
	),
	array(
		'id'    => '_um_exclude_these_users',
		'type'  => 'textarea',
		'label' => __( 'Exclude specific users (Enter one username per line)', 'ultimate-member' ),
		'value' => $exclude_these_users,
	),
);

if ( get_option( 'show_avatars' ) ) {
	$fields[] = array(
		'id'          => '_um_has_profile_photo',
		'type'        => 'checkbox',
		'label'       => __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
		'description' => __( 'If \'Use Gravatars\' as profile photo is enabled, this option is ignored', 'ultimate-member' ),
		'value'       => (bool) get_post_meta( $post_id, '_um_has_profile_photo', true ),
	);
}

if ( UM()->options()->get( 'use_cover_photos' ) ) {
	$fields[] = array(
		'id'    => '_um_has_cover_photo',
		'type'  => 'checkbox',
		'label' => __( 'Only show members who have uploaded a cover photo', 'ultimate-member' ),
		'value' => (bool) get_post_meta( $post_id, '_um_has_cover_photo', true ),
	);
}

/**
 * UM hook
 *
 * @type filter
 * @title um_admin_extend_directory_options_general
 * @description Extend Directory options fields
 * @input_vars
 * [{"var":"$fields","type":"array","desc":"Directory options fields"}]
 * @change_log
 * ["Since: 2.0"]
 * @usage add_filter( 'um_admin_directory_sort_users_select', 'function_name', 10, 1 );
 * @example
 * <?php
 * add_filter( 'um_admin_directory_sort_users_select', 'my_directory_sort_users_select', 10, 1 );
 * function my_directory_sort_users_select( $sort_types ) {
 *     // your code here
 *     return $sort_types;
 * }
 * ?>
 */
$fields = apply_filters( 'um_admin_extend_directory_options_general', $fields );
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin()->forms(
		array(
			'class'     => 'um-member-directory-general um-half-column',
			'prefix_id' => 'um_metadata',
			'fields'    => $fields,
		)
	)->render_form();
	?>

	<div class="um-admin-clear"></div>
</div>
