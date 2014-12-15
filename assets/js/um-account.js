jQuery(document).ready(function() {

	var account_ = jQuery('.um-account-main').attr('data-current_tab');
	
	jQuery('.um-account-tab[data-tab='+account_+']').show();

	jQuery(document).on('click','.um-account-side li a',function(e){
		e.preventDefault();
		var link = jQuery(this);
		
		link.parents('ul').find('li a').removeClass('current');
		link.addClass('current');
		
		var url_ = jQuery(this).attr('href');
		var tab_ = jQuery(this).attr('data-tab');
		
		window.history.pushState("", "", url_);
		
		jQuery('.um-account-tab').hide();
		jQuery('.um-account-tab[data-tab='+tab_+']').show();
		
		return false;
	});
	
});