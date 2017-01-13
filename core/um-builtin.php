<?php

class UM_Builtin {

	public $predefined_fields = array();

	function __construct() { 

		add_action('init',  array(&$this, 'set_core_fields'), 1);
		
		add_action('init',  array(&$this, 'set_predefined_fields'), 1);
		
		add_action('init',  array(&$this, 'set_custom_fields'), 1);
		
		$this->saved_fields = get_option('um_fields');

	}
	
	/***
	***	@regular or multi-select/options
	***/
	function is_dropdown_field( $field, $attrs ) {
		
		if ( isset( $attrs['options'] ) )
			return true;
		
		$fields = $this->all_user_fields;
		
		if ( isset($fields[$field]['options']) )
			return true;
		
		return false;
	}
	
	/***
	***	@get a field
	***/
	function get_a_field( $field ) {
		$fields = $this->all_user_fields;
		if ( isset( $fields[$field] ) ) {
			return $fields[$field];
		}
		return '';
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
			} else if ( isset( $this->saved_fields[$field] ) ) {
				$array = $this->saved_fields[$field];
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
		if ( isset( $this->core_fields[ $key ] ) ) return __('Your meta key is a reserved core field and cannot be used','ultimatemember');
		if ( isset( $this->predefined_fields[ $key ] ) ) return __('Your meta key is a predefined reserved key and cannot be used','ultimatemember');
		if ( isset( $this->saved_fields[ $key ] ) ) return __('Your meta key already exists in your fields list','ultimatemember');
		if ( !$ultimatemember->validation->safe_string( $key ) ) return __('Your meta key contains illegal characters. Please correct it.','ultimatemember');
		return 0;
	}
	
	/***
	***	@check date range errors (start date)
	***/
	function date_range_start_err( $date ) {
		global $ultimatemember;
		if ( empty( $date ) ) return __('Please provide a date range beginning','ultimatemember');
		if ( !$ultimatemember->validation->validate_date( $date ) ) return __('Please enter a valid start date in the date range','ultimatemember');
		return 0;
	}
	
