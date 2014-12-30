<?php

class UM_Shortcodes {

	function __construct() {
	
		$this->message_mode = false;

		add_shortcode('ultimatemember', array(&$this, 'ultimatemember'), 1);

	}
	
	/***
	***	@Add class based on shortcode
	***/
	function get_class( $mode ){
	
		global $ultimatemember;
		
		$classes = 'um-'.$mode;
		
		if ( is_admin() ) {
			$classes .= ' um-in-admin';
		}
		
		if ( $ultimatemember->fields->editing == true ) {
			$classes .= ' um-editing';
		}
		
		if ( $ultimatemember->fields->viewing == true ) {
			$classes .= ' um-viewing';
		}
		
		$classes = apply_filters('um_form_official_classes__hook', $classes);
		return $classes;
	}
	
	/***
	***	@Shortcode
	***/
	function ultimatemember( $args = array() ) {
		return $this->load( $args );
	}
	
	/***
	***	@Load a module with global function
	***/
	function load( $args ) {
		global $ultimatemember;
		ob_start();

		$defaults = array();
		$args = wp_parse_args( $args, $defaults );

		// when to not continue
		$this->form_id = (isset($args['form_id'])) ? $args['form_id'] : null;
		if (!$this->form_id) return;
		$this->form_status = get_post_status( $this->form_id );
		if ( $this->form_status != 'publish' ) return;
		
		// get data into one global array
		$post_data = $ultimatemember->query->post_data( $this->form_id );
		if ( !isset( $args['template'] ) ) $args['template'] = '';
		if ( isset( $post_data['template'] ) && $post_data['template'] != $args['template']) $args['template'] = $post_data['template'];
		if ( !$this->template_exists( $args['template'] ) ) $args['template'] = $post_data['mode'];
		if ( !isset( $post_data['template'] ) ) $post_data['template'] = $post_data['mode'];
		$args = array_merge( $post_data, $args );
		
		if ( isset( $args['use_globals'] ) && $args['use_globals'] == 1 ) {
			$args = array_merge( $args, $this->get_css_args( $args ) );
		} else {
			$args = array_merge( $this->get_css_args( $args ), $args );
		}

		$args = apply_filters('um_shortcode_args_filter', $args );
		
		if ( um_profile_id() && isset( $args['role'] ) && $args['role'] && $args['role'] != $ultimatemember->query->get_role_by_userid( um_profile_id() ) )
			return;

		extract( $args, EXTR_SKIP );
		
		do_action("um_pre_{$mode}_shortcode", $args);
		
		do_action("um_before_form_is_loaded", $args);
		
		do_action("um_before_{$mode}_form_is_loaded", $args);
		
		do_action("um_before_{$template}_form_is_loaded", $args);
		
		$this->template_load( $template, $args );
		
		if ( !is_admin() && !defined( 'DOING_AJAX' ) ) {
			$this->dynamic_css( $args );
		}
		
		if ( um_get_requested_user() ) {
			um_reset_user();
		}

		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	/***
	***	@Get dynamic css args
	***/
	function get_css_args( $args ) {
		$arr = um_styling_defaults( $args['mode'] );
		$arr = array_merge( $arr, array( 'form_id' => $args['form_id'], 'mode' => $args['mode'] ) );
		return $arr;
	}
	
	/***
	***	@Load dynamic css
	***/
	function dynamic_css( $args=array() ) {
		extract($args);
		$global = um_path . 'assets/dynamic_css/dynamic_global.php';
		$file = um_path . 'assets/dynamic_css/dynamic_'.$mode.'.php';
		include $global;
		if ( file_exists( $file ) )
			include $file;
	}
	
	/***
	***	@Loads a template file
	***/
	function template_load( $template, $args=array() ) {
		global $ultimatemember;
		extract($args);
		$file = um_path . 'templates/'. $template . '.php';
		if ( file_exists( $file ) ) include $file;
	}
	
	/***
	***	@Checks if a template file exists
	***/
	function template_exists( $template ) {
		if (file_exists( um_path . 'templates/'. $template . '.php'))
			return true;
		return false;
	}
	
	/***
	***	@Get File Name without path and extension
	***/
	function get_template_name($file){
		$file = basename($file);
		$file = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file);
		return $file;
	}
	
	/***
	***	@Get Templates
	***/
	function get_templates( $excluded = null ) {
		
		if ($excluded) {
			$array[$excluded] = 'Default Template';
		}
		
		$files = glob( um_path . 'templates/' . '*.php');
		foreach($files as $file){
		
			$clean_filename = $this->get_template_name($file);
			
			if (0 === strpos($clean_filename, $excluded)) {
			
			$source = file_get_contents( $file );
			$tokens = token_get_all( $source );
			$comment = array(
				T_COMMENT,      // All comments since PHP5
				T_DOC_COMMENT   // PHPDoc comments      
			);
			foreach( $tokens as $token ) {
				if( in_array($token[0], $comment) && $clean_filename != $excluded ) {
					$txt = $token[1];
					$txt = str_replace('/* Template: ','',$txt);
					$txt = str_replace(' */','',$txt);
					$array[ $clean_filename ] = $txt;
				}
			}
			
			}
			
		}

		return $array;
	
	}
	
	/***
	***	@Get Shortcode for given form ID
	***/
	function get_shortcode($post_id){
		$shortcode = '[ultimatemember form_id='.$post_id.']';
		return $shortcode;
	}

}