<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$user_fields = array();
foreach ( UM()->builtin()->all_user_fields() as $key => $arr ) {
	$user_fields[ $key ] = isset( $arr['title'] ) ? $arr['title'] : '';
}

$_um_tagline_fields = get_post_meta( $post_id, '_um_tagline_fields', true );
$_um_reveal_fields  = get_post_meta( $post_id, '_um_reveal_fields', true );

$fields = array(
	array(
		'id'    => '_um_profile_photo',
		'type'  => 'checkbox',
		'label' => __( 'Enable Profile Photo', 'ultimate-member' ),
		'value' => (bool) get_post_meta( $post_id, '_um_profile_photo', true ),
	),
	array(
		'id'      => '_um_cover_photos',
		'type'    => 'checkbox',
		'label'   => __( 'Enable Cover Photo', 'ultimate-member' ),
		'tooltip' => __( 'If turned on, the users cover photo will appear in the directory', 'ultimate-member' ),
		'value'   => (bool) get_post_meta( $post_id, '_um_cover_photos', true ),
	),
	array(
		'id'    => '_um_show_name',
		'type'  => 'checkbox',
		'label' => __( 'Show display name', 'ultimate-member' ),
		'value' => (bool) get_post_meta( $post_id, '_um_show_name', true ),
	),
	array(
		'id'    => '_um_show_tagline',
		'type'  => 'checkbox',
		'label' => __( 'Show tagline below profile name', 'ultimate-member' ),
		'value' => (bool) get_post_meta( $post_id, '_um_show_tagline', true ),
	),
	array(
		'id'                  => '_um_tagline_fields',
		'type'                => 'multi_selects',
		'label'               => __( 'Choose field(s) to display in tagline', 'ultimate-member' ),
		'value'               => $_um_tagline_fields,
		'conditional'         => array( '_um_show_tagline', '=', 1 ),
		'add_text'            => __( 'Add New Custom Field', 'ultimate-member' ),
		'options'             => $user_fields,
		'show_default_number' => 1,
		'sorting'             => true,
	),
	array(
		'id'    => '_um_show_userinfo',
		'type'  => 'checkbox',
		'label' => __( 'Show extra user information below tagline?', 'ultimate-member' ),
		'value' => (bool) get_post_meta( $post_id, '_um_show_userinfo', true ),
	),
	array(
		'id'                  => '_um_reveal_fields',
		'type'                => 'multi_selects',
		'label'               => __( 'Choose field(s) to display in extra user information section', 'ultimate-member' ),
		'value'               => $_um_reveal_fields,
		'add_text'            => __( 'Add New Custom Field', 'ultimate-member' ),
		'conditional'         => array( '_um_show_userinfo', '=', 1 ),
		'options'             => $user_fields,
		'show_default_number' => 1,
		'sorting'             => true,
	),
	array(
		'id'          => '_um_show_social',
		'type'        => 'checkbox',
		'label'       => __( 'Show social connect icons in extra user information section', 'ultimate-member' ),
		'value'       => (bool) get_post_meta( $post_id, '_um_show_social', true ),
		'conditional' => array( '_um_show_userinfo', '=', 1 ),
	),
	array(
		'id'          => '_um_userinfo_animate',
		'type'        => 'checkbox',
		'label'       => __( 'Hide extra user information to the reveal section', 'ultimate-member' ),
		'tooltip'     => __( 'If not checked always shown', 'ultimate-member' ),
		'value'       => (bool) get_post_meta( $post_id, '_um_userinfo_animate', true ),
		'conditional' => array( '_um_show_userinfo', '=', 1 ),
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
$fields = apply_filters( 'um_admin_extend_directory_options_profile', $fields ); ?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-member-directory-profile um-half-column',
			'prefix_id' => 'um_metadata',
			'fields'    => $fields,
		)
	)->render_form();
	?>
	<div class="clear"></div>
</div>