	/***
	***	@check date range errors (end date)
	***/
	function date_range_end_err( $date, $start_date ) {
		global $ultimatemember;
		if ( empty( $date ) ) return __('Please provide a date range end','ultimatemember');
		if ( !$ultimatemember->validation->validate_date( $date ) ) return __('Please enter a valid end date in the date range','ultimatemember');
		if ( strtotime( $date ) <= strtotime( $start_date ) ) return __('The end of date range must be greater than the start of date range','ultimatemember');
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'number' => array(
				'name' => __('Number','ultimatemember'),
				'col1' => array('_title','_metakey','_help','_default','_min','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate','_max'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => __('You must provide a title','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'select' => array(
				'name' => 'Dropdown',
				'col1' => array('_title','_metakey','_help','_default','_options','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_custom_dropdown_options_source','_parent_dropdown_relationship'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required', 
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => __('You have not added any choices yet.','ultimatemember')
					),
				)
			),
			
			'multiselect' => array(
				'name' => 'Multi-Select',
				'col1' => array('_title','_metakey','_help','_default','_options','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_min_selections','_max_selections','_custom_dropdown_options_source'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => __('You have not added any choices yet.','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => __('You have not added any choices yet.','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_options' => array(
						'mode' => 'required',
						'error' => __('You have not added any choices yet.','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_max_size' => array(
						'mode' => 'numeric',
						'error' => __('Please enter a valid size','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_max_size' => array(
						'mode' => 'numeric',
						'error' => __('Please enter a valid size','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
					'_years' => array(
						'mode' => 'numeric',
						'error' => __('Number of years is not valid','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
					'_content' => array(
						'mode' => 'required',
						'error' => __('You must add a shortcode to the content area','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
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
						'error' => __('You must provide a title','ultimatemember')
					),
				)
			),
			
			'googlemap' => array(
				'name' => 'Google Map',
				'col1' => array('_title','_metakey','_help','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'youtube_video' => array(
				'name' => 'YouTube Video',
				'col1' => array('_title','_metakey','_help','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'vimeo_video' => array(
				'name' => 'Vimeo Video',
				'col1' => array('_title','_metakey','_help','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
					),
				)
			),
			
			'soundcloud_track' => array(
				'name' => 'SoundCloud Track',
				'col1' => array('_title','_metakey','_help','_visibility'),
				'col2' => array('_label','_placeholder','_public','_roles','_validate','_custom_validate'),
				'col3' => array('_required','_editable','_icon'),
				'validate' => array(
					'_title' => array(
						'mode' => 'required',
						'error' => __('You must provide a title','ultimatemember')
					),
					'_metakey' => array(
						'mode' => 'unique',
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
		
		if ( !isset( $ultimatemember->query ) || ! method_exists( $ultimatemember->query, 'get_roles' ) ) {
			return;
		} else {
			//die('Method loaded!');
		}
		
		$um_roles = $ultimatemember->query->get_roles( false, array('admin') );
		
		$profile_privacy = apply_filters('um_profile_privacy_options', array( __('Everyone','ultimatemember'), __('Only me','ultimatemember') ) );
		
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
			
			'nickname' => array(
				'title' => __('Nickname','ultimatemember'),
				'metakey' => 'nickname',
				'type' => 'text',
				'label' => __('Nickname','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
			),

			'user_registered' => array(
				'title' => __('Registration Date','ultimatemember'),
				'metakey' => 'user_registered',
				'type' => 'text',
				'label' => __('Registration Date','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'edit_forbidden' => 1,
			),
			
			'last_login' => array(
				'title' => __('Last Login','ultimatemember'),
				'metakey' => '_um_last_login',
				'type' => 'text',
				'label' => __('Last Login','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'edit_forbidden' => 1,
			),
			
			'user_email' => array(
				'title' => __('E-mail Address','ultimatemember'),
				'metakey' => 'user_email',
				'type' => 'text',
				'label' => __('E-mail Address','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'validate' => 'unique_email',
				'autocomplete' => 'off'
			),

			'secondary_user_email' => array(
				'title' => __('Secondary E-mail Address','ultimatemember'),
				'metakey' => 'secondary_user_email',
				'type' => 'text',
				'label' => __('Secondary E-mail Address','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'validate' => 'unique_email',
				'autocomplete' => 'off'
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
				'title' => __('Country','ultimatemember'),
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

			'vk' => array(
				'title' => __('VKontakte','ultimatemember'),
				'metakey' => 'vkontakte',
				'type' => 'url',
				'label' => __('VKontakte','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'url_target' => '_blank',
				'url_rel' => 'nofollow',
				'icon' => 'um-faicon-vk',
				'validate' => 'vk_url',
				'url_text' => 'VKontakte',
				'advanced' => 'social',
				'color' => '#2B587A',
				'match' => 'https://vk.com/',
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
				'options' => $um_roles,
			),
			
			'role_radio' => array(
				'title' => __('Roles (Radio)','ultimatemember'),
				'metakey' => 'role_radio',
				'type' => 'radio',
				'label' => __('Account Type','ultimatemember'),
				'required' => 0,
				'public' => 1,
				'editable' => 1,
				'options' => $um_roles,
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
				'max_size' => ( um_get_option('profile_photo_max_size') ) ? um_get_option('profile_photo_max_size') : 999999999,
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
				'max_size' => ( um_get_option('cover_photo_max_size') ) ? um_get_option('cover_photo_max_size') : 999999999,
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
				'options' => $profile_privacy,
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
	***	@may be used to show a dropdown, or source for user meta
	***/
	function all_user_fields( $exclude_types = null, $show_all = false ) {
	
		global $ultimatemember;
		
		$fields_without_metakey = array('block','shortcode','spacing','divider','group');
		remove_filter('um_fields_without_metakey', 'um_user_tags_requires_no_metakey');
		$fields_without_metakey = apply_filters('um_fields_without_metakey', $fields_without_metakey );
		
		if ( !$show_all ) {
			$this->fields_dropdown = array('image','file','password','rating');
			$this->fields_dropdown = array_merge( $this->fields_dropdown, $fields_without_metakey );
		} else {
			$this->fields_dropdown = $fields_without_metakey;
		}

		$custom = $this->custom_fields;
		$predefined = $this->predefined_fields;
		
		if ( $exclude_types ) {
			$exclude_types = explode(',', $exclude_types);
		}
		
		$all = array( 0 => '' );
		
		if ( is_array( $custom ) ){
		$all = $all + array_merge( $predefined, $custom );
		} else {
			$all = $all + $predefined;
		}
		
		foreach( $all as $k => $arr ) {
			
			if ( $k == 0 ) {
				unset($all[$k]);
			}
			
			if ( isset( $arr['title'] ) ){
				$all[$k]['title'] = stripslashes( $arr['title'] );
			}
			
			if ( $exclude_types && isset( $arr['type'] ) && in_array( $arr['type'], $exclude_types ) ) {
				unset( $all[$k] );
			}
			if ( isset( $arr['account_only'] ) || isset( $arr['private_use'] ) ) {
				if ( !$show_all ) {
					unset( $all[$k] );
				}
			}
			if ( isset( $arr['type'] ) && in_array( $arr['type'], $this->fields_dropdown ) ) {
				unset( $all[$k] );
			}
		}
		
		$all = $ultimatemember->fields->array_sort_by_column( $all, 'title');
		
		$all = array( 0 => '') + $all;

		return $all;
	}
	
	/***
	***	@Possible validation types for fields
	***/
	function validation_types(){
	
		$array[0] = __('None','ultimatemember');
		$array['alphabetic'] = __('Alphabetic value only','ultimatemember');
		$array['alpha_numeric'] = __('Alpha-numeric value','ultimatemember');
		$array['english'] = __('English letters only','ultimatemember');
		$array['facebook_url'] = __('Facebook URL','ultimatemember');
		$array['google_url'] = __('Google+ URL','ultimatemember');
		$array['instagram_url'] = __('Instagram URL','ultimatemember');
		$array['linkedin_url'] = __('LinkedIn URL','ultimatemember');
		$array['vk_url'] = __('VKontakte URL','ultimatemember');
		$array['lowercase'] = __('Lowercase only','ultimatemember');
		$array['numeric'] = __('Numeric value only','ultimatemember');
		$array['phone_number'] = __('Phone Number','ultimatemember');
		$array['skype'] = __('Skype ID','ultimatemember');
		$array['soundcloud'] = __('SoundCloud Profile','ultimatemember');
		$array['twitter_url'] = __('Twitter URL','ultimatemember');
		$array['unique_email'] = __('Unique E-mail','ultimatemember');
		$array['unique_value'] = __('Unique Metakey value','ultimatemember');
		$array['unique_username'] = __('Unique Username','ultimatemember');
		$array['unique_username_or_email'] = __('Unique Username/E-mail','ultimatemember');
		$array['url'] = __('Website URL','ultimatemember');
		$array['youtube_url'] = __('YouTube Profile','ultimatemember');
		$array['custom'] = __('Custom Validation','ultimatemember');
		
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
							"aa" => __("Afar","ultimatemember"),
							 "ab" => __("Abkhazian","ultimatemember"),
							 "ae" => __("Avestan","ultimatemember"),
							 "af" => __("Afrikaans","ultimatemember"),
							 "ak" => __("Akan","ultimatemember"),
							 "am" => __("Amharic","ultimatemember"),
							 "an" => __("Aragonese","ultimatemember"),
							 "ar" => __("Arabic","ultimatemember"),
							 "as" => __("Assamese","ultimatemember"),
							 "av" => __("Avaric","ultimatemember"),
							 "ay" => __("Aymara","ultimatemember"),
							 "az" => __("Azerbaijani","ultimatemember"),
							 "ba" => __("Bashkir","ultimatemember"),
							 "be" => __("Belarusian","ultimatemember"),
							 "bg" => __("Bulgarian","ultimatemember"),
							 "bh" => __("Bihari","ultimatemember"),
							 "bi" => __("Bislama","ultimatemember"),
							 "bm" => __("Bambara","ultimatemember"),
							 "bn" => __("Bengali","ultimatemember"),
							 "bo" => __("Tibetan","ultimatemember"),
							 "br" => __("Breton","ultimatemember"),
							 "bs" => __("Bosnian","ultimatemember"),
							 "ca" => __("Catalan","ultimatemember"),
							 "ce" => __("Chechen","ultimatemember"),
							 "ch" => __("Chamorro","ultimatemember"),
							 "co" => __("Corsican","ultimatemember"),
							 "cr" => __("Cree","ultimatemember"),
							 "cs" => __("Czech","ultimatemember"),
							 "cu" => __("Church Slavic","ultimatemember"),
							 "cv" => __("Chuvash","ultimatemember"),
							 "cy" => __("Welsh","ultimatemember"),
							 "da" => __("Danish","ultimatemember"),
							 "de" => __("German","ultimatemember"),
							 "dv" => __("Divehi","ultimatemember"),
							 "dz" => __("Dzongkha","ultimatemember"),
							 "ee" => __("Ewe","ultimatemember"),
							 "el" => __("Greek","ultimatemember"),
							 "en" => __("English","ultimatemember"),
							 "eo" => __("Esperanto","ultimatemember"),
							 "es" => __("Spanish","ultimatemember"),
							 "et" => __("Estonian","ultimatemember"),
							 "eu" => __("Basque","ultimatemember"),
							 "fa" => __("Persian","ultimatemember"),
							 "ff" => __("Fulah","ultimatemember"),
							 "fi" => __("Finnish","ultimatemember"),
							 "fj" => __("Fijian","ultimatemember"),
							 "fo" => __("Faroese","ultimatemember"),
							 "fr" => __("French","ultimatemember"),
							 "fy" => __("Western Frisian","ultimatemember"),
							 "ga" => __("Irish","ultimatemember"),
							 "gd" => __("Scottish Gaelic","ultimatemember"),
							 "gl" => __("Galician","ultimatemember"),
							 "gn" => __("Guarani","ultimatemember"),
							 "gu" => __("Gujarati","ultimatemember"),
							 "gv" => __("Manx","ultimatemember"),
							 "ha" => __("Hausa","ultimatemember"),
							 "he" => __("Hebrew","ultimatemember"),
							 "hi" => __("Hindi","ultimatemember"),
							 "ho" => __("Hiri Motu","ultimatemember"),
							 "hr" => __("Croatian","ultimatemember"),
							 "ht" => __("Haitian","ultimatemember"),
							 "hu" => __("Hungarian","ultimatemember"),
							 "hy" => __("Armenian","ultimatemember"),
							 "hz" => __("Herero","ultimatemember"),
							 "ia" => __("Interlingua (International Auxiliary Language Association)","ultimatemember"),
							 "id" => __("Indonesian","ultimatemember"),
							 "ie" => __("Interlingue","ultimatemember"),
							 "ig" => __("Igbo","ultimatemember"),
							 "ii" => __("Sichuan Yi","ultimatemember"),
							 "ik" => __("Inupiaq","ultimatemember"),
							 "io" => __("Ido","ultimatemember"),
							 "is" => __("Icelandic","ultimatemember"),
							 "it" => __("Italian","ultimatemember"),
							 "iu" => __("Inuktitut","ultimatemember"),
							 "ja" => __("Japanese","ultimatemember"),
							 "jv" => __("Javanese","ultimatemember"),
							 "ka" => __("Georgian","ultimatemember"),
							 "kg" => __("Kongo","ultimatemember"),
							 "ki" => __("Kikuyu","ultimatemember"),
							 "kj" => __("Kwanyama","ultimatemember"),
							 "kk" => __("Kazakh","ultimatemember"),
							 "kl" => __("Kalaallisut","ultimatemember"),
							 "km" => __("Khmer","ultimatemember"),
							 "kn" => __("Kannada","ultimatemember"),
							 "ko" => __("Korean","ultimatemember"),
							 "kr" => __("Kanuri","ultimatemember"),
							 "ks" => __("Kashmiri","ultimatemember"),
							 "ku" => __("Kurdish","ultimatemember"),
							 "kv" => __("Komi","ultimatemember"),
							 "kw" => __("Cornish","ultimatemember"),
							 "ky" => __("Kirghiz","ultimatemember"),
							 "la" => __("Latin","ultimatemember"),
							 "lb" => __("Luxembourgish","ultimatemember"),
							 "lg" => __("Ganda","ultimatemember"),
							 "li" => __("Limburgish","ultimatemember"),
							 "ln" => __("Lingala","ultimatemember"),
							 "lo" => __("Lao","ultimatemember"),
							 "lt" => __("Lithuanian","ultimatemember"),
							 "lu" => __("Luba-Katanga","ultimatemember"),
							 "lv" => __("Latvian","ultimatemember"),
							 "mg" => __("Malagasy","ultimatemember"),
							 "mh" => __("Marshallese","ultimatemember"),
							 "mi" => __("Maori","ultimatemember"),
							 "mk" => __("Macedonian","ultimatemember"),
							 "ml" => __("Malayalam","ultimatemember"),
							 "mn" => __("Mongolian","ultimatemember"),
							 "mr" => __("Marathi","ultimatemember"),
							 "ms" => __("Malay","ultimatemember"),
							 "mt" => __("Maltese","ultimatemember"),
							 "my" => __("Burmese","ultimatemember"),
							 "na" => __("Nauru","ultimatemember"),
							 "nb" => __("Norwegian Bokmal","ultimatemember"),
							 "nd" => __("North Ndebele","ultimatemember"),
							 "ne" => __("Nepali","ultimatemember"),
							 "ng" => __("Ndonga","ultimatemember"),
							 "nl" => __("Dutch","ultimatemember"),
							 "nn" => __("Norwegian Nynorsk","ultimatemember"),
							 "no" => __("Norwegian","ultimatemember"),
							 "nr" => __("South Ndebele","ultimatemember"),
							 "nv" => __("Navajo","ultimatemember"),
							 "ny" => __("Chichewa","ultimatemember"),
							 "oc" => __("Occitan","ultimatemember"),
							 "oj" => __("Ojibwa","ultimatemember"),
							 "om" => __("Oromo","ultimatemember"),
							 "or" => __("Oriya","ultimatemember"),
							 "os" => __("Ossetian","ultimatemember"),
							 "pa" => __("Panjabi","ultimatemember"),
							 "pi" => __("Pali","ultimatemember"),
							 "pl" => __("Polish","ultimatemember"),
							 "ps" => __("Pashto","ultimatemember"),
							 "pt" => __("Portuguese","ultimatemember"),
							 "qu" => __("Quechua","ultimatemember"),
							 "rm" => __("Raeto-Romance","ultimatemember"),
							 "rn" => __("Kirundi","ultimatemember"),
							 "ro" => __("Romanian","ultimatemember"),
							 "ru" => __("Russian","ultimatemember"),
							 "rw" => __("Kinyarwanda","ultimatemember"),
							 "sa" => __("Sanskrit","ultimatemember"),
							 "sc" => __("Sardinian","ultimatemember"),
							 "sd" => __("Sindhi","ultimatemember"),
							 "se" => __("Northern Sami","ultimatemember"),
							 "sg" => __("Sango","ultimatemember"),
							 "si" => __("Sinhala","ultimatemember"),
							 "sk" => __("Slovak","ultimatemember"),
							 "sl" => __("Slovenian","ultimatemember"),
							 "sm" => __("Samoan","ultimatemember"),
							 "sn" => __("Shona","ultimatemember"),
							 "so" => __("Somali","ultimatemember"),
							 "sq" => __("Albanian","ultimatemember"),
							 "sr" => __("Serbian","ultimatemember"),
							 "ss" => __("Swati","ultimatemember"),
							 "st" => __("Southern Sotho","ultimatemember"),
							 "su" => __("Sundanese","ultimatemember"),
							 "sv" => __("Swedish","ultimatemember"),
							 "sw" => __("Swahili","ultimatemember"),
							 "ta" => __("Tamil","ultimatemember"),
							 "te" => __("Telugu","ultimatemember"),
							 "tg" => __("Tajik","ultimatemember"),
							 "th" => __("Thai","ultimatemember"),
							 "ti" => __("Tigrinya","ultimatemember"),
							 "tk" => __("Turkmen","ultimatemember"),
							 "tl" => __("Tagalog","ultimatemember"),
							 "tn" => __("Tswana","ultimatemember"),
							 "to" => __("Tonga","ultimatemember"),
							 "tr" => __("Turkish","ultimatemember"),
							 "ts" => __("Tsonga","ultimatemember"),
							 "tt" => __("Tatar","ultimatemember"),
							 "tw" => __("Twi","ultimatemember"),
							 "ty" => __("Tahitian","ultimatemember"),
							 "ug" => __("Uighur","ultimatemember"),
							 "uk" => __("Ukrainian","ultimatemember"),
							 "ur" => __("Urdu","ultimatemember"),
							 "uz" => __("Uzbek","ultimatemember"),
							 "ve" => __("Venda","ultimatemember"),
							 "vi" => __("Vietnamese","ultimatemember"),
							 "vo" => __("Volapuk","ultimatemember"),
							 "wa" => __("Walloon","ultimatemember"),
							 "wo" => __("Wolof","ultimatemember"),
							 "xh" => __("Xhosa","ultimatemember"),
							 "yi" => __("Yiddish","ultimatemember"),
							 "yo" => __("Yoruba","ultimatemember"),
							 "za" => __("Zhuang","ultimatemember"),
							 "zh" => __("Chinese","ultimatemember"),
							 "zu" => __("Zulu","ultimatemember")
			);
			break;

			case 'countries':
				$array = array (
							'AF' => __('Afghanistan',"ultimatemember"),
							'AX' => __('Åland Islands',"ultimatemember"),
							'AL' => __('Albania',"ultimatemember"),
							'DZ' => __('Algeria',"ultimatemember"),
							'AS' => __('American Samoa',"ultimatemember"),
							'AD' => __('Andorra',"ultimatemember"),
							'AO' => __('Angola',"ultimatemember"),
							'AI' => __('Anguilla',"ultimatemember"),
							'AQ' => __('Antarctica',"ultimatemember"),
							'AG' => __('Antigua and Barbuda',"ultimatemember"),
							'AR' => __('Argentina',"ultimatemember"),
							'AM' => __('Armenia',"ultimatemember"),
							'AW' => __('Aruba',"ultimatemember"),
							'AU' => __('Australia',"ultimatemember"),
							'AT' => __('Austria',"ultimatemember"),
							'AZ' => __('Azerbaijan',"ultimatemember"),
							'BS' => __('Bahamas',"ultimatemember"),
							'BH' => __('Bahrain',"ultimatemember"),
							'BD' => __('Bangladesh',"ultimatemember"),
							'BB' => __('Barbados',"ultimatemember"),
							'BY' => __('Belarus',"ultimatemember"),
							'BE' => __('Belgium',"ultimatemember"),
							'BZ' => __('Belize',"ultimatemember"),
							'BJ' => __('Benin',"ultimatemember"),
							'BM' => __('Bermuda',"ultimatemember"),
							'BT' => __('Bhutan',"ultimatemember"),
							'BO' => __('Bolivia, Plurinational State of',"ultimatemember"),
							'BA' => __('Bosnia and Herzegovina',"ultimatemember"),
							'BW' => __('Botswana',"ultimatemember"),
							'BV' => __('Bouvet Island',"ultimatemember"),
							'BR' => __('Brazil',"ultimatemember"),
							'IO' => __('British Indian Ocean Territory',"ultimatemember"),
							'BN' => __('Brunei Darussalam',"ultimatemember"),
							'BG' => __('Bulgaria',"ultimatemember"),
							'BF' => __('Burkina Faso',"ultimatemember"),
							'BI' => __('Burundi',"ultimatemember"),
							'KH' => __('Cambodia',"ultimatemember"),
							'CM' => __('Cameroon',"ultimatemember"),
							'CA' => __('Canada',"ultimatemember"),
							'CV' => __('Cape Verde',"ultimatemember"),
							'KY' => __('Cayman Islands',"ultimatemember"),
							'CF' => __('Central African Republic',"ultimatemember"),
							'TD' => __('Chad',"ultimatemember"),
							'CL' => __('Chile',"ultimatemember"),
							'CN' => __('China',"ultimatemember"),
							'CX' => __('Christmas Island',"ultimatemember"),
							'CC' => __('Cocos (Keeling) Islands',"ultimatemember"),
							'CO' => __('Colombia',"ultimatemember"),
							'KM' => __('Comoros',"ultimatemember"),
							'CG' => __('Congo',"ultimatemember"),
							'CD' => __('Congo, the Democratic Republic of the',"ultimatemember"),
							'CK' => __('Cook Islands',"ultimatemember"),
							'CR' => __('Costa Rica',"ultimatemember"),
							'CI' => __("Côte d'Ivoire","ultimatemember"),
							'HR' => __('Croatia',"ultimatemember"),
							'CU' => __('Cuba',"ultimatemember"),
							'CY' => __('Cyprus',"ultimatemember"),
							'CZ' => __('Czech Republic',"ultimatemember"),
							'DK' => __('Denmark',"ultimatemember"),
							'DJ' => __('Djibouti',"ultimatemember"),
							'DM' => __('Dominica',"ultimatemember"),
							'DO' => __('Dominican Republic',"ultimatemember"),
							'EC' => __('Ecuador',"ultimatemember"),
							'EG' => __('Egypt',"ultimatemember"),
							'SV' => __('El Salvador',"ultimatemember"),
							'GQ' => __('Equatorial Guinea',"ultimatemember"),
							'ER' => __('Eritrea',"ultimatemember"),
							'EE' => __('Estonia',"ultimatemember"),
							'ET' => __('Ethiopia',"ultimatemember"),
							'FK' => __('Falkland Islands (Malvinas)',"ultimatemember"),
							'FO' => __('Faroe Islands',"ultimatemember"),
							'FJ' => __('Fiji',"ultimatemember"),
							'FI' => __('Finland',"ultimatemember"),
							'FR' => __('France',"ultimatemember"),
							'GF' => __('French Guiana',"ultimatemember"),
							'PF' => __('French Polynesia',"ultimatemember"),
							'TF' => __('French Southern Territories',"ultimatemember"),
							'GA' => __('Gabon',"ultimatemember"),
							'GM' => __('Gambia',"ultimatemember"),
							'GE' => __('Georgia',"ultimatemember"),
							'DE' => __('Germany',"ultimatemember"),
							'GH' => __('Ghana',"ultimatemember"),
							'GI' => __('Gibraltar',"ultimatemember"),
							'GR' => __('Greece',"ultimatemember"),
							'GL' => __('Greenland',"ultimatemember"),
							'GD' => __('Grenada',"ultimatemember"),
							'GP' => __('Guadeloupe',"ultimatemember"),
							'GU' => __('Guam',"ultimatemember"),
							'GT' => __('Guatemala',"ultimatemember"),
							'GG' => __('Guernsey',"ultimatemember"),
							'GN' => __('Guinea',"ultimatemember"),
							'GW' => __('Guinea-Bissau',"ultimatemember"),
							'GY' => __('Guyana',"ultimatemember"),
							'HT' => __('Haiti',"ultimatemember"),
							'HM' => __('Heard Island and McDonald Islands',"ultimatemember"),
							'VA' => __('Holy See (Vatican City State)',"ultimatemember"),
							'HN' => __('Honduras',"ultimatemember"),
							'HK' => __('Hong Kong',"ultimatemember"),
							'HU' => __('Hungary',"ultimatemember"),
							'IS' => __('Iceland',"ultimatemember"),
							'IN' => __('India',"ultimatemember"),
							'ID' => __('Indonesia',"ultimatemember"),
							'IR' => __('Iran, Islamic Republic of',"ultimatemember"),
							'IQ' => __('Iraq',"ultimatemember"),
							'IE' => __('Ireland',"ultimatemember"),
							'IM' => __('Isle of Man',"ultimatemember"),
							'IL' => __('Israel',"ultimatemember"),
							'IT' => __('Italy',"ultimatemember"),
							'JM' => __('Jamaica',"ultimatemember"),
							'JP' => __('Japan',"ultimatemember"),
							'JE' => __('Jersey',"ultimatemember"),
							'JO' => __('Jordan',"ultimatemember"),
							'KZ' => __('Kazakhstan',"ultimatemember"),
							'KE' => __('Kenya',"ultimatemember"),
							'KI' => __('Kiribati',"ultimatemember"),
							'KP' => __("Korea, Democratic People's Republic of","ultimatemember"),
							'KR' => __('Korea, Republic of',"ultimatemember"),
							'KW' => __('Kuwait',"ultimatemember"),
							'KG' => __('Kyrgyzstan',"ultimatemember"),
							'LA' => __("Lao People's Democratic Republic","ultimatemember"),
							'LV' => __('Latvia',"ultimatemember"),
							'LB' => __('Lebanon',"ultimatemember"),
							'LS' => __('Lesotho',"ultimatemember"),
							'LR' => __('Liberia',"ultimatemember"),
							'LY' => __('Libyan Arab Jamahiriya',"ultimatemember"),
							'LI' => __('Liechtenstein',"ultimatemember"),
							'LT' => __('Lithuania',"ultimatemember"),
							'LU' => __('Luxembourg',"ultimatemember"),
							'MO' => __('Macao',"ultimatemember"),
							'MK' => __('Macedonia, the former Yugoslav Republic of',"ultimatemember"),
							'MG' => __('Madagascar',"ultimatemember"),
							'MW' => __('Malawi',"ultimatemember"),
							'MY' => __('Malaysia',"ultimatemember"),
							'MV' => __('Maldives',"ultimatemember"),
							'ML' => __('Mali',"ultimatemember"),
							'MT' => __('Malta',"ultimatemember"),
							'MH' => __('Marshall Islands',"ultimatemember"),
							'MQ' => __('Martinique',"ultimatemember"),
							'MR' => __('Mauritania',"ultimatemember"),
							'MU' => __('Mauritius',"ultimatemember"),
							'YT' => __('Mayotte',"ultimatemember"),
							'MX' => __('Mexico',"ultimatemember"),
							'FM' => __('Micronesia, Federated States of',"ultimatemember"),
							'MD' => __('Moldova, Republic of',"ultimatemember"),
							'MC' => __('Monaco',"ultimatemember"),
							'MN' => __('Mongolia',"ultimatemember"),
							'ME' => __('Montenegro',"ultimatemember"),
							'MS' => __('Montserrat',"ultimatemember"),
							'MA' => __('Morocco',"ultimatemember"),
							'MZ' => __('Mozambique',"ultimatemember"),
							'MM' => __('Myanmar',"ultimatemember"),
							'NA' => __('Namibia',"ultimatemember"),
							'NR' => __('Nauru',"ultimatemember"),
							'NP' => __('Nepal',"ultimatemember"),
							'NL' => __('Netherlands',"ultimatemember"),
							'AN' => __('Netherlands Antilles',"ultimatemember"),
							'NC' => __('New Caledonia',"ultimatemember"),
							'NZ' => __('New Zealand',"ultimatemember"),
							'NI' => __('Nicaragua',"ultimatemember"),
							'NE' => __('Niger',"ultimatemember"),
							'NG' => __('Nigeria',"ultimatemember"),
							'NU' => __('Niue',"ultimatemember"),
							'NF' => __('Norfolk Island',"ultimatemember"),
							'MP' => __('Northern Mariana Islands',"ultimatemember"),
							'NO' => __('Norway',"ultimatemember"),
							'OM' => __('Oman',"ultimatemember"),
							'PK' => __('Pakistan',"ultimatemember"),
							'PW' => __('Palau',"ultimatemember"),
							'PS' => __('Palestine',"ultimatemember"),
							'PA' => __('Panama',"ultimatemember"),
							'PG' => __('Papua New Guinea',"ultimatemember"),
							'PY' => __('Paraguay',"ultimatemember"),
							'PE' => __('Peru',"ultimatemember"),
							'PH' => __('Philippines',"ultimatemember"),
							'PN' => __('Pitcairn',"ultimatemember"),
							'PL' => __('Poland',"ultimatemember"),
							'PT' => __('Portugal',"ultimatemember"),
							'PR' => __('Puerto Rico',"ultimatemember"),
							'QA' => __('Qatar',"ultimatemember"),
							'RE' => __('Réunion',"ultimatemember"),
							'RO' => __('Romania',"ultimatemember"),
							'RU' => __('Russian Federation',"ultimatemember"),
							'RW' => __('Rwanda',"ultimatemember"),
							'BL' => __('Saint Barthélemy',"ultimatemember"),
							'SH' => __('Saint Helena',"ultimatemember"),
							'KN' => __('Saint Kitts and Nevis',"ultimatemember"),
							'LC' => __('Saint Lucia',"ultimatemember"),
							'MF' => __('Saint Martin (French part)',"ultimatemember"),
							'PM' => __('Saint Pierre and Miquelon',"ultimatemember"),
							'VC' => __('Saint Vincent and the Grenadines',"ultimatemember"),
							'WS' => __('Samoa',"ultimatemember"),
							'SM' => __('San Marino',"ultimatemember"),
							'ST' => __('Sao Tome and Principe',"ultimatemember"),
							'SA' => __('Saudi Arabia',"ultimatemember"),
							'SN' => __('Senegal',"ultimatemember"),
							'RS' => __('Serbia',"ultimatemember"),
							'SC' => __('Seychelles',"ultimatemember"),
							'SL' => __('Sierra Leone',"ultimatemember"),
							'SG' => __('Singapore',"ultimatemember"),
							'SK' => __('Slovakia',"ultimatemember"),
							'SI' => __('Slovenia',"ultimatemember"),
							'SB' => __('Solomon Islands',"ultimatemember"),
							'SO' => __('Somalia',"ultimatemember"),
							'ZA' => __('South Africa',"ultimatemember"),
							'GS' => __('South Georgia and the South Sandwich Islands',"ultimatemember"),
							'SS' => __('South Sudan',"ultimatemember"),
							'ES' => __('Spain',"ultimatemember"),
							'LK' => __('Sri Lanka',"ultimatemember"),
							'SD' => __('Sudan',"ultimatemember"),
							'SR' => __('Suriname',"ultimatemember"),
							'SJ' => __('Svalbard and Jan Mayen',"ultimatemember"),
							'SZ' => __('Swaziland',"ultimatemember"),
							'SE' => __('Sweden',"ultimatemember"),
							'CH' => __('Switzerland',"ultimatemember"),
							'SY' => __('Syrian Arab Republic',"ultimatemember"),
							'TW' => __('Taiwan, Province of China',"ultimatemember"),
							'TJ' => __('Tajikistan',"ultimatemember"),
							'TZ' => __('Tanzania, United Republic of',"ultimatemember"),
							'TH' => __('Thailand',"ultimatemember"),
							'TL' => __('Timor-Leste',"ultimatemember"),
							'TG' => __('Togo',"ultimatemember"),
							'TK' => __('Tokelau',"ultimatemember"),
							'TO' => __('Tonga',"ultimatemember"),
							'TT' => __('Trinidad and Tobago',"ultimatemember"),
							'TN' => __('Tunisia',"ultimatemember"),
							'TR' => __('Turkey',"ultimatemember"),
							'TM' => __('Turkmenistan',"ultimatemember"),
							'TC' => __('Turks and Caicos Islands',"ultimatemember"),
							'TV' => __('Tuvalu',"ultimatemember"),
							'UG' => __('Uganda',"ultimatemember"),
							'UA' => __('Ukraine',"ultimatemember"),
							'AE' => __('United Arab Emirates',"ultimatemember"),
							'GB' => __('United Kingdom',"ultimatemember"),
							'US' => __('United States',"ultimatemember"),
							'UM' => __('United States Minor Outlying Islands',"ultimatemember"),
							'UY' => __('Uruguay',"ultimatemember"),
							'UZ' => __('Uzbekistan',"ultimatemember"),
							'VU' => __('Vanuatu',"ultimatemember"),
							'VE' => __('Venezuela, Bolivarian Republic of',"ultimatemember"),
							'VN' => __('Viet Nam',"ultimatemember"),
							'VG' => __('Virgin Islands, British',"ultimatemember"),
							'VI' => __('Virgin Islands, U.S.',"ultimatemember"),
							'WF' => __('Wallis and Futuna',"ultimatemember"),
							'EH' => __('Western Sahara',"ultimatemember"),
							'YE' => __('Yemen',"ultimatemember"),
							'ZM' => __('Zambia',"ultimatemember"),
							'ZW' => __('Zimbabwe',"ultimatemember"),
				);
				break;
	
		}
		
		$array = apply_filters("um_{$data}_predefined_field_options", $array);
		
		return $array;
		
	}

}
