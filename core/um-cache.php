<?php

class UM_Cache {

	function __construct() {

		add_action( 'init', array(&$this, 'do_not_cache' ) );
		
	}

	/***
	***	@needed for some cache plugins
	***/
	function do_not_cache() {
	
		if ( um_is_core_uri() && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( "DONOTCACHEPAGE", true );
		}
		
	}

}