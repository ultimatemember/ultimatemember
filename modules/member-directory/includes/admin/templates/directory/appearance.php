<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post_id; ?>

<div class="um-admin-metabox">
	<?php
	UM()->admin()->forms(
		array(
			'class'     => 'um-member-directory-appearance um-top-label',
			'prefix_id' => 'um_metadata',
			'fields'    => array(
				array(
					'id'      => '_um_directory_template',
					'type'    => 'select',
					'label'   => __( 'Template', 'ultimate-member' ),
					'value'   => get_post_meta( $post_id, '_um_directory_template', true ),
					'options' => UM()->common()->shortcodes()->get_templates( 'members' ),
				),
			),
		)
	)->render_form();
	?>
</div>