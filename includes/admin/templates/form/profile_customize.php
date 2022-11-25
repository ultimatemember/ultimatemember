<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$profile_role_array = array();
foreach ( UM()->roles()->get_roles() as $key => $value ) {
	$_um_profile_role = UM()->query()->get_meta_value( '_um_profile_role', $key );
	if ( ! empty( $_um_profile_role ) ) {
		$profile_role_array[] = $_um_profile_role;
	}
}

$profile_cover_enabled        = ! isset( $post_id ) ? true : get_post_meta( $post_id, '_um_profile_cover_enabled', true );
$profile_photo_required       = ! isset( $post_id ) ? false : get_post_meta( $post_id, '_um_profile_photo_required', true );
$profile_disable_photo_upload = ! isset( $post_id ) ? false : get_post_meta( $post_id, '_um_profile_disable_photo_upload', true );

$profile_show_name            = ! isset( $post_id ) ? true : get_post_meta( $post_id, '_um_profile_show_name', true );
$profile_show_social_links    = ! isset( $post_id ) ? false : get_post_meta( $post_id, '_um_profile_show_social_links', true );
$profile_show_bio             = ! isset( $post_id ) ? true : get_post_meta( $post_id, '_um_profile_show_bio', true );

$profile_customize_fields = array(
	array(
		'id'          => '_um_profile_role',
		'type'        => 'select',
		'multi'       => true,
		'label'       => __( 'Make this profile form role-specific', 'ultimate-member' ),
		'description' => __( 'Please note if you make a profile form specific to a role then you must make sure that every other role is assigned a profile form', 'ultimate-member' ),
		'value'       => $profile_role_array,
		'options'     => UM()->roles()->get_roles(),
	),
	array(
		'id'      => '_um_profile_template',
		'type'    => 'select',
		'label'   => __( 'Template', 'ultimate-member' ),
		'value'   => UM()->query()->get_meta_value( '_um_profile_template', null, UM()->options()->get( 'profile_template' ) ),
		'options' => UM()->common()->shortcodes()->get_templates( 'profile' ),
	),
	array(
		'id'          => '_um_profile_primary_btn_word',
		'type'        => 'text',
		'label'       => __( 'Primary Button Text', 'ultimate-member' ),
		'description' => __( 'Customize the button text', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_profile_primary_btn_word', null, UM()->options()->get( 'profile_primary_btn_word' ) ),
	),
	array(
		'id'      => '_um_profile_cover_enabled',
		'type'    => 'select',
		'label'   => __( 'Enable Cover Photos', 'ultimate-member' ),
		'value'   => $profile_cover_enabled,
		'options' => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
	array(
		'id'          => '_um_profile_disable_photo_upload',
		'type'        => 'select',
		'label'       => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
		'description' => __( 'Switch on/off the profile photo uploader', 'ultimate-member' ),
		'value'       => $profile_disable_photo_upload,
		'options'     => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
	array(
		'id'          => '_um_profile_photo_required',
		'type'        => 'select',
		'label'       => __( 'Make Profile Photo Required', 'ultimate-member' ),
		'description' => __( 'Require user to update a profile photo when updating their profile', 'ultimate-member' ),
		'value'       => $profile_photo_required,
		'options'     => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
		'conditional' => array( '_um_profile_disable_photo_upload', '=', 0 ),
	),
	array(
		'id'      => '_um_profile_show_name',
		'type'    => 'select',
		'label'   => __( 'Show display name in profile header?', 'ultimate-member' ),
		'value'   => $profile_show_name,
		'options' => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
	array(
		'id'      => '_um_profile_show_social_links',
		'type'    => 'select',
		'label'   => __( 'Show social links in profile header?', 'ultimate-member' ),
		'value'   => $profile_show_social_links,
		'options' => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
	array(
		'id'      => '_um_profile_show_bio',
		'type'    => 'select',
		'label'   => __( 'Show user description in profile header?', 'ultimate-member' ),
		'value'   => $profile_show_bio,
		'options' => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
);
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-form-profile-customize um-top-label',
			'prefix_id' => 'form',
			'fields'    => $profile_customize_fields,
		)
	)->render_form();
	?>
	<div class="um-admin-clear"></div>
</div>
