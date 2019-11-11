<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>


<div class="um-admin-metabox">

	<?php $profile_role_array = array();
	foreach ( UM()->roles()->get_roles() as $key => $value ) {
		$_um_profile_role = UM()->query()->get_meta_value( '_um_profile_role', $key );
		if ( ! empty( $_um_profile_role ) ) {
			$profile_role_array[] = $_um_profile_role;
		}
	}

	UM()->admin_forms( array(
		'class'		=> 'um-form-profile-customize um-top-label',
		'prefix_id'	=> 'form',
		'fields' => array(
			array(
				'id'        => '_um_profile_use_custom_settings',
				'type'      => 'select',
				'label'     => __( 'Apply custom settings to this form', 'ultimate-member' ),
				'tooltip'   => __( 'Switch to yes if you want to customize this form settings, styling &amp; appearance', 'ultimate-member' ),
				'value'     => UM()->query()->get_meta_value( '_um_profile_use_custom_settings', null, 0 ),
				'options'   => array(
					0   => __( 'No', 'ultimate-member' ),
					1   => __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'            => '_um_profile_role',
				'type'          => 'select',
				'multi'         => true,
				'label'         => __( 'Make this profile form role-specific', 'ultimate-member' ),
				'tooltip'       => __( 'Please note if you make a profile form specific to a role then you must make sure that every other role is assigned a profile form', 'ultimate-member' ),
				'value'         => $profile_role_array,
				'options'       => UM()->roles()->get_roles(),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 )
			),
			array(
				'id'            => '_um_profile_template',
				'type'          => 'select',
				'label'         => __( 'Template', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_template', null, UM()->options()->get( 'profile_template' ) ),
				'options'       => UM()->shortcodes()->get_templates( 'profile' ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 )
			),
			array(
				'id'            => '_um_profile_max_width',
				'type'          => 'text',
				'label'         => __( 'Max. Width (px)', 'ultimate-member' ),
				'tooltip'       => __( 'The maximum width of shortcode in pixels e.g. 600px', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value('_um_profile_max_width', null, UM()->options()->get( 'profile_max_width' ) ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 )
			),
			array(
				'id'            => '_um_profile_area_max_width',
				'type'          => 'text',
				'label'         => __( 'Profile Area Max. Width (px)', 'ultimate-member' ),
				'tooltip'       => __( 'The maximum width of the profile area inside profile (below profile header)', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value('_um_profile_area_max_width', null, UM()->options()->get( 'profile_area_max_width' ) ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 )
			),
			array(
				'id'            => '_um_profile_icons',
				'type'          => 'select',
				'label'         => __( 'Field Icons', 'ultimate-member' ),
				'tooltip'       => __( 'Whether to show field icons and where to show them relative to the field', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_icons', null, UM()->options()->get( 'profile_icons' ) ) ,
				'options'       => array(
					'field' => __( 'Show inside text field', 'ultimate-member' ),
					'label' => __( 'Show with label', 'ultimate-member' ),
					'off'   => __( 'Turn off', 'ultimate-member' )
				),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 )
			),
			array(
				'id'            => '_um_profile_primary_btn_word',
				'type'          => 'text',
				'label'         => __( 'Primary Button Text', 'ultimate-member' ),
				'tooltip'       => __( 'Customize the button text', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_primary_btn_word', null, UM()->options()->get( 'profile_primary_btn_word' ) ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 )
			),
			array(
				'id'            => '_um_profile_secondary_btn',
				'type'          => 'select',
				'label'         => __( 'Show Secondary Button', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_secondary_btn', null, UM()->options()->get( 'profile_secondary_btn' ) ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 ),
				'options'       => array(
					0   => __( 'No', 'ultimate-member' ),
					1   => __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'            => '_um_profile_secondary_btn_word',
				'type'          => 'text',
				'label'         => __( 'Secondary Button Text', 'ultimate-member' ),
				'tooltip'       => __( 'Customize the button text', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_secondary_btn_word', null, UM()->options()->get( 'profile_secondary_btn_word' ) ),
				'conditional'   => array( '_um_profile_secondary_btn', '=', 1 )
			),
			array(
				'id'            => '_um_profile_cover_enabled',
				'type'          => 'select',
				'label'         => __( 'Enable Cover Photos', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_cover_enabled', null, 1 ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 ),
				'options'       => array(
					0   => __( 'No', 'ultimate-member' ),
					1   => __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'            => '_um_profile_coversize',
				'type'          => 'select',
				'label'         => __( 'Cover Photo Size', 'ultimate-member' ),
				'tooltip'       => __( 'Set the profile photo size in pixels here', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_coversize', null, UM()->options()->get( 'profile_coversize' ) ),
				'options'       => UM()->files()->get_profile_photo_size( 'cover_thumb_sizes' ),
				'conditional'   => array( '_um_profile_cover_enabled', '=', 1 )
			),
			array(
				'id'            => '_um_profile_cover_ratio',
				'type'          => 'select',
				'label'         => __( 'Cover photo ratio', 'ultimate-member' ),
				'tooltip'       => __( 'The shortcode is centered by default unless you specify otherwise here', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_cover_ratio', null, UM()->options()->get( 'profile_cover_ratio' ) ),
				'options'       => array(
					'1.6:1' => '1.6:1',
					'2.7:1' => '2.7:1',
					'2.2:1' => '2.2:1',
					'3.2:1' => '3.2:1'
				),
				'conditional'   => array( '_um_profile_cover_enabled', '=', 1 )
			),
			array(
				'id'            => '_um_profile_disable_photo_upload',
				'type'          => 'select',
				'label'         => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
				'tooltip'       => __( 'Switch on/off the profile photo uploader', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_disable_photo_upload', null, UM()->options()->get( 'disable_profile_photo_upload' ) ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 ),
				'options'       => array(
					0   => __( 'No', 'ultimate-member' ),
					1   => __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'            => '_um_profile_photosize',
				'type'          => 'select',
				'label'         => __( 'Profile Photo Size', 'ultimate-member' ),
				'tooltip'       => __( 'Set the profile photo size in pixels here', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_photosize', null, UM()->options()->get( 'profile_photosize' ) ),
				'options'       => UM()->files()->get_profile_photo_size( 'photo_thumb_sizes' ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 )
			),
			array(
				'id'            => '_um_profile_photo_required',
				'type'          => 'select',
				'label'         => __( 'Make Profile Photo Required', 'ultimate-member' ),
				'tooltip'       => __( 'Require user to update a profile photo when updating their profile', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_photo_required' ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 ),
				'options'       => array(
					0   => __( 'No', 'ultimate-member' ),
					1   => __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'            => '_um_profile_show_name',
				'type'          => 'select',
				'label'         => __( 'Show display name in profile header?', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_show_name', null, 1 ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 ),
				'options'       => array(
					0   => __( 'No', 'ultimate-member' ),
					1   => __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'            => '_um_profile_show_social_links',
				'type'          => 'select',
				'label'         => __( 'Show social links in profile header?', 'ultimate-member' ),
				'value'         => UM()->query()->get_meta_value( '_um_profile_show_social_links', null, UM()->options()->get( 'profile_show_social_links' ) ),
				'conditional'   => array( '_um_profile_use_custom_settings', '=', 1 ),
				'options'       => array(
					0   => __( 'No', 'ultimate-member' ),
					1   => __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'		    => '_um_profile_show_bio',
				'type'		    => 'select',
				'label'    		=> __( 'Show user description in profile header?', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_profile_show_bio', null, 1 ),
				'conditional'	=> array( '_um_profile_use_custom_settings', '=', 1 ),
				'options'		=> array(
					0	=> __( 'No', 'ultimate-member' ),
					1	=> __( 'Yes', 'ultimate-member' ),
				),
			),

		)
	) )->render_form(); ?>

	<div class="um-admin-clear"></div>
</div>