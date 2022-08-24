if ( typeof ( window.UM ) !== 'object' ) {
	window.UM = {};
}

if ( typeof ( UM.forms ) !== 'object' ) {
	UM.forms = {};
}

UM.forms = {
	honeypot: function () {
		// flush fields using honeypot security
		jQuery('input[name="<?php echo esc_js( UM()->honeypot ); ?>"]').val('');
	}
};

jQuery( window ).on( 'load', function() {
	UM.forms.honeypot();
});
