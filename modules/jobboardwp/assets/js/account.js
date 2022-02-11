wp.hooks.addAction( 'um_after_account_tab_changed', 'um_jobboardwp', function( tab_ ) {
	if ( 'jobboardwp' === tab_ ) {
		jb_responsive();
	}
});

wp.hooks.addAction( 'um_account_active_tab_inited', 'um_jobboardwp', function( tab_ ) {
	if ( 'jobboardwp' === tab_ ) {
		jb_responsive();
	}
});