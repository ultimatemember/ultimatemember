<?php

	/***
	***	@dynamic frontend modal content
	***/
	add_action('wp_ajax_nopriv_ultimatemember_frontend_modal', 'ultimatemember_frontend_modal');
	add_action('wp_ajax_ultimatemember_frontend_modal', 'ultimatemember_frontend_modal');
	function ultimatemember_frontend_modal(){
		global $ultimatemember;

		extract($_POST);
		
		switch ( $act_id ) {
			
			case 'um_frontend_shortcode':
				
				$output = do_shortcode( $arg1 );
				
				break;
				
		}
		
		if(is_array($output)){ print_r($output); }else{ echo $output; } die;
		
	}