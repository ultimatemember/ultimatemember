<?php

class UM_Builtin {

	function __construct() {

		add_action('init',  array(&$this, 'set_core_fields'), 1);
		
		add_action('init',  array(&$this, 'set_predefined_fields'), 1);
		
		add_action('init',  array(&$this, 'set_custom_fields'), 1);
		
		$this->saved_fields = get_option('um_fields');

	}
	
	/***
	***	@regular or multi-select/options
	***/
	function is_dropdown_field( $field ) {
		$fields = $this->all_user_fields;
		if ( isset($fields[$field]['options']) )
			return true;
		return false;
	}
	
	/***
	***	@get specific fields
	***/
	function get_specific_fields( $fields ) {
		$fields = explode(',', $fields);
		$array=array();
		foreach ($fields as $field ) {
			if ( isset( $this->predefined_fields[$field] ) ) {
				$array[$field] = $this->predefined_fields[$field];
			}
		}
		return $array;
	}
	
	/***
	***	@get specific field
	***/
	function get_specific_field( $fields ) {
		$fields = explode(',', $fields);
		$array=array();
		foreach ($fields as $field ) {
			if ( isset( $this->predefined_fields[$field] ) ) {
				$array = $this->predefined_fields[$field];
			}
		}
		return $array;
	}
	
	/***
	***	@Checks for a unique field error
	***/
	function unique_field_err( $key ){
		global $ultimatemember;
		if ( empty( $key ) ) return 'Please provide a meta key';
		if ( isset( $this->core_fields[ $key ] ) ) return 'Your meta key is a reserved core field and cannot be used';
		if ( isset( $this->predefined_fields[ $key ] ) ) return 'Your meta key is a predefined reserved key and cannot be used';
		if ( isset( $this->saved_fields[ $key ] ) ) return 'Your meta key already exists in your fields list';
		if ( !$ultimatemember->validation->safe_string( $key ) ) return 'Your meta key contains illegal characters. Please correct it.';
		return 0;
	}
	
	/***
	***	@check date range errors (start date)
	***/
	function date_range_start_err( $date ) {
		global $ultimatemember;
		if ( empty( $date ) ) return 'Please provide a date range beginning';
		if ( !$ultimatemember->validation->validate_date( $date ) ) return 'Please enter a valid start date in the date range';
		return 0;
	}
	
	/***
	***	@check date range errors (end date)
	***/
	function date_range_end_err( $date, $start_date ) {
		global $ultimatemember;
		if ( empty( $date ) ) return 'Please provide a date range end';
		if ( !$ultimatemember->validation->validate_date( $date ) ) return 'Please enter a valid end date in the date range';
		if ( strtotime( $date ) <= strtotime( $start_date ) ) return 'The end of date range must be greater than the start of date range';
		return 0;
	}
	
	/***
	***	@Get a core field attrs
	***/
	function get_core_field_attrs( $type ) {
		return ( isset( $this->core_fields[$type] ) ) ? $this->core_fields[$type] : array('');
	}
	
