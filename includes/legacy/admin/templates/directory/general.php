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

$_um_view_types_value = get_post_meta( $post_id, '_um_view_types', true );
$_um_view_types_value = empty( $_um_view_types_value ) ? array( 'grid', 'list' ) : $_um_view_types_value;

$view_types_options = array_map(
	function( $item ) {
		return $item['title'];
	},
	UM()->member_directory()->view_types
);

$conditional = array();
foreach ( $view_types_options as $key => $value ) {
	$conditional[] = '_um_view_types_' . $key;
}

$default_view = get_post_meta( $post_id, '_um_default_view', true );
$default_view = empty( $default_view ) ? 'grid' : $default_view;

$fields = array(
	array(
		'id'    => '_um_mode',
		'type'  => 'hidden',
		'value' => 'directory',
	),
	array(
		'id'      => '_um_view_types',
		'type'    => 'multi_checkbox',
		'label'   => __( 'View type(s)', 'ultimate-member' ),
		'tooltip' => __( 'View type a specific parameter in the directory', 'ultimate-member' ),
		'options' => $view_types_options,
		'columns' => 3,
		'value'   => $_um_view_types_value,
		'data'    => array( 'fill__um_default_view' => 'checkbox_key' ),
	),
	array(
		'id'          => '_um_default_view',
		'type'        => 'select',
		'label'       => __( 'Default view type', 'ultimate-member' ),
		'tooltip'     => __( 'Default directory view type', 'ultimate-member' ),
		'options'     => $view_types_options,
		'value'       => $default_view,
		'conditional' => array( implode( '|', $conditional ), '~', 1 ),
	),
	array(
		'id'      => '_um_roles',
		'type'    => 'multi_checkbox',
		'label'   => __( 'User Roles to Display', 'ultimate-member' ),
		'tooltip' => __( 'If you do not want to show all members, select only user roles to appear in this directory', 'ultimate-member' ),
		'options' => UM()->roles()->get_roles(),
		'columns' => 3,
		'value'   => $_um_roles_value,
	),
	array(
		'id'      => '_um_has_profile_photo',
		'type'    => 'checkbox',
		'label'   => __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
		'tooltip' => __( 'If \'Use Gravatars\' as profile photo is enabled, this option is ignored', 'ultimate-member' ),
		'value'   => (bool) get_post_meta( $post_id, '_um_has_profile_photo', true ),
	),
	array(
		'id'    => '_um_has_cover_photo',
		'type'  => 'checkbox',
		'label' => __( 'Only show members who have uploaded a cover photo', 'ultimate-member' ),
		'value' => (bool) get_post_meta( $post_id, '_um_has_cover_photo', true ),
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
$fields = apply_filters( 'um_admin_extend_directory_options_general', $fields ); ?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-member-directory-general um-half-column',
			'prefix_id' => 'um_metadata',
			'fields'    => $fields,
		)
	)->render_form();
	?>

	<div class="um-admin-clear"></div>
</div>
