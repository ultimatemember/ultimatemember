jQuery(document).ready(function() {

	/* Tooltips */
	jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, live: true, offset: 3 });
	jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, live: true, offset: 3 });
	jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, live: true, offset: 3 });
	jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, live: true, offset: 3 });

	/* Custom Radio Buttons */
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
	
	/* Custom Checkbox Buttons */
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
	
	/* Date picker */
	jQuery('.um-datepicker').pickadate({
		min: [1900,1,1],
		max: true,
		selectYears: 100,
	    formatSubmit: 'yyyy/mm/dd',
		hiddenSuffix: '__true'
	});

	/* Time picker */
	jQuery('.um-timepicker').pickatime({
		formatSubmit: 'HH:i',
		hiddenSuffix: '__true'
	});
	
	/* Rating field */
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
	
	jQuery('.um-rating-readonly').raty({
		half: 		false,
		starType: 	'i',
		number: 	function() {return jQuery(this).attr('data-number');},
		score: 		function() {return jQuery(this).attr('data-score');},
		scoreName: 	function(){return jQuery(this).attr('data-key');},
		hints: 		false,
		readOnly: true
	});
	
	/* Image Upload */
	jQuery(".um-single-image-upload").each(function(){
	
		var trigger = jQuery(this);
		
		if (trigger.data('upload_help_text')){
			upload_help_text = '<span class="help">' + trigger.data('upload_help_text') + '</span>';
		} else {
			upload_help_text = '';
		}
		
		if ( trigger.data('icon') ) {
			icon = '<span class="icon"><i class="'+ trigger.data('icon') + '"></i></span>';
		} else {
			icon = '';
		}

		if ( trigger.data('upload_text') ) {
			upload_text = '<span class="str">' + trigger.data('upload_text') + '</span>';
		} else {
			upload_text = '';
		}
		
		trigger.uploadFile({
			url: ultimatemember_image_upload_url,
			method: "POST",
			multiple: false,
			formData: {key: trigger.data('key'), set_id: trigger.data('set_id'), set_mode: trigger.data('set_mode') },
			fileName: trigger.data('key'),
			allowedTypes: trigger.data('allowed_types'),
			maxFileSize: trigger.data('max_size'),
			dragDropStr: icon + upload_text + upload_help_text,
			sizeErrorStr: trigger.data('max_size_error'),
			extErrorStr: trigger.data('extension_error'),
			maxFileCountErrorStr: trigger.data('max_files_error'),
			maxFileCount: 1,
			showDelete: false,
			showAbort: false,
			showDone: false,
			showFileCounter: false,
			showStatusAfterSuccess: false,
			onSubmit:function(files){
			
				trigger.parents('.um-field').find('.um-error-block').remove();
				
			},
			onSuccess:function(files,data,xhr){
			
				trigger.selectedFiles = 0;
				
				data = jQuery.parseJSON(data);
				if (data.error && data.error != '') {

					trigger.parents('.um-field').append('<div class="um-error-block">'+data.error+'</div>');
					
				} else {

					trigger.parents('.um-field').find('.ajax-upload-dragdrop').fadeOut( function() {
						trigger.parents('.um-field').find('.um-single-image-preview').fadeIn();
					});

					jQuery.each( data, function(key, value) {
						trigger.parents('.um-field').find('.um-single-image-preview img').attr('src', value);
					});
				
				}
				
			}
		});
		
	});
	
	/* Remove a single image upload */
	jQuery(document).on('click', '.um-single-image-preview a.cancel', function(e){
		e.preventDefault();
		
		var trigger = jQuery(this).parents('.um-field').find('.um-single-image-upload');

		trigger.parents('.um-field').find('.um-single-image-preview').fadeOut(function(){
			trigger.parents('.um-field').find('.ajax-upload-dragdrop').fadeIn();
		});
		
		return false;
	});
	
	/* File Upload */
	jQuery(".um-single-file-upload").each(function(){
	
		var trigger = jQuery(this);
		
		if (trigger.data('upload_help_text')){
			upload_help_text = '<span class="help">' + trigger.data('upload_help_text') + '</span>';
		} else {
			upload_help_text = '';
		}
		
		if ( trigger.data('icon') ) {
			icon = '<span class="icon"><i class="'+ trigger.data('icon') + '"></i></span>';
		} else {
			icon = '';
		}

		if ( trigger.data('upload_text') ) {
			upload_text = '<span class="str">' + trigger.data('upload_text') + '</span>';
		} else {
			upload_text = '';
		}
		
		trigger.uploadFile({
			url: ultimatemember_file_upload_url,
			method: "POST",
			multiple: false,
			formData: {key: trigger.data('key'), set_id: trigger.data('set_id'), set_mode: trigger.data('set_mode') },
			fileName: trigger.data('key'),
			allowedTypes: trigger.data('allowed_types'),
			maxFileSize: trigger.data('max_size'),
			dragDropStr: icon + upload_text + upload_help_text,
			sizeErrorStr: trigger.data('max_size_error'),
			extErrorStr: trigger.data('extension_error'),
			maxFileCountErrorStr: trigger.data('max_files_error'),
			maxFileCount: 1,
			showDelete: false,
			showAbort: false,
			showDone: false,
			showFileCounter: false,
			showStatusAfterSuccess: false,
			onSubmit:function(files){
			
				trigger.parents('.um-field').find('.um-error-block').remove();
				
			},
			onSuccess:function(files,data,xhr){
			
				trigger.selectedFiles = 0;
				
				data = jQuery.parseJSON(data);
				if (data.error && data.error != '') {

					trigger.parents('.um-field').append('<div class="um-error-block">'+data.error+'</div>');
					
				} else {
				
					trigger.parents('.um-field').find('.ajax-upload-dragdrop').fadeOut( function() {
						trigger.parents('.um-field').find('.um-single-file-preview').fadeIn();
					});

					jQuery.each( data, function(key, value) {
						
						if (key == 'icon') {
						trigger.parents('.um-field').find('.um-single-fileinfo i').removeClass().addClass(value);
						} else if ( key == 'icon_bg' ) {
						trigger.parents('.um-field').find('.um-single-fileinfo span.icon').css({'background-color' : value } );
						} else if ( key == 'filename' ) {
						trigger.parents('.um-field').find('.um-single-fileinfo span.filename').html(value);
						} else {
						trigger.parents('.um-field').find('.um-single-fileinfo a').attr('href', value);
						}
						
					});
				
				}
				
			}
		});
		
	});
	
	/* Remove a single file upload */
	jQuery(document).on('click', '.um-single-file-preview a.cancel', function(e){
		e.preventDefault();
		
		var trigger = jQuery(this).parents('.um-field').find('.um-single-file-upload');

		trigger.parents('.um-field').find('.um-single-file-preview').fadeOut(function(){
			trigger.parents('.um-field').find('.ajax-upload-dragdrop').fadeIn();
		});

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