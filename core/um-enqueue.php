<?php

class UM_Enqueue {

	function __construct() {
	
		add_action('wp_enqueue_scripts',  array(&$this, 'wp_enqueue_scripts'), 0);
	
	}
	
	/***
	***	@Include Google charts
	***/
	function load_google_charts(){

		wp_register_script('um_gchart', 'https://www.google.com/jsapi' );
		wp_enqueue_script('um_gchart');
		
	}
	
	/***
	***	@Load plugin css
	***/
	function load_css(){
	
		wp_register_style('um_styles', um_url . 'assets/css/um-styles.css' );
		wp_enqueue_style('um_styles');
		
		wp_register_style('um_members', um_url . 'assets/css/um-members.css' );
		wp_enqueue_style('um_members');
		
		wp_register_style('um_profile', um_url . 'assets/css/um-profile.css' );
		wp_enqueue_style('um_profile');
		
		wp_register_style('um_account', um_url . 'assets/css/um-account.css' );
		wp_enqueue_style('um_account');
		
	}
	
	/***
	***	@Load select-dropdowns JS
	***/
	function load_selectjs(){
	
		wp_register_script('um_select', um_url . 'assets/js/um-select.js', array('jquery') );
		wp_enqueue_script('um_select');
		
		wp_register_style('um_select', um_url . 'assets/css/um-select.css' );
		wp_enqueue_style('um_select');
		
	}
	
	/***
	***	@Load Fonticons
	***/
	function load_fonticons(){
	
		wp_register_style('um_fonticons', um_url . 'assets/css/um-fonticons.css' );
		wp_enqueue_style('um_fonticons');
		
	}
	
	/***
	***	@Load fileupload JS
	***/
	function load_fileupload() {
		
		wp_register_script('um_jquery_form', um_url . 'assets/js/um-jquery-form.js' );
		wp_enqueue_script('um_jquery_form');
		
		wp_register_script('um_fileupload', um_url . 'assets/js/um-fileupload.js' );
		wp_enqueue_script('um_fileupload');
		
		wp_register_style('um_fileupload', um_url . 'assets/css/um-fileupload.css' );
		wp_enqueue_style('um_fileupload');
		
	}
	
	/***
	***	@Load JS functions
	***/
	function load_functions(){
	
		wp_register_script('um_functions', um_url . 'assets/js/um-functions.js' );
		wp_enqueue_script('um_functions');
		
	}
	
	/***
	***	@Load custom JS
	***/
	function load_customjs(){
	
		wp_register_script('um_conditional', um_url . 'assets/js/um-conditional.js' );
		wp_enqueue_script('um_conditional');
		
		wp_register_script('um_scripts', um_url . 'assets/js/um-scripts.js' );
		wp_enqueue_script('um_scripts');

		wp_register_script('um_members', um_url . 'assets/js/um-members.js' );
		wp_enqueue_script('um_members');
		
		wp_register_script('um_profile', um_url . 'assets/js/um-profile.js' );
		wp_enqueue_script('um_profile');
		
		wp_register_script('um_account', um_url . 'assets/js/um-account.js' );
		wp_enqueue_script('um_account');
		
	}
	
	/***
	***	@Load date & time picker
	***/
	function load_datetimepicker(){
	
		wp_register_script('um_datetime', um_url . 'assets/js/pickadate/picker.js' );
		wp_enqueue_script('um_datetime');
		
		wp_register_script('um_datetime_date', um_url . 'assets/js/pickadate/picker.date.js' );
		wp_enqueue_script('um_datetime_date');
		
		wp_register_script('um_datetime_time', um_url . 'assets/js/pickadate/picker.time.js' );
		wp_enqueue_script('um_datetime_time');
		
		wp_register_script('um_datetime_legacy', um_url . 'assets/js/pickadate/legacy.js' );
		wp_enqueue_script('um_datetime_legacy');
		
		wp_register_style('um_datetime', um_url . 'assets/css/pickadate/default.css' );
		wp_enqueue_style('um_datetime');
		
		wp_register_style('um_datetime_date', um_url . 'assets/css/pickadate/default.date.css' );
		wp_enqueue_style('um_datetime_date');
		
		wp_register_style('um_datetime_time', um_url . 'assets/css/pickadate/default.time.css' );
		wp_enqueue_style('um_datetime_time');
		
	}
	
	/***
	***	@Load rating
	***/
	function load_raty(){
	
		wp_register_script('um_raty', um_url . 'assets/js/um-raty.js' );
		wp_enqueue_script('um_raty');
		
		wp_register_style('um_raty', um_url . 'assets/css/um-raty.css' );
		wp_enqueue_style('um_raty');

	}
	
	/***
	***	@Load crop script
	***/
	function load_imagecrop(){
	
		wp_register_script('um_crop', um_url . 'assets/js/um-crop.js' );
		wp_enqueue_script('um_crop');
		
		wp_register_style('um_crop', um_url . 'assets/css/um-crop.css' );
		wp_enqueue_style('um_crop');
		
	}
	
	/***
	***	@Load masonry
	***/
	function load_masonry(){
	
		wp_register_script('um_masonry', um_url . 'assets/js/um-masonry.js' );
		wp_enqueue_script('um_masonry');

	}
	
	/***
	***	@Load tipsy
	***/
	function load_tipsy(){
	
		wp_register_script('um_tipsy', um_url . 'assets/js/um-tipsy.js' );
		wp_enqueue_script('um_tipsy');
		
		wp_register_style('um_tipsy', um_url . 'assets/css/um-tipsy.css' );
		wp_enqueue_style('um_tipsy');

	}
	
	/***
	***	@Load modal
	***/
	function load_modal(){

		wp_register_style('um_modal', um_url . 'assets/css/um-modal.css' );
		wp_enqueue_style('um_modal');
		
		wp_register_script('um_modal', um_url . 'assets/js/um-modal.js' );
		wp_enqueue_script('um_modal');
		
	}
	
	/***
	***	@Load responsive styles
	***/
	function load_responsive(){
	
		wp_register_script('um_responsive', um_url . 'assets/js/um-responsive.js' );
		wp_enqueue_script('um_responsive');
		
		wp_register_style('um_responsive', um_url . 'assets/css/um-responsive.css' );
		wp_enqueue_style('um_responsive');
		
	}
	
	/***
	***	@Enqueue scripts and styles
	***/
	function wp_enqueue_scripts(){
	
		$this->load_google_charts();
	
		$this->load_fonticons();
		
		$this->load_selectjs();
		
		$this->load_modal();
		
		$this->load_css();
		
		$this->load_fileupload();
		
		$this->load_datetimepicker();
		
		$this->load_raty();
		
		$this->load_imagecrop();
		
		$this->load_masonry();
		
		$this->load_tipsy();
		
		$this->load_functions();
		
		$this->load_responsive();
		
		$this->load_customjs();

	}
	
}