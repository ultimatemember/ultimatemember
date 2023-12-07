jQuery(document).ready(function ($) {
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

	if ( $('#tmpl-um-nav-menus-fields').length ) {
		var template = wp.template( 'um-nav-menus-fields' );

		$( document ).on( 'menu-item-added', function ( e, $menuMarkup ) {
			var id = $( $menuMarkup ).attr('id').substr(10);

			var template_content = template({
				menuItemID: id,
				restriction_data:{
					um_nav_public:0,
					um_nav_roles:[]
				}
			});

			if ( $( $menuMarkup ).find( 'fieldset.field-move' ).length > 0 ) {
				$( $menuMarkup ).find( 'fieldset.field-move' ).before( template_content );
			} else {
				$( $menuMarkup ).find( '.menu-item-actions' ).before( template_content );
			}
		});

		$( 'ul#menu-to-edit > li' ).each( function(){
			var id = $(this).attr('id').substr(10);
			var template_content = template({
				menuItemID: id,
				restriction_data: um_menu_restriction_data[ id ]
			});

			if ( $( this ).find( 'fieldset.field-move' ).length > 0 ) {
				$( this ).find( 'fieldset.field-move' ).before( template_content );
			} else {
				$( this ).find( '.menu-item-actions' ).before( template_content );
			}
		});
	}
});
