var um_dropdown_triggers = {};

function um_init_new_dropdown() {
	jQuery('.um-new-dropdown').each( function() {
		var menu = jQuery(this);

		var is_inited = menu.data( 'um-dropdown-inited' );
		if ( is_inited ) {
			return;
		}

		var element = menu.data('element');
		var trigger = menu.data('trigger');

		menu.data( 'um-dropdown-inited', true );

		if ( -1 === jQuery.inArray( element, um_dropdown_triggers[ trigger ] ) ) {
			jQuery( document.body ).on( trigger, element, function(e) {
			var obj = jQuery(this);

			if ( obj.data( 'um-new-dropdown-show' ) === true ) {
				obj.data( 'um-new-dropdown-show', false );
				obj.find( '.um-new-dropdown' ).hide();
			} else {
				jQuery('.um-new-dropdown').hide();
				jQuery('.um-new-dropdown').parent().data( 'um-new-dropdown-show', false );

				if ( ! obj.find( '.um-new-dropdown' ).length ) {
					var dropdown_layout = menu.clone();

					// dropdown_layout.css({
					// 	top : '20px',
					// 	width: '150px',
					// 	right: 0
					// });

					obj.append( dropdown_layout );

					obj.trigger( 'fmwp_dropdown_render', { dropdown_layout:dropdown_layout, trigger:trigger, element:element, obj:obj} );

					dropdown_layout.show();
				} else {
					obj.find( '.um-new-dropdown' )./*css({
						top : '20px',
						width: '150px',
						right: 0
					}).*/show();
				}

				obj.data( 'um-new-dropdown-show', true );

				jQuery( document.body ).bind( 'click', function( event ) {
				
					if ( jQuery('.um-new-dropdown').find( '.' + jQuery( event.target ).attr('class').trim().replace( ' ', '.' ) ).length === 0 &&
						

						jQuery( '.' + jQuery(event.target).attr('class').trim() ) !== element ) {
						//event = ev;
						jQuery('.um-new-dropdown').hide();
						jQuery('.um-new-dropdown').parent().data( 'um-new-dropdown-show', false );
						jQuery( document.body ).unbind( event );

					}
				});
			}
		});

			if ( typeof um_dropdown_triggers[ trigger ] == 'undefined' ) {
				um_dropdown_triggers[ trigger ] = [];
			}
			um_dropdown_triggers[ trigger ].push( element );
		}
	});
}

jQuery( document ).ready( function($) {
	um_init_new_dropdown();

	jQuery( document.body ).on( 'click', '.um-new-dropdown a', function(e) {
		jQuery(this).parents('.um-new-dropdown').hide();
		jQuery(this).parents('.um-new-dropdown').parent().data( 'um-new-dropdown-show', false );
		jQuery('body').trigger('click');
		e.stopPropagation();
	});
});