function um_init_helptips() {
	var helptips = jQuery( '.um-helptip' );
	if ( helptips.length > 0 ) {
		helptips.tooltip({
			tooltipClass: "um-helptip",
			content: function () {
				return jQuery( this ).attr( 'title' );
			}
		});
	}
}
