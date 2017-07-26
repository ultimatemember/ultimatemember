<div class="um-admin-metabox">
	<?php UM()->admin_forms( array(
		'class'		=> 'um-form-login-customize um-top-label',
		'prefix_id'	=> 'form',
		'fields' => array(
			array(
				'id'		    => '_um_login_use_globals',
				'type'		    => 'select',
				'label'    		=> __( 'Apply custom settings to this form', 'ultimate-member' ),
				'tooltip' 	=> __( 'Switch to yes if you want to customize this form settings, styling &amp; appearance', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_use_globals', null, 0 ),
				'options'		=> array(
					0	=> __( 'No', 'ultimate-member' ),
					1	=> __( 'Yes', 'ultimate-member' ),
				),
			),
			array(
				'id'		    => '_um_login_template',
				'type'		    => 'select',
				'label'    		=> __( 'Template', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_template', null, um_get_option( 'login_template' ) ),
				'options'		=> UM()->shortcodes()->get_templates( 'login' ),
				'conditional'	=> array( '_um_login_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_login_max_width',
				'type'		    => 'text',
				'label'    		=> __( 'Max. Width (px)', 'ultimate-member' ),
				'tooltip'    	=> __( 'The maximum width of shortcode in pixels e.g. 600px', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value('_um_login_max_width', null, um_get_option( 'login_max_width' ) ),
				'conditional'	=> array( '_um_login_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_login_icons',
				'type'		    => 'select',
				'label'    		=> __( 'Field Icons', 'ultimate-member' ),
				'tooltip'    	=> __( 'Whether to show field icons and where to show them relative to the field', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_icons', null, um_get_option( 'login_icons' ) ) ,
				'options'		=> array(
					'field' => __( 'Show inside text field', 'ultimate-member' ),
					'label' => __( 'Show with label', 'ultimate-member' ),
					'off' 	=> __( 'Turn off', 'ultimate-member' )
				),
				'conditional'	=> array( '_um_login_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_login_primary_btn_word',
				'type'		    => 'text',
				'label'    		=> __( 'Primary Button Text', 'ultimate-member' ),
				'tooltip'    	=> __( 'Customize the button text', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_primary_btn_word', null, um_get_option( 'login_primary_btn_word' ) ),
				'conditional'	=> array( '_um_login_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_login_secondary_btn',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Show Secondary Button', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_secondary_btn', null, 1 ),
				'conditional'	=> array( '_um_login_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_login_secondary_btn_word',
				'type'		    => 'text',
				'label'    		=> __( 'Primary Button Text', 'ultimate-member' ),
				'tooltip'    	=> __( 'Customize the button text', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_secondary_btn_word', null, um_get_option( 'login_secondary_btn_word' ) ),
				'conditional'	=> array( '_um_login_secondary_btn', '=', 1 )
			),
			array(
				'id'		    => '_um_login_forgot_pass_link',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Show Forgot Password Link?', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_forgot_pass_link', null, um_get_option('login_forgot_pass_link') ),
				'conditional'	=> array( '_um_login_use_globals', '=', 1 )
			),
			array(
				'id'		    => '_um_login_show_rememberme',
				'type'		    => 'checkbox',
				'label'    		=> __( 'Show "Remember Me"?', 'ultimate-member' ),
				'value' 		=> UM()->query()->get_meta_value( '_um_login_show_rememberme', null, um_get_option('login_show_rememberme') ),
				'conditional'	=> array( '_um_login_use_globals', '=', 1 )
			),
		)
	) )->render_form(); ?>

	<div class="um-admin-clear"></div>
</div>