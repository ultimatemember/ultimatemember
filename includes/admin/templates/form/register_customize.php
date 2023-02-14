<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

foreach ( UM()->roles()->get_roles( __( 'Default', 'ultimate-member' ) ) as $key => $value ) {
	$_um_register_role = UM()->query()->get_meta_value( '_um_register_role', $key );
	if ( ! empty( $_um_register_role ) ) {
		$register_role = $_um_register_role;
	}
}

$options = array(
	'' => __( 'Select page', 'ultimate-member' ),
);

$pages = get_pages();
foreach ( $pages as $page ) {
	$options[ $page->ID ] = $page->post_title;
}

$register_use_gdpr = ! isset( $post_id ) ? false : get_post_meta( $post_id, '_um_register_use_gdpr', true );

$register_customize_fields = array(
	array(
		'id'      => '_um_register_role',
		'type'    => 'select',
		'label'   => __( 'User registration role', 'ultimate-member' ),
		'value'   => ! empty( $register_role ) ? $register_role : 0,
		'options' => UM()->roles()->get_roles( __( 'Default', 'ultimate-member' ) ),
	),
	array(
		'id'      => '_um_register_template',
		'type'    => 'select',
		'label'   => __( 'Template', 'ultimate-member' ),
		'value'   => UM()->query()->get_meta_value( '_um_register_template', null, UM()->options()->get( 'register_template' ) ),
		'options' => UM()->common()->shortcodes()->get_templates( 'register' ),
	),
	array(
		'id'          => '_um_register_primary_btn_word',
		'type'        => 'text',
		'label'       => __( 'Primary Button Text', 'ultimate-member' ),
		'description' => __( 'Customize the button text', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_register_primary_btn_word', null, UM()->options()->get( 'register_primary_btn_word' ) ),
	),
	array(
		'id'      => '_um_register_use_gdpr',
		'type'    => 'select',
		'label'   => __( 'Enable privacy policy agreement', 'ultimate-member' ),
		'value'   => $register_use_gdpr,
		'options' => array(
			0 => __( 'No', 'ultimate-member' ),
			1 => __( 'Yes', 'ultimate-member' ),
		),
	),
	array(
		'id'          => '_um_register_use_gdpr_content_id',
		'type'        => 'select',
		'label'       => __( 'Privacy policy content', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_register_use_gdpr_content_id', null, '' ),
		'options'     => $options,
		'conditional' => array( '_um_register_use_gdpr', '=', '1' ),
	),
	array(
		'id'          => '_um_register_use_gdpr_toggle_show',
		'type'        => 'text',
		'label'       => __( 'Toggle Show text', 'ultimate-member' ),
		'placeholder' => __( 'Show privacy policy', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_register_use_gdpr_toggle_show', null, __( 'Show privacy policy', 'ultimate-member' ) ),
		'conditional' => array( '_um_register_use_gdpr', '=', '1' ),
	),
	array(
		'id'          => '_um_register_use_gdpr_toggle_hide',
		'type'        => 'text',
		'label'       => __( 'Toggle Hide text', 'ultimate-member' ),
		'placeholder' => __( 'Hide privacy policy', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_register_use_gdpr_toggle_hide', null, __( 'Hide privacy policy', 'ultimate-member' ) ),
		'conditional' => array( '_um_register_use_gdpr', '=', '1' ),
	),
	array(
		'id'          => '_um_register_use_gdpr_agreement',
		'type'        => 'text',
		'label'       => __( 'Checkbox agreement description', 'ultimate-member' ),
		'placeholder' => __( 'Please confirm that you agree to our privacy policy', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_register_use_gdpr_agreement', null, __( 'Please confirm that you agree to our privacy policy', 'ultimate-member' ) ),
		'conditional' => array( '_um_register_use_gdpr', '=', '1' ),
	),
	array(
		'id'          => '_um_register_use_gdpr_error_text',
		'type'        => 'text',
		'label'       => __( 'Error Text', 'ultimate-member' ),
		'placeholder' => __( 'Please confirm your acceptance of our privacy policy', 'ultimate-member' ),
		'value'       => UM()->query()->get_meta_value( '_um_register_use_gdpr_error_text', null, __( 'Please confirm your acceptance of our privacy policy', 'ultimate-member' ) ),
		'conditional' => array( '_um_register_use_gdpr', '=', '1' ),
	),
);
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin()->forms(
		array(
			'class'     => 'um-form-register-customize um-top-label',
			'prefix_id' => 'form',
			'fields'    => $register_customize_fields,
		)
	)->render_form();
	?>
	<div class="um-admin-clear"></div>
</div>
