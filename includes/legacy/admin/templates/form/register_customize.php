<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$use_custom_settings = ! isset( $post_id ) ? false : get_post_meta( $post_id, '_um_register_use_custom_settings', true );

foreach ( UM()->roles()->get_roles( __( 'Default', 'ultimate-member' ) ) as $key => $value ) {
	$_um_register_role = UM()->query()->get_meta_value( '_um_register_role', $key );
	if ( ! empty( $_um_register_role ) ) {
		$register_role = $_um_register_role;
	}
}

$register_secondary_btn = ! isset( $post_id ) ? UM()->options()->get( 'register_secondary_btn' ) : get_post_meta( $post_id, '_um_register_secondary_btn', true );
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-form-register-customize um-top-label',
			'prefix_id' => 'form',
			'fields'    => array(
				array(
					'id'      => '_um_register_use_custom_settings',
					'type'    => 'select',
					'label'   => __( 'Apply custom settings to this form', 'ultimate-member' ),
					'tooltip' => __( 'Switch to yes if you want to customize this form settings, styling &amp; appearance', 'ultimate-member' ),
					'value'   => $use_custom_settings,
					'options' => array(
						0 => __( 'No', 'ultimate-member' ),
						1 => __( 'Yes', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_register_role',
					'type'        => 'select',
					'label'       => __( 'Assign role to form', 'ultimate-member' ),
					'value'       => ! empty( $register_role ) ? $register_role : 0,
					'options'     => UM()->roles()->get_roles( __( 'Default', 'ultimate-member' ) ),
					'conditional' => array( '_um_register_use_custom_settings', '=', 1 ),
				),
				array(
					'id'          => '_um_register_template',
					'type'        => 'select',
					'label'       => __( 'Template', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_template', null, UM()->options()->get( 'register_template' ) ),
					'options'     => UM()->shortcodes()->get_templates( 'register' ),
					'conditional' => array( '_um_register_use_custom_settings', '=', 1 ),
				),
				array(
					'id'          => '_um_register_max_width',
					'type'        => 'text',
					'label'       => __( 'Max. Width (px)', 'ultimate-member' ),
					'tooltip'     => __( 'The maximum width of shortcode in pixels e.g. 600px', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_max_width', null, UM()->options()->get( 'register_max_width' ) ),
					'conditional' => array( '_um_register_use_custom_settings', '=', 1 ),
				),
				array(
					'id'          => '_um_register_icons',
					'type'        => 'select',
					'label'       => __( 'Field Icons', 'ultimate-member' ),
					'tooltip'     => __( 'Whether to show field icons and where to show them relative to the field', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_icons', null, UM()->options()->get( 'register_icons' ) ),
					'options'     => array(
						'field' => __( 'Show inside text field', 'ultimate-member' ),
						'label' => __( 'Show with label', 'ultimate-member' ),
						'off'   => __( 'Turn off', 'ultimate-member' ),
					),
					'conditional' => array( '_um_register_use_custom_settings', '=', 1 ),
				),
				array(
					'id'          => '_um_register_primary_btn_word',
					'type'        => 'text',
					'label'       => __( 'Primary Button Text', 'ultimate-member' ),
					'tooltip'     => __( 'Customize the button text', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_primary_btn_word', null, UM()->options()->get( 'register_primary_btn_word' ) ),
					'conditional' => array( '_um_register_use_custom_settings', '=', 1 ),
				),
				array(
					'id'          => '_um_register_secondary_btn',
					'type'        => 'select',
					'label'       => __( 'Show Secondary Button', 'ultimate-member' ),
					'value'       => $register_secondary_btn,
					'conditional' => array( '_um_register_use_custom_settings', '=', 1 ),
					'options'     => array(
						0 => __( 'No', 'ultimate-member' ),
						1 => __( 'Yes', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_register_secondary_btn_word',
					'type'        => 'text',
					'label'       => __( 'Secondary Button Text', 'ultimate-member' ),
					'tooltip'     => __( 'Customize the button text', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_secondary_btn_word', null, UM()->options()->get( 'register_secondary_btn_word' ) ),
					'conditional' => array( '_um_register_secondary_btn', '=', 1 ),
				),
			),
		)
	)->render_form();
	?>
	<div class="um-admin-clear"></div>
</div>
