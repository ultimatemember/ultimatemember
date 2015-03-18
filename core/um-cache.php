<?php

class UM_Cache {

	function __construct() {

		add_action( 'init', array(&$this, 'do_not_cache' ) );
		
		add_action('um_admin_after_editing_role', array(&$this, 'delete_role_cache'), 10, 2 );
		
		$this->role_data = get_option('um_cache_role_data');
		
	}

	/***
	***	@needed for some cache plugins
	***/
	function do_not_cache() {
	
		if ( um_is_core_uri() ) {
			define( "DONOTCACHEPAGE", true );
		}
		
	}
	
	/***
	***	@clear cached role data
	***/
	function delete_role_cache( $post_id, $post ) {
		$role_slug = $post->post_name;
		if ( isset( $this->role_data[ $role_slug ] ) ) {
			unset( $this->role_data[ $role_slug ] );
			update_option('um_cache_role_data', $this->role_data);
		}
	}
	
	/***
	***	@get cached role data
	***/
	function role_data( $role_slug ) {
		if ( isset( $this->role_data[ $role_slug ] ) )
			return $this->role_data[ $role_slug ];
		return null;
	}
	
	/***
	***	@set role data cache
	***/
	function set_role_data( $role_slug, $array ) {
		$this->role_data[ $role_slug ] = $array;
		update_option('um_cache_role_data', $this->role_data);
	}

}