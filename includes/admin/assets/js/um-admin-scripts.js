function um_admin_init_datetimepicker() {
	jQuery('.um-datepicker:not(.picker__input)').each(function(){
		elem = jQuery(this);

		if ( typeof elem.attr('data-disabled_weekdays') != 'undefined' && elem.attr('data-disabled_weekdays') != '' ) {
			var disable = JSON.parse( elem.attr('data-disabled_weekdays') );
		} else {
			var disable = false;
		}

		var years_n = null;
		if ( typeof elem.attr('data-years') != 'undefined' ) {
			years_n = elem.attr('data-years');
		}

		var minRange = elem.attr('data-date_min');
		var maxRange = elem.attr('data-date_max');

		var minSplit = [], maxSplit = [];
		if ( typeof minRange != 'undefined' ) {
			minSplit = minRange.split(",");
		}
		if ( typeof maxRange != 'undefined' ) {
			maxSplit = maxRange.split(",");
		}

		var min = minSplit.length ? new Date(minSplit) : null;
		var max = minSplit.length ? new Date(maxSplit) : null;

		// fix min date for safari
		if ( min && min.toString() == 'Invalid Date' && minSplit.length == 3 ) {
			var minDateString = minSplit[1] + '/' + minSplit[2] + '/' + minSplit[0];
			min = new Date(Date.parse(minDateString));
		}

		// fix max date for safari
		if ( max && max.toString() == 'Invalid Date' && maxSplit.length == 3 ) {
			var maxDateString = maxSplit[1] + '/' + maxSplit[2] + '/' + maxSplit[0];
			max = new Date(Date.parse(maxDateString));
		}

		var data = {
			disable: disable,
			format: elem.attr( 'data-format' ),
			formatSubmit: 'yyyy/mm/dd',
			hiddenName: true,
			onOpen: function() { elem.blur(); },
			onClose: function() { elem.blur(); }
		};

		if ( years_n !== null ) {
			data.selectYears = years_n;
		}

		if ( min !== null ) {
			data.min = min;
		}

		if ( max !== null ) {
			data.max = max;
		}

		elem.pickadate( data );
	});

	jQuery('.um-timepicker:not(.picker__input)').each(function(){
		elem = jQuery(this);

		elem.pickatime({
			format: elem.attr('data-format'),
			interval: parseInt( elem.attr('data-intervals') ),
			formatSubmit: 'HH:i',
			hiddenName: true,
			onOpen: function() { elem.blur(); },
			onClose: function() { elem.blur(); }
		});
	});
}

function um_init_tooltips() {
	if ( jQuery( '.um_tooltip' ).length > 0 ) {
		jQuery( '.um_tooltip' ).tooltip({
			tooltipClass: "um_tooltip",
			content: function () {
				return jQuery( this ).attr( 'title' );
			}
		});
	}
}


