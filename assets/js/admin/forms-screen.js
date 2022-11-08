jQuery(document).ready( function() {
	if ( jQuery( '.page-title-action' ).length > 0 ) {
		var um_form_buttons_html = '';
		jQuery.each( um_forms_buttons, function(i) {
			um_form_buttons_html += '<a href="' + um_forms_buttons[i].link  + '" class="page-title-action">' + um_forms_buttons[i].title + '</a>';
		});
		jQuery( '.page-title-action' ).replaceWith( um_form_buttons_html );
	}
});
