<?php

class UM_Profile {

	function __construct() {
	
	}
	
	function show_meta( $array ) {
		$output = '';
		
		foreach( $array as $k ) {
			if ( $k ) {
				$items[] = '<span>' . um_user( $k ) . '</span>';
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