jQuery(document).ready(function() {

	/**
		clone a field dropdown
	**/
	jQuery(document.body).on('click', '.um-admin-clone', function(e){
		e.preventDefault();
		var container = jQuery(this).parents('.um-admin-field');
		var parent = jQuery(this).parents('p').find('.um-admin-field:last-child');
		container.find('select').select2('destroy');
		var cloned = container.clone();
		cloned.find('.um-admin-clone').replaceWith('<a href="#" class="um-admin-clone-remove button um-admin-tipsy-n" title="Remove Field"><i class="um-icon-close" style="margin-right:0!important"></i></a>');
		cloned.insertAfter( parent );
		cloned.find('select').val('');
		jQuery('.um-admin-field select').select2({
			allowClear: true,
			minimumResultsForSearch: 10
		});
		return false;
	});
	
	/**
		remove a field dropdown
	**/
	jQuery(document.body).on('click', '.um-admin-clone-remove', function(e){
		e.preventDefault();
		var container = jQuery(this).parents('.um-admin-field');
		jQuery('.tipsy').remove();
		container.remove();
		jQuery('.um-admin-field select').select2({
			allowClear: true,
			minimumResultsForSearch: 10
		});
		return false;
	});
	
	/**
		Ajax link
	**/
	jQuery('.um-admin-ajaxlink').click(function(e){
		e.preventDefault();
		return false;
	});
	
	/**
		On/Off Buttons
	**/
	jQuery(document.body).on('click', '.um-admin-yesno span.btn', function(){
		if (!jQuery(this).parents('p').hasClass('disabled-on-off')){
		if ( jQuery(this).parent().find('input[type=hidden]').val() == 0 ){
			update_val = 1;
			jQuery(this).animate({'left': '48px'}, 200);
			jQuery(this).parent().find('input[type=hidden]').val( update_val ).trigger('change');
		} else {
			update_val = 0;
			jQuery(this).animate({'left': '0'}, 200);
			jQuery(this).parent().find('input[type=hidden]').val( update_val ).trigger('change');
		}
		}
	});
	
	/**
		WP Color Picker
	**/
	if ( jQuery('.um-admin-colorpicker').length ) {
		jQuery('.um-admin-colorpicker').wpColorPicker();
	}

	
	/**
		Tooltips
	**/
	um_init_tooltips();
	
	if( typeof tipsy !== 'undefined' ){
		jQuery('.um-admin-tipsy-n').tipsy({gravity: 'n', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-w').tipsy({gravity: 'w', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-e').tipsy({gravity: 'e', opacity: 1, live: 'a.live' });
		jQuery('.um-admin-tipsy-s').tipsy({gravity: 's', opacity: 1, live: 'a.live' });
	}

	
	/**
		Conditional fields
	**/
	jQuery( document.body ).on('change', '.um-adm-conditional', function(){

		var value;
		if ( jQuery(this).attr("type") == 'checkbox' ) {
			value = jQuery(this).is(':checked') ? 1 : 0;
		} else {
			value = jQuery(this).val();
		}

		if ( jQuery(this).data('cond1') ) {
			if ( value == jQuery(this).data('cond1') ) {
				jQuery('.' + jQuery(this).data('cond1-show') ).show();
				jQuery('.' + jQuery(this).data('cond1-hide') ).hide();

				if ( jQuery(this).data('cond1-show') == '_roles' ) {
					return false;
				}

			} else {
				jQuery('.' + jQuery(this).data('cond1-show') ).hide();
				jQuery('.' + jQuery(this).data('cond1-hide') ).show();
			}
		}
		
		if ( jQuery(this).data('cond2') ) {
			if ( value == jQuery(this).data('cond2') ) {
				jQuery('.' + jQuery(this).data('cond2-show') ).show();
				jQuery('.' + jQuery(this).data('cond2-hide') ).hide();

				if ( jQuery(this).data('cond2-show') == '_roles' ) {
					return false;
				}

			} else {
				jQuery('.' + jQuery(this).data('cond2-show') ).hide();
				jQuery('.' + jQuery(this).data('cond2-hide') ).show();
			}
		}
		
		if ( jQuery(this).data('cond3') ) {
			if ( value == jQuery(this).data('cond3') ) {
				jQuery('.' + jQuery(this).data('cond3-show') ).show();
				jQuery('.' + jQuery(this).data('cond3-hide') ).hide();
			} else {
				jQuery('.' + jQuery(this).data('cond3-show') ).hide();
				jQuery('.' + jQuery(this).data('cond3-hide') ).show();
			}
		}
		
	});jQuery('.um-adm-conditional').each(function(){jQuery(this).trigger('change');});
	
	/**
		Conditional fields for
		Radio Group
	**/
	jQuery('.um-conditional-radio-group input[type=radio]').click(function(){
		var holder = jQuery('.um-conditional-radio-group');
		
		var val = jQuery(this).val();
		var cond1 = holder.data('cond1');
		var show1 = holder.data('cond1-show');
		if ( val == cond1 ) { // condition met
			jQuery('.' + show1).show();
		} else {
			jQuery('.' + show1).hide();
		}
		
		var val2 = jQuery(this).val();
		var cond2 = holder.data('cond2');
		var show2 = holder.data('cond2-show');
		if ( val2 == cond2 ) { // condition met
			jQuery('.' + show2).show();
		} else {
			jQuery('.' + show2).hide();
		}
		
	});jQuery('.um-conditional-radio-group input[type=radio]:checked').each(function(){jQuery(this).trigger('click');});



	/**
		Conditional fields for
		nav-menu editor options
	**/
	
	jQuery('.um-nav-mode').each( function() {
		if ( jQuery(this).find('select').val() == 2 ) {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').show();
		} else {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').hide();
		}
	});


	jQuery( document.body ).on('change', '.um-nav-mode select', function(){
		if ( jQuery(this).val() == 2 ) {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').show();
		} else {
			jQuery(this).parents('.um-nav-edit').find('.um-nav-roles').hide();
		}
	});
});