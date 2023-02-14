<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = array(
	'' => __( 'Select page', 'ultimate-member' ),
);

$pages = get_pages();
foreach ( $pages as $page ) {
	$options[ $page->ID ] = $page->post_title;
}
?>

<div class="um-admin-metabox">
	<?php
	UM()->admin()->forms(
		array(
			'class'     => 'um-form-register-terms-conditions um-top-label',
			'prefix_id' => 'form',
			'fields'    => array(
				array(
					'id'      => '_um_register_use_terms_conditions',
					'type'    => 'select',
					'label'   => __( 'Enable on this form', 'ultimate-member' ),
					'value'   => UM()->query()->get_meta_value( '_um_register_use_terms_conditions', null, '' ),
					'options' => array(
						0 => __( 'No', 'ultimate-member' ),
						1 => __( 'Yes', 'ultimate-member' ),
					),
				),
				array(
					'id'          => '_um_register_use_terms_conditions_content_id',
					'type'        => 'select',
					'label'       => __( 'Content', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_use_terms_conditions_content_id', null, '' ),
					'options'     => $options,
					'conditional' => array( '_um_register_use_terms_conditions', '=', '1' ),
				),
				array(
					'id'          => '_um_register_use_terms_conditions_toggle_show',
					'type'        => 'text',
					'label'       => __( 'Toggle Show text', 'ultimate-member' ),
					'placeholder' => __( 'Show Terms', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_use_terms_conditions_toggle_show', null, __( 'Show Terms', 'ultimate-member' ) ),
					'conditional' => array( '_um_register_use_terms_conditions', '=', '1' ),
				),
				array(
					'id'          => '_um_register_use_terms_conditions_toggle_hide',
					'type'        => 'text',
					'label'       => __( 'Toggle Hide text', 'ultimate-member' ),
					'placeholder' => __( 'Hide Terms', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_use_terms_conditions_toggle_hide', null, __( 'Hide Terms', 'ultimate-member' ) ),
					'conditional' => array( '_um_register_use_terms_conditions', '=', '1' ),
				),
				array(
					'id'          => '_um_register_use_terms_conditions_agreement',
					'type'        => 'text',
					'label'       => __( 'Checkbox agreement description', 'ultimate-member' ),
					'placeholder' => __( 'Please confirm that you agree to our terms & conditions', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_use_terms_conditions_agreement', null, __( 'Checkbox agreement description', 'ultimate-member' ) ),
					'conditional' => array( '_um_register_use_terms_conditions', '=', '1' ),
				),
				array(
					'id'          => '_um_register_use_terms_conditions_error_text',
					'type'        => 'text',
					'label'       => __( 'Error Text', 'ultimate-member' ),
					'placeholder' => __( 'You must agree to our terms & conditions', 'ultimate-member' ),
					'value'       => UM()->query()->get_meta_value( '_um_register_use_terms_conditions_error_text', null, __( 'You must agree to our terms & conditions', 'ultimate-member' ) ),
					'conditional' => array( '_um_register_use_terms_conditions', '=', '1' ),
				)
			),
		)
	)->render_form();
	?>
	<div class="um-admin-clear"></div>
</div>
