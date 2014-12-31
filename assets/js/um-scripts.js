jQuery(document).ready(function() {

	/* dropdown menu links */
	jQuery(document).on('click', '.um-dropdown a', function(e){
		e.preventDefault();
		return false;
	});
	
	/* trigger dropdown on click */
	jQuery(document).on('click', '.um-trigger-menu-on-click', function(e){
		e.preventDefault();
		jQuery('.um-dropdown').hide();
		menu = jQuery(this).find('.um-dropdown');
		menu.show();
		return false;
	});
	
	/* hide dropdown */
	jQuery(document).on('click', '.um-dropdown-hide', function(e){
		menu = jQuery(this).parents('.um-dropdown');
		menu.hide();
	});
	
	/* manual triggers */
	jQuery(document).on('click', 'a.um-manual-trigger', function(){
		var child = jQuery(this).attr('data-child');
		var parent = jQuery(this).attr('data-parent');
		jQuery(this).parents( parent ).find( child ).trigger('click');
	});
	
	/* tooltips */
	jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, live: true, offset: 3 });
	jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, live: true, offset: 3 });
	jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, live: true, offset: 3 });
	jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, live: true, offset: 3 });
		
	/* custom radio buttons */
	jQuery('.um-field-radio').mouseenter(function(){
		if (!jQuery(this).hasClass('active')) {
		jQuery(this).find('i').removeClass().addClass('um-icon-check-3');
		}
	}).mouseleave(function(){
		if (!jQuery(this).hasClass('active')) {
		jQuery(this).find('i').removeClass().addClass('um-icon-blank');
		}
	});
	
	jQuery('.um-field input[type=radio]').change(function(){
		var field = jQuery(this).parents('.um-field');
		var this_field = jQuery(this).parents('label');
		field.find('.um-field-radio').removeClass('active');
		field.find('.um-field-radio').find('i').removeClass('um-icon-check-3').addClass('um-icon-blank');
		this_field.addClass('active');
		this_field.find('i').removeClass('um-icon-blank').addClass('um-icon-check-3');
	});
	
	/* custom checkbox buttons */
	jQuery('.um-field-checkbox').mouseenter(function(){
		if (!jQuery(this).hasClass('active')) {
		jQuery(this).find('i').removeClass().addClass('um-icon-cross');
		}
	}).mouseleave(function(){
		if (!jQuery(this).hasClass('active')) {
		jQuery(this).find('i').removeClass().addClass('um-icon-blank');
		}
	});
	
	jQuery('.um-field input[type=checkbox]').change(function(){
		
		var field = jQuery(this).parents('.um-field');
		var this_field = jQuery(this).parents('label');
		if ( this_field.hasClass('active') ) {
		this_field.removeClass('active');
		this_field.find('i').addClas('um-icon-blank').removeClass('um-icon-cross');
		} else {
		this_field.addClass('active');
		this_field.find('i').removeClass('um-icon-blank').addClass('um-icon-cross');
		}

	});
	
	/* datepicker */
	jQuery('.um-datepicker').each(function(){
		elem = jQuery(this);
		
		if ( elem.attr('data-years_x') == 'past' ) {var max = true;}
		if ( elem.attr('data-years_x') == 'equal' ) {var max = false;}
		if ( elem.attr('data-years_x') == 'future' ) {var min = true;var max = '';}
		
		if ( elem.attr('data-disabled_weekdays') != '' ) {
			var disable = JSON.parse( elem.attr('data-disabled_weekdays') );
		} else {
			var disable = false;
		}
		
		if ( elem.attr('data-range') == 'date_range' ) {
			var min = new Date( elem.attr('data-date_min') );
			var max = new Date( elem.attr('data-date_max') );
		}
		
		elem.pickadate({
			selectYears: elem.attr('data-years'),
			min: min,
			max: max,
			disable: disable,
			format: elem.attr('data-format'),
			formatSubmit: 'yyyy/mm/dd',
			hiddenName: true,
		});
	});

	/* timepicker */
	jQuery('.um-timepicker').pickatime({
		formatSubmit: 'HH:i',
		hiddenSuffix: '__true'
	});
	
	/* rating field */
	jQuery('.um-rating').raty({
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
	
	/* rating read-only field */
	jQuery('.um-rating-readonly').raty({
		half: 		false,
		starType: 	'i',
		number: 	function() {return jQuery(this).attr('data-number');},
		score: 		function() {return jQuery(this).attr('data-score');},
		scoreName: 	function(){return jQuery(this).attr('data-key');},
		hints: 		false,
		readOnly: true
	});
	
	/* remove uploaded image */
	jQuery(document).on('click', '.um .um-single-image-preview a.cancel', function(e){
		e.preventDefault();
		var parent = jQuery(this).parents('.um-field');
		parent.find('.um-single-image-preview').hide();
		parent.find('.um-btn-auto-width').html('Upload');
		parent.find('input[type=hidden]').val('');
		return false;
	});
	
	/* remove uploaded file */
	jQuery(document).on('click', '.um .um-single-file-preview a.cancel', function(e){
		e.preventDefault();
		var parent = jQuery(this).parents('.um-field');
		parent.find('.um-single-file-preview').hide();
		parent.find('.um-btn-auto-width').html('Upload');
		parent.find('input[type=hidden]').val('');
		return false;
	});
	
	/* Nice Select Dropdown */
	jQuery(".um-s1").select2({
		allowClear: true,
		minimumResultsForSearch: 10
	});
	
	jQuery(".um-s2").select2({
		allowClear: false,
		minimumResultsForSearch: 10
	});
	
	jQuery('.um-s1,.um-s2').css({'display':'block'});
	
	/* Open New Group */
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
	
	/* Remove a group */
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

});