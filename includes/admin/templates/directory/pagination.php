<div class="um-admin-metabox">
	<?php UM()->admin_forms( array(
		'class'		=> 'um-member-directory-pagination um-half-column',
		'prefix_id'	=> 'um_metadata',
		'fields' => array(
			array(
				'id'		=> '_um_profiles_per_page',
				'type'		=> 'text',
				'label'		=> __( 'Number of profiles per page', 'ultimate-member' ),
				'tooltip'	=> __( 'Number of profiles to appear on page for standard users', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value( '_um_profiles_per_page', null, 12 ),
				'size'		=> 'small'
			),
			array(
				'id'		=> '_um_profiles_per_page_mobile',
				'type'		=> 'text',
				'label'		=> __( 'Number of profiles per page (for Mobiles & Tablets)', 'ultimate-member' ),
				'tooltip'	=> __( 'Number of profiles to appear on page for mobile users', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value( '_um_profiles_per_page_mobile', null, 8 ),
				'size'		=> 'small'
			),
			array(
				'id'		=> '_um_max_users',
				'type'		=> 'text',
				'label'		=> __( 'Maximum number of profiles', 'ultimate-member' ),
				'tooltip'	=> __( 'Use this setting to control the maximum number of profiles to appear in this directory. Leave blank to disable this limit', 'ultimate-member' ),
				'value'		=> UM()->query()->get_meta_value( '_um_max_users', null, 'na' ),
				'size'		=> 'small'
			)
		)
	) )->render_form(); ?>

	<div class="um-admin-clear"></div>
</div>