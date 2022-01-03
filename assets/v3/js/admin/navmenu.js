// wp-admin scripts that must be enqueued on Appearance > Menus screen

jQuery(document).ready( function() {

	jQuery('.um-nav-mode').each( function() {
		if ( jQuery(this).find('select').val() == 2 ) {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').show();
		} else {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').hide();
		}
	});


	jQuery( document.body ).on('change', '.um-nav-mode select', function() {
		if ( jQuery(this).val() == 2 ) {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').show();
		} else {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').hide();
		}
	});


	var template = wp.template( 'um-nav-menus-fields' );

	jQuery( document ).on( 'menu-item-added', function ( e, $menuMarkup ) {
		var id = jQuery( $menuMarkup ).attr('id').substr(10);

		var template_content = template({
			menuItemID: id,
			restriction_data:{
				um_nav_public:0,
				um_nav_roles:[]
			}
		});

		if ( jQuery( $menuMarkup ).find( 'fieldset.field-move' ).length > 0 ) {
			jQuery( $menuMarkup ).find( 'fieldset.field-move' ).before( template_content );
		} else {
			jQuery( $menuMarkup ).find( '.menu-item-actions' ).before( template_content );
		}
	});


	jQuery( 'ul#menu-to-edit > li' ).each( function() {
		var id = jQuery(this).attr('id').substr(10);
		var template_content = template({
			menuItemID: id,
			restriction_data: um_menu_restriction_data[ id ]
		});

		if ( jQuery( this ).find( 'fieldset.field-move' ).length > 0 ) {
			jQuery( this ).find( 'fieldset.field-move' ).before( template_content );
		} else {
			jQuery( this ).find( '.menu-item-actions' ).before( template_content );
		}
	});

});
