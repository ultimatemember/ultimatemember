<?php

class UM_Cache {

	function __construct() {

		add_action( 'init', array(&$this, 'do_not_cache' ) );
		
	}

	function do_not_cache() {
	
		if ( um_is_core_uri() ) {
			define( "DONOTCACHEPAGE", true );
		}
		
	}

}