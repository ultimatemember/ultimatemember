<?php

class UM_Profile {

	function __construct() {
	
	}
	
	function show_meta( $array ) {
		global $ultimatemember;
		$output = '';
		
		foreach( $array as $k ) {
			$data = '';
			if ( $k && um_user( $k ) ) {
				
				$value =  um_user( $k );
				$data = $ultimatemember->builtin->get_specific_field( $k );
				$value = apply_filters("um_profile_field_filter_hook__", $value, $data );
				
				$items[] = '<span>' . $value . '</span>';
				$items[] = '<span class="b">&bull;</span>';
				
			}
		}

		if ( isset( $items ) ) {
			array_pop($items);
			foreach( $items as $item ) {
				$output .= $item;
			}
		}

		return $output;
	}

}