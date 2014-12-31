<?php

class UM_Profile {

	function __construct() {
	
	}
	
	function show_meta( $array ) {
		global $ultimatemember;
		$output = '';
		
		foreach( $array as $key ) {
			$data = '';
			if ( $key && um_user( $key ) ) {
				
				$value =  um_user( $key );
				$data = $ultimatemember->builtin->get_specific_field( $key );
				$type = (isset($data['type']))?$data['type']:'';
				
				$value = apply_filters("um_profile_field_filter_hook__", $value, $data );
				$value = apply_filters("um_profile_field_filter_hook__{$key}", $value, $data );
				$value = apply_filters("um_profile_field_filter_hook__{$type}", $value, $data );
				
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