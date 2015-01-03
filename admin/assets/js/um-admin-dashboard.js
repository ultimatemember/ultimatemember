jQuery(document).ready(function() {

	var active_tab = jQuery('.um-admin-dash-nav a.active').attr('data-rel');
	jQuery('.um-admin-dash-content').hide();
	jQuery('.um-admin-dash-content#'+active_tab).show();
	
	chart_ready = 0;
	
	jQuery(document).on('click', '.um-admin-dash-nav a', function(e){
		e.preventDefault();
		active_tab = jQuery(this).attr('data-rel');
		jQuery('.um-admin-dash-nav a').removeClass('active');
		jQuery(this).addClass('active');
		jQuery('.um-admin-dash-content').hide();
		jQuery('.um-admin-dash-content#'+active_tab).show();
		if ( chart_ready == 0 ) {
			draw_linechart();
			chart_ready = 1;
		}
		return false;
	});
	
	if ( jQuery('.um-admin-dash-body').length && ( jQuery('.updated').length || jQuery('.error').length ) )  {
		jQuery('.updated,.error').prependTo('.um-admin-dash-body').show();
	}
	
});