	/***
	***	@Core Fields
	***/
	function set_core_fields(){
	
		$this->core_fields = array(
		
			'row' => array(
				'name' => 'Row',
				'in_fields' => false,
				'form_only' => true,
				'conditional_support' => 0,
				'icon' => 'um-faicon-pencil',
				'col1' => array('_id','_background','_text_color','_padding','_margin','_border','_borderradius','_borderstyle','_bordercolor'),
				'col2' => array('_heading','_heading_text','_heading_background_color','_heading_text_color','_icon','_icon_color','_css_class'),
			),
			
			'text' => array(
				'name' => 'Text Box',
				'col1' => array('_title','_metakey','_help','_default','_min_chars','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate','_max_chars'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'textarea' => array(
				'name' => 'Textarea',
				'col1' => array('_title','_metakey','_help','_height','_max_chars','_max_words','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_default','_html'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'select' => array(
				'name' => 'Dropdown',
				'col1' => array('_title','_metakey','_help','_default','_options','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => 'You have not added any choices yet.'
					),
				)
			),
			
			'multiselect' => array(
				'name' => 'Multi-Select',
				'col1' => array('_title','_metakey','_help','_default','_options','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_min_selections','_max_selections'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => 'You have not added any choices yet.'
					),
				)
			),
			
			'radio' => array(
				'name' => 'Radio',
				'col1' => array('_title','_metakey','_help','_default','_options','_visibility'),
				'col2' => array('_label','_public','_roles'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => 'You have not added any choices yet.'
					),
				)
			),
			
			'checkbox' => array(
				'name' => 'Checkbox',
				'col1' => array('_title','_metakey','_help','_default','_options','_visibility'),
				'col2' => array('_label','_public','_roles','_max_selections'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => 'You have not added any choices yet.'
					),
				)
			),
			
			'url' => array(
				'name' => 'URL',
				'col1' => array('_title','_metakey','_help','_default','_url_text','_visibility'),
				'col2' => array('_label','_placeholder','_url_target','_url_rel','_public','_roles','_validate','_custom_validate'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'password' => array(
				'name' => 'Password',
				'col1' => array('_title','_metakey','_help','_min_chars','_max_chars','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_force_good_pass','_force_confirm_pass'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'image' => array(
				'name' => 'Image Upload',
				'col1' => array('_title','_metakey','_help','_allowed_types','_max_size','_crop','_visibility'),
				'col2' => array('_label','_public','_roles','_upload_text','_upload_help_text','_button_text'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_max_size' => array(
						'mode' => 'numeric',
						'error' => 'Please enter a valid size'
					),
				)
			),
			
			'file' => array(
				'name' => 'File Upload',
				'col1' => array('_title','_metakey','_help','_allowed_types','_max_size','_visibility'),
				'col2' => array('_label','_public','_roles','_upload_text','_upload_help_text','_button_text'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_max_size' => array(
						'mode' => 'numeric',
						'error' => 'Please enter a valid size'
					),
				)
			),
			
			'date' => array(
				'name' => 'Date Picker',
				'col1' => array('_title','_metakey','_help','_range','_years','_years_x','_range_start','_range_end','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_format','_pretty_format','_disabled_weekdays'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_years' => array(
						'mode' => 'numeric',
						'error' => 'Number of years is not valid'
					),
					'_range_start' => array(
						'mode' => 'range-start',
					),
					'_range_end' => array(
						'mode' => 'range-end',
					),
				)
			),
			
			'time' => array(
				'name' => 'Time Picker',
				'col1' => array('_title','_metakey','_help','_format','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_intervals'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'rating' => array(
				'name' => 'Rating',
				'col1' => array('_title','_metakey','_help','_visibility'),
				'col2' => array('_label','_public','_roles','_number','_default'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'block' => array(
				'name' => 'Content Block',
				'col1' => array('_title','_visibility'),
				'col2' => array('_public','_roles'),
				'col_full' => array('_content'),
				'mce_content' => true,
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
				)
			),
			
			'shortcode' => array(
				'name' => 'Shortcode',
				'col1' => array('_title','_visibility'),
				'col2' => array('_public','_roles'),
				'col_full' => array('_content'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_content' => array(
						'mode' => 'required',
						'error' => 'You must add a shortcode to the content area'
					),
				)
			),
			
			'spacing' => array(
				'name' => 'Spacing',
				'col1' => array('_title','_visibility'),
				'col2' => array('_spacing'),
				'form_only' => true,
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
				)
			),
			
			'divider' => array(
				'name' => 'Divider',
				'col1' => array('_title','_width','_divider_text','_visibility'),
				'col2' => array('_style','_color'),
				'form_only' => true,
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
				)
			),
			
			/*'group' => array(
				'name' => 'Field Group',
				'col1' => array('_title','_max_entries'),
				'col2' => array('_label','_public','_roles'),
				'form_only' => true,
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => 'You must provide a title'
					),
					'_label' => array(
						'mode' => 'required',
						'error' => 'You must provide a label'
					),
				)
			),*/
		
		);
		
		$this->core_fields = apply_filters('um_core_fields_hook', $this->core_fields );
	
	}
	
	/***
	***	@Predefined Fields
	***/
	function set_predefined_fields(){
	
		global $ultimatemember;
		
		$this->predefined_fields = array(
		
			'user_login' => array(
				'title' => __('Username','ultimatemember'),
				'metakey' => 'user_login',
				'type' => 'text',
				'label' => __('Username','ultimatemember'),
				'required' => 1,
				'public' => 1,
				'editable' => 0,
				'validate' => 'unique_username',
				'min_chars' => 3,
				'max_chars' => 24
			),
			
			'username' => array(
				'title' => __('Username or E-mail','ultimatemember'),
				'metakey' => 'username',
				'type' => 'text',
				'label' => __('Username or E-mail','ultimatemember'),
				'required' => 1,
				'public' => 1,
				'editable' => 0,
				'validate' => 'unique_username_or_email',
			),
			
			'user_password' => array(
				'title' => __('Password','ultimatemember'),
				'metakey' => 'user_password',
				'type' => 'password',
				'label' => __('Password','ultimatemember'),
				'required' => 1,
				'public' => 1,
				'editable' => 1,
				'min_chars' => 8,
				'max_chars' => 30,
				'force_good_pass' => 1,
				'force_confirm_pass' => 1,
			),
			
			'first_name' => array(
				'title' => __('First Name','ultimatemember'),
				'metakey' => 'first_name',
				'type' => 'text',
				'label' => __('First Name','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
			),
			
			'last_name' => array(
				'title' => __('Last Name','ultimatemember'),
				'metakey' => 'last_name',
				'type' => 'text',
				'label' => __('Last Name','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
			),
			
			'display_name' => array(
				'title' => __('Display Name','ultimatemember'),
				'metakey' => 'display_name',
				'type' => 'text',
				'label' => __('Display Name','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
			),
			
			'user_email' => array(
				'title' => __('E-mail Address','ultimatemember'),
				'metakey' => 'user_email',
				'type' => 'text',
				'label' => __('E-mail Address','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'validate' => 'unique_email',
			),
			
			'description' => array(
				'title' => __('Biography','ultimatemember'),
				'metakey' => 'description',
				'type' => 'textarea',
				'label' => __('Biography','ultimatemember'),
				'html' => 0,
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'max_words' => 40,
				'placeholder' => 'Enter a bit about yourself...',
			),
			
			'birth_date' => array(
				'title' => __('Birth Date','ultimatemember'),
				'metakey' => 'birth_date',
				'type' => 'date',
				'label' => __('Birth Date','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'pretty_format' => 1,
				'years' => 115,
				'years_x' => 'past',
				'icon' => 'um-faicon-calendar'
			),
			
			'gender' => array(
				'title' => __('Gender','ultimatemember'),
				'metakey' => 'gender',
				'type' => 'radio',
				'label' => __('Gender','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'options' => array( __('Male','ultimatemember'), __('Female','ultimatemember') )
			),
			
			'country' => array(
				'title' => __('Countries','ultimatemember'),
				'metakey' => 'country',
				'type' => 'select',
				'label' => __('Country','ultimatemember'),
				'placeholder' => __('Choose a Country','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'options' => $this->get('countries')
			),
			
			'facebook' => array(
				'title' => __('Facebook','ultimatemember'),
				'metakey' => 'facebook',
				'type' => 'url',
				'label' => __('Facebook','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-facebook',
				'validate' => 'facebook_url',
				'url_text' => 'Facebook',
				'advanced' => 'social',
				'color' => '#3B5999',
				'match' => 'https://facebook.com/',
			),
			
			'twitter' => array(
				'title' => __('Twitter','ultimatemember'),
				'metakey' => 'twitter',
				'type' => 'url',
				'label' => __('Twitter','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-twitter',
				'validate' => 'twitter_url',
				'url_text' => 'Twitter',
				'advanced' => 'social',
				'color' => '#4099FF',
				'match' => 'https://twitter.com/',
			),
			
			'linkedin' => array(
				'title' => __('LinkedIn','ultimatemember'),
				'metakey' => 'linkedin',
				'type' => 'url',
				'label' => __('LinkedIn','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-linkedin',
				'validate' => 'linkedin_url',
				'url_text' => 'LinkedIn',
				'advanced' => 'social',
				'color' => '#0976b4',
				'match' => 'https://linkedin.com/in/',
			),
			
			'googleplus' => array(
				'title' => __('Google+','ultimatemember'),
				'metakey' => 'googleplus',
				'type' => 'url',
				'label' => __('Google+','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-google-plus',
				'validate' => 'google_url',
				'url_text' => 'Google+',
				'advanced' => 'social',
				'color' => '#dd4b39',
				'match' => 'https://google.com/+',
			),
			
			'instagram' => array(
				'title' => __('Instagram','ultimatemember'),
				'metakey' => 'instagram',
				'type' => 'url',
				'label' => __('Instagram','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-instagram',
				'validate' => 'instagram_url',
				'url_text' => 'Instagram',
				'advanced' => 'social',
				'color' => '#3f729b',
				'match' => 'https://instagram.com/',
			),
			
			'skype' => array(
				'title' => __('Skype ID','ultimatemember'),
				'metakey' => 'skype',
				'type' => 'url',
				'label' => __('Skype ID','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-skype',
				'validate' => 'skype',
				'url_text' => 'Skype',
			),
			
			'youtube' => array(
				'title' => __('YouTube','ultimatemember'),
				'metakey' => 'youtube',
				'type' => 'url',
				'label' => __('YouTube','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-youtube',
				'validate' => 'youtube_url',
				'url_text' => 'YouTube',
				'advanced' => 'social',
				'color' => '#e52d27',
				'match' => 'https://youtube.com/',
			),
			
			'soundcloud' => array(
				'title' => __('SoundCloud','ultimatemember'),
				'metakey' => 'soundcloud',
				'type' => 'url',
				'label' => __('SoundCloud','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-soundcloud',
				'validate' => 'soundcloud_url',
				'url_text' => 'SoundCloud',
				'advanced' => 'social',
				'color' => '#f50',
				'match' => 'https://soundcloud.com/',
			),
			
			'role_select' => array(
				'title' => __('Roles (Dropdown)','ultimatemember'),
				'metakey' => 'role_select',
				'type' => 'select',
				'label' => __('Account Type','ultimatemember'),
				'placeholder' => 'Choose account type',
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'options' => $ultimatemember->query->get_roles( false, array('admin') ),
			),
			
			'role_radio' => array(
				'title' => __('Roles (Radio)','ultimatemember'),
				'metakey' => 'role_radio',
				'type' => 'radio',
				'label' => __('Account Type','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'options' => $ultimatemember->query->get_roles( false, array('admin') ),
			),
			
			'languages' => array(
				'title' => __('Languages','ultimatemember'),
				'metakey' => 'languages',
				'type' => 'multiselect',
				'label' => __('Languages Spoken','ultimatemember'),
				'placeholder' => __('Select languages','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'options' => $this->get('languages'),
			),
			
			'phone_number' => array(
				'title' => __('Phone Number','ultimatemember'),
				'metakey' => 'phone_number',
				'type' => 'text',
				'label' => __('Phone Number','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'validate' => 'phone_number',
				'icon' => 'um-faicon-phone',
			),
			
			'mobile_number' => array(
				'title' => __('Mobile Number','ultimatemember'),
				'metakey' => 'mobile_number',
				'type' => 'text',
				'label' => __('Mobile Number','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'validate' => 'phone_number',
				'icon' => 'um-faicon-mobile',
			),
			
			// private use ( not public list )
		
			'profile_photo' => array(
				'title' => __('Profile Photo','ultimatemember'),
				'metakey' => 'profile_photo',
				'type' => 'image',
				'label' => __('Change your profile photo','ultimatemember'),
				'upload_text' => __('Upload your photo here','ultimatemember'),
				'icon' => 'um-faicon-camera',
				'crop' => 1,
				'min_width' => str_replace('px','',um_get_option('profile_photosize')),
				'min_height' => str_replace('px','',um_get_option('profile_photosize')),
				'private_use' => true,
			),
			
			'cover_photo' => array(
				'title' => __('Cover Photo','ultimatemember'),
				'metakey' => 'cover_photo',
				'type' => 'image',
				'label' => __('Change your cover photo','ultimatemember'),
				'upload_text' => __('Upload profile cover here','ultimatemember'),
				'icon' => 'um-faicon-picture-o',
				'crop' => 2,
				'modal_size' => 'large',
				'ratio' => str_replace(':1','',um_get_option('profile_cover_ratio')),
				'min_width' => um_get_option('cover_min_width'),
				'private_use' => true,
			),
			
			'password_reset_text' => array(
				'title' => __('Password Reset','ultimatemember'),
				'type' => 'block',
				'content' => '<div style="text-align:center">' . __('To reset your password, please enter your email address or username below','ultimatemember'). '</div>',
				'private_use' => true,
			),
			
			'username_b' => array(
				'title' => __('Username or E-mail','ultimatemember'),
				'metakey' => 'username_b',
				'type' => 'text',
				'placeholder' => __('Enter your username or email','ultimatemember'),
				'required' => 1,
				'public' => 1,
				'editable' => 0,
				'private_use' => true,
			),
			
			// account page use ( not public )
			
			'profile_privacy' => array(
				'title' => __('Profile Privacy','ultimatemember'),
				'metakey' => 'profile_privacy',
				'type' => 'select',
				'label' => __('Profile Privacy','ultimatemember'),
				'help' => __('Who can see your public profile?','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'default' => __('Everyone','ultimatemember'),
				'options' => array( __('Everyone','ultimatemember'), __('Only me','ultimatemember') ),
				'allowclear' => 0,
				'account_only' => true,
				'required_perm' => 'can_make_private_profile',
			),
			
			'hide_in_members' => array(
				'title' => __('Hide my profile from directory','ultimatemember'),
				'metakey' => 'hide_in_members',
				'type' => 'radio',
				'label' => __('Hide my profile from directory','ultimatemember'),
				'help' => __('Here you can hide yourself from appearing in public directory','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'default' => __('No','ultimatemember'),
				'options' => array( __('No','ultimatemember'), __('Yes','ultimatemember') ),
				'account_only' => true,
				'required_opt' => array( 'members_page', 1 ),
			),
			
			'delete_account' => array(
				'title' => __('Delete Account','ultimatemember'),
				'metakey' => 'delete_account',
				'type' => 'radio',
				'label' => __('Delete Account','ultimatemember'),
				'help' => __('If you confirm, everything related to your profile will be deleted permanently from the site','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'default' => __('No','ultimatemember'),
				'options' => array( __('Yes','ultimatemember') , __('No','ultimatemember') ),
				'account_only' => true,
			),
			
			'single_user_password' => array(
				'title' => __('Password','ultimatemember'),
				'metakey' => 'single_user_password',
				'type' => 'password',
				'label' => __('Password','ultimatemember'),
				'required' => 1,
				'public' => 1,
				'editable' => 1,
				'account_only' => true,
			),
			
		);
		
		$this->predefined_fields = apply_filters('um_predefined_fields_hook', $this->predefined_fields );
	
	}
	
	/***
	***	@Custom Fields
	***/
	function set_custom_fields(){

		if ( is_array( $this->saved_fields ) ) {
			
			$this->custom_fields = $this->saved_fields;
		
		} else {
			
			$this->custom_fields = '';
		
		}

		$custom = $this->custom_fields;
		$predefined = $this->predefined_fields;

		if ( is_array( $custom ) ){
			$this->all_user_fields = array_merge( $predefined, $custom );
		} else {
			$this->all_user_fields = $predefined;
		}

	}
	
	/***
	***	@predefined + custom fields ( Global, not form wide )
	***/
	function all_user_fields( $exclude_types = null ) {
	
		global $ultimatemember;
		
		$this->fields_dropdown = array('image','file','password','textarea','rating','block','shortcode','spacing','divider','group');
		
		$custom = $this->custom_fields;
		$predefined = $this->predefined_fields;
		
		if ( $exclude_types ) {
			$exclude_types = explode(',', $exclude_types);
		}
		
		$all = array( '' => '' );
		
		if ( is_array( $custom ) ){
		$all = $all + array_merge( $predefined, $custom );
		} else {
			$all = $all + $predefined;
		}
		
		foreach( $all as $k => $arr ) {
			
			if ( isset( $arr['title'] ) ){
				$all[$k]['title'] = stripslashes( $arr['title'] );
			}
			
			if ( $exclude_types && isset( $arr['type'] ) && in_array( $arr['type'], $exclude_types ) ) {
				unset( $all[$k] );
			}
			if ( isset( $arr['account_only'] ) || isset( $arr['private_use'] ) ) {
				unset( $all[$k] );
			}
			if ( isset( $arr['type'] ) && in_array( $arr['type'], $this->fields_dropdown ) ) {
				unset( $all[$k] );
			}
		}
		
		$all = $ultimatemember->fields->array_sort_by_column( $all, 'title');

		return $all;
	}
	
	/***
	***	@Possible validation types for fields
	***/
	function validation_types(){
	
		$array[0] = 'None';
		$array['alphabetic'] = 'Alphabetic value only';
		$array['alpha_numeric'] = 'Alpha-numeric value';
		$array['english'] = 'English letters only';
		$array['facebook_url'] = 'Facebook URL';
		$array['google_url'] = 'Google+ URL';
		$array['instagram_url'] = 'Instagram URL';
		$array['linkedin_url'] = 'LinkedIn URL';
		$array['lowercase'] = 'Lowercase only';
		$array['numeric'] = 'Numeric value only';
		$array['phone_number'] = 'Phone Number';
		$array['skype'] = 'Skype ID';
		$array['soundcloud'] = 'SoundCloud Profile';
		$array['twitter_url'] = 'Twitter URL';
		$array['unique_email'] = 'Unique E-mail';
		$array['unique_value'] = 'Unique Metakey value';
		$array['unique_username'] = 'Unique Username';
		$array['unique_username_or_email'] = 'Unique Username/E-mail';
		$array['url'] = 'Website URL';
		$array['youtube_url'] = 'YouTube Profile';
		$array['custom'] = 'Custom Validation';
		
		$array = apply_filters('um_admin_field_validation_hook', $array );
		return $array;
	}
	
	/***
	***	@Get predefined options
	***/
	function get( $data ){
		switch($data) {
		
			case 'languages':
				$array = array(
							"aa" => "Afar",
							 "ab" => "Abkhazian",
							 "ae" => "Avestan",
							 "af" => "Afrikaans",
							 "ak" => "Akan",
							 "am" => "Amharic",
							 "an" => "Aragonese",
							 "ar" => "Arabic",
							 "as" => "Assamese",
							 "av" => "Avaric",
							 "ay" => "Aymara",
							 "az" => "Azerbaijani",
							 "ba" => "Bashkir",
							 "be" => "Belarusian",
							 "bg" => "Bulgarian",
							 "bh" => "Bihari",
							 "bi" => "Bislama",
							 "bm" => "Bambara",
							 "bn" => "Bengali",
							 "bo" => "Tibetan",
							 "br" => "Breton",
							 "bs" => "Bosnian",
							 "ca" => "Catalan",
							 "ce" => "Chechen",
							 "ch" => "Chamorro",
							 "co" => "Corsican",
							 "cr" => "Cree",
							 "cs" => "Czech",
							 "cu" => "Church Slavic",
							 "cv" => "Chuvash",
							 "cy" => "Welsh",
							 "da" => "Danish",
							 "de" => "German",
							 "dv" => "Divehi",
							 "dz" => "Dzongkha",
							 "ee" => "Ewe",
							 "el" => "Greek",
							 "en" => "English",
							 "eo" => "Esperanto",
							 "es" => "Spanish",
							 "et" => "Estonian",
							 "eu" => "Basque",
							 "fa" => "Persian",
							 "ff" => "Fulah",
							 "fi" => "Finnish",
							 "fj" => "Fijian",
							 "fo" => "Faroese",
							 "fr" => "French",
							 "fy" => "Western Frisian",
							 "ga" => "Irish",
							 "gd" => "Scottish Gaelic",
							 "gl" => "Galician",
							 "gn" => "Guarani",
							 "gu" => "Gujarati",
							 "gv" => "Manx",
							 "ha" => "Hausa",
							 "he" => "Hebrew",
							 "hi" => "Hindi",
							 "ho" => "Hiri Motu",
							 "hr" => "Croatian",
							 "ht" => "Haitian",
							 "hu" => "Hungarian",
							 "hy" => "Armenian",
							 "hz" => "Herero",
							 "ia" => "Interlingua (International Auxiliary Language Association)",
							 "id" => "Indonesian",
							 "ie" => "Interlingue",
							 "ig" => "Igbo",
							 "ii" => "Sichuan Yi",
							 "ik" => "Inupiaq",
							 "io" => "Ido",
							 "is" => "Icelandic",
							 "it" => "Italian",
							 "iu" => "Inuktitut",
							 "ja" => "Japanese",
							 "jv" => "Javanese",
							 "ka" => "Georgian",
							 "kg" => "Kongo",
							 "ki" => "Kikuyu",
							 "kj" => "Kwanyama",
							 "kk" => "Kazakh",
							 "kl" => "Kalaallisut",
							 "km" => "Khmer",
							 "kn" => "Kannada",
							 "ko" => "Korean",
							 "kr" => "Kanuri",
							 "ks" => "Kashmiri",
							 "ku" => "Kurdish",
							 "kv" => "Komi",
							 "kw" => "Cornish",
							 "ky" => "Kirghiz",
							 "la" => "Latin",
							 "lb" => "Luxembourgish",
							 "lg" => "Ganda",
							 "li" => "Limburgish",
							 "ln" => "Lingala",
							 "lo" => "Lao",
							 "lt" => "Lithuanian",
							 "lu" => "Luba-Katanga",
							 "lv" => "Latvian",
							 "mg" => "Malagasy",
							 "mh" => "Marshallese",
							 "mi" => "Maori",
							 "mk" => "Macedonian",
							 "ml" => "Malayalam",
							 "mn" => "Mongolian",
							 "mr" => "Marathi",
							 "ms" => "Malay",
							 "mt" => "Maltese",
							 "my" => "Burmese",
							 "na" => "Nauru",
							 "nb" => "Norwegian Bokmal",
							 "nd" => "North Ndebele",
							 "ne" => "Nepali",
							 "ng" => "Ndonga",
							 "nl" => "Dutch",
							 "nn" => "Norwegian Nynorsk",
							 "no" => "Norwegian",
							 "nr" => "South Ndebele",
							 "nv" => "Navajo",
							 "ny" => "Chichewa",
							 "oc" => "Occitan",
							 "oj" => "Ojibwa",
							 "om" => "Oromo",
							 "or" => "Oriya",
							 "os" => "Ossetian",
							 "pa" => "Panjabi",
							 "pi" => "Pali",
							 "pl" => "Polish",
							 "ps" => "Pashto",
							 "pt" => "Portuguese",
							 "qu" => "Quechua",
							 "rm" => "Raeto-Romance",
							 "rn" => "Kirundi",
							 "ro" => "Romanian",
							 "ru" => "Russian",
							 "rw" => "Kinyarwanda",
							 "sa" => "Sanskrit",
							 "sc" => "Sardinian",
							 "sd" => "Sindhi",
							 "se" => "Northern Sami",
							 "sg" => "Sango",
							 "si" => "Sinhala",
							 "sk" => "Slovak",
							 "sl" => "Slovenian",
							 "sm" => "Samoan",
							 "sn" => "Shona",
							 "so" => "Somali",
							 "sq" => "Albanian",
							 "sr" => "Serbian",
							 "ss" => "Swati",
							 "st" => "Southern Sotho",
							 "su" => "Sundanese",
							 "sv" => "Swedish",
							 "sw" => "Swahili",
							 "ta" => "Tamil",
							 "te" => "Telugu",
							 "tg" => "Tajik",
							 "th" => "Thai",
							 "ti" => "Tigrinya",
							 "tk" => "Turkmen",
							 "tl" => "Tagalog",
							 "tn" => "Tswana",
							 "to" => "Tonga",
							 "tr" => "Turkish",
							 "ts" => "Tsonga",
							 "tt" => "Tatar",
							 "tw" => "Twi",
							 "ty" => "Tahitian",
							 "ug" => "Uighur",
							 "uk" => "Ukrainian",
							 "ur" => "Urdu",
							 "uz" => "Uzbek",
							 "ve" => "Venda",
							 "vi" => "Vietnamese",
							 "vo" => "Volapuk",
							 "wa" => "Walloon",
							 "wo" => "Wolof",
							 "xh" => "Xhosa",
							 "yi" => "Yiddish",
							 "yo" => "Yoruba",
							 "za" => "Zhuang",
							 "zh" => "Chinese",
							 "zu" => "Zulu"
			);
			break;

			case 'countries':
				$array = array (
							'AF' => 'Afghanistan',
							'AX' => 'Åland Islands',
							'AL' => 'Albania',
							'DZ' => 'Algeria',
							'AS' => 'American Samoa',
							'AD' => 'Andorra',
							'AO' => 'Angola',
							'AI' => 'Anguilla',
							'AQ' => 'Antarctica',
							'AG' => 'Antigua and Barbuda',
							'AR' => 'Argentina',
							'AM' => 'Armenia',
							'AW' => 'Aruba',
							'AU' => 'Australia',
							'AT' => 'Austria',
							'AZ' => 'Azerbaijan',
							'BS' => 'Bahamas',
							'BH' => 'Bahrain',
							'BD' => 'Bangladesh',
							'BB' => 'Barbados',
							'BY' => 'Belarus',
							'BE' => 'Belgium',
							'BZ' => 'Belize',
							'BJ' => 'Benin',
							'BM' => 'Bermuda',
							'BT' => 'Bhutan',
							'BO' => 'Bolivia, Plurinational State of',
							'BA' => 'Bosnia and Herzegovina',
							'BW' => 'Botswana',
							'BV' => 'Bouvet Island',
							'BR' => 'Brazil',
							'IO' => 'British Indian Ocean Territory',
							'BN' => 'Brunei Darussalam',
							'BG' => 'Bulgaria',
							'BF' => 'Burkina Faso',
							'BI' => 'Burundi',
							'KH' => 'Cambodia',
							'CM' => 'Cameroon',
							'CA' => 'Canada',
							'CV' => 'Cape Verde',
							'KY' => 'Cayman Islands',
							'CF' => 'Central African Republic',
							'TD' => 'Chad',
							'CL' => 'Chile',
							'CN' => 'China',
							'CX' => 'Christmas Island',
							'CC' => 'Cocos (Keeling) Islands',
							'CO' => 'Colombia',
							'KM' => 'Comoros',
							'CG' => 'Congo',
							'CD' => 'Congo, the Democratic Republic of the',
							'CK' => 'Cook Islands',
							'CR' => 'Costa Rica',
							'CI' => "Côte d'Ivoire",
							'HR' => 'Croatia',
							'CU' => 'Cuba',
							'CY' => 'Cyprus',
							'CZ' => 'Czech Republic',
							'DK' => 'Denmark',
							'DJ' => 'Djibouti',
							'DM' => 'Dominica',
							'DO' => 'Dominican Republic',
							'EC' => 'Ecuador',
							'EG' => 'Egypt',
							'SV' => 'El Salvador',
							'GQ' => 'Equatorial Guinea',
							'ER' => 'Eritrea',
							'EE' => 'Estonia',
							'ET' => 'Ethiopia',
							'FK' => 'Falkland Islands (Malvinas)',
							'FO' => 'Faroe Islands',
							'FJ' => 'Fiji',
							'FI' => 'Finland',
							'FR' => 'France',
							'GF' => 'French Guiana',
							'PF' => 'French Polynesia',
							'TF' => 'French Southern Territories',
							'GA' => 'Gabon',
							'GM' => 'Gambia',
							'GE' => 'Georgia',
							'DE' => 'Germany',
							'GH' => 'Ghana',
							'GI' => 'Gibraltar',
							'GR' => 'Greece',
							'GL' => 'Greenland',
							'GD' => 'Grenada',
							'GP' => 'Guadeloupe',
							'GU' => 'Guam',
							'GT' => 'Guatemala',
							'GG' => 'Guernsey',
							'GN' => 'Guinea',
							'GW' => 'Guinea-Bissau',
							'GY' => 'Guyana',
							'HT' => 'Haiti',
							'HM' => 'Heard Island and McDonald Islands',
							'VA' => 'Holy See (Vatican City State)',
							'HN' => 'Honduras',
							'HK' => 'Hong Kong',
							'HU' => 'Hungary',
							'IS' => 'Iceland',
							'IN' => 'India',
							'ID' => 'Indonesia',
							'IR' => 'Iran, Islamic Republic of',
							'IQ' => 'Iraq',
							'IE' => 'Ireland',
							'IM' => 'Isle of Man',
							'IL' => 'Israel',
							'IT' => 'Italy',
							'JM' => 'Jamaica',
							'JP' => 'Japan',
							'JE' => 'Jersey',
							'JO' => 'Jordan',
							'KZ' => 'Kazakhstan',
							'KE' => 'Kenya',
							'KI' => 'Kiribati',
							'KP' => "Korea, Democratic People's Republic of",
							'KR' => 'Korea, Republic of',
							'KW' => 'Kuwait',
							'KG' => 'Kyrgyzstan',
							'LA' => "Lao People's Democratic Republic",
							'LV' => 'Latvia',
							'LB' => 'Lebanon',
							'LS' => 'Lesotho',
							'LR' => 'Liberia',
							'LY' => 'Libyan Arab Jamahiriya',
							'LI' => 'Liechtenstein',
							'LT' => 'Lithuania',
							'LU' => 'Luxembourg',
							'MO' => 'Macao',
							'MK' => 'Macedonia, the former Yugoslav Republic of',
							'MG' => 'Madagascar',
							'MW' => 'Malawi',
							'MY' => 'Malaysia',
							'MV' => 'Maldives',
							'ML' => 'Mali',
							'MT' => 'Malta',
							'MH' => 'Marshall Islands',
							'MQ' => 'Martinique',
							'MR' => 'Mauritania',
							'MU' => 'Mauritius',
							'YT' => 'Mayotte',
							'MX' => 'Mexico',
							'FM' => 'Micronesia, Federated States of',
							'MD' => 'Moldova, Republic of',
							'MC' => 'Monaco',
							'MN' => 'Mongolia',
							'ME' => 'Montenegro',
							'MS' => 'Montserrat',
							'MA' => 'Morocco',
							'MZ' => 'Mozambique',
							'MM' => 'Myanmar',
							'NA' => 'Namibia',
							'NR' => 'Nauru',
							'NP' => 'Nepal',
							'NL' => 'Netherlands',
							'AN' => 'Netherlands Antilles',
							'NC' => 'New Caledonia',
							'NZ' => 'New Zealand',
							'NI' => 'Nicaragua',
							'NE' => 'Niger',
							'NG' => 'Nigeria',
							'NU' => 'Niue',
							'NF' => 'Norfolk Island',
							'MP' => 'Northern Mariana Islands',
							'NO' => 'Norway',
							'OM' => 'Oman',
							'PK' => 'Pakistan',
							'PW' => 'Palau',
							'PS' => 'Palestine',
							'PA' => 'Panama',
							'PG' => 'Papua New Guinea',
							'PY' => 'Paraguay',
							'PE' => 'Peru',
							'PH' => 'Philippines',
							'PN' => 'Pitcairn',
							'PL' => 'Poland',
							'PT' => 'Portugal',
							'PR' => 'Puerto Rico',
							'QA' => 'Qatar',
							'RE' => 'Réunion',
							'RO' => 'Romania',
							'RU' => 'Russian Federation',
							'RW' => 'Rwanda',
							'BL' => 'Saint Barthélemy',
							'SH' => 'Saint Helena',
							'KN' => 'Saint Kitts and Nevis',
							'LC' => 'Saint Lucia',
							'MF' => 'Saint Martin (French part)',
							'PM' => 'Saint Pierre and Miquelon',
							'VC' => 'Saint Vincent and the Grenadines',
							'WS' => 'Samoa',
							'SM' => 'San Marino',
							'ST' => 'Sao Tome and Principe',
							'SA' => 'Saudi Arabia',
							'SN' => 'Senegal',
							'RS' => 'Serbia',
							'SC' => 'Seychelles',
							'SL' => 'Sierra Leone',
							'SG' => 'Singapore',
							'SK' => 'Slovakia',
							'SI' => 'Slovenia',
							'SB' => 'Solomon Islands',
							'SO' => 'Somalia',
							'ZA' => 'South Africa',
							'GS' => 'South Georgia and the South Sandwich Islands',
							'ES' => 'Spain',
							'LK' => 'Sri Lanka',
							'SD' => 'Sudan',
							'SR' => 'Suriname',
							'SJ' => 'Svalbard and Jan Mayen',
							'SZ' => 'Swaziland',
							'SE' => 'Sweden',
							'CH' => 'Switzerland',
							'SY' => 'Syrian Arab Republic',
							'TW' => 'Taiwan, Province of China',
							'TJ' => 'Tajikistan',
							'TZ' => 'Tanzania, United Republic of',
							'TH' => 'Thailand',
							'TL' => 'Timor-Leste',
							'TG' => 'Togo',
							'TK' => 'Tokelau',
							'TO' => 'Tonga',
							'TT' => 'Trinidad and Tobago',
							'TN' => 'Tunisia',
							'TR' => 'Turkey',
							'TM' => 'Turkmenistan',
							'TC' => 'Turks and Caicos Islands',
							'TV' => 'Tuvalu',
							'UG' => 'Uganda',
							'UA' => 'Ukraine',
							'AE' => 'United Arab Emirates',
							'GB' => 'United Kingdom',
							'US' => 'United States',
							'UM' => 'United States Minor Outlying Islands',
							'UY' => 'Uruguay',
							'UZ' => 'Uzbekistan',
							'VU' => 'Vanuatu',
							'VE' => 'Venezuela, Bolivarian Republic of',
							'VN' => 'Viet Nam',
							'VG' => 'Virgin Islands, British',
							'VI' => 'Virgin Islands, U.S.',
							'WF' => 'Wallis and Futuna',
							'EH' => 'Western Sahara',
							'YE' => 'Yemen',
							'ZM' => 'Zambia',
							'ZW' => 'Zimbabwe'
				);
				break;
	
		}
		
		$array = apply_filters("um_{$data}_predefined_field_options", $array);
		
		return $array;
		
	}

}