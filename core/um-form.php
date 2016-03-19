<?php

class UM_Form {

	public $form_suffix;

	function __construct() {

		$this->post_form = null;

		$this->form_suffix = null;

		$this->errors = null;

		$this->processing = null;

		add_action('init', array(&$this, 'form_init'), 2);

		add_action('init', array(&$this, 'field_declare'), 10);

	}

	/**
	 * Count the form errors.
	 * @return integer
	 */
	function count_errors() {
		$errors = $this->errors;

		if( $errors && is_array( $errors ) ) {
			return count( $errors );
		}

		return 0;
	}

	/***
	***	@add errors
	***/
	function add_error( $key, $error ) {
		if ( !isset( $this->errors[$key] ) ){
			$this->errors[$key] = $error;
		}
	}

	/***
	***	@has error
	***/
	function has_error( $key ) {
		if ( isset($this->errors[$key]) )
			return true;
		return false;
	}

	/***
	***	@declare all fields
	***/
	function field_declare(){
		global $ultimatemember;
		if ( isset( $ultimatemember->builtin->custom_fields ) ) {
			$this->all_fields = $ultimatemember->builtin->custom_fields;
		} else {
			$this->all_fields = null;
		}
	}

	/***
	***	@Checks that we've a form
	***/
	function form_init(){
		global $ultimatemember;

		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
		} else {
			$http_post = 'POST';
		}


		if ( $http_post && !is_admin() && isset( $_POST['form_id'] ) && is_numeric($_POST['form_id']) ) {

			$this->form_id = $_POST['form_id'];
			$this->form_status = get_post_status( $this->form_id );


			if ( $this->form_status == 'publish' ) {

				/* save entire form as global */
				$this->post_form = $_POST;

				$this->post_form = $this->beautify( $this->post_form );

				$this->form_data = $ultimatemember->query->post_data( $this->form_id );

				$this->post_form['submitted'] = $this->post_form;

				$this->post_form = array_merge( $this->form_data, $this->post_form );

				$role = $this->assigned_role( $this->form_id );

				if( $role && isset( $this->form_data['custom_fields'] ) && ! strstr( $this->form_data['custom_fields'], 'role_' ) ){ // has assigned role.  Validate non-global forms
					if ( isset( $this->form_data['role'] ) && ( (boolean) $this->form_data['role'] ) && isset(  $_POST['role']  ) && $_POST['role'] != $role ) {
						wp_die( __( 'This is not possible for security reasons.','ultimatemember') );
					} else {
						if ( isset( $_POST['role'] ) ) {
							if ( $role != $_POST['role'] ) {
									wp_die( __( 'This is not possible for security reasons.','ultimatemember') );
							}
						}
					}
				}

				if ( isset( $_POST[ $ultimatemember->honeypot ] ) && $_POST[ $ultimatemember->honeypot ] != '' ){
					wp_die('Hello, spam bot!');
				}

				if ( !in_array( $this->form_data['mode'], array('login') ) ) {

					$form_timestamp  = trim($_POST['timestamp']);
					$live_timestamp  = current_time( 'timestamp' );

					if ( $form_timestamp == '' && um_get_option('enable_timebot') == 1 )
						wp_die( __('Hello, spam bot!') );

					if ( !current_user_can('manage_options') && $live_timestamp - $form_timestamp < 6 && um_get_option('enable_timebot') == 1  )
						wp_die( __('Whoa, slow down! You\'re seeing this message because you tried to submit a form too fast and we think you might be a spam bot. If you are a real human being please wait a few seconds before submitting the form. Thanks!') );

				}

				/* Continue based on form mode - pre-validation */

				do_action('um_submit_form_errors_hook', $this->post_form );

				do_action("um_submit_form_{$this->post_form['mode']}", $this->post_form );

			}

		}

	}

	/***
	***	@Beautify form data
	***/
	function beautify( $form ){

		if (isset($form['form_id'])){

			$this->form_suffix = '-' . $form['form_id'];

			$this->processing = $form['form_id'];

			foreach($form as $key => $value){
				if (strstr($key, $this->form_suffix) ) {
					$a_key = str_replace( $this->form_suffix, '', $key);
					$form[$a_key] = $value;
					unset($form[$key]);
				}
			}

		}

		return $form;
	}

	/***
	***	@Display Form Type as Text
	***/
	function display_form_type($mode, $post_id){
		$output = null;
		switch($mode){
			case 'login':
				$output = 'Login';
				break;
			case 'profile':
				$output = 'Profile';
				break;
			case 'register':
				$output = 'Register';
				break;
		}
		return $output;
	}

	function assigned_role( $post_id ){

		$register_use_globals = get_post_meta( $post_id, '_um_register_use_globals', true);

		if( $register_use_globals == 1 ){
			$role = um_get_option('default_role');
		}else if( $register_use_globals == 0 ){
			$role = get_post_meta( $post_id, '_um_register_role', true );
		}

		if( ! $role ){
			$role = false;
		}
		return $role;
	}
}
