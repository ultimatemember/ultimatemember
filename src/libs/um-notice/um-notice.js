(function( $, undefined ) {
	var options;
	var $count = -1;
	var $step_size = 10;

	var default_options = {
		'message'    : '',
		'type'       : 'update',
		'dismissible': false,
		'param'      : '', // maybe can be displayed via $_GET param in url.
		'timeout'    : 3000 // or displayed via JS code and hidden in timeout interval.
	};

	var methods = {
		init : function( settings ) {
			//merge default & current options
			options = $.extend( {}, default_options, settings );

			$( this ).data( 'options', options );

			let html = '<span class="um-notice-message-text"></span>' +
				'<span class="um-notice-message-progress"></span>';
			if ( false !== options.dismissible ) {
				html =  '<span class="um-notice-message-text"></span><span class="um-notice-message-dismiss"></span>' +
					'<span class="um-notice-message-progress"></span>'
			}

			var obj = $( '<div class="um-notice-message"></div>').appendTo( 'body' ).html( html );

			obj.find( '.um-notice-message-text' ).html( options.message );

			var $type = 'update';
			if ( options.type ) {
				$type = options.type;
			}

			obj.addClass( 'um-notice-message-' + $type );

			obj.show();

			$count++;

			var $notice_size = obj.height() + 25;

			if ( 0 < $count ) {
				$step_size = $step_size + $notice_size;
			}

			obj.animate({
				bottom: $step_size
			}, 1000);

			if ( false === options.dismissible ) {
				setTimeout( function () {
					obj.fadeOut( 1000, function () {
						obj.remove();

						if ( 0 < $count ) {
							$step_size = $step_size - $notice_size;
						}

						$count--;
					});
				}, options.timeout );
			}

			//methods.show.apply();
		},
		show : function() {

		},
		close : function() {

		}
	};

	$.fn.um_notice = function( method ) {
		if ( methods[ method ] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist for jQuery.um_notice plugin' );
		}
	};

})( jQuery );
