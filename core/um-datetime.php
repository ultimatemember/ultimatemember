<?php

class UM_DateTime {

	function __construct() {
	
	}
	
	/***
	***	@Display time in specific format
	***/
	function get_time( $format ) {
		return current_time( $format );
	}
	
	/***
	***	@Reformat dates
	***/
	function format($old, $new){
		$datetime = new DateTime($old);
		$output = $datetime->format( $new );
		return $output;
	}
	
	/***
	***	@Get last 30 days as array
	***/
	function get_last_days($num = 30, $reverse = true) {
		$d = array();
		for($i = 0; $i < $num; $i++) {
			$d[ date('Y-m-d', strtotime('-'. $i .' days')) ] = date('m/d', strtotime('-'. $i .' days'));
		}
		if ($reverse == true){
			return array_reverse($d);
		} else {
			return $d;
		}
	}

}