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
				
				$value = um_filtered_value( $key );
				
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