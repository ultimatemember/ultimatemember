<?php

	/***
	***	@add-ons panel
	***/
	add_filter("redux/options/um_options/sections", 'um_add_addons_settings_tab', 8 );
	function um_add_addons_settings_tab($sections){
		global $ultimatemember;

		foreach( $ultimatemember->addons as $addon_id => $addon ) {
			$array[] = 	array(
				'id'       		=> 'addon_' . $addon_id,
				'type'     		=> 'switch',
				'title'   		=> $addon[0],
				'desc' 	   		=> $addon[1],
				'on'			=> __('Activated','ultimatemember'),
				'off'			=> __('Deactivated','ultimatemember'),
			);
		}
		
		$array = apply_filters('um_builtin_addons_options', $array );
		
		$sections[] = array(

			'icon'       => 'um-faicon-plug',
			'title'      => __( 'Add ons','ultimatemember'),
			'fields'     => $array

		);

		return $sections;
		
	}