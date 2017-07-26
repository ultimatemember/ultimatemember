<div class="um-admin-metabox">

	<?php
	foreach ( UM()->roles()->get_roles( __( 'All roles', 'ultimate-member' ) ) as $key => $value ) {
		if ( ! empty( UM()->query()->get_meta_value( '_um_profile_role', $key ) ) )
			$profile_role = UM()->query()->get_meta_value( '_um_profile_role', $key );
	}

	UM()->admin_forms( array(
		'class'		=> 'um-form-profile-customize um-top-label',
		'prefix_id'	=> 'form',
		'fields' => array(
			array(
				'id'		    => '_um_profile_use_globals',
				'type'		    => 'select',
				'label'    		=> __( 'Apply custom settings to this form', 'ultimate-member' ),
				'tooltip' 	=> __( 'Switch to yes if you want to customize this form settings, styling &amp; appearance', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_use_globals', null, 0 ),
				'options'		=> array(
					0	=> __( 'No', 'ultimate-member' ),
					1	=> __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'		    => '_um_profile_role',
				'type'		    => 'select',
				'label'    		=> __( 'Make this profile role-specific', 'ultimate-member' ),
				'value' 		=> ! empty( $profile_role ) ? $profile_role : 0,
				'options'		=> UM()->roles()->get_roles( __( 'All roles', 'ultimate-member' ) ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_template',
				'type'		    => 'select',
				'label'    		=> __( 'Template', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_template', null, um_get_option( 'profile_template' ) ),
				'options'		=> UM()->shortcodes()->get_templates( 'profile' ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_max_width',
				'type'		    => 'text',
				'label'    		=> __( 'Max. Width (px)', 'ultimate-member' ),
				'tooltip'    	=> __( 'The maximum width of shortcode in pixels e.g. 600px', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value('_um_profile_max_width', null, um_get_option( 'profile_max_width' ) ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_area_max_width',
				'type'		    => 'text',
				'label'    		=> __( 'Profile Area Max. Width (px)', 'ultimate-member' ),
				'tooltip'    	=> __( 'The maximum width of the profile area inside profile (below profile header)', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value('_um_profile_area_max_width', null, um_get_option( 'profile_area_max_width' ) ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_icons',
				'type'		    => 'select',
				'label'    		=> __( 'Field Icons', 'ultimate-member' ),
				'tooltip'    	=> __( 'Whether to show field icons and where to show them relative to the field', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_icons', null, um_get_option( 'profile_icons' ) ) ,
				'options'		=> array(
					'field' => __( 'Show inside text field', 'ultimate-member' ),
					'label' => __( 'Show with label', 'ultimate-member' ),
					'off' 	=> __( 'Turn off', 'ultimate-member' )
				),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_primary_btn_word',
				'type'		    => 'text',
				'label'    		=> __( 'Primary Button Text', 'ultimate-member' ),
				'tooltip'    	=> __( 'Customize the button text', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_primary_btn_word', null, um_get_option( 'profile_primary_btn_word' ) ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_secondary_btn',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Show Secondary Button', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_secondary_btn', null, 1 ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_secondary_btn_word',
				'type'		    => 'text',
				'label'    		=> __( 'Primary Button Text', 'ultimate-member' ),
				'tooltip'    	=> __( 'Customize the button text', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_secondary_btn_word', null, um_get_option( 'profile_secondary_btn_word' ) ),
				'conditional'	=> array( '_um_profile_secondary_btn', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_cover_enabled',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Enable Cover Photos', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_cover_enabled', null, 1 ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_cover_ratio',
				'type'		    => 'select',
				'label'    		=> __( 'Cover photo ratio', 'ultimate-member' ),
				'tooltip'    		=> __( 'The shortcode is centered by default unless you specify otherwise here', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_cover_ratio', null, um_get_option( 'profile_cover_ratio' ) ),
				'options'		=> array(
					'2.7:1'	=>	'2.7:1',
					'2.2:1'	=>	'2.2:1',
					'3.2:1'	=>	'3.2:1'
				),
				'conditional'	=> array( '_um_profile_cover_enabled', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_photosize',
				'type'		    => 'text',
				'label'    		=> __( 'Profile Photo Size', 'ultimate-member' ),
				'tooltip'    	=> __( 'Set the profile photo size in pixels here', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_photosize', null, um_get_option( 'profile_photosize' ) ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_photo_required',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Make Profile Photo Required', 'ultimate-member' ),
				'tooltip'    		=> __( 'Require user to update a profile photo when updating their profile', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_photo_required' ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_show_name',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Show display name in profile header?', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_show_name', null, 1 ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_show_social_links',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Show social links in profile header?', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_show_social_links', null, 0 ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_profile_show_bio',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Show user description in profile header?', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_show_bio', null, 1 ),
				'conditional'	=> array( '_um_profile_use_globals', '=', 1 )
			),

		)
	) )->render_form(); ?>

	<div class="um-admin-clear"></div>
</div>