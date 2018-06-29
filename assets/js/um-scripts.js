/*jQuery('body').on('error', '.um-avatar', function() {
	if( jQuery(this).data('load-error') != undefined ) return;
	jQuery(this).data('load-error', '1').attr('src', jQuery(this).data('default'));
});*/
jQuery(document).ready(function() {


	//Profile & Account Page
	jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, live: 'a.live', offset: 3 });
	jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, live: 'a.live', offset: 3 });
	jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, live: 'a.live', offset: 3 });
	jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, live: 'a.live', offset: 3 });

	//Profile & Account Page
	jQuery(document).on('change', '.um-field-area input[type="radio"]', function(){
		var field = jQuery(this).parents('.um-field-area');
		var this_field = jQuery(this).parents('label');
		field.find('.um-field-radio').removeClass('active');
		field.find('.um-field-radio').find('i').removeClass().addClass('um-icon-android-radio-button-off');
		this_field.addClass('active');
		this_field.find('i').removeClass().addClass('um-icon-android-radio-button-on');
	});

	//Profile & Account Page
	jQuery(document).on('change', '.um-field-area input[type="checkbox"]', function(){
		var this_field = jQuery(this).parents('label');
		if ( this_field.hasClass('active') ) {
			this_field.removeClass('active');
			this_field.find('i').removeClass().addClass('um-icon-android-checkbox-outline-blank');
		} else {
			this_field.addClass('active');
			this_field.find('i').removeClass().addClass('um-icon-android-checkbox-outline');
		}
	});



	jQuery('.um-datepicker').each( function(){
		elem = jQuery(this);

		var disable = false;
		if ( elem.attr('data-disabled_weekdays') != '' ) {
			disable = JSON.parse( elem.attr('data-disabled_weekdays') );
		}

		var years_n = elem.attr('data-years');

		var minRange = elem.attr('data-date_min');
		var maxRange = elem.attr('data-date_max');

		var minSplit = minRange.split(",");
		var maxSplit = maxRange.split(",");

		var min = minSplit.length ? new Date(minSplit) : null;
		var max = minSplit.length ? new Date(maxSplit) : null;

		// fix min date for safari
		if(min && min.toString() == 'Invalid Date' && minSplit.length == 3) {
			var minDateString = minSplit[1] + '/' + minSplit[2] + '/' + minSplit[0];
			min = new Date(Date.parse(minDateString));
		}

		// fix max date for safari
		if(max && max.toString() == 'Invalid Date' && maxSplit.length == 3) {
			var maxDateString = maxSplit[1] + '/' + maxSplit[2] + '/' + maxSplit[0];
			max = new Date(Date.parse(maxDateString));
		}

		elem.pickadate({
			selectYears: years_n,
			min: min,
			max: max,
			disable: disable,
			format: elem.attr('data-format'),
			formatSubmit: 'yyyy/mm/dd',
			hiddenName: true,
			onOpen: function() { elem.blur(); },
			onClose: function() { elem.blur(); }
		});
	});

	jQuery('.um-timepicker').each( function(){
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

	jQuery('.um-rating').um_raty({
		half: 		false,
		starType: 	'i',
		number: 	function() {return jQuery(this).attr('data-number');},
		score: 		function() {return jQuery(this).attr('data-score');},
		scoreName: 	function(){return jQuery(this).attr('data-key');},
		hints: 		false,
		click: function(score, evt) {
			live_field = this.id;
			live_value = score;
			um_conditional();
		}
	});

	jQuery('.um-rating-readonly').um_raty({
		half: 		false,
		starType: 	'i',
		number: 	function() {return jQuery(this).attr('data-number');},
		score: 		function() {return jQuery(this).attr('data-score');},
		scoreName: 	function(){return jQuery(this).attr('data-key');},
		hints: 		false,
		readOnly: true
	});

	//Profile & Account Page
	jQuery('.um-s1,.um-s2').css({'display':'block'});
	//Profile & Account Page
	jQuery(".um-s1").select2({
		allowClear: true
	});
	//Profile & Account Page
	jQuery(".um-s2").select2({
		allowClear: false,
		minimumResultsForSearch: 10
	});

	//Profile Page
	jQuery(document).on('click', '.um-field-group-head:not(.disabled)', function(){
		var field = jQuery(this).parents('.um-field-group');
		var limit = field.data('max_entries');

		if ( field.find('.um-field-group-body').is(':hidden')){
			field.find('.um-field-group-body').show();
		} else {
			field.find('.um-field-group-body:first').clone().appendTo( field );
		}

		increase_id = 0;
		field.find('.um-field-group-body').each(function(){
			increase_id++;
			jQuery(this).find('input').each(function(){
				var input = jQuery(this);
				input.attr('id', input.data('key') + '-' + increase_id );
				input.attr('name', input.data('key') + '-' + increase_id );
				input.parent().parent().find('label').attr('for', input.data('key') + '-' + increase_id );
			});
		});

		if ( limit > 0 && field.find('.um-field-group-body').length == limit ) {

			jQuery(this).addClass('disabled');

		}
	});

	//Profile Page
	jQuery(document).on('click', '.um-field-group-cancel', function(e){
		e.preventDefault();
		var field = jQuery(this).parents('.um-field-group');

		var limit = field.data('max_entries');

		if ( field.find('.um-field-group-body').length > 1 ) {
		jQuery(this).parents('.um-field-group-body').remove();
		} else {
		jQuery(this).parents('.um-field-group-body').hide();
		}

		if ( limit > 0 && field.find('.um-field-group-body').length < limit ) {
			field.find('.um-field-group-head').removeClass('disabled');
		}

		return false;
	});

	//Profile Page
	jQuery(document).on('click', '.um-ajax-paginate', function(e){
		e.preventDefault();
		var parent = jQuery(this).parent();
		parent.addClass('loading');
		var args = jQuery(this).data('args');
		var hook = jQuery(this).data('hook');
		var container = jQuery(this).parents('.um').find('.um-ajax-items');

		wp.ajax.send( 'um_ajax_paginate', {
			data: {
				hook: hook,
				args: args,
				nonce: um_scripts.nonce
			},
			complete: function() {
				parent.removeClass('loading');
			},
			success: function( data ) {
				parent.remove();
				container.append( data );
			}
		});

		return false;
	});

	//Profile Page (bbPress temlate) to-do: transfer to bbPress
	jQuery(document).on('click', '.um-ajax-action', function(e){
		e.preventDefault();
		var hook = jQuery(this).data('hook');
		var user_id = jQuery(this).data('user_id');
		var arguments = jQuery(this).data('arguments');

		if ( jQuery(this).data('js-remove') ){
			jQuery(this).parents('.'+jQuery(this).data('js-remove')).fadeOut('fast');
		}

		wp.ajax.send( 'um_muted_action', {
			data: {
				hook: hook,
				user_id: user_id,
				arguments: arguments,
				nonce: um_scripts.nonce
			},
			success: function(data){

			}
		});

		return false;
	});

	jQuery('.um-form input[class="um-button"][type="submit"]').removeAttr( 'disabled' );

	//Profile Form
	jQuery(document).one('click', '.um:not(.um-account) .um-form input[class="um-button"][type="submit"]:not(.um-has-recaptcha)', function() {
		jQuery(this).attr('disabled','disabled');
		jQuery(this).parents('form').submit();
	});

});
