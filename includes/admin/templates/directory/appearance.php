<div class="um-admin-metabox">

	<?php UM()->admin_forms( array(
		'class'		=> 'um-member-directory-appearance um-top-label',
		'prefix_id'	=> 'um_metadata',
		'fields' => array(
			array(
				'id'		=> '_um_directory_template',
				'type'		=> 'select',
				'name'		=> '_um_directory_template',
				'label'		=> __( 'Template', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value( '_um_directory_template', null, um_get_option( 'directory_template' ) ),
				'options'	=> UM()->shortcodes()->get_templates( 'members' ),
			)
		)
	) )->render_form(); ?>
</div>