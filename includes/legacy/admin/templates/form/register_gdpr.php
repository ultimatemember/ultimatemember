<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id;

$options = array(
	'' => __( 'Select page', 'ultimate-member' ),
);

$pages = get_pages();
foreach ( $pages as $page ) {
	$options[ $page->ID ] = $page->post_title;
}

$register_use_gdpr = ! isset( $post_id ) ? false : get_post_meta( $post_id, '_um_register_use_gdpr', true ); ?>

<div class="um-admin-metabox">
	<?php
	UM()->admin_forms(
		array(
			'class'     => 'um-form-register-gdpr um-top-label',
			'prefix_id' => 'form',
			'fields'    => array(
				array(
					'id'      => '_um_register_use_gdpr',
					'type'    => 'select',
					'label'   => __( 'Enable on this form', 'ultimate-member' ),
					'value'   => $register_use_gdpr,
					'options' => array(
						0 => __( 'No', 'ultimate-member' ),
						1 => __( 'Yes', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_register_use_gdpr_content_id',
					'type'        => 'select',
					'label'       => __( 'Content', 'ultimate-member' ),
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
			),
		)
	)->render_form();
	?>
	<div class="um-admin-clear"></div>
</div>
